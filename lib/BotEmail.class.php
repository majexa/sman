<?php

class BotEmail {

  protected $serverName, $user, $check, $folder;

  function __construct() {
    $this->user = Config::getSubVar('botEmail', 'user', true);
    $this->email = $this->user.'@localhost';
    $this->folder = ($this->user == 'root' ? '/root' : '/home/'.$this->user).'/Maildir/new';
  }

  function check() {
    mail($this->email, 'check', 'one check');
    for ($i=1; $i<=3; $i++) {
      foreach (glob($this->folder.'/*') as $file) {
        if (strstr(file_get_contents($file), 'one check')) {
          unlink($file);
          print 1;
          return;
        }
      }
      sleep(1);
    }
    print 0;
  }


}