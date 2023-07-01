<?php
error_reporting('E_ALL');
ini_set('display_errors', 'On');

global $included;


if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

!function_exists('wp_custom_cache') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php" : '';

//include ABSPATH.'wp-config.php';



function get_movie_rating($movie_id='')
{
    !class_exists('RWT_RATING') ?    include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : "";
    if (!$movie_id)
    {
        $movie_id = intval($_GET['id']);
    }

    $data = new RWT_RATING;
//    $type= $data->get_movie_type($movie_id);
//    $gender = $data->gender_and_diversity_rating($movie_id);
//    $family = $data->ajax_pg_rating($movie_id);///real id
//    $audience = $data->rwt_audience($movie_id, 1);
//    $stuff ='';// $data->rwt_audience($movie_id, 2);
//    $rating = $data->rwt_total_rating($movie_id);
//    $indie = $data->box_office($movie_id);

    $content_result = $data->get_rating_movie($movie_id);


    $array_result = $content_result;//array('type'=>$type,'gender' => $gender, 'family' => $family, 'audience' => $audience, 'stuff' => $stuff,'total_rating'=>$rating,'indie'=>$indie);
    if ($array_result) {
        return json_encode($array_result);
    }

}


//        $cache =get_movie_rating();
//        echo $cache;
//        return;

if ($included) return;


if (isset($_POST['action'])) {

    if ($_POST['action']=='get_link')
    {
        $mid  =intval($_POST['id']);
        $type  =$_POST['type'];
        !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';

        if (strstr($type,'_rating'))
        {
            $type = substr($type,0,strpos($type,'_rating'));
        }



        $data = PgRatingCalculate::get_rating_url($mid,$type);
        echo json_encode($data);


    }

}

if (isset($_GET['id'])) {


//if (function_exists('wp_custom_cache') )
//{
//    $cache=   wp_custom_cache('p-'.$_GET['id'].'_get_movie_rating_1','file_cache', 3600);
//}
//else
{
    $cache =get_movie_rating(); //////single movie
}

echo $cache;


}