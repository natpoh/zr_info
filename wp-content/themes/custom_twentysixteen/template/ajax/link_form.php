<?php

// WP api
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');

global $cfront;
if (!$cfront) {
    exit;
}

$uf = $cfront->get_uf();

// Get form
if (isset($_GET['url'])) {
    $url = $_GET['url'];
    $exist = $uf->link_exist($url);

    if (!$exist['error']) {
        print $uf->link_form($exist);
    } 
}

