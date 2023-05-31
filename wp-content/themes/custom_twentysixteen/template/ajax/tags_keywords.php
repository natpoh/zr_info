<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

function tags_keyword($id='')
{
    if (!$id)
    {
        $id= $_GET['id'];
    }



    !class_exists('Movie_Keywords') ? include ABSPATH . "analysis/include/keywords.php" : '';

    $keywords = new Movie_Keywords;

    $data = $keywords->front($id);


    echo $data;

}




if (isset($_GET['id']))
{
    $id = intval($_GET['id']);


    if (!function_exists('wp_custom_cache')) {
        require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php');
    }

    $cache = wp_custom_cache('p-'.$id.'_tags_keyword_1', 'fastcache', 86400);
    if (!$cache || $cache=='')
    {
        $cache=	'<p style="margin: 25px auto;">No keywords have been found yet, they will be added soon...</p>';

    }
    echo $cache;

  ///  tags_keyword($id);
}


