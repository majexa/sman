<?php

if (!defined('SMAN_PATH')) throw new Exception('sman not initialized');

class SmanCore {

  /**
   * Создаёт сервер, инсталирует среду
   *
   * @param string        projects|serverManager|dnsMaster|dnsSlave
   * @param integer|null Уникальный идентификатор сервера
   */
  static function create($type, $id = null) {
    if (!$id) $id = self::lastId($type) + 1;
    $name = $type.$id;
    (new DoceanServer($name))->create();
    output('Waiting 15 sec after creation');
    sleep(15);
    self::createDns($name);
    self::createInstance($type, $name);
  }

  static function a() {
    $type = 'projects';
    $id = self::lastId($type) + 1;
    $name = $type.$id;
    (new DoceanServer($name))->create();
    $ssh = SmanInstance::get($type, $name)->ssh;
    print $ssh->exec([
      'apt-get update',
      'apt-get -y install php5-fpm',
    ]);
    print $ssh->exec([
      'sed -i "s|www-data|user|g" /etc/php5/fpm/pool.d/www.conf',
      '/etc/init.d/php5-fpm restart'
    ]);
    print $ssh->exec('ps aux | grep fpm');
  }

  static function b() {
    $type = 'projects';
    $name = 'projects1';
    $ssh = SmanInstance::get($type, $name)->ssh;
    print $ssh->exec('/etc/init.d/php5-fpm restart');
    print $ssh->exec('ps aux | grep fpm');
  }

  static function createInstance($type, $name) {
    self::checkConfig();
    SmanInstance::get($type, $name)->install();
    SmanEnv::get($type, $name)->install();
  }

  // -------------------------------------------

  static function cli($s) {
    if (!trim($s)) {
      print <<<TEXT
*-- Server Manager Program (c) masted 2014 --*
Supported commands:
- create {type}       // creates server by type
- delete {serverName} // delete server by name
- info {serverName}   // shows server info

TEXT;
      return;
    }
    $parts = explode(' ', $s);
    if ($parts[0] == 'create') {
      if (empty($parts[1])) {
        output3('Choose server type');
        return;
      }
      self::create($parts[1]);
    }
    elseif ($parts[0] == 'delete') {
      if (empty($parts[1])) {
        output3('Choose server name');
        return;
      }
      $name = $parts[1];
      // @todo ssh-keygen -f "/home/user/.ssh/known_hosts" -R 107.170.28.146
      SmanConfig::removeSubVar('userPasswords', Docean::get()->server($name)['ip_address']); // user password
      SmanConfig::removeSubVar('doceanServers', $name); // root password
      Docean::get()->deleteServer($name);
    }
    elseif ($parts[0] == 'list') {
      print '* '.implode("\n* ", Arr::get(Docean::get()->servers(), 'name'))."\n";
    }
    elseif ($parts[0] == 'login') {
      $host = Docean::get()->server($parts[1])['ip_address'];
      `ssh $host`;
      exit(0);
      // print '* '.implode("\n* ", Arr::get(Docean::get()->servers()w, 'name'))."\n";
    }
    elseif ($parts[0] == 'info') {
      if (empty($parts[1])) {
        output3('Choose server name');
        return;
      }
      $name = $parts[1];
      $server = Docean::get()->server($parts[1]);
      $host = $server['ip_address'];
      $rootPassword = Config::getSubVar('doceanServers', $name);
      $userPassword = Config::getSubVar('userPasswords', $host);
      print <<<TEXT
*-- Server: $name --*
Host: $host
Root password: $rootPassword
User password: $userPassword

TEXT;
    }
    else {
      self::$parts[0]();
    }
  }

  // -------------------------------------------

  static function checkConfig() {
    Config::getVar('doceanAccess');
    Config::getSubVar('botEmail', 'domain');
    Config::getSubVar('servers', 'dnsMaster');
    Config::getVar('git');
    Config::getVar('baseDomain');
  }

  static function lastId($type) {
    $servers = Docean::get()->servers();
    $max = 0;
    foreach ($servers as $v) if (Misc::hasPrefix($type, $v['name'])) {
      $id = (int)Misc::removePrefix($type, $v['name']);
      if ($id > $max) $max = $id;
    };
    return $max;
  }

  static function createDns($name) {
    $dnsHost = Config::getSubVar('servers', 'dnsMaster');
    $ssh = (new Ssh((new SshDefaultConnection($dnsHost, 'root'))));
    self::createZone($name, Docean::get()->server($name)['ip_address'], $ssh);
    self::createZone('*.'.$name, Docean::get()->server($name)['ip_address'], $ssh);
  }

  static protected function createZone($name, $host, Ssh $ssh) {
    $cmd = str_replace('"', '\\"', '(new DnsServer)->replaceZone("'.$name.'.'.Config::getVar('baseDomain').'", "'.$host.'")');
    $cmd = Cli::addRunPaths($cmd, 'NGN_ENV_PATH/dns-server/lib');
    print $ssh->exec($cmd);
  }

  static function createEnv($name) {
    $host = Docean::get()->server($name)['ip_address'];
    $sshConnection = new SshPasswordConnection($host, 'user', Config::getSubVar('userPasswords', $host, true));
    SmanEnv::get('projects', $sshConnection)->install();
  }

}