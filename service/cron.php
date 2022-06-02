<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


$array_jobs = array(

'add_tmdb_without_id'=>10,///add imdb id to tmdb database
'check_last_actors'=>10,
'check_kairos'=>5,///add kairos images
'update_tmdb_actors'=>10, //update tmdb id and images

'check_tmdb_data'=>15,///update country and poster from tmdb
'add_gender_rating'=>15,///add new gender rating

'add_pgrating'=>30,////add pg rating
'add_to_db_from_userlist'=>60,///add new movies from user vote list //// !not sync
'add_providers'=>30,
'update_imdb_data'=>30,//update movies
'disqus_comments'=>30, ///disquss count comments

'download_crowd_images'=>60,///load image to server from crowdsource status 1
'update_actors_verdict'=>30,///update verdict actors


'update_all_rwt_rating'=>60,////update all rating
'check_tv_series_imdb'=>120, ///add tvseries from list




'get_new_movies'=>(60*12),///add new movies from fandango
'get_new_tv'=>(60*12),///add new tv from tmdb

'add_pg_rating_for_new_movies'=>(60*12),///add pg rating to new movies
'add_gender_rating_for_new_movies'=>(60*12),///add gender rating to new movies

'update_all_audience_and_staff'=>(60*12),///recreate cache audience and staff
'get_coins_data'=>60*24,////get data donations

'add_imdb_data_to_options'=>(60*24*7),
'add_tv_shows_to_options'=>(60*24*30),

    'sync_tables'=>20, ///sync all remote tables
    'get_family'=>10, //family to actors meta
    'get_forebears'=>10, //forebears to actors meta
    'set_tmdb_actors_for_movies'=>30,////update tmdb actors from japan anime

///'add_rating'=>10,  ////add new rating to movies (old version)
//'check_tvexport'=>10
);

global $included;
$included =1;
include ABSPATH .'analysis/include/scrap_imdb.php';


class Cronjob
{

    public $max_time=300; //sec run process
    public $timestart=0;

    public function __construct()
    {

        if(isset($_GET['install']))
        {
            if ($_GET['install']=='add_db')
            {
                $this->install();
                return;
            }

        }
        if (isset($_GET['reset']))
        {
            $this->reset();
            return;
        }
    }
    private function reset()
    {
        $this->set_option('run_cron', 1);
    }

    public function timer_start()
    {
          $this->timestart = microtime(1);
    }


    private function timer_stop( $precision = 3)
    {

        $mtime = microtime(1);
        $timetotal = $mtime - $this->timestart;
        $r = number_format($timetotal, $precision);
        return $r;
    }



    private  function get_options($id)
    {
        $sql = "SELECT time  FROM `cron` where task = ?";
        $r = Pdo_an::db_fetch_row($sql,array($id));
        $time = $r->time;
        if (!$time) $time = 0;
        return $time;
    }

    private function set_option($id, $option)
    {
        if ($option && $id) {
            $enable=$this->get_options($id);
            if ($enable)
            {
                ///update
                $sql = "UPDATE `cron` SET  `time` = ? WHERE `task` = ?" ;
                Pdo_an::db_results($sql,array($option,$id));
            }
            else
            {
                ///insert
                $sql = "INSERT INTO `cron`  VALUES (NULL,?,?)";
                Pdo_an::db_results($sql,array($id,$option));
            }

        }
    }

    public   function run($array_jobs)
    {




        $run_cron = $this->get_options('run_cron');

        echo 'Last run :'.date('H:i:s d.m.Y',$run_cron).'<br>' . PHP_EOL;

        if ($run_cron < time()-3600/2) {

            $this->set_option('run_cron', time());

            $this->timer_start();

            $i = 1;
            $count = count($array_jobs);

            foreach ($array_jobs as $jobs => $period) {


                if ($this->timer_stop() < $this->max_time) {
                    echo 'run ' . $i . ' from ' . $count . '<br>' . PHP_EOL;
                    self::run_function($jobs, $period);
                } else {
                    echo '<br>Ended max time > ' . max_time . '<br>' . PHP_EOL;
                    $this->set_option('run_cron', 1);

                    break;

                }

                $i++;
            }

            $this->set_option('run_cron', 1);
        }
        else
        {
            echo '<br>cron is runned <br> last task:<br>' . PHP_EOL;
///get last task
/// task	time
        $sql = "SELECT * FROM `cron` order by time desc";
        $row = Pdo_an::db_results_array($sql);
        foreach ($row as $r)
        {

            echo  date('H:i:s d.m.Y',$r['time']).' '.$r['task'].'<br>';



        }



        }



    }


    public   function run_function($name,$period)
    {


      echo 'Function '.$name.' checked '. self::timer_stop().'<br>'.PHP_EOL;
      $last_time =   self::get_options($name);
      echo 'Last updated '.date('H:i:s d.m.Y',$last_time).'<br>'.PHP_EOL;

      if (time()>$last_time+$period*60)
      {
       self::set_option($name." started",time());
       echo 'Started '. self::timer_stop().'<br>'.PHP_EOL;

          /////run function

            if (function_exists($name))
            {
            $name();
            }

        self::set_option($name,time());
        echo '<br>Ended  '.$name.'  '. self::timer_stop().'<br><br>'.PHP_EOL.PHP_EOL;
      }
      else
        {
           // self::set_option($name.' skipped',time());
            echo 'skipped  '.date('H:i:s d.m.Y',time()).'<'.date('H:i:s d.m.Y',($last_time+$period*60)).'<br><br>'.PHP_EOL.PHP_EOL;
        }


    }
    private function install() {

$sql_result = "CREATE TABLE  IF NOT EXISTS `cron` (
  `id` int(10)  NOT NULL AUTO_INCREMENT,
  `task` varchar(100) DEFAULT NULL,
  `time` int(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT COLLATE utf8_general_ci;";
        Pdo_an::db_query($sql_result);
    }
}


$cron = new Cronjob;

$cron->run($array_jobs);

