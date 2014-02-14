<?php

/**
 * Instance installer
 */
abstract class SmanInstance extends SmanInstaller {

  protected $serverName;

  function __construct($serverName) {
    parent::__construct(new DoceanRootConnection($serverName));
    $this->serverName = $serverName;
  }

  /**
   * @param string Server Name
   * @return string
   */
  static function getClass($name) {
    return 'SmanInstance'.ucfirst(SmanCore::serverType($name));
  }

  protected $user = 'root';

  function install() {
    $this->installCore();
    parent::install();
    print $this->ssh->exec('ps aux | grep fpm');
  }

  protected function createUser() {
    $user = 'user';
    $pass = Misc::randString(7);
    $this->ssh->exec([
      "useradd -m -s /bin/bash -p `openssl passwd -1 $pass` $user",
    ]);
    SmanConfig::updateSubVar('userPasswords', $this->sshConnection->host, $pass);
    $this->ssh->exec([
      'apt-get -y install sudo',
      "echo '%$user ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers"
    ]);
  }

  /**
   * mc, git-core
   */
  function installCore() {
    print $this->ssh->exec([
      'apt-get update',
      'apt-get -y install mc git-core',
    ]);
    $this->createUser();
  }

  /**
   * php5.4, phpUnit; extensions: pear, curl
   */
  function installPhp() {
    print $this->ssh->exec([
      'apt-get -y install python-software-properties',
      'apt-get update',
      'add-apt-repository --yes ppa:ondrej/php5-oldstable',
      'apt-get update',
      'apt-get -y install php5-cli php5-dev php-pear php5-curl',
    ]);
    //print $this->ssh->exec('apt-get -y install libssh2-1-dev libssh2-php');
    print $this->ssh->exec([
      'pear channel-discover pear.phpunit.de',
      'pear install phpunit/PHPUnit',
    ]);
  }

  /**
   * php5.4, phpUnit, memcached, imagemagick; extensions: pear, curl, memcached, fpm, gd, mysql
   */
  function installPhpFull() {
    $this->installPhp();
    print $this->ssh->exec([
      'apt-get -y install php5-memcached php5-fpm php5-gd php5-mysql',
      'apt-get -y install memcached',
      'apt-get -y install imagemagick',
    ]);
    print $this->ssh->exec([
      'sed -i "s|www-data|user|g" /etc/php5/fpm/pool.d/www.conf',
      '/etc/init.d/php5-fpm restart'
    ]);
  }

  function installNginx() {
    $this->ssh->exec([
      'apt-get -y install nginx',
      'cd /etc/nginx',
      'sed -i "s/^\s*#.*$//g" nginx.conf',
      'sed -i "/^\s*$/d" nginx.conf',
      'sed -i "s|^\s*include /etc/nginx/sites-enabled/\*;|\tserver_names_hash_bucket_size 64;\\n\tinclude /home/user/ngn-env/config/nginxProjects/\*;\\n\tinclude /home/user/ngn-env/config/nginx/*;|g" nginx.conf',
      'sed -i "s|www-data|user|g" nginx.conf',
    ]);
  }

  function installRabbitmq() {
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

  /**
   * postfix
   */
  function installMail() {
    print $this->ssh->exec([
      'export DEBIAN_FRONTEND=noninteractive',
      'apt-get -y install postfix',
      'postconf -e "home_mailbox = Maildir/"',
      '/etc/init.d/postfix restart',
      'postconf -e "mydestination = localhost, '.Config::getSubVar('botEmail', 'domain').'"',
    ]);
  }

  /**
   * Создаёт DNS-записить базового хоста сервера
   */
  function installDns() {
    $this->ei->addSshKey($this->serverName, 'dnsMaster', 'user');
    $this->createBaseZone();
  }

  protected function createBaseZone() {
    $this->ei->cmd('dnsMaster', Cli::formatRunCmd("(new DnsServer)->createZone('{$this->baseDomain()}', '{$this->ei->api->server($this->serverName)['ip_address']}')", 'NGN_ENV_PATH/dns-server/lib'));
  }

  /**
   * Удаляет DNS-записить базового хоста сервера
   */
  function removeDns() {
    $this->ei->removeSshKey($this->serverName, 'dnsMaster', 'user');
    $this->ei->cmd('dnsMaster', Cli::formatRunCmd("(new DnsServer)->deleteZone('{$this->baseDomain()}')", 'NGN_ENV_PATH/dns-server/lib'));
  }

  protected function runTests() {
  }

  protected function installMysql() {
    $pass = '123';
    $this->ssh->exec("debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password password $pass'", "debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password_again password $pass'", "apt-get -y install mysql-server");
  }

}