<?php

namespace MailApp;

use Silex\Application;
use Silex\ServiceProviderInterface;

class MailSettingsProvider implements ServiceProviderInterface {

  public function register(Application $app) {
    $this->app = $app;
    $app['settings'] = $this;
  }

  public function get() {
    $sql = "SELECT * FROM settings WHERE id = ?";
    $ary = $this->app['db']->fetchAssoc($sql, array(1));

    return new MailSettings($ary['host'], $ary['user'], $ary['pass']);
  }

  public function update($host, $user, $pass) {
    $sql = "UPDATE settings set host=?, user=?, pass=? where id=1";
    $this->app['db']->executeUpdate($sql, array($host, $user, $pass));

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
