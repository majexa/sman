<?php

class DigitaloceanApi {

  public $outputResult = false;

  function api($uri, array $data = []) {
    $r = json_decode(file_get_contents("https://api.digitalocean.com/$uri?client_id=aeOp6fwVrvbS0Sijbyj7A&api_key=WrgjH5DimPjU1NdG00VPWOR2vpShoRKoBZREHO2uD&".http_build_query($data)), true);
    if ($this->outputResult) print_r($r);
    return $r;
  }

  function createServer($name, array $opts = []) {
    return $this->api('droplets/new', array_merge([
      'size_id'   => 66,
      'image_id'  => 284211,
      'region_id' => 2,
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
