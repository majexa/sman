<?php

/**
 * Instance installer
 */
abstract class SmanInstanceAbstract extends SmanInstallerBase {

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
  }

  public $userPass;

  protected function createUser() {
    $user = 'user';
    $this->userPass = Misc::randString(7);
    $this->exec([
      "useradd -m -s /bin/bash -p `openssl passwd -1 {$this->userPass}` $user",
      "echo -n '{$this->userPass}' > /home/$user/.pass",
    ]);
    if (!$this->disable) SmanConfig::updateSubVar('userPasswords', $this->serverHost(), $this->userPass);
    LogWriter::str('userPasswords', $this->userPass, SMAN_PATH.'/logs');
    $this->exec([
      'apt-get -y install sudo',
      "echo '%$user ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers"
    ]);
  }

  /**
   * mc, git-core
   */
  function installCore() {
    print $this->exec([
      'apt-get update',
      'apt-get -y install mc git-core',
    ]);
    $this->createUser();
  }

  function installPhpBasic() {
    print $this->exec([
      'apt-get -y install python-software-properties',
      'apt-get update',
      'add-apt-repository --yes ppa:ondrej/php5-oldstable',
      'apt-get update',
      'apt-get -y install php5-cli',
    ]);
  }

  function installPhpAdvanced() {
    print $this->exec([
      'apt-get -y install php5-curl php5-dev php-pear',
    ]);
    //print $this->exec('apt-get -y install libssh2-1-dev libssh2-php');
    print $this->exec([
      'pear channel-discover pear.phpunit.de',
      'pear install phpunit/PHPUnit',
    ]);
  }

  function installPhp() {
    $this->installPhpBasic();
    $this->installPhpAdvanced();
  }

  /**
   * php5.4, phpUnit; extensions: pear, curl, fpm, memcached
   */
  function installPhpWeb() {
    $this->installPhp();
    print $this->exec([
      'apt-get -y install php5-memcached php5-fpm',
    ]);
    print $this->exec([
      'sed -i "s|www-data|user|g" /etc/php5/fpm/pool.d/www.conf',
      '/etc/init.d/php5-fpm restart'
    ]);
  }

  /**
   * php5.4, phpUnit, memcached, imagemagick; extensions: pear, curl, memcached, fpm, gd, mysql
   */
  function installPhpFull() {
    $this->installPhpWeb();
    print $this->exec([
      'apt-get -y install php5-gd php5-mysql',
      'apt-get -y install memcached',
      'apt-get -y install imagemagick',
    ]);
  }

  function installNginx() {
    $this->exec([
      'apt-get -y install nginx',
      'cd /etc/nginx',
      'sed -i "s/^\s*#.*$//g" nginx.conf',
      'sed -i "/^\s*$/d" nginx.conf',
      'sed -i "s|www-data|user|g" nginx.conf',
    ]);
  }

  function installNginxFull() {
    $this->installNginx();
    $this->configNginx();
    $this->exec('sudo /etc/init.d/nginx start');
    if (!strstr($this->exec('ps aux | grep nginx', false), 'nginx: master process')) throw new Exception('Problems with installing nginx');
  }

  function configNginx() {
    $this->exec([
      'cd /etc/nginx',
      'sed -i "s|^'. //
      '\s*include /etc/nginx/sites-enabled/\*;'. //
      '|'. //
      '\tserver_names_hash_bucket_size 64;\\n'. //
      '\tinclude /home/user/ngn-env/config/nginx/static/*;\\n'. //
      '\tinclude /home/user/ngn-env/config/nginx/projects/*;\\n'. //
      '\tinclude /home/user/ngn-env/config/nginx/system/*;'. //
      '|g" nginx.conf'
    ]);
  }

  // protected function

  function installRabbitmq() {
    $this->exec([
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
    print $this->exec([
      'debconf-set-selections <<< "postfix postfix/mailname string localhost"',
      'debconf-set-selections <<< "postfix postfix/main_mailer_type string \'Internet Site\'"',
      //'export DEBIAN_FRONTEND=noninteractive',
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

  function installMysql() {
    if (file_exists('/home/user/ngn-env/config/database.php')) {
      $pass = Config::getConstant('/home/user/ngn-env/config/database.php', 'DB_PASS');
    }
    elseif (file_exists('/home/user/.pass')) {
      $pass = file_get_contents('/home/user/.pass');
    }
    else {
      throw new Exception('pass not found');
    }
    //throw new Exception('тут проблема');
    $this->exec([ //
      "//debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password password $pass'", //
      //"debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password_again password $pass'", //
      //"apt-get -y install mysql-server" //
    ]);
  }

}