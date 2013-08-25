<?php

class DnsInstaller {

  public $ei;

  function __construct() {
    $this->ei = new EnvInstaller;
  }

  function installMaster() {
    $this->ei->cmd(DNS_MASTER, <<<CMD
apt-get -y install git-core
{$this->ei->getCmd(GIT, "git clone ssh://{$this->ei->api->server(GIT)['ip_address']}/~/repo/dns-server.git")}
cd dns-server
sed -i \"s/read slave/slave='{$this->ei->api->server(DNS_SLAVE)['ip_address']}'/\" install.sh
# remove ssh key generation and copy (from master to slave). already done above
sed -i \"s/^ssh-keygen.*$//\" install.sh
sed -i \"s/^cat ~\\/\\.ssh.*$//\" install.sh
# ./install.sh
CMD
    );
  }

}
