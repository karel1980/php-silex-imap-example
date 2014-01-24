<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;

$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views'
));

$env = getenv('APP_ENV') ?: 'prod';
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.yml"));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html');
});

$app->get('/inbox', function () use ($app) {
    $mbox = $app['mbox'];
    $user = $app['user'];
    $pass = $app['pass'];

    var_dump($mbox);
    $stream = imap_open($mbox,$user,$pass) or die(imap_last_error());

    $check = imap_check($stream);
    $overview = imap_fetch_overview($stream,"1:{$check->Nmsgs}",0);

    imap_close($stream);

    return $app['twig']->render('mailbox.html', array('emails' => $overview));
});

$app->run();

?>
