<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
nocache_headers();

if (!class_exists('CriticMatic')) {
    return;
}

$p = '2338g_D0dDSF-Fs';

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

$force= false;
if ($_GET['force']) {
    $force= true;
}

if (!class_exists('CriticParser')) {
    //Critic feeds    
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
}

$cm = new CriticMatic();
if (!class_exists('CriticAudience')) {
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticAudience.php' );
}
$ca = new CriticAudience($cm);


$ca->run_cron($count, $debug, $force);
