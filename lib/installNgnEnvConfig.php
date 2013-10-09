<?php

$home = "/home/user";
mkdir("$home/ngn-env/config/nginx");
mkdir("$home/ngn-env/config/nginxProjects");
mkdir("$home/ngn-env/config/remoteServers");
file_put_contents("$home/ngn-env/config/server.php", "<?php\n\nreturn ".var_export([
    'host'        => '{host}',
    'baseDomain'  => '{baseDomain}',
    'sType'        => 'prod',
    'os'          => 'linux',
    'ftpUser'     => 'user',
    'ftpPass'     => '[nevermind]',
    'ftpRoot'     => 'ngn-env',
    'ftpWebroot'  => 'ngn-env/{projectsPath}/{domain}',
    'dbUser'      => 'root',
    'dbPass'      => '{pass}',
    'dbHost'      => 'localhost',
    'sshUser'     => 'user',
    'sshPass'     => '{pass}',
    'ngnEnvPath'  => '/home/user/ngn-env',
    'ngnPath'     => "{ngnEnvPath}/ngn",
    'webserver'   => 'nginx',
    'webserverP'  => '/etc/init.d/nginx',
    //'pmDomain' => 'none',
    'prototypeDb' => 'file',
    'dnsMasterHost' => '{dnsMasterHost}'
  ], true).';');
file_put_contents("$home/ngn-env/config/database.php", <<<CODE
<?php

setConstant('DB_HOST', 'localhost');
setConstant('DB_USER', 'root');
setConstant('DB_PASS', '{pass}');
setConstant('DB_LOGGING', false);
setConstant('DB_NAME', PROJECT_KEY);
CODE
);