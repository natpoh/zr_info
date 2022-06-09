<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';



class SimilarMovies
{

    public static function get_from_db($id,$last_update=0)
    {
        $q='';
        if ($last_update)
        {
            $q=" and last_update > '". (time() - ($last_update*86400)) ."' ";
        }

        $sql ="SELECT * FROM `cache_movies_similar` WHERE `movie_id` ={$id}  ".$q." limit 1";
       $r = Pdo_an::db_fetch_row($sql);
       return $r;

    }

    public static function get_data($title)
    {
        ///get data from movie-map

        $url = "https://www.movie-map.com/map-search.php?f=".urlencode($title);

      //  echo $url;

        $result = GETCURL::getCurlCookie($url);
        $resultarray=[];

      //  echo $result;

        $reg_v ='#\<a href="[^"]+" class=S id=s([0-9]+)\>([^\<]+)\<\/a\>#';
        if (preg_match_all($reg_v,$result,$match ) )
        {
            foreach ($match[0] as $a=>$b)
            {
                if ($match[1][$a]!=0)
                {
                    $resultarray[$match[1][$a]]  =$match[2][$a];
                }

            }
        }

        return $resultarray;
    }

    public static function get_movie_by_name($title)
    {
        $sql = "SELECT id from data_movie_imdb where title =? ORDER BY `data_movie_imdb`.`rating` DESC limit 1";
        $r = Pdo_an::db_results_array($sql,[$title]);
        $id = $r[0]['id'];

        return $id;
    }

    public static function find_movies_byname($resultarray)
    {
        $result = [];
        foreach ($resultarray as $index=>$name)
        {
            $id = self::get_movie_by_name($name);
            if ($id)
            {
                $result[$index]=$id;
            }
        }
        return $result;
    }

    public static function save_data($id,$result_ids)
    {
        ///check

        $result_ids = json_encode($result_ids);

        $data  = self::get_from_db($id);
        if ($data)
        {
            $mid = $data->id;
            $sql ="UPDATE `cache_movies_similar` SET `data`=?,`last_update`=? WHERE `id` =?";
            Pdo_an::db_results_array($sql,[$result_ids,time(),$mid]);
        }
        else
        {
            $sql="INSERT INTO `cache_movies_similar`(`id`, `movie_id`, `data`, `last_update`) VALUES (NULL,?,?,?)";
            Pdo_an::db_results_array($sql,[$id,$result_ids,time()]);
            $mid = Pdo_an::last_id();

        }

        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'update', 'cache_movies_similar', array('id' => $mid), 'movies_similar',20);

    }

    public static function template_movies($array)
    {
        ob_start();

        if (!function_exists('template_single_movie')) {
            include($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/custom_twentysixteen/template/movie_single_template.php');
        }
        $content_result=[];

        foreach ($array as $pos => $id)
    {


    $sql = "select title ,`type`, post_name from data_movie_imdb where id = " . $id . " limit 1";
    $rows = Pdo_an::db_fetch_row($sql);

    $post_name = $rows->post_name;
    $title = $rows->title;
    $type = $rows->type;



    if (!$post_name) {

            if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
            }
            $cfront = new CriticFront();
        if ($cfront) {
            $post_name = $cfront->get_or_create_ma_post_name($id);
        }
    }


    $post_type = strtolower($type);

    if ($post_type == 'movie' || $post_type == 'tvseries') {
        if (function_exists('template_single_movie')) {
            template_single_movie($id, $title, $post_name);
            $content_result[$id] = $id;
        }
    }
}

   $content =  ob_get_contents();
        ob_clean();

!class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';
$RWT_RATING = new RWT_RATING;
$content_result = $RWT_RATING->get_rating_data($content_result,0);

$rs = ['content'=>$content,'rating'=>$content_result];

return json_encode($rs);

    }


    public static function get_movies($id)
    {

        $data  = self::get_from_db($id,14);
        if ($data)
        {
            $result_ids = json_decode($data->data,1);
        }

        else
        {
            $sql = "SELECT title from data_movie_imdb where id =".intval($id);
            $r = Pdo_an::db_fetch_row($sql);
            $title = $r->title;
            $resultarray = self::get_data($title);
            $result_ids = self::find_movies_byname($resultarray);
            if ($result_ids)
            {
                self::save_data($id,$result_ids);
            }
        }

        if ($result_ids)
        {
            ///add tempate
            $result_data = self::template_movies($result_ids);

            return $result_data;
        }


    }





}