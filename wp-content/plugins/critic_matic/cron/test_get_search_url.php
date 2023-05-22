<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
set_time_limit(0);


if (!class_exists('CriticMatic')) {
    return;
}

$p = 'D_23_2D0FS0-vbb';

if ($_GET['p'] != $p) {
    return;
}


$url = '/search/price_free/release_2004-2030/rrwt_37-48/minus-indie_isfranchise_bigdist_meddist/minus-rf_lgbt_woke';

// Init url
$last_req = $_SERVER['REQUEST_URI'];

$_SERVER['REQUEST_URI'] = $url;
$search_front = new CriticFront();
$result = $search_front->find_results(array(),false, true);

// Deinit url
$_SERVER['REQUEST_URI']=$last_req;


print '<pre>';
print_r($result);
print '</pre>';