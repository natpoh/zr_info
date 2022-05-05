<?php

ini_set('display_errors', 0);
ini_set('error_reporting', E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

///try find movies
include($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/custom_twentysixteen/template/movie_single_template.php');
include($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/custom_twentysixteen/template/include/pccf_filter.php');

function format_interval($interval, $granularity = 2) {
    $units = array('1 year|@count years' => 31536000, '1 week|@count weeks' => 604800, '1 day|@count days' => 86400, '1 hour|@count hours' => 3600, '1 min|@count min' => 60, '1 sec|@count sec' => 1);
    $output = '';
    foreach ($units as $key => $value) {
        $key = explode('|', $key);
        if ($interval >= $value) {
            $floor = floor($interval / $value);
            $output .= ($output ? ' ' : '') . ($floor == 1 ? $key[0] : str_replace('@count', $floor, $key[1]));
            $interval %= $value;
            $granularity--;
        }

        if ($granularity == 0) {
            break;
        }
    }

    return $output ? $output : '0 sec';
}

function add_template($id, $trehead_links, $parents_array, $comment_array_ids, $array_data) {
    $inner_content = '';

    //  var_dump($array_data);

    $comment = $array_data[$id];



    if (in_array($id, $parents_array)) {
        foreach ($parents_array as $pi => $pv) {
            if ($pv == $id) {

                $inner_content .= add_template($pi, $trehead_links, $parents_array, $comment_array_ids, $array_data);
            }
        }
    }



    $trehead = $comment->thread;

    //echo $trehead.'<br>';
    $link = $trehead_links[$trehead];

    $media = $comment->media;
    $message = $comment->message;
    $reg_v = '#<a.+title="([^"]+)"[^\<]+\<\/a\>#Uis';

    $array_replace = [];

    if (preg_match_all($reg_v, $message, $mach)) {
        ///var_dump($mach);
        foreach ($mach[0] as $i => $v) {
            $array_replace[$mach[1][$i]] = $v;
        }
    }



    if ($media) {
        foreach ($media as $mdata) {

            if ($mdata->html && $mdata->mediaType == 3) {
                //  $content.=  $mdata->html;
            }
            if ($mdata->url && ($mdata->mediaType == 1 || $mdata->mediaType == 2)) {

                if ($array_replace[$mdata->url]) {
                    $content_data = '<img  alt="' . $mdata->title . '" src="' . $mdata->url . '"/>';
                    $message = str_replace($array_replace[$mdata->url], $content_data, $message);
                }
            }
        }
    }











    if (function_exists('pccf_filter')) {
        $message = pccf_filter($message);
    }

    $content = '<p>' . $message . '</p>';


    $actorstitle = $comment->author->name;
    $addtime = $comment->createdAt;
    $ptime = strtotime($addtime);


    if (function_exists('pccf_filter')) {
        $actorstitle = pccf_filter($actorstitle);
    }


    $addtime_title = date('M', $ptime) . ' ' . date('jS Y', $ptime);
    $addtime = format_interval(time() - $ptime, 1) . ' ago';


    $img = $comment->author->avatar->cache;


    $finalResults = '<div class="disqus_main_block">
    <div class="disqus_block">
        <div class="disqus_autor" ><a target="_blank" href="' . $comment->author->profileUrl . '"><img class="disqus_autor_image" src="' . $img . '" /></a></div>
         <div class="disqus_message">
            <div class="disqus_autor_name">
            <a target="_blank" href="' . $comment->author->profileUrl . '">' . $actorstitle . '</a>
            <a  class="disqus_addtime" href="' . $link . '" title="' . $addtime_title . '">' . $addtime . '</a>
            </div>
            ' . $content . '
            <div class="disqus_content_bottom"><a  href="' . $link . '#reply-' . $comment->id . '" class="disqus_reply">Reply</a><a  href="' . $link . '" class="disqus_view">View</a></div>
        </div>
         
    </div>
        ' . $inner_content . '</div>';

    /// $code = base_convert($id, 10, 36);
    //   $finalResults = '<div class="a_msg_container"><iframe src="https://embed.disqus.com/p/' . $code . '" style="width: 100%; min-height: 250px"  seamless="seamless" scrolling="no" frameborder="0" allowtransparency="true"></iframe>' . $inner_content . '</div>';


    return $finalResults;
}

function check_movie($id, $link) {
    $sql = "SELECT post_name FROM `data_movie_imdb` where id =" . intval($id);


    $r = Pdo_an::db_fetch_row($sql);
    if ($r) {
        $name = $r->post_name;
        if (strstr($link, $name)) {
            return $id;
        }
    }
    return '';
}

function get_last_disqus_comment($limit = 10, $cursor = '') {

    $key = 'Zt8xSiTUeoQuBLJ060aEdofTRBzQRTq6uMkn5Xwm5GsNZzTyatx37i9valgksE5B'; // TODO replace with your Disqus secret key from http://disqus.com/api/applications/
    $forum = 'hollywoodstfu'; // Disqus shortname
    //$limit = '10'; // The number of comments you want to show
    $thread = '6846668'; // Same as your disqus_identifier
    $url = 'https://disqus.com/api/3.0/forums/listPosts.json';
//$url='https://disqus.com/api/3.0/posts/list.json';
//&thread='.$thread.

    if ($cursor) {
        $cursor = '&cursor=' . $cursor;
    }

    $endpoint = $url . '?api_secret=' . $key . '&forum=' . $forum . '&limit=' . $limit . $cursor;


    ///echo $endpoint;
///echo $endpoint.'&cursor=1642247711105329:0:0';
//$endpoint = 'http://disqus.com/';
// Get the results
    $session = curl_init($endpoint);
    $ch = curl_init();
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($session);
    curl_close($session);

// decode the json data to make it easier to parse with php
    $results = json_decode($data);



    if (isset($results->cursor)) {
        if (isset($results->cursor->next)) {
            $cursor = $results->cursor->next;
        }
    }


// parse the desired JSON data into HTML for use on your site
    $comments = $results->response;
    $array_trehead = [];
    $trehead_request = '';
    $parents_array = [];
    $comment_array_ids = [];


    foreach ($comments as $comment) {
        $trehead = $comment->thread;
        $parent = $comment->parent;
        $id = $comment->id;

        $comment_array_ids[] = $id;

        if ($parent) {
            $parents_array[$comment->id] = $parent;
        }


        if (!in_array($trehead, $array_trehead)) {
            $array_trehead[] = $trehead;

            $trehead_request .= 'thread=' . $trehead . '&';
        }
    }

    if (!$trehead_request)
        return;

    $endpoint = 'https://disqus.com/api/3.0/threads/list.json?' . $trehead_request . 'api_secret=' . $key . '&forum=' . $forum . '&limit=' . $limit;

    $session = curl_init($endpoint);
    $ch = curl_init();
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($session);
    curl_close($session);

// decode the json data to make it easier to parse with php
    $results = json_decode($data);
    $trehead_links = [];
    $trehead_data = $results->response;
    foreach ($trehead_data as $tdata) {
        $trehead_links[$tdata->id] = $tdata->link;
        $array_trehead_id[$tdata->id] = $tdata->identifiers;
    }

///get parents

    $array_sort = [];
    $array_data = [];
    $i = 0;
    foreach ($comments as $comment) {

        $trehead = $comment->thread;
        $parent = $comment->parent;
        $id = $comment->id;

        $array_sort[$trehead][$id] = 1;
        $array_data[$id] = $comment;
        $i++;
    }


    $finalResults_big = '';


    foreach ($array_sort as $trehead_id => $dc) {
        $finalResults = '';

        foreach ($dc as $id => $comment) {



            if (isset($parents_array[$id])) {
                $enable_parents = $parents_array[$id];



                if ($enable_parents && $comment_array_ids) {
                    if (in_array($parents_array[$id], $comment_array_ids)) {
                        continue;
                    }
                }
            }

            $finalResults .= add_template($id, $trehead_links, $parents_array, $comment_array_ids, $array_data);
        }

        ///get template
        //var_dump($array_trehead_id[$trehead_id]);


        $link = $array_trehead_id[$trehead_id][0];


        $reg = '#([0-9]+) (.*)#';
        $movie_block = '';
        if (!preg_match($reg, $link, $mach)) {

            if (isset($array_trehead_id[$trehead_id][1])) {
                $link = $array_trehead_id[$trehead_id][1];
            }
        }

        /// echo $link.'<br>';

        if (preg_match($reg, $link, $mach)) {
            $link_id = $mach[1];
            $link_l = $mach[2];

            if ($link_l && $link_l) {

                if (!strstr($link, '/critics/')) {
                    ////movie

                    if (strstr($link, '/movies/') || strstr($link, '/tvseries/')) {
                        $link_id = check_movie($link_id, $link_l);

                        if ($link_id) {
                            $movie_block = template_single_movie_small($link_id, '', $link_l, 1);
                        }
                    } else {
                        $new_link = $link_l;
                        if (strstr($link_l, 'https://' . $_SERVER['HTTP_HOST'] . '/')) {
                            $new_link = str_replace('https://' . $_SERVER['HTTP_HOST'] . '/', '', $link_l);
                        }

                        $movie_block = '<div class="review_block"><span class="review_block_title">Page: </span></span><a href="' . $link_l . '">' . $new_link . '</a></div>';
                    }
                } else {

                    //get critic data

                    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                    }

                    if (!class_exists('CriticFront')) {
                        require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
                    }


                    global $cfront;
                    $cfront = new CriticFront();

                    $critic_data = $cfront->cm->get_post($link_id);
                    $movie_id = $critic_data->top_movie;
                    $critic_type = $critic_data->type;
                    /*
                      Author type
                      0 => 'Staff',
                      1 => 'Pro',
                      2 => 'Audience'
                     */
                    $array_type = array(0 => 'Staff',
                        1 => 'Pro',
                        2 => 'Audience'
                    );


                    $movie_block = '';
                    if ($link_id) {
                        $movie_block = template_single_movie_small($movie_id, '', $link_l, 1);
                    }


                    $movie_block = '<div class="review_block review_' . $array_type[$critic_type] . '"><div class="review_block_title">' . $array_type[$critic_type] . ' review of </div>' . $movie_block . '</div>';
                }
            } else {
                //get critic data
                echo 'not found ' . $link_id . ' - ' . $link;
            }
        }


        $finalResults_big .= '<div class="big_block_comment">' . $movie_block . '<div>' . $finalResults . '</div></div>';
    }


    return $finalResults_big . '<div style="display: none" class="next_cursor">' . $cursor . '</div>';
}

//$cache =  get_last_disqus_comment(30);
//echo $cache;
//return;
!function_exists('wp_custom_cache') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php" : '';






if (function_exists('wp_custom_cache')) {

    if (isset($_GET['count'])) {
        $cursor = '';
        if (isset($_GET['cursor'])) {
            $cursor = $_GET['cursor'];
        }

        $cache = get_last_disqus_comment(intval($_GET['count']), $cursor);
        echo $cache;
        return;
    }


    $cache = wp_custom_cache('get_last_disqus_comment', 'fastcache', 1800); ///30 min
} else {
    $cache = get_last_disqus_comment();
}
echo $cache;
?>