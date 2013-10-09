<?php

class ErrorsCollector {

  function __construct() {
    $this->api = new Digitalocean;
  }

  function run($type = 'get') {
    $servers['projects1'] = Arr::getValueByKey($this->api->api->servers(), 'name', 'projects1')['ip_address'];
    $servers['107'] = '-p 22107 user@176.9.151.229';
    $servers['109'] = '-p 22109 user@176.9.151.229';
    $grouped = [];
    $method = $type == 'clear' ? 'clear' : 'get';
    print "Monitoring errors on servers: ".implode(', ', array_keys($servers))."\n\n";
    foreach ($servers as $server => $sshConnect) {
      if (($r = Cli::rpc($sshConnect, '(new AllErrors)->'.$method.'()'))) {
      foreach ($r as $v) {
        $k = $v['body'].$v['trace'];
        if (isset($grouped[$k])) $grouped[$k]['count']++;
        else {
          $v['count'] = 1;
          $v['body'] .= " [$server]";
          $grouped[$k] = $v;
        }
      }
      }
    }
    if ($grouped) foreach ($grouped as $v) {
      if ($type == 'trace') {
        $v['trace'] = preg_replace('/^(.*)$/m', '                     $1', $v['trace']);
      }
      print date('d.m.Y H:i:s', $v['time']).": {$v['body']} ({$v['count']})\n".($type == 'trace' ? $v['trace'] : '');
    }
  }

  protected function localFile() {
    return DATA_PATH."/logs/r_errors.log";
  }

  /*
  function copyRemoteSiteLogsToLocal($remoteIp, $server) {
    $tmpFile = DATA_PATH."/tmp".rand(100, 10000);
    $cmd = <<<CMD
for D in /home/user/ngn-env/projects/*; do
  if [ -d "\${D}" ]; then
    if ssh $remoteIp stat \${D}/site/logs/r_errors.log \> /dev/null 2\>\&1
    then
      scp $remoteIp:\${D}/site/logs/r_errors.log $tmpFile
      echo "Errors on $server exists"
      ssh $remoteIp rm \${D}/site/logs/r_errors.log
    else
      echo "All ok on $server"
    fi
  fi
done
CMD;
    system($cmd);
    file_put_contents(file_get_contents($tmpFile), $this->localFile(), FILE_APPEND);
  }
  */

  function copyRemoteLogToLocal($remoteIp, $remotePath, $server) {
    $remotePath = $remotePath.'/logs';
    $tmpFile = DATA_PATH."/tmp".rand(100, 10000);

    //foreach ((new Server('ip'))->getErrors() as $v)

    $cmd = <<<CMD
if ssh $remoteIp stat $remotePath/r_errors.log \> /dev/null 2\>\&1
  then
    scp $remoteIp:$remotePath/r_errors.log $tmpFile
    echo "Errors on $server exists"
    ssh $remoteIp rm $remotePath/r_errors.log
  else
    echo "All ok on $server"
fi
CMD;
    system($cmd);
    file_put_contents(file_get_contents($tmpFile), $this->localFile(), FILE_APPEND);
  }

  function clear() {
    Dir::clear(DATA_PATH.'/logs');
  }

}