<?php

class TMDBIMPORT
{
    public static function add_data_to_tmdb_table()
    {
        ///get movie list


        $sql = "select data_movie_imdb.id , data_movie_imdb.movie_id,  	data_movie_imdb.title  from data_movie_imdb LEFT JOIN data_movies_tmdb_actors 
    ON `data_movie_imdb`.id=data_movies_tmdb_actors.rwt_id
        WHERE  data_movies_tmdb_actors.id IS NULL and (data_movie_imdb.type ='Movie' OR data_movie_imdb.type ='TVSeries') limit 10000";
        $row = Pdo_an::db_results_array($sql);

        foreach ($row as $r)
        {
            /////add data
            $sql="INSERT INTO `data_movies_tmdb_actors`(`id`, `rwt_id`, `movie_id`, `title`, `imdb_actors`, `tmdb_actors`, `tmdb_actors_converted`, `status`, `last_update`) 
                VALUES (NULL,?,?,?,NULL,NULL,NULL,0,?)";
            Pdo_an::db_results_array($sql,array($r['id'],$r['movie_id'],$r['title'],time()));
        }

    }

    public static function add_tmdb_actors_to_tmdb_table()
    {


        ///add imdb actors
        $sql = "SELECT rwt_id FROM `data_movies_tmdb_actors` where tmdb_actors IS NULL limit 1000";
        $row = Pdo_an::db_results_array($sql);

        if (count($row)) {
            echo '<br>///////////////add_tmdb_actors_to_tmdb_table '.count($row).'<br>';

            foreach ($row as $r) {
                $actor_tmdb = self::get_tmdb_cast($r['rwt_id']);
                if ($actor_tmdb) {
                    $actor_tmdb_string = json_encode($actor_tmdb);
                    //update
                    $sql = "UPDATE `data_movies_tmdb_actors` SET `tmdb_actors` = ?, `status` = 1 WHERE `data_movies_tmdb_actors`.`rwt_id` = ? ";
                    Pdo_an::db_results_array($sql, [$actor_tmdb_string, $r['rwt_id']]);
                } else {
                    ///check and add data
                   // echo 'no data ' . $r['rwt_id'] . ' try get ';

                    $array_tmdb_data = self::get_tmbdb_parse_data($r['rwt_id']);

                    ///var_dump($array_tmdb_data["cast"]);

                    self::proced_tmdb_cast($r['rwt_id'], $array_tmdb_data["cast"]);
                    self::update_cache_tmdb_sinc($r['rwt_id'],4,'cast',1);

                    $actor_tmdb = self::get_tmdb_cast($r['rwt_id']);

                    if ($actor_tmdb) {
                       /// echo 'ok<br>';

                        $actor_tmdb_string = json_encode($actor_tmdb);
                        //update
                        $sql = "UPDATE `data_movies_tmdb_actors` SET `tmdb_actors` = ?, `status` = 1 WHERE `data_movies_tmdb_actors`.`rwt_id` = ? ";
                        Pdo_an::db_results_array($sql, [$actor_tmdb_string, $r['rwt_id']]);
                    }
                    else
                    {
                     //   echo 'false <br>';
                    }


                }

            }

            echo '<br>///////////////add_tmdb_actors_to_tmdb_table_end<br>';
        }
    }

    public static function add_imdb_actors_to_tmdb_table()
    {
        ///add imdb actors
        $sql = "SELECT rwt_id FROM `data_movies_tmdb_actors` where imdb_actors IS NULL limit 1000";
        $row = Pdo_an::db_results_array($sql);

        foreach ($row as $r)
        {
            $actor_imdb = self::get_default_cast($r['rwt_id']);
            if ($actor_imdb)
            {
                $actor_imdb_string = json_encode($actor_imdb);
                //update
                $sql= "UPDATE `data_movies_tmdb_actors` SET `imdb_actors` = ? WHERE `data_movies_tmdb_actors`.`rwt_id` = ? ";
                Pdo_an::db_results_array($sql,[$actor_imdb_string,$r['rwt_id']]);

            }
        }
    }

    public static function get_imdb_id_from_actors_tmdbid($tmdb_id)
    {
        $sql ="SELECT actor_id FROM `data_actors_tmdb` where tmdb_id = '".intval($tmdb_id)."' limit 1";
        $r = Pdo_an::db_fetch_row($sql);
        if ($r)
        {
            return $r->actor_id;
        }
    }

    public static function convert_tmdb_actors_to_tmdb_table()
    {



        $sql = "SELECT rwt_id, tmdb_actors, imdb_actors FROM `data_movies_tmdb_actors` where `status` =1  limit 1000";
        $row = Pdo_an::db_results_array($sql);

        $i=0;
        $count = count($row);

        foreach ($row as $r)
        {
            echo $i.'/'.$count.' ';
            $i++;

            $actors = $r['tmdb_actors'];
            $actors_array_new = [];
            $actors_array = json_decode($actors,1);

            $actors_imdb = $r['imdb_actors'];
            $actors_imdb_array = json_decode($actors_imdb,1);


            if ($actors_array)
            {
                ///conver actors

                foreach ($actors_array as $name =>$tmdb_id)
                {
                    $imdb_id =self::get_imdb_id_from_actors_tmdbid($tmdb_id);
                    if (!$imdb_id)
                    {
                      $actors_array_new=[];

                      self::compare_arrays($actors_array,$actors_imdb_array);


                      echo $r['rwt_id'].' not actor imdb_id '.$tmdb_id.'<br>';
                    break;
                    }
                    else
                    {
                        $actors_array_new[$tmdb_id]=$imdb_id;
                    }

                }
                echo '<br>';

            }


            if ($actors_array_new)
            {
               ///update converted data
                $actors_array_new_string = json_encode($actors_array_new);
                //update
                $sql= "UPDATE `data_movies_tmdb_actors` SET `tmdb_actors_converted` = ?, `status`=3 WHERE `data_movies_tmdb_actors`.`rwt_id` = ? ";
                Pdo_an::db_results_array($sql,[$actors_array_new_string,$r['rwt_id']]);
            }
            else
            {
                echo $r['rwt_id'].' set status 2 <br>';

                $sql= "UPDATE `data_movies_tmdb_actors` SET  `status`=2 WHERE `data_movies_tmdb_actors`.`rwt_id` = ".$r['rwt_id'];
                Pdo_an::db_query($sql);
            }

        }

    }

    public static function compare_arrays($actors_tmdb_array,$actors_imdb_array)
    {
      //  var_dump($actors_tmdb_array);
       // echo '<br>';
      //  var_dump($actors_imdb_array);


        global $ma;
        $actors_imdb_array_modifed=[];
        foreach ($actors_imdb_array as $name=>$imdbid)
        {

           $name = strtolower($name);
           $new_name =  $ma->create_slug($name,' ');
           $actors_imdb_array_modifed[$new_name]=$imdbid;

        }
        //echo '<br>';
        //var_dump($actors_imdb_array_modifed);

        $count = count($actors_tmdb_array);
        $i=0;
        foreach ($actors_tmdb_array as $name=>$tmdbid)
        {
            $i++;

            if ($actors_imdb_array[$name])
            {
              // echo $i.'/'.$count.' '. $name.' enabled<br>';
            }

            else if (!$actors_imdb_array[$name])
            {
              //  echo $i.'/'.$count.' '. $name.'   ';
               //try to find
                $name = strtolower($name);
                $new_name =  $ma->create_slug($name,' ');
                if ($new_name)
                {
                if ($actors_imdb_array_modifed[$new_name])
                {
                    echo $new_name.' found <br>';
                    $actor_imdb = $actors_imdb_array_modifed[$new_name];
                    ///add to db

                    $imdb_id_check = self::get_imdb_id_from_actors_tmdbid($tmdbid);
                    if (!$imdb_id_check)
                    {
                        $sql ="UPDATE `data_actors_tmdb` SET `actor_id`='{$actor_imdb}', `last_update`='".time()."' WHERE `tmdb_id`='{$tmdbid}'";
                        // echo $sql;
                        Pdo_an::db_query($sql);
                        !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';
                        ACTIONLOG::update_actor_log('tmdb_add_imdbid','data_actors_tmdb',$actor_imdb);

                    }

                }
                else
                {
                 //   echo $new_name.' notfound <br>';
                }
                }

               /// echo '<br>';

            }


        }

    }


    public static function check_actors_empty()
    {


        echo '<br>///////////////check_actors_empty<br>';

        !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';

        $sql = "SELECT rwt_id, tmdb_actors, imdb_actors FROM `data_movies_tmdb_actors` where `status` =2  limit 1000";
        $row = Pdo_an::db_results_array($sql);

        $actor_counts =0;

        foreach ($row as $r)
        {
            $actors = $r['tmdb_actors'];
            $actors_array = json_decode($actors,1);

            $actors_imdb = $r['imdb_actors'];
            $actors_imdb_array = json_decode($actors_imdb,1);
            $total_not_found=[];

            $skip_movie=0;

            if ($actors_array)
            {

                ///conver actors
                if ($actor_counts>10)
                {
                    break;
                }

                foreach ($actors_array as $name =>$tmdb_id)
                {
                    if ($actor_counts>10)
                    {
                        break;
                    }

                    $imdb_id =self::get_imdb_id_from_actors_tmdbid($tmdb_id);
                    if (!$imdb_id) {
                    echo $tmdb_id.' not found data, try to add <br>';


                        /////try add actors
                     $imdb_id = TMDB::add_tmdb_without_id($tmdb_id);
                        if ($imdb_id != 'n') {
                            $actor_counts++;
                        }
                        else {
                            echo 'imdb_id is 0 <br>';
                        }

                    }
                    if (!$imdb_id || $imdb_id == 'n') {
                        $total_not_found[] = $tmdb_id;
                        $skip_movie = 1;
                    }
                }


                if ($skip_movie==0)
                {
                    echo $r['rwt_id'].' return to status 1<br>';

                    $sql= "UPDATE `data_movies_tmdb_actors` SET  `status`=1 WHERE `data_movies_tmdb_actors`.`rwt_id` = ? ";
                    Pdo_an::db_results_array($sql,[$r['rwt_id']]);
                }
                else
                {
                    echo $r['rwt_id'].' total not found  '.count($actors_array).' / '.count($total_not_found).'<br>';
                }



            }


        }

        echo '///////////////check_actors_empty<br><br>';
    }

    public static function update_actors_imdb_to_tmdb()
    {

        $sql = "SELECT * FROM `data_movies_tmdb_actors` where `status` =3  limit 1000";
        $row = Pdo_an::db_results_array($sql);


        foreach ($row as $r)
        {
            $actors_converted = $r['tmdb_actors_converted'];
            $array  = json_decode($actors_converted,1);

            //var_dump($array);


            $id = $r['rwt_id'];
            ////get tmdb array
            $sql ="SELECT *  FROM `meta_movie_tmdb_actor` where `meta_movie_tmdb_actor`.`mid` ='".$id."'";
            /// echo $sql;
            $rm =Pdo_an::db_results_array($sql);
            if ($rm)
            {
                //////delete and rebuild movie meta
                $sql="DELETE FROM `meta_movie_actor` WHERE mid = {$id}";
                Pdo_an::db_query($sql);

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                $commit_id = Import::create_commit($commit_id, 'delete', 'meta_movie_actor', array('mid' => $id),'movie_meta_actor',3);



                $array_temp=[];
                $array_type = [];

                foreach ($rm as $data)
                {

                    $aid =$data['aid'];

                ///    echo ' aid='.$aid.'<br>';

                    $aid_imdb = $array[$aid];
                    $pos =$data['pos'];
                    $type =$data['type'];

                    $array_type[$type]=1;

                    $array_temp[]=[$id,$aid_imdb,$pos,$type];

                 //   var_dump([$id,$aid_imdb,$pos,$type,$aid]);

                 ///   echo 'data<br>';
                }

                    $pos = 1;
                    foreach ($array_temp as $num=>$data)
                    {
                        if ($num>2)$pos=2;
                        if ($num>14)$pos=3;

                        if (!$array_type[1])
                        {
                       //     echo 'not pos<br>';

                            $data[2]=$num;
                            $data[3]=$pos;
                        }
                       // var_dump($data);

                        $sql = "INSERT INTO `meta_movie_actor`(`id`, `mid`, `aid`, `pos`, `type`)
                                    VALUES (NULL,?,?,?,?)";
                        $last_id =Pdo_an::db_insert_sql($sql,$data);


                       /// Import::create_commit($commit_id,'update','meta_movie_actor',array('id'=>$last_id),'movie_meta_actor',5,['skip'=>['id']]);

                        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                        $array_custom =array('skip'=>['id']);
                        Import::create_commit($commit_id,'update','meta_movie_actor',array('mid'=>$data[0],'aid'=>$data[1],'type'=>$data[3]),'movie_meta_actor',4,$array_custom);
                    }


//                ///update status
                $sql= "UPDATE `data_movies_tmdb_actors` SET  `status`=4 WHERE `data_movies_tmdb_actors`.`rwt_id` = ? ";
                Pdo_an::db_results_array($sql,[$id]);
            }
        }
    }


    public static function set_tmdb_actors_for_movies()
    {

        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        }

        if (!class_exists('MoviesAn')) {
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractFunctions.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBAn.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDB.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMatic.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesAn.php' );
            global $ma;
            $ma = new MoviesAn();
        }

        ///get movie list, add empty data
        self::add_data_to_tmdb_table();

        //add imdb actors
        self::add_imdb_actors_to_tmdb_table();

        ///add tmdb actors
        self::add_tmdb_actors_to_tmdb_table();


        ///convert tmdb_actors
        self::convert_tmdb_actors_to_tmdb_table();


        ///check actors empty
        self::check_actors_empty();


        ///update actor meta

        self::update_actors_imdb_to_tmdb();


    }




    public static  function update_tmdb_actors($id='')
{



    !class_exists('KAIROS') ? include ABSPATH . "analysis/include/kairos.php" : '';
    ///set logs
    !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';


    $where='';
    if ($id)
    {
        $where = " AND `data_actors_meta`.id=".intval($id);
    }

    $sql = "SELECT `data_actors_meta`.`id`,`data_actors_meta`.`actor_id`, `data_actors_tmdb`.`tmdb_id` , `data_actors_tmdb`.`profile_path` , `data_actors_tmdb`.`gender` FROM `data_actors_meta` LEFT JOIN `data_actors_tmdb` ON `data_actors_tmdb`.`actor_id` = `data_actors_meta`.`actor_id`
    
    WHERE `data_actors_meta`.`tmdb_id` IS NULL AND `data_actors_tmdb`.`tmdb_id`>0  AND  `data_actors_tmdb`.`status`=1  ".$where." LIMIT 600";
//echo $sql.'<br>';
    $rows = Pdo_an::db_results_array($sql);
    foreach ($rows as $r) {
        ///update tmdb_id
        $id =  $r['id'];
        $imdb_id =  $r['actor_id'];
        $image='';
        $image_add=0;


        if ($r['profile_path']) {
            $image = "https://www.themoviedb.org/t/p/w600_and_h900_bestv2" .$r['profile_path'];
            $image_add = KAIROS::check_image_on_server($imdb_id, $image, '_tmdb');
            ///try copy images
        }

        $tmdb_id = $r['tmdb_id'];

        $gender='';

        if ($r['gender']) {
            $gender = "`gender` = '" . intval($r['gender']) . "',";

        }

            echo 'UPDATE '.$imdb_id.' ' . $tmdb_id . ' ' . $gender . ' ' . $image_add . '<br>';

            $sql1 = "UPDATE `data_actors_meta` SET
                              `tmdb_id` = '" . intval($tmdb_id) . "',
                              `tmdb_img` = '" . intval($image_add) . "',
                              ".$gender."
                               `last_update` = " . time() . "
                              
                   WHERE `data_actors_meta`.`id` = '" . $id . "'";


            Pdo_an::db_query($sql1);

            ACTIONLOG::update_actor_log('tmdb_id','data_actors_meta',$imdb_id);
            if ($image_add)ACTIONLOG::update_actor_log('tmdb_image','data_actors_meta',$imdb_id);
        if ($gender) ACTIONLOG::update_actor_log('gender','data_actors_meta',$imdb_id);

        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'update', 'data_actors_meta', array('id' => $id), 'movie_meta_actor',6);

    }


    }

    public static function proced_tmdb_cast($id, $array)
    {
        // var_dump($array_tmdb["cast"]);


        ////get default cast

        $cast = self::get_default_cast($id);

        //var_dump($cast);

        ////compare actors
        foreach ($array as $i => $data) {

            if ($cast[$data["name"]]) {
                $data["imdb_id"] = $cast[$data["name"]];
               // echo $data["imdb_id"] . ' ' . $data["name"] . ' enabled <br>' . PHP_EOL;
            }
            //else  echo $data["name"] . ' false <br>' . PHP_EOL;
            ////update tmdb table
            self::update_tmdb_actors_table($data);

        }
        self::update_tmdb_actors_meta_table($id, $array);

    }


    public static function check_imdb_data($id = '')
    {

        !class_exists('TMDBIMPORT') ? include ABSPATH . "analysis/include/tmdb_import.php" : '';

        /////get request data ////type = 1///country


        if ($id)
        {

            $sql = "SELECT `data_movie_imdb`.`id` FROM  `data_movie_imdb`  left join `cache_tmdb_sinc` ON cache_tmdb_sinc.rwt_id = data_movie_imdb.id 
        WHERE data_movie_imdb.id = ".$id." order by cache_tmdb_sinc.id limit 1";

            echo $sql;

        }
        else
        {

            $sql = "SELECT `data_movie_imdb`.`id` FROM  `data_movie_imdb`  left join `cache_tmdb_sinc` ON cache_tmdb_sinc.rwt_id = data_movie_imdb.id 
        WHERE (cache_tmdb_sinc.id IS NULL ) order by cache_tmdb_sinc.id limit 1000";

        }
        if (isset($_GET['update_all']))
        {
            $sql = "SELECT `data_movie_imdb`.`id` FROM  `data_movie_imdb`  left join `cache_tmdb_sinc` ON cache_tmdb_sinc.rwt_id = data_movie_imdb.id 
        WHERE (cache_tmdb_sinc.id IS NOT NULL ) order by cache_tmdb_sinc.id";


        }



        //OR (`cache_tmdb_sinc`.`type` = 1 and `cache_tmdb_sinc`.`status` = 0 )

        $r = Pdo_an::db_results_array($sql);
        $count =count($r);
        $i=0;
        if ($r)
        {
            foreach ($r as $row)
            {

                $i++;

                echo $i.'/'.$count.'<br>'.PHP_EOL;


                $id = $row['id'];
                $result='';
                $update_countries='';


                $array_tmdb = self::get_tmbdb_parse_data($id);

                if ($array_tmdb["countries"])
                {
                    if ($array_tmdb["countries"])
                    {
                        $update_countries = self::get_tmdb_countries($id,$array_tmdb["countries"],1);

                        if ($update_countries)
                        {
                            $update_countries =json_encode($update_countries);
                        }
                        ///echo 'countries enable <br>';
                    }
                    else
                    {
                        echo 'no countries';
                    }

                   $result.=  self::update_cache_tmdb_sinc($id,1,$update_countries,1);
                }
                else
                {
                    $result =    self::update_cache_tmdb_sinc($id,1,'',0);
                }

                if ($array_tmdb["poster"])
                {

                    self::update_movie_data($id);

                    $result.=  self::update_cache_tmdb_sinc($id,2,$array_tmdb["poster"],1);
                }
                else
                {
                    $result.=    self::update_cache_tmdb_sinc($id,2,'',0);
                }


                if ($array_tmdb["title"])
                {
                    $result.=  self::update_cache_tmdb_sinc($id,3,$array_tmdb["title"],1);
                }
                else
                {
                    $result.=    self::update_cache_tmdb_sinc($id,3,'',0);
                }

                if ($array_tmdb["cast"])
                {
                    self::proced_tmdb_cast($id, $array_tmdb["cast"]);

                    $result.=  self::update_cache_tmdb_sinc($id,4,'cast',1);
                }
                else
                {
                   $result.=  self::update_cache_tmdb_sinc($id,4,'cast',0);
                }


                if ($array_tmdb["crew"])
                {


                    $cast = self::get_default_crew($id);


                    ////compare actors
                    foreach ($array_tmdb["crew"] as $i=>$data)
                    {

                        if ($cast[$data["name"]])
                        {
                            $data["imdb_id"]=  $cast[$data["name"]];
                            echo $data["imdb_id"].' '.$data["name"].' enabled'.PHP_EOL;
                        }
                        else  echo $data["name"].' false'.PHP_EOL;
                        ////update tmdb table
                        self::update_tmdb_actors_table($data);


                    }
                    self::update_tmdb_directors_meta_table($id,$array_tmdb["crew"]);


                    $result.=  self::update_cache_tmdb_sinc($id,5,'crew',1);
                }
                else
                {
                     $result.=  self::update_cache_tmdb_sinc($id,5,'crew',0);
                }


                echo $id.' '.$result.'<br>'.PHP_EOL;

            }

        }


    }
    function get_data_from_archive($id,$movie_id,$last_update)
    {
        $data = self::get_archive($id,$movie_id,$last_update);
        global $debug;
        if ($debug){echo 'get_data_from_archive '; var_dump($data);}
        ///var_dump($data);
        if ($data) {
            $key = array_keys($data);
            $data_file = $data[$key[0]];
        }
        return $data_file;
    }

    public static function get_tmbdb_parse_data($movie_id)
    {


        ///get movie type

        $sql = "SELECT `type` FROM `data_movie_imdb` WHERE `id`=".intval($movie_id)." limit 1";
        $r = Pdo_an::db_fetch_row($sql);

        $type = $r->type;

        $array_type = array('Movie'=>9,'TVSeries'=>10);

        $tmdb_title='';

            $id = $array_type[$type];

            $data = self::get_archive($id,$movie_id);

            ///var_dump($data);
             if ($data)
            {
                $key = array_keys($data);
                $data_file = $data[$key[0]];
                $data_file = json_decode($data_file,1);

                ///var_dump($data_file);
                //update countries
                $countries = $data_file["production_countries"];

                ///get poster
                $poster =  $data_file["poster_path"];

                $title =  $data_file["title"];
                if ($title)
                {
                    $tmdb_title= $title;
                }

                $cast = $data_file["credits"]["cast"];
                $crew = $data_file["credits"]["crew"];

            }

        return array('countries'=>$countries,'poster'=>$poster,'title'=>$tmdb_title,'cast'=>$cast,'crew'=>$crew);

    }
    public static function get_archive($company_id=12,$top_movie=0,$last_update=0)
    {
        $result = [];

        //nocache_headers();

        if (!defined(MOVIES_LINKS_PLUGIN_DIR))
        {
            define('MOVIES_LINKS_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/movies_links/');
        }

        if (!class_exists('MoviesLinks')) {

            require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
            require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );

            require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
            require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesParserCron.php' );
        }

        $ml = new MoviesLinks();
// get parser
        $mp = $ml->get_mp();


        $start = 0;
        $count = 100;

        //var_dump([$company_id,$start,$count,$top_movie]);

        $arhives = $mp->get_last_arhives($company_id,$start,$count,$top_movie,$last_update);


        global $debug;
        if ($debug)var_dump($arhives);

        if ($arhives){
            foreach ($arhives as $item) {

                $file = $mp->get_arhive_file($company_id,$item->arhive_hash);

                $result[$item->arhive_hash]=$file;
            }
        }

        return $result;
    }
    public static function update_movie_data($id)
    {

        $sql="UPDATE `data_movie_imdb` SET `add_time`=".time()." WHERE id =".$id;
        Pdo_an::db_query($sql);

        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'update', 'data_movie_imdb', array('id' => $id), 'movie_update',5);

    }
    public static function get_tmdb_countries($mid,$array,$update =0)
    {
        $array_countries =[];
        global $ma;

        if ($array)
        {

        if (!class_exists('MoviesAn')) {

            require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractFunctions.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBAn.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDB.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMatic.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesAn.php' );
            $ma = new MoviesAn();
        }


        if ($update)
        {
            $ma->remove_movie_country($mid);
        }


        foreach ($array as $country_data)
        {
            $cca2 =  $country_data ["iso_3166_1"];
            $country =  TMDB::get_country_name($cca2);

            $gid = $ma->get_or_create_country_by_name($country);
            $array_countries[$gid]=$country;
            if ($update) {
                ///re attach countries
                $ma->add_movie_country($mid, $gid);
            }
        }

        return $array_countries;
    }
    }
    public static function update_cache_tmdb_sinc($id,$type,$data,$status)
    {
        $sql = "SELECT id FROM `cache_tmdb_sinc` WHERE `rwt_id` =".$id." and `type`={$type}";
        $r = Pdo_an::db_fetch_row($sql);

        if ($r)
        {
            $sql="UPDATE `cache_tmdb_sinc` SET `data`=?,`status`=?,`last_update`=? WHERE id =?";
            Pdo_an::db_results_array($sql,array($data,$status,time(),$r->id));
            return 'updated';
        }
        else
        {
            $sql ="INSERT INTO `cache_tmdb_sinc`(`id`, `rwt_id`, `type`, `data`, `status`, `last_update`) 
                VALUES (NULL,?,?,?,?,?)";
            Pdo_an::db_results_array($sql,array($id,$type,$data,$status,time()));
            return $type.' added; ';
        }


//        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
//        Import::create_commit('', 'update', 'cache_tmdb_sinc', array('rwt_id' => $id,'type'=>$type), 'cache_tmdb',6);


    }

    public static function get_default_crew($id)
    {
        $result =[];
        $sql ="SELECT `meta_movie_director`.`aid` , `data_actors_imdb`.`name`  FROM `meta_movie_director`  left join data_actors_imdb ON data_actors_imdb.id = `meta_movie_director`.`aid` where `meta_movie_director`.`mid` ='".$id."'";
        /// echo $sql;
        $r =Pdo_an::db_results_array($sql);

        foreach ($r as $v)
        {
            $result[$v['name']]=$v['aid'];

        }
        return $result;
    }
    public static function get_default_cast($id)
    {
        $result =[];
        $sql ="SELECT `meta_movie_actor`.`aid` , `data_actors_imdb`.`name`  FROM `meta_movie_actor`  left join data_actors_imdb ON data_actors_imdb.id = `meta_movie_actor`.`aid` where `meta_movie_actor`.`mid` ='".$id."'";
        /// echo $sql;
        $r =Pdo_an::db_results_array($sql);

        foreach ($r as $v)
        {
            $result[$v['name']]=$v['aid'];

        }
        return $result;
    }
    public static function get_tmdb_cast($id)
    {
        $result =[];
        $sql ="SELECT `meta_movie_tmdb_actor`.`aid` , `data_actors_tmdb`.`name`  FROM `meta_movie_tmdb_actor`  left join data_actors_tmdb ON data_actors_tmdb.tmdb_id = `meta_movie_tmdb_actor`.`aid` where `meta_movie_tmdb_actor`.`mid` ='".$id."'";
        /// echo $sql;
        $r =Pdo_an::db_results_array($sql);

        foreach ($r as $v)
        {
            $result[$v['name']]=$v['aid'];

        }
        return $result;
    }

    public static function update_tmdb_actors_meta_table($id,$data)
    {

        $sql ="DELETE FROM `meta_movie_tmdb_actor` WHERE `mid` = ".$id;
        Pdo_an::db_query($sql);

        //!class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        //Import::create_commit('', 'delete', 'meta_movie_tmdb_actor', array('mid' => $id), 'tmdb_actors_meta',6);

        foreach ($data as $val)
        {
            $type=1;
            $pos = $val["order"];

            if ($pos>2)
            {
                $type=2;
            }
            if ($pos>14)
            {
                $type=3;
            }

            $sql = "INSERT INTO `meta_movie_tmdb_actor`(`id`, `mid`, `aid`, `pos`, `type`) 
                VALUES (NULL,{$id},{$val['id']},{$pos},{$type})";
            Pdo_an::db_query($sql);


        }
        //Import::create_commit('', 'update', 'meta_movie_tmdb_actor', array('mid' => $id), 'tmdb_actors_meta',6);

    }

    public static function update_tmdb_directors_meta_table($id,$data)
    {
        $director_types = array('Director' => 1, 'Writer' => 2, 'Casting' => 3,'Producer'=>4,'Executive Producer'=>4,'Co-Producer'=>4,'Associate Producer'=>4);

        $sql ="DELETE FROM `meta_movie_tmdb_director` WHERE `mid` = ".$id;

        //!class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        //Import::create_commit('', 'delete', 'meta_movie_tmdb_director', array('mid' => $id), 'tmdb_actors_meta',6);

        Pdo_an::db_query($sql);

        foreach ($data as $val)
        {
            $type=5;

            if ($director_types[$val["job"]])
            {
                $type=  $director_types[$val["job"]];
            }


            $sql = "INSERT INTO `meta_movie_tmdb_director`(`id`, `mid`, `aid`, `pos`, `type`,`department`, `job`) 
                VALUES (NULL,{$id},{$val['id']},NULL,{$type},?,?)";
            Pdo_an::db_results_array($sql,array($val["department"],$val["job"]));

        }
        //Import::create_commit('', 'update', 'meta_movie_tmdb_director', array('mid' => $id), 'tmdb_actors_meta',6);
    }




    public static function update_tmdb_actors_table($data)
    {
        $id = $data['id'];

        if ($id)
        {
            $sql ="SELECT * FROM `data_actors_tmdb` WHERE `tmdb_id` = ".intval($id)." limit 1";
            $r =Pdo_an::db_fetch_row($sql);
            if ($r)
            {
                ///check imdb id
                if ($data["imdb_id"])
                {
                    if ($r->actor_id && $r->actor_id !=$data["imdb_id"])
                    {
                        ///update error status
                        $sql = "UPDATE `data_actors_tmdb` SET `actor_id`={$data["imdb_id"]},`status`=2,`last_update`=".time()." WHERE id=".$r->id;
                        Pdo_an::db_query($sql);

                    }
                    else if ($r->actor_id && $r->actor_id ==$data["imdb_id"] && $r->status ==2 )
                    {
                        ///update error status
                        $sql = "UPDATE `data_actors_tmdb` SET `actor_id`={$data["imdb_id"]},`status`=1,`last_update`=".time()." WHERE id=".$r->id;
                        Pdo_an::db_query($sql);

                    }
                    else if (!$r->actor_id )
                    {
                        $sql = "UPDATE `data_actors_tmdb` SET `actor_id`={$data["imdb_id"]},`status`=1,`last_update`=".time()." WHERE id=".$r->id;
                        Pdo_an::db_query($sql);
                    }


                }
                else
                {
                    //skip
                }
            }
            else
            {
                $status=1;
                if (!$data["imdb_id"])
                {
                    $status=2;
                }

                ///add
                $sql="INSERT INTO `data_actors_tmdb`(`id`, `actor_id`, `tmdb_id`, `gender`, `known_for_department`, `name`, `original_name`, `profile_path`, `popularity`, `status`, `last_update`) 
                        VALUES (NULL,?,?,?,?,?,?,?,?,?,?)";
                Pdo_an::db_results_array($sql,array($data["imdb_id"],$data["id"],$data["gender"],$data["known_for_department"],$data["name"],$data["original_name"],$data["profile_path"],$data["popularity"],$status,time()));

            }

            //!class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            //Import::create_commit('', 'update', 'data_actors_tmdb', array('tmdb_id' => $data["id"]), 'actor_tmdb',6);

        }
    }

}
