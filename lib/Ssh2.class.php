<?php

class SSH2 {

  public $_host;
  public $_port;

  public $_username;
  public $_password;

  public $_c;

  public $_current_stream;

  public $_sftp;

  public $_log_reads = false;
  public $_log_writes = false;

  public $_log_buf = '';

  function __construct($host, $port = 22, $callbacks = []) {
    if (!function_exists('ssh2_connect')) throw new Exception('ERROR: PECL ssh2 must be installed!');
    $this->_host = $host;
    $this->_port = $port;
    $this->_c = ssh2_connect($this->_host, $this->_port, [], $callbacks);
  }

  function setLogReads($setting = true) {
    $this->_log_reads = $setting;
  }

  function setLogWrites($setting = true) {
    $this->_log_writes = $setting;
  }

  function loginWithPassword($username, $password) {
    return ssh2_auth_password($this->_c, $username, $password);
  }

  // WARNING: Blocking only really works as expected if reading data afterwards. 
  function execCommand($command, $set_blocking = false, $pty = null, $env = []) {
    if (!$pty) $pty = null;
    $stream = ssh2_exec($this->_c, $command, $pty, $env);
    $this->_current_stream = $stream;
    if ($set_blocking) stream_set_blocking($stream, true);
    return $stream;
  }

  function _generateCommand($command, $get_stdout = true, $get_stderr = false, $append_output = '') {
    $command = " ( $command ) ";
    if ($get_stdout && $get_stderr) {
      $command .= ' 2>&1 ';
    }
    elseif ($get_stdout && !$get_stderr) {
      $command .= ' 2>/dev/null ';
    }
    elseif (!$get_stdout && $get_stderr) {
      $command = " ( $command 1>/dev/null ) 2>&1 ";
    }
    else {
      $command .= ' >/dev/null 2>&1 ';
    }
    $command = ' sh -c '.escapeshellarg($command);
    if ($append_output) $command .= ' ; echo '.escapeshellarg($append_output).' ; ';
    return $command;
  }

  // Use this if you want to wait until the command is executed.
  function execCommandBlockNoOutput($command, $not_used = true, $pty = null, $env = []) {
    $command = SSH2::_generateCommand($command, false, false, '@');
    $stream = $this->execCommand($command, true, $pty, $env);
    $this->waitPrompt('@');
    return $stream;
  }

  // Use this if you want to wait until the command is executed and want the output.
  // This is an old implementation of execCommandBlockING(); it has a b64encode dependency.
  function execCommandBlock($command, $not_used = true, $pty = null, $env = [], $get_stderr = false) {
    $command = SSH2::_generateCommand($command, true, $get_stderr);
    $command .= ' | b64encode - | sed 1d | sed \'$d\' ';
    $command .= ' ; echo \'@\'; ';
    $stream = $this->execCommand($command, true, $pty, $env);
    $this->waitPrompt('@', $_buf);
    return base64_decode($_buf);
  }

  // Use this if you want to wait until the command is executed and want the output.
  function execCommandBlocking($command, $not_used = true, $pty = null, $env = [], $get_stderr = false) {
    $command = SSH2::_generateCommand($command, true, $get_stderr);
    $stream = $this->execCommand($command, true, $pty, $env);
    $buf = '';
    while (!$this->feof()) $buf .= $this->getStreamOutput();
    if ($this->_log_reads) $this->_log_buf .= $buf;
    return $buf;
  }

  function getShell($set_blocking = false, $term_type = 'vt102', $env = [], $width = null, $height = null) {
    $stream = ssh2_shell($this->_c, $term_type, $env, $width, $height, ($width && $height) ? SSH2_TERM_UNIT_CHARS : null);
    $this->_current_stream = $stream;
    if ($set_blocking) stream_set_blocking($stream, true);
    return $stream;
  }

  function waitPrompt($prompt_regex = '> $', &$buf = '', $timeout_secs = 0, $stream = null) {
    if (!$stream) $stream = $this->_current_stream;
    if ($timeout_secs) {

      $_ver = preg_replace('#-.*?$#', '', phpversion('ssh2'));
      if (version_compare($_ver, '0.11.0', '<')) {
        echo "ERROR: Using old version of PECL ssh2 ($_ver); timeouts broken!";
        die();
      }

      $end = time() + $timeout_secs;

      $saved_meta_info = $this->getMeta($stream);
      stream_set_blocking($stream, false);

      while (!$_r = preg_match("#$prompt_regex#", $buf .= fread($stream, 4096))) {
        if (time() > $end) break;
        fflush($stream);
        usleep(1);
      }

      stream_set_blocking($stream, $saved_meta_info['blocked']);

    }
    else {
      while (!$_r = preg_match("#$prompt_regex#", $buf .= fread($stream, 4096))) fflush($stream);
    }

    if ($this->_log_reads) $this->_log_buf .= $buf;
    return $_r;
  }

  function writePrompt($command, $add_newline = true, $stream = null) {
    if (!$stream) $stream = $this->_current_stream;
    fflush($stream);
    $_command = ($command.($add_newline ? "\n" : ''));
    $num_bytes = fwrite($stream, $_command);
    fflush($stream);
    if ($this->_log_writes) $this->_log_buf .= substr($_command, 0, $num_bytes);
    return $num_bytes;
  }

  function getStreamOutput($length = 4096, $stream = null) {
    if (!$stream) $stream = $this->_current_stream;
    $buf = fread($stream, $length);
    if ($this->_log_reads) $this->_log_buf .= $buf;
    return $buf;
  }

  // WARNING: This may not necessarily get all data.
  function getAllStreamOutput($stream = null) {
    if (!$stream) $stream = $this->_current_stream;
    $buf = stream_get_contents($stream);
    if ($this->_log_reads) $this->_log_buf .= $buf;
    return $buf;
  }

  function closeStream($stream = null) {
    if (!$stream) $stream = $this->_current_stream;
    return fclose($stream);
  }

  function fetchSTDERR($set_blocking = false, $stream = null) {
    if (!$stream) $stream = $this->_current_stream;
    $err_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    if ($set_blocking) stream_set_blocking($err_stream, true);
    return $err_stream;
  }

  function SCPSend($local_file, $remote_file, $create_mode = null) {
    return ssh2_scp_send($this->_c, $local_file, $remote_file, $create_mode);
  }

  function sendContents($file_contents, $remote_file, $create_mode = null, $set_blocking = false) {
    $fp = fopen("ssh2.sftp://".$this->_sftp."$remote_file", $create_mode);
    if ($set_blocking) stream_set_blocking($fp, true);
    return fwrite($fp, $file_contents, strlen($file_contents));
  }

  function sendStream($input_stream, $remote_file, $create_mode = null, $set_blocking = false) {
    $fp = fopen("ssh2.sftp://".$this->_sftp."$remote_file", $create_mode);
    if ($set_blocking) stream_set_blocking($fp, true);
    $bytes = stream_copy_to_stream($input_stream, $fp);
    fclose($fp);
    return $bytes;
  }

  function SCPReceive($remote_file, $local_file) {
    return ssh2_scp_recv($this->_c, $remote_file, $local_file);
  }

  function openSFTP() {
    $this->_sftp = ssh2_sftp($this->_c);
  }

  function unlink($filename) {
    return ssh2_sftp_unlink($this->_sftp, $filename);
  }

  function feof($stream = null) {
    if (!$stream) $stream = $this->_current_stream;
    return feof($stream);
  }

  function getMeta($stream = null) {
    if (!$stream) $stream = $this->_current_stream;
    return stream_get_meta_data($stream);
  }

  // Use file wrappers for other functionality.

}