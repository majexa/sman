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

  function delete() {
    foreach ($this->servers() as $v) $this->api->deleteServer($v['id']);
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
    output("Wait 30 sec");
    sleep(30); // need to try connection here
    $this->addSshKey($server, 'git');
  }

  function configSsh($server, $dir) {
    output("Configuring server '$server'");
    $config = "StrictHostKeyChecking=no\nLogLevel=quiet\nUserKnownHostsFile=/dev/null";
    $this->cmd($server, <<<CMD
if grep -q StrictHostKeyChecking=no $dir/.ssh/config; then
  echo 'Already installed'
else
  echo '$config' > $dir/.ssh/config
fi
CMD
    );
  }

  function deleteServer($server, $strict = true) {
    $exists = $this->api->server($server, false);
    if ($strict and !$exists) throw new Exception("Server '$server' not exists");
    if (!$exists) {
      output("Server '$server' not exists. Skipped");
      return;
    }
    $this->api->deleteServer($server);
    $this->removeSshKey($server, 'git');
    Config::removeSubVar(DATA_PATH.'/passwords.php', $server);
    output("Waiting for server is removed");
    while ($this->api->server($server, false)) sleep(5);
    output("Removed");
  }

  function findMailFiles($server) {
    $r = [];
    $serverIp = $this->api->server($server)['ip_address'];
    Misc::checkEmpty($serverIp, "Server '$server' is not active yet");
    if (($files = glob('/home/'.self::$mailUser.'/Maildir/new/*'))) {
      foreach ($files as $file) {
        $fileName = basename($file);
        try {
          $d = (new MailMimeParser)->decode(['File' => $file]);
          $subj  =$d[0]['Headers']['subject:'];
          $body = $d[0]['Body'];
        } catch (Exception $e) {
          throw new Exception("Error while parsing file '$file'");
        }
        $p1 = '/.*IP Address: ([0-9.]+).*/s';
        $p2 = '/.*Password: (\w+).*/s';
        $p3 = '/.*\((\w+)\) - DigitalOcean.*/';
        if (!preg_match($p1, $body)) {
          output('Skipped '.$fileName.'. Wrong body format. Expected "IP Address: ..."');
          continue;
        }
        if (!preg_match($p2, $body)) {
          output('Skipped '.$fileName.'. Wrong body format. Expected "Password: ..."');
          continue;
        }
        if (!preg_match($p3, $subj)) {
          output('Skipped '.$fileName.'. Wrong subject format. Expected "(serverName) - DigitalOcean"');
          continue;
        }
        $mServerIp = preg_replace($p1, '$1', $body);
        $mServer = preg_replace($p3, '$1', $subj);
        if ($server != $mServer) continue;
        if ($serverIp != $mServerIp) continue;
        $r[] = [
          'file' => $file,
          'ip' => $mServerIp,
          'password' => preg_replace($p2, '$1', $body)
        ];
      }
    }
    return $r;
  }

  protected function storePassFromMail($server) {
    output("Waiting for mail");
    $files = $this->findMailFiles($server);
    if (count($files) == 0) throw new Exception("Mail for server '$server' not found");
    if (count($files) > 1) throw new Exception("Can not be more than 1 email for server '$server'");
    Config::updateSubVar(DATA_PATH.'/passwords.php', $server, $files[0]['password']);
    unlink($files[0]['file']);
  }

  function servers() {
    $r = [];
    foreach ($this->api->servers() as $v) {
      if (in_array($v['name'], $this->_servers)) $r[] = $v;
    }
    return $r;
  }

  function password($server) {
    $password = Config::getFileVar(DATA_PATH.'/passwords.php', false)[$server];
    Misc::checkEmpty($password, "server '$server' password is empty");
    return $password;
  }

  function sshpass($server) {
    return "sshpass -p '{$this->password($server)}'";
  }

  function getCmd($server, $cmd) {
    $s = $this->api->server($server);
    if (strstr($cmd, "\n")) $cmd = "<< EOF\n$cmd\nEOF";
    return $this->sshpass($server)." ssh -T {$s['ip_address']} $cmd";
  }

  function cmd($server, $cmd) {
    $cmd = str_replace("\r", "\n", $cmd);
    //output("$server: $cmd");
    return sys($this->getCmd($server, $cmd));
  }

  function cmdFile($server, $cmd) {
    file_put_contents("/root/temp/$server-install", trim($cmd));
    $this->cmd($server, "mkdir -p ~/temp");
    sys($this->sshpass($server)." scp ~/temp/$server-install {$this->api->server($server)['ip_address']}:~/temp/$server-install");
    $this->cmd($server, "chmod +x ~/temp/$server-install");
    $this->cmd($server, "~/temp/$server-install");
  }

  function addSshKey($fromServer, $toServer, $user = null) {
    if ($this->sshKeyIsInAuthorized($fromServer, $toServer, $user)) return;
    $this->genSshKey($fromServer, $user);
    $sshKey = $this->getSshKey($fromServer, $user);
    output("Adding ssh key from '$fromServer' to '$toServer'");
    $this->uploadSshKey($sshKey, $toServer);
  }

  function sshKeyIsInAuthorized($fromServer, $toServer, $user = null) {
    $sshKey = $this->getSshKey($fromServer, $user);
    return (bool)$this->cmd($toServer, "'grep \"$sshKey\" ~/.ssh/authorized_keys'");
  }

  function removeSshKey($fromServer, $toServer, $user = null) {
    $sshKey = $this->getSshKey($fromServer, $user);
    $this->cmd($toServer, <<<CMD
grep -v "$sshKey" ~/.ssh/authorized_keys > ~/.ssh/authorized_keys_tmp
mv ~/.ssh/authorized_keys_tmp ~/.ssh/authorized_keys
CMD
);
  }

  function addLocalSshKey($toServer) {
    $this->uploadSshKey(file_get_contents('/root/.ssh/id_rsa.pub'), $toServer);
  }

  function showServer($server) {
    var_export($this->api->server($server));
  }

  function genSshKey($server, $user = null) {
    if ($user) $suCmd = "su $user\n";
    $this->cmd($server, <<<CMD
{$suCmd}if [ ! -f ~/.ssh/id_rsa ]; then
  ssh-keygen -q -f ~/.ssh/id_rsa -t rsa -N ''
fi
CMD
);
  }

  protected $getSshKeyCache;

  function getSshKey($server, $user = null) {
    if ($this->getSshKeyCache[$server.$user]) return $this->getSshKeyCache[$server.$user];
    $dir = $user ? "/home/$user" : '/root';
    $this->configSsh($server, $dir);
    $this->genSshKey($server, $user);
    output("Getting ssh key from '$server'");
    print sys($this->sshpass($server)." scp {$this->api->server($server)['ip_address']}:$dir/.ssh/id_rsa.pub /root/temp/$server.pub");
    $r = file_get_contents("/root/temp/$server.pub");
    File::delete("/root/temp/$server.pub");
    return $this->getSshKeyCache[$server.$user] = trim($r);
  }

  function uploadSshKey($sshKey, $server) {
    sys("echo '$sshKey' | ".$this->sshpass($server)." ssh root@{$this->api->server($server)['ip_address']} 'cat >> ~/.ssh/authorized_keys'");
  }

  function installPhp($server) {
    $this->cmd($server, <<<CMD
apt-get -y install python-software-properties
apt-get update
add-apt-repository --yes ppa:ondrej/php5-oldstable
apt-get update
apt-get -y install php5-cli
CMD
);
  }

}
