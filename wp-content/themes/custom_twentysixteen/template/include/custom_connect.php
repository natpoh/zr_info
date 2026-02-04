<?php
global $debug;
$debug=0;

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';

global $table_prefix;


//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

// Create connection

//$pdo =   Pdo_wp::connect();
//global $pdo;
if (!function_exists('set_post_meta_custom')){

    function set_post_meta_custom($id, $metakey = '',$value='')
    {
        global $table_prefix;
        global $pdo;

        if ($metakey && $id) {
            $sql = "DELETE FROM " . $table_prefix . "postmeta WHERE post_id =? and `meta_key` = '" . $metakey . "' ";
            Pdo_wp::db_results_array($sql,[$id]);


            $sql = "INSERT INTO `" . $table_prefix . "postmeta`  VALUES (NULL, '".$id."', '".$metakey."', ?) ";
            $id =Pdo_wp::db_insert_sql($sql,[$value]);

        }

        return $id;
    }
}
if (!function_exists('get_post_meta_custom')){

    function get_post_meta_custom($id, $metakey = '',$single='')
    {
        global $table_prefix;
        global $pdo;

        $meta = [];

        if ($metakey) {
            $sql = "SELECT meta_key, meta_value FROM " . $table_prefix . "postmeta WHERE post_id =? and `meta_key` = '" . $metakey . "' ";

            Pdo_wp::db_results_array($sql,[$id]);
        } else {
            $sql = "SELECT meta_key, meta_value FROM " . $table_prefix . "postmeta WHERE post_id =? ";
          $r=  Pdo_wp::db_results_array($sql,[$id]);

        }


        if ($single)
        {


            return $r[0]['meta_value'];
        }
        else {
            foreach ($r as $row) {

                $meta[$row['meta_key']] = $row['meta_value'];
            }

            return $meta;
        }
    }
}
if (!function_exists('get_post_data')) {
    function get_post_data($val, $ask, $query, $table)
    {
        ///////check mention

        global $table_prefix;
        $sql = "SELECT " . $ask . "  FROM `" . $table_prefix . $table . "`  WHERE " . $query . " = ?  LIMIT 1";

        $r = Pdo_wp::db_results_array($sql);
        return $r[0][$ask];

    }
}
