<?php
set_time_limit(0);
ini_set('display_errors', 'On');
error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

!class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
/////////add rating


function get_array($id='')
{

    $type = 'imdb';
    $id = intval($id);

    if (isset($_GET['type']))
    {
        $type=  $_GET['type'];
    }


    if ($type=='tmdbmovie')
    {

        header('Content-Type: application/json');
          $r =  TMDB::get_tmdb_data_movie($id, 'movie');
        echo json_encode($r);
        return;

    }

    else if ($type=='tmdbtv')
    {
        header('Content-Type: application/json');

        $r = TMDB::get_tmdb_data_movie($id, 'tv');
        echo json_encode($r);
        return;

    }
    else if ($type=='get_tmdb_from_imdb')
        {

          $r =  TMDB::get_tmdbid_from_imdbid($id);
          echo $r;
          return;

        }

    else if ($type=='imdb')
    {
        $archive=0;
        if (isset($_GET['a']))
        {
            $archive = 1;
        }

        header('Content-Type: application/json');
        $array_movie =  TMDB::get_content_imdb($id,1,1,$archive);
        echo $array_movie;
        return;
    }

}


function check_tmdb_actors($id)
{
    ////not used
    return;

//
//    !class_exists('KAIROS') ? include ABSPATH . "analysis/include/kairos.php" : '';
//    $where='';
//    if ($id)
//    {
//        $where = " AND id=".intval($id);
//    }
//
//    $sql = "SELECT `actor_id`, `last_update` FROM `data_actors_meta`  WHERE `tmdb_id` IS NULL ".$where." and (last_update < ".(time()-86400*30)." OR last_update  IS NULL ) order by last_update desc LIMIT 10";
////echo $sql.'<br>';
//    $rows = Pdo_an::db_results_array($sql);
//    $count = count($rows);
//    $i=0;
//    foreach ($rows as $r2)
//    {
//        $i++;
//        $id = $r2['actor_id'];
//        $last_update = $r2['last_update'];
//
//        echo 'last_update='.$last_update.'<br>';
//
//       KAIROS::add_actors_from_tmdb($id);
//
//        $sql2 = "UPDATE `data_actors_meta` SET `last_update` = '".time()."' WHERE `data_actors_meta`.`actor_id` = ".$id;
//        Pdo_an::db_query($sql2);
//
//
//        echo $i.' of '.$count.' id='.$id.'<br>'.PHP_EOL;
//
//    }
}


function check_actors_meta()
{

    $sql = "SELECT `data_actors_meta`.id  FROM `data_actors_meta` LEFT JOIN data_actors_imdb 
    ON `data_actors_imdb`.id=data_actors_meta.actor_id
        WHERE   data_actors_imdb.id IS NULL order by `data_actors_meta`.id desc";

    $rows = Pdo_an::db_results_array($sql);
    if ($rows)echo 'add empty '.count($rows);

    foreach ($rows as $r)
    {
        $id = $r['id'];
        $actor_id = $r['actor_id'];
        echo   'found id= ' . $id . ' imdb_id ' .$actor_id.' deleted  <br>' . PHP_EOL;
        $sql ="DELETE FROM `data_actors_meta` WHERE `data_actors_meta`.`id` = ".$id;
        Pdo_an::db_query($sql);

    }



    $sql ="SELECT id, actor_id FROM `data_actors_meta` ";

    $last_data=[];
    $rows = Pdo_an::db_results_array($sql);
    foreach ($rows as $r)
    {
        $id = $r['id'];
        $actor_id = $r['actor_id'];


        if ($actor_id==$actor_id_last)
        {
            echo   '<br>Origin id=' . $last_data['id'] . ' imdb_id ' .$last_data['actor_id'].'  <br>' . PHP_EOL;
            echo   'found id= ' . $id . ' imdb_id ' .$actor_id.' deleted  <br>' . PHP_EOL;

            if ($_GET['delete']==1)
            {
                $sql ="DELETE FROM `data_actors_meta` WHERE `data_actors_meta`.`id` = ".$id;
                Pdo_an::db_query($sql);
            }

        }
        else
        {
            $last_data = $r;
            $actor_id_last=$actor_id;
        }


    }
}

function update_actors_verdict($id='')
{
    !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';
    set_time_limit(0);
if ($id)
{
  $where ="where actor_id = ".$id." ";
}
else
{
   $where ="where verdict IS NULL limit 100000";
}

$sql = "select * from data_actors_meta ".$where." ";
///echo $sql;

    $rows = Pdo_an::db_results_array($sql);
    ///echo 'count = '.count($rows).'<br>';
    $array_verdict = array('crowdsource','ethnic','jew','kairos','bettaface','placebirth','surname');
$array_exclude = array('NJW');
    foreach ($rows as $row)
    {
      // print_r($row);
        foreach ($array_verdict as $val)
        {
            $verdict = $row[$val];


            if ($verdict && !is_numeric($verdict) && !in_array($verdict,$array_exclude) )
            {

                $sql = "update `data_actors_meta` set verdict =?, n_verdict =?  where id = ".$row['id']." ";
                Pdo_an::db_results_array($sql,array($row[$val],intconvert($row[$val])));

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'data_actors_meta', array('id' => $row['id']), 'actor_meta',9);

                ACTIONLOG::update_actor_log('verdict');
                break;
            }
        }

    }

}


function get_last_options($id)
{


    $sql = "SELECT val FROM `options` where id = " . $id;
    $rows = Pdo_an::db_fetch_row($sql);
    $last_id = $rows->val;



    if (!$last_id) $last_id = 0;
    return $last_id;
}

if (!function_exists('set_option')) {

    function set_option($id, $option)
    {
        if ($option && $id) {

            $sql = "DELETE FROM `options` WHERE `options`.`id` = " . $id;
            Pdo_an::db_query($sql);

            $sql = "INSERT INTO `options`  VALUES ('" . $id . "',?)";
            Pdo_an::db_results_array($sql,array($option));

        }

//print_r($q->errorInfo());

    }
}

function add_to_db_from_userlist()
{
    $sql = "SELECT * FROM `options` where id=16 ";
    $rows = Pdo_an::db_fetch_row($sql);
    $data = $rows->val;
    if ($data) {
        $movie_list = json_decode($data, 1);

    foreach ($movie_list as $movie_id=>$count)
    {


        $addeded = TMDB::check_imdb_id($movie_id);
        if (!$addeded)
        {
            ////add movie to database
            $array_movie =  TMDB::get_content_imdb($movie_id);
            $add =  TMDB::addto_db_imdb($movie_id, $array_movie);
            echo $movie_id.' adedded <br>'.PHP_EOL;

            if(isset($movie_list[$movie_id]))
            {
                unset($movie_list[$movie_id]);
            }
        }
        else
        {
            echo  $movie_id.' already adedded <br>'.PHP_EOL;
        }
    }

    }

    $sql = "DELETE FROM `options` WHERE `options`.`id` = 16";
    Pdo_an::db_query($sql);
    $sql = "INSERT INTO `options`  VALUES ('16',?)";
    Pdo_an::db_results_array($sql,array(json_encode($movie_list)));

}

function update_imdb_data($from_archive=0)
{
////update all imdb movies

    echo 'update_imdb_data only new movies<br>';

    //$from_archive=1;

    $time_min = time()-86400*7;



    if ($from_archive)
    {
        $limit = 300;
    }
    else
    {
        $limit = 10;
    }


    $date_current = date('Y-m-d', time());
    $date_main = date('Y-m-d', strtotime('-90 days', time()));


    $sql = "SELECT movie_id FROM `data_movie_imdb` where  (`release`  >=  '" . $date_main . "'  OR `release` IS NULL )   and  `add_time` < '".$time_min."'  order by add_time asc limit ".$limit;

   // echo $sql;

    $result =Pdo_an::db_results($sql);

    foreach ($result as $row) {

        $movie_id = $row->movie_id;

            if ($movie_id) {
                   // sleep(0.5);
                    $array_movie =  TMDB::get_content_imdb($movie_id,'',1,$from_archive);
                    $add =  TMDB::addto_db_imdb($movie_id, $array_movie);
                    if ($add) {
                        echo $movie_id . ' updated<br> ' . PHP_EOL;
                    }
            }

    }


////select last movies

}


function get_new_movies(){
    !class_exists('GETNEWMOVIES') ? include ABSPATH . "analysis/include/get_new_movies.php" : '';
    GETNEWMOVIES::get_new_movies();
}
function get_new_tv(){
    !class_exists('GETNEWMOVIES') ? include ABSPATH . "analysis/include/get_new_movies.php" : '';
    GETNEWMOVIES::get_new_tv();
}


//function check_race($id='')
//{
//    return;
//
////    $array_face = array('white' => 'W', 'hispanic' => 'H', 'black' => 'B',  'asian' => 'EA','N/A'=>'');
////
////    $sql ="SELECT * FROM `data_actors_race` where
////    kairos_verdict!='W'
////and  kairos_verdict!='H'
////and  kairos_verdict!='B'
////and  kairos_verdict!='EA'
////limit 1000000";
////$row = Pdo_an::db_results_array($sql);
////
////foreach ($row as $r)
////{
////
////    $id = $r['id'];
////    $verdict = $r['kairos_verdict'];
////
////    if ($verdict)
////    {
////        $newverdict = $array_face[$verdict];
////        //update
////
////    }
////    else
////    {
////        //	Black	Hispanic	White	kairos_verdict	img_type	error_msg	last_update
////
////        $kairos = array('W' => $r['White'], 'H' => $r['Hispanic'],  'B'=>$r['Black'] , 'EA'=>$r['Asian']);
////
////        arsort($kairos);
////        $key = array_keys($kairos);
////        $newverdict =$key[0];
////    }
////$sql = "UPDATE `data_actors_race` SET kairos_verdict=?,`last_update`=? WHERE id=?";
////
////    Pdo_an::db_results_array($sql,array($newverdict,time(),$id));
////
////
////
////
////}
//
//
//}


function set_tmdb_actors_for_movies()
{
    !class_exists('TMDBIMPORT') ? include ABSPATH . "analysis/include/tmdb_import.php" : '';
    TMDBIMPORT::set_tmdb_actors_for_movies();

}


function download_crowd_images()
{
    echo 'download_crowd_images<br>';

$sql ="SELECT * FROM `data_actors_crowd` WHERE `image`!='' and `loaded` IS NULL and `status` = 1 limit 10";
    $row = Pdo_an::db_results_array($sql);

    $count = count($row);

    foreach ($row as $r)
    {

        $actor_id = $r['actor_id'];
        $image = $r['image'];


        ///add image to folder
        if (strstr($image,'.jpg') && check_image_on_server($actor_id, $image,'_crowd'))
        {
        ///update data
           $loaded = 1;

        }
        else
        {
            $loaded=0;
        }
        echo  $r['id'].' => '.$loaded.'<br>';

        $sql ="UPDATE `data_actors_crowd` SET `loaded`={$loaded} WHERE `id`=".$r['id'];
        Pdo_an::db_query($sql);

        ///update actor meta
        $sql2 = "UPDATE `data_actors_meta` SET `last_update` = '".time()."' WHERE `data_actors_meta`.`actor_id` = '".$r['actor_id']."'";
        Pdo_an::db_query($sql2);

        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'update', 'data_actors_meta', array('actor_id' => $r['actor_id']), 'actor_meta',9);

    }


}


function add_tmdb_without_id()
{
    TMDB::add_tmdb_without_id($_GET['add_tmdb_without_id']);

}

function update_tmdb_actors($id='')
{
    TMDB::update_tmdb_actors($id);
}

function check_tmdb_data($id='')
{
    TMDB::check_imdb_data($id);
}

function get_coins_data()
{
    !class_exists('GETCOINS') ? include ABSPATH . "analysis/include/get_coin_info.php" : '';

    GETCOINS::get_request();


}

function update_all_rwt_rating($force='')
{
    !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';

    ///get option

    global $table_prefix;

    $sql="SELECT option_value FROM `{$table_prefix}options` WHERE `option_name` = 'movies_raiting_weight' ";
    $r = Pdo_wp::db_fetch_row($sql);
    if ($r)
    {
     $value =    $r->option_value;
        $value= unserialize($value);

        $rwt_array= $value["rwt"];

      ///  var_dump($rwt_array);
    }

    if ($force)
    {
        $sql = "SELECT `data_movie_imdb`.id  FROM `data_movie_imdb`";
    }
    else
    {
        $last_update_min = time()-86400;
        $last_update = time()-86400*30;

        $sql = "SELECT `data_movie_imdb`.id  FROM `data_movie_imdb` LEFT JOIN data_movie_rating 
    ON `data_movie_imdb`.id=data_movie_rating.movie_id
        WHERE   (
            data_movie_rating.id IS NULL 
                OR (data_movie_rating.last_update < {$last_update} )
                OR (`data_movie_imdb`.rating>0  AND data_movie_rating.imdb ='')
            ) order by `data_movie_rating`.last_update desc  limit 1000";
//echo $sql;
    }


    $rows = Pdo_an::db_results_array($sql);
    $count = count($rows);
    $i=0;
    foreach ($rows as $r2)
    {
        $i++;
        $id = $r2['id'];

        PgRatingCalculate::add_movie_rating($id,$rwt_array);

        echo $i.' of '.$count.' id='.$id.'<br>'.PHP_EOL;
    }

}



function add_gender_rating()
{
    !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';
    $data = new RWT_RATING;


    $sql = "SELECT `data_movie_imdb`.id  FROM `data_movie_imdb` LEFT JOIN cache_rating 
    ON `data_movie_imdb`.id=cache_rating.movie_id
        WHERE   cache_rating.id IS NULL order by `data_movie_imdb`.id desc  limit 200";
    ////  echo $sql;
    $rows = Pdo_an::db_results_array($sql);
    $count = count($rows);
    $i=0;
    foreach ($rows as $r2)
    {
        $i++;
        $id = $r2['id'];
        $data->gender_and_diversity_rating($id);
        echo $i.' of '.$count.' id='.$id.'<br>'.PHP_EOL;
    }
}

function get_imdb_actor_parse_inner($content)
{

    $regv = '/\>Birth Name\<\/td\>\<td\>([^\<]+)/';
    if (preg_match($regv, $content, $mach)) {
        $array_result['burn_name'] = trim($mach[1]);
    }
    $regv = "/image_src\' href\=\\\"([^\\\"]+)/";

    if (preg_match($regv, $content, $mach)) {

        $image = trim($mach[1]);
        if (!strstr($image, 'imdb_logo')) {
            $array_result['image'] = $image;
        }
    }

    $regv = '/"title" content="([^\>]+)/';

    if (preg_match($regv, $content, $mach)) {
        $array_result['name'] = trim($mach[1]);

        $array_result['name'] = substr($array_result['name'], 0, strpos($array_result['name'], '- IMDb"'));
        $array_result['name'] = trim($array_result['name']);

    }
    $regv = '/\<time datetime="([^"]+)/';

    if (preg_match($regv, $content, $mach)) {
        $array_result['birthDate'] = trim($mach[1]);
    }


    $regv = '/birth_place[^\>]+\>([^\>]+)\</';

    if (preg_match($regv, $content, $mach)) {
        $array_result['burn_place'] = trim($mach[1]);
    }
    $regv = '/description" content="([^"]+)/';

    if (preg_match($regv, $content, $mach)) {
        $array_result['description'] = trim($mach[1]);
    }
//  var_dump($array_result);
    /// echo $content;


    return $array_result;

}
function get_imdb_actor_parse($content)
{
    $pos = '<script type="application/ld+json">';

    $content = substr($content, strpos($content, $pos));


    $pos = "</script>";

    $script = substr($content, 35, strpos($content, $pos) - 35);
    $array = [];

    $array = json_decode($script, JSON_FORCE_OBJECT);


    $reg_v = '/name\?birth_place\=[^\>]+\>([^\<]+)/';

    if (preg_match($reg_v, $content, $mach)) {
        $array['burn_place'] = trim($mach[1]);
    }


    return ($array);

}

function check_image_on_server($actor_id, $image = '', $tmdb = '')
{
 !class_exists('KAIROS') ? include ABSPATH . "analysis/include/kairos.php" : '';
 return   KAIROS::check_image_on_server($actor_id, $image, $tmdb);

}
function addto_db_actors($actor_id, $array_result, $update = 0)
{


    /// print_r($array_result);


    $name = '';
    if (isset($array_result['name'])) {
        $name = $array_result['name'];
        unset($array_result['name']);
    }


    $description = '';
    if (isset($array_result['description'])) {
        $description = $array_result['description'];
        unset($array_result['description']);
    }
    $birthDate = '';
    if (isset($array_result['birthDate'])) {
        $birthDate = $array_result['birthDate'];
        unset($array_result['birthDate']);
    }
    $burn_place = '';
    if (isset($array_result['burn_place'])) {
        $burn_place = $array_result['burn_place'];
        unset($array_result['burn_place']);
    }
    $burn_name = '';
    if (isset($array_result['burn_name'])) {
        $burn_name = $array_result['burn_name'];
        unset($array_result['burn_name']);
    }


    $image = '';
    if (isset($array_result['image'])) {
        $image = $array_result['image'];


        if (check_image_on_server($actor_id, $image)) {
            $image = 'Y';

            if ($name)
            {
                !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';
                ACTIONLOG::update_actor_log('image');

            }

        } else {
            $image = 'N';
        }


        unset($array_result['image']);
    } else {
        $image = 'N';
    }

    if ($name)
    {
        !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';
        ACTIONLOG::update_actor_log('name');

    }



    if ($update) {
        $array_request = array($name, $burn_name, $burn_place, $birthDate, $description, $image, time());
        $sql = "UPDATE `data_actors_imdb` SET
               `name`=?, `birth_name`=?, `birth_place`=?, `burn_date`=?, `description`=?, `image`=?, `lastupdate`=?
WHERE `data_actors_imdb`.`id` = " . $actor_id;

        Pdo_an::db_results_array($sql,$array_request);
        echo 'updated ' . $actor_id . '<br>' . PHP_EOL;
    }
    else
    {
        $array_request = array($name, $burn_name, $burn_place, $birthDate, $description, $image, time());
        $sql = "INSERT INTO `data_actors_imdb` VALUES ( '" . $actor_id . "' ,?, ?, ?, ?, ?, ?, ?)";
        Pdo_an::db_results_array($sql,$array_request);
        echo 'adedded ' . $actor_id . '<br>' . PHP_EOL;

    }

    !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';

    Import::create_commit('', 'update', 'data_actors_imdb', array('actor_id' => $actor_id), 'actor_update',5);


    return 1;
}

function add_actors_to_db($id, $update = 0)
{

    $final_value = sprintf('%07d', $id);


    $url = 'https://www.imdb.com/name/nm' . $final_value . '/bio/';

   //echo $url . PHP_EOL;

    $result = GETCURL::getCurlCookie($url);

    $array_result = get_imdb_actor_parse_inner($result);

    if ($array_result) {

        return addto_db_actors($id, $array_result, $update);


    } else {

        $sql = "UPDATE `data_actors_imdb` SET `lastupdate` = '".time()."' WHERE `data_actors_imdb`.`id` = {$id}";
        Pdo_an::db_query($sql);
    }

}

function intconvert($data)
{
    $result=0;

    $array_int_convert = array('W'=>1,'EA'=>2,'H'=>3,'B'=>4,'I'=>5,'M'=>6,'MIX'=>7,'JW'=>8,'NJW'=>9,'IND'=>10);

    if ($array_int_convert[$data])
    {
        $result = $array_int_convert[$data];
    }

   return $result;
}

function check_last_actors()
{

    !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';

    ACTIONLOG::clear_history();

    set_time_limit(600);

    $commit_actors = [];

    //check actor gender
    $sql = "SELECT data_actors_gender.actor_id,  data_actors_gender.Gender 	  FROM `data_actors_gender`
    LEFT JOIN data_actors_meta ON data_actors_gender.actor_id=data_actors_meta.actor_id
        WHERE data_actors_meta.gender IS NULL and data_actors_meta.actor_id >0  limit 10000";
    $result= Pdo_an::db_results_array($sql);
    foreach ($result as $r) {
        $gender = '';

        if ($r['Gender'] == 'm') {
            $gender = 2;
        } else if ($r['Gender'] == 'f') {
            $gender = 1;
        } else  {
            $gender = 0;
        }

        $i++;
        $sql1 = "UPDATE `data_actors_meta` SET `gender` = '" . $gender . "'  ,`last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";

        $commit_actors[$r['actor_id']]=1;

       /// echo $sql1.'<br>';

        Pdo_an::db_query($sql1);
        ACTIONLOG::update_actor_log('gender');
    }
    echo 'check actor gender (' . $i . ')' . PHP_EOL;


    //check actor gender auto
    $sql = "SELECT data_actor_gender_auto.actor_id,  data_actor_gender_auto.gender 	  FROM `data_actor_gender_auto`
    LEFT JOIN data_actors_meta ON data_actor_gender_auto.actor_id=data_actors_meta.actor_id
        WHERE (data_actors_meta.gender IS NULL OR data_actors_meta.gender =0 ) and data_actors_meta.actor_id >0  and data_actor_gender_auto.gender>0 limit 10000";
    $result= Pdo_an::db_results_array($sql);
    foreach ($result as $r) {
        $gender = '';


        if ($r['gender'] == 1) {
            $gender = 2;///male
        } else if ($r['gender'] == 2) {
            $gender = 1;///female
        } else  {
            $gender = 0;
        }

        $i++;
        $sql1 = "UPDATE `data_actors_meta` SET `gender` = '" . $gender . "'  ,`last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";

        /// echo $sql1.'<br>';

        Pdo_an::db_query($sql1);
        ACTIONLOG::update_actor_log('gender');

        $commit_actors[$r['actor_id']]=1;
    }
    echo 'check actor gender auto (' . $i . ')' . PHP_EOL;





    $array_min = array('Asian' => 'EA', 'White' => 'W', 'Latino' => 'H', 'Black' => 'B', 'Arab' => 'M', 'Dark Asian' => 'I',
        'Jewish' => 'JW', 'Other' => 'MIX', 'Mixed / Other' => 'MIX', 'Indigenous' => 'IND',
        'Not a Jew' => 'NJW', 'Sadly, not' => 'NJW','Barely a Jew'=>'JW',
        'Borderline Jew'=>'JW','Jew'=>'JW','Sadly, a Jew'=>'JW','Infinitesimally a Jew'=>'JW','Sadly, not a Jew'=>'NJW');
    global $array_compare;
    if (!$array_compare)
    {
        $array_compare = TMDB::get_array_compare();
    }
//        $array_total_v=[];
//    //data_actors_meta.jew IS NULL and
//    ////check actor jew
//    $sql = "SELECT data_actors_jew.actor_id , data_actors_jew.Verdict FROM `data_actors_jew` LEFT JOIN data_actors_meta ON data_actors_jew.actor_id=data_actors_meta.actor_id
//        WHERE  data_actors_meta.actor_id >0  and data_actors_jew.actor_id  !=0";
//    $result= Pdo_an::db_results_array($sql);
//    foreach ($result as $r) {
//
//        $verdict = '';
//        if ($r['Verdict']) {
//            $verdict = $r['Verdict'];
//            $verdict =trim($verdict);
//
//        }
//        if ($array_min[$verdict]) {
//            $verdict = $array_min[$verdict];
//        }
//
//      ///  else if ($verdict)     {$verdict='NJW';}
//
//        if (!$verdict) {
//            $verdict = 0;
//        }
//        $array_total_v[$verdict]+=1;
//        $i++;
//        $sql1 = "UPDATE `data_actors_meta` SET `jew` = '" . $verdict . "' WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";
//        Pdo_an::db_query($sql1);
//        update_actors_verdict($r['actor_id']);
//    }
//    echo 'check actor jew (' . $i . ')' . PHP_EOL;
//    print_r($array_total_v);





    //////check actors meta

    $sql = "SELECT `data_actors_imdb`.id FROM `data_actors_imdb`
        LEFT JOIN `data_actors_meta` ON `data_actors_imdb`.id=`data_actors_meta`.actor_id
        WHERE `data_actors_meta`.actor_id IS NULL LIMIT 10000";
    $i = 0;
    $result= Pdo_an::db_results_array($sql);
    foreach ($result as $r) {
        $i++;
        $sql1 = "INSERT INTO `data_actors_meta` (`id`, `actor_id`)  VALUES (NULL, '" . $r['id'] . "')";
        Pdo_an::db_query($sql1);

        ACTIONLOG::update_actor_log('data_actors_meta');
        $commit_actors[$r['id']]=1;
    }
    echo 'check actors meta (' . $i . ')' . PHP_EOL;


    $sql = "SELECT id FROM `data_actors_imdb` where lastupdate = '0' order by id asc limit 200";
    ///echo $sql.PHP_EOL;
    $result= Pdo_an::db_results_array($sql);

    $i = 0;
    foreach ($result as $r) {
        $i++;
        $id = $r['id'];

        echo 'try add actor ' . $id . PHP_EOL;

        $result = add_actors_to_db($id, 1);

        ///  set_option(8, $r['id']);


    }


    echo 'check_last_actors (' . $i . ') ' . PHP_EOL;


    //////check actors surname
    $i = 0;
    $sql = "SELECT data_actors_surname.wiki_data, `data_actors_surname`.actor_id FROM `data_actors_meta` ,data_actors_surname 
        WHERE `data_actors_meta`.actor_id=`data_actors_surname`.actor_id
        AND `data_actors_meta`.surname  = 1 LIMIT 10000";
    $result= Pdo_an::db_results_array($sql);
    foreach ($result as $r) {

        $meta_result = get_actor_result($r['wiki_data']);
        $i++;

        if ($meta_result) {




            $sql1 = "UPDATE `data_actors_meta` SET 
                              `surname` = '" . $meta_result . "',
                              `n_surname` = '" . intconvert($meta_result) . "',
              
              
              `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";
            Pdo_an::db_query($sql1);
            update_actors_verdict($r['actor_id']);
            ACTIONLOG::update_actor_log('data_actors_surname');

            $commit_actors[$r['actor_id']]=1;
        }

    }
    echo 'check actors surname (' . $i . ')' . PHP_EOL;

    $i = 0;
//    ////check actor face
//    $sql = "SELECT data_actors_face.actor_id  FROM `data_actors_face` LEFT JOIN data_actors_meta ON data_actors_face.actor_id=data_actors_meta.actor_id
//        WHERE data_actors_meta.bettaface IS NULL and data_actors_meta.actor_id >0  limit 300";
//    $result= Pdo_an::db_results_array($sql);
//    foreach ($result as $r) {
//
//        $verdict = get_verdict($r['actor_id']);
//
//        if ($verdict) {
//            $enable_image = $verdict;
//        } else {
//            $enable_image = 1;
//        }
//
//
//        $i++;
//        $sql1 = "UPDATE `data_actors_meta` SET `bettaface` = '" . $enable_image . "'  ,`last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";
//        Pdo_an::db_query($sql1);
//        update_actors_verdict($r['actor_id']);
//        ACTIONLOG::update_actor_log('bettaface');
//    }
//    echo 'check actor face (' . $i . ')' . PHP_EOL;

    $i = 0;
    ////check actor kairos tmdb

    $sql = "SELECT data_actors_race.actor_id , data_actors_race.kairos_verdict  FROM `data_actors_race` LEFT JOIN data_actors_meta ON data_actors_race.actor_id=data_actors_meta.actor_id
        WHERE data_actors_meta.kairos IS NULL and data_actors_meta.actor_id >0 and  data_actors_race.kairos_verdict !='' limit 300";
    $result= Pdo_an::db_results_array($sql);
    foreach ($result as $r) {
        $kairos = $r['kairos_verdict'];
        $i++;
        $sql1 = "UPDATE `data_actors_meta` SET `kairos` = '" . $kairos . "'  ,
        `n_kairos` = '" . intconvert($kairos) . "',
        
        `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";
        Pdo_an::db_query($sql1);
        update_actors_verdict($r['actor_id']);
        ACTIONLOG::update_actor_log('kairos');

        $commit_actors[$r['actor_id']]=1;
    }
    echo 'check actor kairos imdb (' . $i . ')' . PHP_EOL;

    $i = 0;


    /////////crowd
    $sql = "SELECT data_actors_crowd_race.actor_id, data_actors_crowd_race.kairos_verdict  FROM `data_actors_crowd_race` 
    LEFT JOIN data_actors_meta ON data_actors_crowd_race.actor_id=data_actors_meta.actor_id
        WHERE (data_actors_meta.kairos IS NULL) 
          and data_actors_meta.actor_id >0 
          and  data_actors_crowd_race.kairos_verdict !=''
           limit 100";

    $result= Pdo_an::db_results_array($sql);
    $i =count($result);

    foreach ($result as $r) {
        $kairos = $r['kairos_verdict'];
        $i++;
        $sql1 = "UPDATE `data_actors_meta` SET `kairos` = '" . $kairos . "'  ,
         `n_kairos` = '" . intconvert($kairos) . "',
         
        `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";


        ///echo $sql1;

        Pdo_an::db_query($sql1);
        update_actors_verdict($r['actor_id']);
        ACTIONLOG::update_actor_log('kairos');

        $commit_actors[$r['actor_id']]=1;
    }
    echo 'check actor kairos crowd (' . $i . ')' . PHP_EOL;



    $sql = "SELECT data_actors_tmdb_race.actor_id, data_actors_tmdb_race.kairos_verdict  FROM `data_actors_tmdb_race` 
    LEFT JOIN data_actors_meta ON data_actors_tmdb_race.actor_id=data_actors_meta.actor_id
        WHERE (data_actors_meta.kairos IS NULL OR data_actors_meta.kairos!=data_actors_tmdb_race.kairos_verdict) 
          and data_actors_meta.actor_id >0 
          and  data_actors_tmdb_race.kairos_verdict !=''
           limit 300";

    $result= Pdo_an::db_results_array($sql);
    $i =count($result);
    foreach ($result as $r) {
        $kairos = $r['kairos_verdict'];
        $i++;
        $sql1 = "UPDATE `data_actors_meta` SET `kairos` = '" . $kairos . "'  ,
         `n_kairos` = '" . intconvert($kairos) . "',
         
        `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";
        Pdo_an::db_query($sql1);
        update_actors_verdict($r['actor_id']);
        ACTIONLOG::update_actor_log('kairos');

        $commit_actors[$r['actor_id']]=1;
    }
    echo 'check actor kairos tmdb (' . $i . ')' . PHP_EOL;


    $i = 0;
    ////check actor ethnic
    ///
    ///


//    $sql = "SELECT data_actors_ethnic.*  FROM `data_actors_ethnic` LEFT JOIN data_actors_meta ON data_actors_ethnic.actor_id=data_actors_meta.actor_id
//        WHERE data_actors_meta.ethnic IS NULL and data_actors_ethnic.verdict IS NOT NULL limit 300";
//    $result= Pdo_an::db_results_array($sql);
//    foreach ($result as $r) {
//
//
//        $actor_id=  $r['actor_id'];
//        $verdict=  $r['verdict'];
//        if ($verdict)
//        {
//            $verdict_result = $array_min[$verdict];
//
//        }
//
//        if ($verdict_result)
//        {
//            //echo $verdict.'=>'.$verdict_result.' '.PHP_EOL;
//
//            $sql1 = "UPDATE `data_actors_meta` SET `ethnic` = '" . $verdict_result . "' WHERE `data_actors_meta`.`actor_id` = '" . $actor_id . "'";
//            Pdo_an::db_query($sql1);
//            $i++;
//
//            update_actors_verdict($actor_id);
//        }
//
//    }
//    echo 'check actor ethnic (' . $i . ')' . PHP_EOL;
//    $i = 0;






    $sql = "SELECT id FROM `data_actors_imdb` where `name` = '' and lastupdate != '0' and lastupdate < ".(time()-86400)." order by lastupdate 	 desc limit 10";
    ///echo $sql.'<br>';

    $result= Pdo_an::db_results_array($sql);
    $i = 0;
    foreach ($result as $r) {
        $i++;
        $id = $r['id'];
        echo 'try add actor ' . $id . PHP_EOL;
        $result = add_actors_to_db($id, 1);
        ///  set_option(8, $r['id']);
        $sql = "UPDATE `data_actors_imdb` SET `lastupdate` = '".time()."' WHERE `data_actors_imdb`.`id` =  ".$id;
        Pdo_an::db_results_array($sql);
    }
    echo 'check_last_actors status 2 (' . $i . ') ' . PHP_EOL;



    if ( $commit_actors)
    {
        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';

        foreach ($commit_actors as $actor_id=>$enable)
        {
         Import::create_commit('', 'update', 'data_actors_meta', array('actor_id' => $actor_id), 'actor_meta',6);

        }
    }
}


function force_surname_update()
{
    set_time_limit(0);
    echo 'check actors surname <br>'.PHP_EOL;
    //////check actors surname
    $i = 0;
    $sql = "SELECT data_actors_surname.wiki_data, `data_actors_surname`.actor_id FROM data_actors_surname";
    $result= Pdo_an::db_results_array($sql);
    $count = count($result);
    foreach ($result as $r) {

        $meta_result = get_actor_result($r['wiki_data']);
        $i++;

        if ($meta_result) {

            $sql1 = "UPDATE `data_actors_meta` SET `surname` = '" . $meta_result . "'  ,`last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";
            Pdo_an::db_query($sql1);
            update_actors_verdict($r['actor_id']);
        }
        echo $r['actor_id']. ' '.$meta_result. ' (' . $i . ' / '.$count.') <br>' . PHP_EOL;
    }


}


function get_actor_result($data)
{
    $actor_data = [];
    if ($data) {
        $data = json_decode($data);


        $actor_data['EA'] += (float)$data[4] * 100;// "Asian,GreaterEastAsian,EastAsian",
        $actor_data['EA'] += (float)$data[5] * 100;//   "Asian,GreaterEastAsian,Japanese",
        $actor_data['I'] = (float)$data[6] * 100;// "Asian,IndianSubContinent",
        $actor_data['B'] = (float)$data[7] * 100;// "GreaterAfrican,Africans",

        $actor_data['M'] = (float)$data[8] * 100;// "GreaterAfrican,Muslim",
        $actor_data['W'] += (float)$data[9] * 100;// "GreaterEuropean,British",
        $actor_data['W'] += (float)$data[10] * 100;// "GreaterEuropean,EastEuropean",

        $actor_data['JW'] = (float)$data[11] * 100;// "GreaterEuropean,Jewish",
        $actor_data['W'] += (float)$data[12] * 100;// "GreaterEuropean,WestEuropean,French",
        $actor_data['W'] += (float)$data[13] * 100;// "GreaterEuropean,WestEuropean,Germanic",
        $actor_data['H'] += (float)$data[14] * 100;// "GreaterEuropean,WestEuropean,Hispanic",
        $actor_data['W'] += (float)$data[15] * 100;// "GreaterEuropean,WestEuropean,Italian",
        $actor_data['W'] += (float)$data[16] * 100;// "GreaterEuropean,WestEuropean,Nordic"]]

    }

    arsort($actor_data);
    $key = array_keys($actor_data);
    $surname = $key[0];
    if ($surname) {
        return $surname;
    }


}
function get_verdict($actor_id)
{
     $sql0 = "SELECT  race  FROM data_actors_face  where actor_id =" . $actor_id . " LIMIT 1";
    $r=Pdo_an::db_fetch_row($sql0);
    $verdict = $r->race;
    //echo 'verdict='.$verdict.PHP_EOL;
    $array_face = array('white' => 'W', 'hispanic' => 'H', 'black' => 'B', 'mideast' => 'M', 'indian' => 'I', 'asian' => 'EA');
    if ($array_face[$verdict]) {
        $verdict = $array_face[$verdict];
    }

    return $verdict;
}


function update_all_pg_rating()
{

    !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';


    set_time_limit(0);
    $sql ="SELECT * FROM `data_pg_rating` ORDER BY `data_pg_rating`.`id` ASC ";
    $rows = Pdo_an::db_results_array($sql);

    $count = count($rows);
   $i =0;

    foreach ($rows as $r )
    {

        $id =$r['id'];
        $movie_id =$r['movie_id'];
        $title =$r['movie_title'];


        $rating = PgRatingCalculate::CalculateRating($movie_id,$id,0,1);

        echo '<span style="display: inline-block; width: 120px">'.$i.' of '.$count.'</span><span style="display: inline-block; width: 80px">'.$movie_id.'</span><span style="display: inline-block; width: 400px">'.$title.'</span><span style="display: inline-block; width: 100px">'.$rating.'</span><br><hr>'.PHP_EOL;
        $i++;


//        ///check rwt id
//        $sql1 = "SELECT `rwt_id` FROM `data_movie_imdb` WHERE `movie_id` = ".$movie_id;
//        $r2 = Pdo_an::db_fetch_row($sql1);
//        $rwt_id_original = $r2->rwt_id;
//        if ($rwt_id_original!= $rwt_id)
//        {
//            $sql2 ="UPDATE `data_pg_rating` SET `rwt_id` = ? WHERE `data_pg_rating`.`movie_id` = ".$movie_id;
//            Pdo_an::db_results_array($sql2,array($rwt_id_original));
//        }

//
//        if (!$r['movie_title'])
//        {
//            ///add title
//          $sql1 = "SELECT `title` FROM `data_movie_imdb` WHERE `movie_id` = ".$movie_id;
//            $r2 = Pdo_an::db_fetch_row($sql1);
//            $title = $r2->title;
//            $sql2 ="UPDATE `data_pg_rating` SET `movie_title` = ? WHERE `data_pg_rating`.`movie_id` = ".$movie_id;
//            Pdo_an::db_results_array($sql2,array($title));
//        }


    }

}
function update_pgrating($imdb_id='')
{
    !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';

    PgRatingCalculate::CalculateRating($imdb_id, '', 1);


}



function set_actors_ethnic($id)
{
    !class_exists('Ethinc') ? include ABSPATH . "analysis/include/ethnic.php" : '';

    if (isset($_GET['update']))
    {
        Ethinc::update_verdict_meta($id);
    }
    else
    {
        Ethinc::set_actors_ethnic($id);
    }
}



function add_gender_rating_for_new_movies()
{
    !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';
    $RWT_RATING = new RWT_RATING;


    $sql = "SELECT * FROM `options` where id = 13 OR id = 14";
    $rows = Pdo_an::db_results_array($sql);
    foreach ($rows as $r)
    {
        $data =  $r['val'];
        if ($data)
        {
            $array_movies = json_decode($data, 1);
            //print_r($array_movies);
            foreach ($array_movies as $movie_id=>$date)
            {
                $RWT_RATING->gender_and_diversity_rating($movie_id,'',1);
            }
        }

    }

}
function add_pg_rating_for_new_movies()
{

    !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';

    $sql = "SELECT * FROM `options` where id = 13 OR id = 14";
    $rows = Pdo_an::db_results_array($sql);
    foreach ($rows as $r)
    {
       $data =  $r['val'];
       if ($data)
       {
           $array_movies = json_decode($data, 1);
           //print_r($array_movies);
           foreach ($array_movies as $movie_id=>$date)
           {

               $imdb_id = TMDB::get_imdb_id_from_id($movie_id);
               //echo $imdb_id.' ';
               PgRating::add_pgrating($imdb_id,1);

           }
       }

    }

}


function add_pgrating($imdb_id='')
{

    !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';

    PgRating::add_pgrating($imdb_id,1);

    return;
}

function import_from_rwt($id='')
{
    return;
    if ($_GET['delete']==1) {

        $sql = "SELECT * FROM `data_rwt_movie_export` order by  movie_title asc";
        $last_data = [];
        $rows = Pdo_an::db_results_array($sql);
        foreach ($rows as $r) {
            $id = $r['id'];
            $movie_title = $r['movie_title'];


            if ($movie_title == $movie_id_last && $r['rwt_tmdb'] == $last_data['rwt_tmdb']) {
                echo '<br>Origin id=' . $last_data['id'] . ' tmdb_id ' . $last_data['rwt_tmdb'] . ' ' . $last_data['movie_title'] . '  <br>' . PHP_EOL;
                echo 'found id= ' . $id . ' tmdb_id ' . $r['rwt_tmdb'] . ' ' . $r['movie_title'] . '  deleted  <br>' . PHP_EOL;

                if ($_GET['delete'] == 1) {
                    $sql = "DELETE FROM `data_rwt_movie_export` WHERE `id` = " . $id;
                    Pdo_an::db_query($sql);
                }

            } else {
                $last_data = $r;
                $movie_id_last = $movie_title;
            }


        }
return;
    }



    $debug = 0;

  ///  !class_exists('GETNEWMOVIES') ? include ABSPATH . "analysis/include/get_new_movies.php" : '';
    global $table_prefix;
    $where = '';
    if ($id) {
        $where = " and ID= " . $id . " ";
    }
    $sql = "select * from {$table_prefix}posts where post_type ='movie' and post_status='publish' " . $where . " limit 50000";
    $array = Pdo_wp::db_results_array($sql);
    foreach ($array as $r) {
        $id = $r['ID'];
        $post_title = $r['post_title'];
        $post_name = $r['post_name'];
        if ($debug) echo $id . ' ' . $post_title . ' <br>';
        $sql = "select * from {$table_prefix}postmeta  where post_id = {$id}";
        $rows = Pdo_wp::db_results_array($sql);

        $tmdb_id = '';
        $imdb_id = '';
        $title = '';

        foreach ($rows as $meatadata) {
            $key = $meatadata["meta_key"];
            $val = $meatadata["meta_value"];
            // echo $key.' => '.$val.'<br>';
            if ($key == '_wpmoly_movie_trailers') {
                continue;
            }
            if (strstr($key, '_wpmoly_movie_')) {
                $key = substr($key, 14);
                $array_metavalue[$key] = $val;
            }
            if (strstr($key, '_thumbnail_id')) {
                $array_metavalue[$key] = $val;
            }

        }

        ////check imdb_id

        $tmdb_id = $array_metavalue['tmdb_id'];
        $imdb_id = $array_metavalue['imdb_id'];


        if ($imdb_id) {
            if (strstr($imdb_id, 'tt')) {
                $imdb_id = substr($imdb_id, 2);
            }
            $imdb_id = intval($imdb_id);
        }

        // $title = $array_metavalue['title'];
//        if (!$title)
//        { $title = $post_title; }
        $title = $post_title;

        $movie_data = json_encode($array_metavalue);
        //add_to_db

        $sql = "SELECT id FROM `data_rwt_movie_export` where rwt_id ='" . $id . "'  limit 1 ";
        /// echo $sql;
        $rdb = Pdo_an::db_fetch_row($sql);
        if ($rdb) {

            $temp_id = $rdb->id;
        } else {
            $sql = "INSERT INTO `data_rwt_movie_export`
    (`id`, `movie_title`, `imdb_title`, `rwt_id`, `rwt_tmdb`, `rwt_imdb`, `enable`, `compare_data`, `movie_data`, `last_update`)
         VALUES (NULL, ?, NULL , '{$id}', '{$tmdb_id}', '{$imdb_id}', NULL, NULL, ?, ?);";
            ///  echo $sql;
            $result = Pdo_an::db_results_array($sql, array($title, $movie_data, time()));
            continue;
        }

    }



    return;
}
function import_from_rwt_3()
{
    $debug='';

    $sql="select * from data_rwt_movie_export where enable = 5 or enable = 4 OR  enable = 3  OR  enable = 2   limit 1000";
    $array_movie = Pdo_an::db_results_array($sql);

    foreach ($array_movie as $movie_data) {
        $row_id = $movie_data['id'];
        $tmdb_id = $movie_data['rwt_tmdb'];
        $imdb_id = $movie_data['rwt_imdb'];
        $id = $movie_data['rwt_id'];
        $title = $movie_data['movie_title'];
        $title2 = $movie_data['imdb_title'];
        $movie_array = json_decode($movie_data['movie_data'], 1);
        $release_date = $movie_array['release_date'];

        /////////try find on imdb

        $result_data_array =TMDB::get_data($title,'all');

      if (is_array($result_data_array))
      {

          if ($result_data_array[$imdb_id])
          {

                  echo 'found '.$imdb_id.'<br>';
                  $add =    TMDB::check_and_add_to_imdb_db($imdb_id);
                  if ($add==1)
                  {
                      echo 'adedded '.$imdb_id.'<br>';
                  }

                  $sql ="UPDATE `data_rwt_movie_export` SET `enable` = '1'  WHERE `data_rwt_movie_export`.`id` = {$row_id}; ";
                  Pdo_an::db_results_array($sql);

                  $sql = "UPDATE `data_movie_imdb` SET `rwt_id` = '" . $id . "' WHERE `data_movie_imdb`.`movie_id`=" . $imdb_id;
                  Pdo_an::db_query($sql);
         }
      }
    }
}
function import_from_rwt_2($id='')
    {
        $debug='';

        $sql="select * from data_rwt_movie_export where enable =3 OR  enable = 4 limit 100";
        $array_movie = Pdo_an::db_results_array($sql);

        foreach ($array_movie as $movie_data) {
            $row_id = $movie_data['id'];
            $tmdb_id = $movie_data['rwt_tmdb'];
            $imdb_id = $movie_data['rwt_imdb'];
            $id = $movie_data['rwt_id'];
            $title = $movie_data['movie_title'];
            $title2 = $movie_data['imdb_title'];
            $movie_array = json_decode($movie_data['movie_data'], 1);
            $release_date = $movie_array['release_date'];

            $title_conv = TMDB::replace_movie_text($title,1);
            $title2_conv = TMDB::replace_movie_text($title2,1);

            if ($title2_conv==$title_conv || strstr($title_conv,$title2_conv) || strstr($title2_conv,$title_conv)) {

                echo $title . '==' . $title2 . '<br>';
                $sql ="UPDATE `data_rwt_movie_export` SET `enable` = '1'  WHERE `data_rwt_movie_export`.`id` = {$row_id}; ";
                Pdo_an::db_results_array($sql);

                $sql = "UPDATE `data_movie_imdb` SET `rwt_id` = '" . $id . "' WHERE `data_movie_imdb`.`movie_id`=" . $imdb_id;
                Pdo_an::db_query($sql);

            }
            else
            {

                if ($tmdb_id)
                {
                    $imdb_id = TMDB::get_imdbid_from_tmdb($tmdb_id);
                    if ($imdb_id)
                    {
                        $array_movie =  TMDB::get_content_imdb($imdb_id);
                        $title2=$array_movie['title'];

                        $sql ="UPDATE `data_rwt_movie_export` SET `enable` = '5', `imdb_title` = ?, rwt_imdb = {$imdb_id} WHERE `data_rwt_movie_export`.`id` = {$row_id}; ";///new imdb id
                        Pdo_an::db_results_array($sql,array($title2));


                        $title_conv = TMDB::replace_movie_text($title,1);
                        $title2_conv = TMDB::replace_movie_text($title2,1);

                        if ($title2_conv==$title_conv || strstr($title_conv,$title2_conv) || strstr($title2_conv,$title_conv)) {

                            echo $title . '==' . $title2 . '<br>';
                            $sql ="UPDATE `data_rwt_movie_export` SET `enable` = '1'  WHERE `data_rwt_movie_export`.`id` = {$row_id}; ";
                            Pdo_an::db_results_array($sql);

                            $sql = "UPDATE `data_movie_imdb` SET `rwt_id` = '" . $id . "' WHERE `data_movie_imdb`.`movie_id`=" . $imdb_id;
                            Pdo_an::db_query($sql);

                        }

                    }

                }



             //  echo TMDB::replace_movie_text($title,1) . '!=' . TMDB::replace_movie_text($title2,1) . '!!!!!!!!!!<br>';
            }


        }

return;

        $sql="select * from data_rwt_movie_export where enable =2";
        $array_movie = Pdo_an::db_results_array($sql);

        foreach ($array_movie as $movie_data) {
            $row_id = $movie_data['id'];
            $tmdb_id = $movie_data['rwt_tmdb'];
            $imdb_id = $movie_data['rwt_imdb'];
            $id = $movie_data['rwt_id'];
            $title = $movie_data['movie_title'];
            $movie_array = json_decode($movie_data['movie_data'], 1);
            $release_date = $movie_array['release_date'];


            if ($tmdb_id) {

                $sql = "SELECT * FROM `data_movie_imdb` where `tmdb_id` ='" . $tmdb_id . "' ";

                $rtmdb = Pdo_an::db_results_array($sql);
                if ($rtmdb)
                {
                foreach ($rtmdb as $tdata)
                {
                    $title2 =$tdata['title'];
                    $imdb_id=$tdata['movie_id'];

                    if ($title) {

                        if (TMDB::replace_movie_text($title) == TMDB::replace_movie_text($title2)) {
                            echo $title . '==' . $title2 . '<br>';

                            $sql ="UPDATE `data_rwt_movie_export` SET `enable` = '1', `imdb_title` = ?, rwt_imdb = {$imdb_id} WHERE `data_rwt_movie_export`.`id` = {$row_id}; ";
                            Pdo_an::db_results_array($sql,array($title2));

                            $sql = "UPDATE `data_movie_imdb` SET `rwt_id` = '" . $id . "' WHERE `data_movie_imdb`.`movie_id`=" . $imdb_id;
                            Pdo_an::db_query($sql);
                        }
                        else
                        {
                            $sql ="UPDATE `data_rwt_movie_export` SET `enable` = '3'   WHERE `data_rwt_movie_export`.`id` = {$row_id}; ";///not compare
                            Pdo_an::db_results_array($sql);
                        }
                    }
                }
            }
                else
                    {
                        $sql ="UPDATE `data_rwt_movie_export` SET `enable` = '4'  WHERE `data_rwt_movie_export`.`id` = {$row_id}; ";////not found on imdb table
                        Pdo_an::db_results_array($sql);

                    }


            }


        }



            return;

        $sql="select * from data_rwt_movie_export where enable is NULL";
        $array_movie = Pdo_an::db_results_array($sql);

    foreach ($array_movie as $movie_data)
    {
        $row_id = $movie_data['id'];
        $tmdb_id = $movie_data['rwt_tmdb'];
        $imdb_id =  $movie_data['rwt_imdb'];
        $id =  $movie_data['rwt_id'];
        $title = $movie_data['movie_title'];
        $movie_array = json_decode($movie_data['movie_data'],1);
        $release_date =$movie_array['release_date'];


         if ($imdb_id) {

             $enable_in_db = TMDB::check_imdb_id($imdb_id);
             if ($enable_in_db) {
                 ///check title

                 if ($title) {
                     $title2 = TMDB::get_column_from_imdb_id($imdb_id, 'title');
                     if (TMDB::replace_movie_text($title) == TMDB::replace_movie_text($title2)) {
                         echo $title . '==' . $title2 . '<br>';

                         $sql ="UPDATE `data_rwt_movie_export` SET `enable` = '1', `imdb_title` = ? WHERE `data_rwt_movie_export`.`id` = {$row_id}; ";
                         Pdo_an::db_results_array($sql,array($title2));

                         $sql = "UPDATE `data_movie_imdb` SET `rwt_id` = '" . $id . "' WHERE `data_movie_imdb`.`movie_id`=" . $imdb_id;
                         Pdo_an::db_query($sql);
                     }
                     else
                     {
                         $sql ="UPDATE `data_rwt_movie_export` SET `enable` = '2' , `imdb_title` = ?  WHERE `data_rwt_movie_export`.`id` = {$row_id}; ";
                         Pdo_an::db_results_array($sql,array($title2));

                     }
                 }


             }

         }
         else
         {
             continue;
         }
        continue;

/*
        if ($imdb_id)
        {
             if ($debug)echo 'try find on imdb_id '.$imdb_id.'<br>';
            // $imdb_id = intval(substr($imdb_id,2));

             $add =    TMDB::check_and_add_to_imdb_db($imdb_id,$tmdb_id,$id,$title);
             if ($add && $add<3)
             {


                 if ($add==2) {
                     if ($debug)echo $imdb_id . ' enabled<br> ' . PHP_EOL;
                 }
                 else {echo $imdb_id . ' adedded<br> ' . PHP_EOL;}
                 continue;
             }
             if ($add==3) {

                 echo $id.' '.$post_title.' <br>';

                 $sql = "delete from {$table_prefix}postmeta where meta_key ='_wpmoly_movie_imdb_id' and post_id ='{$id}' ";
                 Pdo_wp::db_results_array($sql);
             echo $imdb_id . ' deleted from postmeta<br> ' . PHP_EOL;
             }
         }
         ///try find on tmdb

         if ($tmdb_id)
         {
             echo 'try find on tmdb_id '.$tmdb_id.'<br>';
             $an_id = TMDB::get_id_from_tmdbid($tmdb_id);
             if ($an_id)
             {
                 $imdb_id=   TMDB::get_imdb_id_from_id($an_id);
             }

             if (!$imdb_id) {
                 $imdb_id = TMDB::get_imdbid_from_tmdb($tmdb_id);
             }

             if ($imdb_id)
             {


              $add =  TMDB::check_and_add_to_imdb_db($imdb_id,$tmdb_id,$id,$title);
              if ($add < 3)
              {
                  if (!$array_metavalue['imdb_id']) {
                      $sql = "INSERT INTO {$table_prefix}postmeta  (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,?,?,?)";
                      Pdo_wp::db_results_array($sql, array($id, '_wpmoly_movie_imdb_id', 'tt' . $imdb_id));
                  }
                  if ($add==2) {echo $imdb_id . ' enabled<br> ' . PHP_EOL;}
                  else {echo $imdb_id . ' adedded<br> ' . PHP_EOL;}
                  continue;
              }
             }
         }
         ////try find on title

        $movie_date='';

        //echo '$release_date='.$release_date;

        if ($release_date) {
            $movie_date =date('Y',strtotime($release_date));
        }
         echo 'try find on movie name '.$title.' '.$movie_date.'<br>';
        $result_data_array =TMDB::get_data($title,'ft') ;


        //print_r($result_data_array);

        if (is_array($result_data_array))
        {
            $movie_name=$title;


            $imdb_id = GETNEWMOVIES::check_movie_coincidence($result_data_array,$movie_name,$movie_date);
            if ($imdb_id)
            {
                echo 'try add on movie name '.$title.' ' .$movie_date.'<br>';
                $add =    TMDB::check_and_add_to_imdb_db($imdb_id,$tmdb_id,$id,$title);
                if ($add < 3)
                {
                    if (!$array_metavalue['imdb_id'])
                    {
                        //add to post_meta
                        $sql = "INSERT INTO {$table_prefix}postmeta  (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL,?,?,?)";
                        Pdo_wp::db_results_array($sql,array($id,'_wpmoly_movie_imdb_id','tt'.$imdb_id));
                    }

                    if ($add==2) {echo $imdb_id . ' enabled<br> ' . PHP_EOL;}
                    else {echo $imdb_id . ' adedded<br> ' . PHP_EOL;}
                    continue;
                }

            }
        }
        echo 'add to db without imdb<br>';
        ///print_r($array_metavalue);

    }


        $sql="select * from data_rwt_movie_export where enable =3 OR  enable = 4";
        $array_movie = Pdo_an::db_results_array($sql);

        foreach ($array_movie as $movie_data) {
            $row_id = $movie_data['id'];
            $tmdb_id = $movie_data['rwt_tmdb'];
            $imdb_id = $movie_data['rwt_imdb'];
            $id = $movie_data['rwt_id'];
            $title = $movie_data['movie_title'];
            $movie_array = json_decode($movie_data['movie_data'], 1);
            $release_date = $movie_array['release_date'];
            $sql = "SELECT * FROM `data_movie_imdb` where `tmdb_id` ='" . $tmdb_id . "'  limit 1";

            $rtmdb = Pdo_an::db_results_array($sql);
            if ($rtmdb)
            {
                foreach ($rtmdb as $tdata)
                {
                    $title2 =$tdata['title'];
                    $imdb_id=$tdata['movie_id'];
                    $sql ="UPDATE `data_rwt_movie_export` SET  `imdb_title` = ?, rwt_imdb = {$imdb_id} WHERE `data_rwt_movie_export`.`id` = {$row_id}; ";
                    Pdo_an::db_results_array($sql,array($title2));

                }
            }

*/
        }

    }

function check_face()
{
    global $pdo;


    $sql = "SELECT `actor_id` FROM `data_actors_meta`  WHERE `bettaface` IS NULL LIMIT 100";

    //echo $sql;

    $q = $pdo->prepare($sql);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $i = 0;
    while ($r = $q->fetch()) {
        $i++;

        $actor_id = $r['actor_id'];

        $enable_image = check_bettaface($actor_id);

        if (!$enable_image) {
            $enable_image = 0;
        }
        echo $actor_id . ' image = ' . $enable_image . PHP_EOL;

        $verdict = get_verdict($actor_id);

        if ($verdict) {
            $enable_image = $verdict;
        }


        $sql1 = "UPDATE `data_actors_meta` SET `bettaface` = '" . $enable_image . "'  ,`last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $actor_id . "'";
        $q1 = $pdo->prepare($sql1);
        $q1->execute();
        update_actors_verdict($actor_id);
    }

}
function check_bettaface($actor_id)
{

    global $pdo;
    $sql = "SELECT  id  FROM data_actors_face where actor_id =" . $actor_id . " LIMIT 1";

    $q = $pdo->prepare($sql);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);

    $row = $q->fetch();
    $id = $row['id'];

    if ($id) {
        echo $actor_id . ' alredy in db ' . PHP_EOL;
        return 1;
    } else {
        ///check enable photo
        $sql = "SELECT  image  FROM data_actors_imdb  where id =" . $actor_id . " LIMIT 1";

        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        $row = $q->fetch();
        $images = $row['image'];
        if ($images == 'Y') {
            ////try add actor data to db

            echo $actor_id . ' get data from ' . $actor_id . '<br>' . PHP_EOL;
            $img_64 = create_image_64($actor_id);
            if ($img_64) {
                sleep(1);
                $array_race = get_actor_race($img_64);

                /// var_dump($array_race);

                //////update bd
                if ($array_race[0]) {
                    add_toracebd($actor_id, $array_race);
                    echo $actor_id . ' add ' . PHP_EOL;


                    return 1;

                } else {
                    echo 'error get ethnic data  ' . PHP_EOL;
                }

            } else {

                echo 'no img64 ' . PHP_EOL;
                return 2;
            }
        } else {
            echo $actor_id . ' no image ' . PHP_EOL;

            return 2;
        }


    }


}
function check_imdb($last_id = 0)
{


    global $pdo;

    if (!$pdo) {
        include '../db_config.php';
        pdoconnect_db();
    }

    $pdo->query('use imdbvisualization');


    $array_movie = get_last_options(9);

    //// echo $array_movie;

    if ($array_movie) {
        $array_movie = explode(',', $array_movie);
    }
    if (!$last_id) {
        $last_id = get_last_options(10);
    }


    /// var_dump($array_movie);

    echo 'last_id=' . $last_id . PHP_EOL;

    $i = 0;
    foreach ($array_movie as $link) {
        if ($link > $last_id) {
            echo $link . '>' . $last_id . PHP_EOL;
            $last_id = $link;
            $i++;

            /// echo $link . PHP_EOL;

            $link = 'https://www.boxofficemojo.com/release/rl' . $link . '/';

            $result = GETCURL::getCurlCookie($link);
            /// var_dump($result);

            //<a class="a-link-normal mojo-title-link refiner-display-highlight" href="/title/tt0076759/?ref_=bo_rl_ti"><img src="https://m.media-amazon.com/images/G/01/boxofficemojo/ic_summary_m._CB485936930_.png" width="21" height="18"><span class="mojo-hidden-from-mobile"> Title Summary</span></a>

            ////

            $regv2 = '#href\=\"\/title\/tt([0-9]+)\/#';

            if (preg_match($regv2, $result, $match1)) {
                $imdb_link = 'https://www.imdb.com/title/tt' . $match1[1] . '/';
                ///echo $imdb_link.PHP_EOL;

                $movie_id = intval($match1[1]);

                /// echo $movie_id . PHP_EOL;


                $result_imdb = TMDB::check_imdb_id($movie_id);


                if (!$result_imdb) {


                    $array_movie =  TMDB::get_content_imdb($movie_id);
                    $add =  TMDB::addto_db_imdb($movie_id, $array_movie);


                    if (!$add) {
                        echo $movie_id . ' not addeded ' . PHP_EOL;
                    }

                } else {
                    echo $movie_id . ' already adedded' . PHP_EOL;
                }

            }
            set_option(10, $last_id);
        }
        if ($i > 1000) {
            break;
        }
        //  break;
    }


    //

}
function check_tv_series_imdb($last_id = 0)
{

    global $pdo;
    if (!$pdo) {
        include '../db_config.php';
        pdoconnect_db();
    }
    $pdo->query('use imdbvisualization');
    $array_movie = get_last_options(11);
    //// echo $array_movie;

    if ($array_movie) {
        $array_movie = explode(',', $array_movie);
    }
    if (!$last_id) {
        $last_id = get_last_options(12);
    }

    /// var_dump($array_movie);

    echo 'last_id=' . $last_id . PHP_EOL;

    $i = 0;
    foreach ($array_movie as $movie_id) {
        if ($movie_id > $last_id) {
            echo $movie_id . '>' . $last_id . PHP_EOL;
            $last_id = $movie_id;
            $i++;

                $result_imdb = TMDB::check_imdb_id($movie_id);
                if (!$result_imdb) {
                    $array_movie =  TMDB::get_content_imdb($movie_id);
                    $add =  TMDB::addto_db_imdb($movie_id, $array_movie);

                    if (!$add) {
                        echo $movie_id . ' not addeded ' . PHP_EOL;
                    }

                } else {
                    echo $movie_id . ' already adedded' . PHP_EOL;
                }

            }
            set_option(12, $last_id);
        if ($i >10) {
            break;
        }
        }

}
function check_actor_image($actor_id)
{
    check_image_on_server($actor_id);
}
function add_tv_shows_to_options()
{
    include '../db_config.php';
    global $pdo;
    pdoconnect_db();
    $pdo->query('use imdbvisualization');

    $array_year = [];
    $year_end = date('Y', time());
    $count=1;
    for ($count = 1;$count<=10000;$count+=50)
    {
        $link = 'https://www.imdb.com/search/title/?title_type=tv_series%2Ctv_miniseries&start='.$count.'&ref_=adv_nxt';

        echo $link . PHP_EOL;
        $result = GETCURL::getCurlCookie($link);
//echo $result;

        $regv = '#title\/tt([0-9]+)\/\?ref_\=adv_li_tt#';
        if (preg_match_all($regv, $result, $match)) {
            foreach ($match[1] as $link) {
                ///echo $link . PHP_EOL;

                $link = intval($link);

                if (!in_array($link, $array_year)) {
                    $array_year[] = $link;
                }
            }
        }


    }
    sort($array_year);

    echo 'count:' . count($array_year) . '<br>';

    $array_year = implode($array_year, ',');
    print_r($array_year);

    set_option(11, $array_year);
    set_option(12, 0);
    return;

}
function add_rating()
{
    ///pg rating
//TODO add new rating cache
    return;





    ///TODO check the audience and staff rating on the possibility of duplicates, + cache is created not for all posts
    ///
    global $table_prefix;

    for ($type = 1; $type<3;$type++) {

        $sql = "SELECT * FROM `{$table_prefix}posts` LEFT JOIN {$table_prefix}post_rating 
    ON (`{$table_prefix}posts`.ID={$table_prefix}post_rating.movie_id and  {$table_prefix}post_rating.type ={$type})
        WHERE ( `" . $table_prefix . "posts`.post_type ='movie' OR `" . $table_prefix . "posts`.post_type ='tvseries')
          and `" . $table_prefix . "posts`.post_status ='publish'
         
          and  {$table_prefix}post_rating.id IS NULL limit 300";

        //  echo $sql;
        $rows = Pdo_wp::db_results_array($sql);
        foreach ($rows as $r2)
        {

            $rwt_id = $r2['ID'];
            echo 'id='.$rwt_id.PHP_EOL;
            $data->rwt_audience($rwt_id, $type);
        }
    }


}

function update_all_gender_cache($pid)
{

    !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';
    $where='';
    if ($pid)
    {
        $where = " where movie_id=".intval($pid);
    }
    global $table_prefix;
    $data = new RWT_RATING;

    $sql = "SELECT id FROM `data_movie_imdb` ".$where;

    $rows = Pdo_an::db_results_array($sql);
    $count = count($rows);
    $i=0;
    foreach ($rows as $r2)
    {
        $i++;
        $id = $r2['id'];
        $data->gender_and_diversity_rating($id,'',1);
        echo $i.' of '.$count.' id='.$id.'<br>'.PHP_EOL;
    }
}

function update_audience_rating($movie_id,$audiencetype=1)
{
    !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';

    $rwt_id = TMDB::get_id_from_imdbid($movie_id);
    $result =PgRatingCalculate::rwt_audience($rwt_id,$audiencetype,1);
    var_dump($result);


}


function update_all_audience_and_staff()
{
    !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';
    global $table_prefix;
    $data = new RWT_RATING;

    for ($type = 1; $type < 3; $type++) {

        if ($type==1)
        {
            $staff_type="and a.type=2";
        }
        else if ($type==2)
        {
            $staff_type="and a.type=0";
        }


        $sql = "select m.fid from {$table_prefix}critic_matic_rating as r 
    inner join {$table_prefix}critic_matic_posts_meta as m ON m.cid = r.cid
inner join {$table_prefix}critic_matic_posts as p ON p.id = r.cid
inner join {$table_prefix}critic_matic_authors_meta as am ON am.cid = m.cid
inner join {$table_prefix}critic_matic_authors as a ON a.id = am.aid


where  m.state!=0  and p.status=1 ".$staff_type;

        $rows = Pdo_an::db_results_array($sql);

        $count = count($rows);
        echo '<br>count ='.$count.'<br>';
        $i=1;
        foreach ($rows as $r2)
        {
            $rwt_id = $r2['fid'];

            $result =PgRatingCalculate::rwt_audience($rwt_id,$type,1);
            echo $i.' of '.$count. ' id=' . $rwt_id .' '.$type.'<br>'. PHP_EOL;
            print_r($result);
            echo '<br><hr><br>';
            $i++;
        }
    }
}

function check_movie_dublicates()
{
    $sql = "SELECT * FROM `data_movie_imdb` order by  movie_id, id asc";
    $last_data=[];
    $rows = Pdo_an::db_results_array($sql);
    foreach ($rows as $r)
    {
        $id = $r['id'];
        $movie_id = $r['movie_id'];


        if ($movie_id==$movie_id_last)
        {
            echo   '<br>Origin id=' . $last_data['id'] . ' imdb_id ' .$last_data['movie_id'].' '.$last_data['title'].' '.$last_data['post_name'].'  <br>' . PHP_EOL;
            echo   'found id= ' . $id . ' imdb_id ' .$movie_id.' '.$r['title'].' '.$r['post_name'].' deleted  <br>' . PHP_EOL;

          if ($_GET['delete']==1)
          {
              $sql ="DELETE FROM `data_movie_imdb` WHERE `data_movie_imdb`.`id` = ".$id;
              Pdo_an::db_query($sql);
          }

        }
        else
        {
            $last_data = $r;
            $movie_id_last=$movie_id;
        }


    }
}



function add_providers()
{


    chdir($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/custom_twentysixteen/template/ajax');
    include $_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/custom_twentysixteen/template/ajax/get_wach.php';


    $sql = "SELECT `data_movie_imdb`.id  FROM `data_movie_imdb` LEFT JOIN just_wach 
    ON `data_movie_imdb`.id=just_wach.rwt_id
        WHERE   just_wach.rwt_id IS NULL order by `data_movie_imdb`.id desc  limit 500";

$rows = Pdo_an::db_results_array($sql);
if ($rows)echo 'add empty '.count($rows);
foreach ($rows as $r)
{
        $id = $r['id'];
        echo 'rwt_id=' . $id . '  <br>' . PHP_EOL;
        if ($id)
        {
            get_just_wach($id);
        }

    }

$sql = "SELECT rwt_id FROM just_wach WHERE just_wach.`addtime` =0 limit 20 ";

$rows = Pdo_an::db_results_array($sql);
    if ($rows)echo 'update empty '.count($rows);
foreach ($rows as $r)
{
    $id = $r['rwt_id'];
    echo 'rwt_id=' . $id . '  <br>' . PHP_EOL;
    if ($id)
    {
        get_just_wach($id);
    }

}

}

function add_imdb_data_to_options()
{
    include '../db_config.php';
    global $pdo;
    pdoconnect_db();
    $pdo->query('use imdbvisualization');

    $array_year = [];
    $year_end = date('Y', time());
///for ($year = 1977;$year<=$year_end;$year++)
    {
        $year = $year_end;
        $mount = strtolower(date('F', time() - 86400 * 7));

        $link = 'https://www.boxofficemojo.com//month/' . $mount . '/' . $year . '/';

        echo $link . PHP_EOL;
        $result = GETCURL::getCurlCookie($link);

        $regv = '#\/release\/rl([0-9]+)\/#';
        if (preg_match_all($regv, $result, $match)) {
            foreach ($match[1] as $link) {
                ///echo $link . PHP_EOL;

                if (!in_array($link, $array_year)) {
                    $array_year[] = $link;
                }
            }
        }


    }
    sort($array_year);

    echo 'count:' . count($array_year) . '<br>';

    $array_year = implode($array_year, ',');
    print_r($array_year);

    set_option(9, $array_year);
    set_option(10, 0);
    check_imdb(1);


}
function check_kairos($id='')
{

    !class_exists('KAIROS') ? include ABSPATH . "analysis/include/kairos.php" : '';

    KAIROS::check_actors($id);

}




global $included;
if ($included) return;





if (isset($_GET['add_rating'])) {

    add_rating();

    return;
}

if (isset($_GET['check_last_actors'])) {

    check_last_actors();
    return;
}
if (isset($_GET['add_imdb_data_to_options'])) {


    return;
}
if (isset($_GET['check_imdb'])) {

    check_imdb();
    return;
}
if (isset($_GET['check_face'])) {

    include('bettaface.php');


    check_face();
    return;
}


if (isset($_GET['update_tmdb_actors'])) {

    update_tmdb_actors($_GET['update_tmdb_actors']);
    return;

}


////////update actors stars data
if (isset($_GET['update_actors_stars_data'])) {
    global $pdo;


    /////////parse from imdb


    $sql = "SELECT  `data_movie_imdb`.id,`data_movie_imdb`.movie_id,  `data_movie_imdb`.actors  FROM `data_movie_imdb`
        WHERE `data_movie_imdb`.add_time = 2  LIMIT 250";

    //  echo $sql;
    //  echo '<br>';

    $q = $pdo->prepare($sql);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $i = 0;
    while ($r = $q->fetch()) {

        $array_result = [];
        $movie_id = $r['movie_id'];
        $actors = $r['actors'];
        $ID = $r['id'];
        $array_new_actors = [];
        $actors_array = json_decode($r['actors'], JSON_FORCE_OBJECT);
        //var_dump($actors_array);
        //echo '<br>';

        foreach ($actors_array as $type => $data) {
            foreach ($data as $a_id => $a_name) {
                $array_result["actors"]['e'][$a_id] = $a_name;
            }
        }
        $final_value = sprintf('%07d', $movie_id);
        $url = "https://www.imdb.com/title/tt" . $final_value . '/';
       /// echo $url . '<br>';
        $result1 = GETCURL::getCurlCookie($url);
        if ($result1) {

            $array_result = TMDB::get_imdb_parse_data($result1, $array_result);

            //var_dump($array_result);

            if ($array_result["runtime"]) {

                ////update actors table
                $runtime = $array_result["runtime"];


                $sql3 = "UPDATE `data_movie_imdb` SET `runtime` = ?   WHERE `data_movie_imdb`.`id` = '" . $ID . "';";
                $q3 = $pdo->prepare($sql3);
                $q3->execute(array($runtime));

                echo 'runtime updated ';
                // var_dump($array_result);
            }

            if ($array_result["actors"]['s']) {

                ////update actors table

                $actors_string = json_encode($array_result["actors"]);

                $sql3 = "UPDATE `data_movie_imdb` SET `actors` = ?, `add_time`= 3  WHERE `data_movie_imdb`.`id` = '" . $ID . "';";
                $q3 = $pdo->prepare($sql3);
                $q3->execute(array($actors_string));

                echo 'actors updated from imdb ' . PHP_EOL;

                /// var_dump($array_result);
            } else {
                $sql3 = "UPDATE `data_movie_imdb` SET  `add_time`= 3  WHERE `data_movie_imdb`.`id` = '" . $ID . "';";
                $q3 = $pdo->prepare($sql3);
                $q3->execute();

                echo 'not found';
            }

        }
    }


    ////https://www.themoviedb.org/person/1003944


    $sql = "SELECT  `data_movie_imdb`.id,`data_movie_imdb`.movie_id,  `data_movie_imdb`.actors  FROM `data_movie_imdb`
        WHERE `data_movie_imdb`.add_time = 1  LIMIT 1000";

    //echo $sql;
    //echo '<br>';

    $q = $pdo->prepare($sql);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $i = 0;
    while ($r = $q->fetch()) {
        $array_all_actors = [];
        $movie_id = $r['movie_id'];
        $actors = $r['actors'];
        $ID = $r['id'];
        $array_new_actors = [];
        $actors_array = json_decode($r['actors'], JSON_FORCE_OBJECT);
        foreach ($actors_array as $type => $data) {
            foreach ($data as $a_id => $a_name) {
                $array_all_actors[$a_id] = $a_name;
            }
        }
        //var_dump($array_all_actors);

        //  echo '<br>';

        $sq1l = "SELECT data_actors.Name, data_actors.actor_id, 
       data_actors.Category, data_actors.MovieID  FROM  data_actors WHERE data_actors.MovieID  = '" . $movie_id . "'";
        // echo $sq1l;
        // echo '<br>';
        $q2 = $pdo->prepare($sq1l);
        $q2->execute();
        $q2->setFetchMode(PDO::FETCH_ASSOC);
        while ($r2 = $q2->fetch()) {
            $actor_id = $r2['actor_id'];
            $actor_type = $r2['Category'];
            if ($array_all_actors[$actor_id] && ($actor_type == 'star' or $actor_type == 'main')) {
                //   var_dump($r2);
                //   echo '<br>';
                unset($array_all_actors[$actor_id]);

                if ($actor_type == 'star') {
                    $array_new_actors['s'][$actor_id] = $r2['Name'];
                } else if ($actor_type == 'main') {
                    $array_new_actors['m'][$actor_id] = $r2['Name'];
                }

            }
        }
        if ($array_new_actors) {
            $array_new_actors['e'] = $array_all_actors;
            ///update movie
            $actors_string = json_encode($array_new_actors);

            $sql3 = "UPDATE `data_movie_imdb` SET `actors` = ?, `add_time`= 3  WHERE `data_movie_imdb`.`id` = '" . $ID . "';";
            $q3 = $pdo->prepare($sql3);
            $q3->execute(array($actors_string));

            //  echo $actors_string;
            echo 'addeded <br>';
        } else {
            $sql3 = "UPDATE `data_movie_imdb` SET  `add_time`= 2  WHERE `data_movie_imdb`.`id` = '" . $ID . "';";
            $q3 = $pdo->prepare($sql3);
            $q3->execute();
            echo 'no actors <br>';
        }
    }


}
if (isset($_GET['get_imdb_movie_id'])) {
        $id = intval($_GET['get_imdb_movie_id']);

        $array_movie =  TMDB::get_content_imdb($id);



        $add =  TMDB::addto_db_imdb($id, $array_movie);

    echo $add;
    return;
}


/////////add providers
if (isset($_GET['add_providers'])) {
    add_providers();
}
else if (isset($_GET['add_pgrating'])) {
    add_pgrating($_GET['add_pgrating']);
return;
}

else if (isset($_GET['update_pgrating'])) {
    update_pgrating($_GET['update_pgrating']);
    return;
}
else if (isset($_GET['update_all_pg_rating'])) {
    update_all_pg_rating();
    return;
}





///////add tv shows
if (isset($_GET['add_tv_shows_to_options'])) {
    add_tv_shows_to_options();
    return;

}

if (isset($_GET['check_tv_series_imdb'])) {
    check_tv_series_imdb();
    return;
}

///////////add Franchises
if (isset($_GET['add_franchises'])) {

    include "franchises.php";
    Franchises::parse();
    return;
}
if (isset($_GET['check_kairos'])) {
    check_kairos($_GET['check_kairos']);
    return;
}

if (isset($_GET['force_surname_update'])) {
    force_surname_update();
    return;
}
if (isset($_GET['update_imdb_data'])) {
    update_imdb_data($_GET['update_imdb_data']);
    return;
}
if (isset($_GET['update_all_gender_cache'])) {
    update_all_gender_cache($_GET['update_all_gender_cache']);
    return;
}
if (isset($_GET['update_all_audience_and_staff'])) {
    update_all_audience_and_staff();
    return;
}
if (isset($_GET['get_new_movies'])) {
    get_new_movies();
    return;
}
if (isset($_GET['get_new_tv'])) {
    get_new_tv();
    return;
}
if (isset($_GET['add_pg_rating_for_new_movies'])) {
    add_pg_rating_for_new_movies();
    return;
}
if (isset($_GET['add_gender_rating'])) {
    add_gender_rating();
    return;
}

if (isset($_GET['add_gender_rating_for_new_movies'])) {
    add_gender_rating_for_new_movies();
    return;
}

if (isset($_GET['check_movie_dublicates'])) {
    check_movie_dublicates();
    return;
}
if (isset($_GET['add_to_db_from_userlist'])) {
    add_to_db_from_userlist();
    return;
}
if (isset($_GET['update_audience_rating'])) {
    update_audience_rating($_GET['update_audience_rating'],$_GET['type']);
    return;
}
if (isset($_GET['import_from_rwt'])) {

    $stage = $_GET['import_from_rwt'];
    if ($stage==1)
    {
        import_from_rwt();
    }
    else if ($stage==1)
    {
        import_from_rwt_2();
    }
    else if ($stage==2)
    {
        import_from_rwt_3();
    }

    return;
}

if (isset($_GET['set_actors_ethnic'])) {
    set_actors_ethnic($_GET['set_actors_ethnic']);
    return;
}
if (isset($_GET['update_actors_verdict'])) {
    update_actors_verdict($_GET['update_actors_verdict']);
    return;
}
if (isset($_GET['check_actors_meta'])) {

    ///check_actors_meta();
    return;
}

if (isset($_GET['update_all_rwt_rating'])) {

    update_all_rwt_rating($_GET['update_all_rwt_rating']);
    return;
}

if (isset($_GET['check_dublicates_tmdbid'])) {

$sql="SELECT tmdb_id,type, count(tmdb_id) FROM `data_movie_imdb` GROUP by tmdb_id, type having count(tmdb_id) > 1 ";
$rows = Pdo_an::db_results_array($sql);
foreach ($rows as $r)
{
    $tmdb_id = $r['tmdb_id'];
    if ($tmdb_id>0)
    {
        $q = "UPDATE `data_movie_imdb` SET `tmdb_id` = 0 WHERE `tmdb_id` =  ".$tmdb_id;
        Pdo_an::db_query($q);
        echo 'deleted '.$tmdb_id.'<br>';
    }
}
    return;
}

if (isset($_GET['check_dublicates_postname'])) {

    $sql="SELECT `post_name`,  count(`post_name`) FROM `data_movie_imdb` GROUP by `post_name`,`type` having count(`post_name`) > 1 ";
    $rows = Pdo_an::db_results_array($sql);
    foreach ($rows as $r)
    {
        $post_name = $r['post_name'];
        if ($post_name)
        {
            $q = "UPDATE `data_movie_imdb` SET `post_name` = '' WHERE `post_name` = '".$post_name."'";
            Pdo_an::db_query($q);
            echo 'deleted '.$post_name.'<br>';
        }
    }
    return;
}

if (isset($_GET['check_archvie_movie'])) {
$id = $_GET['check_archvie_movie'];
    if (function_exists('gzencode'))
    {
        $bytesCount = file_get_contents(ABSPATH.'analysis/imdb_gzdata/m'.$id  );
        $gzdata =gzdecode($bytesCount);


            echo $gzdata;

    }

}

if (isset($_GET['get_twitter'])) {
    $id = $_GET['get_twitter'];

    !class_exists('GETTWITTER') ? include ABSPATH . "analysis/include/twitter.php" : '';

    GETTWITTER::get_url($id);

}
if (isset($_GET['get_coins_data'])) {

    get_coins_data();
    return;

}
if (isset($_GET['check_tmdb_data'])) {

    check_tmdb_data($_GET['check_tmdb_data']);
    return;
}


if (isset($_GET['update_meta'])) {
    $sql ="SELECT * FROM `data_actors_meta` WHERE `verdict` is NOT NULL and `n_verdict` IS NULL limit 300000";
    $rows = Pdo_an::db_results_array($sql);
    foreach ($rows as $r)
    {

        $ethnic = intconvert($r['ethnic']);
        $jew = intconvert($r['jew']);
        $kairos = intconvert($r['kairos']);
        $bettaface = intconvert($r['bettaface']);
        $surname = intconvert($r['surname']);
        $crowdsource = intconvert($r['crowdsource']);
        $verdict = intconvert($r['verdict']);


        $sql = "UPDATE `data_actors_meta` SET `n_ethnic`=?,`n_jew`=?,`n_kairos`=?,`n_bettaface`=?,`n_surname`=?,`n_crowdsource`=?,`n_verdict`=?
                            WHERE `id`=?";
        Pdo_an::db_results_array($sql,array($ethnic,$jew,$kairos,$bettaface,$surname,$crowdsource,$verdict,$r['id']));

    }
    return;
}


if (isset($_GET['add_tmdb_without_id'])) {




    add_tmdb_without_id();

    return;
}


if (isset($_GET['download_crowd_images'])) {

    download_crowd_images();

    return;
}
if (isset($_GET['set_tmdb_actors_for_movies'])) {

    set_tmdb_actors_for_movies();

    return;
}
if (isset($_GET['get_array'])) {

    get_array($_GET['get_array']);

    return;
}

if (isset($_GET['fix_kairos'])) {

    $sql ="SELECT * FROM `data_actors_race` WHERE `White` = 0 AND `kairos_verdict` = 'W' limit 100000";
    $row = Pdo_an::db_results_array($sql);
    if ($row)
    {
        foreach ($row as $r)
        {
            $sql = "SELECT id FROM `data_actors_meta` WHERE `kairos`= 'W' and `actor_id` = '{$r['actor_id']}'";

            echo 'id = '.$r['actor_id'].'<br>';

            $sql1 = "UPDATE `data_actors_meta` SET `kairos` = NULL  ,
        `n_kairos` = NULL ,
        
        `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";
            Pdo_an::db_query($sql1);
            update_actors_verdict($r['actor_id']);


            $sql2 = "UPDATE `data_actors_race` SET `kairos_verdict` = NULL WHERE `data_actors_race`.`id` = ".$r['id'];
            Pdo_an::db_query($sql2);
        }
    }

    return;
}



echo 'ok';

