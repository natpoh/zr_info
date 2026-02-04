<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

!class_exists('CreateTsumbs') ? include ABSPATH . "analysis/include/create_tsumbs.php" : '';

if (!class_exists('GETTSUMB')) {

    class GETTSUMB
    {

        public function get_poster_tsumb($id, $array_request = array([220, 330], [440, 660]),$image='',$name='')
        {
           return  CreateTsumbs::get_poster_tsumb($id, $array_request ,$image,$name);
        }

     public function getThumbLocal_custom($x, $y,$root)
    {
        return   CreateTsumbs::getThumbLocal_custom($x,$y,$root);


    }

    }
}

if (!function_exists('get_poster_tsumb')) {
    function get_poster_tsumb($id, $array_request = array([220, 330], [440, 660]),$image='',$name='')
    {
        $data = new GETTSUMB;
        $array = $data->get_poster_tsumb($id, $array_request,$image,$name);
        return $array;
    }

}
