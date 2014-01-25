<?php

class SmanEnvProjects extends SmanEnv {

  function _install() {
    //$this->cloneNgnEnv(['ngn', 'ci', 'run', 'sman', 'pm', 'scripts']);
    $this->createConfig();
    return;
    $this->ssh->exec('~/ngn-env/ci/update');
    $this->ssh->exec('php ~/ngn-env/pm/pm.php localServer updateHosts');
  }

}