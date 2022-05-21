<?php

error_reporting(E_ERROR);

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


class METALOG
{
    public static function get_groop()
    {
        $array_result=[];
        $sql="SELECT `description` FROM `commit` GROUP BY `description` ";
        $rows =Pdo_an::db_results_array($sql);
        foreach ($rows as $i =>$v)
        {
            $array_result[] =  $v["description"];
        }
        return $array_result;
    }

    public static function clear_history()
    {
    $sql = "DELETE FROM `meta_commit_log` WHERE `time` < '".(time()-86400*7)."'";
     Pdo_an::db_query($sql);
    }

    public static function get_last_data($type)
    {
    $time =time()-86400;
    $timew =time()-86400*7;


    $sql="SELECT COUNT(*) as count FROM `meta_commit_log` where `time` >".$time." and `type` = '".$type."'";

    $row = Pdo_an::db_fetch_row($sql);
    $daily = $row->count;


        $sql="SELECT COUNT(*) as count FROM `meta_commit_log` where `time` >".$timew." and `type` ='".$type."'";

        $row = Pdo_an::db_fetch_row($sql);
        $week = $row->count;

        return array($daily,$week);

    }


public static function update_log($action_id,$action_type=1)
{
    $sql = "INSERT INTO `meta_commit_log`(`id`, `type`, `result`, `time`) VALUES (NULL,'{$action_id}',".$action_type.",".time().")";
    Pdo_an::db_query($sql);
}

}
