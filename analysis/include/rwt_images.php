<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


class RWTimages
{

    public static function get_last_time($id)
    {
        $sql ="select add_time  from data_movie_imdb where id =".intval($id);
        $r = Pdo_an::db_fetch_row($sql);
        return $r->add_time;
    }

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
    public static function get_simple_image_link($id='',$w=640,$las_update='',$image ='')
    {

        $current_site  ='https://rightwingtomatoes.com';
        $cache_site  ='https://img.rightwingtomatoes.com';

        if ($image)
        {
            $result = $cache_site.'/'.$image;
            return $result;
        }


        $result = $cache_site.'/jpg/'.$w.'/'.$current_site.'/analysis/create_image/' . $id .'_v'.$las_update.'.jpg.jpg';


        return $result;
    }

    public static function get_image_link($id='',$resolution=540,$request='',$las_update='',$image ='')
    {


        $current_site  ='https://rightwingtomatoes.com';
        $cache_site  ='https://img.rightwingtomatoes.com';

        if ($image)
        {
            $result = $cache_site.'/webp/'.$resolution.'/'.$image.'.webp';
            return $result;
        }


        if (!$las_update)
        {
            $las_update  =self::get_last_updete($id);
        }


        $result = $cache_site.'/webp/'.$resolution.'/'.$current_site.'/analysis/create_image/' . $id .'_v'.$las_update.'.jpg.webp';
        //$result = $cache_site.'/webp/'.$resolution.'/'.$current_site.'/analysis/create_image/' . $id .'.jpg.webp';

         return $result;
    }




}