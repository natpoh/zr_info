<?php

if ( !defined('ABSPATH') )
	define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

class FileService {

    public static function check_and_create_dir($path) {
        # Создать дирикторию
        $arr = explode("/", $path);

        $path = '';
        if (ABSPATH) {
            $path = ABSPATH . '/';
        }
        foreach ($arr as $a) {
            if ($a) {
                $path = $path . $a . '/';
                self::fileman($path);
            }
        }
        return null;
    }

    public static function check_and_create_abs_dir($abs_path) {
        $ret = true;
        if (!file_exists($abs_path)) {
            $path = str_replace(ABSPATH, '', $abs_path);
            $arr = explode("/", $path);
            $path = ABSPATH;
            foreach ($arr as $a) {
                if ($a) {
                    $path = $path . $a . '/';
                    if (!self::fileman($path)) {
                        $ret = false;
                        break;
                    }
                }
            }
        }
        return $ret;
    }

    private static function fileman($way) {
        //Проверка наличия и создание директории
        // string $way - путь к дириктории
        $ret = true;
        if (!file_exists($way)) {
            if (!mkdir("$way", 0777)) {
                $ret = false;
                // throw new Exception('Can not create dir: ' . $way . ', check cmod');
            }
        }
        return $ret;
    }

}