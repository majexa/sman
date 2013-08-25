<?php

$n = 6;
define('DNS_MASTER', "dnsMaster$n");
define('DNS_SLAVE', "dnsSlave$n");
define('PROJECTS1', "projects1$n");
define('GIT', 'git');

class EnvInstaller {

  public $api;

  function __construct() {
    $this->api = new Digitalocean;
  }

  protected $_servers = [DNS_MASTER, DNS_SLAVE, PROJECTS1];

  protected $addSshKeys = [
// этот ↓ сервер будет доступен с этих ↓ серверов без пароля
    DNS_SLAVE  => [DNS_MASTER, PROJECTS1],
    DNS_MASTER => [GIT, DNS_SLAVE],
    PROJECTS1 => [GIT]
  ];

  function create() {
    foreach ($this->_servers as $v) $this->createServer($v);
  }

  function destroy() {
    foreach ($this->servers() as $v) $this->api->destroyServer($v['id']);
  }

  static $mailUser = 'bot';

  protected function sshKeyIds(array $servers) {
    $ids = [];
    foreach ($servers as &$server) if (($id = $this->api->sshKeyId($server))) $ids[] = $id;
    return implode(',', $ids);
  }

  function createServer($server) {
    output("Creating server '$server'");
    $this->api->createServer($server, !isset($this->addSshKeys[$server]) ? [] : [
      'ssh_key_ids' => $this->sshKeyIds($this->addSshKeys[$server])
    ]);
    output("Waiting for server is active");
    while (true) {
      if ($this->api->server($server)['status'] == 'active') break;
      sleep(5);
    }
    return;
    $password = $this->getPassFromMail($server);
    $this->copyLocalSshKey($server, $password);
    $this->createSshKey($server);
  }

  function destroyServer($server) {
    $this->api->destroyServer($server);
    Config::removeSubVar(DATA_PATH.'/passwords.php', $server);
  }

  protected function getPassFromMail($server) {
    output("Waiting for mail");
    $foundFile = $found = false;
    $serverIp = $this->api->server($server)['ip_address'];
    Misc::checkEmpty($serverIp, 'Server is not activr yet');
    if (($files = glob('/home/'.self::$mailUser.'/Maildir/new/*'))) {
      foreach ($files as $file) {
        try {
          $body = (new MailMimeParser)->decode(['File' => $file])[0]['Body'];
        } catch (Exception $e) {
          throw new Exception("Error while parsing file '$file'");
        }
        $p1 = '/.*IP Address: ([0-9.]+).*/s';
        $p2 = '/.*Password: (\w+).*/s';
        if (!preg_match($p1, $body)) throw new Exception('Wrong body format. Expected "IP Address: ..."');
        if (!preg_match($p2, $body)) throw new Exception('Wrong body format. Expected "Password: ..."');
        $ip = preg_replace($p1, '$1', $body);
        $password = preg_replace($p2, '$1', $body);
        if ($serverIp != $ip) continue;
        $found = true;
        $foundFile = $file;
        break;
      }
    }
    if (!$found) throw new Exception("Mail for server IP=$serverIp not found");
    Config::updateSubVar(include DATA_PATH.'/passwords.php', $server, $password);
    unlink($foundFile);
    return $password;
  }

  function createSshKey($server) {
    output("Generating ssh key on '$server'");
    $this->genSshKey($server);
    output("Getting ssh key from '$server'");
    $sshKey = $this->getSshKey($server);
    output("Adding ssh key to DigitalOcean");
    $this->api->createSshKey($server, $sshKey);
  }

  function servers() {
    $r = [];
    foreach ($this->api->servers() as $v) {
      if (in_array($v['name'], $this->_servers)) $r[] = $v;
    }
    return $r;
  }

  /**
   * Копирует локальный публичный ssh-ключ на $server
   *
   * @param string Имя сервера
   */
  function copyLocalSshKey($server, $password = null) {
    output("Copy ssh key from 'installer' to '$server'");
    if ($password) {
      sys("sshpass -p '$password' ssh-copy-id -i ~/.ssh/id_rsa.pub {$this->api->server($server)['ip_address']}");
    } else {
      sys("ssh-copy-id -i ~/.ssh/id_rsa.pub {$this->api->server($server)['ip_address']}");
    }
  }

  function genSshKey($server) {
    system("ssh {$this->api->server($server)['ip_address']} \"ssh-keygen -f ~/.ssh/id_rsa -t rsa -N ''\"");
  }

  function getSshKey($server) {
    system("scp {$this->api->server($server)['ip_address']}:~/.ssh/id_rsa.pub ~/temp/$server.pub");
    return file_get_contents("/root/temp/$server.pub");
  }

  // ------------------------

  function installDnsMaster() {
sys("ssh {$this->api->server(DNS_MASTER)} << EOF
  apt-get -y install git-core
  git clone ssh://{$this->api->server(GIT)}/~/repo/dns-server.git
  cd dns-server
  sed -i \"s/read slave/slave='{$this->api->server(DNS_SLAVE)}'/\" install.sh
  # remove ssh key generation and copy (from master to slave). already done above
  sed -i \"s/^ssh-keygen.*$//\" install.sh
  sed -i \"s/^cat ~\\/\\.ssh.*$//\" install.sh
    ./install.sh
EOF
");
  }

}
