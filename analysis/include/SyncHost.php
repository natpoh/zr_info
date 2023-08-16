<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';

/**
 * @author brahman
 */
class SyncHost {

    public static function push_file_analysis($path = '', $debug=false) {
        $data = array(
            'cmd' => 'rsync_file_analysis',
            'path' => $path,
        );
        if ($debug) {
            print_r($data);
        }
        if (!defined('SYNC_DST_HOST')) {
            return false;
        }
        $host = SYNC_DST_HOST;        
        return self::post($data, $host);
    }

    public static function post($data = array(), $host='') {


        $fields_string = http_build_query($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        return $result;
    }

}
