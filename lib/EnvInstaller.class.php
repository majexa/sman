<?php

$n = '';
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
    $this->api->createServer($server);
    output("Waiting for server is active");
    while (true) {
      if ($this->api->server($server)['status'] == 'active') break;
      sleep(5);
    }
    $this->storePassFromMail($server);
    $config = "StrictHostKeyChecking=no\nLogLevel=quiet\nUserKnownHostsFile=/dev/null";
    $this->cmd($server, "echo '$config' > ~/.ssh/config");
  }

  function destroyServer($server) {
    $this->api->destroyServer($server);
    Config::removeSubVar(DATA_PATH.'/passwords.php', $server);
    output("Waiting for server is removed");
    while ($this->api->server($server, false)) sleep(5);
    output("Removed");
  }

  protected function storePassFromMail($server) {
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
    Config::updateSubVar(DATA_PATH.'/passwords.php', $server, $password);
    unlink($foundFile);
  }

  function servers() {
    $r = [];
    foreach ($this->api->servers() as $v) {
      if (in_array($v['name'], $this->_servers)) $r[] = $v;
    }
    return $r;
  }

  function sshpass($server) {
    $password = Config::getFileVar(DATA_PATH.'/passwords.php', false)[$server];
    Misc::checkEmpty($password);
    return "sshpass -p '$password'";
  }

  function getCmd($server, $cmd) {
    $s = $this->api->server($server);
    if (strstr($cmd, "\n")) $cmd = "<< EOF\n$cmd\nEOF";
    return $this->sshpass($server)." ssh {$s['ip_address']} $cmd";
  }

  function cmd($server, $cmd) {
    sys($this->getCmd($server, $cmd));
  }

  function genSshKey($server) {
    $this->cmd($server, "\"ssh-keygen -f ~/.ssh/id_rsa -t rsa -N ''\"");
  }

  function getSshKey($server) {
    sys($this->sshpass($server)." scp {$this->api->server($server)['ip_address']}:~/.ssh/id_rsa.pub ~/temp/$server.pub");
    return file_get_contents("/root/temp/$server.pub");
  }

  function uploadSshKey($sshKey, $server) {
    sys("scp {$this->api->server($server)['ip_address']}:~/.ssh/id_rsa.pub ~/temp/$server.pub");
  }


}
