<?php

/*
 * Cron script for parse dove.org
 */

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//File service
!class_exists('FileService') ? include ABSPATH . "analysis/include/FileService.php" : '';
//FileLog
!class_exists('FileLog') ? include ABSPATH . "analysis/include/log.php" : '';
//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';
//Parser Dove.org
!class_exists('ParserDove') ? include ABSPATH . "analysis/include/ParserDove.php" : '';


if (isset($_GET['install'])) {
    if ($_GET['install'] = 'Ssd3e_klmn-DSF') {
        $pd = new ParserDove();
        $pd->install();
        exit();
    }
}

$debug = false;
if (isset($_GET['debug'])) {
    $debug = true;
}

$limit = 1;
if (isset($_GET['limit'])) {
    $limit = (int) $_GET['limit'];
}

$sleep = 0;
if (isset($_GET['sleep'])) {
    $sleep = (int) $_GET['sleep'];
}

$wait_days = 30;
if (isset($_GET['wait_days'])) {
    $wait_days = (int) $_GET['wait_days'];
}

$use_cache = false;
if (isset($_GET['use_cache'])) {
    $use_cache = (int) $_GET['use_cache'];
}

$use_proxy = false;
if (isset($_GET['use_proxy'])) {
    $use_proxy = (int) $_GET['use_proxy'];
}

//one time task
if (isset($_GET['bag_fix'])) {
    $pd = new ParserDove();
    $pd->json_bug_fix();
    exit();
}

if (isset($_GET['parse']) && $_GET['parse'] == 'D-dl23DFSsk_d') {
    parse_dove($debug, $limit, $wait_days, $sleep, $use_cache, $use_proxy);
    exit();
}

if (isset($_GET['show_log'])) {
    $pd = new ParserDove();
    $data = $pd->log->getLogData();
    $pd->log->renderLogData($data);
    exit();
}

/**
 * Parse dove.org function
 *
 * @author brahman
 */
function parse_dove($debug = false, $limit = 1, $wait_days = 30, $sleep = 0, $use_cache = false, $use_proxy = false) {
    $pd = new ParserDove();
    if ($debug) {
        $pd->debug_on = true;
    }
    if ($use_cache) {
        $pd->cache_result = true;
    }

    if ($use_proxy) {
        $pd->use_proxy = true;
    }

    $pd->update($limit, $wait_days, $sleep);
}
