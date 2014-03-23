<?php

class SmanEnvManagerSelf extends SmanEnvAbstract {

  protected $userPass;

  function __construct($serverName, $userPass) {
    $this->userPass = $userPass;
    parent::__construct($serverName);
  }

  function _install() {
    $this->exec('su user');
    $this->cloneRepos(['ngn', 'ci', 'run', 'sman']);
    /*
    $this->exec([
      "mkdir -p ~/ngn-env/config/nginx",
      "mkdir ~/ngn-env/config/nginxProjects",
      "mkdir ~/ngn-env/config/remoteServers"
    ]);
    $this->sftp->putContents('/home/user/ngn-env/config/server.php', "<?php\n\nreturn ".Arr::formatValue([
        'host'          => '',
        'baseDomain'    => '',
        'sType'         => 'dev',
        'os'            => 'linux',
        'ngnEnvPath'    => '/home/user/ngn-env',
        'ngnPath'       => "{ngnEnvPath}/ngn",
        'webserver'     => 'nginx',
        'webserverP'    => '/etc/init.d/nginx',
        'nginxFastcgiPassUnixSocket' => true,
        'prototypeDb'   => 'file',
        'dnsMasterHost' => Config::getSubVar('servers', 'dnsMaster')
      ]).';');
    */
  }

}