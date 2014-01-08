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
    output("Creating server {$this->name}");
    $this->checkEmail();
    $this->docean->createServer($this->name);
    $this->storePassFromMail();
  }

  protected function checkEmail() {
    mail($this->botEmailCheck, 'check', 'one check');
    usleep(500);
    foreach (glob($this->botEmailFolder.'/*') as $file) {
      if (strstr(file_get_contents($file), 'one check')) {
        unlink($file);
        return;
      }
    }
    throw new Exception('Email problem');
  }

  protected function output($s) {
    output($s);
  }

  protected function storePassFromMail() {
    output("Waiting for mail");
    $server = $this->name;
    $files = $this->findMailFiles($server);
    if (count($files) == 0) throw new Exception("Mail for server '$server' not found");
    if (count($files) > 1) throw new Exception("Can not be more than 1 email for server '$server'");
    SmanConfig::updateSubVar('doceanServers', $server, $files[0]['password']);
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
          output('Skipped '.$fileName.'. Wrong body format. Expected "IP Address: ..."');
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