<?php

if (function_exists('get_template_directory')) {
    if (!function_exists('get_poster_tsumb')) {
        require(get_template_directory() . '/template/include/create_tsumb.php');
    }


    require(get_template_directory() . "/template/include/pccf_filter.php");
    if (!function_exists('lib_feed_pro')) {
        require(get_template_directory() . '/template/include/lib_feed_pro.php');
    }
    require(get_template_directory() . '/template/plugins/spoiler_plugin.php');
    require (get_template_directory() . '/template/include/get_full_staff.php');
} else {
    if (!function_exists('get_poster_tsumb')) {
        require('include/create_tsumb.php');
    }

    require("include/pccf_filter.php");
    if (!function_exists('lib_feed_pro')) {
        require('include/lib_feed_pro.php');
    }
    require('plugins/spoiler_plugin.php');
    require ('include/get_full_staff.php');
}

function get_content_review() {
    global $review_type, $review_id, $movie_id;


    $result_movie = '';

    if ($movie_id) {

        $result_movie = get_small_movie($movie_id);
    }

    switch ($review_type) {
        case 'p':
            $result_rw = get_feed_pro_templ($review_id, '', '', '', '', 1);

            break;

        case 's':
            $result_rw = get_feed_pro_templ($review_id, '', '', '', 1, 1);

            break;
        case 'a':
            $result_rw = get_audience_templ('', '', $review_id, '', 1);
            break;
    }

    if ($result_movie && $result_rw) {

        $content = '<div class="full_review">' . $result_movie . $result_rw . '</div><div id="disqus_thread"></div>';

        if (isset($_GET['id'])) {
            $key = $_GET['id'];
            $link = $key;
        } else {
            $key = WP_SITEURL . $_SERVER['REQUEST_URI'];
            $link = $key;
        }



        ///try pet pgind from db
        $sql ="SELECT `idn` FROM `cache_disqus_treheads` WHERE `type`='critics' and `post_id` ='{$review_id}' limit 1";
        $r1 = Pdo_an::db_fetch_row($sql);
        if ($r1)
        {
            $pg_idnt =  $r1->idn;
        }

        if (!$pg_idnt)
        {
            $pg_idnt = $review_id . ' ' . $link;
        }





        if (function_exists('get_the_title')) {

            $title = get_the_title($review_id);
        } else if (function_exists('get_post_data')) {

            $title = get_post_data($review_id, 'post_title', 'ID', 'posts');
        }


        $result = array('page_url' => $link, 'page_identifier' => $pg_idnt, 'title' => $title, 'content' => $content);

        //// $content = json_encode($result);

        return $result;
    }
}
