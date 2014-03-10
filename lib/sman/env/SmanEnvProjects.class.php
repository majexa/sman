<?php

class SmanEnvProjects extends SmanEnvAbstract {

  protected function _install() {
    $this->cloneNgnEnv(['ngn', 'ci', 'run', 'pm', 'scripts']);
    $this->createConfig();
    output($this->ssh->exec('~/ngn-env/ci/update'));
    output($this->ssh->exec('php ~/ngn-env/pm/pm.php localServer updateHosts'));
  }



}