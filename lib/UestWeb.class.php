<?php

class UestWeb extends NgnTestCase {

  function test() {
    $server = require dirname(SMAN_PATH).'/config/server.php';
    $url = 'default.'.$server['baseDomain'];
    $this->assertTrue((bool)strstr(`wget -qO- $url`, 'default ngn host'));
  }

}
