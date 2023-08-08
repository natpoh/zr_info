<?php

/**
 * Description of UserFilters
 *
 * @author brahman
 */
class UserFilters extends AbstractDB {

    private $cm;
    private $db;

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->db = array(
            'user_filters' => 'data_user_filters',
        );
    }

    public function ajax() {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-type: application/json');
        header('Access-Control-Allow-Origin: *');

        $rtn = array();
        
        /*
         * TODO         
         * 1. Validate data
         * 2. Add data
         */
        
        die(json_encode($rtn));
    }
}
