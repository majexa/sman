<?php

class SmanEnvManager extends SmanEnvAbstract {

  protected function _install() {
    //$this->cloneNgnEnv(['ngn', 'ci', 'run', 'sman']);
    $this->createConfig();
  }

}