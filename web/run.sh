# Ngn-env installation script for server type 'projects':
apt-get update
locale-gen "ru_RU.UTF-8"
apt-get -y install mc git-core
useradd -m -s /bin/bash -p `openssl passwd -1 CHANGE_IT` user
echo -n 'CHANGE_IT' > /home/user/.pass
apt-get -y install sudo
echo '%user ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers
apt-get -y purge apache2
apt-get -y install nginx
sed -i "s/^\s*#.*$//g" /etc/nginx/nginx.conf
sed -i "/^\s*$/d" /etc/nginx/nginx.conf
sed -i "s|www-data|user|g" /etc/nginx/nginx.conf
sed -i "s|^\s*include /etc/nginx/sites-enabled/\*;|\tinclude /home/user/ngn-env/config/nginx/all.conf;\n"
sudo /etc/init.d/nginx start
ps aux | grep nginx
apt-get -y install python-software-properties software-properties-common
apt-get install -y language-pack-en-base && export LC_ALL=en_US.UTF-8 && export LANG=en_US.UTF-8
apt-get update
add-apt-repository --yes ppa:ondrej/php
apt-get update
apt-get -y install php5.6 php5.6-mbstring
sed -i "s/short_open_tag = Off/short_open_tag = On/g" /etc/php/5.6/cli/php.ini
sed -i "s/display_errors = Off/display_errors = On/g" /etc/php/5.6/cli/php.ini
apt-get -y install php5.6-curl
apt-get -y install memcached php5.6-memcached php5.6-fpm
sed -i "s/short_open_tag = Off/short_open_tag = On/g" /etc/php/5.6/fpm/php.ini
sed -i "s/display_errors = Off/display_errors = On/g" /etc/php/5.6/fpm/php.ini
sed -i "s|www-data|user|g" /etc/php/5.6/fpm/pool.d/www.conf
/etc/init.d/php5.6-fpm restart
apt-get -y install php5.6-gd php5.6-mysql
apt-get -y install imagemagick
bash -c 'debconf-set-selections <<< "server-5.5 mysql-server/root_password password 123"'
bash -c 'debconf-set-selections <<< "server-5.5 mysql-server/root_password_again password 123"'
apt-get -y install mysql-server
/etc/mysql/conf.d/disable_strict_mode.cnf < echo "[mysqld]\nsql_mode=IGNORE_SPACE,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"
mkdir ~/ngn-env
mkdir ~/ngn-env/logs
cd ~/ngn-env
git clone https://github.com/majexa/ngn.git
git clone https://github.com/majexa/issue.git
git clone https://github.com/majexa/ci.git
git clone https://github.com/majexa/run.git
git clone https://github.com/majexa/scripts.git
git clone https://github.com/majexa/pm.git
git clone https://github.com/majexa/dummyProject.git
git clone https://github.com/majexa/tst.git
git clone https://github.com/mootools/mootools-core.git
git clone https://github.com/mootools/mootools-more.git
cd ~/ngn-env/ci && ./ci _updateBin
