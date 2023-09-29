<?php

/**
 * Abstract Data Base and other functions
 *
 * @author brahman
 */
class AbstractDBi extends AbstractFunctionsi {

    public function db_query($sql) {
        return Pdoi::db_query($sql);
    }

    public function db_results($sql, $array = []) {
        return Pdoi::db_results($sql, $array);
    }

    public function db_fetch_object(&$arr) {
        if (sizeof($arr) > 0) {
            return array_unshift($arr);
        }
        return null;
    }

    public function db_fetch_row($sql, $array = []) {
        return Pdoi::db_fetch_row($sql, $array);
    }

    public function db_get_var($sql, $array = []) {
        return Pdoi::db_get_var($sql, $array);
    }

    public function escape($text) {
        return addslashes($text);
    }

    public function db_update($data, $table, $id) {
        return Pdoi::db_update($data, $table, $id);
    }

    public function db_insert($data, $table) {
        return Pdoi::db_insert($data, $table);
    }

}
