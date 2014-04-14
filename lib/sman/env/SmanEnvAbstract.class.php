<?php

/**
 * Environment installer
 */
abstract class SmanEnvAbstract extends SmanInstallerBase {

  static function getClass($name) {
    return 'SmanEnv'.ucfirst(SmanCore::serverType($name));
  }

  protected $user = 'user';
  protected $name;

  protected function cloneRepos($repos = []) {
    $this->gitUrl = Config::getVar('git');
    $cmd = [
      'mkdir ~/ngn-env',
      'mkdir ~/ngn-env/logs',
      'cd ~/ngn-env',
    ];
    foreach ($repos as $repo) {
      $b = '';
      if (is_array($repo)) {
        $b = " -b {$repo[1]}";
        $repo = $repo[0];
      }
      $cmd[] = "git clone{$b} $this->gitUrl/$repo.git";
    }
    print $this->exec($cmd);
  }

  function install() {
    $this->_install();
  }

  /**
   * Создаёт стандартный конфиг для сервера
   */
  function createConfig($baseDomain) {
    // auto vhost folders. look at PmServerConfigAbstract class
    $this->exec([
      "mkdir -p ~/ngn-env/config/nginx",
      "mkdir ~/ngn-env/config/nginx/static",
      "mkdir ~/ngn-env/config/nginx/projects",
      "mkdir ~/ngn-env/config/nginx/system",
      //"mkdir ~/ngn-env/config/remoteServers"
    ]);
    /*
    $server = [
      'host'          => $this->serverHost(),
      'baseDomain'    => $this->name.'.'.Config::getVar('baseDomain'),
      'sType'         => 'prod',
      'os'            => 'linux',
      'dbUser'        => 'root',
      'dbPass'        => $pass,
      'dbHost'        => 'localhost',
      'sshUser'       => 'user',
      'sshPass'       => $pass,
      'ngnEnvPath'    => '/home/user/ngn-env',
      'ngnPath'       => "{ngnEnvPath}/ngn",
      'webserver'     => 'nginx',
      'webserverP'    => '/etc/init.d/nginx',
      'nginxFastcgiPassUnixSocket' => true,
      'prototypeDb'   => 'file',
      'dnsMasterHost' => Config::getSubVar('servers', 'dnsMaster')
    ];
    */
    if ($this->serverName == 'local') {
      $pass = file_get_contents('/home/user/.pass');
    } else {
      $pass = SmanConfig::getSubVar('userPasswords', $this->serverHost());
    }
    //$server = ['baseDomain' => $this->name.'.'.Config::getVar('baseDomain')];
    $server = [
      'baseDomain' => $baseDomain,
      'nginxFastcgiPassUnixSocket' => true,
      'dbPass' => $pass
    ];
    $this->ftp->putContents('/home/user/ngn-env/config/server.php', FileVar::formatVar($server));
    // setup common database config to whole server
    $this->ftp->putContents('/home/user/ngn-env/config/database.php', '<?php

$server = require __DIR__."/server.php";
setConstant("DB_HOST", $server["dbHost"]);
setConstant("DB_USER", $server["dbUser"]);
setConstant("DB_PASS", $server["dbPass"]);
setConstant("DB_LOGGING", false);
setConstant("DB_NAME", PROJECT_KEY);
'
    );
  }

}