<?php

class SmanEnvManager extends SmanEnvAbstract {

  protected function _install() {
    $this->cloneRepos(['ngn', 'ci', 'run', 'sman']);
    $this->createConfig();
  }

}