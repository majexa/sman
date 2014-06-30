<?php

class SmanEnvProjects extends SmanEnvAbstract {

  protected function _install() {
    $this->cloneRepos(['ngn', 'ci', 'run', 'pm', 'scripts']);
    //$this->createConfig();
    //$this->
    //output($this->shell->exec('~/ngn-env/ci/update'));
    //output($this->shell->exec('php ~/ngn-env/pm/pm.php localServer updateHosts'));
  }



}