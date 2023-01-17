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

$count = 1;
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

// Check server load
!class_exists('CPULOAD') ? include ABSPATH . "service/cpu_load.php" : '';
$load = CPULOAD::check_load();
if ($load['loaded']) {
    if ($debug) {
        p_r($load);
    }
    exit();
}

if (!class_exists('CriticMaticTrans')) {
    //Critic feeds    
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMaticTrans.php' );
}

$cm = new CriticMatic();
$cmt = new CriticMaticTrans($cm);

$cron_name = 'find_transcriptions';
if ($cm->cron_already_run($cron_name, 10, $debug, $force)) {
    exit();
}
$cm->register_cron($cron_name);
$cmt->find_transcriptions_youtube($count, $debug, $force);
$cm->unregister_cron($cron_name);