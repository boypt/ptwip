<?php
require_once 'config.php';
require_once PHPBASE.'/lib/Slim/Slim.php';
require_once PHPBASE.'/lib/Slim/DateTimeFileWriter.php';
require_once PHPBASE.'/lib/ptwip.class.php';

\Slim\Slim::registerAutoloader();

// Set the current mode
$app = new \Slim\Slim(array(
    'mode' => 'development'
));

// Only invoked if mode is "production"
/*
$app->configureMode('production', function () use ($app) {
    $app->config(array(
        'log.enable' => true,
        'debug' => false
    ));
});
 */

// Only invoked if mode is "development"
$app->configureMode('development', function () use ($app) {
    $app->config(array(
        'debug' => true,
        'log.level' => \Slim\Log::DEBUG,
        'log.enabled' => true,
    ));

    $log = $app->getLog();
    $log->setWriter(new \Slim\Extras\Log\DateTimeFileWriter(array('path' => '/tmp')));

    if ( substr( @$_SERVER['SERVER_SOFTWARE'], 0, 3 ) === "PHP" ){
        $app->hook('slim.before.router', function () use ($app) {
            $env = $app->environment();
            if($env['PATH_INFO'] === '/' && $env['SCRIPT_NAME'] !== basename(__FILE__)) {
                $env['PATH_INFO'] = $env['SCRIPT_NAME'];
            }
        });
    }
});

$log = $app->getLog();

$app->get('/info', function() {
    phpinfo();
});

$app->get('/robots.txt', function () use ($app) {
    $resp = $app->response();
    $resp['Content-Type'] = 'text/plain';
    $resp->body("User-agent: *\nDisallow: /\n");
});

$app->map('/t/:resource+', function($resource) use ($app) {
    $req = $app->request();
    $params = $req->params();
    $pt = new PTwip(
        $req->getMethod(),
        $resource,
        $req->headers(),
        $params
    );

    $api_resp = $pt->t_mode_transfer();

    // copy remote response to custom
    if($api_resp !== false) {
        $resp = $app->response();
        $resp->status($pt->response_info['http_code']);
        foreach($pt->response_headers as $k => $v) $resp[$k] = $v;
        $resp->body($pt->response_body);
    }

})->via('GET', 'POST');

$app->run();
?>
