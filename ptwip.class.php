<?php
require_once 'config.php';
require_once PHPBASE.'/lib/curl.php';
require_once PHPBASE.'/lib/curl_response.php';

require_once('Log.php');


class PTwip {

    const PARENT_API = 'https://api.twitter.com/';

    private $forward_headers = array(
            'User-Agent',
            'Authorization',
            'Content-Type',
            'X-Forwarded-For',
            'Expect',
        );

    private $transmit_headers = array();
    private $transmit_params = null;

    function __construct($resc, &$app) {
        assert(is_array($resc));
        $this->resc = $resc;
        $this->app = $app;
        $this->log = \Log::factory('file', '/tmp/ptwip.log', 'Slim');
        if(preg_match('/^[0-9\.]{1,3}$/', $resc[0]) === 1 || $resc[0] === 'oauth') {
            $this->api_ver = array_shift($resc);
        } else {
            $this->api_ver = API_VERSION;
        }

        $this->api_url = self::PARENT_API . $this->api_ver . '/' . implode('/', $resc);
    }

    private function twitter_api_parse() {
    }

    public function t_mode_transfer() {

        $l = $this->log;

        $req = $this->app->request();
        $req_hdr = $req->headers();

        $forward_hdr = array();
        foreach($this->forward_headers as $h) {
            if($req_hdr->has($h))
                $forward_hdr[$h] = $req_hdr[$h];
        }

        $forward_hdr['Expect'] = '';
        $forward_hdr['Host'] = 'api.twitter.com';

        $this->curl = $curl = new Curl();
        $curl->headers = $forward_hdr;

        //$curl->options = array('CURLOPT_HTTPPROXYTUNNEL' => true, 'CURLOPT_PROXY' => '192.168.0.197:8124');
        $curl->options = array('CURLOPT_TIMEOUT' => 10);

        if(strpos(end($this->resc), 'update_with_media') !== false) {
            die();
        }

        switch($req->getMethod()) {
            case 'POST':
                $this->resp = $curl->post($this->api_url, $req->params());
                break;
            case 'GET':
                $this->resp = $curl->get($this->api_url, $req->params());
                break;
            case 'DELETE':
                $this->resp = $curl->delete($this->api_url, $req->params());
                break;
            default:
                break;
        }

        return $this->resp;
    }
}

?>
