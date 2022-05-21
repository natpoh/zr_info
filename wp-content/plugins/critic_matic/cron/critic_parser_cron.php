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

if (!class_exists('CriticParser')) {
    //Critic feeds    
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
}

$cm = new CriticMatic();
$cp = new CriticParser($cm);


$cp->run_cron($cron_type, $force, $debug);
