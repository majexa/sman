<?php

class SmanEnvServerManager extends SmanEnv {

  function _install() {
    //$this->cloneNgnEnv(['ngn', 'ci', 'run', 'sman']);
    $this->createConfig();
  }

}