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
public static function calculate_actor_data($a_id)
{
    !class_exists('ActorWeight') ? include ABSPATH . "analysis/include/actors_weight.php" : '';
    ActorWeight::update_actor_weight($a_id,1,0,1,0,1);




}
    public static  function check_in_db($movie_id)
    {

        $sql = "select id from data_movie_imdb where movie_id = " . intval($movie_id) . " limit 1";

        $result = Pdo_an::db_fetch_row($sql);

        if ($result) {
            return $result->id;
        }
        return 0;

    }

    public static function search_result($key, $type,$exclude=[],$debug =0,$force=0)
    {
        //print_r($exclude);
        $array_enable=[];
        $array_not_enable=[];

        $keycode = serialize([$key,$type]);
        $name  ='p-0_get_imdb_data_search_1_'.urlencode($keycode);
        $cache=   wp_custom_cache($name,'file_cache', 3600*4);

        if ($cache && !$force)
        {
            $result_data_array = json_decode($cache,1);
        }
       else
       {
           $result_data_array = TMDB::get_data($key, $type,$debug);
       }

       if ($debug)
       {
           print_r($result_data_array);
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

                if ($data['imageType']!='tvEpisode')
                {

                $poster =  $data['titlePosterImageModel']['url'];

                if ($poster) {
                    $postersmall = str_replace('_V1_.jpg', '_V1_QL75_UY330_CR1,0,220,330_.jpg', $poster);
                    $posterbig = str_replace('_V1_.jpg', '_V1_QL75_UY660_CR1,0,440,660_.jpg', $poster);
                }
                else
                {

                    $postersmall =   WP_SITEURL . '/wp-content/themes/custom_twentysixteen/images/empty_image.svg';
                    $posterbig = $postersmall;
                }

                    $desc = $data['titleReleaseText'];

                    if (!strstr($desc,'('))
                    {
                        $desc='('.$desc.')';
                    }
                    $array_not_enable[$movie_id]=array('link'=>'https://www.imdb.com/title/' . $data['id'] ,
                        'title'=> $data['titleNameText'],
                        'poster'=>$postersmall,
                        'posterbig'=>$posterbig,
                        'desc'=>$desc,
                        'cast'=>$data['topCredits'],
                        'type'=>$data['imageType']
                    );

                }

            }
            else {

                $array_enable[$movie_id] =array('imdb_id'=>$movie_id,'link'=>'' ,'title'=> $data['titleNameText']);

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


               // $result_data.= '<tr class="click_open" id="'.$movie_id.'"><td><img src="'.$data['poster'].'" /><span style="padding:0px 10px;display:inline-block;">'.$data['title'].' ('.$data['desc'].') <span class="item_type">'.$data['type'].'</span></span><a target="_blank" style="padding:0px 10px;display:inline-block;" href="'.$data['link'].'">Open in IMDB</a></td><td>'.$button.$comment.'</td></tr>';
                if ($data['type'] == 'movie') {
                    $movie_link_desc = 'class="card_movie_type ctype_movies" title="Movie"';
                } else if ($data['type'] == 'tvSeries') {
                    $movie_link_desc = 'class="card_movie_type ctype_tvseries" title="TV Show"';
                } else if ($data['type'] == 'VideoGame') {
                    $movie_link_desc = 'class="card_movie_type ctype_videogame" title="Game"';
                }

                if ($data['cast'])
                {
                    $summary ='<div class="block block_summary"><span></span>Actors:  '.implode($data['cast'],', ').'</div>';
                }

                $result_data.= '<div  class="movie_container">
            <div class="movie_poster">
            <a target="_blank"  href="'.$data['link'].'">
                <div class="image">
                    <div class="wrapper" style="min-width: 220px;min-height: 330px;">
                        <span '.$movie_link_desc.'></span>
                        <img loading="lazy" class="poster"  srcset="'.$data['poster'].' 1x, '.$data['posterbig'].' 2x">
                    </div>
                </div>
            </a>
                <div class="movie_button_action">'.$button.'</div>
            </div>
            <div class="movie_description">
                <div class="header_title">
                    <h1 class="entry-title">'.$data['title'].' '.$data['desc'].'</h1>
                </div>
                <div class="movie_description_container">
                    <div class="movie_summary">
                    '.$summary.'
                    </div>
                </div>
            </div>
        </div>';

            }
        }

        if ($result_data)
        {
            $content= $content.'<div class="crowd_rwt" style="width: 100%;  padding: 12px 0; margin-top: 20px"><b style="text-align: center;display: block;">Please help us crowdsource the ZR database.
<br>
If what you\'re looking for isn\'t on ZR yet, try finding it below.</b>

<div class="flex_movies_block" >'.$result_data.'</div>';


        }

        $content.='<b style="text-align: center;display: block;">If the movie or show you\'re looking for isn\'t shown above, try entering an IMDb ID or url here:</b>
<table><tr class="container_for_add_movies"><td><input autocomplete="off" placeholder="IMDb ID or Link" class="addmoviesfrom_id" /></td><td><button  id="'.$movie_id.'" style="border-radius: 4px;" class="button button-primary add_movie_todb check_imdb_movie">Add to database</button></td></tr></table>
</div>';


        if ($content_result['result']) {
            $RWT_RATING = new RWT_RATING;
            $content_result['rating'] = $RWT_RATING->get_rating_data($content_result['result']);
        }

        $content_result['content'] = $content;//.$result_data;

        $content_string = json_encode($content_result);
        return $content_string;

    }

    private static function set_option($id,$option,$name = '',$coomit =0)
    {
        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
        OptionData::set_option($id,$option,$name,$coomit);


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

                self::set_option(16,json_encode($movie_list),'movie_list',1);

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
            self::set_option(16,json_encode($movie_list),'movie_list',1);

        }

    }


}



if (isset($_POST['calculate_actor_data'])){
    $a_id = $_POST['calculate_actor_data'];
    if ($a_id)
    {
        $a_id=intval($a_id);
        $result = custom_imdb_search::calculate_actor_data($a_id);

    }

    echo $result;
    return;
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
       ///check regx
       if (preg_match('#(imdb\.com\/title\/)*(tt)([0-9]+)#',$movie,$match))
       {
           $movie = $match[3];
       }


       $movie=intval($movie);
       if ($movie)
       {
           custom_imdb_search::add_movie_to_list($movie);
       }


   }

    echo 1;
    return;
}
if (isset($_GET['id']))
{
    $key=$_GET['id'];
    $type='';
    $debug = $_GET['debug'];
    $force = $_GET['force'];

    $key = urldecode($key);
    $key_array = unserialize($key);
    ///var_dump($key_array);

    $type =$key_array[1];
    $key=$key_array[0];
    $exclude = $key_array[2];

if ($type=='movies')$type='ft';

   echo custom_imdb_search::search_result($key,$type,$exclude,$debug,$force);

}



