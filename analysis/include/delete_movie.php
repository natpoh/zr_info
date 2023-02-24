<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
!class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';



class DeleteMovie{



    public static function delete_table($movie_id,$table_row,$table,$sync=1)
    {
        $table = trim($table);
        $movie_id = trim ($movie_id);

            $q = "select `".$table_row."` FROM `".$table."` WHERE `".$table_row."` = '" . $movie_id."'";
            $count = Pdo_an::db_results_array($q);

            global $debug;
            if ( $debug)
            {
                ///echo $q.'<br>';
                echo $movie_id.'=>'.$table.' '.count($count).' deleted<br>';
            }


            if ($count)
            {
        $q="DELETE FROM `".$table."` WHERE `".$table_row."` = ".$movie_id;
        Pdo_an::db_query($q);

        if ($sync && $count) {
            $skip='';

            if ($table_row!='id')
            {
                $skip =['skip' => ['id']];
            }
            Import::create_commit('', 'delete', $table, array($table_row => $movie_id), 'delete_'.$table, 10, $skip);
        }
            }


    }
    private static function check_actors($id,$actor)
    {
        $q = "SELECT aid FROM `meta_movie_actor` where mid!=".$id." and  aid =".$actor;
        $res = Pdo_an::db_results_array($q);
        if (!$res)
        {
            $q = "SELECT aid FROM `meta_movie_director` where mid!=".$id." and  aid =".$actor;
            $res = Pdo_an::db_results_array($q);
        }
        if ($res)
        {
            return 1;
        }

    }
    public static function delete_actor($id,$sync)
    {
        global $debug;

        if ($debug) {
            echo 'try delete actor ' . $id . '<br>';
        }
            self::delete_table($id,'actor_id','data_actors_meta',$sync);
            self::delete_table($id,'id','data_actors_imdb',$sync);
            self::delete_table($id,'aid','data_actors_ethnicolr',$sync);
            self::delete_table($id,'actor_id','data_actors_face',$sync);
            self::delete_table($id,'actor_id','data_actors_face',$sync);

    }

    public static function check_and_delete_actors($id,$sync)
    {
        $array = [];
        $q = "SELECT aid FROM `meta_movie_actor` where mid =".$id;
        $r = Pdo_an::db_results_array($q);
        foreach ($r as $v)
        {
            $array[$v['aid']]=1;
        }
        $q = "SELECT aid FROM `meta_movie_director` where mid =".$id;
        $r = Pdo_an::db_results_array($q);
        foreach ($r as $v)
        {
            $array[$v['aid']]=1;
        }
        global $debug;


        if ($array)
        {
            foreach ($array as $actor=>$enable)
            {
                $enable = self::check_actors($id,$actor);
                if ($enable && $debug)
                {
                    echo 'actor '.$id.' enabled<br>';
                }
                if (!$enable){self::delete_actor($actor,$sync);}
            }
        }

    }


    public static function get_title($id)
    {
        $id = intval($id);

        $sql = "SELECT title FROM `data_movie_imdb` where id ='" . $id . "' limit 1 ";

        $r = Pdo_an::db_fetch_row($sql);

        return $r->title;

    }
    public static function get_imdb_id_from_id($id)
    {
        $id = intval($id);

        $sql = "SELECT movie_id FROM `data_movie_imdb` where id ='" . $id . "' limit 1 ";

        $r = Pdo_an::db_fetch_row($sql);

        if ($r->movie_id) {
            $movie_id = $r->movie_id;
        }
        if ($movie_id) {
            return $movie_id;
        }
    }

    public static function delete_movie($id,$sync=0,$logtype='default')
    {

        global $debug;

        $id = intval($id);

        $movie_id = self::get_imdb_id_from_id($id);
        $title = self::get_title($id);

        if(!$movie_id)
        {
            if ($debug)
            {
                echo $id.' already deleted<br>';
            }

            return;
        }

        ///check an delete actors
        self::check_and_delete_actors($id,$sync);



        ////delete movie meta
        $array_meta = array('search_movies_meta','meta_movie_actor','meta_movie_country','meta_movie_director','meta_movie_genre','meta_movie_tmdb_actor','meta_movie_tmdb_director');

        foreach ($array_meta as $meta)
        {
            $q= "DELETE FROM `'.$meta.'` WHERE `mid` = ".$id;
            Pdo_an::db_query($q);
            if ($sync)
            {
                if ( $debug)
                {
                    echo $id.'=>'.$meta.' deleted<br>';
                }

                Import::create_commit('', 'delete', $meta, array('mid' => $id), 'delete_movie_meta',10,['skip'=>['id']]);
            }

        }
        ///delete from pg rating

        self::delete_table($movie_id,'movie_id','data_pg_rating',$sync);
        self::delete_table($id,'movie_id','data_movie_rating',$sync);
        self::delete_table($id,'movie_id','data_movie_erating',$sync);
        self::delete_table($id,'movie_id','cache_rating',$sync);
        self::delete_table($id,'movie_id','search_movies_meta',$sync);
        self::delete_table($id,'rwt_id','just_wach',$sync);
        self::delete_table($id,'rwt_id','cache_movie_trailers',$sync);
        self::delete_table($id,'mid','meta_movie_keywords',$sync);
        self::delete_table($id,'id','data_movie_imdb',$sync);





        $comment =$title.' deleted';
        TMDB::add_log($id,$movie_id,'delete movies',$comment,1,$logtype);
    }




}