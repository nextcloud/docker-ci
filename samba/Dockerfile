# Based upon https://hub.docker.com/r/silvershell/samba/
From ubuntu:16.04

ENV SMB_USER smbuser
ENV SMB_PASSWORD smbpassword

RUN apt-get update

RUN apt-get install -y samba
RUN mkdir -p /opt/samba/share
RUN chmod 777 /opt/samba/share

RUN mkdir -p /opt/samba/user
RUN chmod 777 /opt/samba/user

RUN useradd -s /bin/false "$SMB_USER"
RUN (echo "$SMB_PASSWORD"; echo "$SMB_PASSWORD" ) | pdbedit -a -u "$SMB_USER"

EXPOSE 137/udp 138/udp 139 445

COPY smb.conf /etc/samba/smb.conf

CMD ["/usr/sbin/smbd", "-FS"]
