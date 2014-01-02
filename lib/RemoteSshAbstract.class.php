<?php

abstract class RemoteSshAbstract {

  abstract protected function server($serverName);

  abstract protected function password($serverName);

  function sshpass($serverName) {
    return "sshpass -p '{$this->password($serverName)}'";
  }

  function getCmd($serverName, $cmd) {
    $s = $this->api->server($serverName);
    Arr::checkEmpty($s, ['ip_address']);
    if (strstr($cmd, "\n")) $cmd = "<< EOF\n$cmd\nEOF";
    return $this->sshpass($serverName)." ssh -T {$s['ip_address']} $cmd";
  }

  function cmd($serverName, $cmd) {
    $cmd = str_replace("\r", "\n", $cmd);
    if ($serverName == 'local') return sys($cmd);
    return sys($this->getCmd($serverName, $cmd));
  }

  function addSshKey($fromServer, $toServer, $user = 'root') {
    if ($this->sshKeyIsInAuthorized($fromServer, $toServer, $user)) return;
    $this->genSshKey($fromServer, $user);
    $sshKey = $this->getSshKey($fromServer, $user);
    $this->output("Adding SSH key from '$fromServer' to '$toServer'");
    $this->uploadSshKey($sshKey, $toServer);
  }

}