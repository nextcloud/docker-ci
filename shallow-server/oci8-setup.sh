#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

ORACLE_DIR="/opt/oracle/instantclient"

# Remove existing files if they exist
[ -f "instantclient-basic-linuxx64.zip" ] && rm "instantclient-basic-linuxx64.zip"
[ -f "instantclient-sdk-linuxx64.zip" ] && rm "instantclient-sdk-linuxx64.zip"

# Download Oracle Instant Client basic package
curl -kL "https://download.oracle.com/otn_software/linux/instantclient/instantclient-basic-linuxx64.zip" > "instantclient-basic-linuxx64.zip"

# Download Oracle Instant Client SDK package
curl -kL "https://download.oracle.com/otn_software/linux/instantclient/instantclient-sdk-linuxx64.zip" > "instantclient-sdk-linuxx64.zip"

# Unzip the downloaded files
echo "Unzipping downloaded files..."
unzip -o instantclient-basic-linuxx64.zip
unzip -o instantclient-sdk-linuxx64.zip

# Create Oracle directory
echo "Creating Oracle directory..."
mkdir -p $ORACLE_DIR

# Move Instant Client to the Oracle directory
echo "Moving Instant Client to the Oracle directory..."
mv instantclient*/* $ORACLE_DIR
ls -la $ORACLE_DIR

# Create symbolic links
echo "Creating symbolic links..."
ln -sf $ORACLE_DIR/libclntsh.so.12.1 $ORACLE_DIR/libclntsh.so
ln -sf $ORACLE_DIR/libocci.so.12.1 $ORACLE_DIR/libocci.so

# Configure dynamic linker run-time bindings
echo "Configuring dynamic linker run-time bindings..."
echo $ORACLE_DIR | tee /etc/ld.so.conf.d/oracle-instantclient.conf
ldconfig

# Install OCI8 for PHP 8.2
echo "Installing OCI8 extension for PHP 8.2..."
/usr/bin/expect <<EOF
set timeout 60
spawn pecl install oci8-3.2.1
expect "Please provide the path to the ORACLE_HOME directory"
send "instantclient,/opt/oracle/instantclient\r"
expect eof
EOF

echo "Oracle Instant Client and OCI8 installation completed."
