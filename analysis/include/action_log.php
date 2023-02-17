<?php

error_reporting(E_ERROR);

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


class ACTIONLOG
{
    public static $array = array('data_actors_meta'=>1,'data_actors_imdb'=>2,'data_actors_surname'=>3,'bettaface'=>4,'kairos'=>5,
        'name'=>6,'image'=>7,'verdict'=>8,'new_actors'=>9,'gender'=>10,'tmdb_id'=>11,'tmdb_image'=>12,'tmdb_add_imdbid'=>13);





    public static function clear_history()
    {
    $sql = "DELETE FROM `meta_actors_log` WHERE `time` < '".(time()-86400*7)."'";
     Pdo_an::db_query($sql);
    }

    public static function get_last_data($db)
    {
    $time =time()-86400;
    $timew =time()-86400*7;

    $type = static::$array[$db];

    $sql="SELECT COUNT(*) as count FROM `meta_actors_log` where `time` >".$time." and `type` = ".$type;

    $row = Pdo_an::db_fetch_row($sql);
    $daily = $row->count;


        $sql="SELECT COUNT(*) as count FROM `meta_actors_log` where `time` >".$timew." and `type` = ".$type;

        $row = Pdo_an::db_fetch_row($sql);
        $week = $row->count;

        return array($daily,$week);

    }


public static function update_actor_log($id,$table='',$aid=0,$action_type=1)
{
    $action_id = static::$array[$id];
    $sql = "INSERT INTO `meta_actors_log`(`id`, `type`, `result`, `table`, `actor_id`, `last_update`) VALUES (NULL,{$action_id},".$action_type.",".$table.",".$aid.",".time().")";
    Pdo_an::db_query($sql);

}

public function log_data_graph()
{
//ob_start();

    $page = $_POST['page'];      //
    $limit = $_POST['rows'];     //
    $sidx = $_POST['sidx'];      //
    $sord = $_POST['sord'];      //


    if (!$sidx) {
        $sidx = 1;
    } else {

        $sidx = '`' . $sidx . '`';
    }

    $qWhere = '';

    if ((isset($_POST['_search']) && $_POST['_search'] == 'true') || ($_POST['search'] == 'true')) {
        $allowedFields = array('surname', 'fname', 'lname');
        $allowedOperations = array('AND', 'OR');

        $searchData = ($_POST['filters']);
        $data = $_POST['data'];
        $table = $_POST['table'];


        function getWhereClause($col, $oper, $val)
        {


            $ops = array(
                'eq' => '=', //equal
                'ne' => '<>',//not equal
                'lt' => '<', //less than
                'le' => '<=',//less than or equal
                'gt' => '>', //greater than
                'ge' => '>=',//greater than or equal
                'bw' => 'LIKE', //begins with
                'bn' => 'NOT LIKE', //doesn't begin with
                'in' => 'LIKE', //is in
                'ni' => 'NOT LIKE', //is not in
                'ew' => 'LIKE', //ends with
                'en' => 'NOT LIKE', //doesn't end with
                'cn' => 'LIKE', // contains
                'nc' => 'NOT LIKE'  //doesn't contain
            );


            if ($oper == 'bw' || $oper == 'bn') $val .= '%';
            if ($oper == 'ew' || $oper == 'en') $val = '%' . $val;
            if ($oper == 'cn' || $oper == 'nc' || $oper == 'in' || $oper == 'ni') $val = '%' . $val . '%';
            return "  $col {$ops[$oper]} '$val' ";
        }


        $allowedOperations = array('AND', 'OR');

        $searchData = ($_POST['filters']);

        $searchData = trim(preg_replace("/([\\\]+)([\'\"]{1})/", "\$2", $searchData));
        $group = substr($searchData, strpos($searchData, '"groupOp":"') + 11, strpos($searchData, '","rules') - 12);
        $searchData = substr($searchData, strpos($searchData, '[') + 1);
        $searchData = substr($searchData, 0, strpos($searchData, ']'));


        if (preg_match_all('#\{\"field\"\:\"(\w+)\"\,\"op\"\:\"(\w+)\"\,\"data\"\:\"([^\"]+)\"\}#', $searchData, $math0)) {
            foreach ($math0[0] as $value) {
                if (preg_match('#\{\"field\"\:\"(\w+)\"\,\"op\"\:\"(\w+)\"\,\"data\"\:\"([^\"]+)\"\}#', $value, $math)) {
                    switch ($math[2]) {
                        case 'eq':
                            $operation = " = '" . $math[3] . "'";
                            break;
                        case 'ne':
                            $operation = " <> '" . $math[3] . "'";
                            break;
                        case 'bw':
                            $operation = " LIKE '" . $math[3] . "%'";
                            break;
                        case 'cn':
                            $operation = " LIKE '%" . $math[3] . "%'";
                            break;
                        case 'bn':
                            $operation = " NOT LIKE '" . $math[3] . "%'";
                            break;
                        case 'ew':
                            $operation = " LIKE '%" . $math[3] . "'";
                            break;
                        case 'en':
                            $operation = " NOT LIKE '%" . $math[3] . "'";
                            break;
                        case 'nc':
                            $operation = " NOT LIKE '%" . $math[3] . "%'";
                            break;
                        case 'nu':
                            $operation = " IS NULL";
                            break;
                        case 'nn':
                            $operation = " IS NOT NULL";
                            break;
                        case 'in':
                            $operation = " IN ('" . str_replace(",", "','", $math[3]) . "')";
                            break;
                        case 'ni':
                            $operation = " NOT IN ('" . str_replace(",", "','", $math[3]) . "')";
                            break;
                        default:
                            $operation = " LIKE '%" . $math[3] . "%'";
                            break;
                    }
                    if ($math[3] != 'All') {

                        $where .= " " . $math[1] . " " . $operation . " " . $group . " ";

                    }

                }
            }
        }


        $where = substr($where, 0, strrpos($where, $group));


        $searchField = isset($_POST['searchField']) ? $_POST['searchField'] : false;
        $searchOper = isset($_POST['searchOper']) ? $_POST['searchOper'] : false;
        $searchString = isset($_POST['searchString']) ? $_POST['searchString'] : false;

        if ($searchField) {

            $where = getWhereClause($searchField, $searchOper, $searchString);

        }


        if (strlen($where) > 6) {
            $where1 = ' AND  ' . $where . '  ';

        }

        //   echo $where1;
    }


    ///get data for graph

    $period =$_POST['period'];


    $data_time='last_update';

    if ($period)$period = intval($period);
    if (!$period)$period=24*86400;

    $min_time = time()-$period*3600;

    $step =60*$period;// ($period)/60;


    $a_keys  = array_flip( static::$array);


    //$sql ="SELECT * FROM `commit` WHERE last_update > {$min_time}  ".$where1;
    $sql ="SELECT * FROM `meta_actors_log` WHERE {$data_time} > {$min_time}  ".$where1." order by {$data_time} ASC";
    $row = Pdo_an::db_results_array($sql);
    $result = [];
    foreach ($row as $r)
    {

        $description = $a_keys[$r['type']];


        $add_time = $r[$data_time];


        if ($add_time)
        {
            $add_time = round( $add_time/$step,0)*$step*1000;
            //$add_time=$add_time*1000;




                $count=1;

            $result[$description][$add_time]+=$count;
            $result['All'][$add_time]+=$count;
        }
    }
    $array_series=[];
    foreach ($result as $index=> $data)
    {
        $data_r = [];
        foreach ($data as $i=>$v)
        {
            $data_r[]=['x'=>$i,'y'=>$v];
        }

        if ($index=='All')
        {
            $array_series['series'][]=['name'=>$index,'data'=>$data_r,'visible'=>false,'turboThreshold'=>0];
        }
        else
        {
            $array_series['series'][]=['name'=>$index,'data'=>$data_r];
        }


    }
    if ($data_type=='time')
    {

        $array_series['title']= 'Commits time';
    }
    else
    {
        $array_series['title']= 'Commits count';

    }





    $array_series['period']=$period;
    $array_series['step']=$step;
//    $array_series['sql']=$sql;
    $array_series['time']=date('H:i d-m-Y',time());
    $array_series['min_time']=date('H:i d-m-Y',$min_time);
    echo json_encode($array_series);







}


}

if (isset($_POST['oper']))
{

 if ($_POST['oper']=='get_log_data')
 {

     $log = new ACTIONLOG();

     $log->log_data_graph();

 }
}
