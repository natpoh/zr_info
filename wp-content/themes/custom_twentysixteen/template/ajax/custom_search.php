<?php

error_reporting('E_ALL');
ini_set('display_errors', 'On');

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

!class_exists('Search_api') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/search_api.php" : '';
!class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';

function get_s_data($url)
{
    if (!strstr($url,'/search/'))
    {
        $url=   '/search/'.$url;
    }

    global $cfront;

    if (!$cfront) {
        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        }

        if (!class_exists('CriticFront')) {
            require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
        }
    }


    [$array_movies,$content_result] = Search_api::get_search_by_url($url);
    if (count($content_result['result'])) {


        $link = $url;
        $title = 'Load more';

        $content_result['result'][] = array('link' => $link, 'title' => $title, 'genre' => 'load_more', 'poster_link_small' => '', 'poster_link_big' => '', 'content_pro' => '');
        $content_result['count'] = count($content_result['result']);
        $content_result['mids'] = $array_movies;
        return $content_result;
    } else {
        $content_result['count'] = 0;
        $content_result['message'] = 'no result';
        return $content_result;
    }
}

if (isset($_GET['id']))
{
    $url = $_GET['id'];

    $content_result = get_s_data($url);



    global $video_template;
    include(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/video_item_template.php');
    $content_result['tmpl'] = $video_template;

    if ($content_result['result']) {
        $RWT_RATING = new RWT_RATING;
        $content_result['rating'] = $RWT_RATING->get_rating_data($content_result['result']);
    }
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


    $user = $cfront->cm->get_current_user();

    if ($user->ID) {

        $content_result = $cfront->append_watch_list_scroll_data($content_result);

    }

echo json_encode($content_result);


}