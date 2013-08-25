<?php

class Digitalocean extends ObjectMapper {

  public $api;

  function __construct() {
    $this->api = new DigitaloceanApiCached;
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

  function destroyServer($server) {
    $this->api->destroyServer($this->server($server)['id']);
    if (($sshKeyId = $this->sshKeyId($server))) $this->api->destroySshKey($sshKeyId);
  }

  function sshKeyId($server) {
    return Arr::getSubValue($this->api->sshKeys(), 'name', $server, 'id');
  }

}