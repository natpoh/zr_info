<?php

/**
 * Abstract Data Base and other functions
 *
 * @author brahman
 */
class AbstractDBFda extends AbstractFunctions {

    public function db_query($sql) {
        return Pdo_fda::db_query($sql);
    }

    public function db_results($sql, $array = []) {
        return Pdo_fda::db_results($sql, $array);
    }

    public function db_fetch_object(&$arr) {
        if (sizeof($arr) > 0) {
            return array_unshift($arr);
        }
        return null;
    }

    public function db_fetch_row($sql, $array = []) {
        return Pdo_fda::db_fetch_row($sql, $array);
    }

    public function db_get_var($sql, $array = []) {
        return Pdo_fda::db_get_var($sql, $array);
    }

    public function escape($text) {
        return addslashes($text);
    }

    public function db_update($data, $table, $id) {
        return Pdo_fda::db_update($data, $table, $id);
    }

    public function db_insert($data, $table) {
        return Pdo_fda::db_insert($data, $table);
    }

}
