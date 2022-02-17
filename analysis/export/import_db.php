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

    public static function get_key()
    {
        $key='1R3W5T8s13t21a34f';
        return $key;
    }
    public static function generate_request($array_sql)
    {
        $result =[];
        $array_updated =[];

    foreach ($array_sql as $i =>$v)
    {
        $uid = $v["uniq_id"];
        $sql_data =  self::commit_info_request($uid);

        if ($sql_data["error"])
        {
            return $sql_data;
        }

        $rslt = self::set_data($sql_data);
        $result[] = self::update_commit_data($uid,$rslt);
        $array_updated[$uid]=2;

    }

    return $result;
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
        'update_status'=>2
    );

    $result =  GETCURL::getCurlCookie($link,'',$request);


    if ($result)
    {
        $result = json_decode($result,1);
    }
    return $result;

}
    public static function push_request($array_sql=[])
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
            'limit'=>$limit
            );

        //$request =http_build_query($request);


       $result =  GETCURL::getCurlCookie($link,'',$request);



       if ($result)
       {
           $result = json_decode($result,1);
       }


       return $result;

    }

    public static function generate_id($name='')
    {
        $options_data = self::get_import_data();
        $site_id = $options_data['site_id'];
        $prefix = array(1=>'t_',2=>'_p');

        if (!$site_id)
        {
            $site_id =1;
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



    public static function create_commit($commit_id='',$type,$db,$request,$name = '')
    {

        $ajax_data = array("type"=>$type,"request"=>$request,"db"=>$db);
        return self::set_commit($name,$ajax_data,$commit_id);

    }

    public static function set_commit($name='',$data='',$unique_id='',$site_id='',$status=0)
    {

        if (!$unique_id || !$site_id)

        {
            $unique_id_array = self::generate_id($name);
            if (!$site_id) $site_id = $unique_id_array[1];
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

            $q = "INSERT INTO `commit`(`id`, `uniq_id`, `description`, `text`,`update_data`, `status`,`site_id`, `last_update`)
            VALUES (NULL,?,?,?,?,?,?,?)";

            Pdo_an::db_results_array($q, array($unique_id,$name,$data_string,'',$status,$site_id,time()));

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

            if ($data['update_status'])
            {
                $update_status =   intval($data['update_status']);
                self::update_status($uid,$update_status);

            }


                if (strstr($uid,','))
                {
                    $uid =explode(',',$uid);

                }

                if (is_array($uid)) {
                    foreach ($uid as $i ) {
                        $where .= "OR `uniq_id` = '" . $i . "' ";
                    }
                }
                else
                {
                    $where = "  `uniq_id` = '" . $uid . "' ";
                }
                if ($where) {
                    $where = substr($where, 2);
                    $where = " WHERE (" . $where . ") ";
                }

        }

        $sql ="SELECT *  FROM `commit` ".$where." ";

        $rows = Pdo_an::db_results_array($sql);
        foreach ($rows as $r)
        {
            $status = $r['status'];
            if ($status==0)
            {
                $key = $r['uniq_id'];
                self::update_status($key,1);
            }


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


        return $array_sql;

    }

    public static function create_request($val)
    {
        if ($val['type']=="update")
        {
            $db=$val['db'];
            $wo = $val['request'];
            if ($wo) {
                foreach ($wo as $i => $v) {
                    $where .= "and `" . $i . "` = '" . $v . "' ";
                }
                if ($where) {
                    $where = substr($where, 3);
                }


                $object_setup[$db]['request'] = $wo;

                $sql = "SELECT * FROM " . $db . " where ".$where;
                $result = Pdo_an::db_results_array($sql);
                $object_setup = [];


            }
            if (is_array($result))
                if (isset($result[0]))
                    foreach ($result[0] as $i=>$v) {
                     $object_setup[$db]['columns'][$i] = $v;
                    }

        }
        return array($val['type']=>$object_setup);

    }

    public static function last_sinc_commits($data)
    {
        $site_id = self::generate_id();
        $count=10;
        if ($data['count'])
        {
            $count =intval($data['count']);

        }
        $sql ="SELECT *  FROM `commit` WHERE `status` = 1 and site_id!='".$site_id."' limit ".$count;
        $rows = Pdo_an::db_results_array($sql);
        return $rows;
    }
    public static function last_commits_updated($data)
    {
        $count=10;
        if ($data['count'])
        {
            $count =intval($data['count']);

        }

        $site_id = self::generate_id();
        $sql ="SELECT `uniq_id` , `status`   FROM `commit` WHERE `status` = 2 and site_id!='".$site_id."' limit ".$count;
        $rows = Pdo_an::db_results_array($sql);
        return $rows;
    }


    public static function last_commits($data)
    {

        $count=10;
        if ($data['count'])
        {
            $count =intval($data['count']);

        }
        $sql ="SELECT *  FROM `commit` WHERE `status` = 0 limit ".$count;
        $rows = Pdo_an::db_results_array($sql);
        return $rows;
    }



    public static function get_import_data()
    {
        $sql = "SELECT `val` FROM `options` where id =18 ";
        $rows = Pdo_an::db_fetch_row($sql);

        $options_data  =$rows->val;
        if ($options_data)
        {
            $options_data = json_decode($options_data,1);
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

           $row =  self::check_status_commit($key);
           if (!$row) {
               self::set_commit($name,$data,$key,$site_id,1);
               $result[$key]=1;
           }
           else
           {
               $result[$key]=2;
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

    public static function set_data($data)
    {
        $result=[];


        foreach ($data as $key =>$array_data) {
            foreach ($array_data["data"] as $index => $object_setup_type) {
                foreach ($object_setup_type as $type => $object_setup_data) {

                    if ($type == 'update') {

                        foreach ($object_setup_data as $table => $object_setup) {

                            if (!$object_setup['request'])
                            {
                                $object_setup['request'] = self::check_request($object_setup['columns'],$table);
                            }


                            $array_req = self::set_array_colmuns($object_setup['columns'], $object_setup['request'], $object_setup['return']);

                          $result[$type][$table][] = self::update_table($table, $array_req['data'], $array_req['where']);
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

        ////update data
        $data_obj = $data['commit_data'];

        if ($data_obj) {
            $object_setup_all = json_decode($data_obj, 1);
            if (!$object_setup_all) {
                $result['error']['data_obj'] = json_last_error_msg();
            }

            $result_data = self::check_and_set_data($object_setup_all);
        }

//        $update_status = $data['update_status'];
//        $update_result = self::sinc_status_commit($update_status);


        /////get last commit

        $last_commit =self::last_commits($data);

        return array('sync_result'=>$result_data,'last_commit'=>$last_commit,'update_status_result'=>$update_result);

    }

    public static function update_commit_data($key,$update_data)
    {
        $update_data = json_encode($update_data);
        $sql = "UPDATE `commit` SET `update_data`=?, `status`= 2 , `last_update`='".time()."' WHERE `uniq_id`  = '".$key."'";
        Pdo_an::db_results_array($sql,array($update_data));
        return array($key=>2);
    }

    public static function update_status($key,$status)
    {
        $sql = "UPDATE `commit` SET `status`='".$status."' WHERE `uniq_id`  = '".$key."'";
        Pdo_an::db_query($sql);
    }

    public static function sync($data)
    {

        $res_return =[];

        $limit = $data['limit'];
        if (!$limit)$limit=10;

        ////check new data

        ///$array_update_status = self::last_commits_updated($data);////check status 2
        $array_sql = self::last_commits($data);////check status 0

        if ($array_sql )
        {
         $result =   self::push_request($array_sql);

         if ($result['error'])
         {
             return $result;
         }


         if ($result['sync_result'])
         {
             foreach ($result['sync_result'] as $key=>$status)
             {
                 self::update_status($key,1);
             }
         }

            $res_return['get_status_0']=count($array_sql);
            $res_return['sinc']=count($result['sync_result']);
         //check commit from remote url

            if ($result['last_commit'])
            {
                $result_data = self::check_and_set_data($result['last_commit']);
            }


            $res_return['last_commit']=count($result['last_commit']);
        }


        ///get data from url

        $array_sql =  self::last_sinc_commits($data);

        $res_return['last_sinc_commits']=count($array_sql);

        if ($array_sql)
        {
            $result['last_sinc_commits']  = self::generate_request($array_sql);
        }



        return array($res_return,$result);

    }

    public static function prepare_data($data)
    {

        $action = $data['action'];
//        if ($action == 'last_commit') {
//            $result = self::last_commits($data);
//        }

        if ($action == 'get_commit') {
            $result = self::get_commit($data);
        }
        else if ($action == 'sync') { ////curl sinc
            $result = self::sync($data);
        }
        else if ($action == 'sync_data') { ////get request from remote url
            $result = self::sync_data($data);
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

    public static function delete_data($data)
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
        $options_data = self::get_import_data();
        if ($options_data['delete_request']==1) {


            foreach ($object_setup as $type => $object_data) {

                $array_req = self::get_array_colmuns('', $object_data['request']);

                $result[$type] = self::delete_table($type, $array_req['where']);

            }
        }
        else
        {
            $result['request']   = 'no_permission_to_delete';
        }
        return $result;



    }

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
            if (is_array($request))
            {
                foreach ($request as $i=>$v)
                {
                    $where .= "OR `" . $i . "` = " . $v . " ";
                }
            }
            if ($where) {
                $where = substr($where, 2);
                $where = " WHERE (" . $where . ") ";
            }
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

        if (($oper=='update' && $options_data['update_request']==1) || ($oper=='insert' && $options_data['add_request']==1))
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
        if ($where)
        {
            $sql = "DELETE  FROM " .  $table_name . $where;
           // $query = Pdo_an::db_results_array($sql);
            if (self::debug()) {
                return array( 'request' => $sql);
            }
        }
        else
        {
            if (self::debug()) {
                return array('request' => 'no data');
            }

        }




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

    public  function show_table($type)
    {
return;
        $link = 'import.php';
        $key='1R3W5T8s13t21a34f';

        $sql = "SELECT * FROM " .  $type . " LIMIT 1";
        $result = Pdo_an::db_results_array($sql);
        $object_setup=[];
        $object_setup_id=[];
        $object_setup_id_set=[];
        $object_setup_id_set_field=[];
        $object_setup_id_set_field_result=[];
        $object_setup_del=[];
        $r=0;
        $object_setup_column=[];

        $object_setup_simple[$type]= [];

        if (is_array($result))
        if (isset($result[0]))
        foreach ($result[0] as $i=>$v)
        {
            if ($r==0)
            {
                $object_setup[$type]['request'][$i]=$v;
                $object_setup_id[$type]['request'][$i]=$v;
                $object_setup_id_set[$type][0]['request'][$i]=$v;
                $object_setup_id_set_field[$type][0]['request'][$i]=$v;
                $object_setup_del[$type]['request'][$i]=$v;

            }
            $r++;

           $object_setup[$type]['columns'][]=$i;
           $object_setup_id_set[$type][0]['columns'][$i]=$v;

           if ($r<3)
           {
               $object_setup_column[$type]['columns'][]=$i;
               $object_setup_id_set_field[$type][0]['columns'][$i]=$v;

           }

        }

        $sql = "SELECT * FROM " .  $type . " LIMIT 3";
        $result = Pdo_an::db_results_array($sql);

        $object_setup_id_multy=[];
        $object_setup_id_multy_more=[];
        foreach ($result as $index=>$value)
        {
            $r=0;
            foreach ($value as $i=>$v)
            {

                if ($r==0)
                {
                    $object_setup_id_set_field_result[$type][$index]['return'][]=$i;
                    $object_setup_id_multy[$type]['request'][$i][]=$v;
                    $object_setup_del_multy[$type]['request'][$i][]=$v;
                }
                if ($r>0)
                {
                    $object_setup_id_set_field_result[$type][$index]['columns'][$i]=$v;
                }
                if ($r>1) break;
                $r++;
                $object_setup_id_multy_more[$type]['request'][$i][]=$v;

            }
        }


        $data = self::prepare_json($object_setup);
        $data_set = self::prepare_json($object_setup_id_set);
        $data_del = self::prepare_json($object_setup_del);

        $array_request =array('key'=>$key,'action'=>'get');


        $link_all_dist =    self::prepare_json($array_request);
        $link_all = $link.'?key='.$key.'&action=get';


        $link_simple  = self::prepare_json_print($array_request,$link_all,$object_setup_simple);

        $link_default  = self::prepare_json_print($array_request,$link_all,$object_setup);

        $link_id  = self::prepare_json_print($array_request,$link_all,$object_setup_id);

        $link_id_multy  = self::prepare_json_print($array_request,$link_all,$object_setup_id_multy);
        $link_id_multy_more  = self::prepare_json_print($array_request,$link_all,$object_setup_id_multy_more);
        $link_column = self::prepare_json_print($array_request,$link_all,$object_setup_column);

        $link_all_set = $link.'?key='.$key.'&action=set';
        $array_request_set =array('key'=>$key,'action'=>'set');
        $link_default_set  = self::prepare_json_print($array_request_set,$link_all_set,$object_setup_id_set);
        $link_fields_set  = self::prepare_json_print($array_request_set,$link_all_set,$object_setup_id_set_field);
        $link_fields_result_set  = self::prepare_json_print($array_request_set,$link_all_set,$object_setup_id_set_field_result);


        $link_all_del = $link.'?key='.$key.'&action=delete';
        $array_request_del =array('key'=>$key,'action'=>'delete');
        $link_default_del = self::prepare_json_print($array_request_del,$link_all_del,$object_setup_del);
        $link_default_del_multy = self::prepare_json_print($array_request_del,$link_all_del,$object_setup_del_multy);

$content='<div>
<p class="togleafter"><a id="cl" class="open_ul " href="#"></a>Запросы на получение данных get '.$type.'</p>
<div style="display: none">
<p>Стандартные поля для запроса:</p>

<p>key = '.$key.' <span class="desc">//ключ доступа</span><p>
<p>action =get <span class="desc">//действие</span><p>
<p>data ={"product":{"columns":{},"request":{}},"other_table":{"columns":{},"request":{}},...} <span class="desc">//json обьект с данными</span><p>
<p>columns <span class="desc">//запрашивает поля для показа, по умолчанию отображаются все поля</span></p>
<p>request <span class="desc">//запрос по значению, например по id может содержать стринг или массив</span></p>
<p>пример запроса data:</p>
'.$data.'
<p>Пример</p>
<a href=\''.$link_default[1].'\'" target="_blank">'.$link_default[1].'</a>
<hr>

<p>Примеры запроса со всеми данными</p>
<p>'.$link_simple[0].'</p>
<a href=\''.$link_simple[1].'\'" target="_blank">'.$link_simple[1].'</a>
<hr>
<p>Примеры запроса по параметру</p>
<p>'.$link_id[0].'</p>
<a href=\''.$link_id[1].'\'" target="_blank">'.$link_id[1].'</a>
<hr>
<p>Примеры мульти запроса по параметру</p>
<p>'.$link_id_multy[0].'</p>
<a href=\''.$link_id_multy[1].'\'" target="_blank">'.$link_id_multy[1].'</a>
<hr>
<p>Примеры мульти запроса по нескольким параметрам</p>
<p>'.$link_id_multy_more[0].'</p>
<a href=\''.$link_id_multy_more[1].'\'" target="_blank">'.$link_id_multy_more[1].'</a>
<hr>
<p>Примеры отображения column</p>
<p>'.$link_column[0].'</p>
<a href=\''.$link_column[1].'\'" target="_blank">'.$link_column[1].'</a>
</div>


<p class="togleafter"><a id="cl" class="open_ul " href="#"></a>Добавление, обновление данных set '.$type.'</p>
<div style="display: none">
<p>Стандартные поля для запроса:</p>

<p>key = '.$key.' <span class="desc">//ключ доступа</span><p>
<p>action =set <span class="desc">//действие</span><p>
<p>data ={"product":{["columns":{},"request":{},"return:{}"],["columns":{},"request":{},"return:{}"],... }} <span class="desc">//json обьект с массивом данных</span><p>
<p>columns <span class="desc">//поля которые будут обновляться</span></p>
<p>request <span class="desc">//запрос по значению, например по id должен быть стрингом (не обязательное)</span></p>
<p>return <span class="desc">//вернуть значение поля после добавления записи (не обязательное)</span></p>
<p>пример запроса data:</p>
'.$data_set.'
<p>Пример</p>
<a href=\''.$link_default_set[1].'\'" target="_blank">'.$link_default_set[1].'</a>
<hr>

<p>Пример добавления/обновления нескольких полей</p>
<p>'.$link_fields_set[0].'</p>
<a href=\''.$link_fields_set[1].'\'" target="_blank">'.$link_fields_set[1].'</a>
<hr>
<p>Пример добавления нескольких строк с возвратом данных</p>
<p>'.$link_fields_result_set[0].'</p>
<a href=\''.$link_fields_result_set[1].'\'" target="_blank">'.$link_fields_result_set[1].'</a>
<hr>
</div>




<p class="togleafter"><a id="cl" class="open_ul " href="#"></a>Удаление данных   delete '.$type.'</p>
<div style="display: none">
<p>Стандартные поля для запроса:</p>

<p>key = '.$key.' <span class="desc">//ключ доступа</span><p>
<p>action =delete <span class="desc">//действие</span><p>
<p>data ={"product":{request":{}}} <span class="desc">//json обьект с массивом данных</span><p>
<p>request <span class="desc">//запрос по значению, например по id должен быть стрингом (не обязательное)</span></p>
<p>пример запроса data:</p>
'.$data_del.'
<p>Пример</p>
<a href=\''.$link_default_del[1].'\'" target="_blank">'.$link_default_del[1].'</a>
<hr>
<p>Пример удаления группы данных</p>
<p>'.$link_default_del_multy[0].'</p>
<a href=\''.$link_default_del_multy[1].'\'" target="_blank">'.$link_default_del_multy[1].'</a>
<hr>
</div>
</div>';

  return $content;
    }

}