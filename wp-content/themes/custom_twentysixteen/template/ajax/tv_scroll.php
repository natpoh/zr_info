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

    public static function show_scroll() {

        $sql = "SELECT * FROM `options` where id = 14";
        $rows = Pdo_an::db_fetch_row($sql);

        $array_movies = $rows->val;
        if ($array_movies) {
            $array_movies = json_decode($array_movies, 1);
        } else {
            $starttime = time();
            $date_current = date('Y-m-d', $starttime);
            $date_main = date('Y-m-d', strtotime('-1 year', $starttime));
            $sql = "SELECT * FROM `data_movie_imdb` WHERE `release`  >=  '" . $date_main . "' and `release`  <=  '" . $date_current . "' and `type`= 'TVSeries' order by `release` desc LIMIT 20 ";
            $rows = Pdo_an::db_results_array($sql);
            foreach ($rows as $r) {
                $movie_id = $r['id'];
                $array_movies[$movie_id] = strtotime($r['release']);
            }
            arsort($array_movies);
        }

        if (is_array($array_movies)) {

            $i = 0;

            $cfront = '';
            foreach ($array_movies as $id => $enable) {

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


                $slug = 'tvseries';


                if (!$post_name) {
                    if (!$cfront) {
                        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                        }

                        if (!class_exists('CriticFront')) {
                            require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
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

                $i++;



                //else echo $imdb_id.'found<br>';


                if ($i >= 20) {
                    break;
                }
            }

            if ($i > 5) {
                $link = '/search/type_tv';
                $title = 'Load more Streaming';
                $content_result['result'][] = array('link' => $link, 'title' => $title, 'genre' => 'load_more', 'poster_link_small' => '', 'poster_link_big' => '', 'content_pro' => '');
                $i++;
            }
            $content_result['count'] = $i;


            return $content_result;
        } else {
            $content_result['count'] = 0;
            $content_result['message'] = 'no result';
            $content_string = json_encode($content_result);
            return $content_string;
        }
    }

}

function tv_scroll() {
    $content_result = TV_Scroll::show_scroll();
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
/*if (function_exists('wp_custom_cache')) {

    $cache = wp_custom_cache('tv_scroll', 'fastcache', 86400);
} else {*/
    $cache = tv_scroll();
/*}*/

echo $cache;
