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
      'cd ngn-env',
    ];
    foreach ($repos as $repo) $cmd[] = "git clone $this->gitUrl/$repo.git";
    $this->ssh->exec($cmd);
  }

  function configNgnEnv() {
    $file = __DIR__.'/installNgnEnvConfig.php';
    copy($file, "/root/temp/installNgnEnvConfig.php");
    $file = "/root/temp/installNgnEnvConfig.php";
    $pass = file_get_contents("/root/temp/userPass_{$this->name}");
    file_put_contents($file, str_replace('{pass}', $pass, file_get_contents($file)));
    file_put_contents($file, str_replace('{host}', $this->ei->api->server($this->name)['ip_address'], file_get_contents($file)));
    file_put_contents($file, str_replace('{baseDomain}', $this->baseDomain(), file_get_contents($file)));
    file_put_contents($file, str_replace('{dnsMasterHost}', $this->ei->api->server('dnsMaster')['ip_address'], file_get_contents($file)));
    sys("scp $file {$this->ei->api->server($this->name)['ip_address']}:/home/user/installNgnEnvConfig.php");
    Cli::ssh($this->name, 'php /home/user/installNgnEnvConfig.php');
    Cli::ssh($this->name, 'php /home/user/ngn-env/pm/pm.php localServer updateHosts');
    Cli::ssh($this->name, 'chown -R user:user /home/user/ngn-env/config');
  }

}