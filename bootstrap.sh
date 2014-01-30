#!/bin/bash

apt-get -q update
apt-get -q -y install curl php5 php5-imap php5-sqlite apache2 git vim
export DEBIAN_FRONTEND=noninteractive; apt-get -q -y install mysql-server
cp /vagrant/vagrant/files/etc/apache2/sites-available/default /etc/apache2/sites-available/default
/etc/init.d/apache2 restart
cp /vagrant/mailapp.db /tmp
chmod 664 /tmp/mailapp.db
chown www-data:www-data /tmp/mailapp.db
