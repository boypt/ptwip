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

        if(in_array($resource[0], array("1.1", "1", "oauth")))
            $this->api_ver = array_shift($resource);
        else $this->api_ver = API_VERSION;

        $this->api_url = self::PARENT_API . $this->api_ver . '/' . implode('/', $resource);

        global $log;
        $log->debug("ptwip class init: ".print_r($this, true));
    }

    private function proccess_media_upload_request() {
        if(strpos(end($this->resource), 'update_with_media') !== false &&
            strpos(@$this->headers['CONTENT_TYPE'], 'multipart/form-data') !== false &&
            $this->method === 'POST' &&
            count($_FILES) > 0 ) {

            $media = @$_FILES['media'];
            if(is_array($media['tmp_name'])) $fn = $media['tmp_name'][0];
            else $fn = $media['tmp_name'];

            $this->params["media"] = '@' . $fn;
            unset($forward_hdr['CONTENT_TYPE']);
            $this->method = 'POST_UPLOAD';
        }
    }

    public function t_mode_transfer() {

        foreach($this->forward_headers_keys as $h) {
            if(array_key_exists($h, $this->headers))
                $this->forward_headers[$h] = $this->headers[$h];
        }

        $this->proccess_media_upload_request();
        $this->curl = $curl = new PTCurl(
            $this->api_url,
            $this->params,
            $this->forward_headers);

        switch($this->method) {
            case 'POST':
                $resp = $curl->http_post();
                break;
            case 'POST_UPLOAD':
                $resp = $curl->http_upload();
                break;
            case 'GET':
                $resp = $curl->http_get();
                break;
            default:
                return false;
        }

        $this->response_info = $curl->response_info;
        $this->response_body = $curl->response_body;

        $hdr = $curl->response_headers;
        if(!is_array($hdr) || count($hdr) === 0) 
            $hdr = array();

        $this->response_headers = $hdr;
        global $log;
        $log->debug("ptwip t_mode_transfer end: \n" . print_r($this, true));
        return $resp;
    }
}

?>
