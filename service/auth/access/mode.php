<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

$root = $_SERVER['DOCUMENT_ROOT'];
if (strstr('service', $_SERVER['DOCUMENT_ROOT'])) {
    $root = str_replace('service', '', $_SERVER['DOCUMENT_ROOT']);
    define('ABSPATH', $root . '/');
}

require_once( ABSPATH . 'an_config.php' );


$pdo_connect_data = array(
    'host' => DB_HOST_AN,
    'user' => DB_NAME_AN,
    'pass' => DB_PASSWORD_AN,
    'db' => 'cpuinfo'
);
