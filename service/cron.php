<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('CPULOAD') ? include ABSPATH . "service/cpu_load.php" : '';

global $array_jobs;

$array_jobs = array(

'add_tmdb_without_id'=>10,///add imdb id to tmdb database
'check_last_actors'=>20,
///'check_kairos'=>5,///add kairos images

'movie_keywords'=>20,///upadate movies keywords
'update_tmdb_actors'=>10, //update tmdb id and images

'check_tmdb_data'=>15,///update country and poster from tmdb



'add_to_db_from_userlist'=>60,///add new movies from user vote list //// !not sync
'add_providers'=>30,
'update_imdb_data'=>30,//update movies
'disqus_comments'=>30, ///disquss count comments

'download_crowd_images'=>60,///load image to server from crowdsource status 1
'update_actors_verdict'=>120,///update verdict actors

'check_face'=>120,///add bettaface verdict

'update_crowd_verdict'=>60,    ///crowdsource udate actor verdict

'update_all_rwt_rating'=>30,////update all rating
'add_pgrating'=>60*2,////add pg rating
//'add_gender_rating_for_new_movies'=>(60),///add gender rating to new movies

'add_pg_rating_for_new_movies'=>(60),///add pg rating to new movies
'add_gender_rating'=>60,///add new gender rating



'check_tv_series_imdb'=>120, ///add tvseries from list

'fix_all_directors'=>60,////temp started 12/01/2023

'get_new_movies'=>(60*12),///add new movies from fandango
'get_new_tv'=>(60*12),///add new tv from tmdb




'update_all_audience_and_staff'=>(60*12),///recreate cache audience and staff
'get_coins_data'=>60*24,////get data donations

'add_imdb_data_to_options'=>(60*24*7),
'add_tv_shows_to_options'=>(60*24*30),

    'sync_tables'=>30, ///sync all remote tables
    'get_family'=>10, //family to actors meta
    'get_forebears'=>15, //forebears to actors meta
    'set_tmdb_actors_for_movies'=>30,////update tmdb actors from japan anime



 'add_noname_actors'=>120, //actor witout names

///'add_rating'=>10,  ////add new rating to movies (old version)
//'check_tvexport'=>10
);

global $included;
$included =1;
require_once ABSPATH .'analysis/include/scrap_imdb.php';


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
        $this->set_cron_option('run_cron', 1);
    }

    public function timer_start($time=0)
    {
        if ($time)
        {
            $this->max_time=$time;
        }


        if (!$this->timestart)
        {
          $this->timestart = microtime(1);
        }
    }


    public function timer_stop( $precision = 3)
    {

        $mtime = microtime(1);
        $timetotal = $mtime - $this->timestart;
        $r = number_format($timetotal, $precision);
        return $r;
    }


    private function get_all_options($array_jobs)
    {
        $array_result = [];
        $sql = "SELECT *  FROM `cron`";
        $r = Pdo_an::db_results_array($sql);
        foreach ($r as $val)
        {
            if ( $array_jobs[$val['task']])
            {
                $last_time = intval($val['time']);
                if ($last_time>time())
                {
                    $last_time=  0;
                }

                $array_result[$val['task']] = intval(( time() - $last_time )/60 - $array_jobs[$val['task']]);
            }
        }

        foreach ($array_jobs as $jobs => $period) {
            if (!$array_result[$jobs]) {
                $array_result[$jobs] = 1;
            }
        }

        arsort($array_result);
        return $array_result;

    }

    private  function get_cron_options($id)
    {
        $sql = "SELECT `time`  FROM `cron` where task = '".$id."'";

        global $cron_debug;
        if ($cron_debug)
        {
            echo '<br>'.$sql.'<br>';
        }


        $r = Pdo_an::db_results_array($sql);

        if ($cron_debug) {
            var_dump($r);
        }
        $time = $r[0]['time'];
        if (!$time) $time = 0;
        return $time;
    }

    private function set_cron_option($id, $option,$enable='')
    {
        if ($option && $id) {

           $enable=$this->get_cron_options($id);

            if ($enable)
            {
                ///update

                $sql = "UPDATE `cron` SET  `time` = '".$option."' WHERE `task` = '".$id."'" ;
                global $cron_debug;
                if ($cron_debug)
                {
                    echo '<br>'.$sql.'<br>';
                }


                Pdo_an::db_results($sql);
            }
            else
            {
                ///insert
                $sql = "INSERT INTO `cron`  VALUES (NULL,?,?)";
                Pdo_an::db_results_array($sql,array($id,$option));
            }

        }
        else
        {
            echo 'set_cron_option error: not id and options '.$id.' '.$option.' <br>';
        }
    }

    public   function run($array_jobs,$only_info = 0)
    {




        $run_cron = $this->get_cron_options('run_cron');

        echo '<p>Last run :'.date('H:i:s d.m.Y',$run_cron).'</p>' . PHP_EOL;

///////check last run
        $jobs_data =  $this->get_all_options($array_jobs);


        if (isset($_GET['force']))
        {
            $force  = $_GET['force'];
        }

        //var_dump($jobs_data);


        if ((($run_cron < time()-3600/2) || $force==1) && !$only_info) {

            $this->set_cron_option('run_cron', time());
            $this->set_cron_option('cron started', time());

            $this->timer_start();

            $i = 1;
            $count = count($array_jobs);

            foreach ($jobs_data as $jobs => $last_update) {
                $period=$array_jobs[$jobs];

                if ($this->timer_stop() < $this->max_time) {



                    echo 'run ' . $i . ' from ' . $count . ' lastrun: '.$last_update.'<br>' . PHP_EOL;
                    $this->run_function($jobs, $period);


                } else {
                    echo '<br>Ended max time > ' . $this->max_time . '<br>' . PHP_EOL;
                    $this->set_cron_option('cron', time());
                    $this->set_cron_option('run_cron', 1);

                    break;

                }

                $i++;
            }
            $this->set_cron_option('cron', time());
            $this->set_cron_option('run_cron', 1);
        }
        else
        {
            if (!$only_info)
            {
                echo '<br>cron is runned <br>' . PHP_EOL;
            }



            $content='';
            foreach ($jobs_data as $i=> $r)
            {
                $n='no';
                if ($r>0)$n='yes';


                $content.= '<tr><td>'.$i.'</td><td>'.round($r,0).'</td><td>'.$array_jobs[$i].'</td><td>'.$n.'</td></tr>';

            }

            $content = ' <br>Tasks:<br><table border="1" cellspacing="0"><tr><th>Job</th><th>Order</th><th>Default  (min)</th><th>Need to update</th></tr>'.$content.'</table>';

            echo $content;

///get last task
/// task	time

        $sql = "SELECT * FROM `cron` order by time desc";
        $row = Pdo_an::db_results_array($sql);


        $last_run=[];
        foreach ($row as $r)
        {

            if ($array_jobs[$r['task']])
            {

                $last_run[$r['task']]['end']=$r['time'];

            }
            else if (strpos($r['task'],' started'))
            {
               $rdata =trim( substr($r['task'],0,strpos($r['task'],' started')));
                $last_run[$rdata]['start']=$r['time'];

            }
            else if ($r['task'] =='cron'){
                $last_run[$r['task']]['end']=$r['time'];
            }
            else{
                $last_run[$r['task']]['end']=$r['time'];
            }

        }
      //  var_dump($last_run);
            $content='';
        foreach ($last_run as $i=> $v)
            {
                $ddata='';
                $sdata='';
                $edata='';
                $vtotal='';

                if ($v['start']) $ddata  = date('d.m.Y',$v['start']);
               if ($v['start']) $sdata  = date('H:i:s',$v['start']);
                if ($v['end']) $edata  = date('H:i:s',$v['end']);

                if ($v['start'] && $v['end'])
                {
                    $vtotal  = $v['end']-$v['start'];
                }

                $content.= '<tr><td>'.$i.'</td><td>'.$ddata.'</td><td>'.$sdata.'</td><td>'.$edata.'</td><td>'.$vtotal.'</td></tr>';


            }

            $content = ' <br>last tasks:<br><table border="1" cellspacing="0"><tr><th>Job</th><th>Day</th><th>Start</th><th>End</th><th>Total</th></tr>'.$content.'</table>';

        echo $content;
        }



    }

    public function check_time()
    {

        if ($this->timer_stop() > $this->max_time) {
            return array('result'=>1,'curtime'=>$this->timer_stop(),'maxtime'=> $this->max_time);
        }
        return array('result'=>0,'curtime'=>$this->timer_stop(),'maxtime'=> $this->max_time);
    }


    public   function run_function($name,$period)
    {

        $fname =trim($name);


      echo 'Function '.$fname.' checked '. $this->timer_stop().'<br>'.PHP_EOL;
      $last_time =   $this->get_cron_options($fname);
      echo 'Last updated '.date('H:i:s d.m.Y',$last_time).'<br>'.PHP_EOL;

      if (time()>$last_time+$period*60)
      {
          $this->set_cron_option($fname." started",time());
       echo 'Started '. $this->timer_stop().'<br>'.PHP_EOL;

          /////run function

            if (function_exists($name))
            {
            $name();
            }

          $this->set_cron_option($fname,time(),$last_time);
          echo '<br>Ended  '.$fname.'  '. $this->timer_stop().'<br><br>'.PHP_EOL.PHP_EOL;
      }
      else
        {
           // $this->set_cron_option($name.' skipped',time());
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



if (isset($_GET['debug']))
{

    global $cron_debug;
    $cron_debug=1;
}




if (isset($_GET['runjob']))
{

    $cron = new Cronjob;
    $cron->timer_start();
    $cron->run_function($_GET['runjob'], 1);
}
    if (isset($_GET['runcron']))
    {

        if ($_GET['runcron']==1)
        {
            ///check cpu load

            $load = CPULOAD::check_load();

            global $cron_debug;
            if ($cron_debug)
                {
                    var_dump($load);
                }

            if ($load['loaded'])
            {
                return;
            }

            global $cron;
            $cron = new Cronjob;

            $cron->run($array_jobs);




        }

    }


