<?php

/**
 * Instance installer
 */
abstract class SmanInstance extends SmanInstanceAbstract {

  protected $user = 'root';
  static $classPrefix = 'SmanInstance';

  protected function createUser() {
    $user = 'user';
    $pass = '123';
    $this->ssh->exec([
      "useradd -m -s /bin/bash -p `openssl passwd -1 $pass` $user",
    ]);
    SmanConfig::updateSubVar('userPasswords', $this->sshConnection->host, $pass);
    $this->ssh->exec([
      'apt-get -y install sudo',
      "%$user ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers"
    ]);
  }

  protected function installCore() {
    print $this->ssh->exec([
      'apt-get -y install mc',
    ]);
  }

  protected function installPhp() {
    print $this->ssh->exec([
      'apt-get -y install python-software-properties',
      'apt-get update',
      'add-apt-repository --yes ppa:ondrej/php5-oldstable',
      'apt-get update',
      'apt-get -y install php5-cli php5-dev php-pear',
    ]);
    print $this->ssh->exec([
      'pear channel-discover pear.phpunit.de',
      'pear install phpunit/PHPUnit',
    ]);
    print $this->ssh->exec([
      'wget http://archive.ubuntu.com/ubuntu/pool/universe/libs/libssh2/libssh2-1_1.4.2-1.1_amd64.deb',
      'wget http://archive.ubuntu.com/ubuntu/pool/universe/p/php-ssh2/libssh2-php_0.11.3-0.1build1_amd64.deb',
      'dpkg -i libssh2-1_1.4.2-1.1_amd64.deb',
      'dpkg -i libssh2-php_0.11.3-0.1build1_amd64.deb'
    ]);
  }

  protected function installPhpFull() {
    $this->installPhp();
    print $this->ssh->exec([
      'apt-get -y install php5-curl php5-memcached  php5-fpm php5-gd php5-mysql php5-dev',
      'apt-get -y install memcached',
      'apt-get -y install imagemagick',
    ]);
  }

  protected function installGit() {
    print $this->ssh->exec('apt-get -y install git-core');
  }

  protected function installNginx() {
    $this->ssh->exec([
      'apt-get -y install nginx',
      'cd /etc/nginx',
      'sed -i "s/^\s*#.*$//g" nginx.conf',
      'sed -i "/^\s*$/d" nginx.conf',
      'sed -i "s|^\s*include /etc/nginx/sites-enabled/\*;|\tserver_names_hash_bucket_size 64;\\n\tinclude /home/user/ngn-env/config/nginxProjects/\*;\\n\tinclude /home/user/ngn-env/config/nginx/*;|g" nginx.conf',
      'sed -i "s|www-data|user|g" nginx.conf',
      'sed -i "s|www-data|user|g" /etc/php5/fpm/pool.d/www.conf',
    ]);
  }

  protected function installRabbitmq() {
    $this->ssh->exec([
      'cd /tmp',
      'echo -e "deb http://www.rabbitmq.com/debian/ testing main" >> /etc/apt/sources.list',
      'wget http://www.rabbitmq.com/rabbitmq-signing-key-public.asc',
      'apt-key add rabbitmq-signing-key-public.asc',
      'apt-get update',
      'apt-get install rabbitmq-server',
      'pecl install amqp',
      'echo -e "extension=amqp.so" > /etc/php5/conf.d/amqp.ini'
    ]);
  }

  protected function installPiwik() {
  }

  protected function installMail() {
    print $this->ssh->exec([
      'export DEBIAN_FRONTEND=noninteractive',
      'apt-get -y install postfix',
      'postconf -e "home_mailbox = Maildir/"',
      '/etc/init.d/postfix restart',
      'postconf -e "mydestination = localhost, '.Config::getSubVar('botEmail', 'domain').'"',
    ]);
  }

  function installDns() {
    $this->ei->addSshKey($this->name, 'dnsMaster', 'user');
    $this->createBaseZone();
  }

  function createBaseZone() {
    $this->ei->cmd('dnsMaster', Cli::formatRunCmd("(new DnsServer)->createZone('{$this->baseDomain()}', '{$this->ei->api->server($this->name)['ip_address']}')", 'NGN_ENV_PATH/dns-server/lib'));
  }

  function removeDns() {
    $this->ei->removeSshKey($this->name, 'dnsMaster', 'user');
    $this->ei->cmd('dnsMaster', Cli::formatRunCmd("(new DnsServer)->deleteZone('{$this->baseDomain()}')", 'NGN_ENV_PATH/dns-server/lib'));
  }

  protected function runTests() {
  }

  protected function installMysql() {
    $pass = '123';
    $this->ssh->exec("debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password password $pass'", "debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password_again password $pass'", "apt-get -y install mysql-server");
  }

}