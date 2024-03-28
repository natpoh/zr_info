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

    public static function get_meta_img($id)
    {
        $sql = "SELECT `img`, `tmdb_img` ,`crowd_img` FROM `data_actors_meta` WHERE `actor_id`=".intval($id)." limit 1";
        $r = Pdo_an::db_fetch_row($sql);
        if ($r)
        {
        $result =  $r->img;
        $result_tmdb =  $r->tmdb_img;

        if ($result_tmdb)$result=2;

        if (!$result && $r->crowd_img==1)$result=3;
        }
        if (!$result)$result=0;
        return $result;

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

        if (strstr($id,'m_') && !$image)
        {
            $id =substr($id,2);
            ///https://img2.zeitgeistreviews.com/poster_thumb/220x330/21055.webp

            $result = 'https://img2.zeitgeistreviews.com/poster_thumb/'.$w.'/'.$id.'-'.$las_update.'.jpg';

            return $result;

        }



        $current_site  ='https://info.antiwoketomatoes.com';
        $cache_site  ='https://img.zeitgeistreviews.com';
        if (defined('LOCALCACHEIMAGES'))
        {


            if (LOCALCACHEIMAGES ==1)
            {
                $cache_site  ='https://img.4aoc.ru';
                $current_site ='https://zeitgeistreviews.com';

            }
            else if (LOCALCACHEIMAGES ==2)
            {
                $cache_site  ='https://img2.zeitgeistreviews.com';
                $current_site ='https://zeitgeistreviews.com';
            }
        }
        if ($image)
        {
            $result = $cache_site.'/jpg/'.$w.'/'.$image.'.jpg';
            return $result;
        }



        ////https://img2.zeitgeistreviews.com/jpg/640/https://zeitgeistreviews.com/analysis/create_image/m_133608_v1701333138.jpg.jpg
        $result = $cache_site.'/jpg/'.$w.'/'.$current_site.'/analysis/create_image/' . $id .'_v'.$las_update.'.jpg.jpg';

        return $result;
    }


    public static function get_image_link($id='',$resolution=540,$request='',$las_update='',$image ='',$original=0)
    {


        if (strstr($id,'m_') && !$image)
        {
            $id =substr($id,2);

            $result = 'https://img2.zeitgeistreviews.com/poster_thumb/'.$resolution.'/'.$id.'-'.$las_update.'.webp';
            return $result;

        }

        $current_site  ='https://info.antiwoketomatoes.com';
        $cache_site  ='https://img.zeitgeistreviews.com';


        if (defined('LOCALCACHEIMAGES'))
        {
            if (LOCALCACHEIMAGES ==1)
            {
                $cache_site  ='https://img.4aoc.ru';
                $current_site ='https://zeitgeistreviews.com';
            }
            else if (LOCALCACHEIMAGES ==2)
            {
                $cache_site  ='https://img2.zeitgeistreviews.com';
                $current_site ='https://zeitgeistreviews.com';
            }
        }

        if ($image)
        {
            $result = $cache_site.'/webp/'.$resolution.'/'.$image.'.webp';
            return $result;
        }

        if ($original)
        {
            $img_type = self::get_meta_img($id);
            $result = $cache_site.'/webp/'.$resolution.'/'.$current_site.'/analysis/create_image/' . $id .'_o'.$img_type.'.jpg.webp';

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