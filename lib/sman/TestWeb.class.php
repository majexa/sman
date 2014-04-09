<?php

class TestWeb extends NgnTestCase {

  function test() {
    $server = require dirname(SMAN_PATH).'/config/server.php';
    $this->assertTrue(false);
    Cli::shell('wget --spider default.'.$server['baseDomain']);
  }

}
