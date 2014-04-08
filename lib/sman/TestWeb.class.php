<?php

class TestWeb extends NgnTestCase {

  function test() {
    $server = require dirname(SMAN_PATH).'/config/server.php';
    die2($server['baseDomain']);
  }

}
