<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;

$app = new Silex\Application();

$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views'
));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html');
});

$app->get('/inbox', function () use ($app) {
    $mbox = '{imap.gmail.com:993/imap/ssl}';
    $user = 'karel@vervaeke.info';
    $pass = 'secret';

    /* Work in progress; Get mbox, user and pass from a yaml file
    $configDirectories = array(__DIR__.'/../config');
    $locator = new FileLocator($configDirectories);
    $loader = new YamlFileLoader($sc, $locator);
    $config = $loader->load('dev.yml');
    var_dump($config);
     */

    $stream = imap_open($mbox,$user,$pass) or die(imap_last_error());

    $check = imap_check($stream);
    $overview = imap_fetch_overview($stream,"1:{$check->Nmsgs}",0);

    imap_close($stream);

    return $app['twig']->render('mailbox.html', array('emails' => $overview));
});

$app->run();

?>
