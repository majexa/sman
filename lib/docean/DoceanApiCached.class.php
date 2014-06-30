<?php

class DoceanApiCached extends ObjectCacher {

  protected function getObject() {
    return new DoceanApi;
  }

  function cachedMethods() {
    return [
      'servers',
      'sshKeys'
    ];
  }

  function cleanupMethods() {
    return [
      'createServer' => ['servers'],
      'createSshKey' => ['sshKeys']
    ];
  }

}