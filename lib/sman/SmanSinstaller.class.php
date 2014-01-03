<?php

/**
 * Hole server installer
 */
class SmanSinstaller {
  use DebugOutput;

  protected $name, $type, $docean, $botEmail, $mailUser;

  function __construct($name, $type) {
    $this->name = $name;
    $this->type = $type;
    $this->docean = Docean::get();
    $this->botEmail = 'bot@masted.ru';
    $this->mailUser = explode('@', $this->botEmail)[0];
  }

  function install() {
    $this->checkEmail();
    $this->docean->createServer($this->name);
    $this->storePassFromMail();
    SmanIinstaller::get($this->type, $this->name)->install();
  }

  protected function checkEmail() {
    mail($this->botEmail, 'check', 'one check');
    foreach (glob('/home/'.$this->mailUser.'/Maildir/new/*') as $file) {
      if (strstr(file_get_contents($file), 'one check')) {
        unlink($file);
        return;
      }
    }
    throw new Exception('Email problem');
  }

  protected function storePassFromMail() {
    $this->output("Waiting for mail");
    $server = $this->name;
    $files = $this->findMailFiles($server);
    if (count($files) == 0) throw new Exception("Mail for server '$server' not found");
    if (count($files) > 1) throw new Exception("Can not be more than 1 email for server '$server'");
    Config::updateSubVar(DATA_PATH.'/passwords.php', $server, $files[0]['password']);
    unlink($files[0]['file']);
  }

  protected function findMailFiles() {
    $r = [];
    $serverIp = $this->docean->server($this->name)['ip_address'];
    Misc::checkEmpty($serverIp, "Server '$this->name' is not active yet");
    if (($files = glob('/home/'.self::$mailUser.'/Maildir/new/*'))) {
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
          $this->output('Skipped '.$fileName.'. Wrong body format. Expected "IP Address: ..."');
          continue;
        }
        if (!preg_match($p2, $body)) {
          $this->output('Skipped '.$fileName.'. Wrong body format. Expected "Password: ..."');
          continue;
        }
        if (!preg_match($p3, $subj)) {
          $this->output('Skipped '.$fileName.'. Wrong subject format. Expected "(serverName) - DigitalOcean"');
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