<?php
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}
$_GET['type']='compilation';

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


if (!class_exists('TV_Scroll'))  {
    require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/ajax/tv_scroll.php');
}
else
{
    $cache = tv_scroll('compilation',intval($_GET['id']));
    echo $cache;
}

