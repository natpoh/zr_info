<?php

error_reporting('E_ALL');
ini_set('display_errors', 'On');




if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');



//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

!function_exists('wp_custom_cache') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php" : '';

!class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';

!class_exists('CreateTsumbs') ? include ABSPATH . "analysis/include/create_tsumbs.php" : '';


include(ABSPATH.'wp-content/themes/custom_twentysixteen/template/include/custom_connect.php');

class TV_Scroll {

    private static function prepare_movies($id,$content_result,$slug = 'tvseries')
    {

        if ($content_result['result'][$id])
        {
            return  $content_result;
        }

        global $cfront;

        try {
            $array_tsumb = CreateTsumbs::get_poster_tsumb_fast($id, array([220, 330], [440, 660]));
        } catch (Exception $ex) {

            ///  var_dump($ex);
            // return null;
        }
        $sql = "select * from data_movie_imdb where id = " . $id . " limit 1";
        $rows = Pdo_an::db_fetch_row($sql);

        $post_name = $rows->post_name;
        $title = $rows->title;
        $type = $rows->type;
        $release = strtotime($rows->release);

            if (!$post_name) {
                if (!$cfront) {
                    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                    }

                    if (!class_exists('CriticFront')) {
                        require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
                    }
                    $cfront = new CriticFront();
                }
                if ($cfront) {
                    $post_name = $cfront->get_or_create_ma_post_name($id);
                }
            }


            $content_result['result'][$rows->id] = array(
                'link' => '/' . $slug . '/' . $post_name,
                'title' => $title,
                'genre' => $rows->genre,
                'poster_link_small' => $array_tsumb[0],
                'poster_link_big' => $array_tsumb[1],
                'type' => $slug
            );


        return $content_result;
    }

    public static function show_scroll($type='TVSeries') {

        if ($type== 'TVSeries') {
            $sql = "SELECT * FROM `options` where id = 14";
            $rows = Pdo_an::db_fetch_row($sql);
            $array_movies_dop = [];
            $array_movies = $rows->val;
            if ($array_movies) {
                $array_movies = json_decode($array_movies, 1);
            }


        }

            $starttime = time();
            $date_current = date('Y-m-d', $starttime);
            $date_main = date('Y-m-d', strtotime('-6 month', $starttime));
            $sql = "SELECT * FROM `data_movie_imdb` WHERE `release`  >=  '" . $date_main . "' and `release`  <=  '" . $date_current . "' and `type`= '".$type."' order by `rating` desc , `release` desc LIMIT 30 ";

            $rows = Pdo_an::db_results_array($sql);
            foreach ($rows as $r) {
                $movie_id = $r['id'];
                $array_movies_dop[$movie_id] = strtotime($r['release']);
            }

        $content_result=[];



        if (is_array($array_movies)) {
            arsort($array_movies);
            $i = 0;

            $cfront = '';
            foreach ($array_movies as $id => $enable) {


                $content_result = self::prepare_movies($id, $content_result);
                //else echo $imdb_id.'found<br>';


                if (count($content_result['result']) >= 20) {
                    break;
                }
            }
        }

            if (count($content_result['result']) < 20) {
                foreach ($array_movies_dop as $id => $enable) {


                    $content_result = self::prepare_movies($id, $content_result,strtolower($type));
                    //else echo $imdb_id.'found<br>';


                    if (count($content_result['result']) >= 20) {
                        break;
                    }
                }
            }



            if (count($content_result['result']) ) {

                if ($type== 'TVSeries') {
                    $link = '/search/type_tv';
                    $title = 'Load more Streaming';
                }
                else if ($type== 'VideoGame') {
                    $link = '/search/type_videogame';
                    $title = 'Load more Games';
                }

                $content_result['result'][] = array('link' => $link, 'title' => $title, 'genre' => 'load_more', 'poster_link_small' => '', 'poster_link_big' => '', 'content_pro' => '');
                $content_result['count'] = count($content_result['result']);
                return $content_result;
            }

        else  {
            $content_result['count'] = 0;
            $content_result['message'] = 'no result';
            return $content_result;
        }
    }

}

function tv_scroll($type='TVSeries') {
    $content_result = TV_Scroll::show_scroll($type);
    include(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/video_item_template.php');    
    $content_result['tmpl'] = $video_template;

    if ($content_result['result']) {
        $RWT_RATING = new RWT_RATING;
        $content_result['rating'] = $RWT_RATING->get_rating_data($content_result['result']);
    }

    $content_string = json_encode($content_result);

    return $content_string;
}

//echo tv_scroll();
//return;

if (isset($_GET['type']))
{
    if ($_GET['type'] =='games')
    {
        $cache = tv_scroll('VideoGame');
    }
}
else {

//    if (function_exists('wp_custom_cache')) {
//
//        $cache = wp_custom_cache('tv_scroll', 'fastcache', 3600);
//    } else {
        $cache = tv_scroll();
    //}
}


echo $cache;
