<?php

class SmanInstanceProjects extends SmanInstance {

  function _install() {
    $this->installPhpFull();
    $this->createUser();
  }

}