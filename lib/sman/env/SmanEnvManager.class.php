<?php

class SmanEnvManager extends SmanEnv {

  protected function _install() {
    //$this->cloneNgnEnv(['ngn', 'ci', 'run', 'sman']);
    $this->createConfig();
  }

}