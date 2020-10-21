# BEGIN COPYRIGHT BLOCK
# This Program is free software; you can redistribute it and/or modify it under
# the terms of the GNU General Public License as published by the Free Software
# Foundation; version 2 of the License.
#
# This Program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along with
# this Program; if not, write to the Free Software Foundation, Inc., 59 Temple
# Place, Suite 330, Boston, MA 02111-1307 USA.
#
# Copyright (C) 2007 Red Hat, Inc.
# All rights reserved.
# END COPYRIGHT BLOCK
#

package AdminServer;
require Exporter;
@ISA       = qw(Exporter);
@EXPORT    = qw(createAdminServer reconfigAdminServer
                createASFilesAndDirs setFileOwnerPerms updateHttpConfFiles
                startAdminServer stopAdminServer removeAdminServer setDefaults
                updateSelinuxPolicy);
@EXPORT_OK = qw(createAdminServer reconfigAdminServer
                createASFilesAndDirs setFileOwnerPerms updateHttpConfFiles
                startAdminServer stopAdminServer removeAdminServer setDefaults
                updateSelinuxPolicy);

use File::Path;
use File::Copy;
use File::Basename;
use File::Temp;

my $template_backup_dir = "tmpdirXXXX";
my $secfile_backup_dir = "";

# tempfiles
use File::Temp qw(tempfile tempdir);

# load perldap
use Mozilla::LDAP::Conn;
use Mozilla::LDAP::Utils qw(normalizeDN);
use Mozilla::LDAP::API qw(ldap_url_parse);
use Mozilla::LDAP::LDIF;

use DSUtil;
use Inf;
use Setup;
use AdminUtil;

sub setDefaults {
    my $setup = shift;

    if (!defined($setup->{inf}->{admin}->{ServerIpAddress})) {
        $setup->{inf}->{admin}->{ServerIpAddress} = '0.0.0.0';
    }
    if (!defined($setup->{inf}->{admin}->{Port})) {
        $setup->{inf}->{admin}->{Port} = 9830;
    }
    if (!defined($setup->{inf}->{admin}->{SysUser})) {
        my $user = $setup->{inf}->{General}->{SuiteSpotUserID};
        if (!defined($user)) {
            if ($> == 0) { # if root, use the default user
                $user = "nobody";
            } else { # if not root, use the user's uid
                $user = getLogin;
            }
        }
        $setup->{inf}->{admin}->{SysUser} = $user;
    }
    if (!defined($setup->{inf}->{admin}->{ServerAdminID})) {
        my $id = $setup->{inf}->{General}->{ConfigDirectoryAdminID};
        if (isValidDN($id)) {
            $id =~ s/^(.*)=.*/$1/;
        }
        $setup->{inf}->{admin}->{ServerAdminID} = $id;
    }
    if (!defined($setup->{inf}->{admin}->{ServerAdminPwd})) {
        my $pwd = $setup->{inf}->{General}->{ConfigDirectoryAdminPwd};
        $setup->{inf}->{admin}->{ServerAdminPwd} = $pwd;
    }

    return 1;
}

sub checkRequiredParameters {
    my $setup = shift;
    for my $asparam (qw(ServerIpAddress Port SysUser ServerAdminID ServerAdminPwd)) {
        if (!defined($setup->{inf}->{admin}->{$asparam})) {
            $setup->msg($FATAL, "missing_adminserver_param", $asparam);
            return 0;
        }
    }

    for my $general (qw(AdminDomain SuiteSpotUserID SuiteSpotGroup ConfigDirectoryLdapURL)) {
        if (!defined($setup->{inf}->{General}->{$general})) {
            $setup->msg($FATAL, "missing_general_param", $general);
            return 0;
        }
    }

    return 1;
}

sub usingSELinux {
    my $mydevnull = (-c "/dev/null" ? " /dev/null " : " NUL ");

    if ((getLogin() eq 'root') and "yes" and -f "/usr/sbin/sestatus" and
        !system ("/usr/sbin/sestatus | egrep -i \"selinux status:\\s*enabled\" > $mydevnull 2>&1")) {
        # We are using SELinux
        return 1;
    }
    return 0;
}

sub setFileOwnerPerms {
    my $setup = shift;
    my $configdir = shift;
    my $admConf = getAdmConf($configdir);
    my $uid = getpwnam $admConf->{sysuser};

    # chown the config directory
    $! = 0; # clear errno
    chown $uid, -1, $configdir;
    if ($!) {
        $setup->msg($FATAL, 'error_chowning_file', $configdir,
                    $admConf->{sysuser}, $!);
        return 0;
    }

    # chown and chmod other files appropriately
    for (glob("$configdir/*")) {
        # these are owned by root
        next if (/httpd.conf$/);
        next if (/nss.conf$/);
        next if (/admserv.conf$/);
        next if (! -f $_); # should never happen
        # all other files should be owned by SysUser
        $! = 0; # clear errno
        chown $uid, -1, $_;
        if ($!) {
            $setup->msg($FATAL, 'error_chowning_file', $_,
                        $admConf->{sysuser}, $!);
            return 0;
        }
        # the files should be writable
        $! = 0; # clear errno
        chmod 0600, $_;
        if ($!) {
            $setup->msg($FATAL, 'error_chmoding_file', $_, $!);
            return 0;
        }
    }

    return 1;
}

sub createASFilesAndDirs {
    my $setup = shift;
    my $configdir = shift;
    my $securitydir = shift;
    my $logdir = shift;
    my $rundir = shift;

    my $uid = getpwnam $setup->{inf}->{admin}->{SysUser};
    my $gid = getgrnam $setup->{inf}->{General}->{SuiteSpotGroup};

    $setup->msg('create_adminserver_filesdirs');

    # these paths are owned exclusively by admin sever
    my @errs;
    for ($configdir, $securitydir, $logdir) {
        @errs = makePaths($_, 0700, $setup->{inf}->{admin}->{SysUser},
                          $setup->{inf}->{General}->{SuiteSpotGroup});
        if (@errs) {
            $setup->msg($FATAL, @errs);
            return 0;
        }
        $! = 0; # clear errno
        chmod 0700, $_;
        if ($!) {
            $setup->msg($FATAL, 'error_chmoding_directory', $_, $!);
            return 0;
        }
        chown $uid, -1, $_;
        if ($!) {
            $setup->msg($FATAL, 'error_chowning_directory', $_,
                        $setup->{inf}->{admin}->{SysUser}, $!);
            return 0;
        }
    }

    # these paths are shared by SuiteSpotGroup members
    @errs = makePaths($rundir, 0770, $setup->{inf}->{admin}->{SysUser},
                      $setup->{inf}->{General}->{SuiteSpotGroup});
    if (@errs) {
        $setup->msg($FATAL, @errs);
        return 0;
    }
    $! = 0; # clear errno
    chmod 0770, $rundir;
    if ($!) {
        $setup->msg($FATAL, 'error_chmoding_directory', $rundir, $!);
        return 0;
    }
    chown -1, $gid, $rundir;
    if ($!) {
        $setup->msg($FATAL, 'error_chgrping_directory', $rundir,
                    $setup->{inf}->{General}->{SuiteSpotGroup}, $!);
        return 0;
    }

    return 1;
}

sub makeConfFiles {
    my $setup = shift;
    my $configdir = shift;

    my @start_slapd;
    if ($setup->{inf}->{slapd}->{SlapdConfigForMC} =~ /yes/i) {
        my $sbindir = $setup->{inf}->{slapd}->{sbindir};
        my $inst_name = $setup->{inf}->{slapd}->{ServerIdentifier};
        @start_slapd = ('ldapStart', "$sbindir/start-dirsrv $inst_name");
    }
    $setup->msg('updating_admconf');
    my $rc = updateAdmConf({ldapurl => $setup->{inf}->{General}->{ConfigDirectoryLdapURL},
                            SuiteSpotUserID => $setup->{inf}->{General}->{SuiteSpotUserID},
                            SuiteSpotGroup => $setup->{inf}->{General}->{SuiteSpotGroup},
                            sysuser => $setup->{inf}->{admin}->{SysUser},
                            sysgroup => $setup->{inf}->{General}->{SuiteSpotGroup},
                            AdminDomain => $setup->{inf}->{General}->{AdminDomain},
                            @start_slapd},
                           $configdir);
    if (!$rc) {
        $setup->msg($FATAL, 'error_updating_admconf', $!);
        return 0;
    }

    $setup->msg('updating_admpw');
    $rc = updateAdmpw($setup->{inf}->{admin}->{ServerAdminID},
                      $setup->{inf}->{admin}->{ServerAdminPwd},
                      $configdir);
    if (!$rc) {
        $setup->msg($FATAL, 'error_updating_admpw');
        return 0;
    }

    return 1;
}

# sub addDefaultSecurityInfo {
#     my $setup = shift;
#     my $inf = $setup->{inf};
#     my $configdir = shift;
#     my $reconfig = shift;
#     my @errs;

#     my $admConf = getAdmConf($configdir);
#     my $localconf = "$configdir/local.conf";
#     if (!open(LOCALCONF, ">$localconf")) {
#         $setup->msg($FATAL, 'error_updating_localconf', $localconf, $!);
#         return 0;
#     }

#     if (!open(CONSOLECONF, "$admConf->{configdir}/console.conf")) {
#         debug(0, "Error opening $admConf->{configdir}/console.conf: $!");
#         return 0;
#     }

#     print LOCALCONF "configuration.Encryption\n";

#     close(LOCALCONF);
#     return 1;
# }

# This is how we extract the sie and isie as the as entries are
# being added
sub registercb {
    my ($context, $entry, $errs) = @_;

    my $rc = check_and_add_entry([$context->{conn}], $entry, $errs);
    my $setup = $context->{setup};
    if ($rc) {
        if ($entry->hasValue('objectclass', 'nsApplication', 1)) {
            $context->{isie} = $entry->getDN();
        } elsif ($entry->hasValue('objectclass', 'nsAdminServer', 1)) {
            $context->{sie} = $entry->getDN();
        }

        if ($context->{sie}) {
            $rc = updateLocalConf($entry, $context->{sie}, $context->{localfh});
            if (!$rc) {
                $setup->msg($FATAL, 'error_updating_localconf_entry', $entry->getDN());
            }
        }
    } else {
        $setup->msg(@{$errs});
        $setup->msg($FATAL, 'error_adding_adminserver_config_entry', $entry->getDN());
    }

    return $rc;
}

sub registerASWithConfigDS {
    my $setup = shift;
    my $inf = $setup->{inf};
    my $configdir = shift;
    my @errs;

    $setup->msg('registering_adminserver');
    # open a connection to the configuration directory server
    my $conn = getConfigDSConn($inf->{General}->{ConfigDirectoryLdapURL},
                               $inf->{General}->{ConfigDirectoryAdminID},
                               $inf->{General}->{ConfigDirectoryAdminPwd},
                               $configdir, \@errs);

    if (@errs) {
        $setup->msg($FATAL, @errs);
        return 0;
    }

    # add the Admin Server configuration entries
    my @ldiffiles = ("/usr/share/dirsrv/data/20asdata.ldif.tmpl",
                     "/usr/share/dirsrv/data/21astasks.ldif.tmpl",
                     "/usr/share/dirsrv/data/22ascommands.ldif.tmpl"
                     );
    my @infs = getInfs("admin", "setup");
    my $mapper = new Inf("/usr/share/dirsrv/inf/adminserver.map");

    $mapper = process_maptbl($mapper, \@errs, $inf, @infs);
    if (!$mapper or @errs) {
        $conn->close();
        $setup->msg(@errs);
        $setup->msg($FATAL, 'error_creating_adminserver_maptbl');
        return 0;
    }

    # context will get filled in with isie and sie in registercb
    my $localconf = "$configdir/local.conf";
    my $isnew;
    if (! -f $localconf) {
        $isnew = 1;
    }
    if (!open(LOCALCONF, ">$localconf")) {
        $setup->msg($FATAL, 'error_updating_localconf', $localconf, $!);
        return 0;
    }
    my $context = {conn => $conn, localfh => \*LOCALCONF, setup => $setup};
    getMappedEntries($mapper, \@ldiffiles, \@errs, \&registercb, $context);
    close(LOCALCONF);

    if ($isnew) {
        my $admConf = getAdmConf($configdir);
        my $uid = getpwnam $admConf->{sysuser};
        chmod 0600, "$localconf";
        chown $uid, -1, "$localconf";
    }

    $setup->msg('updating_admconf_configds');
    if ($context->{sie} or $context->{isie}) {
        if (!updateAdmConf({sie => $context->{sie},
                            isie => $context->{isie},
                            userdn => $conn->{adminbinddn}},
                           $configdir)) {
            $setup->msg($FATAL, 'error_updating_admconf', $!);
            return 0;
        }
    }

    $conn->close();
    return @errs ? 0 : 1;
}

my @saveconffiles = qw(admserv.conf httpd.conf nss.conf console.conf);
my @savesecfiles = qw(cert8.db key3.db secmod.db password.conf);
my @reconfigsavefiles = qw (httpd.conf nss.conf cert8.db key3.db secmod.db password.conf);

# update other config files - these are the fields which users typically want to
# change during an install or an upgrade, that also must be synced to the Apache
# style config files - we use the config CGI in command line mode because it
# already has all of the logic to update the files correctly
sub updateHttpConfFiles {
    my $serverAddress = shift;
    my $port = shift;
    my $configdir = shift;
    my $origport = shift;
    my $admConf = getAdmConf($configdir);
    my $user = $admConf->{sysuser};

    # this is required on some platforms in order to execute the config command
    my $savepath = $ENV{SHLIB_PATH} || $ENV{LD_LIBRARY_PATH};
    $ENV{LD_LIBRARY_PATH} = "";
    libpath_add("/usr/lib64");
    libpath_add("$savepath");
    $ENV{SHLIB_PATH} = $ENV{LD_LIBRARY_PATH};

    if (! -d "$admConf->{configdir}/bakup") {
        if (system ("mkdir -p $admConf->{configdir}/bakup")) {
            debug(0, "Error backing up $admConf->{configdir}/console.conf failed: $!");
        }
    }
    # backup the savefiles for "remove-ds-admin.pl -a"
    foreach my $savefile (@saveconffiles, @savesecfiles) {
        if (! -f "$admConf->{configdir}/bakup/$savefile") {
            if (-e "$admConf->{configdir}/$savefile"){
                if(system ("cp -p $admConf->{configdir}/$savefile $admConf->{configdir}/bakup")) {
                    debug(0, "Error backing up $admConf->{configdir}/$savefile failed: $!\n");
                }
            }
        }
    }

    my $cmd = "/usr/lib64/dirsrv/cgi-bin/config op=set configuration.nsSuiteSpotUser=\"$user\"";
    if (!defined($origport) or ($port != $origport)) { # need to change the port number
        $cmd .= " configuration.nsServerPort=\"$port\"";
    }
    if ($serverAddress) {
        $cmd .= " configuration.nsServerAddress=\"$serverAddress\"";
    }
    debug(1, "Running $cmd ...");
    $? = 0; # clear error
    my $output = `$cmd 2>&1`;
    # Check the output of the config CGI to see if something bad happened.
    if ($? || $output =~ /NMC_Status: 1/) {
        debug(0, "Error updating console.conf:\n");
        debug(0, $output);
        $ENV{LD_LIBRARY_PATH} = $savepath;
        $ENV{SHLIB_PATH} = $savepath;
        return 0;
    }

    debug(1, $output);
    $ENV{LD_LIBRARY_PATH} = $savepath;
    $ENV{SHLIB_PATH} = $savepath;

    # update Group in console.conf
    if ($admConf->{sysgroup}) {
        if (!open(CONSOLECONF, "$admConf->{configdir}/console.conf")) {
            debug(0, "Error opening $admConf->{configdir}/console.conf: $!");
            return 0;
        }
        my @contents = <CONSOLECONF>;
        close (CONSOLECONF);
        grep { s/^Group.*$/Group $admConf->{sysgroup}/ } @contents;
        if (!open(CONSOLECONF, ">$admConf->{configdir}/console.conf")) {
            debug(0, "Error writing new group $admConf->{sysgroup} to $admConf->{configdir}/console.conf: $!");
            return 0;
        }
        print CONSOLECONF @contents;
        close (CONSOLECONF);
    }

    return 1;
}

sub startAdminServer {
    return 1;
    my $setup = shift;
    my $configdir = shift;
    my $logdir = shift;
    my $rundir = shift;
    my $isrunning;

    $pidfile = "$rundir/admin-serv.pid";
    if (-f $pidfile) {
        open(PIDFILE, $pidfile);
        my $pid = <PIDFILE>;
        close(PIDFILE);
        if (kill 0, $pid) {
            $isrunning = 1;
        }
    }

    my ($fh, $filename) = tempfile("asstartupXXXXXX", UNLINK => 1,
                                   SUFFIX => ".log", DIR => File::Spec->tmpdir);
    close($fh);
    my $rc;
    my $selinux_cmd = "";

    # If we're using selinux, start the server with the proper context
    # to allow the process to transition to the proper domain.
    if (usingSELinux()) {
        $selinux_cmd = "runcon -u system_u -r system_r -t initrc_t";
    }

    if ($isrunning) {
        $setup->msg('restarting_adminserver');
        if ("") {
            $rc = system("service dirsrv-admin restart > $filename 2>&1");
        } elsif ("/usr/lib/systemd/system") {
            $rc = system("/bin/systemctl restart dirsrv-admin.service > $filename 2>&1");
        } else {
            $rc = system("$selinux_cmd /usr/sbin/restart-ds-admin > $filename 2>&1");
        }
    } else {
        $setup->msg('starting_adminserver');
        if ("") {
            $rc = system("service dirsrv-admin start > $filename 2>&1");
        } elsif ("/usr/lib/systemd/system") {
            $rc = system("/bin/systemctl start dirsrv-admin.service > $filename 2>&1");
        } else {
            $rc = system("$selinux_cmd /usr/sbin/start-ds-admin > $filename 2>&1");
        }
    }

    open(STARTLOG, "$filename");
    while (<STARTLOG>) {
        $setup->msg('adminserver_startup_output', $_);
    }
    close(STARTLOG);
    unlink($filename);

    if ($rc) {
        $setup->msg($FATAL, 'error_starting_adminserver', $rc);
        return 0;
    }

    $setup->msg('success_starting_adminserver');
    return 1;
}

sub reconfig_backup_secfiles
{
    #
    # Backup the security files, because when we reconfigure the admin
    # server it overwrites these files and breaks SSL.
    #
    my $configdir = shift;

    my $dirname = dirname $configdir;
    my $my_template_backup_dir = $dirname . "/" . $template_backup_dir;
    $secfile_backup_dir = mkdtemp($my_template_backup_dir);
    if ( ! -d $secfile_backup_dir){
        $setup->msg($FATAL, 'error_creating_secfile_backup', $secfile_backup_dir, $!);
        return 0;
    }
    foreach my $savefile (@reconfigsavefiles) {
        if ( -e "$configdir/$savefile"){
            # To keep the ownership and modes, use move for backup.
            move ("$configdir/$savefile", "$secfile_backup_dir/$savefile");
            debug(1, "Backing up $configdir/$savefile to $secfile_backup_dir/$savefile\n");
            if (! -e "$secfile_backup_dir/$savefile"){
                debug(0, "Backup file $secfile_backup_dir/$savefile not found, error $!\n");
            }
        }
    }
    return 1;
}

sub reconfig_restore_secfiles
{
    #
    # Restore security files
    #
    my $configdir = shift;

    if ( ! -d $secfile_backup_dir){
        $setup->msg($FATAL, 'error_accessing_secfile_backup', $secfile_backup_dir);
        return 0;
    }
    foreach my $savefile (@reconfigsavefiles) {
        move ("$secfile_backup_dir/$savefile" ,"$configdir/$savefile");
        debug(1, "Restoring $configdir/$savefile with $secfile_backup_dir/$savefile\n");
    }
    rmdir ($secfile_backup_dir);
    return 1;
}

sub createAdminServer {
    my $setup = shift;
    my $reconfig = shift;
    # setup has inf, res, and log

    if (!setDefaults($setup)) {
        return 0;
    }

    if (!checkRequiredParameters($setup)) {
        return 0;
    }

    my $configdir = $setup->{inf}->{admin}->{config_dir} ||
        $ENV{ADMSERV_CONF_DIR} ||
        $setup->{configdir} . "/admin-serv";

    my $securitydir = $setup->{inf}->{admin}->{security_dir} ||
        $configdir;

    my $logdir = $setup->{inf}->{admin}->{log_dir} ||
        $ENV{ADMSERV_LOG_DIR} ||
        "/var/log/dirsrv/admin-serv";

    my $rundir = $setup->{inf}->{admin}->{run_dir} ||
        $ENV{ADMSERV_PID_DIR} ||
        "/var/run/dirsrv";

    if ($reconfig) {
        $setup->msg('begin_reconfig_adminserver');
        if (!reconfig_backup_secfiles($configdir)) {
            foreach my $savefile (@reconfigsavefiles) {
                if (-e "$secfile_backup_dir/$savefile") {
                    move ("$secfile_backup_dir/$savefile" ,"$configdir/$savefile");
                    debug(1, "Restoring $configdir/$savefile with $secfile_backup_dir/$savefile\n");
                }
            }
            return 0;
        }
    } else {
        $setup->msg('begin_create_adminserver');
    }

    # if we're just doing the update, just register and return
    if ($setup->{update}) {
        if (!registerASWithConfigDS($setup, $configdir)) {
            return 0;
        }

        # Update SELinux policy if needed
        updateSelinuxPolicy($setup, $configdir, $securitydir, $logdir, $rundir);

        # Restore the security files before we start the server
        if ($reconfig) {
            if (!reconfig_restore_secfiles($configdir)) {
                return 0;
            }
        }

        return 1;
    }

    if (!createASFilesAndDirs($setup, $configdir, $securitydir, $logdir, $rundir)) {
        return 0;
    }

    if (!makeConfFiles($setup, $configdir)) {
        return 0;
    }

    if (!registerASWithConfigDS($setup, $configdir)) {
        return 0;
    }

    $setup->msg('updating_httpconf');
    if (!updateHttpConfFiles($setup->{inf}->{admin}->{ServerIpAddress},
                             $setup->{inf}->{admin}->{Port},
                             $configdir, $setup->{asorigport})) {
        $setup->msg($FATAL, 'error_updating_httpconf');
        return 0;
    }

    if (!setFileOwnerPerms($setup, $configdir)) {
        return 0;
    }

    # Update SELinux policy if needed
    updateSelinuxPolicy($setup, $configdir, $securitydir, $logdir, $rundir);

    # Restore the security files before we start the server
    if ($reconfig) {
        if (!reconfig_restore_secfiles($configdir)) {
            return 0;
        }
    }

    if (!startAdminServer($setup, $configdir, $logdir, $rundir)) {
        return 0;
    }

    # Force to make log files owned by admin user and group
    # to maintain consistency with the log files created via CGI/Console
    my $uid = getpwnam $setup->{inf}->{admin}->{SysUser};
    my $gid = getgrnam $setup->{inf}->{General}->{SuiteSpotGroup};
    # chown log files appropriately
    for (glob("$logdir/*")) {
        $! = 0; # clear errno
        debug(1, "Changing the owner of $_ to \($uid, $gid\)\n");
        chown $uid, $gid, $_;
        if ($!) {
            $setup->msg($FATAL, 'error_chowning_file', $_,
                        $admConf->{sysuser}, $!);
            return 0;
        }
    }

    if ($reconfig) {
        $setup->msg('end_reconfig_adminserver');
    } else {
        $setup->msg('end_create_adminserver');
    }
    return 1;
}

sub reconfigAdminServer {
    my $setup = shift;
    return createAdminServer($setup, 1);
}

sub stopAdminServer {
    my $prog = "/usr/sbin/stop-ds-admin";
    if ("") {
        $prog = "service dirsrv-admin stop";
    } elsif ("/usr/lib/systemd/system") {
        $prog = "/bin/systemctl stop dirsrv-admin.service";
    } elsif (! -x $prog) {
        debug(1, "stopping admin server: no such program $prog: cannot stop server\n");
        return 0;
    }
    $? = 0;
    # run the stop command
    my $output = `$prog 2>&1`;
    my $status = $?;
    debug(3, "stopping admin server returns status $status: output $output\n");
    if ($status) {
        # Ignore the stop failure
        debug(1,"Warning: Could not stop admin server: status $status: output $output\n");
        return 1;
    }

    debug(1, "Successfully stopped admin server\n");
    return 1;
}

sub removeAdminServer {
    my $baseconfigdir = shift;
    my $force = shift;
    my $all = shift;
    if (!stopAdminServer()) {
        if ($force) {
            debug(1, "Warning: Could not stop admin server - forcing continue\n");
        } else {
            debug(1, "Error: Could not stop admin server - aborting - use -f flag to force removal\n");
            return ( [ 'error_stopping_adminserver', $! ] );
        }
    }

    my $configdir = $ENV{ADMSERV_CONF_DIR} || $baseconfigdir . "/admin-serv";

    my $securitydir = $configdir;

    my $logdir = $ENV{ADMSERV_LOG_DIR} || "/var/log/dirsrv/admin-serv";

    my $rundir = $ENV{ADMSERV_PID_DIR} || "/var/run/dirsrv";

    # Need to unlabel the port if we're using SELinux.
    if (usingSELinux()) {
        my $port;

        # Read the console.conf file to find the port number.
        if (!open(CONSOLECONF, "$configdir/console.conf")) {
            if ($force) {
                debug(1, "Warning: Could not open $configdir/console.conf: $!");
            } else {
                debug(1, "Error: Could not open $configdir/console.conf: $!");
                return( [ 'error_reading_conffile', "$configdir/console.conf", $! ] );
            }
        } else {
            # Find the Listen directive and read the port number.
            while (<CONSOLECONF>) {
                if (/^Listen /g) {
                    # The port is after the last ':'
                    my @listenline = split(/:/);
                    $port = $listenline[-1];
                }
            }
            close(CONSOLECONF);
        }

        if (!$port) {
            if ($force) {
                debug(1, "Warning: Could not determine port number - forcing continue\n");
                debug(1, "Warning: Port not removed from selinux policy correctly.  Remove label manually using semanage.\n");
            } else {
                debug(1, "Error: Could not determine port number - aborting - use -f flag to force removal\n");
                return ( [ 'error_reading_port' ] );
            }
        } else {
            # Attempt to remove the http_port_t label from the port used by Admin Server.
            my $semanage_err = `semanage port -d -t http_port_t -p tcp $port 2>&1`;
            if ($? != 0)  {
                if ($semanage_err !~ /defined in policy, cannot be deleted/) {
                    debug(1, "Warning: Port $port not removed from selinux policy correctly.  Error: $semanage_err\n");
                    if (!$force) {
                        return( [ 'error_removing_port_label', $port, $semanage_err ] );
                    }
                }
            }
        }

        # turn off the switch to allow admin server to connect to the ldap port
        $? = 0; # clear error

        my $cmd = "getsebool httpd_can_connect_ldap";
        my $output = `$cmd 2>&1`;
        chomp($output);
        if ($output =~ /Error getting active value for httpd_can_connect_ldap/) {
            # this version of selinux does not support the boolean value
            debug(1, "This version of selinux does not support httpd_can_connect_ldap\n");
        } elsif ($?) {
            $setup->msg($SetupLog::WARN, 'error_running_command', $cmd, $output, $!);
        } elsif ($output =~ /on$/) {
            $cmd = "setsebool -P httpd_can_connect_ldap off";
            $? = 0; # clear error
            $output = `$cmd 2>&1`;
            chomp($output);
            if ($?) {
                $setup->msg($SetupLog::WARN, 'error_running_command', $cmd, $output, $!);
            } else {
                debug(1, "$cmd was successful\n");
            }
        } else {
            debug(1, "selinux boolean httpd_can_connect_ldap is already off - $output\n");
        }
    }

    # remove admin server files in $rundir
    my $file;
    for $file (glob("$rundir/admin-serv.*")) {
        unlink($file);
    }

    # remove admin server log dir
    if ($logdir =~ /admin-serv/) { # make sure directory has admin-serv in it somewhere
        if (!rmtree($logdir)) {
            debug(1, "Warning: Could not remove directory $logdir: $!\n");
            if (!$force) {
                return ( [ 'error_removing_path', $logdir, $! ] );
            }
        }
    }

    # remove config files
    my @savefiles = (@savesecfiles, @saveconffiles); # save security and conf files by default
    if ($all) {
        @savefiles = @saveconffiles; # $all means remove everything, except the files in rpm.
    }
    if (opendir(CONFDIR, $configdir)) {
        while ($file = readdir(CONFDIR)) {
            next if ($file eq '.' || $file eq '..');
            if (-d "$configdir/$file") {
                debug(1, "Skipping directory $configdir/$file - remove manually\n");
                next;
            }
            if (grep /^$file$/, @savefiles) {
                debug(1, "saving file $configdir/$file\n");
            } else {
                debug(1, "removing file $configdir/$file\n");
                unlink("$configdir/$file");
            }
        }
        closedir(CONFDIR);
        # restore original conf files
        foreach my $savefile (@saveconffiles) {
            if (-f "$configdir/bakup/$savefile") {
                if (system ("mv $configdir/bakup/$savefile $configdir")) {
                    debug(0, "Error Restoring $configdir/$savefile failed: $!");
                }
            }
        }
        # Clean up the bakup dir
        system ("rm -rf $configdir/bakup");
    } else {
        debug(1, "Error: could not read config files in $configdir: $!");
        if (!$force) {
            return ( [ 'error_removing_path', $configdir, $! ] );
        }
    }

    return;
}

sub updateSelinuxPolicy {
    my $setup = shift;
    my $configdir = shift;
    my $securitydir = shift;
    my $logdir = shift;
    my $rundir = shift;

    # if selinux is not available, do nothing
    if (usingSELinux()) {
        # run restorecon on all directories we created
        system("restorecon -R $configdir $securitydir $logdir $rundir");

        # Label the selected port as http_port_t.
        if ($setup->{inf}->{admin}->{Port}) {
            my $need_label = 1;

            # check if the port is already labeled properly
            my $portline = `semanage port -l | grep http_port_t | grep tcp`;
            chomp($portline);
            $portline =~ s/http_port_t\s+tcp\s+//g;
            my @labeledports = split(/,\s+/, $portline);
            foreach my $labeledport (@labeledports) {
                if ($setup->{inf}->{admin}->{Port} == $labeledport) {
                    $need_label = 0;
                    last;
                }
            }

            if ($need_label == 1) {
                system("semanage port -a -t http_port_t -p tcp $setup->{inf}->{admin}->{Port}");
            }
        }

        # turn on the switch to allow admin server to connect to the ldap port
        $? = 0; # clear error

        my $cmd = "getsebool httpd_can_connect_ldap";
        my $output = `$cmd 2>&1`;
        chomp($output);
        if ($output =~ /Error getting active value for httpd_can_connect_ldap/) {
            # this version of selinux does not support the boolean value
            debug(1, "This version of selinux does not support httpd_can_connect_ldap\n");
        } elsif ($?) {
            $setup->msg($SetupLog::WARN, 'error_running_command', $cmd, $output, $!);
        } elsif ($output =~ /off$/) {
            $cmd = "setsebool -P httpd_can_connect_ldap on";
            $? = 0; # clear error
            $output = `$cmd 2>&1`;
            chomp($output);
            if ($?) {
                $setup->msg($SetupLog::WARN, 'error_running_command', $cmd, $output, $!);
            } else {
                debug(1, "$cmd was successful\n");
            }
        } else {
            debug(1, "selinux boolean httpd_can_connect_ldap is already on - $output\n");
        }
    }
}

sub libpath_add {
    my $libpath = shift;

    if ($libpath) {
        if ($ENV{'LD_LIBRARY_PATH'}) {
            $ENV{'LD_LIBRARY_PATH'} = "$ENV{'LD_LIBRARY_PATH'}:$libpath";
        } else {
            $ENV{'LD_LIBRARY_PATH'} = "$libpath";
        }
    }
}

1;

# emacs settings
# Local Variables:
# mode:perl
# indent-tabs-mode: nil
# tab-width: 4
# End: