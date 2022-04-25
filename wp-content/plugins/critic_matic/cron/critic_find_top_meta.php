<?php
/*
 * +Find top movie in meta for critics
 * +One time task
 * 
 * 
 */


require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
nocache_headers();

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


$count = 100;
if ($_GET['c']) {
    $count = (int) $_GET['c'];
}

// One time transit data
require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTransit.php' );
$cm = new CriticMatic();
$cr = new CriticTransit($cm);

//$cr->transit_an_meta($count, $debug);

// Transit post slug
$cr->transit_an_post_slug($count, $debug);

// Unused
//$cm->find_top_rating_meta($count, $debug);

//$cm->find_top_rating_no_meta($count, $debug);
