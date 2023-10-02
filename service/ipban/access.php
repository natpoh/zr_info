<?php

$keycom = "XF3-ds3_24dfsb";

$key = $_GET['key'];
$debug = $_GET['debug'];

if ($keycom != $key) {
    exit();
}

require_once('ipban.php');

$ipBanService = new IpBanService();

if ($_GET['install']){
    $ipBanService->install_info();
    exit();
}

$ipBanService->run_cron($debug);