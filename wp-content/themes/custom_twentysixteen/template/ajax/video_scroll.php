<?php

error_reporting('E_ALL');
ini_set('display_errors', 'On');

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';

!class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';

!class_exists('CreateTsumbs') ? include ABSPATH . "analysis/include/create_tsumbs.php" : '';


global $site_url;
if (!$site_url)
    $site_url = WP_SITEURL. '/';




if (!function_exists('video_scroll')) {

    function video_scroll() {
        require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/video_item_template.php');


        error_reporting('E_ALL');
//get last video
///country
        $array_country = array("United States", "United Kingdom", "France", "Canada", "Germany", "Australia", "Italy", "Spain");
        $array_country_ignored = array("India");

        $array_movies=[];
//        $sql = "SELECT * FROM `options` where id = 13";
//        $rows = Pdo_an::db_fetch_row($sql);

//        $array_movies = $rows->val;
//        if ($array_movies) {
//            $array_movies = json_decode($array_movies, 1);
//            arsort($array_movies);
//        } else

           // {

        $starttime = time();
            $date_current = date('Y-m-d', $starttime);
            $date_main = date('Y-m-d', strtotime('-1 year', $starttime));
            $sql = "SELECT * FROM `data_movie_imdb` WHERE `release`  >=  '" . $date_main . "' and `release`  <=  '" . $date_current . "' and `type`= 'Movie' order by `release` desc LIMIT 50 ";
            $rows = Pdo_an::db_results_array($sql);
            foreach ($rows as $r) {
                $movie_id = $r['id'];
                $array_movies[$movie_id] = strtotime($r['release']);
            }
            arsort($array_movies);
       //}

        $i = 0;

        $content_result = [];
        $array_movies_rank = [];
        if (is_array($array_movies)) {
            $array_tsumb = [];
            $cfront = '';
            foreach ($array_movies as $id => $time) {


                $sql = "select * from data_movie_imdb where id = " . $id . " limit 1";
                $rows = Pdo_an::db_fetch_row($sql);


                $country = $rows->country;
                $checked_country = 2;

                foreach ($array_country as $check_country) {
                    if (strstr($country, $check_country)) {
                        $checked_country = 1;
                        break;
                    }
                }

                foreach ($array_country_ignored as $check_country) {
                    if (strstr($country, $check_country)) {
                        $checked_country = 3;
                        break;
                    }
                }

                $array_movies_rank[$checked_country][$id] = $rows;
            }
            ksort($array_movies_rank);


            foreach ($array_movies_rank as $checked_country => $data) {
                if ($checked_country == 3) {
                    continue;
                }
                foreach ($data as $id => $rows) {
                    $release=0;
                    $post_name = $rows->post_name;
                    $title = $rows->title;
                    $type = $rows->type;
                    $release = $rows->release;
                    if ($release)
                    {
                        $release = strtotime($release);
                    }

                    $slug = 'movies';
                    if ($type == 'TVseries') {
                        $slug = 'tvseries';
                    }

                    try {
                        $array_tsumb = CreateTsumbs::get_poster_tsumb_fast($id, array([220, 330], [440, 660]));
                    } catch (Exception $ex) {
                        
                    }
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
                    ///check poster

                    $poster = CreateTsumbs::get_img_from_db($rows->id);

                    global $site_url;

                    if ($poster) {
                        $content_result['result'][$rows->id] = array(
                            'link' => $site_url . $slug . '/' . $post_name,
                            'title' => $title,
                            'genre' => $rows->genre,
                            'poster_link_small' => $array_tsumb[0],
                            'poster_link_big' => $array_tsumb[1],
                            'type' => $slug,
                            'release'=>$release
                        );
                        $i++;
                    }




                    //else echo $imdb_id.'found<br>';


                    if ($i >= 20) {
                        break;
                    }
                }
                if ($i >= 20) {
                    break;
                }
            }
            $content_result['count'] = $i;
            $content_result['tmpl'] = $video_template;
        } else {
            $content_result['count'] = 0;
            $content_result['message'] = 'no result';
        }

        if ($content_result['result']) {
            $RWT_RATING = new RWT_RATING;
            $content_result['rating'] = $RWT_RATING->get_rating_data($content_result['result']);
        }
        $content_string = json_encode($content_result);
        return $content_string;
    }

}

// if (LOCAL_CACHE == 0) {
echo video_scroll();
//    return;
// }

/*
if (!function_exists('wp_custom_cache')) {
    require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php');
}

$cache = wp_custom_cache('video_scroll', 'fastcache', 3600);

echo $cache;
*/