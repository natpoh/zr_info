<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
set_time_limit(0);
/*
 * Transit critcs wp_posts to critic_matic posts
 */

if (!class_exists('CriticMatic')) {
    return;
}

$p = 'D_23_2D0FS0-vbb';

if ($_GET['p'] != $p) {
    return;
}

$count = 100;
if ($_GET['c']) {
    $count = (int) $_GET['c'];
}


$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$force = false;
if ($_GET['force']) {
    $force = true;
}

$t = 1;
if ($_GET['t']) {
    $t = (int) $_GET['t'];
}

$cm = new CriticMatic();

//One time transit data
require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTransit.php' );
$cr = new CriticTransit($cm);

//$ids = array(68852,68853);
if ($t == 1) {
    $cr->movie_title_slugs($count, $debug, $force, $ids);
} else {
    $cr->movie_set_new_slugs($count, $debug, $force);
}
//$cr->movie_duble_slugs($count,$debug,$force);