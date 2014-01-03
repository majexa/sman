<?php

class DigitaloceanApi {
  use DebugOutput;

  public $outputResult = false;

  function api($uri, array $data = []) {
    $r = (new Curl)->get("https://api.digitalocean.com/$uri?client_id=aeOp6fwVrvbS0Sijbyj7A&api_key=WrgjH5DimPjU1NdG00VPWOR2vpShoRKoBZREHO2uD&".http_build_query($data));
    $r = json_decode($r, true);
    if ($r['status'] == 'ERROR') throw new Exception('DigitalOcean: '.$r['error_message']);
    if ($this->outputResult) print_r($r);
    return $r;
  }

  function createServer($name, array $opts = []) {
    $this->output("Creating server '$name'");
    return $this->api('droplets/new', array_merge([
      'size_id'   => 66,
      'image_id'  => 1505447, // ubuntu 12.04 x64
      //'image_id'  => 1505527, // ubuntu 12.04 x32
      'region_id' => 5, // amsterdam 2
      'name'      => $name
    ], $opts));
  }

  function createSshKey($name, $sshKey) {
    return $this->api('ssh_keys/new', [
      'name' => $name,
      'ssh_pub_key' => $sshKey
    ]);
  }

  function servers() {
    return $this->api('droplets')['droplets'];
  }

  function deleteServer($id) {
    return $this->api("droplets/$id/destroy");
  }

  function sshKeys() {
    return $this->api('ssh_keys')['ssh_keys'];
  }

  function deleteSshKey($id) {
    $this->api("ssh_keys/$id/destroy");
  }

}
