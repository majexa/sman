<?php

class DnsInstaller {

  public $ei, $nsZone;

  function __construct($nsZone) {
    $this->nsZone = $nsZone;
    $this->ei = new EnvInstaller;
  }

  function install() {
    $this->installSlave();
    $this->installMaster();
  }

  function installMaster() {
    $this->ei->deleteServer('dnsMaster');
    $this->ei->createServer('dnsMaster');
    $this->ei->installPhp('dnsMaster');
    $this->ei->cmd(DNS_MASTER, 'apt-get -y install git-core, bind9');
    $this->ei->cmd(DNS_MASTER, "rm -r ~/ngn-env");
    $this->ei->cmd(DNS_MASTER, "git clone ssh://{$this->ei->api->server(GIT)['ip_address']}/~/repo/dns-server.git");
    $this->ei->addSshKey('dnsMaster', 'dnsSlave');
    $this->ei->cmd(DNS_MASTER, <<<CMD
cd ~/dns-server
sed -i "s/read slave/slave='{$this->ei->api->server(DNS_SLAVE)['ip_address']}'/" install.sh
sed -i "s/^ssh-keygen.*$//" install.sh
sed -i "s/^cat ~\\/\\.ssh.*$//" install.sh
./install.sh
export DEBIAN_FRONTEND=noninteractive
apt-get -y install postfix
sudo pear channel-discover pear.phpunit.de
pear install phpunit/PHPUnit
CMD
);
    $this->updateConfig();
    $this->createNsZone();
  }

  function createNsZone() {
    $this->ei->cmd(DNS_MASTER, Cli::formatRunCmd('(new DnsServer)->createNsZone()', 'NGN_ENV_PATH/dns-server/lib'));
  }

  function updateConfig() {
    $masterIp = $this->ei->api->server(DNS_MASTER)['ip_address'];
    $code = "<?php\n\nreturn ".var_export([
      'nsZone' => $this->nsZone,
      'ip' => '123.123.123.123',
      'masterIp' => $masterIp,
      'slaveIp' => $this->ei->api->server(DNS_SLAVE)['ip_address']
    ], true).';';
    sys("ssh $masterIp ".Cli::formatPutFileCommand($code, '~/dns-server/config.php'));
  }

  function installSlave() {
    $this->ei->deleteServer('dnsSlave');
    $this->ei->createServer('dnsSlave');
    $this->ei->installPhp('dnsSlave');
  }

}
