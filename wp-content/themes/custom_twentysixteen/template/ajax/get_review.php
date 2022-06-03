<?php

//ini_set('error_reporting', E_ERROR);
//ini_set('display_errors','On');

$wp_core = false;
if (isset($_GET['wp_core']) && $_GET['wp_core'] == 1) {
    $wp_core = true;
}

if ($wp_core) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

/*
 * New api after 23.07.2021
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
}

if (!class_exists('CriticFront')) {
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}



$cfront = new CriticFront();

if (isset($_GET['id'])) {
    if (preg_match('/critics\/([0-9]+)-/', $_GET['id'], $match)) {
        $critic_id = $match[1];
        $title = '';
        $content = '';
        $link = '';

        $post = $cfront->cm->get_post($critic_id);
        if ($post) {

            $top_movie = $post->top_movie;
            //$top_movie = $cfront->cm->get_top_movie($post->id);
            // Get external movie meta
            if (preg_match('/meta=([0-9]+)/', $_GET['id'], $match2)) {
                $get_meta = $match2[1];
                // Validate meta
                $valid_meta = $cfront->cm->get_movies_data($post->id, $get_meta);
                if ($valid_meta) {
                    $top_movie = $get_meta;
                }
            }
            $critic_content = $cfront->cache_single_critic_content($post->id, $top_movie, $post->date_add);
            $link = $cfront->get_critic_url($post);
        }

        $content_templ = '<div class="full_review">' . $critic_content . '</div><div id="disqus_thread"></div>';

        // Emotions
        $emotions = $cfront->ce->get_emotions($post->id, true);

        ///try pet pgind from db
        $sql ="SELECT `idn` FROM `cache_disqus_treheads` WHERE `type`='critics' and `post_id` ='".$critic_id."' limit 1";
        $r1 = Pdo_an::db_fetch_row($sql);
        if ($r1)
        {
            $pg_idnt =  $r1->idn;
        }
        if (!$pg_idnt)
        {
            $pg_idnt = $critic_id.' '.$link;
        }


        $result = array(
            'page_url' => $link,
            'page_identifier' => $pg_idnt,
            'title' => $title,
            'content' => $content_templ,
            'emotions' => $emotions
        );
        print json_encode($result);
    }
}

exit();
