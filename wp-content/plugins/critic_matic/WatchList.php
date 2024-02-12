<?php

/**
 * @author brahman
 */
class WatchList extends AbstractDB {

    private $db;

    public function __construct() {
        $this->db = array(
            'list' => 'watch_list',
            'item' => 'watch_item',
        );
    }

    /*
     * Ajax
     */

    public function ajax_get_user_lists() {
        // Get current user
        $wp_uid = 0;
        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();
            if ($user->exists()) {
                $wp_uid = $user->ID;
            }
        }
        $lists = array();
        if ($wp_uid) {
            // Get user lists
            $lists = $this->get_user_lists($wp_uid);
        }
        $ret = array('uid' => $wp_uid, 'lists' => $lists);
        print json_encode($ret);
        exit();
    }

    public function get_user_lists($uid = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['list']} WHERE uid=%d", (int) $uid);
        $results = $this->db_results($sql);
        return $results;
    }

    public function get_user_list($lid = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['item']} WHERE lid=%d", (int) $lid);
        $results = $this->db_results($sql);
        return $results;
    }

    public function add_list($uid = 0, $name = '', $share = 0) {
        $data = array(
            'uid' => $uid,
            'date' => $this->curr_time(),
            'name' => $name,
            'share' => $share,
        );
        $id = $this->db_insert($data, $this->db['list']);
        return $id;
    }

    public function update_list($data = array(), $id) {
        $data['last_update'] = $this->curr_time();
        $this->db_update($data, $this->db['list'], $id);
    }

    public function add_item($mid = 0, $lid = 0, $weight = 0) {
        $data = array(
            'mid' => $mid,
            'lid' => $lid,
            'date' => $this->curr_time(),
            'weight' => $weight,
        );

        $id = $this->db_insert($data, $this->db['item']);
        return $id;
    }

    public function update_item($data = array(), $id) {
        $this->db_update($data, $this->db['item'], $id);
    }
}
