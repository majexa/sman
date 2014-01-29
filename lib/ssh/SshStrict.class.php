<?php

class SshStrict extends Ssh {

  function exec($cmd) {
    $r = parent::exec($cmd);
    if (strstr($r, 'Uncaught exception')) {
      $r = str_replace('Uncaught exception: ', '', $r);
      $r = str_replace('---------------', '--- remote: ---', $r);
      throw new Exception(trim($r));
    }
    return $r;
  }

}