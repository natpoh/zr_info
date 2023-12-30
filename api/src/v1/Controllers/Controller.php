<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace OpenApi\Fd\Controllers;

/**
 * Description of Controller
 *
 * @author brahman
 */
class Controller extends \AbstractDBAn {

    public $sfunction = '';
    public $seach_arr = array();

    public function runPath($command = '', $query_args = []) {
        $sfunction = $this->sfunction;
        if (isset($this->seach_arr[$command])) {
            // Check paths
            $sfunction = $this->seach_arr[$command];
        }
        try {
            if ($sfunction) {
                $this->$sfunction($query_args);
            }
        } catch (Exception $exc) {
            
        }
    }

    public function responce($code = 200, $data = array()) {
        http_response_code($code);
        header('Content-Type: application/json');
        print json_encode($data);
    }

    public function getAnSearchFront() {
        if (!class_exists('AnalyticsFront')) {
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsFront.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsSearch.php' );
        }
        $sf = new \AnalyticsFront();
        return $sf;
    }
}
