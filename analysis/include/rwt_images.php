<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


class RWTimages
{
    public static function get_last_updete($id)
    {
$sql = "SELECT `last_update` FROM `data_actors_meta` WHERE `actor_id`=".intval($id)." limit 1";
    $r = Pdo_an::db_fetch_row($sql);
    if ($r)
    {
        $Time= $r->last_update;
    }
    if (!$Time)
    {
        $Time = time();

        $sql = "UPDATE `data_actors_meta` SET `last_update` = ".$Time."  WHERE `actor_id`=".intval($id)." ";
        Pdo_an::db_query($sql);



    }
        return  $Time;

    }

    public static function get_image_link($id,$resolution=540,$request='')
    {

        $current_site  ='https://rightwingtomatoes.com';
        $cache_site  ='https://img.rightwingtomatoes.com';
        $las_update  =self::get_last_updete($id);

        $result = $cache_site.'/webp/'.$resolution.'/'.$current_site.'/analysis/create_image/' . $id .'_v'.$las_update.'.jpg.webp';
        //$result = $cache_site.'/webp/'.$resolution.'/'.$current_site.'/analysis/create_image/' . $id .'.jpg.webp';

         return $result;
    }




}