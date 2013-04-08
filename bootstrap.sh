#!/bin/bash

apt-get -q update
apt-get -q -y install curl
apt-get -q -y install php5
export DEBIAN_FRONTEND=noninteractive; apt-get -q -y install mysql-server
apt-get -q -y install git
apt-get -q -y install vim
cp /vagrant/vagrant/files/etc/apache2/sites-available/default /etc/apache2/sites-available/default
/etc/init.d/apache2 restart
