<?php

class SshKey {
  use DebugOutput;

  public $key;
  protected $ssh, $server, $user, $homeFolder, $tempFolder;

  function __construct(RemoteSshAbstract $ssh, $server, $user = 'root') {
    $this->ssh = $ssh;
    $this->server = $server;
    $this->ip = '';
    $this->user = $user;
    $this->homeFolder = $user == 'root' ? '/root' : "/home/$user";
    $this->tempFolder = DATA_PATH.'/temp';
    $this->config();
    $this->generate();
    $this->key = $this->store();
  }

  function __toString() {
    return $this->key;
  }

  protected function config() {
    $this->output("Configuring SSH on server '$this->server'");
    $config = "StrictHostKeyChecking=no\nLogLevel=quiet\nUserKnownHostsFile=/dev/null";
    $this->ssh->cmd($this->server, <<<CMD
if grep -q StrictHostKeyChecking=no ~/.ssh/config; then
  echo 'Already installed'
else
  echo '$config' > ~/.ssh/config
fi
CMD
    );
  }

  protected function generate() {
    $suCmd = $this->user != 'root' ? "su $this->user\n" : '';
    $this->ssh->cmd($this->server, <<<CMD
{$suCmd}if [ ! -f ~/.ssh/id_rsa ]; then
  ssh-keygen -q -f ~/.ssh/id_rsa -t rsa -N ''
fi
CMD
    );
  }

  protected function store() {
    print sys($this->ssh->sshpass($this->server)." scp {$this->ip}:$this->homeFolder/.ssh/id_rsa.pub {$this->tempFolder}/$this->server.pub");
    return file_get_contents("{$this->tempFolder}/$this->server.pub");
  }

}