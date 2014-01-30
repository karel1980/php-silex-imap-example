<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();
$app['debug'] = true;

// config service
$env = getenv('APP_ENV') ?: 'prod';
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.yml", array(
  'root' => __DIR__.'/..'
)));

// Add Doctrine DBAL ServiceProvider
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'db.options' => $app['dbconfig']
));

// Mail service
$app->register(new MailApp\MailServiceProvider(), array());

// Template engine
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views'
));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html');
});

$app->get('/settings', function () use ($app) {
    $settings = $app['imap']->getSettings();
    return $app['twig']->render('settings.html', array('settings' => $settings));
});

$app->post('/settings', function () use ($app) {
    $host = $app['request']->get('host');
    $user = $app['request']->get('user');
    $pass = $app['request']->get('pass');

    $app['imap']->updateSettings($host, $user, $pass);
    $settings = $app['imap']->getSettings();

    # TODO: figure out if the session component supports flash messages (or roll our own)
    # That way we can redirect to / and still show the message
    #return $app['twig']->redirect('index.html', array('message' => 'Settings updated'));
    return $app->redirect('/');
});

$app->get('/inbox', function () use ($app) {
    $settings = $app['imap']->getSettings();
    $host = $settings->host;
    $user = $settings->user;
    $pass = $settings->pass;

    $overview = $app['imap']->fetchOverview();

    return $app['twig']->render('mailbox.html', array('overview' => $overview));
});

$app->get('/mail/{uid}/parts/{partId}', function($uid, $partId) use ($app) {
    $result = $app['imap']->fetchMailPart((int)$uid, $partId);

    $path = $result['filePath'];
    if (!file_exists( $path )) {
        return $app->abort(404, 'The image was not found.');
    }

    $stream = function () use ($path) {
        readfile($path);
    };

    return $app->stream($stream, 200, array('Content-Type' => $result['rawpart']->headers['Content-Type']));
});

$app->get('/mail/{uid}', function($uid) use ($app) {
    $result = $app['imap']->fetchByUid((int)$uid);
    return $app['twig']->render('mail.html', $result);
});

$app->get('/test', function () use ($app) {
    $opts = new ezcMailImapTransportOptions();
    $app['imap']->test();
    return $app['twig']->render('index.html');
});

$app->run();

?>
