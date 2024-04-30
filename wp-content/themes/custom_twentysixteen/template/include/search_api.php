<?php
error_reporting('E_ALL');
ini_set('display_errors', 'On');

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
!class_exists('CreateTsumbs') ? include ABSPATH . "analysis/include/create_tsumbs.php" : '';


class Search_api{

    public static function prepare_movies($id, $content_result, $slug = '', $time = 0) {

        if ($content_result['result'][$id]) {
            return $content_result;
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

        if (!$slug)
            $slug = strtolower($type);

        if ($slug == 'movie')
            $slug = 'movies';

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

        $order = count($content_result['result']);

        if ($release > $time) {
            $content_result['result'][$rows->id] = array(
                'link' => '/' . $slug . '/' . $post_name,
                'title' => $title,
                'genre' => $rows->genre,
                'poster_link_small' => $array_tsumb[0],
                'poster_link_big' => $array_tsumb[1],
                'type' => $slug,
                'order'=>$order
            );
        }




        return $content_result;
    }
    public static function get_search_by_url($url,$count=20)
    {
        global $cfront;
        $content_result = [];

        if (!$cfront) {
            if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
            }

            if (!class_exists('CriticFront')) {
                require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
            }
        }

        $last_req = $_SERVER['REQUEST_URI'];
        $_SERVER['REQUEST_URI'] = $url;


        ///$data = $cfront->get_scroll('audience_scroll', $movie_id, $vote, $search);


        $search_front = new CriticFront();
        $search_front->init_search_filters();
        $array_data = $search_front->find_results(0, array(), false, true);

        $_SERVER['REQUEST_URI'] = $last_req;

        //   var_dump($array_data);
        if ($array_data["critics"] ["list"]) {
            global $cfront;
            if (!$cfront) {
                if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                }

                if (!class_exists('CriticFront')) {
                    require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
                }
                $cfront = new CriticFront();
            }

            $items =[];
            foreach ($array_data["critics"] ["list"] as $i => $v) {
                $array_critics[$v->id] = strtotime($v->date_add);
                $item_theme = $cfront->get_top_movie_critic($v->id, $v->date_add);
                $items[]=$item_theme;

                if (count($items) >= $count) {
                    break;
                }
            }

            $items_result = $cfront->get_review_scroll_data(0, [],$items,$url);


            return [[],$items_result];
        }

        ////movies
        else  if ($array_data["movies"] ["list"]) {
            foreach ($array_data["movies"] ["list"] as $i => $v) {

                $array_movies[] = $v->id;

                $content_result = self::prepare_movies($v->id, $content_result);

                if (count($content_result['result']) >= $count) {
                    break;
                }


            }

        }

        return  [$array_movies,$content_result];
    }
}