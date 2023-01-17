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

// Check server load
!class_exists('CPULOAD') ? include ABSPATH . "service/cpu_load.php" : '';
$load = CPULOAD::check_load();
if ($load['loaded']) {
    if ($debug) {
        p_r($load);
    }
    exit();
}

$cm = new CriticMatic();
$cmt = new CriticMaticTrans($cm);


$cron_name = 'update_posts_transcription_' . $t;
if ($cm->cron_already_run($cron_name, 10, $debug, $force)) {
    exit();
}
$cm->register_cron($cron_name);

if ($t == 1) {
    $cmt->update_posts_transcription($count, $debug, $force);
} else if ($t == 2) {
    // One time task unused
    $cmt->update_youtube_urls($count, $debug, $force);
}

$cm->unregister_cron($cron_name);

