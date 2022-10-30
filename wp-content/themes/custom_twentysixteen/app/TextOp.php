<?php

class TextOp {

    public static function okonchanie($num = 1, $one = '', $two = 'а', $five = 'ов') {
        $ret = $one;
        if ($num > 100) {
            $sNum = ' ' . $num;
            $num = substr($sNum, strlen($sNum) - 2);
        }

        if ($num >= 20) {
            $sNum = ' ' . $num;
            $num = $sNum[strlen($sNum) - 1];
        }
        //p_r($num);
        switch ($num) {
            case 1:
                $num = $one;
                break;
            case 2:
                $num = $two;
                break;
            case 3:
                $num = $two;
                break;
            case 4:
                $num = $two;
                break;
            default:
                $num = $five;
                break;
        }
        return $num;
    }

    public static function obrezat_text($text = '', $length = 10, $tchk = true) {
        /*  Умная обрезалка текста
         *  string $text - текст
         *  int $length - длина символов        
         */
        if (strlen($text) > $length) {
            $pos = strpos($text, ' ', $length);
            if ($pos != null)
                $text = substr($text, 0, $pos);
            if ($tchk) {
                $text = $text . '...';
            }
        }
        return $text;
    }

    public static function expfilter($text, $video=false) {
        # Чистка эксперта
        //kill style
        $text = preg_replace('/\<style.*\<\/style\>/Us', '', $text);

        $text = str_replace("<br />", " ", $text);

        //kill tags
        $text = strip_tags($text);

        //n
        $text = str_replace("\n", " ", $text);

        //kill okbm
        if (strstr($text, 'okbm(') == true)
            $text = preg_replace('#okbm\(.*\)#Ui', '', $text);
        //kill audio embled
        $text = preg_replace('#\[audio:.*\]#Ui', '', $text);
        //kill video embled
        if (!$video){
            $text = preg_replace('#\[video:.*\]#Ui', '', $text);
        } else {
            $text = preg_replace('#\[video\:(.+) width[^\]]*\]#i', '$1 ', $text);
        }
        //$text = apply_filters('the_expert', $text);
        //kill cdata
        $text = str_replace('<![CDATA[', '', $text);
        $text = str_replace(']]>', '', $text);
        $text = str_replace('&nbsp;', ' ', $text);

        return $text;
    }

    function get_mount_name($num) {
        $mounts = array(' ', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
        return $mounts[(int) $num];
    }

}

