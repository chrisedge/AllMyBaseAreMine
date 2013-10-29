#!/bin/bash
#MYHOSTNAME=INSERT_HOSTNAME
#export MYHOSTNAME
set -e -x
export DEBIAN_FRONTEND=noninteractive
sudo apt-get update
sudo apt-get upgrade -y

# install packages
sudo apt-get install -y apache2 php5 php5-gd libapache2-mod-php5 php5-xcache git-core mysql-client memcached php5-memcache php5-memcached php5-curl

# create release folders
sudo mkdir -p /var/openx/releases
sudo mkdir -p /var/openx/shared/config
sudo chmod a+w /var/openx/

# setup access to s3
sudo aptitude install -y build-essential libcurl4-openssl-dev libxml2-dev libfuse-dev comerr-dev libfuse2 libidn11-dev libkadm55 libkrb5-dev libldap2-dev libselinux1-dev libsepol1-dev pkg-config fuse-utils sshfs
wget http://s3fs.googlecode.com/files/s3fs-1.61.tar.gz
tar xf ./s3fs-1.61.tar.gz
cd ./s3fs-1.61/
sudo ./configure
sudo make
sudo make install
sudo mkdir /mnt/application-bootstrap
sudo sh -c 'echo "SuperSecret:PasswordStuff" >> /etc/passwd-s3fs'
sudo chmod 640 /etc/passwd-s3fs
sudo sh -c 'echo "s3fs#application-bootstrap /mnt/application-bootstrap fuse allow_other 0 0" >> /etc/fstab'
sudo mount /mnt/application-bootstrap

# download the github deployment ssh key
sudo cp /mnt/application-bootstrap/id_rsa /root/.ssh/
sudo chmod 0600 /root/.ssh/id_rsa
cd /var/openx/
sudo sh -c 'echo "Host github.com\n   StrictHostKeyChecking no" >> /root/.ssh/config'
# we assume master branch is always in the production state (develepment should happen in branches)
sudo git clone git@github.com:username/RepoName.git

# configure files and link folders
sudo ln -nfs /var/openx/ApplicationName/openx /var/openx/releases/current
sudo mv /var/www /var/www_old
sudo ln -nfs /var/openx/releases/current/www/delivery /var/www
# backward compatibility (needed for ads background)
sudo ln -nfs /var/openx/releases/current/ /var/www/www
sudo ln -nfs /var/openx/releases/current/www/admin/ /var/www/www/admin
sudo ln -nfs /var/openx/releases/current/plugins/ /var/www/plugins
sudo mkdir -p /var/openx/releases/current/var/cache
sudo chmod a+w -R /var/openx/releases/current/var
sudo touch /var/www/ok.html

sudo mv /var/openx/ApplicationName/openx/var/default.conf.php /var/openx/ApplicationName/openx/var/__default.conf.php__
#sudo mv /mnt/application-bootstrap/$MYHOSTNAME.default.conf.php /var/openx/ApplicationName/openx/var/default.conf.php
#sudo mv /mnt/application-bootstrap/$MYHOSTNAME.conf.php /var/openx/ApplicationName/openx/var/$MYHOSTNAME.conf.php
sudo chown root:root /var/openx/ApplicationName/openx/var/default.conf.php
#sudo chown root:root /var/openx/ApplicationName/openx/var/$MYHOSTNAME.conf.php
sudo chmod 755 /var/openx/ApplicationName/openx/var/default.conf.php
#sudo chmod 755 /var/openx/ApplicationName/openx/var/$MYHOSTNAME.conf.php

# add cache folder
sudo mkdir /mnt/openx-delivery-cache
sudo chmod a+w /mnt/openx-delivery-cache

# create and import mysql
sudo debconf-set-selections <<< 'mysql-server-5.1 mysql-server/root_password password SuperSecure'
sudo debconf-set-selections <<< 'mysql-server-5.1 mysql-server/root_password_again password SuperSecure'
sudo apt-get -y install mysql-server
sudo apt-get install -y php5-mysql
sudo /etc/init.d/apache2 restart
cd /var/openx/ApplicationName/deployment/delivery/
sudo mysql -pibqiAfUp < ./create_database.sql
sudo mysql -pibqiAfUp application_openx_delivery < ./openx_delivery_schema.sql

# add crontab entries
#sudo crontab /var/openx/ApplicationName/deployment/delivery/crontab.txt

# registerd shut-down script
#sudo cp /var/openx/ApplicationName/deployment/delivery/pre-shutdown.conf /etc/init/
