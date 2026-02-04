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
    public static function compare($type,$i1,$i2)
    {
        if ($type=='type' )
        {
            if ($i1==$i2)$r = 100;
            else $r = 0;
        }
        else if ($type=='rating')
        {
            if (!$i1)
            {
                $i1=0;
            }
            if (!$i2)
            {
                $i2=0;
            }
            $sm = abs($i1*10-$i2*10);
            if ($sm==0)
            {
                $sm=1;
            }

            $r = 100/$sm;
            if ($r>100)$r=100;

        }
        else if ( $type=='year')
        {
            if (!$i1)
            {
                $i1=0;
            }
            if (!$i2)
            {
                $i2=0;
            }
            $sm = abs($i1-$i2);
            if ($sm==0)
            {
                $sm=1;
            }

            $r = 100/$sm;

        }
        else
        {
            $sim = similar_text($i1, $i2, $r);
        }


        return $r;
    }

    public static function compare_data($r,$row_original)
    {
        $array_compare = array('type'=>1,'genre'=>2,'country'=>1,'language'=>1,'keywords'=>1,'rating'=>1,'year'=>1);


        $array_movies_result=[];
        foreach ($r as $row)
        {

           // echo $row['title'].' '.$row['id'].' vs '.$row_original['title'].' '.$row_original['id'].PHP_EOL;
            $array_result =0;
            foreach ($array_compare as $type=>$weight)
            {
                $rs = self::compare($type,$row[$type],$row_original[$type]);

              //  echo $type.' '.$row[$type].' = '.$row_original[$type].' ( '.$rs.' )'.PHP_EOL;

                $array_result+=$rs*$weight;

            }
            $array_movies_result[$row['id']]=$array_result;

        }
        arsort($array_movies_result);
        //var_dump($array_movies_result);
        $keys = array_keys($array_movies_result);
        return $keys[0];




    }

    public static function get_movie_by_name($title,$row_original)
    {
        $sql = "SELECT * from data_movie_imdb where title =? ORDER BY ABS (`data_movie_imdb`.`productionBudget`)  DESC ,ABS (`data_movie_imdb`.`rating`)  DESC";

        $r = Pdo_an::db_results_array($sql,[$title]);
        if (count($r)>1)
        {
            $id = self::compare_data($r,$row_original);
           /// var_dump($r);



        }
        else
        {
            $id  = $r[0]['id'];

        }



        return $id;
    }

    public static function find_movies_byname($resultarray,$row_original)
    {
        $result = [];
        foreach ($resultarray as $index=>$name)
        {
            $id = self::get_movie_by_name($name,$row_original);
            if ($id)
            {

                $result[$index]=$id;
            }
        }
        return $result;
    }

    public static function save_data($id,$result_ids,$resultarray)
    {
        ///check
        $resultarray= json_encode($resultarray);
        $result_ids = json_encode($result_ids);

        $data  = self::get_from_db($id);
        if ($data)
        {
            $mid = $data->id;
            $sql ="UPDATE `cache_movies_similar` SET `titles`=?, `data`=?,`last_update`=? WHERE `id` =?";
            Pdo_an::db_results_array($sql,[$resultarray,$result_ids,time(),$mid]);
        }
        else
        {
            $sql="INSERT INTO `cache_movies_similar`(`id`, `movie_id`, `titles` ,`data`, `last_update`) VALUES (NULL,?,?,?,?)";
            $mid =Pdo_an::db_insert_sql($sql,[$id,$resultarray,$result_ids,time()]);


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

        $data='';


//    if ($content)
//    {
//        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
//        $data =  OptionData::get_options('','similar_shows');
//        $data =  str_replace('\\','',$data);
//
//    }

        if ($content)
        {
            $rs = ['content'=>$content,'rating'=>$content_result,'data'=>$data];
            return json_encode($rs,JSON_INVALID_UTF8_IGNORE);
        }


    }

public static function get_title($id)
{

    $sql = "SELECT * from data_movie_imdb where id =".intval($id);
    $r = Pdo_an::db_results_array($sql);
    $title = $r[0]['title'];
    $type = $r[0]['type'];
    return [urlencode($title),$type];

}
    public static function get_movies($id)
    {
        $result_ids=[];

        $sql = "SELECT * from data_movie_imdb where id =" . intval($id);
        $r = Pdo_an::db_results_array($sql);
        $title = $r[0]['title'];
        $type = $r[0]['type'];
        $strict_type=0;

        if ($type=='VideoGame'){$strict_type=1;}

        if ($type=='Movie'  || $type=='TVSeries') {
            $strict_type=2;

            $data = self::get_from_db($id, 14);
            if ($data) {
                $result_ids = json_decode($data->data, 1);
            } else {
                $resultarray = self::get_data($title);
                $result_ids = self::find_movies_byname($resultarray, $r[0]);
                if ($result_ids) {
                    self::save_data($id, $result_ids, $resultarray);
                }
            }
        }

            if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');

            }
            $cm = new CriticMatic();
            $cs = $cm->get_cs();
             global $debug;
            $result = $cs->related_movies($id, 20, $strict_type, $debug);

            foreach ($result  as $i=>$v)
            {
                if (count($result_ids)>=20)break;
                if (!in_array($v->id,$result_ids))
                {
                    $result_ids[]= $v->id;
                }

            }



        if (count($result_ids)>0)
        {
            ///add tempate
            $result_data = self::template_movies($result_ids);

            return $result_data;
        }


    }





}