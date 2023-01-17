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
// Cron type
$cron_type = 1;
if ($_GET['t']) {
    $cron_type = (int) $_GET['t'];
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

if (!class_exists('CriticParser')) {
    //Critic feeds    
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
}

$cm = new CriticMatic();



$cron_name = 'critic_parser_' . $cron_type;
if ($cm->cron_already_run($cron_name, 10, $debug, $force)) {
    exit();
}

$cm->register_cron($cron_name);

$cp = new CriticParser($cm);
$cp->run_cron($cron_type, $force, $debug);

$cm->unregister_cron($cron_name);