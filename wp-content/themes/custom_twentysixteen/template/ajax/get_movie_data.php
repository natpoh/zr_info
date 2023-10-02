<?php
//if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
//    exit();
//}
error_reporting(E_ERROR);


!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';
include('../include/custom_connect.php');
include('../include/create_tsumb.php');


if (isset($_POST['request']))
{
    $data='';


    if ($_POST['request']=='get_trailer')
    {

        include ('../include/check_movie_trailer.php');


        $id = intval($_POST['id']);
        $tmdb_id = intval($_POST['tmdb_id']);


        if (function_exists('get_movie_trailer'))
        {
         $data = get_movie_trailer($id,$tmdb_id);
        }


    }

echo $data;

}