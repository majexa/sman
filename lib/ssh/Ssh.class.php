<?php

class Ssh extends SshBase {

  function exec($cmd) {
    if (is_array($cmd)) {
      $cmd = implode('; echo "******"; ', $cmd);
    }
    output($cmd);
    $outputStream = ssh2_exec($this->connection, $cmd, false, null);
    $errorStream = ssh2_fetch_stream($outputStream, SSH2_STREAM_STDERR);
    stream_set_blocking($errorStream, true);
    stream_set_blocking($outputStream, true);
    $error = stream_get_contents($errorStream);
    if ($error !== '') throw new RuntimeException($error);
    return stream_get_contents($outputStream);
  }

  function shell($cmds) {
    //$cmds = array_merge(['echo "[[start]]"']/*, (array)$cmds, ['echo "[[end]]"']*/);
    $stream = ssh2_shell($this->connection, 'vt102');
    foreach ($cmds as $cmd) fwrite($stream, $cmd.PHP_EOL);
    $r = [];
    $starts = false;
    while (1) {
      usleep(100);
      $line = trim(fgets($stream));
      if ($line) {
        if (preg_match('/(?<!")\[\[end\]\]/', $line)) break;
        if (preg_match('/(?<!")\[\[start\]\]/', $line)) {
          $starts = true;
          continue;
        }
        if ($starts) $r[] = trim($line);
      }
    }
    return $r;
  }

  function shell_($cmds) {
    $cmds = array_merge(['echo "[[start]]"']/*, (array)$cmds, ['echo "[[end]]"']*/);
    $stream = ssh2_shell($this->connection, 'vt102');
    //die2($cmds);
    foreach ($cmds as $cmd) fwrite($stream, $cmd.PHP_EOL);
    $r = [];
    $starts = false;
    while (1) {
      usleep(100);
      //$line = stream_get_contents($stream);
      $line = trim(fgets($stream, 1000));
      if ($line) {
        if (preg_match('/(?<!")\[\[end\]\]/', $line)) break;
        if (preg_match('/(?<!")\[\[start\]\]/', $line)) {
          $starts = true;
          continue;
        }
        if ($starts) $r[] = trim($line);
      }
    }
    return $r;
  }

}