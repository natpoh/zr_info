<?php

/**
 * @license Apache 2.0
 */

namespace OpenApi\Fd;

class Bootstrap extends Controllers\Controller {

    public $doc_domains = array(
        "http://api.zr.4aoc.ru/",
        "https://api.filmdemographics.com/",
    );
    public $trust_domains = array(
        "https://filmdemographics.com/",
    );
    
    private $admin_api = 'LDKW_asd46-545f';

    public function run($path_arr = array(), $query_args = array()) {

        $command = isset($path_arr[2]) ? $path_arr[2] : '';
        $command2 = isset($path_arr[3]) ? $path_arr[3] : '';

        // Check api key
        $api_valid = false;
        $preview_mode = false;

        // Doc domain
        if (isset($_SERVER['HTTP_REFERER']) && in_array($_SERVER['HTTP_REFERER'], $this->doc_domains)) {
            $api_valid = true;
            $preview_mode = true;
        }
        $api_valid = true;
        /*
        // Trust domain
        if (!$api_valid && isset($_SERVER['HTTP_REFERER']) && in_array($_SERVER['HTTP_REFERER'], $this->trust_domains)) {
            $api_valid = true;            
        }
               
        if (!$api_valid) {
            if (isset($query_args['api_key'])) {
                $api_key = $query_args['api_key'];
                // TODO check api limits
                if ($api_key == $this->admin_api){
                    $api_valid = true;
                }
            }
        }
        if (!$api_valid) {
            http_response_code(401);
            echo 'Unauthorized ' . $_SERVER['HTTP_ORIGIN'];
            exit;
        }*/

        if ($command == 'search') {
            $controller = new Controllers\SearchController($preview_mode);
            $controller->runPath($command2, $query_args);
        } elseif ($command == 'string_uri') {
            $controller = new Controllers\StrUriController($preview_mode);
            $controller->runPath($command2, $query_args);
        } elseif ($command == 'media') {
            $controller = new Controllers\MediaController($preview_mode);
            $query_args['media_id'] = $command2;
            $command3 = isset($path_arr[4]) ? $path_arr[4] : '';
            $controller->runPath($command3, $query_args);
        }
    }
}
