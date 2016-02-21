# Ngn-env installation script for server type 'projects':
apt-get update
apt-get -y install mc git-core
useradd -m -s /bin/bash -p `openssl passwd -1 we8fygeqw` user
echo -n 'we8fygeqw' > /home/user/.pass
apt-get -y install sudo
echo '%user ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers
apt-get -y install nginx
cd /etc/nginx
sed -i "s/^\s*#.*$//g" nginx.conf
sed -i "/^\s*$/d" nginx.conf
sed -i "s|www-data|user|g" nginx.conf
cd /etc/nginx
sed -i "s|^\s*include /etc/nginx/sites-enabled/\*;|\tinclude /home/user/ngn-env/config/nginx/static/*;\n\tinclude /home/user/ngn-env/config/nginx/projects/*;\n\tinclude /home/user/ngn-env/config/nginx/system/*;|g" nginx.conf
sudo /etc/init.d/nginx start
ps aux | grep nginx
apt-get -y install python-software-properties software-properties-common
apt-get -y install language-pack-en-base && export LC_ALL=en_US.UTF-8 && export LANG=en_US.UTF-8
apt-get update
add-apt-repository --yes ppa:ondrej/php5
apt-get update
apt-get -y install php5-cli
apt-get -y install php5-curl php5-dev php-pear
pear channel-discover pear.phpunit.de
pear install phpunit/PHPUnit
apt-get -y install memcached php5-memcached php5-fpm
sed -i "s|www-data|user|g" /etc/php5/fpm/pool.d/www.conf
/etc/init.d/php5-fpm restart
apt-get -y install php5-gd php5-mysql
apt-get -y install imagemagick
bash -c 'debconf-set-selections <<< "server-5.5 mysql-server/root_password password wrgwerg34gw"'
bash -c 'debconf-set-selections <<< "server-5.5 mysql-server/root_password_again password wrgwerg34gw"'
apt-get -y install mysql-server
bash -c 'debconf-set-selections <<< "postfix postfix/mailname string localhost"'
bash -c 'debconf-set-selections <<< "postfix postfix/main_mailer_type string \"Internet Site\""'
apt-get -y install postfix
postconf -e "home_mailbox = Maildir/"
/etc/init.d/postfix restart
postconf -e "mydestination = localhost, masted.ru"
su user
mkdir ~/ngn-env
mkdir ~/ngn-env/logs
cd ~/ngn-env
git clone https://github.com/majexa/ngn.git
git clone https://github.com/majexa/ci.git
git clone https://github.com/majexa/issue.git
git clone https://github.com/majexa/run.git
git clone https://github.com/majexa/sman.git
git clone https://github.com/majexa/ngn-cst.git
git clone https://github.com/majexa/tst.git
git clone https://github.com/majexa/pm.git
git clone https://github.com/majexa/dummyProject.git
git clone https://github.com/masted/myadmin.git
# clone mootools-core
# clone mootools-more
cd ~/ngn-env/ci
chmod +x ci
sudo ./ci _updateBin
./ci update
# store database pass to server.php
# save database.php to ./config
sman enb local createConfig majexa.ru
pm localServer updateHosts
pm localProjects daemons

# encrease php max post value


#php config <? tag

