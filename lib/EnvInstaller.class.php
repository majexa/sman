<?php

$n = '';
define('DNS_MASTER', "dnsMaster$n");
define('DNS_SLAVE', "dnsSlave$n");
define('PROJECTS1', "projects1$n");
define('GIT', 'git');

class EnvInstaller extends DigitaloceanRemoteSsh {
use DebugOutput;

  protected function isDebug() {
    return true;
  }

  protected $tempFolder;

  function __construct() {
    parent::__construct();
    $this->tempFolder = DATA_PATH.'/temp';
    Dir::make($this->tempFolder);
  }

  /*
  protected $_servers = [DNS_MASTER, DNS_SLAVE, PROJECTS1];

  function create() {
    foreach ($this->_servers as $v) $this->createServer($v);
  }

  function delete() {
    foreach ($this->servers() as $v) $this->api->deleteServer($v['id']);
  }
  */

  static $mailUser = 'bot';

  protected function sshKeyIds(array $servers) {
    $ids = [];
    foreach ($servers as &$server) if (($id = $this->api->sshKeyId($server))) $ids[] = $id;
    return implode(',', $ids);
  }


  function createServer($server) {
    $this->checkEmail();
    $this->storePassFromMail($server);
    $this->output("Wait 30 sec");
    sleep(30); // need to try connection here
    $this->addSshKey($server, 'git', 'root');
  }

  function configSsh($server, $dir) {
    $this->output("Configuring SSH on server '$server'");
    $config = "StrictHostKeyChecking=no\nLogLevel=quiet\nUserKnownHostsFile=/dev/null";
    $this->cmd($server, <<<CMD
if grep -q StrictHostKeyChecking=no ~/.ssh/config; then
  echo 'Already installed'
else
  echo '$config' > ~/.ssh/config
fi
CMD
    );
  }

  function deleteServer($server, $strict = true) {
    $exists = $this->api->server($server, false);
    if ($strict and !$exists) throw new Exception("Server '$server' not exists");
    if (!$exists) {
      $this->output("Server '$server' not exists. Skipped");
      return;
    }
    $this->api->deleteServer($server);
    $this->removeSshKey($server, 'git');
    FileVar::removeSubVar(DATA_PATH.'/passwords.php', $server);
    $this->output("Waiting for server is removed");
    while ($this->api->server($server, false)) sleep(5);
    $this->output("Removed");
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
          $subj = $d[0]['Headers']['subject:'];
          $body = $d[0]['Body'];
        } catch (Exception $e) {
          throw new Exception("Error while parsing file '$file'");
        }
        $p1 = '/.*IP Address: ([0-9.]+).*/s';
        $p2 = '/.*Password: (\w+).*/s';
        $p3 = '/.*\((\w+)\) - DigitalOcean.*/';
        if (!preg_match($p1, $body)) {
          $this->output('Skipped '.$fileName.'. Wrong body format. Expected "IP Address: ..."');
          continue;
        }
        if (!preg_match($p2, $body)) {
          $this->output('Skipped '.$fileName.'. Wrong body format. Expected "Password: ..."');
          continue;
        }
        if (!preg_match($p3, $subj)) {
          $this->output('Skipped '.$fileName.'. Wrong subject format. Expected "(serverName) - DigitalOcean"');
          continue;
        }
        $mServerIp = preg_replace($p1, '$1', $body);
        $mServer = preg_replace($p3, '$1', $subj);
        if ($server != $mServer) continue;
        if ($serverIp != $mServerIp) continue;
        $r[] = [
          'file'     => $file,
          'ip'       => $mServerIp,
          'password' => preg_replace($p2, '$1', $body)
        ];
      }
    }
    return $r;
  }

  protected function storePassFromMail($server) {
    $this->output("Waiting for mail");
    $files = $this->findMailFiles($server);
    if (count($files) == 0) throw new Exception("Mail for server '$server' not found");
    if (count($files) > 1) throw new Exception("Can not be more than 1 email for server '$server'");
    SmanConfig::updateSubVar('passwords', $server, $files[0]['password']);
    unlink($files[0]['file']);
  }

  /*
  function servers() {
    $r = [];
    foreach ($this->api->servers() as $v) {
      if (in_array($v['name'], $this->_servers)) $r[] = $v;
    }
    return $r;
  }
  */

  function cmdFile($server, $cmd) {
    if ($server == 'local') {
      return sys($cmd);
      return;
    }
    file_put_contents("{$this->tempFolder}/$server-install", trim($cmd));
    $this->cmd($server, "mkdir -p {$this->tempFolder}");
    sys($this->sshpass($server)." scp {$this->tempFolder}/$server-install {$this->api->server($server)['ip_address']}:{$this->tempFolder}/$server-install");
    $this->cmd($server, "chmod +x {$this->tempFolder}/$server-install");
    $this->cmd($server, "{$this->tempFolder}/$server-install");
  }

  function sshKeyIsInAuthorized($fromServer, $toServer, $user = 'root') {
    $this->output("Check SSH key for server '$fromServer' at '$toServer'");
    $sshKey = $this->getSshKey($fromServer, $user);
    $r = (bool)$this->cmd($toServer, "'grep \"$sshKey\" ~/.ssh/authorized_keys'");
    $this->output("SSH key ".($r ? 'already exists' : 'does not exists'));
    return $r;
  }

  function removeSshKey($fromServer, $toServer, $user = 'root') {
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

  function genSshKey($server, $user = 'root') {
    if ($user) $suCmd = "su $user\n";
    $this->cmd($server, <<<CMD
{$suCmd}if [ ! -f ~/.ssh/id_rsa ]; then
  ssh-keygen -q -f ~/.ssh/id_rsa -t rsa -N ''
fi
CMD
    );
  }

  protected $getSshKeyCache;

  function getSshKey($server, $user = 'root') {
    if ($this->getSshKeyCache[$server.$user]) return $this->getSshKeyCache[$server.$user];
    $dir = $user == 'root' ? '/root' : "/home/$user";
    $this->configSsh($server, $dir);
    $this->genSshKey($server, $user);
    $this->output("Getting SSH key from '$server'");
    print sys($this->sshpass($server)." scp {$this->api->server($server)['ip_address']}:$dir/.ssh/id_rsa.pub {$this->tempFolder}/$server.pub");
    $r = file_get_contents("{$this->tempFolder}/$server.pub");
    File::delete("{$this->tempFolder}/temp/$server.pub");
    $this->output("SSH key: $r");
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
apt-get -y install php5-cli php5-curl php5-memcached php-pear php5-fpm php5-gd php5-mysql php5-dev
sudo pear channel-discover pear.phpunit.de
pear install phpunit/PHPUnit
export DEBIAN_FRONTEND=noninteractive
apt-get -y install postfix
CMD
    );
  }

}