<?php

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

$force = false;
if ($_GET['force']) {
    $force = true;
}

$count = 100;
if ($_GET['c']) {
    $count = (int) $_GET['c'];
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
$cron_name = 'movie_title_weight_cron';
if ($cm->cron_already_run($cron_name, 10, $debug, $force)) {
    exit();
}

$cm->register_cron($cron_name);


$mw = $cm->get_mw();
$mw->run_cron($count, $debug, $force);


$cm->unregister_cron($cron_name);