<?php

class FileService {

    function getProxy($url, $proxy = '', &$header = '') {
        $user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.105 Safari/537.36';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        if ($proxy)
            curl_setopt($ch, CURLOPT_PROXY, "$proxy");

        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close($ch);

        return $body;
    }

    function check_and_create_dir($path) {
        # Создать дирикторию

        $path_abs = '';
        if (ABSPATH) {
            $path_abs = ABSPATH;
        }

        $path= str_replace($path_abs, '', $path);

        $arr = explode("/", $path);

        foreach ($arr as $a) {
            if ($a) {
                $path_abs = $path_abs . $a . '/';
                $this->fileman($path_abs);
            }
        }
        return null;
    }

    function check_and_create_abs_dir($abs_path) {
        $ret = true;
        if (!file_exists($abs_path)) {
            $path = str_replace(ABSPATH, '', $abs_path);
            $arr = explode("/", $path);
            $path = ABSPATH;
            foreach ($arr as $a) {
                if ($a) {
                    $path = $path . $a . '/';
                    if (!$this->fileman($path)) {
                        $ret = false;
                        break;
                    }
                }
            }
        }
        return $ret;
    }

    function fileman($way) {
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

if (!function_exists('getProxy')) {

    function getProxy($url, $proxy = '', &$header = '') {
        $fs = new FileService();
        return $fs->getProxy($url, $proxy, $header);
    }

}