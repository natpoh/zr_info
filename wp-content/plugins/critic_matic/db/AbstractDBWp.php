<?php

/**
 * Abstract Data Base and other functions
 *
 * @author brahman
 */
class AbstractDBWp extends AbstractFunctions {
    /*
     * Hash a link
     */

    //Abstract DB
    public function db_query($sql) {
        global $wpdb;
        return $wpdb->query($sql);
    }

    public function db_results($sql) {
        global $wpdb;
        return $wpdb->get_results($sql);
    }

    public function db_fetch_object(&$arr) {
        if (sizeof($arr) > 0) {
            return array_unshift($arr);
        }
        return null;
    }

    public function db_fetch_row($sql) {
        global $wpdb;
        return $wpdb->get_row($sql);
    }

    public function db_get_var($sql) {
        global $wpdb;
        return $wpdb->get_var($sql);
    }

    public function escape($text) {
        global $wpdb;
        return $wpdb->_escape($text);
    }

    public function db_update($data, $table, $id) {
        return Pdo_an::db_update($data, $table, $id);
    }

    public function db_insert($data, $table) {
        return Pdo_an::db_insert($data, $table);
    }

}
