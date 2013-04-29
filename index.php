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

$app->map('/t/1/:resource+', function($resource) use ($app) {
    $app->halt(410, "Gone. Twitter has had their API v1 retired.");
})->via('GET', 'POST', 'DELETE');


$update_with_media_proc = function () use ($app) {
    $req = $app->request();
    $resp = $app->response();
    $params = $req->params();

    if(strpos($req->getContentType(), 'multipart/form-data') === false)
        $app->halt(403, 'No, come with some media later.');

    $resource = explode('/', '1.1/statuses/update_with_media.json');
    $params = $req->params();

    $pt = new PTwip( $req->getMethod(), $resource,
        $req->headers(), $params);

    $pt->prepare_headers();
    $pt->proccess_media_upload_request();
    $pt->t_mode_load_bullet();
    $pt->t_mode_shoot_with_media();
    $pt->cook_the_prey();

    $code = $pt->response_info['http_code'];

    if($code < 200) {
        $app->halt(500);
    }

    $resp->status($code);
    $resp->body($pt->response_body);
    foreach($pt->response_headers as $k => $v) $resp[$k] = $v;

};

$app->post('/t/1.1/statuses/update_with_media.json', $update_with_media_proc);
$app->post('/t/statuses/update_with_media.json', $update_with_media_proc);

$app->map('/t/:resource+', function($resource) use ($app) {
    $req = $app->request();
    $resp = $app->response();
    $params = $req->params();

    $pt = new PTwip( $req->getMethod(), $resource,
        $req->headers(), $params);

    $pt->prepare_headers();
    $pt->t_mode_load_bullet();
    $pt->t_mode_shoot();
    $pt->cook_the_prey();

    $code = $pt->response_info['http_code'];

    if($code < 200) {
        $app->halt(500, 'Things got wrong on asking for the API server. See log.');
    }

    $resp->status($code);
    $resp->body($pt->response_body);
    foreach($pt->response_headers as $k => $v) $resp[$k] = $v;

})->via('GET', 'POST');

$app->run();
?>
