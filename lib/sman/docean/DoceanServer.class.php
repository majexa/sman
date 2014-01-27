<?php

class DoceanServer {
  use DebugOutput;

  protected $name, $docean, $botEmail, $botEmailFolder;

  function __construct($name) {
    $this->name = $name;
    $this->docean = Docean::get();
    $this->botEmailUser = 'user';
    $this->botEmailCheck = $this->botEmailUser.'@localhost';
    $this->botEmailFolder = ($this->botEmailUser == 'root' ? '/root' : '/home/'.$this->botEmailUser).'/Maildir/new';
  }

  function create() {
    $this->checkEmail();
    $this->docean->createServer($this->name);
    $this->storePassFromMail();
    output('Waiting 30 sec after creation');
    sleep(30);
  }

  protected function checkEmail() {
    mail($this->botEmailCheck, 'check', 'one check');
    for ($i=1; $i<=3; $i++) {
      output("Check localhost email ($i)");
      foreach (glob($this->botEmailFolder.'/*') as $file) {
        if (strstr(file_get_contents($file), 'one check')) {
          unlink($file);
          return;
        }
      }
      sleep(1);
    }
    throw new Exception('Email problem');
  }

  protected function output($s) {
    output($s);
  }

  protected function storePassFromMail() {
    output("Waiting for mail (10 min limit)");
    for ($i=1; $i<=120; $i++) {
      print '.';
      if (($files = $this->findMailFiles())) break;
      sleep(5);
    }
    if (count($files) == 0) throw new Exception("Mail for server '$this->name' not found");
    if (count($files) > 1) throw new Exception("Can not be more than 1 email for server '$this->name'");
    SmanConfig::updateSubVar('doceanServers', $this->name, $files[0]['password']);
    output("Password email found & stored");
    unlink($files[0]['file']);
  }

  function findMailFiles($serverIp = null) {
    $r = [];
    $serverIp = $serverIp ?: $this->docean->server($this->name)['ip_address'];
    Misc::checkEmpty($serverIp, "Server '$this->name' is not active yet");
    if (($files = glob($this->botEmailFolder.'/*'))) {
      foreach ($files as $file) {
        $fileName = basename($file);
        try {
          $d = (new MailMimeParser)->decode(['File' => $file]);
          $subj = $d[0]['Headers']['subject:'];
          $body = $d[0]['Body'];
        } catch (Exception $e) {
          throw new Exception("Error while parsing file '$file'");
        }
        $p1 = '/.*IP Address: ([0-9.]+).*/s';
        $p2 = '/.*Password: (\w+).*/s';
        $p3 = '/.*\((\w+)\) - DigitalOcean.*/';
        if (!preg_match($p1, $body)) {
          output('Skipped '.$fileName.'. Wrong body format. Expected "IP Address: '.$serverIp.'"');
          continue;
        }
        if (!preg_match($p2, $body)) {
          output('Skipped '.$fileName.'. Wrong body format. Expected "Password: ..."');
          continue;
        }
        if (!preg_match($p3, $subj)) {
          output('Skipped '.$fileName.'. Wrong subject format. Expected "(serverName) - DigitalOcean"');
          continue;
        }
        $mServerIp = preg_replace($p1, '$1', $body);
        $mServer = preg_replace($p3, '$1', $subj);
        if ($this->name != $mServer) continue;
        if ($serverIp != $mServerIp) continue;
        $r[] = [
          'file'     => $file,
          'ip'       => $mServerIp,
          'password' => preg_replace($p2, '$1', $body)
        ];
      }
    }
    return $r;
  }

}