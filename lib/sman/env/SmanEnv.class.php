<?php

/**
 * Environment installer
 */
abstract class SmanEnv extends SmanInstaller {

  /**
   * @param $type
   * @param $name
   * @return SmanInstance
   */
  static function get($type, $name) {
    $class = 'SmanEnv'.ucfirst($type);
    return new $class($name);
  }

  protected $user = 'user';
  protected $name;

  function __construct($name) {
    parent::__construct(new DoceanUserConnection($name));
    $this->name = $name;
  }

  protected function cloneNgnEnv($repos = []) {
    $cmd = [
      'mkdir ~/ngn-env',
      'mkdir ~/ngn-env/logs',
      'cd ~/ngn-env',
    ];
    foreach ($repos as $repo) $cmd[] = "git clone $this->gitUrl/$repo.git";
    print $this->ssh->exec($cmd);
  }

  function install() {
    $this->_install();
  }

  function createConfig() {
    $this->ssh->exec([
      "mkdir -p ~/ngn-env/config/nginx",
      "mkdir ~/ngn-env/config/nginxProjects",
      "mkdir ~/ngn-env/config/remoteServers"
    ]);
    $pass = SmanConfig::getSubVar('userPasswords', $this->sshConnection->host);
    $this->sftp->putContents('/home/user/ngn-env/config/server.php', "<?php\n\nreturn ".Arr::formatValue([
        'host'          => $this->sshConnection->host,
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
        'prototypeDb'   => 'file',
        'dnsMasterHost' => Config::getSubVar('servers', 'dnsMaster')
      ]).';');
    output2('ok');
    $this->sftp->putContents('/home/user/ngn-env/config/database.php', <<<CODE
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