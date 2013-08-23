<?php

class DigitaloceanMy {

  public $api;

  function __construct() {
    $this->api = new DigitaloceanApiCached;
  }

  function create() {
    $sshKeys = [
      //'dnsSlave' => ['dnsMaster', 'projects1'],
      'dnsMaster' => ['git', 'dnsSlave'],
      'projects1' => ['git']
    ];
    // создать dnsSlave. с публичным ключем
    // - 'ssh_key_ids' => $this->sshKeys(['installer','sample']),
    foreach (['dnsMaster', 'dnsSlave', 'projects1'] as $v) $this->api->createServer($v, !isset($sshKeys[$v]) ? [] : [
      'ssh_key_ids' => $sshKeys[$v]
    ]);
  }

  function servers() {
    $r = [];
    foreach ($this->api->servers() as $v) {
      if (in_array($v['name'], ['dnsMaster', 'dnsSlave', 'projects1'])) {
        $r[] = $v;
      }
    }
    return $r;
  }

  function destroy() {
    foreach ($this->servers() as $v) $this->api->_destroyServer($v['id']);
  }

  function genKey() {
    //$cachedMethods = $this->api->cachedMethods();
    //die2($cachedMethods);
    //`ssh -o StrictHostKeyChecking=no "ssh-keygen -f ~/.ssh/id_rsa -t rsa -N ''"`;
  }

}
