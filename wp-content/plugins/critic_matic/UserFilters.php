<?php

/**
 * Description of UserFilters
 *
 * @author brahman
 * 
 * TODO
 * 1. Check user login
 * 2. Edit link
 * 3. 
 * 
 */
class UserFilters extends AbstractDB {

    private $cm;
    private $db;

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->db = array(
            'user_filters' => 'data_user_filters',
            'link_filters' => 'data_link_filters',
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

    public function link_form($data) {
        ?>
        <div class="row ">
            <div class="col_title ">Filter Title:</div>
            <div class="col_input">
                <input data-id="title" class="title" value="" placeholder="title">
                <div class="col_desc"></div>
            </div>                
        </div>
        <div class="row ">
            <div class="col_title ">Description:</div>
            <div class="col_input">
                <input data-id="desc" class="title" value="" placeholder="title">
                <div class="col_desc"></div>
            </div>                
        </div>
        <?php
    }

    public function link_exist($link = '') {
        $link_hash = $this->link_hash($link);

        // Get user
        $user = wp_get_current_user();
        $wp_uid = $user->exists() ? $user->ID : 0;

        $ret = array(
            'exist' => 0,
            'error' => '',
            'data' => array(),
            'user' => array(),
        );

        if (!$wp_uid) {
            $ret['exist'] = -1;
            $ret['error'] = 'Need login';
        }

        if (!$ret['error']) {
            // Get exist link
            $link_data = $this->get_link_by_hash($link_hash);
            if ($link_data) {
                // Check user data
                $ret['exist'] = 1;
                $ret['data'] = $link_data;
                $user_data = $this->get_user_data($link_data->id, $wp_uid);
                if ($user_data) {
                    $ret['exist'] = 2;
                    $ret['user'] = $user_data;
                }
            }
        }

        return $ret;
    }

    private function get_link_by_hash($link_hash = '') {
        $sql = sprintf("SELECT * FROM {$this->db['link_filters']} WHERE link_hash='%s'", $link_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    private function get_user_data($fid = 0, $uid = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['user_filters']} WHERE fid=%d AND uid=%d", $fid, $uid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

}
