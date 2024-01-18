<?php
error_reporting(E_ERROR );
ini_set('memory_limit', '4096M');
include  ABSPATH.'an_config.php';

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


include ($_SERVER['DOCUMENT_ROOT'].'/wp-config.php');
global $debug;
$debug =0;

function get_flag($ip)
{
    // Critic matic
    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
    }

    if ($ip) {
        global $cm;
        if (!$cm)
        {
            $cm = new  CriticMatic();
        }
        $country_data  =$cm->get_geo_flag_by_ip($ip);

        if ($country_data['path']) {
            $country_name = $country_data['name'];
            $ip_img = '<span title="' . $country_name . '"><img src="' . $country_data['path'] . '" /></span> ';
        }



    }
    return $ip_img;

}


if (function_exists('current_user_can'))
{
    $curent_user =current_user_can("administrator") ;
}

if ( $curent_user) {

//DB config
    !defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
    !class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


    if ($_POST['oper'] == 'get_graph')
    {

        !class_exists('Last_update') ? include ABSPATH . "analysis/include/last_update_graph.php" : '';
        $Last_update = new Last_update();

        $Last_update->show_data();

        return;
    }


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
    $table_data='';

    if ($_GET['data']) {

        $sql = "SELECT *
FROM information_schema.tables
WHERE table_type='BASE TABLE'
AND table_schema='".DB_NAME_AN."'";

        $rows = Pdo_an::db_results_array($sql);


     if (isset($_GET['doptable'])) {
            $table_data = $_GET['doptable'];
        }
       else  if (isset($_GET['data'])) {
            $table_data = $_GET['data'];
            $table_data_main = 'data_'.$_GET['data'];
        }

        $table_data = preg_replace("/[^a-zA-Z0-9_]/", "",$table_data);

        foreach ($rows as $r) {

            $link = $r["TABLE_NAME"];
            if ($link==$table_data) {

                $te=1;
                break;
            }
        }
        if (!$te){
            foreach ($rows as $r) {

                $link = $r["TABLE_NAME"];
                if ($link==$table_data_main) {
                    $table_data=$table_data_main;
                    $te=1;
                    break;
                }
            }

        }


        if (!$te)return;


    }



      if ($_POST['oper'] == 'del' || $_POST['oper'] == 'edit' || $_POST['oper'] == 'add') {


        if ($_POST['oper'] == 'del') {



            if ($table_data=='data_movie_imdb')
            {

                !class_exists('DeleteMovie') ? include ABSPATH . "analysis/include/delete_movie.php" : '';
                DeleteMovie::delete_movie($_POST['parent'],1,'admin');
            }
            else
            {
                $sql = "DELETE FROM  `" . $table_data . "`   WHERE `id` = '" . intval($_POST['parent']) . "'";
                Pdo_an::db_query($sql);

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'delete', $table_data, array('id' => intval($_POST['parent'])), 'user_'.$table_data,6);


            }





        }
        else if (($_POST['oper'] == 'edit') || ($_POST['oper'] == 'add')) {

            $array = $_POST;

            unset($array['oper']);


            foreach ($array as $i => $v) {
                if (strstr($v, '\"')) {
                    $v = str_replace('\"', '"', $v);
                    $array[$i] = $v;
                }
                if (strstr($v, "\'")) {
                    $v = str_replace("\'", "'", $v);
                    $array[$i] = $v;
                }
                if (strstr($v, "\\/")) {
                    $v = str_replace("\\/", "\/", $v);
                    $array[$i] = $v;
                }
                if (strstr($v, "\\")) {
                    $v =  preg_replace('/\\\\+/', '\\', $v);
                    $array[$i] = $v;
                }
                if ($i=='ip')
                {
                    if (strstr($v,'span'))
                    {
                        $pos = strpos($v,'</span>');
                        $v = trim(substr($v,$pos+7));
                        $array[$i] = $v;
                    }
                }


            }

            global $debug;
            if ($debug) { !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
                TMDB::var_dump_table($arraay);

            }

           // var_dump($array);
            $countval = count($array);

            $sql = "SELECT *  FROM `INFORMATION_SCHEMA`.`COLUMNS`  WHERE `TABLE_SCHEMA`='".DB_NAME_AN."' AND `TABLE_NAME`='" . $table_data . "'   ORDER BY `COLUMNS`.`ORDINAL_POSITION` ASC";

           //echo $sql;

            $rows = Pdo_an::db_results_array($sql);
            $arrdb_type=[];

            foreach ($rows as $r) {


                $arrdb[$r["ORDINAL_POSITION"]] = $r["COLUMN_NAME"];

                $arrdb_type[$r["ORDINAL_POSITION"]] =$r["DATA_TYPE"];
            }
            //  ksort($arrdb);

            ///   var_dump($arrdb);

            $arrayrequest = [];
            $array_index = [];

            foreach ($arrdb as $index => $val) {


                if ($_POST['oper'] == 'edit') {

                    ///check data




                    if ($val != 'id' && $val != 'parent') {




                        if ($val == 'review_id' ) {
                            $review_id = $array[str_replace(' ', '_', $val)];

                            $reg='#\<a[^\>]+\>([0-9]+)#';

                            if (preg_match($reg,$review_id,$match))
                            {

                                $review_id =   $match[1];

                            }

                            $arrayrequest[] = $review_id;

                            $qres .= ", `" . $val . "` = ? ";

                            $qcheck .= ", `" . $val . "` = '".$review_id."' ";
                        }
                        else if ($val == 'add_time' || $val == 'last_update'  || $val == 'lastupdate'  || $val == 'last_upd' ) {
                            $arrayrequest[] = time();


                                $qres .= ", `" . $val . "` = ? ";

                                $qcheck .= ", `" . $val . "` = '".time()."' ";
                        }

                        else {

                            if (isset( $array[str_replace(' ', '_', $val)])) {
                                $qres .= ", `" . $val . "` = ? ";


                                $data = $array[str_replace(' ', '_', $val)];
                                if (!$data  && $arrdb_type[$index] =='int' )$data = 0;

                                $arrayrequest[] = $data;

                                $qcheck .= ", `" . $val . "` = '".$data."' ";
                            }
                        }
                    }


                } else if ($_POST['oper'] == 'add') {

                    if ($val != 'id' && $val != 'parent') {
                        $qres .= ", ? ";
                        ///$qres .= ",'".$array[str_replace(' ','_',$val)]."' ";
                        //
                        if ($val == 'add_time' || $val == 'last_update'  || $val == 'lastupdate' || $val == 'last_upd') {
                            $arrayrequest[] = time();
                        } else {
                            $data =  $array[str_replace(' ', '_', $val)];
                            if (!$data  && $arrdb_type[$index] =='int' )$data = 0;
                            $arrayrequest[] = $data;

                        }


                        $array_index[] = '`' . $val . '`';
                    }

                }

            }
            if ($qres) {
                $qres = substr($qres, 1);
            }
            if ($qcheck) {
                $qcheck = substr($qcheck, 1);
            }

            if ($_POST['oper'] == 'edit') {


                $sql = "UPDATE `" . $table_data . "` SET " . $qres . "  WHERE `id` = '" . $array['parent'] . "'";


                $sql_check = "UPDATE `" . $table_data . "` SET " . $qcheck . "  WHERE `id` = '" . $array['parent'] . "'";

                global $debug;
                if ($debug) { !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
                    TMDB::var_dump_table(['sql debug'=>$sql_check,'sql'=>$sql,'array'=>$arrayrequest]);

                }


                $result = Pdo_an::db_results_array($sql, $arrayrequest);

                if ($table_data == 'data_actors_crowd' || $table_data == 'data_movies_pg_crowd') {
                    ///update cache
                    $sql = "select * from " . $table_data . " where id =" . $array['parent'];
                    $row = Pdo_an::db_fetch_row($sql);
                    if ($row->status == 1) {
                        !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';
                        Crowdsource::rebuild_cache($array['parent'], substr($table_data, 5));
                    }
                }


                if ($table_data=='data_movie_imdb')
                {
                    $title =$arrayrequest[3];
                    $movie_id =$arrayrequest[0];
                    $comment =$title.' Updated manually';

                    !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
                    TMDB::add_log('',$movie_id,'update movies',$comment,1,'admin');
                }
                $res_id = $array['parent'] ;
                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', $table_data, array('id' => intval($res_id)), 'user_'.$table_data,6);
            }
            else if ($_POST['oper'] == 'add') {

                $array_index = implode(',', $array_index);

                //print_r($arrayrequest);
                ///$qres = implode(',',$arrayrequest);


                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                [$enable,$id_insert] = Import::check_table_access($table_data);

              //  echo $enable.' : '.$id_insert;
                if ($enable)
                {


                if ($enable && $id_insert)
                {
                    $sql = "INSERT INTO  `" . $table_data . "` ( `id` , " . $array_index . ") values ( '".$id_insert."', " . $qres . " ) ";
                }
                else
                {
                    $sql = "INSERT INTO  `" . $table_data . "` (" . $array_index . ") values ( " . $qres . " ) ";
                }

                ///echo  "INSERT INTO  `" . $table_data . "` (" . $array_index . ") values ( " . $qcheck . " ) ";

                    $res_id= Pdo_an::db_insert_sql($sql, $arrayrequest);


                    !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                    Import::create_commit('', 'update', $table_data, array('id' => intval($res_id)), 'user_'.$table_data,6);


                }
                else
                {
                    echo 'Permission denied';
                }



            }


        }
    }


    if (isset($_POST['_search']))//////
    {

//ob_start();

        $page = $_POST['page'];      //
        $limit = $_POST['rows'];     //
        $sidx = $_POST['sidx'];      //

        $sord = $_POST['sord'];      //
        $qustom_sort = $_POST['qustom_sort'];
        if (!$sidx)
        {
            $sidx =$qustom_sort;
            if (!$sord)$sord ='desc';
        }




        if (isset($_GET['db'])) {
            if ($_GET['db'] == 'transcriptions') {

                $db = 'Pdo_tc';


            }
        }


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

        } else {
            if (isset($_POST['status'])) {
                $status = strval($_POST['status']);
                if ($status !== 'All') {
                    $status = intval($status);

                }
                $where1 = ' AND  status =  ' . $status . '  ';
            }
        }

        if (isset($_POST['qustom_request'])) {

            if ($_POST['qustom_request'])
            {


                $qr = json_decode(stripslashes($_POST['qustom_request']));


             if ($qr)
             {
                 !class_exists('Last_update') ? include ABSPATH . "analysis/include/last_update_graph.php" : '';
                 $Last_update = new Last_update();

                 $where1.=  $Last_update->prepare_request($qr);


             }

            }



        }

        global $pdo;

        $sql = "SELECT count(*) as count FROM  " . $table_data . " where  1 = 1  " . $where1;
//echo $sql;
        if ($db == 'Pdo_tc') {
            $result = Pdo_tc::db_fetch_row($sql);
        } else {
            $result = Pdo_an::db_fetch_row($sql);
        }


        $count = $result->count;

        /// echo $count;


//
        if ($count > 0 && $limit > 0) {
            $total_pages = ceil($count / $limit);
        } else {
            $total_pages = 0;
        }
//              -                           
        if ($page > $total_pages) $page = $total_pages;

//                                     LIMIT        
        $start = $limit * $page - $limit;
//                                  
        if ($start < 0) $start = 0;

//id, user_name, user_company_name, user_status  FROM  `bra_users`  WHERE id = '".$row[User_Id]."
//                      


        $sql = "SELECT * FROM " . $table_data . " where  1 = 1 " . $where1 . "  ORDER BY " . $sidx . " " . $sord . " LIMIT " . $start . ", " . $limit;


        if ($db == 'Pdo_tc') {
            $result_rows = Pdo_tc::db_results_array($sql);
        } else {
            $result_rows = Pdo_an::db_results_array($sql);
        }

        $responce = (object)[];
        $responce->page = $page;
        $responce->total = $total_pages;
        $responce->records = $count;
        $i = 0;


        foreach ($result_rows as $row) {
            if ($row['last_update']){$row['last_update'] = date('H:i d:m:Y',$row['last_update']);}
            if ($row['lastupdate']){$row['lastupdate'] = date('H:i d:m:Y',$row['lastupdate']);}
            if ($row['add_time']){$row['add_time'] = date('H:i d:m:Y',$row['add_time']);}
            if ($row['last_upd']){$row['last_upd'] = date('H:i d:m:Y',$row['last_upd']);}

            if ($row['ip']){$row['ip'] = get_flag($row['ip']).$row['ip'];}




            $responce->rows[$i]['id'] = $row[0];
            $responce->rows[$i]['cell'] = $row;

            $i++;
        }


//ob_clean();
//var_dump($responce);
        echo json_encode($responce);

    }

}
else
{
    echo 'pd';
}