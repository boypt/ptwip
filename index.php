<?php
require_once 'config.php';
require_once PHPBASE.'/lib/Slim/Slim.php';
require_once PHPBASE.'/ptwip.class.php';

\Slim\Slim::registerAutoloader();


\Slim\Route::setDefaultConditions(array(
    //'req' => '[\w\./]+'
));

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
    $app->hook('slim.before.router', function () use ($app) {
        if ( substr( @$_SERVER['SERVER_SOFTWARE'], 0, 3 ) === "PHP" ){
            $env = $app->environment();
            if($env['PATH_INFO'] === '/' && $env['SCRIPT_NAME'] !== basename(__FILE__)) {
                $env['PATH_INFO'] = $env['SCRIPT_NAME'];
            }
        }
    });
});

$app->get('/info', function() {
    phpinfo();
});

$app->map('/t/:req+', function($req) use ($app) {
    $pt = new PTwip($req, $app);
    $api_resp = $pt->t_mode_transfer();

    // copy remote response to custom
    if($api_resp !== null) {
        $resp = $app->response();
        $resp->status((int)$api_resp->status['code']);
        foreach($api_resp->headers as $k => $v) $resp[$k] = $v;
        $resp->setBody($api_resp->body);
    }

})->via('GET', 'POST', 'DELETE');

$app->run();
?>
