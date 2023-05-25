<?php
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

