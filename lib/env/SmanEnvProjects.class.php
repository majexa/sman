<?php

class SmanEnvProjects extends SmanEnvAbstract {

  protected function _install() {
    $this->cloneRepos([
      'ngn',
      'issue',
      'ci',
      'run',
      'scripts',
      'pm',
      'dummyProject',
      'tst'
    ]);
    $this->exec('git clone https://github.com/mootools/mootools-core.git');
    $this->exec('git clone https://github.com/mootools/mootools-more.git');
    $this->exec('cd ~/ngn-env/ci && ./ci _updateBin');
    //output($this->shell->exec('php ~/ngn-env/pm/pm.php localServer updateHosts'));
  }


}