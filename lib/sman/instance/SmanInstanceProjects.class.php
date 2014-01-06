<?php

class SmanInstanceProjects extends SmanInstance {

  function _install() {
    $this->installPhpFull();
    $this->installGit();
    $this->createUser('user', '123');
  }

}