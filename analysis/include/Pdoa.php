<?php

/**
 * PDO abstract
 * Description of Pdoa
 *
 * @author brahman
 */
class Pdoa {

    //put your code here
    public static $pdo = false;
    public static $db_host = DB_HOST_AN;
    public static $db_name = DB_NAME_AN;
    public static $db_user = DB_USER_AN;
    public static $db_pass = DB_PASSWORD_AN;
    public static $db_charset = DB_CHARSET_AN;

    /*
     * Get pdo instance
     */

    public static function connect() {
        if (static::$pdo) {
            return static::$pdo;
        }

        try {
            static::$pdo = new PDO("mysql:host=" . static::$db_host . ";dbname=" . static::$db_name, static::$db_user, static::$db_pass);
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }

        static::$pdo->exec("SET NAMES '" . static::$db_charset . "' ");
        return static::$pdo;
    }

    //Abstract DB
    public static function db_query($sql) {
        static::connect();
        static::$pdo->query($sql);
    }

    public static function last_id() {
        return static::$pdo->lastInsertId();
    }

    public static function db_results($sql, $array = []) {
        static::connect();
        $sth = static::$pdo->prepare($sql);
        $sth->execute($array);
        $data = $sth->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public static function db_results_array($sql, $array = []) {
        static::connect();
        $sth = static::$pdo->prepare($sql);
        $sth->execute($array);
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public static function db_fetch_object(&$arr) {
        if (sizeof($arr) > 0) {
            return array_unshift($arr);
        }
        return null;
    }

    public static function db_insert($data, $table) {
        $values = array();
        $val_str = array();
        $keys = array();
        foreach ($data as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
            $val_str[] = "?";
        }
        $sql = "INSERT INTO {$table} (" . implode(",", $keys) . ") VALUES (" . implode(",", $val_str) . ")";
        //print_r($data);
        //print_r($sql);
        static::connect();
        $sth = static::$pdo->prepare($sql);
        $sth->execute($values);
        // $arr = $sth->errorInfo();
        // print_r($arr);
    }

    public static function db_update($data, $table, $id) {
        $update = array();
        $values = array();
        foreach ($data as $key => $value) {
            $update[] = $key . "=?";
            $values[] = $value;
        }
        $sql = "UPDATE {$table} SET " . implode(',', $update) . " WHERE id = " . $id;

        static::connect();
        $sth = static::$pdo->prepare($sql);
        $sth->execute($values);
    }

    public static function db_fetch_row($sql, $array = [], $type = 'object') {
        static::connect();
        $sth = static::$pdo->prepare($sql);
        $sth->execute($array);
        if ($type == 'object') {
            $data = $sth->fetch(PDO::FETCH_OBJ);
        } else if ($type == 'array') {
            $data = $sth->fetch(PDO::FETCH_ASSOC);
        }

        return $data;
    }

    public static function db_get_var($sql, $array = []) {
        static::connect();
        $sth = static::$pdo->prepare($sql);
        $sth->execute($array);
        $row = $sth->fetch(PDO::FETCH_OBJ);
        $ret = '';

        if (sizeof((array) $row) > 0) {
            foreach ((array) $row as $item) {
                $ret = $item;
                break;
            }
        }

        return $ret;
    }

    public static function quote($sql) {
        static::connect();
        return static::$pdo->quote($sql);
    }

    public static function db_get_data($sql, $input, $array = []) {
        static::connect();
        $sth = static::$pdo->prepare($sql);
        $sth->execute($array);
        $data = $sth->fetch(PDO::FETCH_OBJ);
        return $data->{$input};
    }

    public static function get_post_meta($id, $metakey = '', $single = '') {
        global $table_prefix;
        $meta = [];
        if ($metakey) {
            $sql = "SELECT meta_key, meta_value FROM " . $table_prefix . "postmeta WHERE post_id =? and `meta_key` = '" . $metakey . "' ";
            static::connect();
            $sth = static::$pdo->prepare($sql);
            $sth->execute([$id]);
        } else {
            $sql = "SELECT meta_key, meta_value FROM " . $table_prefix . "postmeta WHERE post_id =? ";
            static::connect();
            $sth = static::$pdo->prepare($sql);
            $sth->execute([$id]);
        }
        $sth->setFetchMode(PDO::FETCH_ASSOC);
        if ($single) {
            $r = $sth->fetch();
            return $r['meta_value'];
        } else {
            while ($r = $sth->fetch()) {
                $meta[$r['meta_key']] = $r['meta_value'];
            }
            return $meta;
        }
    }

    public static function set_post_meta($id, $metakey = '', $value = '') {
        global $table_prefix;

        $sql = "SELECT meta_id FROM " . $table_prefix . "postmeta WHERE post_id =? and meta_key=? limit 1";
        static::connect();
        $sth = static::$pdo->prepare($sql);
        $sth->execute([$id, $metakey]);
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $meta_id = $data->meta_id;

        if ($meta_id) {
            ///update
            $sql = "UPDATE `" . $table_prefix . "postmeta` set meta_value=? where meta_id =? ";
            static::connect();
            $sth = static::$pdo->prepare($sql);
            $sth->execute([$value, $meta_id]);
        } else {
            $sql = "INSERT INTO `" . $table_prefix . "postmeta`  VALUES (NULL, '" . $id . "', '" . $metakey . "', ?) ";
            static::connect();
            $sth = static::$pdo->prepare($sql);
            $sth->execute([$value]);
        }

        return $id;
    }

}

/*
 * Pdo from analytics
 */

class Pdo_an extends Pdoa {

    public static $pdo = false;
    public static $db_host = DB_HOST_AN;
    public static $db_name = DB_NAME_AN;
    public static $db_user = DB_USER_AN;
    public static $db_pass = DB_PASSWORD_AN;
    public static $db_charset = DB_CHARSET_AN;

}

/*
 * Pdo from WP
 */

class Pdo_wp extends Pdoa {

    public static $pdo = false;
    public static $db_host = DB_HOST_WP;
    public static $db_name = DB_NAME_WP;
    public static $db_user = DB_USER_WP;
    public static $db_pass = DB_PASSWORD_WP;
    public static $db_charset = DB_CHARSET_WP;

}

/*
 * Pdo from Staff
 */

class Pdo_stf extends Pdoa {

    public static $pdo = false;
    public static $db_host = DB_HOST_STF;
    public static $db_name = DB_NAME_STF;
    public static $db_user = DB_USER_STF;
    public static $db_pass = DB_PASSWORD_STF;
    public static $db_charset = DB_CHARSET_STF;

}

/*
 * Pdo from analytics
 */

class Pdo_tc extends Pdoa {

    public static $pdo = false;
    public static $db_host = DB_HOST_TC;
    public static $db_name = DB_NAME_TC;
    public static $db_user = DB_USER_TC;
    public static $db_pass = DB_PASSWORD_TC;
    public static $db_charset = DB_CHARSET_TC;

}

/*
 * Pdo from movies links
 */

class Pdo_ml extends Pdoa {

    public static $pdo = false;
    public static $db_host = DB_HOST_ML;
    public static $db_name = DB_NAME_ML;
    public static $db_user = DB_USER_ML;
    public static $db_pass = DB_PASSWORD_ML;
    public static $db_charset = DB_CHARSET_ML;

}
