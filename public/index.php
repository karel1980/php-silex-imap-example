<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;

$app = new Silex\Application();

$app['debug'] = true;

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views'
));

$env = getenv('APP_ENV') ?: 'prod';
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.yml"));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html');
});

$app->get('/login', function () use ($app) {
    return $app->redirect('/');
});

function handle_failed_imap_login($app) {
    $app['session']->remove('user'); # this avoids redirect loops in case there are bad credentials in the session
    return $app['twig']->render('index.html', array('message' => imap_last_error()));
};

$app->post('/login', function() use ($app) {
    $user = $app['request']->get('user', false);
    $pass = $app['request']->get('pass');
    $imap_host = $app['request']->get('imap_host');

    $mbox=sprintf("{%s:993/imap/ssl}INBOX", $imap_host);
    $stream = imap_open($mbox,$user,$pass);
    if (!$stream) return handle_failed_imap_login($app);
    imap_close($stream);

    $app['session']->set('user', $user);
    $app['session']->set('pass', $pass);
    $app['session']->set('mbox', $mbox);

    return $app->redirect('/inbox');
});

$app->get('/logout', function() use ($app) {
    # TODO: research security implications of invalidate() vs clear() 
    $app['session']->invalidate();
    return $app->redirect('/');
});

$app->get('/inbox', function () use ($app) {
    $user = $app['session']->get('user');
    $pass = $app['session']->get('pass');
    $mbox = $app['session']->get('mbox');

    $stream = imap_open($mbox,$user,$pass);
    # TODO: handle_imap_login will remove credentials from the session
    # Instead we should show an error page so users can hit refresh and be back on track
    if (!$stream) return handle_failed_imap_login($app);

    $check = imap_check($stream);
    $overview = imap_fetch_overview($stream,"1:{$check->Nmsgs}",0);
    imap_close($stream);

    return $app['twig']->render('mailbox.html', array('emails' => $overview));
});

$app->run();

?>
