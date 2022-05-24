<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
nocache_headers();

if (!class_exists('CriticMatic')) {
    return;
}

$p = '8ggD_23_2D0DSF-F';

if ($_GET['p'] != $p) {
    return;
}

$count = 10;
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

if (!class_exists('CriticMaticTrans')) {
    //Critic feeds    
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMaticTrans.php' );
}

$cm = new CriticMatic();
$cmt = new CriticMaticTrans($cm);

if ($t == 1) {
    $cmt->update_posts_transcription($count, $debug, $force);
} else if ($t == 2) {
    $cmt->update_youtube_urls($count, $debug, $force);
}


