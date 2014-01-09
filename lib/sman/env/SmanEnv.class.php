<?php

/**
 * Environment installer
 */
abstract class SmanEnv extends SmanInstanceAbstract {

  protected $user = 'user';
  static $classPrefix = 'SmanEnv';

  protected function cloneNgnEnv($repos = []) {
    $cmd = [
      'mkdir ~/ngn-env',
      'cd ~/ngn-env',
    ];
    foreach ($repos as $repo) $cmd[] = "git clone $this->gitUrl/$repo.git";
    $this->ssh->exec($cmd);
  }

  function install() {
    $this->_install();
  }

  function baseDomain() {
    return $this->name.'.'.Config::getVar('baseDomain');
  }

  function createConfig() {
    $this->ssh->exec([
      "mkdir -p ~/ngn-env/config/nginx",
      "mkdir ~/ngn-env/config/nginxProjects",
      "mkdir ~/ngn-env/config/remoteServers"
    ]);
    return;
    $pass = SmanConfig::getSubVar('userPasswords', $this->sshConnection->host);
    $this->sftp->putContents('~/ngn-env/config/server.php', "<?php\n\nreturn ".var_export([
        'host'          => $this->sshConnection->host,
        'baseDomain'    => $this->baseDomain(),
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
        'prototypeDb'   => 'file',
        'dnsMasterHost' => Config::getSubVar('servers', 'dnsMaster')
      ], true).';');
    $this->sftp->putContents('~/ngn-env/config/server.php', <<<CODE
<?php

setConstant('DB_HOST', 'localhost');
setConstant('DB_USER', 'root');
setConstant('DB_PASS', '$pass');
setConstant('DB_LOGGING', false);
setConstant('DB_NAME', PROJECT_KEY);
CODE
    );
  }

}