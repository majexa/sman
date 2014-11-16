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

  function createMailBotUser() {
    $this->exec("useradd -m -s /bin/bash bot");
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

  /**
   * installPhpBasic + installPhpAdvanced
   */
  function installPhp() {
    $this->installPhpBasic();
    $this->installPhpAdvanced();
  }

  /**
   * installPhp + fpm, memcached + config fpm
   */
  function installPhpWeb() {
    $this->installPhp();
    print $this->exec([
      'apt-get -y install memcached php5-memcached php5-fpm',
    ]);
    print $this->exec([
      'sed -i "s|www-data|user|g" /etc/php5/fpm/pool.d/www.conf',
      '/etc/init.d/php5-fpm restart'
    ]);
  }

  /**
   * installPhpWeb + mysql, gd, imagemagick
   */
  function installPhpFull() {
    $this->installPhpWeb();
    print $this->exec([
      'apt-get -y install php5-gd php5-mysql',
      'apt-get -y install imagemagick',
    ]);
  }

  function configPhp() {
    file_put_contents('/etc/php5/fpm/php.ini', file_get_contents('/etc/php5/fpm/php.ini'));
    //die2();
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
      '\tinclude /home/user/ngn-env/config/nginx/static/*;\\n'. //
      '\tinclude /home/user/ngn-env/config/nginx/projects/*;\\n'. //
      '\tinclude /home/user/ngn-env/config/nginx/system/*;'. //
      '|g" nginx.conf'
    ]);
  }

  function installRabbitmq() {
    print $this->exec([
      'rm -rf temp',
      'mkdir temp',
      'cd temp',
      //
      'wget https://github.com/alanxz/rabbitmq-c/releases/download/v0.5.1/rabbitmq-c-0.5.1.tar.gz',
      'tar -zxvf rabbitmq-c-0.5.1.tar.gz',
      'cd rabbitmq-c-0.5.1',
      './configure',
      'make',
      'sudo make install',
      //
      'cd ..',
      //
      'wget http://pecl.php.net/get/amqp -O amqp.tar.gz',
      'tar -zxvf amqp.tar.gz',
      'cd amqp-1.4.0',
      'phpize',
      './configure --with-amqp',
      'make',
      'sudo make install',
      //
      'sudo echo "extension=amqp.so" | sudo tee /etc/php5/mods-available/amqp.ini',
      'sudo ln -s ../mods-available/amqp.ini /etc/php5/conf.d/20-amqp.ini',
    ]);

  }


  protected function installPiwik() {
  }

  /**
   * postfix
   */
  function installMail() {
    Misc::checkEmpty(Config::getSubVar('botEmail', 'domain'));
    print $this->exec([
      'bash -c \'debconf-set-selections <<< "postfix postfix/mailname string localhost"\'',
      'bash -c \'debconf-set-selections <<< "postfix postfix/main_mailer_type string \\"Internet Site\\""\'',
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
      'bash -c \'debconf-set-selections <<< "server-5.5 mysql-server/root_password password '.$pass.'"\'', //
      'bash -c \'debconf-set-selections <<< "server-5.5 mysql-server/root_password_again password '.$pass.'"\'', //
      "apt-get -y install mysql-server" //
    ]);
  }

  function installNodejs() {
    $this->exec([ //
      'apt-get -y install python-software-properties python g++ make',
      'add-apt-repository -y ppa:chris-lea/node.js',
      'apt-get update',
      'apt-get -y install nodejs'
    ]);
  }

  function installPhantomjs() {
    $this->installNodejs();
    $this->exec([
      'cd /usr/local/share',
      'wget https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-1.9.7-linux-x86_64.tar.bz2',
      'tar xjf phantomjs-1.9.7-linux-x86_64.tar.bz2',
      'ln -s /usr/local/share/phantomjs-1.9.7-linux-x86_64/bin/phantomjs /usr/local/share/phantomjs',
      'ln -s /usr/local/share/phantomjs-1.9.7-linux-x86_64/bin/phantomjs /usr/local/bin/phantomjs',
      'ln -s /usr/local/share/phantomjs-1.9.7-linux-x86_64/bin/phantomjs /usr/bin/phantomjs'
    ]);
  }

}