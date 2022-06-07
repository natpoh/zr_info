<?php

/**
 * Abstract Data Base and other functions
 *
 * @author brahman
 */
class MoviesAbstractDBWp extends MoviesAbstractFunctions {

    //Abstract DB
    public function db_query($sql) {
        return Pdo_wp::db_query($sql);
    }

    public function db_results($sql, $array = []) {
        return Pdo_wp::db_results($sql, $array);
    }

    public function db_fetch_object(&$arr) {
        if (sizeof($arr) > 0) {
            return array_unshift($arr);
        }
        return null;
    }

    public function db_fetch_row($sql, $array = []) {
        return Pdo_wp::db_fetch_row($sql, $array);
    }

    public function db_get_var($sql, $array = []) {
        return Pdo_wp::db_get_var($sql, $array);
    }

    public function escape($text) {
        global $wpdb;
        return $wpdb->_escape($text);
    }

    public function db_update($data, $table, $id) {
        return Pdo_wp::db_update($data, $table, $id);
    }

    public function db_insert($data, $table) {
        return Pdo_wp::db_insert($data, $table);
    }

}
