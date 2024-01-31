FROM httpd:alpine

# Create webdav directory
RUN mkdir -p /usr/local/apache2/webdav; chown www-data /usr/local/apache2/webdav
# Copy config
COPY ./webdav.conf /usr/local/apache2/conf/webdav.conf
# Add password file
RUN /usr/local/apache2/bin/htpasswd -cb /usr/local/apache2/webdav.password test pass
RUN chown root:www-data /usr/local/apache2/webdav.password
RUN chmod 640 /usr/local/apache2/webdav.password
# Enable config
RUN echo "Include conf/webdav.conf" >> /usr/local/apache2/conf/httpd.conf
