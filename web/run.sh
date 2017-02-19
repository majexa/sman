# Ngn-env installation script for server type 'projects':
apt-get update
apt-get -y install mc git-core
useradd -m -s /bin/bash -p `openssl passwd -1 CHANGE_PASSWORD` user
echo -n 'CHANGE_PASSWORD' > /home/user/.pass
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
apt-get install -y language-pack-en-base && export LC_ALL=en_US.UTF-8 && export LANG=en_US.UTF-8
apt-get update
add-apt-repository --yes ppa:ondrej/php5.6
apt-get update
apt-get -y install php5-cli
apt-get -y install php5.6-curl php5.6-dev php-pear
pear channel-discover pear.phpunit.de
pear install phpunit/PHPUnit
apt-get -y install memcached php5.6-memcached php5.6-fpm
sed -i "s|www-data|user|g" /etc/php5/fpm/pool.d/www.conf
/etc/init.d/php5-fpm restart
apt-get -y install php5.6-gd php5.6-mysql
apt-get -y install imagemagick
bash -c 'debconf-set-selections <<< "server-5.5 mysql-server/root_password password 123"'
bash -c 'debconf-set-selections <<< "server-5.5 mysql-server/root_password_again password 123"'
apt-get -y install mysql-server
su user
mkdir ~/ngn-env
mkdir ~/ngn-env/logs
cd ~/ngn-env
git clone -b dev git@github.com:majexa/ngn.git
git clone git@github.com:majexa/ci.git
git clone git@github.com:majexa/run.git
git clone git@github.com:majexa/sman.git
git clone git@github.com:majexa/smon.git
cd ~/ngn-env/ci
chmod +x ci
sudo ./ci _updateBin
./ci update
