<?php

/**
 * Abstract Data Base and other functions
 *
 * @author brahman
 */
class MoviesAbstractDBAn extends MoviesAbstractFunctions {

    public function db_query($sql) {
        return Pdo_an::db_query($sql);
    }

    public function db_results($sql) {
        return Pdo_an::db_results($sql);
    }

    public function db_fetch_object(&$arr) {
        if (sizeof($arr) > 0) {
            return array_unshift($arr);
        }
        return null;
    }

    public function db_fetch_row($sql) {
        return Pdo_an::db_fetch_row($sql);
    }

    public function db_get_var($sql) {
        return Pdo_an::db_get_var($sql);
    }

    public function escape($text) {
        return addslashes($text);
    }

}
