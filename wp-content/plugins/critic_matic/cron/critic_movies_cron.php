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


$count = 100;
if ($_GET['c']) {
    $count = (int) $_GET['c'];
}

$expire = 30;
if ($_GET['e']) {
    $expire = (int) $_GET['e'];
}

$cm = new CriticMatic();
$cs = new CriticSearch($cm);
$cs->run_cron($count, $debug, $expire);
