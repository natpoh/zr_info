<?php

ini_set('display_errors', 0);
ini_set('error_reporting', E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


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
echo $cache;


?>

