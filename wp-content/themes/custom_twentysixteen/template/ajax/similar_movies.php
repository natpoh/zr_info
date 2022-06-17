<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


!class_exists('SimilarMovies') ? include ABSPATH . "analysis/include/similar_movies.php" : '';

function similar_movies($id='')
{
    if (!$id)
    {
        $id= $_GET['id'];
    }

    $data = SimilarMovies::get_movies($id);
    echo $data;

}




if (isset($_GET['id']))
{
    $id = intval($_GET['id']);


    if (!function_exists('wp_custom_cache')) {
        require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php');
    }

    $cache = wp_custom_cache('p-'.$id.'_similar_movies_1', 'fastcache', 86400);
    echo $cache;

  // similar_movies($id);
}

