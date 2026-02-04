<?php

include "get_curl.php";
include "Pdoa.php";


class Franchises extends GETCURL
{

private function get_array()
{
    $array_result  =[];
    $url ='https://www.boxofficemojo.com/franchise/';

    $result = static::getCurlCookie($url);
    /// var_dump($result);
    $regv = '#href\=\"\/franchise\/fr([0-9]+)[^\>]+\>([^\<]+)\<\/a\>#';
    if (preg_match_all($regv, $result, $match)) {
        foreach ($match[0] as $i) {
            if (preg_match($regv, $i, $mach_result)) {
                $array_result[$mach_result[2]] =$mach_result[1];
            }
        }
    }
    return $array_result;

}


public static function parse()
{

    $array_result =static::get_array();

    ////save to db


    var_dump($array_result);

}



}