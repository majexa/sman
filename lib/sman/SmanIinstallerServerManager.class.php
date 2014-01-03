<?php

class SmanIinstallerServerManager extends SmanIinstaller {

  function _install() {
    $this->scp->copy(SMAN_PATH.'/config/vars/doceanAccess.php');
    return;
    $this->installPhp();
    $this->installMailBotUser();
    $this->installGit();
    $this->cloneEnv(['ngn','ci','run', 'sman']);
  }

  protected function installMailBotUser() {
    $this->installMail();
    $this->createUser('bot', Misc::randString(8));
  }

}