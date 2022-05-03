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



require_once('import_db.php');
// Database

///get commit
//////analysis/export/import.php?key=1R3W5T8s13t21a34f&action=last_commit&count=10
//////analysis/export/import.php?key=1R3W5T8s13t21a34f&action=get_commit&uid=t_add_movies1643729961,t_actor_meta1643729962



if (isset($_REQUEST['key'])) {



    $key = '1R3W5T8s13t21a34f';
    if ($_REQUEST['key']!=$key)
    {
        echo 'false key';
        return;
    }

    $data = $_REQUEST;

    $import = new Import;
    $result = $import->prepare_data($data);

    $result_json =  json_encode($result);
    if (!$result_json) {

        echo json_last_error_msg();
        var_dump($result);

        return;
    }
    header('Content-Type: application/json');
    echo $result_json;

    return;


}
else if (isset($_POST['_search']))//////
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
    if ($period)$period = intval($period);
    if (!$period)$period=24*86400;

    $min_time = time()-$period*86400;

    $step = ($period/60)*86400;


    //$sql ="SELECT * FROM `commit` WHERE add_time > {$min_time}  ".$where1;
    $sql ="SELECT * FROM `commit` WHERE last_update > {$min_time}  ".$where1;
    $row = Pdo_an::db_results_array($sql);
    $result = [];
    foreach ($row as $r)
    {

        $description = $r['description'];

        $add_time = $r['add_time'];
        if (!$add_time)
        {
            $add_time = $r['last_update'];

        }
        if ($add_time)
        {
            $add_time = round( $add_time/$step,0)*$step*1000;
            $result[$description][$add_time]+=1;
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

        $array_series['series'][]=['name'=>$index,'data'=>$data_r];

    }
    $array_series['title']= 'Commits count';

    echo json_encode($array_series);






}


