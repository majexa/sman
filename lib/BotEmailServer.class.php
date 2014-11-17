<?php

class BotEmailServer extends BotEmail {

  protected $docean;

  function __construct($serverName) {
    parent::__construct();
    $this->serverName = $serverName;
    $this->docean = Docean::get();
  }

  function storePass() {
    if (SmanConfig::getVarVar('doceanServers', $this->serverName, true)) {
      // Если пароль для сервер уже есть, выводим успех
      print 1;
      return;
    }
    $files = [];
    for ($i = 1; $i <= 120; $i++) {
      if (($files = $this->findMailFiles())) break;
      sleep(5);
    }
    if (count($files) == 0) {
      Err::log(new Exception("Mail for server '$this->serverName' not found"));
      print 0;
      return;
    }
    if (count($files) > 1) {
      Err::log(new Exception("Can not be more than 1 email for server '$this->serverName'"));
      print 0;
      return;
    }
    SmanConfig::updateSubVar('doceanServers', $this->serverName, $files[0]['password']);
    unlink($files[0]['file']);
    print 1;
  }

  protected function findMailFiles($serverIp = null) {
    $r = [];
    $serverIp = $serverIp ?: $this->docean->server($this->serverName)['ip_address'];
    Misc::checkEmpty($serverIp, "Server '$this->serverName' is not active yet");
    if (($files = glob($this->folder.'/*'))) {
      foreach ($files as $file) {
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
          //output('Skipped '.$fileName.'. Wrong body format. Expected "IP Address: '.$serverIp.'"');
          continue;
        }
        if (!preg_match($p2, $body)) {
          //output('Skipped '.$fileName.'. Wrong body format. Expected "Password: ..."');
          continue;
        }
        if (!preg_match($p3, $subj)) {
          //output('Skipped '.$fileName.'. Wrong subject format. Expected "(serverName) - DigitalOcean"');
          continue;
        }
        $mServerIp = preg_replace($p1, '$1', $body);
        $mServer = preg_replace($p3, '$1', $subj);
        if ($this->serverName != $mServer) continue;
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