<?php

error_reporting('E_ALL');
ini_set('display_errors', 'On');

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';

!class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';

require '../movie_single_template.php';

if (!function_exists('wp_custom_cache')) {
    include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php";
}


function get_imdb_data_search()
{
    $type=    $_GET['data'];
    if ($type)
    {
        $result =unserialize(urldecode($type));
    }

    $result_data_array  = TMDB::get_data($result[0], $result[1]);
    if ($result_data_array)
    {
        echo json_encode($result_data_array);
    }
}





class custom_imdb_search
{
    public static  function check_in_db($movie_id)
    {

        $sql = "select id from data_movie_imdb where movie_id = " . intval($movie_id) . " limit 1";

        $result = Pdo_an::db_fetch_row($sql);

        if ($result) {
            return $result->id;
        }
        return 0;

    }

    public static function search_result($key, $type,$exclude=[])
    {
        //print_r($exclude);
        $array_enable=[];
        $array_not_enable=[];

        $keycode = serialize([$key,$type]);
        $name  ='p-0_get_imdb_data_search_1_'.urlencode($keycode);
        $cache=   wp_custom_cache($name,'file_cache', 3600*4);

        if ($cache)
        {
            $result_data_array = json_decode($cache,1);
        }
       else
       {
           $result_data_array = TMDB::get_data($key, $type);
       }


        foreach ($result_data_array as $movie_id => $data) {


            if ($movie_id) {
                $enable_on_db = self::check_in_db($movie_id);

                if ($exclude && $enable_on_db)
                {
                    if ($exclude[$enable_on_db])
                    {
                        //echo $enable_on_db.' excluded ';
                        continue;
                    }
                }

            }
            if (!$enable_on_db) {

                if (!strstr($data[3],'(TV Episode)'))
                {
                    $array_not_enable[$movie_id]=array('link'=>'https://www.imdb.com' . $data[0] ,'title'=> $data[2],'poster'=>$data[1] ,'desc'=>$data[3]);

                }

            }
            else {

                $array_enable[$movie_id] =array('imdb_id'=>$movie_id,'link'=>'' ,'title'=> $data[2]);

            }

        }
        $result =  array('result'=>$array_enable,'result_imdb'=>$array_not_enable);
        $content_result=[];
        $content='';
        if (is_array($array_enable))
        {
            foreach ($array_enable as $movie_id=>$data)
            {
               $id =  TMDB::get_id_from_imdbid($movie_id);

               if ($id)
               {
                   $content_result['result'][$id]=1;
                   ob_start();
                   template_single_movie($id);
                   $content .= ob_get_contents();
                   ob_clean();
               }

            }
        }
        if ($content)
        {
            $content= '<h4 style="
    width: 100%;
    text-align: center;
    padding: 10px;
    font-size: 20px;
"> Maybe you are looking for:</h4>'.$content;
        }
        $result_data='';

        if (is_array($array_not_enable))
        {
            $sql = "SELECT * FROM `options` where id=16 ";
            $rows = Pdo_an::db_fetch_row($sql);
            $data  = $rows->val;
            if ($data)
            {
                $movie_list = json_decode($data,1);
            }



            foreach ($array_not_enable as $movie_id=>$data)
            {
                if ($movie_list[$movie_id])
                {
                    $button = '<button  id="'.$movie_id.'" class="button button-primary add_movie_todb">Vote for movie</button>';
                    $comment='<p style="font-size: 14px;line-height: 18px;margin-top: 8px;">A user already added this to the queue. <br>
Vote to increase its priority.</p>';
                }
                else
                {
                    $button = '<button  id="'.$movie_id.'" class="button button-primary add_movie_todb">Add to database</button>';
                    $comment='';
                }


                $result_data.= '<tr class="click_open" id="'.$movie_id.'"><td><img src="'.$data['poster'].'" /><span style="padding:0px 10px;display:inline-block;">'.$data['title'].' '.$data['desc'].'</span><a target="_blank" style="padding:0px 10px;display:inline-block;" href="'.$data['link'].'">Open in IMDB</a></td><td>'.$button.$comment.'</td></tr>';

            }
        }

        if ($result_data)
        {
            $content= $content.'<div style="width: 100%; background-color: #fff; color: #000; padding: 10px; margin-top: 20px"><h5 style="
width: 100%;
text-align: center;
padding: 10px;
font-size: 18px;
">Please help us crowdsource the RWT database.
<br>
If what you\'re looking for isn\'t on RWT yet, try finding it below.</h5>

<table >'.$result_data.'</table></div>';

        }




        if ($content_result['result']) {
            $RWT_RATING = new RWT_RATING;
            $content_result['rating'] = $RWT_RATING->get_rating_data($content_result['result']);
        }

        $content_result['content'] = $content;//.$result_data;

        $content_string = json_encode($content_result);
        return $content_string;

    }

    private static function set_option($id,$option)
    {
        if ($option && $id)
        {

            $sql = "DELETE FROM `options` WHERE `options`.`id` = ".$id;
            Pdo_an::db_query($sql);
            $sql = "INSERT INTO `options`  VALUES ('".$id."',?)";
            Pdo_an::db_results_array($sql,array($option));
        }

    }

    public static function remove_movie_from_list($movie_id)
    {

        $sql = "SELECT * FROM `options` where id=16 ";
        $rows = Pdo_an::db_fetch_row($sql);
        $data = $rows->val;
        if ($data) {
            $movie_list = json_decode($data, 1);

            if(isset($movie_list[$movie_id]))
            {
                unset($movie_list[$movie_id]);
            }

                self::set_option(16,json_encode($movie_list));
                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'options', array('id' => 16), 'options',7);
        }

    }


    public static function add_movie_to_list($movie_id)
    {
        $movie_list =[];
        $options=array();
        $sql = "SELECT * FROM `options` where id=16 ";
        $rows = Pdo_an::db_fetch_row($sql);
        $data  = $rows->val;
        if ($data)
        {
            $movie_list = json_decode($data,1);
        }
        $user = '';
        $enable_on_db = self::check_in_db($movie_id);
        if (!$enable_on_db)
        {
            $movie_list[$movie_id]++;

        }
        if ($movie_list)
        {
            self::set_option(16,json_encode($movie_list));

            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            Import::create_commit('', 'update', 'options', array('id' => 16), 'options',7);
        }

    }


}



if (isset($_POST['remove_movie']))
{

    $movie = $_POST['remove_movie'];
    if ($movie)
    {
        $movie=intval($movie);
        custom_imdb_search::remove_movie_from_list($movie);

    }

    echo 1;
    return;
}


if (isset($_POST['read_more_rating']))
{

    if ($_POST['read_more_rating']==1) {
        if (isset($_POST['rwt_id']))
        {
            $id = $_POST['rwt_id'];
        }
        if (isset($_POST['movie_id'])) {
            $id = $_POST['movie_id'];
        }
        if ($id){
            !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
            echo  PgRatingCalculate::get_movie_desc($id);
        }
    }
return;
}



if (isset($_POST['refresh_rwt_rating']))
{

    if ($_POST['refresh_rwt_rating']==1)
    {

        if (isset($_POST['rwt_id']))
        {
            $id = $_POST['rwt_id'];
        }

        if (isset($_POST['movie_id']))
        {
            $id = $_POST['movie_id'];
        }


        !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
        PgRatingCalculate::add_movie_rating($id,'',1,0);

        return;
    }

}
if (isset($_POST['refresh_rating']))
{

    if ($_POST['refresh_rating']==1)
    {

        if (isset($_POST['rwt_id']))
        {
            $id = $_POST['rwt_id'];
        }

        if (isset($_POST['movie_id']))
        {
            $id = $_POST['movie_id'];
        }

//PgRatingCalculate
        !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
        PgRatingCalculate::CalculateRating('',$id,1,0);

        return;
    }

}


if (isset($_POST['add_movie']))
{

   $movie = $_POST['add_movie'];
   if ($movie)
   {
       $movie=intval($movie);
       custom_imdb_search::add_movie_to_list($movie);

   }

    echo 1;
    return;
}


if (isset($_GET['id']))
{
    $key=$_GET['id'];
    $type='';

    $key = urldecode($key);
    $key_array = unserialize($key);
    ///var_dump($key_array);

    $type =$key_array[1];
    $key=$key_array[0];
    $exclude = $key_array[2];

if ($type=='movies')$type='ft';

   echo custom_imdb_search::search_result($key,$type,$exclude);

}



