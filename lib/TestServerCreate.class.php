<?php

class TestServerCreate extends NgnTestCase {

  function test() {
    $name = SmanCore::create('projects');
    $this->assertTrue((bool)strstr(`curl -G http://scripts.$name.sitedraw.ru/c/allErrors`, '<?xml'));

  }

}