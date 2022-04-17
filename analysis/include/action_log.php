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


public static function update_actor_log($id,$action_type=1)
{
    $action_id = static::$array[$id];
    $sql = "INSERT INTO `meta_actors_log`(`id`, `type`, `result`, `time`) VALUES (NULL,{$action_id},".$action_type.",".time().")";
    Pdo_an::db_query($sql);

}

}
