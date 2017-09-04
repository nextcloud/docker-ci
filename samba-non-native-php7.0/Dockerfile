# Based upon https://hub.docker.com/r/silvershell/samba/
FROM nextcloudci/php7.0:php7.0-16

ENV SMB_USER smbuser
ENV SMB_PASSWORD smbpassword

RUN apt-get update

RUN apt-get install -y samba smbclient
RUN mkdir -p /opt/samba/share
RUN chmod 777 /opt/samba/share

RUN mkdir -p /opt/samba/user
RUN chmod 777 /opt/samba/user

RUN useradd -s /bin/false "$SMB_USER"
RUN (echo "$SMB_PASSWORD"; echo "$SMB_PASSWORD" ) | pdbedit -a -u "$SMB_USER"

EXPOSE 137/udp 138/udp 139 445

COPY smb.conf /etc/samba/smb.conf

