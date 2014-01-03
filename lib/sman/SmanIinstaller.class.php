<?php

/**
 * Instance installer
 */
abstract class SmanIinstaller {

  /**
   * @param $type
   * @param $name
   * @return SmanIinstaller
   */
  static function get($type, $name) {
    $class = 'SmanIinstaller'.ucfirst($type);
    $connection = new SmanPasswordConnection($name);
    return new $class($connection);
  }

  protected $scp, $sftp;

  function __construct(SshConnection $sshConnection) {
    $this->scp = new SshScp($sshConnection);
    $this->sftp = new SshSftp($sshConnection);
    $this->ssh = new Ssh($sshConnection);
  }

  function install() {
    $this->_install();
    //$this->addSshKey($server, 'git', 'root');
    $this->runTests();
  }

  abstract function _install();

  protected function createUser($user, $pass, $super = false) {
    $this->ssh->exec([
      "useradd -d /home/$user -s /bin/bash -p `openssl passwd -1 $pass` $user",
      "mkdir /home/$user && chown $user /home/$user"
    ]);
    if ($super) {
      $this->ssh->exec([
        'apt-get -y install sudo',
        "%$user ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers"
      ]);
    }
  }

  protected function installPhp() {
    print $this->ssh->exec([
      'apt-get -y install python-software-properties',
      'apt-get update',
      'add-apt-repository --yes ppa:ondrej/php5-oldstable',
      'apt-get update',
      'apt-get -y install php5-cli php5-dev',
    ]);
    print $this->ssh->exec([
      'wget http://archive.ubuntu.com/ubuntu/pool/universe/libs/libssh2/libssh2-1_1.4.2-1.1_amd64.deb',
      'wget http://archive.ubuntu.com/ubuntu/pool/universe/p/php-ssh2/libssh2-php_0.11.3-0.1build1_amd64.deb',
      'dpkg -i libssh2-1_1.4.2-1.1_amd64.deb',
      'dpkg -i libssh2-php_0.11.3-0.1build1_amd64.deb'
    ]);
  }

  protected function installPhpFull() {
    print $this->ssh->exec([
      'apt-get -y install php5-curl php5-memcached php-pear php5-fpm php5-gd php5-mysql php5-dev',
      'sudo pear channel - discover pear.phpunit.de',
      'pear install phpunit / PHPUnit'
    ]);
  }

  protected function installGit() {
    print $this->ssh->exec('apt-get -y install git-core');
  }

  protected function installMail() {
    print $this->ssh->exec([
      'export DEBIAN_FRONTEND=noninteractive',
      'apt-get -y install postfix'
    ]);
  }

  protected function cloneEnv($repos = []) {
    $git = 'http:://git.majexa.ru/~/repo';
    $cmd = [
      'mkdir ~/ngn-env',
      'cd ngn-env',
    ];
    foreach ($repos as $repo) $cmd[] = "git clone $git/$repo.git";
    $this->ssh->exec($cmd);
  }

  protected function runTests() {
  }

}