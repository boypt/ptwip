<?php

/**
 * A basic CURL wrapper
 *
**/

class PTCurl {

    private $url = '';
    private $params = null;
    private $headers = null;
    public $response_info = null;
    public $response_headers = null;
    public $response_headers_raw = array();
    public $verbose = null;

    function __construct($url, $params = null, $headers = null) {
        $this->url = $url;
        $this->params = $params;
        $this->headers = $headers;
        $this->ch = curl_init();
    }

    private function set_common_options() {

        curl_setopt_array($this->ch, array(
            //CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ));

        if(count($this->headers) > 0) {
            $headers = array();
            foreach ($this->headers as $key => $value) {
                $headers[] = "{$key}: {$value}";
            }
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        }
    }

    private function parse_response_headers() {

        if(strlen($this->response_headers_raw) > 0) {
            $headers = explode("\r\n", str_replace("\r\n\r\n", '', $this->response_headers_raw));
            foreach ($headers as $header) {
                if(strpos($header, ": ") === false) continue;
                preg_match('#(.*?)\:\s(.*)#', $header, $m);
                $this->response_headers[$m[1]] = $m[2];
            }
        }
    }


    public function go() {

        global $log;

        $app = \Slim\Slim::getInstance();
        if($app->getMode() === 'development') {
            $verbose = fopen('php://temp', 'rw+');
            curl_setopt($this->ch, CURLOPT_VERBOSE, true);
            curl_setopt($this->ch, CURLOPT_STDERR, $verbose);
            $log->debug("dev mode, VERBOSE");
        }

        $resp = curl_exec($this->ch);

        if ($resp) {
            list($headers, $body) = explode("\r\n\r\n", $resp, 2);
            $this->response_info = curl_getinfo($this->ch);
            $this->response_headers_raw = $headers;
            $this->response_body = $body;
        } else {
            $this->response_info = array();
            $this->response_headers_raw = '';
            $this->response_body = '';
        }

        curl_close($this->ch);

        if(isset($verbose) && rewind($verbose)) {
            $this->verbose = stream_get_contents($verbose);
            fclose($verbose);
        }
    }

    public function http_get() {
        $url = &$this->url;
        $params = &$this->params;

        if(count($this->params) > 0) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= (is_string($params)) ? $params : http_build_query($params, '', '&');
        }

        curl_setopt($this->ch, CURLOPT_URL , $this->url);
        curl_setopt($this->ch, CURLOPT_HTTPGET, true);

        $this->set_common_options();
        $this->go();
        $this->parse_response_headers();
    }

    public function http_post() {
        $url = &$this->url;
        $params = &$this->params;

        curl_setopt($this->ch, CURLOPT_URL , $this->url);
        curl_setopt($this->ch, CURLOPT_POST, true);

        if(is_array($params) && count($params) > 0) {
            $query = http_build_query($params, '', '&');
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $query);
        }

        $this->set_common_options();
        $this->go();
        $this->parse_response_headers();
    }

    public function http_upload() {
        $url = &$this->url;
        $params = &$this->params;

        curl_setopt($this->ch, CURLOPT_URL , $this->url);

        if(is_array($params) && count($params) > 0) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
        } else {
            die('not going any where.');
        }

        $this->set_common_options();
        $this->go();
        $this->parse_response_headers();
    }

}
