<?php

class Docean extends ObjectMapper {

  // for autocomplete
  static function get() {
    /* @var $a Docean|DoceanApi */
    static $a;
    if (isset($a)) return $a;
    $a = new self;
    return $a;
  }

  protected $api;

  function __construct() {
    $this->api = new DoceanApiCached;
  }

  protected function getObject() {
    return $this->api;
  }

  function server($name, $strict = true) {
    if (!($r = Arr::getValueByKey($this->api->servers(), 'name', $name))) {
      if ($strict) throw new NotFoundException("server $name");
      else return false;
    }
    return $r;
  }

  function createServer($name) {
    $this->api->createServer($name);
    $this->output("Waiting for server is active");
    while (true) {
      if ($this->api->server($name)['status'] == 'active') break;
      sleep(5);
    }
  }

  function deleteServer($server) {
    $this->api->deleteServer($this->server($server)['id']);
    if (($sshKeyId = $this->sshKeyId($server))) $this->api->deleteSshKey($sshKeyId);
  }

  function sshKeyId($server) {
    return Arr::getSubValue($this->api->sshKeys(), 'name', $server, 'id');
  }

}