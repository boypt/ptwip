<?php
require_once 'config.php';
require_once PHPBASE.'/lib/pt_curl.php';



class PTwip {

    const PARENT_API = 'https://api.twitter.com/';
    const PARENT_HOST = 'api.twitter.com';

    private $forward_headers_keys = array( 'USER-AGENT', 'AUTHORIZATION', 'CONTENT_TYPE', 'EXPECT'); 
    private $forward_headers = array('HOST' => self::PARENT_HOST, 'EXPECT' => '');

    function __construct($method, $resource, $headers, $params) {

        $this->method = $method;
        $this->resource = $resource;
        $this->headers = $headers;
        $this->params = $params;

        if(in_array($resource[0], array("1.1", "oauth")))
            $this->api_ver = array_shift($resource);
        else $this->api_ver = API_VERSION;

        $this->api_url = self::PARENT_API . $this->api_ver . '/' . implode('/', $resource);

        global $log;
        $log->debug("ptwip class init: ".print_r($this, true));
    }

    public function proccess_media_upload_request() {
        global $log;
        if( count($_FILES) > 0 ) {

            $media = @$_FILES['media'];
            $fn = is_array($media['tmp_name']) ? $media['tmp_name'][0] : $media['tmp_name'];
            $this->params["media"] = '@' . $fn;

            $log->debug("params .... " . print_r($this->params, true));
            unset($this->forward_headers['CONTENT_TYPE']);
        }
    }

    public function prepare_headers() {
        foreach($this->forward_headers_keys as $h) {
            if(array_key_exists($h, $this->headers))
                $this->forward_headers[$h] = $this->headers[$h];
        }
    }

    public function t_mode_load_bullet() {
        $this->curl = $curl = new PTCurl(
            $this->api_url,
            $this->params,
            $this->forward_headers
        );
    }

    public function t_mode_shoot () {
        switch($this->method) {
            case 'POST':
                $this->curl->http_post();
                break;
            case 'GET':
                $this->curl->http_get();
                break;
            default:
                //return false;
                //raise
        }
    }


    public function t_mode_shoot_with_media () {
        $this->curl->http_upload();
    }

    public function cook_the_prey() {

        $curl = $this->curl;

        $this->response_info = $curl->response_info;
        $this->response_body = $curl->response_body;

        $hdr = $curl->response_headers;
        if(!is_array($hdr) || count($hdr) === 0) 
            $hdr = array();
        $this->response_headers = $hdr;

        global $log;
        $log->debug("ptwip t_mode_transfer end: \n" . print_r($this, true));
    }
}

?>
