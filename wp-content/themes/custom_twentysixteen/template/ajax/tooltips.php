<?php

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

!class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';


if (isset($_GET['type']))
{
    $type = $_GET['type'];

    if (preg_match('/^[a-zA-Z_-]+$/', $type)) {
        $result = OptionData::get_options('', $type);
        $result = stripslashes($result);
        echo $result;

    }
}