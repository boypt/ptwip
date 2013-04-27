<?php
define('PHPBASE', dirname(__FILE__));
require PHPBASE.'/lib/Slim/Slim.php';
require_once PHPBASE.'/lib/curl.php';
require_once PHPBASE.'/lib/curl_response.php';
require_once PHPBASE.'/ptwip.class.php';

\Slim\Slim::registerAutoloader();


//\Slim\Route::setDefaultConditions(array(
//    'req+' => '[a-z0-9.]{1,}'
//));

// Set the current mode
$app = new \Slim\Slim(array(
    'mode' => 'development'
));

// Only invoked if mode is "production"
$app->configureMode('production', function () use ($app) {
    $app->config(array(
        'log.enable' => true,
        'debug' => false
    ));
});

// Only invoked if mode is "development"
$app->configureMode('development', function () use ($app) {
    $app->config(array(
        'debug' => true,
        'log.level' => \Slim\Log::DEBUG,
        'log.enabled' => true
    ));
});

$app->get('/info', function() {
    phpinfo();
});

$app->map('/foo/bar', function() use ($app) {
    echo "I respond to multiple HTTP methods!";
})->via('GET', 'POST');


$app->map('/t/:req+', function($req) use ($app) {
    $pt = new PTwip($req, $app);
    echo $pt->t_mode_transfer();
    echo "\n";
})->via('GET', 'POST');

$app->run();
?>
