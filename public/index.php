<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = true;

// config service
$env = getenv('APP_ENV') ?: 'prod';
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.yml", array(
  'root' => __DIR__.'/..'
)));

// Add Doctrine DBAL ServiceProvider
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'db.options' => $app['dbo']
));

$app->register(new MailApp\MailSettingsProvider(), array());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views'
));

$app->get('/test', function () use ($app) {
  var_dump($app['settings']->get());
  return 'hello';
});

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html');
});

$app->get('/settings', function () use ($app) {
    $settings = $app['settings']->get();
    return $app['twig']->render('settings.html', array('settings' => $settings));
});

$app->post('/settings', function () use ($app) {
    $host = $app['request']->get('host');
    $user = $app['request']->get('user');
    $pass = $app['request']->get('pass');

    $app['settings']->update($host, $user, $pass);
    $settings = $app['settings']->get();

    # TODO: figure out if the session component supports flash messages (or roll our own)
    # That way we can redirect to / and still show the message
    #return $app['twig']->redirect('index.html', array('message' => 'Settings updated'));
    return $app->redirect('/');
});

function handle_failed_imap_login($app) {
    return $app['twig']->render('mailbox.html', array('error' => imap_last_error()));
};


$app->get('/inbox', function () use ($app) {
    $settings = $app['settings']->get();
    $host = $settings->host;
    $user = $settings->user;
    $pass = $settings->pass;

    $mbox = sprintf("{%s:993/imap/ssl}INBOX", $host);
    $stream = imap_open($mbox,$user,$pass);
    if (!$stream) return handle_failed_imap_login($app);

    $check = imap_check($stream);
    $overview = imap_fetch_overview($stream,"1:{$check->Nmsgs}",0);
    imap_close($stream);

    return $app['twig']->render('mailbox.html', array('emails' => $overview));
});

$app->run();

?>
