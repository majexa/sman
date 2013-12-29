<?php

// php ~/run/run.php "(new WebInstaller)->method()" NGN_ENV_PATH/manager/init.php

class WebInstaller {

  public $name;

  function __construct($name = 'local') {
    $this->name = $name;
    $this->ei = new EnvInstaller;
  }

  function randString($len = 20) {
    $allchars = 'abcdefghijklnmopqrstuvwxyz';
    $string = '';
    mt_srand((double)microtime() * 1000000);
    for ($i = 0; $i < $len; $i++) {
      $string .= $allchars[mt_rand(0, strlen($allchars) - 1)];
    }
    return $string;
  }

  function usuck() {
    
  }

  function installUser($pass) {
    $pass = '';
    $cmd = <<<CMD
useradd -d /home/user -s /bin/bash -c "Developer" -p `openssl passwd -1 $pass` user
mkdir /home/user && chown user /home/user
apt-get -y install sudo
echo '%user ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers
CMD;
    $this->ei->cmdFile($this->name, $cmd);
  }

  function installSoft() {
    $pass = $this->randString(10);
    $this->ei->cmd($this->name, 'apt-get -y install mc');
    $this->ei->installPhp($this->name);
    file_put_contents("/root/temp/userPass_{$this->name}", $pass);
    $this->installUser($pass);
    $cmd = <<<CMD
mkdir -p ~/temp
apt-get update
apt-get -y install git-core
debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password password $pass'
debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password_again password $pass'
apt-get -y install mysql-server
apt-get -y install memcached
apt-get -y install imagemagick
/etc/init.d/php5-fpm restart
apt-get -y install nginx
cd /etc/nginx
sed -i "s/^\s*#.*$//g" nginx.conf
sed -i "/^\s*$/d" nginx.conf
sed -i "s|^\s*include /etc/nginx/sites-enabled/\*;|\tserver_names_hash_bucket_size 64;\\n\tinclude /home/user/ngn-env/config/nginxProjects/\*;\\n\tinclude /home/user/ngn-env/config/nginx/*;|g" nginx.conf
sed -i "s|www-data|user|g" nginx.conf
sed -i "s|www-data|user|g" /etc/php5/fpm/pool.d/www.conf
cd /tmp
echo -e "deb http://www.rabbitmq.com/debian/ testing main" >> /etc/apt/sources.list
wget http://www.rabbitmq.com/rabbitmq-signing-key-public.asc
apt-key add rabbitmq-signing-key-public.asc
apt-get update
apt-get install rabbitmq-server
pecl install amqp
echo -e "extension=amqp.so" > /etc/php5/conf.d/amqp.ini

CMD;
    // @todo install piwik here
    $this->ei->cmdFile($this->name, "\n".$cmd);
  }

  function installEnv() {
    $this->ei->addSshKey($this->name, 'git', 'user');
    $this->ei->cmdFile($this->name, <<<CMD
cd /home/user
su -c "git clone --recursive ssh://root@{$this->ei->api->server(GIT)['ip_address']}/~/repo/ngn-env.git" user
CMD
);
  }

  function baseDomain() {
    return $this->name.'.majexa.ru';
  }

  function installConfig() {
    $file = __DIR__.'/installNgnEnvConfig.php';
    copy($file, "/root/temp/installNgnEnvConfig.php");
    $file = "/root/temp/installNgnEnvConfig.php";
    $pass = file_get_contents("/root/temp/userPass_{$this->name}");
    file_put_contents($file, str_replace('{pass}', $pass, file_get_contents($file)));
    file_put_contents($file, str_replace('{host}', $this->ei->api->server($this->name)['ip_address'], file_get_contents($file)));
    file_put_contents($file, str_replace('{baseDomain}', $this->baseDomain(), file_get_contents($file)));
    file_put_contents($file, str_replace('{dnsMasterHost}', $this->ei->api->server('dnsMaster')['ip_address'], file_get_contents($file)));
    sys("scp $file {$this->ei->api->server($this->name)['ip_address']}:/home/user/installNgnEnvConfig.php");
    Cli::ssh($this->name, 'php /home/user/installNgnEnvConfig.php');
    Cli::ssh($this->name, 'php /home/user/ngn-env/pm/pm.php localServer updateHosts');
    Cli::ssh($this->name, 'chown -R user:user /home/user/ngn-env/config');
  }

  function installDns() {
    $this->ei->addSshKey($this->name, 'dnsMaster', 'user');
    $this->createBaseZone();
  }

  function createBaseZone() {
    $this->ei->cmd('dnsMaster', Cli::formatRunCmd("(new DnsServer)->createZone('{$this->baseDomain()}', '{$this->ei->api->server($this->name)['ip_address']}')", 'NGN_ENV_PATH/dns-server/lib'));
  }

  function removeDns() {
    $this->ei->removeSshKey($this->name, 'dnsMaster', 'user');
    $this->ei->cmd('dnsMaster', Cli::formatRunCmd("(new DnsServer)->deleteZone('{$this->baseDomain()}')", 'NGN_ENV_PATH/dns-server/lib'));
  }

  function installPiwik() {
    $this->ei->cmd($this->name, <<<CMD
su user
cd ~
git clone ssh://root@{$this->ei->api->server(GIT)['ip_address']}/~/repo/piwik.git
CMD
    );
  }

}
