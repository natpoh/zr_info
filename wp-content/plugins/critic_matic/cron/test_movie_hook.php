<?php

/*
 * /wp-content/plugins/critic_matic/cron/test_movie_hook.php?p=8ggD_23sdf_DSF&debug=1&mid=11887
 * /wp-content/plugins/critic_matic/cron/test_movie_hook.php?p=8ggD_23sdf_DSF&debug=1&aid=1082477
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}

if (!class_exists('CriticMatic')) {
    return;
}

$p = '8ggD_23sdf_DSF';

if ($_GET['p'] != $p) {
    return;
}

$debug = false;
if ($_GET['debug']) {
    $debug = true;
}


$cm = new CriticMatic();
$cron_name = 'movie_hook_cron';
if ($cm->cron_already_run($cron_name, 10, $debug, $force)) {
    exit();
}

$mid = $_GET['mid'] ? (int) $_GET['mid'] : 0;
$aid = $_GET['aid'] ? (int) $_GET['aid'] : 0;

$ma = $cm->get_ma();

if ($mid) {
    // Update movie meta
    $ma->hook_add_movies($mid, $debug);
} else if ($aid) {
    // Update all movies for actor
    $ma->hook_actors_movie($aid, $debug);
}
