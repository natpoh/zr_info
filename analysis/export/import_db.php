<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

!class_exists('METALOG') ? include ABSPATH . "analysis/include/meta_log.php" : '';




class Import
{

    public static function debug()
    {
        return 0;
    }


    public static function timer_start_data()
    { // if called liketimer_stop_data(1), will echo $timetotal
        global $timestart;
        $timestart = microtime(1);
    }

    public static function timer_stop_data()
    { // if called liketimer_stop_data(1), will echo $timetotal
        global $timestart, $timeend;
        $mtime = microtime(1);
        $timetotal = $mtime - $timestart;
        return $timetotal;
    }

    public static function get_key()
    {
        $key='1R3W5T8s13t21a34f';
        return $key;
    }
    public static function generate_request($array_sql)
    {
        $result =[];


    foreach ($array_sql as $i =>$v)
    {
        $uid = $v["uniq_id"];
        ////update request status

        $time_current = self::timer_stop_data();
        ///self::update_status($uid,2,$time_current);////send request
        ///get data
        $sql_data =  self::commit_info_request($uid);  ///get remote sql from commit

        //if(self::debug()){$result['sql_data']=$sql_data;}

        if ($sql_data["error"])
        {
            self::update_status($uid,1,$time_current);////update to 1
             $result[$uid]['return']=$sql_data;

            return $result;
        }

        $time_current = self::timer_stop_data();
        if ($sql_data) {
            $result[$uid]['sql_data']=$sql_data;

            $update_data = self::set_data($sql_data); ///update site data from sql
            if ($update_data) {
                $result[$uid]['update_data'] = $update_data;
                 self::update_commit_data($uid, $update_data, $time_current); ///update to status 4
            }
            else
            {
                self::update_status($uid,7);////update to 7 no data return
                $result[$uid]['return']='empty';
            }
        }
        else
        {
            self::update_status($uid,1,$time_current);////update to 1
        }

    }

    return $result;
    }

    public static function get_data_from_db($db,$start=0,$limit=200,$row = 'id')
    {
        if (!$start)$start=0;

        $q ="SELECT {$row} FROM {$db} order by {$row} asc LIMIT {$start}, {$limit}";

        $r = Pdo_an::db_results_array($q);
        return $r;


    }


    public static function sync_db($db)
    {

        ////get last id

        $last_id_array = self::get_last_id($db);

        $last_id =$last_id_array['id'];

        $remote_id_array = self::get_remote_last_id($db);


        $remote_id=$remote_id_array['id'];

        if ($last_id>$remote_id)
        {

            ///add new id

            $r = self::get_data_from_db($db,$remote_id);

            foreach ($r as $d)
            {
                $b =self::get_prefix();

                $name = $b.$db.'_'.$d['id'];

                ///check commit
                $rs =  self::check_status_commit($name);


                if (!$rs) {
                    self::create_commit($name, 'update', $db, array('id' => $d['id']), 'sync_'.$db,30);
                }
            }
            echo $db.' updated<br>';
        }
        else echo $db.' '. $last_id.' = '.$remote_id.'<br>';
    }



    public static function commit_info_request($uid)
{

    $key = self::get_key();
    $options_data = self::get_import_data();

    $link  = $options_data['link_request'];

    $limit=10;

    $request = array(
        'uid'=>$uid,
        'action'=>'get_commit',
        'key'=>$key,
        'limit'=>$limit,
    );

    $result =  GETCURL::getCurlCookie($link,'',$request);


    if ($result)
    {
        $result = json_decode($result,1);
    }
    return $result;

}
    public static function get_last_id($table='',$data='',$row='id')
    {
        if ($data)
        {
            $array =$data['data'];
            $array = json_decode($array,1);
            $table = $array['table'];

        }

        if ($table)
        {
            $q = "SELECT {$row} FROM {$table} ORDER BY {$row} DESC limit 1  ";
            $r = Pdo_an::db_results_array($q);
            return array ($row=>$r[0][$row]);
        }
    }


    public static function get_remote_table($arrayinput)
    {

        $array =$arrayinput['data'];
        $array = json_decode($array,1);

      $result['array_input'] =$array;

      $table = $array['table'];
      $request =  $array['request'];
      $column = $array['column'];
      if (!$column)$column = 'id';

      if ($request)
      {
          $where='';

          foreach ($request as $c=>$d)
          {
              $where.= "and `".$c."` = '".$d."' ";
          }
          if ($where)
          {
              $where = substr($where,4);
              $sql ="SELECT `".$column."` from `".$table."` where ".$where." limit 1";

              $result['sql']=$sql;

              $row = Pdo_an::db_results_array($sql);
              if ($row)
              {
                  $id = $row[0][$column];
                 $result['id']=$id;
              }
          }

      }
      if (!$result['id'])
      {
          ///create id
          ///

          $sql = "INSERT INTO `".$table."` (`".$column."`) VALUES (NULL)";
          $result['sql']=$sql;
          Pdo_an::db_query($sql);
          $result['id']= Pdo_an::last_id();


      }


      return $result;
    }
    public static function get_remote_last_id($table)
    {
        $key = self::get_key();
        $options_data = self::get_import_data();
        $link  = $options_data['link_request'];
        $array = array('table'=>$table);
        if ($array){$array_sql = json_encode($array);}
        $request = array(
            'data'=>$array_sql,
            'action'=>'get_last_id',
            'key'=>$key,
        );
        $result =  GETCURL::getCurlCookie($link,'',$request);
        if ($result)
        {
            $result = json_decode($result,1);
        }
        return $result;
    }
    public static function get_remote_id($array)
    {

        $key = self::get_key();
        $options_data = self::get_import_data();

        $link  = $options_data['link_request'];
        if ($array){$array_sql = json_encode($array);}


        ////$array = array('table'=>'data_movie_imdb','request'=>array('movie_id'=>$movie_id));


        $request = array(
            'data'=>$array_sql,
            'action'=>'get_remote_id',
            'key'=>$key,
        );
        $result =  GETCURL::getCurlCookie($link,'',$request);
        if ($result)
        {
            $result = json_decode($result,1);
        }
        return $result;
    }

    public static function push_request($array_sql=[],$status = 0)
    {

        $key = self::get_key();
        $options_data = self::get_import_data();

        $link  = $options_data['link_request'];

        $limit=10;

        if ($array_sql){$array_sql =json_encode($array_sql);}

        $request = array(
            'commit_data'=>$array_sql,
            'action'=>'sync_data',
            'key'=>$key,
            'limit'=>$limit,
            'status'=>$status
            );

        //$request =http_build_query($request);


       $result =  GETCURL::getCurlCookie($link,'',$request);



       if ($result)
       {
           $result = json_decode($result,1);
       }


       return $result;

    }


    public static function get_prefix()
    {

        $options_data = self::get_import_data();
        $site_id = $options_data['site_id_request'];
        $prefix = array(1=>'t_',2=>'p_');

        if (!$site_id)
        {
            $site_id =0;
        }

        $b=$prefix[$site_id];

        return $b;

    }

    public static function generate_id($name='')
    {
        $options_data = self::get_import_data();
        $site_id = $options_data['site_id_request'];
        $prefix = array(1=>'t_',2=>'p_');

        if (!$site_id)
        {
            $site_id =0;
        }

        $b=$prefix[$site_id];
                if ($name){return array($b.$name.time(),$site_id);}
        else {return $site_id;}

    }

    public static function get_commit_id($name)
    {
        $id = self::set_commit($name);

        return $id;
    }

    public static function create_commit($commit_id='',$type,$db,$request,$name = '',$priority=1,$array_custom='',$array_return='')
    {

        $ajax_data = array("type"=>$type,"request"=>$request,"db"=>$db);

        if ($array_return)
        {
            $ajax_data["u"]=$array_return;
        }
        if ($array_custom)
        {
            $ajax_data["custom"]=$array_custom;
        }

        return self::set_commit($name,$ajax_data,$commit_id,'',0,$priority);

    }

    public static function set_commit($name='',$data='',$unique_id='',$site_id='',$status=0,$priority=1)
    {

        if (!$unique_id || !$site_id)

        {
            $unique_id_array = self::generate_id($name);

            if ($name)
            {
                if (!$site_id) $site_id = $unique_id_array[1];
            }
            else
            {
                if (!$site_id) $site_id = self::generate_id();
            }




            if (!$unique_id) $unique_id=$unique_id_array[0];
        }


        $sql = "SELECT `text` FROM `commit` WHERE `uniq_id`  = '".$unique_id."'";
        $row = Pdo_an::db_fetch_row($sql);

        if ($row && $data)
        {
            ///update
            $text = $row->text;
            if ($text)
            {
                $text = json_decode($text,1);
            }
            else
            {
                $text = [];
            }

            $text[]=$data;
            $text = json_encode($text);


            $sql = "UPDATE `commit` SET `text`=? , `last_update`=? WHERE `uniq_id`  = '".$unique_id."'";
            Pdo_an::db_results_array($sql,array($text,time()));

        }
        else
        {
            if ($data)
            {
                $data_string = json_encode(array(0=>$data));
            }

            $q = "INSERT INTO `commit`(`id`, `uniq_id`, `description`, `text`,`update_data`, `status`,`site_id`,`priority`,`add_time`)
            VALUES (NULL,?,?,?,?,?,?,?,?)";

            Pdo_an::db_results_array($q, array($unique_id,$name,$data_string,'',$status,$site_id,$priority,time()));

            METALOG::update_log($name,$site_id);
            METALOG::clear_history();

        }

        return $unique_id;
    }

    public static function get_commit($data)
    {
        $array_sql = [];

        if (self::check_remore_ip())
        {
            return self::check_remore_ip();
        }

        if ($data['uid'])
        {
            $uid =$data['uid'];
                if (strstr($uid,','))
                {
                    $uid =explode(',',$uid);
                }

                if (is_array($uid)) {
                    foreach ($uid as $i ) {
                       self::update_status($uid,3);

                        $where .= "OR `uniq_id` = '" . $i . "' ";
                    }
                }
                else
                {
                   self::update_status($uid,3);
                    $where = "  `uniq_id` = '" . $uid . "' ";
                }
                if ($where) {
                    $where = substr($where, 2);
                    $where = " WHERE (" . $where . ") ";
                }

        }

        if ($where)
        {
            $sql ="SELECT *  FROM `commit` ".$where." ";

            $rows = Pdo_an::db_results_array($sql);
            foreach ($rows as $r)
            {

                $array_s = $r['text'];
                if ($array_s)
                {
                    $array = json_decode($array_s,1);
                    foreach ($array as $time=>$val)
                    {
                        $request = self::create_request($val);

                        $array_sql[$r['uniq_id']]['data'][$time] = $request;

                    }
                }


            }


        }



        return $array_sql;

    }

    public static function create_request($val)
    {
        $db=$val['db'];
        $wo = $val['request'];
        $custom = $val['custom'];



        if ($val['type']=="update")
        {

            if ($wo) {
                foreach ($wo as $i => $v) {

                        $where .= "AND `" . $i . "` = '" . $v . "' ";

                }
                if ($where) {
                    $where = substr($where, 3);
                }


                $object_setup[$db]['request'] = $wo;

                $sql = "SELECT * FROM " . $db . " where ".$where;

                if (self::debug())$object_setup[$db]['sql']=$sql;

                $result = Pdo_an::db_results_array($sql);
                //$object_setup = [];
            }


            if (is_array($result))
                foreach ($result as $num=> $r)
                {
                    foreach ($r as $i=>$v) {

                        if (in_array($i,$custom['skip']))
                        {
                          ///skip
                        }
                        else
                        {
                            $object_setup[$db]['columns'][$num][$i] = $v;
                        }


                    }

                }
        }

        else if ($val['type']=="delete")
        {
            $object_setup[$db]['request'] = $wo;
        }

        if ($val['u'])
        {
            //$object_setup[$db]['u'] = $val['u'];

            self::custom_function($val['u']);
        }

        return array($val['type']=>$object_setup);

    }

    public static function last_sinc_commits($data)
    {
        $site_id = self::generate_id();
        $count=1000;
        if ($data['count'])
        {
            $count =intval($data['count']);

        }
        $count_array=0;
        $result =[];

        $sql ="SELECT *  FROM `commit` WHERE `status` = 1 and site_id!='".$site_id."' ORDER BY `commit`.`priority` ASC, `id` ASC  limit ".$count;
        $rows = Pdo_an::db_results_array($sql);
        foreach ($rows as $i=> $r)
        {
            $data = $r['text'];
            if ($data)
            {
                $array = json_decode($data,1);
                $count_array+= count($array);
            }
            $result[$i]=$r;
            if ($count_array>100)
            {
                break;
            }
        }

        return $result;
    }

//    public static function last_commits_updated($data)
//    {
//        $count=10;
//        if ($data['count'])
//        {
//            $count =intval($data['count']);
//
//        }
//
//        $site_id = self::generate_id();
//        $sql ="SELECT `uniq_id` , `status`   FROM `commit` WHERE `status` = 2 and site_id!='".$site_id."' limit ".$count;
//        $rows = Pdo_an::db_results_array($sql);
//        return $rows;
//    }

    public static function last_commits($data,$status =0)
    {

        $count=500;
        if ($data['count'])
        {
            $count =intval($data['count']);

        }
        $sql ="SELECT *  FROM `commit` WHERE `status` = '".$status."' and `complete` IS NULL ORDER BY `commit`.`priority` ASC, `id` ASC  limit ".$count;
        $rows = Pdo_an::db_results_array($sql);
        return $rows;
    }

    public static function get_table_access($table_name)
    {

        $sql = "SELECT * FROM `commit_tables_rules` where `table_name` ='{$table_name}' ";
        $rows = Pdo_an::db_fetch_row($sql);
        if ($rows)
        {
            $array['export']=$rows->export;
            $array['import']=$rows->import;
            $array['del']=$rows->del;

        }

        return $array;

    }

    public static function get_import_data()
    {
//        $sql = "SELECT `val` FROM `options` where id =18 ";
//        $rows = Pdo_an::db_fetch_row($sql);
//
//        $options_data  =$rows->val;
//        if ($options_data)
//        {
//            $options_data = json_decode($options_data,1);
//        }

        $data =file_get_contents(ABSPATH.'wp-content/uploads/export');
        if ($data)
        {
            $options_data = json_decode($data,1);
        }
        return $options_data;
    }

    public static function check_status_commit($key,$col='id')
    {
        $sql = "SELECT `".$col."` FROM `commit` where `uniq_id` = '".$key."' ";
        $rows = Pdo_an::db_fetch_row($sql);
        if ($rows)
        {
            return $rows->{$col};
        }
        return '';
    }

    public static function sinc_status_commit($data_obj)
    {
        $result = [];

        $data = json_decode($data_obj, 1);
        if (!$data) {
            $result['error']['data_obj'] = json_last_error_msg();
        }


        foreach ($data as $request )
        {
            $key = $request["uniq_id"];
            $status= $request["status"];
            $row =  self::check_status_commit($key,'status');
            if ($row!=$status) {
            self::update_status($key,$status);
                $result[]=$key;
            }
        }
        return $result;

    }

    public static function check_and_set_data($data)
    {

        $result = [];

        foreach ($data as $request )
        {

           $key = $request["uniq_id"];
           $site_id= $request["site_id"];
           $name= $request["description"];
           $data= $request["text"];

           $priority= $request["priority"];


           $row =  self::check_status_commit($key);
           if (!$row) {
               self::set_commit($name,$data,$key,$site_id,1,$priority);
               $result[$key]=1;
           }
           else
           {
               $status =  self::check_status_commit($key,'status');
               if ($status>1) {
                   ///update commit to 1
                self::update_status($key, 1);
              // $result[$key]=10;///error
               $result[$key] = 1;
               }
               else if ($status==1) {

               $result[$key] = 1;

                }

           }


        }

        return $result;

    }

    public static function check_request($column,$table='')
{

    $sql = "SHOW COLUMNS FROM ".  $table." ";

    $rows = Pdo_an::db_results_array($sql);
    $first_row = $rows[0]['Field'];


    $request=[];

    if  ($column[$first_row])
    {
        $request[$first_row]=$column[$first_row];
    }
   return $request;


}

    public static function custom_function($array)
{

//    $array_update = array('k'=>'um','id'=>$mid);
//    $commit_id = Import::create_commit('','update','data_movie_imdb',array('id'=>$mid),'movies_add',5,$array_update);

    if ($array['k']=='um')///update movies
    {
        $movie_id =$array['id'];

        $sql ="UPDATE `data_movie_imdb` SET `add_time` = '".time()."' where `id` = ".intval($movie_id);
        Pdo_an::db_results_array($sql);
    }

}

    public static function set_data($data)
    {
        $result=[];

        foreach ($data as $key =>$array_data) {
            foreach ($array_data["data"] as $index => $object_setup_type) {
                foreach ($object_setup_type as $type => $object_setup_data) {

                    if ($type == 'update') {

                        foreach ($object_setup_data as $table => $object_setup_all) {

                            $count_rows = count($object_setup_all['columns']);////if multy request get default values


                            foreach ($object_setup_all['columns'] as $index => $object_setup){

                            if (!$object_setup_all['request'] )
                            {
                                $object_setup_all['request'] = self::check_request($object_setup,$table);
                            }


                            $array_req = self::set_array_colmuns($object_setup, $object_setup_all['request'], $object_setup_all['return']);

                          $result[$type][$table][] = self::update_table($table, $array_req['data'], $array_req['where']);

                            if ($object_setup['u'])
                            {
                               // self::custom_function($object_setup['u']);
                            }
                            }
                        }
                    }
                    else if ($type == 'delete') {

                        foreach ($object_setup_data as $table => $object_setup) {

                            if (!$object_setup['request'])
                            {
                                $object_setup['request'] = self::check_request($object_setup['columns'],$table);
                            }


                            $array_req = self::set_array_colmuns($object_setup['columns'], $object_setup['request'], $object_setup['return']);

                            $result[$type][$table][] = self::delete_table($table, $array_req['where']);

                            if ($object_setup['u'])
                            {
                               // self::custom_function($object_setup['u']);
                            }
                        }
                    }


                }
            }
        }

        return $result;

    }

    public static function check_remore_ip()
    {
        $options_data = self::get_import_data();

        $remote = $_SERVER['REMOTE_ADDR'];
        $remote_data = $options_data['remote_ip'];

        if ($remote_data!=$remote)
        {
            return array('error'=>'false remote ip '.$remote_data.'!='.$remote);
        }

    }

    public static function sync_data($data)
    {

        if (self::check_remore_ip())
        {
            return self::check_remore_ip();
        }

        ////add comit to db
        $data_obj = $data['commit_data'];
        $status = $data['status'];


        if ($data_obj) {
            $object_setup_all = json_decode($data_obj, 1);
            if (!$object_setup_all) {
                $result['error']['data_obj'] = json_last_error_msg();
            }


            if ($status==0)
            {
                ///add commit data
                $result_data = self::check_and_set_data($object_setup_all);

            }
            else if ($status==5 || $status==7)
            {
                ///update comlete status
                $result_data = [];

                foreach ($object_setup_all as $request ) {

                $key = $request["uniq_id"];
                $update_data  = $request["update_data"];
                $run_time   = $request["run_time"];


                self::complete_status($key,$run_time,$update_data,$status);

                $result_data[$key]=$status;

                }
            }

        }



        return array('sync_result'=>$result_data);

    }

    public static function update_commit_data($key,$update_data,$time_current='')
    {
        $dop='';
        if ($time_current)
        {
                  $dop=",run_time = IF(run_time IS NULL, {$time_current},run_time + {$time_current})";
        }

        $update_data = json_encode($update_data);
        $sql = "UPDATE `commit` SET `update_data`=?, `status`= 4 , `last_update`='".time()."' ".$dop." WHERE `uniq_id`  = '".$key."'";
        Pdo_an::db_results_array($sql,array($update_data));
        return array($key=>4);
    }

    public static function complete_status($key,$time_current='',$update_data='',$status=5)
    {

        $dop='';
        if ($time_current)
        {
            $dop=",run_time = IF(run_time IS NULL, {$time_current},run_time + {$time_current})";
        }
        if ($update_data)
        {
            $sql = "UPDATE `commit` SET `status`={$status}, `complete` =1 , update_data = ?, `last_update` = ".time()." ".$dop."  WHERE `uniq_id`  = '".$key."'";
            Pdo_an::db_results_array($sql,[$update_data]);
        }
        else
        {
            $sql = "UPDATE `commit` SET `status`={$status}, `complete` =1 , `last_update` = ".time()." ".$dop."  WHERE `uniq_id`  = '".$key."'";
            Pdo_an::db_query($sql);
        }


    }

    public static function update_status($key,$status,$time_current='',$complete='')
    {
        $dop='';
        if ($time_current)
        {
            $dop=",run_time = IF(run_time IS NULL, {$time_current},run_time + {$time_current})";
        }
        if ($complete)
        {
            $complete =" ,`complete` = 1 ";
        }


        $sql = "UPDATE `commit` SET `status`='".$status."' ".$complete.", `last_update` = '".time()."' ".$dop." WHERE `uniq_id`  = '".$key."'";
        Pdo_an::db_query($sql);



    }

    public static function service()
    {

        ////delete old comlete request

        $sql = "DELETE FROM `commit` WHERE `complete` = 1 and `last_update` < ".(time()-86400*8);

        Pdo_an::db_query($sql);


        ////delete old status

        $site_id = self::generate_id();


        $sql = "UPDATE `commit` SET `status` = 0 , `complete` = 0   where (`status` = 1 OR `status` = 2 OR `status` = 3 OR `status` = 4 ) and site_id='".$site_id."' and `last_update` < ".(time()-3600);

        Pdo_an::db_query($sql);

        $sql = "UPDATE `commit` SET `status` = 1 , `complete` = 0   where (`status` = 2 OR `status` = 3 OR `status` = 4 ) and site_id!='".$site_id."' and `last_update` < ".(time()-3600);

        Pdo_an::db_query($sql);


        ////check requests

        $sql = "SELECT `text`, `id` FROM `commit` WHERE `requests`  = 0 and `text` IS NOT NULL limit 10000";
        $row = Pdo_an::db_results_array($sql);
        foreach ($row as $r)
        {
            $data = $r['text'];
            if ($data)
            {
                $array = json_decode($data,1);
                $count_array= count($array);
                $sql2 = "UPDATE `commit` SET `requests` = {$count_array} where id = ".$r['id'];
                Pdo_an::db_query($sql2);
            }
        }
    }

    public static function check_status($status)
    {

        $sql ="SELECT COUNT(*) as cnt  FROM `commit` WHERE `status` = '".$status."'";
        $rows = Pdo_an::db_fetch_row($sql);
        if ($rows)
        {
            return $rows->cnt;
        }
    }

    public static function sync($data)
    {

        self::timer_start_data();

        $res_return =[];

//        $limit = $data['limit'];
//        if (!$limit)
 //           $limit=500;

        ////check new data

        ///$array_update_status = self::last_commits_updated($data);////check status 2

        ///check status 6


        $send_request  = self::check_status(6);
        if ($send_request>1000)
        {
            ///wait
            return 'A request is already executed';
        }

        $array_sql = self::last_commits($data,0);////check status 0

        /// send data with status 0 to a remote server to sync_data function
        if ($array_sql )
        {

            foreach ($array_sql as $v)
            {
               self::update_status( $v['uniq_id'], 6);
            }

         $result =   self::push_request($array_sql,0);

         if ($result['error'])
         {
             return $result;
         }

         else if ($result['sync_result'])         ////// get an answer on request update status to 1
         {
             foreach ($result['sync_result'] as $key => $status) {
                 $time_current = self::timer_stop_data();

                 self::update_status($key, $status, $time_current);
             }
         }

            $res_return['get_status_0']=count($array_sql);
            $res_return['sinc']=count($result['sync_result']);

        }

        $res_return['result']=$result;

        return $res_return;

    }
    public  static function sync_last_commit($data)
    {
        ///get data status 1 (sync)

        self::timer_start_data();

        $array_sql =  self::last_sinc_commits($data);

        $res_return['last_sinc_commits']=count($array_sql);

        $send_request  = self::check_status(2);
        if ($send_request>200)
        {
            ///wait
           return 'A request is already executed';
        }



        if ($array_sql)
        {
            foreach ($array_sql as $i =>$v) {
                $uid = $v["uniq_id"];
                ////update request status
                self::update_status($uid, 2);////send request
            }


            ////get data from remote url  and add to db set status 4

            $result['last_sinc_commits']  = self::generate_request($array_sql);

        }

        $res_return['result']=$result;

        return $res_return;
    }
    public  static function sync_complete($data,$input_status=4,$output_status=5)
    {
        self::timer_start_data();
        ////get status 4 and add status 5 Complete
        $array_sql = self::last_commits($data,$input_status);////check status 4



        /// send data with status 0 to a remote server to sync_data function
        if ($array_sql )
        {
            $result =   self::push_request($array_sql,$output_status);

            if ($result['error'])
            {
                return $result;
            }

            else if ($result['sync_result'])
            {
                foreach ($result['sync_result'] as $key=>$status)
                {
                    $time_current = self::timer_stop_data();
                    self::complete_status($key,  $time_current ,'',$status );
                }
            }


            $res_return['complete']=count($array_sql);
            $res_return['sinc']=count($result['sync_result']);

        }

        $res_return['result']=$result;

        return $res_return;
    }

    public static function prepare_data($data)
    {



        $action = $data['action'];
//        if ($action == 'last_commit') {
//            $result = self::last_commits($data);
//        }

        if ($action == 'sync') { ////curl sinc

            self::service();///delete and change staus for old commit


            $result['sync'] = self::sync($data);             ///sync - send data to remote server and change status to 1

            $result['sync_last_commit'] = self::sync_last_commit($data);    ///sync_last_commit - get commit in status 1 and add from remote site update status to 4

            $result['sync_complete'] = self::sync_complete($data);
            $result['sync_empty'] = self::sync_complete($data,7,7);

        }

        else if ($action == 'sync_data') { ////get request from remote url status 0
            $result = self::sync_data($data);
        }

        else if ($action == 'get_commit') {
            $result = self::get_commit($data); ///set sql data from status 2
        }


        else if ($action == 'get_remote_id') {
        $result = self::get_remote_table($data); ///create id from table
         }

        else if ($action == 'get_last_id') {
            $result = self::get_last_id('',$data); ///create id from table
        }


        else
        {
            $result=array('not request'=>$data);
        }



//        if ($action == 'get') {
//            $result = self::get_data($data);
//        }
//        if ($action == 'set') {
//            $result = self::set_data($data);
//        }
//        if ($action == 'delete') {
//            $result = self::delete_data($data);
//        }

        return $result;
    }

//    public static function delete_data($data)
//    {
//        $data_obj = $data['data'];
//        $result = array();
//
//        if ($data_obj) {
//
//            $object_setup = json_decode($data_obj, 1);
//            if (!$object_setup) {
//                $result['error']['data_obj'] = json_last_error_msg();
//            }
//
//
//        }
//        else
//        {
//            $object_setup=[];
//
//        }
//        $options_data = self::get_import_data();
//        if ($options_data['delete_request']==1) {
//
//
//            foreach ($object_setup as $type => $object_data) {
//
//                $array_req = self::get_array_colmuns('', $object_data['request']);
//
//                $result[$type] = self::delete_table($type, $array_req['where']);
//
//            }
//        }
//        else
//        {
//            $result['request']   = 'no_permission_to_delete';
//        }
//        return $result;
//
//
//
//    }

    public static function get_data($data)
    {

        $data_obj = $data['data'];
        $result = array();

        if ($data_obj) {

            $object_setup = json_decode($data_obj, 1);
            if (!$object_setup) {
                $result['error']['data_obj'] = json_last_error_msg();
            }


        }
        else
        {
            $object_setup=[];

        }

        foreach ($object_setup as $type=>$object_data)
        {

            $array_req = self::get_array_colmuns($object_data['columns'],$object_data['request']);
            $result[$type] = self::get_table($type,$array_req['oper'],$array_req['where'] );

        }

        return $result;
    }

    public static function set_array_colmuns($array_columns=[],$request=[],$return=[])
    {

        $oper_insert_colums='';
        $oper_insert_data='';
        $data_array=[];
        $oper_update='';
        $oper_get_colums='';

        if ($array_columns) {

            foreach ($array_columns as $row => $value) {


                $oper_insert_colums .= ",`" . $row . "`";
                $oper_insert_data .= ",?";
                $data_array[] = $value;
                $oper_update .= ",`" . $row . "`=?";

            }

        }

        if (is_array($request))
        {
            foreach ($request as $i=>$v)
            {
                $where .= "AND `" . $i . "` = '" . $v . "' ";
            }
        }
        if ($where) {
            $where = substr($where, 3);
            $where = " WHERE (" . $where . ") ";
        }

        if (is_array($return)) {
            foreach ($return as $column => $data) {
                if ($column || $data) {
                    if (is_numeric($column)) {
                        $column = $data;
                        $data = '';
                    }

                    $oper_get_colums .= ",`" . $column . "`";

                }
            }
            $oper_get_colums = substr($oper_get_colums,1);
        }

        return array('data'=>array($oper_insert_colums, $oper_insert_data,$data_array, $oper_update),'where'=>$where,'return'=>$oper_get_colums);
    }
    public static function get_array_colmuns($array_columns=[],$request=[])
    {
        $oper_get_colums = '';
        $where = '';
        //////check array columns
        if (is_array($array_columns)) {
            foreach ($array_columns as $column => $data) {
                if ($column || $data) {
                    if (is_numeric($column)) {
                        $column = $data;
                        $data = '';
                    }

                    $oper_get_colums .= ",`" . $column . "`";

//                    if ($data && !$request) {
//                        if (is_array($data)) {
//                            foreach ($data as $val) {
//                                $where .= "OR `" . $column . "` = " . $val . " ";
//                            }
//                        } else {
//                            $where .= "OR `" . $column . "` = " . $data . " ";
//                        }
//
//                    }
                }
            }
        }
        if (is_array($request))
        {
            foreach ($request as $i=>$v)
            {
                if (is_array($v))
                {
                    $where_inner='';
                    foreach ($v as $val)
                    {
                        $where_inner .= "OR  `" . $i . "` = '" . $val . "' ";
                    }
                    if ($where_inner)
                    {
                        $where_inner = substr($where_inner, 3);
                        $where.= "AND (".$where_inner.")";
                    }


                }
                else
                {
                    $where.= "AND `" . $i . "` = '" . $v . "' ";
                }

            }
        }
        if ($where) {
            $where = substr($where, 3);
            $where = " WHERE (" . $where . ") ";
        }
        if ($oper_get_colums) {
            $oper_get_colums = substr($oper_get_colums, 1);
        } else {
            $oper_get_colums = '*';
        }
        return array('oper'=>$oper_get_colums,'where'=>$where);
    }
    public static function update_table($table_name, $data=[],$where='',$return=1)
    {

        $oper ='result';

        $sql = "SHOW COLUMNS FROM ".  $table_name." ";

        $rows = Pdo_an::db_results_array($sql);
        $first_row = $rows[0]['Field'];


        $result = [];

        $oper_insert_colums=$data[0];
        $oper_insert_data=$data[1];
        $data_array=$data[2];
        $oper_update=$data[3];
        if ($oper_update)
        {
            $oper_update = substr($oper_update, 1);
        }
        $uddate_id='';

        if ($oper_insert_colums && $where) {


            $sql = "SELECT * FROM " .  $table_name . $where;
            $query = Pdo_an::db_results_array($sql);
            if (self::debug()) {
                $result['request']['select_query']= $sql;
            }

            if ($query)
            {
                $uddate_id=1;
            }
            if (self::debug()) {
                $result['request']['select_query_update']= $uddate_id;
            }
        }


        if ($uddate_id) {
            // echo 'update';

            $oper='update';

            $inser_sql = "UPDATE `" . $table_name . "` SET " . $oper_update . " " . $where;
        } else {
            $oper_insert_colums = substr($oper_insert_colums, 1);
            $oper_insert_data = substr($oper_insert_data, 1);
            $inser_sql = "INSERT INTO `" . $table_name . "`( " . $oper_insert_colums . " )  VALUES ( " . $oper_insert_data . " )";

            $oper='insert';
        }
        if (self::debug()) {
            $result['request']['udpate_query']= $inser_sql;
            $result['request']['udpate_query_array']= $data_array;
        }

        $options_data = self::get_import_data();
        $table_access = self::get_table_access($table_name);

        if ((($oper=='update' && ($options_data['update_request']==1 || $table_access['import']==1 || $table_access['import']==2))
            || ($oper=='insert' && ($options_data['add_request']==1 ||  $table_access['import']==1))) && $table_access['import']!=3 )
        {
            Pdo_an::db_results_array($inser_sql, $data_array);
        }
        else
        {
            $oper='no_permission_to_'.$oper;
        }



        if ($return)
        {
            $sql_get = "SELECT ".$first_row." FROM `" . $table_name . "` ".$where."  limit 1 ";

            if (self::debug()) {
                $result['request']['select_query_after']= $sql_get;
              ///  $result['request']['select_query_after_array']= $data_array;
            }


            $rows = Pdo_an::db_results_array($sql_get);
            $result[$oper] = $rows[0];
        }
        return $result;
    }

    public static function delete_table($table_name, $where='')
    {

        $oper='delete';


        if ($where)
        {
            $sql = "DELETE  FROM " .  $table_name . $where;


            if (self::debug()) {
                $result['request']['delete_query']= $sql;
            }

            $options_data = self::get_import_data();
            $table_access = self::get_table_access($table_name);

            if  ($options_data['delete_request']==1 || $table_access['del']==1 )
            {
                $query = Pdo_an::db_results_array($sql);
            }
            else
            {
                $oper='no_permission_to_'.$oper;
            }


            $result[$oper] = 'success';



        }
        else
        {
            if (self::debug()) {
                $result[$oper] = 'no data';
            }

        }

        return $result;
    }

    public static function get_table($table_name, $oper_get_colums='',$where='')
    {
        if (!$oper_get_colums)
        {
            $oper_get_colums=" * ";
        }

        $sql = "SELECT " . $oper_get_colums . " FROM " .  $table_name . $where;
        $query = Pdo_an::db_results_array($sql);
        if (self::debug()) {
            return array('columns' => $query, 'request' => $sql);
        }
        return array('columns' => $query);
    }

    public static function prepare_json($result)
    {
        return '<pre><code>'.json_encode($result,JSON_PRETTY_PRINT).'</pre></code>';

    }

    public static function prepare_json_print($array_request,$link_all,$object_setup_id_multy)
    {

        $link_id_multy_dist = $array_request;
        $link_id_multy_dist['data']=$object_setup_id_multy;
        $link_id_multy_dist_string = self::prepare_json($link_id_multy_dist);
        $link_id_multy = $link_all.'&data='.json_encode($object_setup_id_multy,JSON_UNESCAPED_SLASHES);

        return array($link_id_multy_dist_string,$link_id_multy);
    }



}