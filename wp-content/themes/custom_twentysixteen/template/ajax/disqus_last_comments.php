<?php
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}

ini_set('display_errors', 0);
ini_set('error_reporting', E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


if (!function_exists('wp_custom_cache'))
{
    include(ABSPATH.'wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php');
}



function get_comment_from_db()
{
    !class_exists('DISQUS_DATA') ? include ABSPATH . "analysis/include/disqus.php" : '';

    $count=7;
    if (isset($_GET['count']))
    {
        $count = intval($_GET['count']);
    }
    $page=0;

    if (isset($_GET['cursor']))
    {
        $page = intval($_GET['cursor']);
    }

    $cache =DISQUS_DATA::get_comment_from_db($count,$page);
    return $cache;

}


$cache = wp_custom_cache('get_comment_from_db', $folder = 'fastcache', $time = 10);
echo $cache;
?>

