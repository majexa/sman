<?php

/**
 * Instance installer
 */
abstract class SmanInstanceAbstract extends SmanInstallerBase {

  /**
   * @param string $name Server Name
   * @return string
   * @throws Exception
   */
  static function getClass($name) {
    return 'SmanInstance'.ucfirst(SmanCore::serverType($name));
  }

  /**
   * @param $name
   * @return SmanInstanceAbstract
   */
  static function get($name) {
    $class = self::getClass($name);
    return new $class($name);
  }

  protected $user = 'root';

  function install() {
    $this->installCore();
    parent::install();
  }

  public $userPass;

  protected function createUser() {
    $user = 'user';
    $this->userPass = 'CHANGE_IT';//Misc::randString(7);
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
      'locale-gen "ru_RU.UTF-8"', // for ubuntu 16.04
      'apt-get -y install mc git-core',
    ]);
    $this->createUser();
  }

  function installPhpBasic() {
    print $this->exec([
      'apt-get -y install python-software-properties software-properties-common',
      'apt-get install -y language-pack-en-base && export LC_ALL=en_US.UTF-8 && export LANG=en_US.UTF-8',
      'apt-get update',
      'add-apt-repository --yes ppa:ondrej/php',
      'apt-get update',
      'apt-get -y install php5.6 php5.6-mbstring',
      'sed -i "s/short_open_tag = Off/short_open_tag = On/g" /etc/php/5.6/cli/php.ini',
      'sed -i "s/display_errors = Off/display_errors = On/g" /etc/php/5.6/cli/php.ini',
    ]);
  }

  function installPhpAdvanced() {
    print $this->exec([
      //'apt-get -y install php5.6-curl php5.6-dev',
      'apt-get -y install php5.6-curl',
    ]);
//    print $this->exec([
//      'pear channel-discover pear.phpunit.de',
//      'pear install phpunit/PHPUnit',
//    ]);
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
      'apt-get -y install memcached php5.6-memcached php5.6-fpm',
      'sed -i "s/short_open_tag = Off/short_open_tag = On/g" /etc/php/5.6/fpm/php.ini',
      'sed -i "s/display_errors = Off/display_errors = On/g" /etc/php/5.6/fpm/php.ini',
    ]);
    print $this->exec([
      'sed -i "s|www-data|user|g" /etc/php/5.6/fpm/pool.d/www.conf',
      '/etc/init.d/php5.6-fpm restart'
    ]);
  }

  /**
   * installPhpWeb + mysql, gd, imagemagick
   */
  function installPhpFull() {
    $this->installPhpWeb();
    print $this->exec([
      'apt-get -y install php5.6-gd php5.6-mysql',
      'apt-get -y install imagemagick',
    ]);
  }

  public $dockerBaseImage = false;

  function installNginx() {
    $r = [];
    if (!$this->dockerBaseImage) {
      $r[] = 'apt-get -y purge apache2';
    }
    $r = array_merge($r, [
      'apt-get -y install nginx',
      'sed -i "s/^\s*#.*$//g" /etc/nginx/nginx.conf',
      'sed -i "/^\s*$/d" /etc/nginx/nginx.conf',
      'sed -i "s|www-data|user|g" /etc/nginx/nginx.conf',
      'sed -i "s|^'. //
      '\s*include /etc/nginx/sites-enabled/\*;'. //
      '|'. //
      '\tinclude /home/user/ngn-env/config/nginx/all.conf;\\n"'
    ]);
    $this->exec($r);
  }

  function installNginxFull() {
    $this->installNginx();
    $this->exec('sudo /etc/init.d/nginx start');
    $r = $this->exec('ps aux | grep nginx');
    if (!$this->disable) {
      if (!strstr($r, 'nginx: master process')) {
        throw new Exception('Problems with installing nginx');
      }
    }
  }

  function installRabbitmq() {
    print $this->exec([
      'sudo apt-get -y install rabbitmq-server',
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
      'wget http://pecl.php.net/get/amqp-1.6.0.tgz',
      'tar -zxvf amqp-1.6.0.tgz',
      'cd amqp-1.6.0',
      'phpize5',
      './configure --with-amqp',
      'make',
      'sudo make install',
      //
      'sudo echo "extension=amqp.so" | sudo tee /etc/php5/mods-available/amqp.ini',
    ]);
    $this->phpModSymlink('amqp');
  }

  protected function phpModSymlink($name) {
    print $this->exec([
      'sudo ln -sf /etc/php5/mods-available/'.$name.'.ini /etc/php5/cli/conf.d/20-'.$name.'.ini',
      'sudo ln -sf /etc/php5/mods-available/'.$name.'.ini /etc/php5/fpm/conf.d/20-'.$name.'.ini'
    ]);
  }

  function installPhpSsh2() {
    print $this->exec([
      'rm -rf temp',
      'mkdir temp',
      'cd temp',
      // http://www.libssh2.org/
      'wget http://www.libssh2.org/download/libssh2-1.4.3.tar.gz',
      'tar vxzf libssh2-1.4.3.tar.gz',
      'cd libssh2-1.4.3',
      './configure',
      'make',
      'sudo make install',
      //
      'cd ..',
      // http://pecl.php.net/package/ssh2
      'wget http://pecl.php.net/get/ssh2-0.12.tgz',
      'tar vxzf ssh2-0.12.tgz',
      'cd ssh2-0.12',
      'phpize5',
      './configure --with-ssh2',
      'make',
      'sudo make install',
      //
      'sudo echo "extension=ssh2.so" | sudo tee /etc/php5/mods-available/ssh2.ini',
    ]);
    $this->phpModSymlink('ssh2');
  }

  protected function installPiwik() {
  }

  /**
   * postfix
   */
  function installMail() {
    if (!Config::getSubVar('botEmail', 'domain', false, true)) return;
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

//  /**
//   * Создаёт DNS-запись базового хоста сервера
//   */
//  function installDns() {
//    $this->ei->addSshKey($this->serverName, 'dnsMaster', 'user');
//    $this->createBaseZone();
//  }
//
//  protected function createBaseZone() {
//    $this->ei->cmd('dnsMaster', Cli::formatRunCmd( //
//      "(new DnsServer)->createZone('{$this->baseDomain()}', ". //
//      "'{$this->ei->api->server($this->serverName)['ip_address']}')", //
//      'dnss/lib'));
//  }
//
//  /**
//   * Удаляет DNS-запись базового хоста сервера
//   */
//  function removeDns() {
//    $this->ei->removeSshKey($this->serverName, 'dnsMaster', 'user');
//    $this->ei->cmd('dnsMaster', Cli::formatRunCmd( //
//      "(new DnsServer)->deleteZone('{$this->baseDomain()}')", //
//      'dnss/lib'));
//  }

  protected function runTests() {
  }

  function installMysql() {
    if (file_exists(NGN_ENV_PATH.'/config/database.php')) {
      $pass = Config::getConstant(NGN_ENV_PATH.'/config/database.php', 'DB_PASS');
    }
    elseif (file_exists(dirname(NGN_ENV_PATH).'/.pass')) {
      $pass = file_get_contents(dirname(NGN_ENV_PATH).'/.pass');
    }
    else {
      throw new Exception('Database password not found');
    }
    $this->exec([ //
      'bash -c \'debconf-set-selections <<< "server-5.5 mysql-server/root_password password '.$pass.'"\'', //
      'bash -c \'debconf-set-selections <<< "server-5.5 mysql-server/root_password_again password '.$pass.'"\'', //
      "apt-get -y install mysql-server" //
    ]);
    $this->exec('/etc/mysql/conf.d/disable_strict_mode.cnf < '.'echo "[mysqld]\nsql_mode=IGNORE_SPACE,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"');
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

  function installUglify() {
    $this->exec('npm install -g uglify-js');
    $this->exec('npm install -g uglifycss');
  }

  function installFfmpeg() {
    $this->exec([
      'sudo apt-get update',
      'sudo apt-get -y --force-yes install autoconf automake build-essential libass-dev libfreetype6-dev libgpac-dev libsdl1.2-dev libtheora-dev libtool libva-dev libvdpau-dev libvorbis-dev libxcb1-dev libxcb-shm0-dev libxcb-xfixes0-dev pkg-config texi2html zlib1g-dev',
      'mkdir ~/ffmpeg_sources',
      // yasm
      'cd ~/ffmpeg_sources',
      'wget http://www.tortall.net/projects/yasm/releases/yasm-1.3.0.tar.gz',
      'tar xzvf yasm-1.3.0.tar.gz',
      'cd yasm-1.3.0',
      './configure --prefix="$HOME/ffmpeg_build" --bindir="$HOME/bin"',
      'make',
      'make install',
      'make distclean',
      // libx264
      'sudo apt-get -y install libx264-dev',
      // libmp3lame
      'sudo apt-get -y install libmp3lame-dev',
      // libvpx
      'cd ~/ffmpeg_sources',
      'wget http://webm.googlecode.com/files/libvpx-v1.3.0.tar.bz2',
      'tar xjvf libvpx-v1.3.0.tar.bz2',
      'cd libvpx-v1.3.0',
      'PATH="$HOME/bin:$PATH" ./configure --prefix="$HOME/ffmpeg_build" --disable-examples',
      'PATH="$HOME/bin:$PATH" make',
      'make install',
      'make clean',
      // ffmpeg
      'cd ~/ffmpeg_sources',
      'wget http://ffmpeg.org/releases/ffmpeg-snapshot.tar.bz2',
      'tar xjvf ffmpeg-snapshot.tar.bz2',
      'cd ffmpeg',
      'PATH="$HOME/bin:$PATH" PKG_CONFIG_PATH="$HOME/ffmpeg_build/lib/pkgconfig" ./configure \
  --prefix="$HOME/ffmpeg_build" \
  --extra-cflags="-I$HOME/ffmpeg_build/include" \
  --extra-ldflags="-L$HOME/ffmpeg_build/lib" \
  --bindir="$HOME/bin" \
  --enable-gpl \
  --enable-libass \
  --enable-libfreetype \
  --enable-libmp3lame \
  --enable-libtheora \
  --enable-libvorbis \
  --enable-libvpx \
  --enable-libx264 \
  --enable-nonfree',
      'PATH="$HOME/bin:$PATH" make',
      'make install',
      'make distclean',
      'hash -r'

    ]);
  }

}