<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
nocache_headers();

$type = $_GET['type'];
$task = $_GET['task'];
if (!$task) {
    $task = 'hotmap';
}

$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$force = false;
if ($_GET['force']) {
    $force = true;
}

if (!$type) {
    exit;
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

if (!class_exists('CriticMatic')) {
    exit;
}

if (!class_exists('CriticSitemap')) {
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticSitemap.php' );
}

$epvSitemap = new CriticSitemap();

if (!in_array($type, $epvSitemap->types)) {
    exit;
}

if ($task == 'hotmap') {
    $epvSitemap->checkUpdateHotMap($type, $debug, $force);
}

if ($task == 'year') {
    $epvSitemap->upadteCurrentYear($type, $debug);
}
///pandoraopen.ru/wp-content/plugins/epv-sitemap/epv-sitemap-cron.php?task=hotmap
?>