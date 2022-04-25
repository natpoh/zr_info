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

$count = 10;
if ($_GET['c']) {
    $count = (int) $_GET['c'];
}

$acount = 100;
if ($_GET['ac']) {
    $acount = (int) $_GET['ac'];
}

$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$force = false;
if ($_GET['force']) {
    $force = true;
}
if ($_GET['directors']) {

    $cm = new CriticMatic();
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTransit.php' );

    $cr = new CriticTransit($cm);
    $cr->transit_directors($count, $debug);
    return;
}


$cm = new CriticMatic();


require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTransit.php' );
$cr = new CriticTransit($cm);

// Create actors slug
$cr->actor_slug($acount, $debug);

//One time transit data
$cr->transit_genres($count, $debug);


$cr->transit_actors($count, $debug);

//One time transit data
$cr->transit_countries($count, $debug);

// One time task. Complite
//$cr->transit_providers();