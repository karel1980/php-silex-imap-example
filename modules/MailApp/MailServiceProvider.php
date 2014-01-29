<?php

namespace MailApp;

use Silex\Application;
use Silex\ServiceProviderInterface;

class MailServiceProvider implements ServiceProviderInterface {

  public function register(Application $app) {
    $this->app = $app;
    $app['imap'] = $this;
  }

  public function getSettings() {
    $sql = "SELECT * FROM settings WHERE id = ?";
    $ary = $this->app['db']->fetchAssoc($sql, array(1));

    return new MailSettings($ary['host'], $ary['user'], $ary['pass']);
  }

  public function updateSettings($host, $user, $pass) {
    $sql = "UPDATE settings set host=?, user=?, pass=? where id=1";
    $this->app['db']->executeUpdate($sql, array($host, $user, $pass));

  }

  public function fetchOverview() {
    $settings = $this->getSettings();
    $host = $settings->host;
    $user = $settings->user;
    $pass = $settings->pass;

    $mbox = sprintf("{%s:993/imap/ssl}INBOX", $host);
    $stream = imap_open($mbox,$user,$pass);
    if (!$stream) { 
	return array('error' => imap_last_error());
    }
    $check = imap_check($stream);
    $overview = imap_fetch_overview($stream,"1:{$check->Nmsgs}",0);
    imap_close($stream);
    return array('mails' => $overview);
  }

  function boot(Application $app) {
  }
};

class MailSettings {

    public $host = 'imap.example.org';
    public $user = 'username';
    public $pass = 'password';

    public function __construct($host, $user, $pass) {
        $this->host=$host;
        $this->user=$user;
        $this->pass=$pass;
    }
}

?>
