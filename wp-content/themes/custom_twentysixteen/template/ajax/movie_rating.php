<?php
error_reporting('E_ALL');
ini_set('display_errors', 'On');




if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

!function_exists('wp_custom_cache') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php" : '';

//include ABSPATH.'wp-config.php';



function get_movie_rating()
{
    !class_exists('RWT_RATING') ?    include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : "";

    $movie_id = intval($_GET['id']);
    $data = new RWT_RATING;

    $gender = $data->gender_and_diversity_rating($movie_id);
    $family = $data->ajax_pg_rating($movie_id);///real id
    $audience = $data->rwt_audience($movie_id, 1);
    $stuff = $data->rwt_audience($movie_id, 2);
    $rating = $data->rwt_total_rating($movie_id);


    $array_result = array('gender' => $gender, 'family' => $family, 'audience' => $audience, 'stuff' => $stuff,'total_rating'=>$rating);
    if ($array_result) {
        return json_encode($array_result);
    }

}


//        $cache =get_movie_rating();
//        echo $cache;
//        return;




if (isset($_GET['id'])) {


if (function_exists('wp_custom_cache') )
{
    $cache=   wp_custom_cache('p-'.$_GET['id'].'_get_movie_rating_1','file_cache', 3600);
}
else
{
    $cache =get_movie_rating(); //////single movie
}

echo $cache;


}