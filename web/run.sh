# wget -O - http://sman./run.sh | bash
#
sudo apt-get update
sudo apt-get -y install mc git-core
sudo useradd -m -s /bin/bash -p `openssl passwd -1 wfVo3jM` user
sudo apt-get -y install sudo
sudo echo '%user ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers
sudo apt-get -y install python-software-properties
sudo apt-get update
sudo add-apt-repository --yes ppa:ondrej/php5-oldstable
sudo apt-get update
sudo apt-get -y install php5-cli php5-dev php-pear php5-curl
sudo pear channel-discover pear.phpunit.de
sudo pear install phpunit/PHPUnit
sudo debconf-set-selections <<< "postfix postfix/mailname string localhost"
sudo debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"
sudo apt-get -y install postfix
sudo postconf -e "home_mailbox = Maildir/"
sudo /etc/init.d/postfix restart
sudo postconf -e "mydestination = localhost, masted.ru"
