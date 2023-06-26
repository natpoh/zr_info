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

$cid = $_GET['cid'] ? (int) $_GET['cid'] : 0;

if (!$cid) {
    return;
}

$type = $_GET['type'] ? $_GET['type'] : '';

$custom_url_id = $_GET['url'] ? (int) $_GET['url'] : 0;

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

$cron_name = 'cm_async_cron_' . $cid . '_' . $type;
if ($cm->cron_already_run($cron_name, 10, $debug)) {
    exit();
}

$cm->register_cron($cron_name);

$cp = $cm->get_cp();
$cp->run_cron_async($cid, $type, $debug, $custom_url_id);

$cm->unregister_cron($cron_name);
