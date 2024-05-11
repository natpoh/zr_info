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
///add option
!class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';

!class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';


function actor_slug()
{

        global $debug;

        check_load(50,60);

        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        $actor_id=intval($_GET['add_actors_slug']);
        if ($actor_id)
        {
            $q="SELECT * FROM `data_actors_imdb` WHERE id =".$actor_id." ";
        }
        else
        {
            $q="SELECT * FROM `data_actors_imdb` WHERE `slug` is NULL and `name` IS NOT NULL and `name`!='' order by id asc limit 100000";
        }

        $r = Pdo_an::db_results_array($q);

        if ($r)
        {
            $count =0;
            foreach ($r as $row)
            {
                $count+=1;
                $name = $row['name'];
                // echo $name.'<br>';
                $slug =  TMDB::getslug($name);
                if ($slug)
                {

                    $q2 ="UPDATE `data_actors_imdb` SET `slug`=?  WHERE `id` =?";
                    Pdo_an::db_results_array($q2,[$slug,$row['id']]);



                    Import::create_commit('', 'update', 'data_actors_imdb', array('id' => $row['id']), 'actor_slug',20);
                    ACTIONLOG::update_actor_log('actor_slug','data_actors_imdb',$row['id']);


                    if (check_cron_time())
                    {

                        echo 'total: '.$count;
                        break;


                    }
                }

            }
        }
}


function update_actor_directors($movie_id)
{
         global $force;
         $force=1;
        ////update movie
        $array_movie =  TMDB::get_content_imdb($movie_id,0,1,1);
        $add =  TMDB::addto_db_imdb($movie_id, $array_movie,'','','update_actor_directors_new');
        echo $movie_id.' updated<br>';
        return 1;

}

function update_actor_stars($id,$movie_id)
{
    $q ="SELECT * FROM `meta_movie_actor` where mid =".$id;
    $rows = Pdo_an::db_results_array($q);
    $types = [];
    $array=[];
    foreach ($rows as $v)
    {
        $types[$v['type']][]=$v['aid'];
        $array[$v['aid']] = $v['type'];
    }
    if ($types[1])
    {
       // echo $movie_id.' skip<br>';
        return 0;
    }
    else
    {
        ////update movie
        $array_movie =  TMDB::get_content_imdb($movie_id,0,1,1);
        $add =  TMDB::addto_db_imdb($movie_id, $array_movie,'','','update_actor_stars');

        echo $id.' updated<br>';
        return 1;
    }
}

function check_load($run_time=200,$time_limit='')
{

    !class_exists('CPULOAD') ? include ABSPATH . "service/cpu_load.php" : '';
    $load = CPULOAD::check_load();
    if ($load['loaded']) {  return;  }

    start_cron_time($run_time);
    if ($time_limit)
    {
        set_time_limit($time_limit);
    }

}




function fix_all_directors_delete($movie_id=0)
{

    check_load(200,300);

    !class_exists('DeleteMovie') ? include ABSPATH . "analysis/include/delete_movie.php" : '';
    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
    $last_id = OptionData::get_options('','directors_last_id_delete');
    echo 'last_id='.$last_id.'<br>';

    if (!$last_id)
    {
        $last_id=0;
    }

    if (!$movie_id)
    {
        $movies_updated = 0;
$last_update = strtotime('11.01.2023');
        $last_update2 = strtotime('26.02.2023');

//        $q= "SELECT `movies_log`.rwt_id, `movies_log`.id  FROM `data_movie_imdb`, `movies_log` LEFT JOIN `data_movie_imdb` as d ON d.`id`  =`movies_log`.rwt_id
//        where `data_movie_imdb`.id  =`movies_log`.movie_id and `movies_log`.`name` = 'add movies' and `movies_log`.`type` IS NULL  and `movies_log`.rwt_id  IS NOT NULL
//         and  d.`id` IS NOT NULL and  `movies_log`.last_update >1673384400  and  `movies_log`.last_update < {$last_update2}    order by `movies_log`.rwt_id asc limit 100";

       $q= "SELECT `movies_log`.rwt_id, `movies_log`.id, d.year  FROM `movies_log` LEFT JOIN `data_movie_imdb` as d ON d.`id` =`movies_log`.rwt_id where `movies_log`.`name` = 'add movies'
        and (d.year < 2010 OR d.year IS NULL) and `movies_log`.rwt_id IS NOT NULL and d.`id` IS NOT NULL and `movies_log`.last_update >1673384400 and `movies_log`.last_update < 1677358800 
                                                                                                                                       order by `movies_log`.rwt_id asc limit 100" ;

        $r = Pdo_an::db_results_array($q);
        foreach ($r as $row)
        {

            $id =  $row['rwt_id'];
            DeleteMovie::delete_movie($id, 1,'false_added');
            OptionData::set_option('',$id,'directors_last_id_delete',false);

            echo $id.' deleted<br>';

            if (check_cron_time())
            {
                break;
            }

        }

    }

}

function fix_all_directors($movie_id=0)
{
    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
    $last_id = OptionData::get_options('','directors_last_id');
    echo 'last_id='.$last_id.'<br>';

    if (!$last_id)
    {
        $last_id=0;
    }

    if (!$movie_id)
    {


        $q= "SELECT id, movie_id FROM `data_movie_imdb` where id > ".$last_id."  order by id asc limit 1000";
        $r = Pdo_an::db_results_array($q);
        foreach ($r as $row)
        {

            $id =  $row['id'];
            $movie_id =  $row['movie_id'];
            update_actor_directors($movie_id);
            OptionData::set_option('',$id,'directors_last_id',false);


            if (check_cron_time())
            {
            break;
            }


        }


    }
    else
    {
        update_actor_directors($movie_id);
    }


}
function fix_actors_stars($movie_id)
{
    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
    $last_id = OptionData::get_options('','actor_stars_last_id');
    echo 'last_id='.$last_id.'<br>';

    if (!$last_id)
    {
        $last_id=0;
    }

    if (!$movie_id)
    {
        $movies_updated = 0;

        $q= "SELECT id, movie_id FROM `data_movie_imdb` where id > ".$last_id."  order by id asc limit 10000";
        $r = Pdo_an::db_results_array($q);
        foreach ($r as $row)
        {

            $id =  $row['id'];
            $movie_id =  $row['movie_id'];
            $movies_updated+=update_actor_stars($id,$movie_id);
            OptionData::set_option('',$id,'actor_stars_last_id',false);

            if ($movies_updated> 100)
            {
                break;
            }
        }


    }
    else
    {
        update_actor_stars($movie_id);
    }


}
function fix_actors_verdict($actor_id='')
{
    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
    $last_id = OptionData::get_options('','actor_verdict_last_id');
    echo 'last_id='.$last_id.'<br>';

    if (!$last_id)
    {
        $last_id=0;
    }

    if (!$actor_id)
    {

        $q= "SELECT id, actor_id FROM `data_actors_meta` where id > ".$last_id."  order by id asc limit 10000";
        $r = Pdo_an::db_results_array($q);
        foreach ($r as $row)
        {

            $id =  $row['id'];
            $actor_id =  $row['actor_id'];
            update_actors_verdict($actor_id,1,0);

            $last_id=$id;

        }
        OptionData::set_option('',$id,'actor_verdict_last_id',false);
    }
    else
    {
        update_actors_verdict($actor_id,1,1);
    }

}

function set_verdict_weight($id)
{
    !class_exists('CPULOAD') ? include ABSPATH . "service/cpu_load.php" : '';

    check_load(50,0);

    !class_exists('ActorWeight') ? include ABSPATH . "analysis/include/actors_weight.php" : '';
    $count=10000;
    if (isset($_GET['count']))
    {
       $count =  $_GET['count'];
    }
    $force=false;

    if (isset($_GET['force']))
    {
        $force =  1;
    }

    ActorWeight::update_actor_weight($id, $_GET['debug'],0,$count,$force);
    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
    $last_id = OptionData::get_options('','actors_meta_last_id');
    echo 'last_id (actors_meta_last_id) ='.$last_id;
}

function get_similar($id)
{
    !class_exists('SimilarMovies') ? include ABSPATH . "analysis/include/similar_movies.php" : '';
    echo SimilarMovies::get_movies($id);
}

function start_cron_time($time)
{
    !class_exists('Cronjob') ? include ABSPATH . "service/cron.php" : '';
    global $cron;
    $cron = new Cronjob();
    $cron->timer_start($time);

}


function check_cron_time($last_time_result=0)
{
    $result = '';
    if (class_exists('Cronjob'))
    {
        global $cron;
        if ($cron)
        {
            $last_time = $cron->check_time();
            $result =$last_time['result'];


        }
    }
    if ($last_time_result)
    {
        return $last_time['curtime'];
    }
    return $result;
}

function sync_tables($table='')
{
    if ($table)
    {
        $array_tables= array($table);
    }
    else
    {
        $array_tables = array('data_familysearch_verdict'=>400, 'data_forebears_verdict'=>400,'meta_reviews_rating'=>200,'meta_keywords'=>1000,'meta_movie_keywords'=>1000);
    }


!class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';


foreach ($array_tables as $table=>$limit)
{

    if (check_cron_time())break;

    Import::sync_db($table,$limit);

}


}


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

function disqus_comments($data='')
{

    !class_exists('DISQUS_DATA') ? include ABSPATH . "analysis/include/disqus.php" : '';

    global $debug;
    if (isset($_GET['debug'])){$debug=$_GET['debug'];}

    DISQUS_DATA::disqus_comments($data,$debug);


}

function check_tmdb_actors($id)
{
    ////not used
    return;

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

function update_actors_verdict($id='',$force=0,$sync = 1 )
{
    !class_exists('ActorWeight') ? include ABSPATH . "analysis/include/actors_weight.php" : '';
    ActorWeight:: update_actors_verdict($id,$force,$sync );

}




function crowd_movie_keywords($id='')
{

    !class_exists('Movie_Keywords') ? include ABSPATH . "analysis/include/keywords.php" : '';

    $keywords = new Movie_Keywords;
    global $debug;
    if (isset($_GET['debug']))
    {
        $debug =$_GET['debug'];
    }

    $keywords->crowd_movie_keywords();

}

function movie_keywords($id='')
{

    !class_exists('Movie_Keywords') ? include ABSPATH . "analysis/include/keywords.php" : '';

    $keywords = new Movie_Keywords;
    global $debug;
    if (isset($_GET['debug']))
    {
        $debug =$_GET['debug'];
    }


    $keywords->get_movies_keyword($id);

}
function get_last_options($id,$type='')
{

    $last_id =OptionData::get_options($id,$type);
    if (!$last_id) $last_id = 0;
    return $last_id;
}

if (!function_exists('set_option')) {

    function set_option($id, $option,$type='')
    {
        OptionData::set_option($id, $option,$type);
    }
}

function add_to_db_from_userlist()
{

    $data =OptionData::get_options(16);
    if ($data) {
        $movie_list = json_decode($data, 1);

    foreach ($movie_list as $movie_id=>$count)
    {


        $addeded = TMDB::check_imdb_id($movie_id);
        if (!$addeded)
        {
            ////add movie to database
            $array_movie =  TMDB::get_content_imdb($movie_id);
            $add =  TMDB::addto_db_imdb($movie_id, $array_movie,'','','from_userlist');
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
    //userlist
    OptionData::set_option(16,json_encode($movie_list),'movie_list');
    


}

function update_imdb_data($from_archive=0,$force_time=0)
{
////update all imdb movies




    echo 'update_imdb_data<br>';


// 1.w50  Last 30 days (30)
// 2. w40 Last year  and rating 3-5 (250)
// 3. w30  Last 3 year and rating 4-5 (200)
//4. w20 All time and rating 4-5 (3500)
//5. w10 Last 3 year (4000)
//6. w0 Other (27000)

    $rating_update = array( 50=> 86400*7, 40 =>86400*14, 30=> 86400*30 , 20=> 86400*60, 10=> 86400*120, 0=>86400*240);


    $where='data_movie_imdb.add_time = 0';

    foreach ($rating_update as $w =>$period){
        $time = time()-$period;
        $where.=" OR (`data_movie_imdb`.add_time < ".$time." and  `data_movie_imdb`.`weight` =".$w." ) ";
    }
    if ($force_time){
        $where='data_movie_imdb.add_time < '.$force_time;
    }


////get movie list
    $sql ="SELECT `data_movie_imdb`.`movie_id` FROM `data_movie_imdb` WHERE  ".$where." order by `data_movie_imdb`.`weight` desc LIMIT 25";
echo $sql;
    $result =Pdo_an::db_results_array($sql);

    foreach ($result as $row) {

        $movie_id = $row['movie_id'];

        if ($movie_id) {
            if ($force_time) {
                sleep(0.5);
            }
            $array_movie =  TMDB::get_content_imdb($movie_id,'',1,$from_archive);
            $add =  TMDB::addto_db_imdb($movie_id, $array_movie,'','','update_imdb_data');
            if ($add) {
                echo $movie_id . ' updated<br> ' . PHP_EOL;
            }
        }
        if (check_cron_time())
        {
            break;
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

function get_family($data='')
{


    $race_small = array(
     1 =>'W',
     2 => 'EA',
     3 =>'H',
     4 =>'B',
     5 => 'I',
     6 => 'M' ,
     7 => 'MIX',
     8 =>'JW' ,
     9 => 'IND',
);



        $q = "SELECT data_actors_meta.actor_id, data_familysearch_verdict.verdict FROM data_actors_normalize, data_actors_meta , data_familysearch_verdict 
         WHERE  data_actors_normalize.aid=data_actors_meta.actor_id
         and data_actors_meta.n_familysearch = 0
           and data_actors_normalize.lastname = data_familysearch_verdict.lastname limit 1000";

    $rows = Pdo_an::db_results_array($q);

              foreach ($rows as $val) {
                  $i = $val['actor_id'];
                  $fm = $val['verdict'];

                  $sql = "UPDATE `data_actors_meta` SET  `n_familysearch` = '" .$fm . "',
                 `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $i. "'";

                  Pdo_an::db_query($sql);
                  update_actors_verdict($i,1);


                  if (check_cron_time())
                  {
                      break;
                  }
              }

}
function get_forebears($data='')
{


    $race_small = array(
        1 =>'W',
        2 => 'EA',
        3 =>'H',
        4 =>'B',
        5 => 'I',
        6 => 'M' ,
        7 => 'MIX',
        8 =>'JW' ,
        9 => 'IND',
    );


    $q = "SELECT a.`aid`, a.lastname, v.`verdict` FROM `data_actors_normalize` as a
    LEFT JOIN `data_actors_meta` as  m ON m.`actor_id` = a.`aid`
      LEFT JOIN `data_forebears_verdict` as v  ON v.`lastname` = a.`lastname`
where  m.n_forebears = 0 and v.`verdict`>0 limit 1000";

    $rows = Pdo_an::db_results_array($q);
    foreach ($rows as $r)
    {

        $aid = $r['aid'];
        $lastname =  $r['lastname'];
        $verdict  =  $r['verdict_rank'];


        ///update data
        $fm = $race_small[$verdict];

        echo $aid.' '.$fm.' lastname='.$lastname.'<br>';

        $sql1 = "UPDATE `data_actors_meta` SET  `n_forebears` = '" . $verdict. "',  `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $aid. "'";

        //echo $sql1.'<br>';
        Pdo_an::db_query($sql1);
        update_actors_verdict($aid,1);


        if (check_cron_time())
        {
            break;
        }
    }

}
function get_forebears_rank($data='')
{


    $race_small = array(
        1 =>'W',
        2 => 'EA',
        3 =>'H',
        4 =>'B',
        5 => 'I',
        6 => 'M' ,
        7 => 'MIX',
        8 =>'JW' ,
        9 => 'IND',
    );


    $q = "SELECT a.`aid`, a.lastname, v.`verdict_rank` FROM `data_actors_normalize` as a
    LEFT JOIN `data_actors_meta` as  m ON m.`actor_id` = a.`aid`
      LEFT JOIN `data_forebears_verdict` as v  ON v.`lastname` = a.`lastname`
where  m.n_forebears_rank = 0 and v.`verdict_rank`>0 limit 1000";

    $rows = Pdo_an::db_results_array($q);
    foreach ($rows as $r)
    {

        $aid = $r['aid'];
        $lastname =  $r['lastname'];
        $verdict  =  $r['verdict_rank'];


                ///update data
                $fm = $race_small[$verdict];

        echo $aid.' '.$fm.' lastname='.$lastname.'<br>';

                $sql1 = "UPDATE `data_actors_meta` SET  `n_forebears_rank` = '" . intconvert($fm) . "',
                 `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $aid. "'";

                //echo $sql1.'<br>';

                Pdo_an::db_query($sql1);

                update_actors_verdict($aid,1);


        if (check_cron_time())
        {
            break;
        }
    }

}

function set_tmdb_actors_for_movies()
{
    !class_exists('TMDBIMPORT') ? include ABSPATH . "analysis/include/tmdb_import.php" : '';
    TMDBIMPORT::set_tmdb_actors_for_movies();

}


function update_crowd_verdict($commit_actors=[],$id='')
{
    $i=0;

    if ($id)
    {
        $where = 'and  id = '.intval($id);
        $sql ="SELECT * FROM `data_actors_crowd` WHERE `status` = 1  ".$where." order by `id` ASC  limit 100";
    }
    else
    {
        $sql = "SELECT data_actors_crowd.actor_id, data_actors_crowd.verdict  FROM `data_actors_crowd` LEFT JOIN data_actors_meta ON data_actors_crowd.actor_id=data_actors_meta.actor_id
        WHERE data_actors_meta.n_crowdsource = 0 and data_actors_meta.actor_id >0 and data_actors_crowd.verdict IS NOT NULL and data_actors_crowd.verdict!='0' limit 100";
    }

    $r = Pdo_an::db_results_array($sql);

    if ($r)
    {
    foreach ($r as $row) {

        $gender = $row['gender'];
        $verdict = $row['verdict'];
        if ($gender) {

            if ($gender == 'm') {
                $gender = 2;///male
            } else if ($gender== 'f') {
                $gender = 1;///female
            }

            if ($gender)
            {
                $set= " `gender` = '" . $gender . "', ";
            }



        }

        $sql1 = "UPDATE `data_actors_meta` SET  " . $set . "  n_crowdsource ='" . intconvert($verdict) . "' ,`last_update` = " . time() . "   WHERE `data_actors_meta`.`actor_id` = '" . $row['actor_id'] . "'";



        Pdo_an::db_query($sql1);

        update_actors_verdict($row['actor_id'], 1, 0);
        ACTIONLOG::update_actor_log('crowd_verdict','data_actors_meta',$r['actor_id'] );
        $commit_actors[$r['actor_id']]=1;

        if (check_cron_time())
        {
        break;
        }
    $i++;
    }
    }

    return [$commit_actors,$i];

}


function download_crowd_images($id='')
{
    echo 'download_crowd_images<br>';
if ($id)
{
    $sql ="SELECT * FROM `data_actors_crowd` WHERE `actor_id`=".$id;
}
else
{
    $sql ="SELECT * FROM `data_actors_crowd` WHERE `image`!='' and `loaded` IS NULL and `status` = 1 limit 10";
}

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

        if ($loaded) {
            ///update actor meta
            $sql2 = "UPDATE `data_actors_meta` SET `last_update` = '" . time() . "', `crowd_img` = 1 WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "' and `crowd_img` = 0 ";
            Pdo_an::db_query($sql2);
            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            Import::create_commit('', 'update', 'data_actors_meta', array('actor_id' => $r['actor_id']), 'actor_meta', 9, ['skip' => ['id']]);
        }

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

function get_weight_list($table,$last_update='last_update',$table_mid="mid",$limit=100,$rating_update='',$dop_request='')
{


// 1.w50  Last 30 days (30)
// 2. w40 Last year  and rating 3-5 (250)
// 3. w30  Last 3 year and rating 4-5 (200)
//4. w20 All time and rating 4-5 (3500)
//5. w10 Last 3 year (4000)
//6. w0 Other (27000)

if (!$rating_update)
{
    $rating_update = array( 50=> 86400*7, 40 =>86400*30, 30=> 86400*60 , 20=> 86400*90, 10=> 86400*180, 0=>86400*360);
}

        $where=$table.".id IS NULL OR `{$table}`.`{$last_update}` IS NULL  OR `{$table}`.`{$last_update}` = 0  ";

        foreach ($rating_update as $w =>$period){
            $time = time()-$period;
            $where.=" OR (`{$table}`.`{$last_update}` < ".$time." and  `data_movie_imdb`.`weight` =".$w." ) ";
        }



////get movie list
    $sql ="SELECT `data_movie_imdb`.`id`, `{$table}`.`{$last_update}` FROM `data_movie_imdb` left join `{$table}` 
       ON `data_movie_imdb`.`id`= `{$table}`.`{$table_mid}`
        WHERE     ( ".$where." ) ".$dop_request."  order by `data_movie_imdb`.`weight` desc LIMIT {$limit}";
        global $debug;
        if ($debug){echo $sql;}
    $rows = Pdo_an::db_results_array($sql);
    return $rows;
}




function update_all_rwt_rating()
{

    start_cron_time(0);
    $force = intval($_GET['force']);

    global $debug;
    $debug =1; ///$_GET['debug'];


    !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';

    if (isset($_GET['id']))
    {
        $rwt_id = intval($_GET['id']);
        $result = PgRatingCalculate::add_movie_rating($rwt_id,'',$debug,1,1);

        return;
    }
    else
    {
        if ($force)
        {
            set_time_limit(0);

            $sql = "SELECT `data_movie_imdb`.id  FROM `data_movie_imdb`";
            $rows = Pdo_an::db_results_array($sql);
        }
        else
        {
global $debug;
$debug=1;
            $rating_update = array( 50=> 86400*3, 40 =>86400*7, 30=> 86400*30 , 20=> 86400*60, 10=> 86400*90, 0=>86400*120);
            $rows =get_weight_list('data_movie_erating','last_upd',"movie_id",1000,$rating_update);
        }

    }
    $count = count($rows);
    $i=0;
    foreach ($rows as $r2)
    {
        $i++;
        $id = $r2['id'];

        if ($force) {
          PgRatingCalculate::add_movie_rating($id,'','',1,1,0);
          //  PgRatingCalculate::add_movie_rating($id,'','',1,1,0);
        }
        else
        {
            PgRatingCalculate::add_movie_rating($id,'','',1,1,1);
        }

        $timeleft = check_cron_time(1);

        echo $i.' of '.$count.' '.$timeleft.' id='.$id.'<br>'.PHP_EOL;
    }

}



function add_gender_rating()
{
    !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';
    $data = new RWT_RATING;



    $rating_update = array( 50=> 86400*3, 40 =>86400*7, 30=> 86400*14 , 20=> 86400*30, 10=> 86400*60, 0=>86400*90);
    $rows =get_weight_list('cache_rating','last_update',"movie_id",100,$rating_update);

    $count = count($rows);
    $i=0;
    foreach ($rows as $r2)
    {
        $i++;
        $id = $r2['id'];
        $data->gender_and_diversity_rating($id,'',1,0);
        echo $i.' of '.$count.' id='.$id.'<br>'.PHP_EOL;
    }
}

function get_imdb_actor_parse_inner($content)
{
    $result=[];
    global $debug;
    $object = TMDB::actor_data_to_object($content,$debug);
    $object_actor = $object['props']["pageProps"]["contentData"];//["entityMetadata"];

 //  if ($debug) var_dump($object_actor);


    $result['name']=$object_actor["entityMetadata"]["nameText"]["text"];
    $result['description']=$object_actor["entityMetadata"]["bio"]["text"]["plainText"];
    $result['image']=$object_actor["entityMetadata"]["primaryImage"]["url"];
    $result['birthDate']=$object_actor["entityMetadata"]["birthDate"]["date"];


    $categories =  $object_actor["categories"];
    foreach ($categories as $data)
    {

    if ($data["id"]=='overview') {

        foreach ($data["section"]["items"] as $item)
        {
          if   ($item["rowTitle"]=='Birth name')
          {
              $result['burn_name']  =$item["htmlContent"];
          }
            if   ($item["rowTitle"]=='Born')
            {
                $birth_place =$item["htmlContent"];
                $regv='/birth_place=([^&]+)&/';
                if (preg_match($regv,$birth_place,$match))
                {
                    $result['burn_place']=$match[1];

                }
            }
        }

        break;
    }

    }

    return $result;


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

function add_actors_description($actor_id,$description,$sync=1)
{

    $q = "SELECT `id`, `description`, `last_updata` FROM `data_actors_description` WHERE `actor_id` = ".$actor_id." limit 1";
    $rows = Pdo_an::db_results_array($q);
    $t = $rows[0];

    if ($t )
    {
        if ($t['description']!=$description)
        {
            $q = "UPDATE `data_actors_description` SET `description`=?,`last_updata`=? WHERE `actor_id`= ".$actor_id;
            Pdo_an::db_results_array($q,[$description,time()]);

            if ($sync)
            {
              //  !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
              //  Import::create_commit('', 'update', 'data_actors_description', array('actor_id' => $actor_id), 'actor_desc_update',50);

            }



        }
        else
        {
            $q = "UPDATE `data_actors_description` SET `last_updata`=".time()." WHERE `actor_id`= ".$actor_id;
            Pdo_an::db_results_array($q);
        }

    }
    else
    {

        $q="INSERT INTO `data_actors_description`(`id`, `actor_id`, `description`, `last_updata`) VALUES (NULL,?,?,?)";
        Pdo_an::db_results_array($q,[$actor_id,$description,time()]);

        if ($sync) {
           // !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
           /// Import::create_commit('', 'update', 'data_actors_description', array('actor_id' => $actor_id), 'actor_desc_insert', 49);
        }
    }



}
function auto_publish_crowdsource()
{
    global $debug;
    $debug = $_GET['debug'];

    !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

    Crowdsource::auto_publish_crowdsource();
}

function migration_actors_description()
{
    check_load(280,290);

    $count =0;
    $q = "SELECT * FROM `data_actors_imdb` WHERE `description` !='' ORDER BY `name` ASC limit 100000";
    $rows = Pdo_an::db_results_array($q);
    foreach ($rows as $t)
    {
        $count++;
        add_actors_description($t['id'],$t['description'],0);
        $sql = "UPDATE `data_actors_imdb` SET `description`='' WHERE `data_actors_imdb`.`id` = " . $t['id'];
        Pdo_an::db_results_array($sql);

        if (check_cron_time())
        {

            break;
        }

    }

    echo $count;


}


function addto_db_actors($actor_id, $imdb_id,$array_result, $update = 0,$debug)
{


    /// print_r($array_result);
    $q = "SELECT * FROM `data_actors_imdb` WHERE id = ".$actor_id." limit 1";
    $rows = Pdo_an::db_results_array($q);
    $t = $rows[0];

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
        $image_url = $array_result['image'];

        $image = 'Y';

        if (DB_SYNC_MODE ==1) {
            if (check_image_on_server($actor_id, $image_url)) {


                if (!$t['image']) {
                    !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';
                    ACTIONLOG::update_actor_log('image', 'data_actors_imdb', $actor_id);
                }
            }
        }

        unset($array_result['image']);

    } else {
        $image = 'N';
    }

      add_actors_description($actor_id,$description);

    if ($t) {

        if (!$t['slug'] ||  $t['name']!=$name )
        {
            $slug =  TMDB::getslug($name);
        }
        else
        {
            $slug  = $t['slug'];
        }


        if (!$t['slug'] ||  $t['name']!=$name || $t['birth_name']!=$burn_name || $t['birth_place']!=$burn_place || $t['burn_date']!=$birthDate
            || $t['image_url']!=$image_url || $t['image']!=$image )
        {
            $array_request = array($name, $burn_name, $burn_place, $birthDate, '', $image_url, $image, $slug ,time());
            $sql = "UPDATE `data_actors_imdb` SET
               `name`=?, `birth_name`=?, `birth_place`=?, `burn_date`=?, `description`=?, `image_url`=?, `image`=?, `slug`=?, `lastupdate`=?
            WHERE `data_actors_imdb`.`id` = " . $actor_id;
            Pdo_an::db_results_array($sql,$array_request);


            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            Import::create_commit('', 'update', 'data_actors_imdb', array('id' => $actor_id), 'actor_update',40);
            if ($debug)echo 'updated ' . $actor_id .' '.$name. '<br>' . PHP_EOL;
        }
        else
        {
            $sql = "UPDATE `data_actors_imdb` SET `lastupdate`=".time()." WHERE `data_actors_imdb`.`id` = " . $actor_id;
            Pdo_an::db_results_array($sql);

            if ($debug)echo 'skip no new data ' . $actor_id .' '.$name. '<br>' . PHP_EOL;
        }

    }
    else
    {
        $array_request = array($name, $burn_name, $burn_place, $birthDate, '',$image_url, $image, time());
        $sql = "INSERT INTO `data_actors_imdb` (`id`, `imdb_id`, `name`, `birth_name`, `birth_place`, `burn_date`, `description`, `image_url`, `image`, `lastupdate`) 
                                            VALUES ( '" . $actor_id . "' ,'" . $imdb_id . "' , ?, ?, ?, ?, ?, ?, ?, ?)";
        Pdo_an::db_results_array($sql,$array_request);
        if ($debug)echo 'adedded ' . $actor_id .' '.$name. '<br>' . PHP_EOL;
        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'insert', 'data_actors_imdb', array('id' => $actor_id), 'actor_add',40);
    }
    ///check actor meta


    if ($image == 'Y')
    {

        $array_insert = check_enable_actor_meta($actor_id);

        $q = "SELECT `id` FROM `data_actors_meta` WHERE ( `img` IS NULL OR `img` = 0) and `actor_id` = ".$actor_id;
        $r = Pdo_an::db_results_array($q);
        if ($r)
        {
            //updata
            $q="UPDATE `data_actors_meta` SET `last_update` =".time().",`img` =1 WHERE `actor_id`=".$actor_id;
            //echo $q;
            Pdo_an::db_query($q);
            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            Import::create_commit('', 'update', 'data_actors_meta', array('actor_id' => $actor_id), 'actor_meta',9,['skip'=>['id']]);
        }
    }
    return 1;
}

function add_actors_to_db($id, $imdb_id='',$update = 0)
{

    if (!$imdb_id)
    {
        $sql = "SELECT  `imdb_id` FROM `data_actors_imdb` where id =".intval($id);
        $r= Pdo_an::db_results_array($sql);
        $imdb_id =$r[0]['imdb_id'];
    }

    global $debug;
    $final_value = sprintf('%07d', $imdb_id);


    $url = 'https://www.imdb.com/name/nm' . $final_value . '/bio/';

   //echo $url . PHP_EOL;

    $result = GETCURL::getCurlCookie($url);

    $array_result = get_imdb_actor_parse_inner($result);

    if  ($debug)var_dump_table($array_result);
    if ($array_result) {

        return addto_db_actors($id,$imdb_id, $array_result, $update,$debug);


    } else {

        $sql = "UPDATE `data_actors_imdb` SET `lastupdate` = '".time()."' WHERE `data_actors_imdb`.`id` = {$id}";
        Pdo_an::db_query($sql);
    }

}
 function var_dump_table($data,$row='',$return ='') {

    $result = TMDB::var_dump_table($data,$row,$return);
    return $result;
}
function intconvert($data)
{
    !class_exists('INTCONVERT') ? include ABSPATH . "analysis/include/intconvert.php" : '';
    return INTCONVERT::str_to_int($data);

}
function check_verdict_surname($commit_actors=[])
{
    $i = 0;

    $sql="SELECT * FROM `data_actors_ethnicolr` WHERE verdict ='' AND firstname!='' LIMIT 500";
    $result= Pdo_an::db_results_array($sql);
    foreach ($result as $r) {

        $meta_result = get_actor_result_new($r['wiki']);
        //echo $meta_result;
        $i++;

        $commit='';

        if ($meta_result && $r['firstname']) {


            $sql="UPDATE `data_actors_ethnicolr` SET `verdict` = '{$meta_result}' WHERE `data_actors_ethnicolr`.`id` = ".$r['id'];
            Pdo_an::db_query($sql);


            $sql1 = "UPDATE `data_actors_meta` SET  `n_surname` = '" . intconvert($meta_result) . "',
              `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['aid'] . "'";
            Pdo_an::db_query($sql1);

            update_actors_verdict($r['aid'],1,0);

            $commit_actors[$r['aid']]=1;


            !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';
            ACTIONLOG::update_actor_log('surname','data_actors_ethnicolr',$r['aid']);

            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            $commit =Import::create_commit($commit, 'update', 'data_actors_ethnicolr', array('id' =>  $r['id']), 'ethnicolr',10);

        }

    }

return [$i,$commit_actors];
}


function add_movie_production()
{
    $q = "SELECT data_movie_imdb.`id`, data_movie_imdb.`production` FROM `data_movie_imdb` left join meta_movie_distributors as meta ON (meta.mid =data_movie_imdb.id ) where `production` !='' and `production` IS NOT NULL and meta.id IS NULL order by id asc LIMIT 1000";

    $s = Pdo_an::db_results_array($q);
    foreach ($s as $row)
    {

    TMDB::add_movie_distributors($row['id'],$row['production'],1,'cron');

        echo 'try update ' . $row['id'] . PHP_EOL;
        if (check_cron_time())
        {
            break;
        }
    }

}

function update_pg_rating_cms($id){

$imdb_id = TMDB::get_imdb_id_from_id($id);

    !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';
    PgRating::update_pg_rating_cms($imdb_id ,1);
}



function add_empty_actors($id='')
{
    ///update actor data
    global $debug;

    check_load(50,60);

    if ($id)
    {
        $where =' imdb_id = '.intval($id);
    }
    else
    {
        $where="( lastupdate = '0' OR  (`name` = '' and lastupdate != '0' and lastupdate < ".(time()-86400).") OR ((`birth_name` = '' OR `birth_place` = '' OR `burn_date` = '' OR `image_url` = NULL) and lastupdate != '0' and lastupdate < ".(time()-86400*60).")  )";
    }

            $sql = "SELECT `id`, `imdb_id` FROM `data_actors_imdb` where  ".$where."  limit 60";
            // if ($debug)echo $sql.PHP_EOL;
             $result= Pdo_an::db_results_array($sql);

            if ($id && !$result)
            {
                ///add empty
                $imdb_id =$result[0]['imdb_id'];
                $resultdata = add_actors_to_db($id, $imdb_id,1);
                ////logs
                TMDB::add_log($id,'','update actors','result: '.$resultdata.' aid:'.$id,1,'add_empty_actors');
            }
            else
            {

            foreach ($result as $r) {
                $imdb_id =$r['imdb_id'];
                $id = $r['id'];
               if ($debug) echo '  try add actor ' . $id . PHP_EOL;
                $result = add_actors_to_db($id,$imdb_id, 1);
                ////logs
                TMDB::add_log($id,'','update actors','result: '.$result.' aid:'.$id,1,'add_empty_actors');

                if (check_cron_time())
                {
                    break;
                }
                sleep(0.5);

            }
            }

}

function check_enable_actor_meta($id='',$commit_actors=[],$debug=0)
{
    $dop='';
    if ($id){$dop = ' and `data_actors_imdb`.id = '.$id.' ';}

    $sql = "SELECT `data_actors_imdb`.id FROM `data_actors_imdb`
        LEFT JOIN `data_actors_meta` ON `data_actors_imdb`.id=`data_actors_meta`.actor_id
        WHERE `data_actors_meta`.actor_id IS NULL ".$dop." LIMIT 10000";

    $i = 0;
    $result= Pdo_an::db_results_array($sql);
    foreach ($result as $r) {
        $i++;
        $sql1 = "INSERT INTO `data_actors_meta` (`id`, `actor_id`)  VALUES (NULL, '" . $r['id'] . "')";
        Pdo_an::db_query($sql1);

        ACTIONLOG::update_actor_log('data_actors_meta','data_actors_meta',$r['id']);
        $commit_actors[$r['id']]=1;
    }
    if ($debug)echo 'check actors meta (' . $i . ')' . PHP_EOL;

    return $commit_actors;

}


function check_last_actors($aid ='')
{
    start_cron_time(50);
    global $debug;

    if ($debug)
    {
        echo 'check_last_actors start<br>';
    }

    !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';

    ACTIONLOG::clear_history();

    $commit_actors = [];

    //////check actors meta

    $commit_actors = check_enable_actor_meta($aid,$commit_actors,$debug);


    if (check_cron_time())
    {
        commit_actors($commit_actors);
        return;
    }
    //////check actors surname
    ///
    if ($debug)
    {
        $timeleft = check_cron_time(1);

        echo $timeleft.' check_enable_actor_meta <br>'.PHP_EOL;

    }


    $i=0;

    //check actor gender
    $sql = "SELECT data_actors_gender.actor_id,  data_actors_gender.Gender 	  FROM `data_actors_gender`
    LEFT JOIN data_actors_meta ON data_actors_gender.actor_id=data_actors_meta.actor_id
        WHERE data_actors_meta.gender = 0 and data_actors_meta.actor_id >0  limit 10000";
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
        ACTIONLOG::update_actor_log('gender','data_actors_meta',$r['actor_id']);
    }


    if ($debug)
    {
        $timeleft = check_cron_time(1);

        echo $timeleft.'   check actor gender (' . $i . ')<br>'.PHP_EOL;

    }

    if (check_cron_time())
    {
        commit_actors($commit_actors);
        return;
    }
    $i=0;
    //check actor gender auto
    $sql = "SELECT data_actor_gender_auto.actor_id,  data_actor_gender_auto.gender 	  FROM `data_actor_gender_auto`
    LEFT JOIN data_actors_meta ON data_actor_gender_auto.actor_id=data_actors_meta.actor_id
        WHERE ( data_actors_meta.gender =0 ) and data_actors_meta.actor_id >0  and data_actor_gender_auto.gender>0 limit 10000";
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
        ACTIONLOG::update_actor_log('gender_auto','data_actors_meta',$r['actor_id'] );

        $commit_actors[$r['actor_id']]=1;
    }

    if ($debug)
    {
        $timeleft = check_cron_time(1);

        echo $timeleft.'   check actor gender auto (' . $i . ')<br>'.PHP_EOL;

    }

    if (check_cron_time())
    {
        commit_actors($commit_actors);
        return;
    }


    $array_min = array('Asian' => 'EA', 'White' => 'W', 'Latino' => 'H', 'Black' => 'B', 'Arab' => 'M', 'Dark Asian' => 'I',
        'Jewish' => 'JW', 'Other' => 'MIX', 'Mixed / Other' => 'MIX', 'Indigenous' => 'IND',
        'Not a Jew' => 'NJW', 'Sadly, not' => 'NJW','Barely a Jew'=>'JW',
        'Borderline Jew'=>'JW','Jew'=>'JW','Sadly, a Jew'=>'JW','Infinitesimally a Jew'=>'JW','Sadly, not a Jew'=>'NJW');
    global $array_compare;
    if (!$array_compare)
    {
        $array_compare = TMDB::get_array_compare();
    }

//    ////check actor jew



    [$i,$commit_actors] = check_verdict_surname($commit_actors);


    if ($debug)
    {
        $timeleft = check_cron_time(1);

        echo $timeleft.'   check_verdict_surname (' . $i . ') <br>'.PHP_EOL;

    }
    if (check_cron_time())
    {
        commit_actors($commit_actors);
        return;
    }


    $i=0;
    ////check actor ethnic
    !class_exists('Ethinc') ? include ABSPATH . "analysis/include/ethnic.php" : '';

    $q ="SELECT `actor_id`  FROM `data_actors_ethnic` WHERE `actor_id`> 0 and`verdict` is NULL and last_update_verdict < ".(time()-86400*7)." and (`Ethnicity` IS NOT NULL OR `Tags` IS NOT NULL) limit 100";
    $r = Pdo_an::db_results_array($q);
    foreach ($r as $row)
    {
        $actor_id  = $row['actor_id'];
        Ethinc::set_actors_ethnic($actor_id,0,0);

        if (check_cron_time())
        {
        break;
        }
    }


    if ($debug)
    {
        $timeleft = check_cron_time(1);
        echo $timeleft.' check actor ethnic (' . $i . ') <br>'.PHP_EOL;

    }
    if (check_cron_time())
    {
        commit_actors($commit_actors);
        return;
    }

//    $i=0;
//
//    $sql = "SELECT data_actors_ethnic.*  FROM `data_actors_ethnic` LEFT JOIN data_actors_meta ON data_actors_ethnic.actor_id=data_actors_meta.actor_id
//        WHERE data_actors_meta.n_ethnic =0 and (data_actors_ethnic.verdict !='' and data_actors_ethnic.verdict IS NOT NULL )  limit 100";
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
//            $sql1 = "UPDATE `data_actors_meta` SET  n_ethnic ='".intconvert($verdict_result) ."'  WHERE `data_actors_meta`.`actor_id` = '" . $actor_id . "'";
//            Pdo_an::db_query($sql1);
//            $i++;
//
//            update_actors_verdict($actor_id,1,0);
//            ACTIONLOG::update_actor_log('add_actors_ethnic','data_actors_meta',$actor_id );
//            $commit_actors[$actor_id]=1;
//        }
//
//    }
//
//
//
//    if ($debug)
//    {
//        $timeleft = check_cron_time(1);
//        echo $timeleft.' check actor ethnic (' . $i . ') <br>'.PHP_EOL;
//
//    }
//    if (check_cron_time())
//    {
//        commit_actors($commit_actors);
//        return;
//    }



   ////crowd

    [$commit_actors,$i] =  update_crowd_verdict($commit_actors);


    if ($debug)
    {
        $timeleft = check_cron_time(1);
        echo $timeleft.' update crowd (' . $i . ') <br>'.PHP_EOL;

    }

    if (check_cron_time())
    {
        commit_actors($commit_actors);
        return;
    }

///betta face

    $array_face = array('white' => 'W', 'hispanic' => 'H', 'black' => 'B', 'mideast' => 'M', 'indian' => 'I', 'asian' => 'EA');

    $i = 0;
    ////check actor face
    $sql = "SELECT data_actors_face.actor_id, data_actors_face.race  FROM `data_actors_face` LEFT JOIN data_actors_meta ON data_actors_face.actor_id=data_actors_meta.actor_id
        WHERE data_actors_meta.n_bettaface = 0 and data_actors_meta.actor_id >0 and data_actors_face.race IS NOT NULL limit 300";
    $result= Pdo_an::db_results_array($sql);
    foreach ($result as $r) {

        $verdict =$r['race'];

        if ($array_face[$verdict]) {
            $verdict = $array_face[$verdict];
        }

        if ($verdict) {
            $enable_image = $verdict;

            $i++;
            $sql1 = "UPDATE `data_actors_meta` SET
           `n_bettaface` = '" . intconvert($enable_image) . "',
           `last_update` = " . time() . "  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";
            Pdo_an::db_query($sql1);


            update_actors_verdict($r['actor_id']);
            ACTIONLOG::update_actor_log('n_bettaface','data_actors_meta',$r['actor_id']);
            $commit_actors[$r['actor_id']]=1;
        }

        if (check_cron_time())
        {
        break;
        }
    }
    if ($debug)
    {
        $timeleft = check_cron_time(1);

        echo $timeleft.' check  data_actors_face (' . $i . ') <br>'.PHP_EOL;

    }
    if (check_cron_time())
    {
        commit_actors($commit_actors);
        return;
    }






    $i = 0;


    /////////crowd
    $sql = "SELECT data_actors_crowd_race.actor_id, data_actors_crowd_race.kairos_verdict  FROM `data_actors_crowd_race` 
    LEFT JOIN data_actors_meta ON data_actors_crowd_race.actor_id=data_actors_meta.actor_id
        WHERE (data_actors_meta.n_kairos =0) 
          and data_actors_meta.actor_id >0 
          and  data_actors_crowd_race.kairos_verdict !=''
           limit 100";

    $result= Pdo_an::db_results_array($sql);
    $i =count($result);

    foreach ($result as $r) {
        $kairos = $r['kairos_verdict'];
        $i++;
        $sql1 = "UPDATE `data_actors_meta` SET 
         `n_kairos` = '" . intconvert($kairos) . "',
         
        `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";


        ///echo $sql1;

        Pdo_an::db_query($sql1);
        update_actors_verdict($r['actor_id']);
        ACTIONLOG::update_actor_log('kairos','data_actors_crowd_race',$r['actor_id']);

        $commit_actors[$r['actor_id']]=1;

        if (check_cron_time())
        {
            break;
        }
    }


    if ($debug)
    {
        $timeleft = check_cron_time(1);

        echo $timeleft.' check actor kairos crowd (' . $i . ') <br>'.PHP_EOL;

    }
    if (check_cron_time())
    {
        commit_actors($commit_actors);
        return;
    }
////check actor kairos tmdb
    $sql = "SELECT data_actors_tmdb_race.actor_id, data_actors_tmdb_race.kairos_verdict  FROM `data_actors_tmdb_race` 
    LEFT JOIN data_actors_meta ON data_actors_tmdb_race.actor_id=data_actors_meta.actor_id
        WHERE (data_actors_meta.n_kairos =0 ) 
          and data_actors_meta.actor_id >0 
          and  data_actors_tmdb_race.kairos_verdict !=''
           limit 300";

    $result= Pdo_an::db_results_array($sql);
    $i =count($result);
    foreach ($result as $r) {
        $kairos = $r['kairos_verdict'];
        $i++;
        $sql1 = "UPDATE `data_actors_meta` SET 
         `n_kairos` = '" . intconvert($kairos) . "',
         
        `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";
        Pdo_an::db_query($sql1);
        update_actors_verdict($r['actor_id']);
        ACTIONLOG::update_actor_log('kairos','data_actors_tmdb_race',$r['actor_id']);

        $commit_actors[$r['actor_id']]=1;

        if (check_cron_time())
        {
            break;
        }
    }


    if ($debug)
    {
        $timeleft = check_cron_time(1);

        echo $timeleft.' check actor kairos tmdb (' . $i . ') <br>'.PHP_EOL;

    }
    if (check_cron_time())
    {
        commit_actors($commit_actors);
        return;
    }


    $i = 0;
    ////check actor kairos imdb

    $sql = "SELECT data_actors_race.actor_id , data_actors_race.kairos_verdict  FROM `data_actors_race` LEFT JOIN data_actors_meta ON data_actors_race.actor_id=data_actors_meta.actor_id
        WHERE data_actors_meta.n_kairos =0 and  data_actors_race.kairos_verdict is not NULL and data_actors_race.kairos_verdict!='' limit 300";
    $result= Pdo_an::db_results_array($sql);
    foreach ($result as $r) {
        $kairos = $r['kairos_verdict'];
        $i++;
        $sql1 = "UPDATE `data_actors_meta` SET 
        `n_kairos` = '" . intconvert($kairos) . "',
        
        `last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $r['actor_id'] . "'";
        Pdo_an::db_query($sql1);
        update_actors_verdict($r['actor_id']);
        ACTIONLOG::update_actor_log('kairos','data_actors_race',$r['actor_id'] );

        $commit_actors[$r['actor_id']]=1;

        if (check_cron_time())
        {
            break;
        }
    }


    if ($debug)
    {
        $timeleft = check_cron_time(1);

        echo $timeleft.' check actor kairos imdb (' . $i . ') <br>'.PHP_EOL;

    }

    commit_actors($commit_actors);

}




function commit_actors($commit_actors)
{

    if ( $commit_actors)
    {
        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';

        foreach ($commit_actors as $actor_id=>$enable)
        {
            Import::create_commit('', 'update', 'data_actors_meta', array('actor_id' => $actor_id), 'actor_meta',3,['skip'=>['id']]);

        }
    }

}

function force_surname_update()
{

    !class_exists('CPULOAD') ? include ABSPATH . "service/cpu_load.php" : '';
    $load = CPULOAD::check_load();
    if ($load['loaded']) {  return;  }

    start_cron_time(550);
    set_time_limit(600);
    echo 'check actors surname <br>'.PHP_EOL;
    //////check actors surname
    $i = 0;
    $sql = "SELECT data_actors_meta.id, data_actors_meta.actor_id, data_actors_meta.n_surname, data_actors_ethnicolr.* from data_actors_meta, data_actors_ethnicolr where data_actors_meta.actor_id =data_actors_ethnicolr.aid and  data_actors_meta.n_surname >0 and	data_actors_ethnicolr.firstname='' and data_actors_ethnicolr.verdict!='' and data_actors_meta.id>1 limit 10000 ";
    $result= Pdo_an::db_results_array($sql);
    $count = count($result);
    foreach ($result as $r) {
        if (check_cron_time())break;
        $id= get_actor_result($r['id']);
        $i++;

        if ($id) {

            $sql1 = "UPDATE `data_actors_meta` SET `n_surname`=0 ,`last_update` = ".time()."  WHERE `data_actors_meta`.`id` = '" .$id . "'";
            Pdo_an::db_query($sql1);
            update_actors_verdict($r['actor_id'],1);
        }
        echo $r['actor_id']. '  (' . $i . ' / '.$count.') <br>' . PHP_EOL;
    }


}




function get_actor_result_new($data)
{

    //[["name", "last",  "id", "__name","rowindex", "Asian,GreaterEastAsian,EastAsian_mean", "Asian,GreaterEastAsian,EastAsian_std", "Asian,GreaterEastAsian,EastAsian_lb", "Asian,GreaterEastAsian,EastAsian_ub", "Asian,GreaterEastAsian,Japanese_mean", "Asian,GreaterEastAsian,Japanese_std", "Asian,GreaterEastAsian,Japanese_lb", "Asian,GreaterEastAsian,Japanese_ub", "Asian,IndianSubContinent_mean", "Asian,IndianSubContinent_std", "Asian,IndianSubContinent_lb", "Asian,IndianSubContinent_ub", "GreaterAfrican,Africans_mean", "GreaterAfrican,Africans_std", "GreaterAfrican,Africans_lb", "GreaterAfrican,Africans_ub", "GreaterAfrican,Muslim_mean", "GreaterAfrican,Muslim_std", "GreaterAfrican,Muslim_lb", "GreaterAfrican,Muslim_ub", "GreaterEuropean,British_mean", "GreaterEuropean,British_std", "GreaterEuropean,British_lb", "GreaterEuropean,British_ub", "GreaterEuropean,EastEuropean_mean", "GreaterEuropean,EastEuropean_std", "GreaterEuropean,EastEuropean_lb", "GreaterEuropean,EastEuropean_ub", "GreaterEuropean,Jewish_mean", "GreaterEuropean,Jewish_std", "GreaterEuropean,Jewish_lb", "GreaterEuropean,Jewish_ub", "GreaterEuropean,WestEuropean,French_mean", "GreaterEuropean,WestEuropean,French_std", "GreaterEuropean,WestEuropean,French_lb", "GreaterEuropean,WestEuropean,French_ub", "GreaterEuropean,WestEuropean,Germanic_mean", "GreaterEuropean,WestEuropean,Germanic_std", "GreaterEuropean,WestEuropean,Germanic_lb", "GreaterEuropean,WestEuropean,Germanic_ub", "GreaterEuropean,WestEuropean,Hispanic_mean", "GreaterEuropean,WestEuropean,Hispanic_std", "GreaterEuropean,WestEuropean,Hispanic_lb", "GreaterEuropean,WestEuropean,Hispanic_ub", "GreaterEuropean,WestEuropean,Italian_mean", "GreaterEuropean,WestEuropean,Italian_std", "GreaterEuropean,WestEuropean,Italian_lb", "GreaterEuropean,WestEuropean,Italian_ub", "GreaterEuropean,WestEuropean,Nordic_mean", "GreaterEuropean,WestEuropean,Nordic_std", "GreaterEuropean,WestEuropean,Nordic_lb", "GreaterEuropean,WestEuropean,Nordic_ub", "race"]]
    //["Tulia", "Virgin", 11667204, "Virgin Tulia", 49,
    // 0.011389325857162475,
    // 0.010472159861481778,
    // 0.0006224170792847872,
    // 0.0010987643618136644, 0.0032547590136528014, 0.0020483294718464944, 0.00046023691538721323, 0.000548442592844367, 0.0044488230347633365, 0.004204079526108209, 0.00011748091492336243, 0.0001409576361766085, 0.009014605283737183, 0.00684514954666181, 0.0010546577395871282, 0.0011152317747473717, 0.06543373107910157, 0.06837851900000398, 0.0061302026733756065, 0.0076684788800776005, 0.3424713134765625, 0.1350352153469736, 0.06270340830087662, 0.07970203459262848, 0.15654437065124513, 0.1154234764313792, 0.012365087866783142, 0.019241709262132645, 0.11423819541931152, 0.08214548885646157, 0.018700571730732918, 0.025969358161091805, 0.13782186508178712, 0.0736318753453318, 0.032585062086582184, 0.03421637788414955, 0.0071594822406768795, 0.0039413855782467885, 0.0016408725641667843, 0.0016677659004926682, 0.05381762981414795, 0.030375072028892718, 0.008716919459402561, 0.01087616290897131, 0.0773210334777832, 0.050100027890374564, 0.01579379476606846, 0.016442598775029182, 0.017084892988204956, 0.017698000887109155, 0.0012373499339446425, 0.0015180566115304828, "GreaterEuropean,British"]


    $actor_data = [];
    if ($data) {
        $data = json_decode($data,1);

        //0 "name",
        //1 "last",
        //2 "id",
        //3 "__name",
        //4 "rowindex",

        $actor_data['EA'] += (float)$data[5] * 100;
        //5 "Asian,GreaterEastAsian,EastAsian_mean", EA
        //6 "Asian,GreaterEastAsian,EastAsian_std", EA
        //7 "Asian,GreaterEastAsian,EastAsian_lb",  EA
        //8 "Asian,GreaterEastAsian,EastAsian_ub",  EA


        $actor_data['EA'] += (float)$data[9] * 100;
        //9 "Asian,GreaterEastAsian,Japanese_mean", EA
        //10 "Asian,GreaterEastAsian,Japanese_std", EA
        //11 "Asian,GreaterEastAsian,Japanese_lb",  EA
        //12 "Asian,GreaterEastAsian,Japanese_ub",  EA

        $actor_data['I'] += (float)$data[13] * 100;
        //13 "Asian,IndianSubContinent_mean",   I
        //14 "Asian,IndianSubContinent_std",   I
        //15 "Asian,IndianSubContinent_lb",   I
        //16 "Asian,IndianSubContinent_ub",   I

        $actor_data['B'] += (float)$data[17] * 100;
        //17 "GreaterAfrican,Africans_mean",    B
        //18 "GreaterAfrican,Africans_std",    B
        //19 "GreaterAfrican,Africans_lb",    B
        //20 "GreaterAfrican,Africans_ub",    B

        $actor_data['M'] += (float)$data[21] * 100;
        //21 "GreaterAfrican,Muslim_mean",  M
        //22 "GreaterAfrican,Muslim_std",  M
        //23 "GreaterAfrican,Muslim_lb",  M
        //24 "GreaterAfrican,Muslim_ub",  M

        $actor_data['W'] += (float)$data[25] * 100;
        //25 "GreaterEuropean,British_mean",    W
        //26 "GreaterEuropean,British_std",    W
        //27 "GreaterEuropean,British_lb",    W
        //28 "GreaterEuropean,British_ub",    W

        $actor_data['W'] += (float)$data[29] * 100;
        //29 "GreaterEuropean,EastEuropean_mean",    W
        //30 "GreaterEuropean,EastEuropean_std",    W
        //31 "GreaterEuropean,EastEuropean_lb",    W
        //32 "GreaterEuropean,EastEuropean_ub",    W

        $actor_data['JW'] += (float)$data[33] * 100;
        //33 "GreaterEuropean,Jewish_mean",     JW
        //34 "GreaterEuropean,Jewish_std",     JW
        //35 "GreaterEuropean,Jewish_lb",     JW
        //36 "GreaterEuropean,Jewish_ub",     JW

        $actor_data['W'] += (float)$data[37] * 100;
        //37 "GreaterEuropean,WestEuropean,French_mean",    W
        //38 "GreaterEuropean,WestEuropean,French_std",    W
        //39 "GreaterEuropean,WestEuropean,French_lb",    W
        //40 "GreaterEuropean,WestEuropean,French_ub",    W

        $actor_data['W'] += (float)$data[41] * 100;
        //41 "GreaterEuropean,WestEuropean,Germanic_mean",    W
        //42 "GreaterEuropean,WestEuropean,Germanic_std",    W
        //43 "GreaterEuropean,WestEuropean,Germanic_lb",    W
        //44 "GreaterEuropean,WestEuropean,Germanic_ub",    W

        $actor_data['H'] += (float)$data[45] * 100;
        //45 "GreaterEuropean,WestEuropean,Hispanic_mean",  H
        //46 "GreaterEuropean,WestEuropean,Hispanic_std",  H
        //47 "GreaterEuropean,WestEuropean,Hispanic_lb",  H
        //48 "GreaterEuropean,WestEuropean,Hispanic_ub",  H

        $actor_data['W'] += (float)$data[49] * 100;
        //49 "GreaterEuropean,WestEuropean,Italian_mean",   W
        //50 "GreaterEuropean,WestEuropean,Italian_std",   W
        //51 "GreaterEuropean,WestEuropean,Italian_lb",   W
        //52 "GreaterEuropean,WestEuropean,Italian_ub",   W

        $actor_data['W'] += (float)$data[53] * 100;
        //53 "GreaterEuropean,WestEuropean,Nordic_mean",   W
        //54 "GreaterEuropean,WestEuropean,Nordic_std",   W
        //55 "GreaterEuropean,WestEuropean,Nordic_lb",   W
        //56 "GreaterEuropean,WestEuropean,Nordic_ub",   W

        //57 "race"


    }

    arsort($actor_data);
    $key = array_keys($actor_data);
    $surname = $key[0];

    if (!$actor_data[$surname])
    {
        $surname='NA';
    }

    if ($surname) {
        return $surname;
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



function update_all_pg_rating()
{

    !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';


    set_time_limit(0);
    $sql ="SELECT * FROM `data_movie_imdb` ORDER BY `data_movie_imdb`.`id` ASC ";
    $rows = Pdo_an::db_results_array($sql);
    $count = count($rows);

    if (!$count)
    {

    }
   $i =0;

    foreach ($rows as $r )
    {

        $id =$r['id'];
        $movie_id =$r['movie_id'];
        $title =$r['movie_title'];


        $rating = PgRatingCalculate::CalculateRating($movie_id,$id,0,1);//update_all_pg_rating

        echo '<span style="display: inline-block; width: 120px">'.$i.' of '.$count.'</span><span style="display: inline-block; width: 80px">'.$movie_id.'</span><span style="display: inline-block; width: 400px">'.$title.'</span><span style="display: inline-block; width: 100px">'.$rating.'</span><br><hr>'.PHP_EOL;
        $i++;



    }

}
function update_pgrating($imdb_id='')
{
    $id='';
    if (isset($_GET['id']))
    {
        $id = intval($_GET['id']);
    }
    !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';

    PgRatingCalculate::CalculateRating($imdb_id, $id, 1);//update_pgrating


}



function set_actors_ethnic($id)
{
    !class_exists('Ethinc') ? include ABSPATH . "analysis/include/ethnic.php" : '';

global $debug;


        if (isset($_GET['debug']))$debug =1;

        if (isset($_GET['force']))$force =1;

        Ethinc::set_actors_ethnic($id,$force,$debug);


//
//    if (isset($_GET['update']))
//    {
//       Ethinc::update_verdict_meta($id);
//    }

}
function set_actors_ethnic_vedict($id)
{
    !class_exists('Ethinc') ? include ABSPATH . "analysis/include/ethnic.php" : '';

        Ethinc::check_verdict($id);


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
function add_pg_rating_for_new_movies($limit=100)
{
    if (!$limit)$limit=1000;
    global $debug;

    if (isset($_GET['debug']))$debug=1;
    !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';

    $rating_update = array( 50=> 86400*7, 40 =>86400*14, 30=> 86400*30 , 20=> 86400*60, 10=> 86400*120, 0=>86400*200);
    $rows =get_weight_list('data_pg_rating','last_update',"rwt_id",$limit,$rating_update);

       if ($rows)
       {

           foreach ($rows as $r)
           {

              /// $imdb_id = TMDB::get_imdb_id_from_id($r['id']);
               echo $r['id'].' <br>';
               PgRating::update_pgrating('',0,$r['id']);

           }
       }

}


function add_pgrating($id='')
{
    global $debug;
    !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';
if (isset($_GET['debug']))$debug=1;

    PgRating::add_pgrating($id,$debug);

    return;
}



function check_face($data='')
{
    if (!class_exists('BETTAFACE')){include(ABSPATH.'analysis/include/bettaface.php');}
    BETTAFACE::Prepare($data);

return;

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
            !class_exists('KAIROS') ? include ABSPATH . "analysis/include/kairos.php" : '';
            $img_64 = KAIROS::create_image_64($actor_id);
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
function check_imdb($last_id = 0,$logdata='check_imdb')
{


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
                    $add =  TMDB::addto_db_imdb($movie_id, $array_movie,'','',$logdata);


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
                    $add =  TMDB::addto_db_imdb($movie_id, $array_movie,'','','check_tv_series_imdb');

                    if (!$add) {
                        echo $movie_id . ' not addeded ' . PHP_EOL;
                    }

                }
                else {
                    echo $movie_id . ' already adedded' . PHP_EOL;
                }
            set_option(12, $last_id);
            if ($i >10) {
                break;
            }

            }


        if (check_cron_time())
        {
            break;
        }
        }

}

function zr_woke($mid=0)
{

  // check_load(30,0);

   $debug= $_GET['debug'];
   !class_exists('WOKE') ? include ABSPATH . "analysis/include/woke.php" : '';
   $woke = new WOKE;
   $woke->zr_woke($mid,$debug);

}


function check_best_games($last_id = 0)
{

    check_load(250,300);

    $array_movie = get_last_options('','best_games');
    //// echo $array_movie;

    if ($array_movie) {
        $array_movie = explode(',', $array_movie);
    }
    if (!$last_id) {
        $last_id = get_last_options('','best_games_last_id');
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
                $add =  TMDB::addto_db_imdb($movie_id, $array_movie,'','','check_best_games');

                if (!$add) {
                    echo $movie_id . ' not addeded ' . PHP_EOL;
                }

            }
            else {
                echo $movie_id . ' already adedded' . PHP_EOL;
            }
            set_option('', $last_id,'best_games_last_id');
            sleep(1);
        }


        if (check_cron_time())
        {
            echo 'end time '.check_cron_time();
            break;
        }

    }

}


function add_tv_shows_to_options()
{

    $array_year = [];
    $year_end = date('Y', time());
    $count=1;
    for ($count = 1;$count<=1000;$count+=50)
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

    $array_year = implode(',',$array_year );
    print_r($array_year);

    set_option(11, $array_year);
    set_option(12, 0);
    return;
}
function add_games_to_options()
{

    $array_year = [];
    $year_end = date('Y', time());
    $count=1;
    for ($count = 1;$count<=1000;$count+=50)
    {
        $link = 'https://www.imdb.com/search/title/?title_type=video_game&start='.$count.'&ref_=adv_nxt';

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

    $array_year = implode(',',$array_year );
    print_r($array_year);

    set_option('', $array_year,'best_games');
    set_option('', 0,'best_games_last_id');
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
    global $debug;
    if (isset($_GET['debug']))
    {
        $debug=1;
    }

    !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';
    $where='';
    if ($pid)
    {
        $where = " where id=".intval($pid);
    }
    if (isset($_GET['start']))
    {
        $where = " where id>".intval($_GET['start']);
    }
    global $table_prefix;
    $data = new RWT_RATING;

    $sql = "SELECT id, movie_id FROM `data_movie_imdb` ".$where." order by id asc";


    if ($debug)
    {
        echo $sql.'<br>';
    }

    $rows = Pdo_an::db_results_array($sql);
    $count = count($rows);
    $i=0;
    foreach ($rows as $r2)
    {
        $i++;
        $id = $r2['id'];
        $movie_id = $r2['movie_id'];


        $data->gender_and_diversity_rating($id,$movie_id,1,$debug); ///update_all_gender_cache
        echo $i.' of '.$count.' id='.$id.'<br>'.PHP_EOL;
    }
}

function update_audience_rating($rwt_id,$audiencetype=1)
{
    global $debug;
    if (isset($_GET['debug'])){$debug=1;}
    !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';

    $result =PgRatingCalculate::rwt_audience($rwt_id,1,1);



}


function update_all_audience_and_staff()
{
    !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';
    global $table_prefix;
    $data = new RWT_RATING;

    for ($type = 1; $type < 3; $type++)
    {

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

            $result =PgRatingCalculate::rwt_audience($rwt_id,1,1);
            PgRatingCalculate::add_movie_rating($rwt_id,'',0,1);
            echo $i.' of '.$count. ' id=' . $rwt_id .' '.$type.'<br>'. PHP_EOL;
            print_r($result);
            echo '<br><hr><br>';
            $i++;
        }
    }
}



function update_just_watch()
{

    global $debug;

    !class_exists('CPULOAD') ? include ABSPATH . "service/cpu_load.php" : '';
    $load = CPULOAD::check_load();
    if ($load['loaded']) {  return;  }

    start_cron_time(55);
    $limit=50;

    !class_exists('JustWatch') ? include ABSPATH . "analysis/include/justwatch.php" : '';

    $dop_request=" and (`data_movie_imdb`.`type`='TVSeries' OR `data_movie_imdb`.`type`='Movie' )";
    $rating_update = array( 50=> 86400*7, 40 =>86400*14, 30=> 86400*21 , 20=> 86400*30, 10=> 86400*60, 0=>86400*90);
    $rows =get_weight_list('just_wach','last_update',"rwt_id",$limit,$rating_update,$dop_request);


    $count = count($rows);
    $i=0;
    foreach ($rows as $r2)
    {
        $i++;
        $id = $r2['id'];
        JustWatch::get_just_wach($id,1);


        echo $i.' of '.$count.' id='.$id.'<br>'.PHP_EOL;

        if (check_cron_time())
        {
            sleep(1);
            break;
        }

    }



}



function add_providers()
{

 !class_exists('JustWatch') ? include ABSPATH . "analysis/include/justwatch.php" : '';

 JustWatch::get_providers();

return;



}

function add_imdb_data_to_options()
{

    $array_year = [];
    $year_end = date('Y', time());
///for ($year = 1977;$year<=$year_end;$year++)
    {
        $year = $year_end;
        $mount = strtolower(date('F', time() - 86400 * 7));

        $link = 'https://www.boxofficemojo.com/month/' . $mount . '/' . $year . '/';

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

    $array_year = implode(',',$array_year );
   // print_r($array_year);

    set_option(9, $array_year);
    set_option(10, 0);
    check_imdb(1,'add_imdb_data_to_options');


}
function check_kairos($id='')
{
    global $debug;
    $debug = $_GET['debug'];

    !class_exists('CPULOAD') ? include ABSPATH . "service/cpu_load.php" : '';
    $load = CPULOAD::check_load();
    if ($load['loaded']) {  return;  }

    start_cron_time(50);


    !class_exists('KAIROS') ? include ABSPATH . "analysis/include/kairos.php" : '';

    KAIROS::check_actors($id);

}
function kairos_prepare_arrays($id='')
{

    global $debug;
    $debug = $_GET['debug'];
    $type =  $_GET['type'];
    if (!$type)$type ='imdb';

    !class_exists('KAIROS') ? include ABSPATH . "analysis/include/kairos.php" : '';
    $rows=[];

    $rows[0]->id = intval($id);
    if ($id)
    {
        KAIROS::prepare_arrays($rows, $type);

    }
return;

}


global $included;
if ($included) return;





if (isset($_GET['add_rating'])) {

    add_rating();

    return;
}

if (isset($_GET['check_last_actors'])) {
    check_load(55,300);
    global $debug;
    if (isset($_GET['debug']))
    {
        $debug=1;
    }

    check_last_actors($_GET['check_last_actors']);
    return;
}
if (isset($_GET['add_imdb_data_to_options'])) {

    add_imdb_data_to_options();
    return;
}
if (isset($_GET['check_imdb'])) {

    check_imdb(0,'get_check_imdb');
    return;
}
if (isset($_GET['check_face'])) {

    if (isset($_GET['debug']))
    {
        global $debug;
        $debug=$_GET['debug'];
    }


check_face($_GET['check_face']);
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

    global $debug;
    if (isset($_GET['debug']))
    {
        $debug=1;
    }

    if (isset($_GET['imdb_id'])) {
        $imdb_id =intval($_GET['imdb_id']);
    }
    else if ($_GET['get_imdb_movie_id'])
    {
        $id =intval($_GET['get_imdb_movie_id']);
        $imdb_id = TMDB::get_imdb_id_from_id($id);
    }

    $add= TMDB::reload_from_imdb($imdb_id,$debug);

    echo $add;
    return;
}



if (isset($_GET['just_watch_api_request'])) {
    global $debug;
    if (isset($_GET['debug']))
    {
        $debug=1;
    }
    !class_exists('JustWatch') ? include ABSPATH . "analysis/include/justwatch.php" : '';
    $result = JustWatch::get_just_wach($_GET['just_watch_api_request']);
    var_dump_table($result);
    return;
}



if (isset($_GET['update_just_watch'])) {

    global $debug;
    if (isset($_GET['debug']))
    {
        $debug=1;
    }
    update_just_watch();
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




if (isset($_GET['add_games_to_options'])) {
    add_games_to_options();
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
if (isset($_GET['check_best_games'])) {
    check_best_games();
    return;
}
if (isset($_GET['zr_woke'])) {
    zr_woke($_GET['zr_woke']);
return;
}


if (isset($_GET['check_kairos'])) {
    check_kairos($_GET['check_kairos']);
    return;
}
if (isset($_GET['kairos_prepare_arrays'])) {
    kairos_prepare_arrays($_GET['kairos_prepare_arrays']);
    return;
}



if (isset($_GET['force_surname_update'])) {
    force_surname_update();
    return;
}
if (isset($_GET['update_imdb_data'])) {
    check_load(50,0);
    $force_time='';
    if (isset($_GET['ftime']))
    {
        $force_time=intval($_GET['ftime']);

    }

    update_imdb_data($_GET['update_imdb_data'],$force_time);
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
    global $debug;
    if (isset($_GET['debug']))
    {
        $debug=1;
    }

    get_new_movies();
    return;
}
if (isset($_GET['get_new_tv'])) {
    get_new_tv();
    return;
}
if (isset($_GET['add_pg_rating_for_new_movies'])) {
    add_pg_rating_for_new_movies($_GET['add_pg_rating_for_new_movies']);
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


if (isset($_GET['add_to_db_from_userlist'])) {
    add_to_db_from_userlist();
    return;
}
if (isset($_GET['update_audience_rating'])) {
    update_audience_rating($_GET['update_audience_rating'],$_GET['type']);
    return;
}


if (isset($_GET['set_actors_ethnic'])) {
    set_actors_ethnic($_GET['set_actors_ethnic']);

    return;
}
if (isset($_GET['set_actors_ethnic_vedict'])) {
    set_actors_ethnic_vedict($_GET['set_actors_ethnic_vedict']);
    return;
}
if (isset($_GET['update_actors_verdict'])) {
    global $debug;
    if (isset($_GET['debug']))
    {
        $debug=1;
    }

    update_actors_verdict($_GET['update_actors_verdict'],1);
    return;
}
if (isset($_GET['check_actors_meta'])) {

    ///check_actors_meta();
    return;
}

if (isset($_GET['update_all_rwt_rating'])) {

    update_all_rwt_rating();
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

}


if (isset($_GET['add_tmdb_without_id'])) {




    add_tmdb_without_id();

    return;
}


if (isset($_GET['download_crowd_images'])) {

    download_crowd_images(intval($_GET['download_crowd_images']));

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

    return;
}


if (isset($_GET['get_family'])) {

    get_family($_GET['get_family']);

    return;
}
if (isset($_GET['get_forebears'])) {

    get_forebears($_GET['get_forebears']);

    return;
}
if (isset($_GET['get_forebears_rank'])) {

    get_forebears_rank($_GET['get_forebears_rank']);

    return;
}




if (isset($_GET['disqus_comments'])) {

    disqus_comments($_GET['disqus_comments']);

    return;
}

if (isset($_GET['check_verdict_surname'])) {

    check_verdict_surname();

    return;
}
if (isset($_GET['check_curl'])) {

    $Result = GETCURL::getCurlCookie($_GET['check_curl'],'172.17.0.1:8118');
    echo $Result;

    return;
}


if (isset($_GET['check_sync'])) {


    !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
    Import::sync_db($_GET['check_sync']);


    return;
}
if (isset($_GET['sync_tables'])) {


  sync_tables($_GET['sync_tables']);

    return;
}

if (isset($_GET['get_similar'])) {


    get_similar($_GET['get_similar']);

    return;
}
if (isset($_GET['set_verdict_weight'])) {


    set_verdict_weight($_GET['set_verdict_weight']);

    return;
}

if (isset($_GET['fix_actors_stars'])) {


    fix_actors_stars($_GET['fix_actors_stars']);

    return;
}
if (isset($_GET['fix_all_directors'])) {


    fix_all_directors($_GET['fix_all_directors']);

    return;
}
if (isset($_GET['fix_all_directors_delete'])) {


    fix_all_directors_delete($_GET['fix_all_directors_delete']);

    return;
}



if (isset($_GET['fix_actors_verdict'])) {

    global $debug;
    if (isset($_GET['debug']))
    {
        $debug = 1;
    }

    fix_actors_verdict($_GET['fix_actors_verdict']);

    return;
}

if (isset($_GET['delete_movie'])) {
return;
    if (isset($_GET['sync']))
    {
        $sync = 1;
    }

    global $debug;
    $debug  =1;

    !class_exists('DeleteMovie') ? include ABSPATH . "analysis/include/delete_movie.php" : '';
    DeleteMovie::delete_movie($_GET['delete_movie'], $sync,'request');

    return;

}
if (isset($_GET['delete_new_movies'])) {
    return;

    !class_exists('DeleteMovie') ? include ABSPATH . "analysis/include/delete_movie.php" : '';
    $start_time = time()-86400*2;
    $end_time = time()-3600*4;

   // $q = "SELECT * FROM `movies_log` where `name` ='add movies'  and  last_update > ".$start_time." and last_update < ".$end_time;
    global $debug;
    $debug  =1;
    $q ="SELECT * FROM `movies_log` where `name` ='add movies' and last_update > 1663076609 and last_update < 1663235009";
    $r = Pdo_an::db_results_array($q);
    foreach ($r as $row)
    {
        $mid = $row['rwt_id'];
        if ($mid)
        {
            DeleteMovie::delete_movie($mid, 1,'request');
        }

    }

return;
}
if (isset($_GET['check_dublicate_movies'])) {
    !class_exists('DeleteMovie') ? include ABSPATH . "analysis/include/delete_movie.php" : '';

    global $debug;
    $debug  =1;

    $q = "SELECT movie_id, count(movie_id) FROM data_movie_imdb GROUP by movie_id having count(movie_id) > 1 ORDER BY count(movie_id) DESC;";
    $r = Pdo_an::db_results_array($q);
    if ($r)
    {
        foreach ($r as $i)
        {
            $mid = $i['movie_id'];
            if ($mid)
            {
                $array_result=[];

                $q1 = "SELECT id, movie_id  FROM `data_movie_imdb` where movie_id = ".$mid." order by id asc";
                $s = Pdo_an::db_results_array($q1);
                foreach ($s as $sv)
                {
                    $array_result[]=$sv['id'];

                }
                unset($array_result[0]);
                foreach ($array_result as $md)
                {
                    DeleteMovie::delete_movie($md, 1,'dublicate');
                }
            }
        }
    }

return;
}

if (isset($_GET['crowd_movie_keywords'])) {

    crowd_movie_keywords($_GET['crowd_movie_keywords'])  ;

    return;
}
if (isset($_GET['movie_keywords'])) {

    movie_keywords($_GET['movie_keywords'])  ;

    return;
}
if (isset($_GET['update_crowd_verdict'])) {

    update_crowd_verdict([],$_GET['update_crowd_verdict'])  ;

    return;
}


if (isset($_GET['update_pg_rating_cms'])) {

    update_pg_rating_cms($_GET['update_pg_rating_cms'])  ;

    return;
}

if (isset($_GET['add_empty_actors'])) {


    global $debug;

    if (isset($_GET['debug']))$debug=1;

    add_empty_actors($_GET['add_empty_actors'])  ;

    return;
}

if (isset($_GET['migration_actors_description'])) {



    migration_actors_description();

    return;
}

if (isset($_GET['add_movie_production'])) {

    add_movie_production();

    return;
}

if (isset($_GET['actor_logs'])) {

    !class_exists('ActorsInfo') ? include ABSPATH . "analysis/include/actor_last_update.php" : '';

    ActorsInfo::info(intval($_GET['actor_logs']));

    return;
}




if (isset($_GET['check_tmdb_image_on_server'])) {

    $actor_id=intval($_GET['check_tmdb_image_on_server']);

    !class_exists('KAIROS') ? include ABSPATH . "analysis/include/kairos.php" : '';
    return   KAIROS::load_tmd_image($actor_id);

}
if (isset($_GET['check_image_on_server'])) {

    $actor_id=intval($_GET['check_image_on_server']);

    $q="SELECT `image_url` FROM `data_actors_imdb` WHERE `id`=".$actor_id;
    $image = Pdo_an::db_get_data($q,'image_url');
    if ($image)
    {
        !class_exists('KAIROS') ? include ABSPATH . "analysis/include/kairos.php" : '';
      echo  KAIROS::check_image_on_server($actor_id, $image);

    }
    return;
}

if (isset($_GET['add_actors_slug'])) {

    actor_slug();
    return;
}


if (isset($_GET['check_audience_movie'])) {
    $fid = intval($_GET['check_audience_movie']);



    global $debug;
    $debug = $_GET['debug'];

    !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';

    PgRatingCalculate::rwt_audience($fid, 1, 1);
    PgRatingCalculate::CalculateRating('', $fid, 0, 1);///check_audience_movie
    PgRatingCalculate::add_movie_rating($fid,'',$debug);


}



if (isset($_GET['test_update_audience_post'])) {
    $pid = intval($_GET['test_update_audience_post']);
    ///critic_matic_posts_meta
    global $debug;
    $debug = $_GET['debug'];

    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');

    }
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
    $cm = new CriticMatic();
    $cm->hook_update_post($pid);

}
if (isset($_GET['convert_tables'])) {

check_load(59,0);

$tablesQuery = "SHOW TABLES";
    $row = Pdo_an::db_results_array($tablesQuery);



        foreach ($row as $tableNames) {

            sort($tableNames);

            $tableName =$tableNames[0];



            $tableStatusQuery = "SHOW TABLE STATUS LIKE '$tableName'";
            $tableStatusRow = Pdo_an::db_results_array($tableStatusQuery);

            $tableCollation = $tableStatusRow[0]['Collation'];


            if (stripos($tableCollation, 'utf8mb4_general_ci') === false) {

                $alterTableQuery = "ALTER TABLE `$tableName` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
                Pdo_an::db_query($alterTableQuery);
                echo 'ok '. $tableName . ' :'.$tableCollation.' <br>';
            }
            else
            {
                echo 'skip '. $tableName . ' :'.$tableCollation.' <br>';
            }

            if (check_cron_time())
            {

                break;
            }



        }
    echo "ok";
}

if (isset($_GET['auto_publish_crowdsource'])) {


    auto_publish_crowdsource();



    return;
}

//echo 'ok';

