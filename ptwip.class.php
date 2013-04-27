<?php
require_once 'config.php';

class PTwip {

    const PARENT_API = 'https://api.twitter.com/';

    function __construct($req, &$app) {
        assert(count($req) > 0);
        if(preg_match('/^[0-9\.]{1,3}$/', $req[0]) === 1) {
            $this->api_ver = $req[0];
            array_shift($req);
        } else {
            $this->api_ver = API_VERSION;
        }

        $this->req = $req;
        $this->app = $app;
    }

    public function t_mode_transfer() {
        $url = self::PARENT_API . $this->api_ver . '/' . implode('/', $this->req);
        return $url;
    }
}

?>
