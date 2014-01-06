<?php

class SmanEnvServerManager extends SmanEnv {

  function _install() {
    $this->cloneNgnEnv(['ngn', 'ci', 'run', 'sman']);
    output("Add 'doceanAccess' config");
  }

}