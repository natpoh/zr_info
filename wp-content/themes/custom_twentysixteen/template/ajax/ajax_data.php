<?php

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}

error_reporting(E_ERROR);

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//ini_set('display_errors', 'On');
//if (isset($_GET['debug']))
//{
//error_reporting(E_ALL);
//ini_set('display_errors', 'On');
//define('WP_DEBUG', true);
//define('WP_DEBUG_DISPLAY', 1);
//define('WP_DEBUG_LOG', 1);
//}
include (ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/custom_connect.php");
include (ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/create_tsumb.php");
if (!function_exists('template_single_movie')) {
    include(ABSPATH . "wp-content/themes/custom_twentysixteen/template/movie_single_template.php");
}

//require ('custom_cahe.php');


global $pdo;
global $table_prefix;

if (!function_exists('wph_cut_by_words')) {

    function wph_cut_by_words($maxlen, $text) {
        $len = (mb_strlen($text) > $maxlen) ? mb_strripos(mb_substr($text, 0, $maxlen), ',') : $maxlen;
        $cutStr = mb_substr($text, 0, $len);
        $temp = $cutStr;
        return $temp;
    }

}

/*
 * New api after 23.07.2021
 */

global $cfront, $cm_new_api;


if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
}

if (!class_exists('CriticFront')) {
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}

$cfront = new CriticFront();
if ($cfront->new_api) {
    $cm_new_api = true;
}


if ($_POST['action'] == 'ajax_search') {

    if ($_POST['type'] == 'movie') {
        //Quick movie filter

        $keyword = isset($_POST['keyword']) ? strip_tags(stripslashes($_POST['keyword'])) : '';        
        $limit = 6;

        if (isset($_POST['nolinks'])) {
            if ($_POST['nolinks'] == 1) {
                $no_links = 1;
                $limit = 20;
            }
        }

        $results = $cfront->cs->front_search_any_movies_by_title_an($cfront->cm->escape($keyword), $limit);
        if (sizeof($results)) {
            foreach ($results as $item) {
                $content .= '<li>' . $cfront->template_single_movie_small_an($item, $no_links) . '</li>';
            }
        }

        if ($content) {
            $content = '<ul>' . $content . '</ul>';
            // $content = '<p class="advanced_search_shead" > Movies</p >'.$content;
        }

        if ($content)
            $content = '<p class="advanced_search_head">Maybe you were looking for...<span class="advanced_search_head_close"></span></p>' . $content;

        echo $content;

        return;
    }
    else if ($_POST['type'] == 'grid') {
        // Default page
        if ($cm_new_api) {
            
        } else {

            include(ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/searchfilters.php");

            if (!function_exists('template_single_movie')) {
                include(ABSPATH . "wp-content/themes/custom_twentysixteen/template/movie_single_template.php");
            }

            if (isset($_POST['filters'])) {
                include (ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/template_critics_search.php");
            }

            //echo $sql;
            // var_dump($sql);

            $q = $pdo->prepare("SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
            $q->execute();

            //echo $sql;

            $q = $pdo->prepare($sql);
            $q->execute([$keyword_search]);
            $q->setFetchMode(PDO::FETCH_ASSOC);
            $content = '';

            !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';

            while ($r = $q->fetch()) {

                ///  var_dump($r);

                $array_result[$r['ID']]['post_title'] = $r['post_title'];
                $array_result[$r['ID']]['post_name'] = $r['post_name'];
                $array_result[$r['ID']]['pid_data'][$r['post_id']] = $r;
            }

//var_dump($array_result);


            foreach ($array_result as $id => $val) {
                /// $meta = get_post_meta_custom($id);
                $title = $val['post_title'];
                $name = $val['post_name'];




                if (isset($filters) && $filters->critics) {

                    //var_dump($filters);

                    $chead = template_single_movie_small($id, $title, $name);


                    $pid_array = $val['pid_data'];

                    foreach ($pid_array as $pid => $data) {
                        $cbody = get_template_critics_custom(array($pid => $data), $id);
                    }

                    if ($cbody) {
                        $content .= '<div class="full_review">' . $chead . $cbody . '</div>';
                        //echo $content;
                    }
                } else {
                    if (function_exists('template_single_movie')) {
                        ob_start();
                        template_single_movie($id, $title, $name);
                        $content .= ob_get_contents();
                        ob_clean();
                    }
                }

                $content_result[$id] = $id;
            }
            if (isset($filters) && $filters->critics) {
                echo '<div class="flex_content_block">' . $content . '</div>';
            } else {
                echo '<div class="flex_movies_block">' . $content . '</div>';


                $RWT_RATING = new RWT_RATING;
                $RWT_RATING->show_rating_script($content_result);
            }


            if (!$array_result) {
                echo '<div class="grid-items " id=""><div class="item">No Post found</div></div>';
            }
        }
    } else if ($_POST['type'] == 'director') {
        $actors = $_POST['search'];

        $actors = strip_tags($actors);
        $actors = strtolower($actors);
        ///echo $actors;

        $actors_search = '%' . $actors . '%';

        if ($actors) {
            $sql = "SELECT meta_value FROM " . $table_prefix . "postmeta WHERE meta_key ='_wpmoly_movie_director' and LOWER(meta_value) like ? and meta_value!='' limit 100";

            $q = $pdo->prepare($sql);
            $q->execute([$actors_search]);
            $q->setFetchMode(PDO::FETCH_ASSOC);

            while ($r = $q->fetch()) {
                $castval = $r['meta_value'];

                if (strstr($castval, ',')) {
                    $arcast = explode(',', $castval);
                } else {
                    $arcast[0] = $castval;
                }

                foreach ($arcast as $cast) {
                    if ($cast && $actors) {
                        if (strstr(strtolower($cast), $actors)) {
                            $cast = trim($cast);
                            $cast = $Content = preg_replace("/&#?[a-z0-9]+;/i", "", $cast);

                            $arraydatacast[$cast] = $cast;
                        }
                    }
                }
            }

            if (is_array($arraydatacast)) {
                foreach ($arraydatacast as $cast) {
                    $curdata["id"] = $cast;
                    $curdata["text"] = $cast;

                    $resdata["results"][] = $curdata;
                }
            }

            if (!$resdata) {
                $resdata["results"][0] = '';
            }

            if ($resdata) {
                //  $resdata["results"]["pagination"]["more"]=true;
                echo json_encode($resdata);
            }
        }
    } else if ($_POST['type'] == 'cast') {
        $actors = $_POST['search'];

        $actors = strip_tags($actors);
        $actors = strtolower($actors);
        ///echo $actors;

        $actors_search = '%' . $actors . '%';

        if ($actors) {
            $sql = "SELECT meta_value FROM " . $table_prefix . "postmeta WHERE meta_key ='_wpmoly_movie_cast' and LOWER(meta_value) like ? and meta_value!='' limit 100";

            $q = $pdo->prepare($sql);
            $q->execute([$actors_search]);
            $q->setFetchMode(PDO::FETCH_ASSOC);

            while ($r = $q->fetch()) {
                $castval = $r['meta_value'];

                if (strstr($castval, ',')) {
                    $arcast = explode(',', $castval);
                } else {
                    $arcast[0] = $castval;
                }

                foreach ($arcast as $cast) {
                    if ($cast && $actors) {
                        if (strstr(strtolower($cast), $actors)) {
                            $cast = trim($cast);
                            $cast = $Content = preg_replace("/&#?[a-z0-9]+;/i", "", $cast);

                            $arraydatacast[$cast] = $cast;
                        }
                    }
                }
            }

            if (is_array($arraydatacast)) {
                foreach ($arraydatacast as $cast) {
                    $curdata["id"] = $cast;
                    $curdata["text"] = $cast;

                    $resdata["results"][] = $curdata;
                }
            }

            if (!$resdata) {
                $resdata["results"][0] = '';
            }

            if ($resdata) {
                //  $resdata["results"]["pagination"]["more"]=true;
                echo json_encode($resdata);
            }
        }
    }


    /////pagination


    if ($sqlcount) {
        if (function_exists('custom_wprss_pagination')) {

            $q = $pdo->prepare($sqlcount);
            $q->execute([$keyword_search]);
            $q->setFetchMode(PDO::FETCH_ASSOC);

            $count = $q->rowCount();

            echo custom_wprss_pagination($count, $s_posts_per_page, 4, $s_page);
        }
    }
}