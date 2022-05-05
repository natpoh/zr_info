<?php
global $debug;
$debug=0;

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';



///$configdata = file_get_contents($_SERVER['DOCUMENT_ROOT']. '/wp-config.php');
//$reg = "/'(DB_[^\)]+)'\, '([^']+)'/is";
//
//if (preg_match_all($reg, $configdata, $mach)) {
/////  var_dump($mach);
//
//    foreach ($mach[1] as $index => $val) {
//        $array_login[$val] = $mach[2][$index];
//    }
//}
//
////var_dump($array_login);
//
//$reg2 = "/table_prefix  \= '([^']+)';/";
//
//if (preg_match($reg2, $configdata, $mach2)) {
//
//    $table_prefix = $mach2[1];
//}
global $table_prefix;

// Create connection
$pdo = new PDO("mysql:host=" .DB_HOST_WP . ";dbname=" . DB_NAME_WP, DB_USER_WP, DB_PASSWORD_WP);
$pdo->exec("SET NAMES '" . DB_CHARSET_WP . "' ");
$pdo->query('use '.DB_NAME_WP);
global $pdo;

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
            $q = $pdo->prepare($sql);
            $q->execute([$id]);

            $sql = "INSERT INTO `" . $table_prefix . "postmeta`  VALUES (NULL, '".$id."', '".$metakey."', ?) ";
            $q = $pdo->prepare($sql);
            $q->execute([$value]);
            $id = $pdo->lastInsertId();
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

            $q = $pdo->prepare($sql);
            $q->execute([$id]);
        } else {
            $sql = "SELECT meta_key, meta_value FROM " . $table_prefix . "postmeta WHERE post_id =? ";
            $q = $pdo->prepare($sql);
            $q->execute([$id]);
        }

        $q->setFetchMode(PDO::FETCH_ASSOC);

        if ($single)
        {
            $r = $q->fetch();

            return $r['meta_value'];
        }
        else {
            while ($r = $q->fetch()) {

                $meta[$r['meta_key']] = $r['meta_value'];
            }

            return $meta;
        }
    }
}
if (!function_exists('get_post_data')) {
    function get_post_data($val, $ask, $query, $table)
    {
        ///////check mention

        global $pdo;
        global $table_prefix;
        $sql = "SELECT " . $ask . "  FROM `" . $table_prefix . $table . "`  WHERE " . $query . " = ?  LIMIT 1";

        $q = $pdo->prepare($sql);
        $q->execute(array($val));
        $q->setFetchMode(PDO::FETCH_ASSOC);
        $r = $q->fetch();
        return $r[$ask];

    }
}
