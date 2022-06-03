<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';


function last_movie_trailer($movie_id=0)
{
    $data  =check_movie_trailer($movie_id);
    if ($data)
    {
        if ($data->status==1)
        {
            $result =  $data->data;
            if ($result)
            {
                return  sort_data(json_decode($result,1));
            }
        }
    }
}
function check_movie_trailer($movie_id=0){
if ($movie_id) {
    $movie_id=intval($movie_id);
$sql ="select * from cache_movie_trailers where rwt_id={$movie_id}";
$rows = Pdo_an::db_fetch_row($sql);
return  $rows;
}
}
function sort_data($array)
{
    $arraysort = array(1=>'Trailer',2=>'Featurette',3=>'Teaser',4=>'Clip');
    $array = array_filter(array_replace(array_fill_keys($arraysort, null), $array));
    $keys = array_keys($array);
    return $array[$keys[0]]['key'];
}

function get_movie_trailer($movie_id=0)
{
    $data  =check_movie_trailer($movie_id);
    if ($data)
    {
        if ($data->status==1)
        {
            $result =  $data->data;
            if ($result)
            {
            return  sort_data(json_decode($result,1));
            }
        }
//        if ($data->status==2 && $data->last_update<time()-86400)
//        {
//            return '';
//        }
    }

    $api_key ='1dd8ba78a36b846c34c76f04480b5ff0';

    $tmdb_id = TMDB::get_tmdbid_from_id($movie_id);

    if ($tmdb_id)
    {
        $array_type = array('Movie' => 'movie', 'TVSeries' => 'tv', 'TVEpisode' => 'tv');
        $movie_type=TMDB::get_movie_type_from_id($movie_id);
        $type= $array_type[$movie_type];

        $url='https://api.themoviedb.org/3/'.$type.'/'.$tmdb_id.'/videos?api_key='.$api_key.'&language=en-US';
        ///echo $url;

        $result = GETCURL::getCurlCookie($url);
        if ($result) {
            try {
                $object_result = json_decode($result);

            } catch (Exception $ex) {
                return false;
            }


            if ($object_result) {
                $status = 1;
                $object = (object)array('last_update' => time(), 'status' => $status, 'data' => $object_result);
            }
        }

    }
    else
    {
        return '';
    }
$array_data = [];

    $data_object = $object->data->results;
    if ($data_object)
    {
        foreach ($data_object as $i=>$val)
        {
            if ($val->site=='YouTube')
            {
                $key = $val->key;
                $array_data[$val->type]=array('name'=>$val->name,'key'=>$val->key);
            }
        }
    }
    if (!$key) {
        $status=2;
    }

    $array_data_string='';
    if ($array_data)
    {
        $array_data_string= json_encode($array_data);
    }

    if ($data)
    {
        $id =$data->id;

        $sql="UPDATE `cache_movie_trailers` SET  `data`=?, `status`=?, `last_update`=? WHERE `id`=? ";
        Pdo_an::db_results_array($sql,array($array_data_string,$status,time(),$id));
    }
    else
    {
        $sql="INSERT INTO `cache_movie_trailers` (`id`, `rwt_id`, `data`, `status`, `last_update`) 
                                                VALUES (NULL, ?, ?, ?, ?);";
        Pdo_an::db_results_array($sql,array($movie_id,$array_data_string,$status,time()));
        $id = Pdo_an::last_id();
    }

    !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
    Import::create_commit('', 'update', 'cache_movie_trailers', array('id' => $id), 'movie_trailers',20);


    if ($array_data_string) {
     return  sort_data(json_decode($array_data_string,1));
    }
}

?>