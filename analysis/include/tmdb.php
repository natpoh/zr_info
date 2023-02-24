<?php
error_reporting(E_ERROR);

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
}


class TMDB
{
    public static $api_key = '1dd8ba78a36b846c34c76f04480b5ff0';




    public static function add_tmdb_without_id($tmdb_id_input='')
    {

        !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';



        if ($tmdb_id_input)
        {
            $sql ="SELECT * FROM `data_actors_tmdb` WHERE `tmdb_id` ='".intval($tmdb_id_input)."' and (actor_id is NULL OR actor_id = 0) limit 1";
          ///  echo $sql.'<br>';
        }
        else
        {
            $sql ="SELECT * FROM `data_actors_tmdb` WHERE `known_for_department` ='Acting' and actor_id is NULL limit 10";
        }


        $row = Pdo_an::db_results_array($sql);

        $count = count($row);

        if (!$count && $tmdb_id_input)
        {
            return 'n';

        }

        foreach ($row as $r)
        {
            $tmdb_id = $r['tmdb_id'];


            ////get imdb id from tmdb
            $result = self::get_person_tmdb_data($tmdb_id);



            ///update imdb data

            if ($result["name"])
            {
                $actor_imdb = $result["imdb_id"];

                if (!$actor_imdb)
                {
                    $actor_imdb=0;
                }
                else
                {
                    $actor_imdb =substr($actor_imdb,2);
                    $actor_imdb = intval($actor_imdb);
                }
                echo  $tmdb_id.' => '.$actor_imdb.' '.$result["name"].'<br>';

                $sql ="UPDATE `data_actors_tmdb` SET `actor_id`='{$actor_imdb}', `last_update`='".time()."' WHERE `tmdb_id`='{$tmdb_id}'";
               /// echo $sql;
                Pdo_an::db_query($sql);

                //!class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                //Import::create_commit('', 'update', 'data_actors_tmdb', array('actor_id' => $actor_imdb), 'actor_tmdb',6);



                ACTIONLOG::update_actor_log('tmdb_add_imdbid','data_actors_tmdb',$actor_imdb);
            }


        }
        if ($tmdb_id_input)
        {

            return $actor_imdb;
        }
        else
        {
            echo 'add_tmdb_without_id updated '.$count.'<br>';
        }

    }

    public static function get_person_tmdb_data($tmdb_id)
    {
        $result =self::get_tmdb_data_movie($tmdb_id,'person');
        return $result;
    }


    public static function check_imdb_data($id)
    {

        !class_exists('TMDBIMPORT') ? include ABSPATH . "analysis/include/tmdb_import.php" : '';
         TMDBIMPORT::check_imdb_data($id);

    }
    public static  function update_tmdb_actors($id='')
    {
        !class_exists('TMDBIMPORT') ? include ABSPATH . "analysis/include/tmdb_import.php" : '';
        TMDBIMPORT::update_tmdb_actors($id);

    }


    public static function get_id_from_imdbid($id)
    {
        $id = intval($id);

        $sql = "SELECT id FROM `data_movie_imdb` where `movie_id` ='" . $id . "' limit 1 ";
        $r = Pdo_an::db_fetch_row($sql);
        return $r->id;

    }
    public static function get_tranding_tv()
    {
        $url = 'https://api.themoviedb.org/3/trending/tv/week?api_key=' . static::$api_key;
        $result = GETCURL::getCurlCookie($url);

        return $result;
    }
    public static function get_movie_type_from_id($id)
    {
        $sql = "SELECT `type` FROM `data_movie_imdb` where `id` ='" . $id . "' limit 1 ";

        $r = Pdo_an::db_fetch_row($sql);
        if ($r->type)
        {
            return $r->type;
        }
    }
    public static function get_tmdbid_from_id($id)
    {
        $sql = "SELECT tmdb_id, movie_id FROM `data_movie_imdb` where `id` ='" . $id . "' limit 1 ";

        $r = Pdo_an::db_fetch_row($sql);
        if ($r->tmdb_id)
        {
            return $r->tmdb_id;
        }
        $imdb_id  = $r->movie_id;
        $tmdb_id  = self::get_tmdbid_from_imdbid($imdb_id);

        return $tmdb_id;
    }
    public static function update_tmdb_id($tmdb_id,$imdb_id)
    {
        $sql = "UPDATE `data_movie_imdb` SET `tmdb_id` = '" . $tmdb_id . "'  WHERE `data_movie_imdb`.`movie_id`=" . $imdb_id;
        Pdo_an::db_query($sql);

        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';

        $mid = self::get_id_from_imdbid($imdb_id);
        Import::create_commit('', 'update', 'data_movie_imdb', array('id' => $mid), 'movie_update',6);

    }

    public static function get_tmdbid_from_imdbid($id)
    {

        $final_value = sprintf('%07d', $id);
        $url = "https://api.themoviedb.org/3/find/tt" . $final_value . "?api_key=" . self::$api_key . "&language=en-US&external_source=imdb_id";

        $result = GETCURL::getCurlCookie($url);

        if ($result) {
            $result = json_decode($result);
            ///var_dump($result);
            if ($result->movie_results) {
                $data = $result->movie_results[0];
            }
            if (!$data) $data = $result->tv_results[0];
            if (!$data) $data = $result->tv_episode_results[0];
            if (!$data) $data = $result->tv_season_results[0];
            if ($data) $tmdb_id = $data->id;

        }
        return $tmdb_id;
    }




    public static function get_id_from_tmdbid($id,$type='Movie')
    {
        $id = intval($id);

        $sql = "SELECT id FROM `data_movie_imdb` where `tmdb_id` ='" . $id . "' and `type`='".$type."' limit 1 ";

        $r = Pdo_an::db_fetch_row($sql);
        return $r->id;

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

    public static function get_column_from_imdb_id($id,$column)
    {
        $id = intval($id);

        $sql = "SELECT {$column} FROM `data_movie_imdb` where movie_id ='" . $id . "' limit 1 ";

        $r = Pdo_an::db_fetch_row($sql);

        return $r->{$column};
    }


    public static function get_imdb_id_from_rwt_id($rwt_id,$fast = '')
    {
        $rwt_id = intval($rwt_id);

        global $table_prefix;

        ////get pos type

        $sql = "SELECT  post_type  FROM " . $table_prefix . "posts WHERE ID ={$rwt_id} ";
        $result_post  =Pdo_wp::db_fetch_row($sql);
        $post_type =$result_post->post_type;

        $imdb_type_array=array('tvseries'=>'TVSeries','movie'=>'Movie');

        $sql = "SELECT movie_id FROM `data_movie_imdb` where rwt_id ='" . $rwt_id . "' and type ='".$imdb_type_array[$post_type]."' limit 1 ";

        $r = Pdo_an::db_fetch_row($sql);

            if ($r->movie_id) {
                $movie_id = $r->movie_id;
            }
        if ($movie_id) {
            return $movie_id;
        }
        else if ($fast)
        {
            return ;
        }

        ///get tmdb_id
        $sql = "SELECT  meta_value FROM " . $table_prefix . "postmeta WHERE post_id ={$rwt_id} and `meta_key` = '_wpmoly_movie_tmdb_id' ";
        $result_meta  =Pdo_wp::db_fetch_row($sql);
        $movie_tmdb_id =$result_meta->meta_value;

        ///////movie type

        if ($movie_tmdb_id) {
            $sql = "SELECT * FROM data_movie_imdb  where tmdb_id=" . $movie_tmdb_id. " and type ='".$imdb_type_array[$post_type]."' limit 1 ";

            $r=Pdo_an::db_fetch_row($sql);
            $movie_id = $r->movie_id;

            if ($movie_id) {

                $sql = "UPDATE `data_movie_imdb` SET  `rwt_id` = '" . $rwt_id . "' WHERE `data_movie_imdb`.`movie_id`=" . $movie_id;
                Pdo_an::db_query($sql);
                return $movie_id;
            }
            $type_request = $post_type;
            if ($post_type == 'tvseries') {
                $type_request = 'tv';
            }


            $imdb_id = self::get_imdbid_from_tmdb($movie_tmdb_id,$type_request);
            //var_dump($result);

                if ($imdb_id) {
                     $sql = "UPDATE `data_movie_imdb` SET `tmdb_id` = '" . $movie_tmdb_id . "', `rwt_id` = '" . $rwt_id . "' WHERE `data_movie_imdb`.`movie_id`=" . $imdb_id;
                    Pdo_an::db_query($sql);
                    $movie_id = $imdb_id;
                }
                else
                {
                   /// $result =self::get_tmdb_data_movie($movie_tmdb_id,$type_request);
                }

        }

        return $movie_id;
    }
    public function find_imdbid_from_tmdbid($movie_tmdb_id,$post_type,$name,$original_name){
        ///find from imdb name

        if ($post_type=='movie') {
            $imdb_id = self::get_imdbid_from_tmdb($movie_tmdb_id, $post_type);

            if ($imdb_id) {
                echo 'get_imdbid_from_tmdb finded '.$imdb_id.PHP_EOL;
                return $imdb_id;
            }
        }


        $imdb_type_array=array('tvseries'=>'TVSeries','movie'=>'Movie');

        if (!$name)
        {
            $result =self::get_tmdb_data_movie($movie_tmdb_id,$post_type);
            $name = $result['name'];
            $original_name = $result['original_name'];
        }

        if ($name || $original_name)
        {

            $sql = "SELECT * FROM data_movie_imdb  where (title=?  OR title=? ) and type ='".$imdb_type_array[$post_type]."' limit 1 ";
            //echo $sql.PHP_EOL;

            $rows=Pdo_an::db_results_array($sql,array($name,$original_name));
            if ($rows)
            {
                foreach ($rows as $r)
                {
                    $imdb_id = $r['movie_id'];
                    if ($imdb_id)
                    {
                        //echo $imdb_id.' ';
                        ////get tmdbid

                        $tmdb_id =  self::get_tmdbid_from_imdbid($imdb_id);

                        if ($tmdb_id == $movie_tmdb_id)
                        {
                            $sql = "UPDATE `data_movie_imdb` SET `tmdb_id` = '" . $movie_tmdb_id . "'  WHERE `data_movie_imdb`.`movie_id`=" . $imdb_id;

                            Pdo_an::db_query($sql);

                            echo 'selected from db finded '.$imdb_id.PHP_EOL;
                            return $imdb_id;
                        }

                    }

                }
            }

        }

    }


    public static function check_tmbd_movies($movie_name)
    {

        $reg_v = '#\(([0-9 ]+)\)#';

        if (preg_match($reg_v, $movie_name, $match)) {
            $year = $match[1];
            $movie_name = trim(str_replace($match[0], '', $movie_name));

        }

        return self::check_tmbd_movies_years($movie_name, $year);
    }

    public static function check_enable_tmdb_movie($tmdb_id,$type)
    {
        global $table_prefix;
        $movie_id = '';
        $sql = "SELECT post_id  FROM `" . $table_prefix . "postmeta`  WHERE meta_key='_wpmoly_movie_tmdb_id' and meta_value = '" . $tmdb_id . "'";

        $row = Pdo_wp::db_results_array($sql);
        foreach ($row as $r )
        {
            $movie_id = $r['post_id'];

            $sqlpost = "SELECT ID  FROM `" . $table_prefix . "posts`  WHERE ID={$movie_id} and `post_type` = '" . $type . "' and  `post_status` = 'publish' ";
            $rowpost = Pdo_wp::db_results_array($sqlpost);
            if (!$rowpost)
            {
                $movie_id='';
            }
        }
        return $movie_id;

    }

    public static function wpml_save($array, $post_type = 'movie', $rwt_id = 0)
    {
        return;

    }

    public static function get_imdbid_from_tmdb($tmdb_id, $type = 'movie')
    {
        $url = 'https://api.themoviedb.org/3/' . $type . '/' . $tmdb_id . '/external_ids?api_key=' . self::$api_key . '&language=en-US<br>';
        //echo $url;

        $result = GETCURL::getCurlCookie($url);
        if ($result) {
            $result = json_decode($result, 1);
            $imdb_id = $result['imdb_id'];

            if ($imdb_id) {
                $imdb_id = substr($imdb_id, 2);
                $imdb_id = intval($imdb_id);
            }
        }
        return $imdb_id;
    }

    public static function get_tmdb_data_movie($tmdb_id, $type = 'movie')
    {
        $url = 'https://api.themoviedb.org/3/' . $type . '/' . $tmdb_id . '?api_key=' . self::$api_key . '&language=en-US<br>';
         //echo $url;

        $result = GETCURL::getCurlCookie($url);
        if ($result) {
            $result = json_decode($result, 1);
        }
        return $result;
    }


public static function check_tmbd_movies_years($movie_name, $year = '')
    {

        if (!$year) return;

        //echo $movie_name.' '.$year.' <br>'.PHP_EOL;
        /////get data from tmdb

        $movie_name_serch = urlencode($movie_name);

        $url = 'https://api.themoviedb.org/3/search/movie?api_key=' . self::$api_key . '&page=1&query=' . $movie_name_serch;
        //echo $url;
        $result = GETCURL::getCurlCookie($url);
        if ($result) {
            $result = json_decode($result, 1);
        }

        if ($result['results']) {
            foreach ($result['results'] as $index => $array) {
                //  var_dump($array);

                $tmdb_id = $array['id'];
                $tmdb_title = $array['title'];

                $tmdb_release_date = $array['release_date'];

                /// echo $tmdb_release_date.' '.$year.' ';
                if ($year && strstr($tmdb_release_date, $year) && self::replace_movie_text($tmdb_title) ==self::replace_movie_text($movie_name) && $array['overview']) {

                    ////check enable movie
                    $movie_id = self::check_enable_tmdb_movie($tmdb_id,'movie');
                    if ( $movie_id) return $movie_id;

                    if (!$movie_id) {
                        ///add movie
                        $movie_id = self::wpml_save($array);

                        return $movie_id;

                    }

                    //echo $movie_name.' =>' .$tmdb_title.'<br>'.PHP_EOL;
                    ///import movies


                    break;
                }
            }
        }
        //   var_dump($result);

    }

public static function replace_movie_text($m,$allYears = '')
    {

        if ($allYears)
        {
            $y = '#(\([0-9]+\))#';
            $m = preg_replace($y, '', $m);
        }
        else {
            $year = date('Y', time());
            $y = '#(\(' . $year . '\))#';
            $m = preg_replace($y, '', $m);

            $y = '#(' . $year . ')#';
            $m = preg_replace($y, '', $m);
            $y = '#(' . ($year - 1) . ')#';
            $m = preg_replace($y, '', $m);
            $y = '#(' . ($year - 2) . ')#';
            $m = preg_replace($y, '', $m);
        }

        $m = trim($m);
        $m = strtolower($m);

        $m = str_replace('&apos;', '', $m);
        $m = str_replace('&amp;', 'and', $m);
        $m = str_replace('&', 'and', $m);
        $m = preg_replace('/[^a-z\d]/ui', '', $m);
        $m = str_replace(' ', '', $m);

        $m = str_replace(':', '', $m);
        $m = str_replace(',', '', $m);


        return $m;
    }



public static function check_tmdb_actors_in_movie($mid)
{

    $sql = "SELECT id  FROM `data_movies_tmdb_actors` WHERE `status` = 4 and `rwt_id` =".$mid;
    $r = Pdo_an::db_fetch_row($sql);

    return $r;

}

public static function addto_db_imdb($movie_id, $array_movie, $rwt_id = 0, $tmdb_id = 0,$log_type='')
{

global $debug;


    $reg_v = '#([0-9]{4})#';
    if (preg_match($reg_v, $array_movie['datePublished'], $mach)) {
        $year = $mach[1];
        $relise = $array_movie['datePublished'];

        unset($array_movie['datePublished']);

    }

       if (isset($array_movie['year'])){
           $year =   $array_movie['year'];
        }


    $type = '';
    if (isset($array_movie['type'])) {
        $type = $array_movie['type'];
        unset($array_movie['type']);
    }

    $country = '';
    if (isset($array_movie['country'])) {
        $country = $array_movie['country'];
        unset($array_movie['country']);
    }
    $language = '';
    if (isset($array_movie['language'])) {
        $language = $array_movie['language'];
        unset($array_movie['language']);
    }
    $genre = '';
    if (isset($array_movie['genre'])) {
        $genre = implode(',', $array_movie['genre']);
        unset($array_movie['genre']);
    }
    if (!$genre) $genre = '';

    $description = '';
    if (isset($array_movie['description'])) {
        $description = $array_movie['description'];
        unset($array_movie['description']);
    }
    $keywords = '';
    if (isset($array_movie['keywords'])) {
        $keywords = $array_movie['keywords'];
        unset($array_movie['keywords']);
    }
    $production = '';
    if (isset($array_movie['production'])) {
        $production = json_encode($array_movie['production']);
        unset($array_movie['production']);
    }
    $actors = '';
    if (isset($array_movie['actors'])) {
        $actors_data = $array_movie['actors'];
        //$actors = json_encode($array_movie['actors']);
        unset($array_movie['actors']);

    }

    //var_dump($actors_data);

    $producers = '';
    if (isset($array_movie['producers'])) {
        $producers = json_encode($array_movie['producers']);
        unset($array_movie['producers']);
    }
    $director = '';
    if (isset($array_movie['director'])) {
        $director = $array_movie['director'];
        unset($array_movie['director']);
    }
    if (isset($array_movie['writer'])) {
        $writer = $array_movie['writer'];
        unset($array_movie['writer']);
    }

    $cast_director = '';
    if (isset($array_movie['cast_director'])) {
        $cast_director = $array_movie['cast_director'];
        unset($array_movie['cast_director']);
    }
    $box_usa = '';
    if (isset($array_movie['box_usa'])) {
        $box_usa = $array_movie['box_usa'];
        unset($array_movie['box_usa']);
    }
    $box_world = '';
    if (isset($array_movie['box_world'])) {
        $box_world = $array_movie['box_world'];
        unset($array_movie['box_world']);
    }

    if ($box_usa && $box_usa>$box_world)
    {
        $box_world=$box_usa;
    }


    $title = '';
    if (isset($array_movie['title'])) {
        $title = $array_movie['title'];
        unset($array_movie['title']);
    }

    $Rating = '';
    if (isset($array_movie['Rating'])) {
        $Rating = $array_movie['Rating'];
        unset($array_movie['Rating']);
    }
    $contentRating = '';
    if (isset($array_movie['contentRating'])) {
        $contentRating = $array_movie['contentRating'];
        unset($array_movie['contentRating']);
    }
    $runtime = '';
    if (isset($array_movie['runtime'])) {
        $runtime = $array_movie['runtime'];
        unset($array_movie['runtime']);
    }
    $array_movie_actor_data = $array_movie['actor_data'];
    unset($array_movie['actor_data']);

    if (isset($array_movie['productionBudget'])) {
        $productionBudget = $array_movie['productionBudget'];
        unset($array_movie['productionBudget']);
    }
    unset($array_movie['datePublished']);

    if (isset($array_movie['actor_pos'])) {
        unset($array_movie['actor_pos']);
    }

    //$writer
    $array_string = json_encode($array_movie);
    $post_name='';



    $array_request = array($movie_id, $rwt_id, $tmdb_id, $title,$post_name, $type, $genre, $relise, $year, $country, $language,
        $production,
        '', '', '', '', '', $box_usa, $box_world, $productionBudget, $keywords, $description, $array_string, $contentRating,
        $Rating, time(), $runtime);

    ///  var_dump($array_request);

    $result_imdb = self::check_imdb_id($movie_id);



    !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';


    $table_access = Import::get_table_access('data_movie_imdb');



   if (!$result_imdb)
    {

        $cq="SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'data_movie_imdb'";
        $rc  =Pdo_an::db_fetch_row($cq);
        $cnt_tble =  $rc->cnt-1;
        $cnt_array = count($array_request);
        if ($cnt_tble>$cnt_array)
        {
            $rescnt = $cnt_tble-$cnt_array;

            for ($d=0;$d<$rescnt;$d++)
            {
                $array_request[]  =0;
            }
        }


        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        $table_access = Import::get_table_access('data_movie_imdb');



        if ( $table_access['export']==2 )
        {
            ///get remote id

            $array = array('table'=>'data_movie_imdb','column'=>'id','request'=>array('movie_id'=>$movie_id));

            $id_array = Import::get_remote_id($array);
            $mid = $id_array['id'];

            if ($mid)
            {

                $sql= "INSERT INTO `data_movie_imdb` VALUES ('".$mid."' ";
                foreach ($array_request as $val)
                {
                    $sql.= ",? ";
                }
                $sql.= ")";

                 Pdo_an::db_results_array($sql,$array_request);

            }


        }
        else
        {
            $sql= "INSERT INTO `data_movie_imdb` VALUES (NULL ";
            foreach ($array_request as $val)
            {
                $sql.= ",? ";
            }
            $sql.= ")";
            Pdo_an::db_results_array($sql,$array_request);
            $mid = Pdo_an::last_id();
        }
        if ($debug)
        {
            echo  $sql;
            var_dump($array_request);
        }

        if ($mid)
        {

            $comment =$mid.' '.$title.' ('.$type.') added';
            self::add_log('',$movie_id,'add movies',$comment,1,$log_type);
        }
        else
        {
            self::add_log('',$movie_id,'add movies','error added',2,$log_type);
        }


    }


    else {
        ///update
        $array_request = array( $title,  $type, $genre, $relise, $year, $country, $language, $production, $box_usa, $box_world, $productionBudget, $keywords, $description, $array_string, $contentRating, $Rating, time(), $runtime,$movie_id);

        $sql ="UPDATE `data_movie_imdb` SET 
`title` =?, `type`=?, `genre`=?, `release`=?, `year`=?, `country`=?, `language`=?, `production`=?, `box_usa`=?, `box_world`=?, 
                             `productionBudget`=?,
                             `keywords`=?,
                             `description`=?, `data`=?, `contentrating`=?, `rating`=?, `add_time`=?, `runtime`=? 
WHERE `data_movie_imdb`.`movie_id` = ? ";


        Pdo_an::db_results_array($sql,$array_request);




        $comment =$title.' ('.$type.') updated';
        self::add_log('',$movie_id,'update movies',$comment,1,$log_type);
        $mid = self::get_id_from_imdbid($movie_id);
    }

    ////add empty actors

    //$movie_id

    if (!$mid)
    {
        return 0;
    }

    //$array_update = array('k'=>'um','id'=>$mid);
    !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
    $commit_id = Import::create_commit('','update','data_movie_imdb',array('id'=>$mid),'movie_add',4);


    $actor_types = array('s' => 1, 'm' => 2, 'e' => 3);
    $director_types = array('director' => 1, 'writer' => 2, 'cast_director' => 3,'producer'=>4);

    $actor_pos = $array_movie['actor_pos'];

    $tmdb_actors = self::check_tmdb_actors_in_movie($mid);


    $commit_id='';





//var_dump($actors_data);
        if (!$tmdb_actors) {

            ////get actors meta current
          $array_actors =  self::get_current_meta('meta_movie_actor',$mid);

         // var_dump($array_actors);


        foreach ($actors_data as $type => $data) {
            foreach ($data as $id => $name) {

                self::add_todb_actor($id);

                //add actor meta
                $pos = $actor_pos[$id];
                ////check for tmdb actors
                self::add_movie_actor($mid, $id, $actor_types[$type], 'meta_movie_actor', $pos);

                if ($array_actors[$id])
                {
                    unset($array_actors[$id]);
                }
            }
        }

        self::remove_actors($array_actors,'meta_movie_actor');

    }


global $force;

    if ($director || $writer || $cast_director || $producers || $force) {
        $array_directors =  self::get_current_meta('meta_movie_director',$mid);

        if ($director) {
            if (strstr($director, ',')) {
                $director_array = explode(',', $director);
            } else {
                $director_array[] = $director;
            }
            foreach ($director_array as $i => $id) {
                self::add_todb_actor($id);

                self::add_movie_actor($mid, $id, $director_types['director'], 'meta_movie_director');

                if ($array_directors[$id])
                {
                    unset($array_directors[$id]);
                }

            }
        }
        if ($writer) {
            if (strstr($writer, ',')) {
                $writer_array = explode(',', $writer);
            } else {
                $writer_array[] = $writer;
            }
            foreach ($writer_array as $i => $id) {
                self::add_todb_actor($id);
                self::add_movie_actor($mid, $id, $director_types['writer'], 'meta_movie_director');
                if ($array_directors[$id])
                {
                    unset($array_directors[$id]);
                }

            }
        }
        if ($cast_director) {
            if (strstr($cast_director, ',')) {
                $cast_director_array = explode(',', $cast_director);
            } else {
                $cast_director_array[] = $cast_director;
            }
            foreach ($cast_director_array as $i => $id) {

                self::add_todb_actor($id);
                self::add_movie_actor($mid, $id, $director_types['cast_director'], 'meta_movie_director');
                if ($array_directors[$id])
                {
                    unset($array_directors[$id]);
                }

            }
        }
        if ($producers) {
            $producers_array = json_decode($producers, 1);

            ///print_r($producers_array);
            foreach ($producers_array as $id => $name) {
                self::add_todb_actor($id);
                self::add_movie_actor($mid, $id, $director_types['producer'], 'meta_movie_director');

                if ($array_directors[$id])
                {
                    unset($array_directors[$id]);
                }
            }
        }


        self::remove_actors($array_directors,'meta_movie_director');
    }




//    !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
//    Import::create_commit($commit_id,'update','meta_movie_director', array('mid'=>$mid),'movie_meta_actor',5);



return 1;
}


public static function  remove_actors($array_actors,$table)
    {
        foreach ($array_actors as $id =>$data)
        {

            if ($data["id"])
            {
                $sql ="DELETE FROM `{$table}` WHERE `id` = ".$data["id"];
                Pdo_an::db_query($sql);

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('','delete',$table,array('mid'=>$data["mid"],'aid'=>$data["aid"],'type'=>$data["type"]),'movie_meta_actor',4);
            }

        }
    }

public static function get_current_meta($table,$id,$aid='aid')
{
    $array_result = [];
    $sql = "SELECT * FROM `{$table}` WHERE mid =".$id;
    $rows = Pdo_an::db_results_array($sql);
    foreach ($rows as $r)
    {

        $array_result[$r[$aid]]=$r;
    }

    return $array_result;

}

public static function add_todb_actor($id,$name='')
{
    if (!self::check_enable_actors($id)) {

        if ($name)
        {
            $sql = "INSERT INTO `data_actors_imdb`  VALUES (?, ?, '', '', '', '', '', '0')";
            Pdo_an::db_results_array($sql, array($id, $name));
        }
        else
        {
            $sql = "INSERT INTO `data_actors_imdb`  VALUES (?, '', '', '', '', '', '', '0')";
            Pdo_an::db_results_array($sql, array($id));
        }

        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'update', 'data_actors_imdb', array('id' => $id),'actor_update',4);
    }
}

public static function add_movie_actor($mid = 0, $id = 0, $type = 0,$table='meta_movie_actor',$pos=0) {

        // Validate values
        if ($mid > 0 && $id > 0) {
            //Get meta

            ///echo $id.' '.$type.' <br>';

            if ($table=='meta_movie_actor')
            {
                $sql = sprintf("SELECT * FROM {$table} WHERE mid=%d AND aid=%d", (int) $mid, (int) $id);
            }
            else
            {
                $sql = sprintf("SELECT * FROM {$table} WHERE mid=%d AND aid=%d AND type=%d", (int) $mid, (int) $id , (int) $type);
            }

            $meta_exist = Pdo_an::db_fetch_row($sql);

            if ($meta_exist)
            {
                if ($meta_exist->type!=$type && $table=='meta_movie_actor')
                {

                    $sql = "UPDATE `{$table}` SET `type` = '{$type}' WHERE `id` = ".intval($meta_exist->id);
                   /// echo $sql.PHP_EOL;
                    Pdo_an::db_query($sql);

                 }
                if ($meta_exist->pos!=$pos && $table=='meta_movie_actor')
                {

                    $sql = "UPDATE `{$table}` SET `pos` = '{$pos}' WHERE `id` = ".intval($meta_exist->id);
                    // echo $sql.PHP_EOL;
                    Pdo_an::db_query($sql);

                }


            } else if (!$meta_exist) {
                //Meta not exist
                //echo 'Meta not exist';

                ///add log



               $sql = sprintf("INSERT INTO {$table} (mid,aid,pos,type) VALUES (%d,%d,%d,%d)", (int) $mid, (int) $id, (int) $pos, (int) $type);

               ///echo $sql.PHP_EOL;
                Pdo_an::db_query($sql);
                $aid = Pdo_an::last_id();

                !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';
                ACTIONLOG::update_actor_log('new_actors',$table,$aid);
                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';

                $array_custom =array('skip'=>['id']);
                Import::create_commit('','update',$table,array('mid'=>$mid,'aid'=>$id,'type'=>$type),'movie_meta_actor',5,$array_custom);

            }

            return true;
        }
        return false;
    }

public static  function check_imdb_id($movie_id, $movie_tmdb_id = '', $rwt_id = '')
    {

        $sql = "SELECT *  FROM `data_movie_imdb`  WHERE `movie_id` = '" . $movie_id . "'";
        $r = Pdo_an::db_fetch_row($sql);


        if ($r->id > 0) {

            if ($movie_tmdb_id && !$r->tmdb_id) {
                $sql = "UPDATE `data_movie_imdb` SET `tmdb_id` = '" . $movie_tmdb_id . "' WHERE `data_movie_imdb`.`movie_id`=" . $movie_id;
                Pdo_an::db_query($sql);

            }
            if ($rwt_id && !$r->rwt_id) {
                $sql = "UPDATE `data_movie_imdb` SET `rwt_id` = '" . $rwt_id . "' WHERE `data_movie_imdb`.`movie_id`=" . $movie_id;
                Pdo_an::db_query($sql);
            }

            return 1;
        }

        return 0;
    }

public static  function check_enable_actors($id)
    {

        $sql = "SELECT id FROM `data_actors_imdb` where  id ='" . $id . "'  limit 1 ";
        $id  = Pdo_an::db_get_data($sql,'id');
        if ($id> 0) {
            return 1;
        }
        return 0;
    }

public static function get_actor_array($stars_data,$type='s',$actors,$actor_data,$array_pos=[])
{

    $i = count($array_pos)+1;
    if (!$i)$i=1;

    foreach ($stars_data as $index=>$val)
    {
        if ($type!='s')
        {
            $val=$val['node'];
        }



        $actor_id = $val['name']['id'];
        $actor_id=   ((int)preg_replace('/[^0-9]/', '',$actor_id));
        $actor_name = $val['name']['nameText']['text'];
        $actor_image=$val['name']['primaryImage']['url'];
        $actor_character=   $val['characters'][0]['name'];
        $actor_episodes =  $val['episodeCredits'];
        $actors[$type][$actor_id]=$actor_name;
        $array_pos[$actor_id]=$i;

        $actor_data[$actor_id]=array('name'=>$actor_name,'image'=>$actor_image,'character'=>$actor_character,'episodes'=>$actor_episodes);
        $i++;
    }
//    echo '------';
//    var_dump($array_pos);
    return array($actors,$actor_data,$array_pos);
}
public static function save_array_meta($array_meta)
{

    return;

    //print_r($array_meta);
//    $movie_id = $array_meta['id'];
//    $movie_id = intval(substr($movie_id,2));
//
//    if ($movie_id)
//    {
//        $sql = "SELECT id FROM `data_movieimdb_metadata` where imdb_id ={$movie_id}";
//        $r  = Pdo_an::db_fetch_row($sql);
//        if ($r)
//        {
//            ///update
//            $id = $r->id;
//            $sql="UPDATE `data_movieimdb_metadata` SET `meta` =? WHERE `meta_movieimdb_metadata`.`id` = {$id}; ";
//            Pdo_an::db_results_array($sql,array(json_encode($array_meta)));
//        }
//        else
//        {
//            //add
//            $sql="INSERT INTO `data_movieimdb_metadata` (`id`, `imdb_id`, `meta`) VALUES (NULL, ?, ?);";
//            Pdo_an::db_results_array($sql,array($movie_id,json_encode($array_meta)));
//        }
//
//    }
}
public  static function get_country_name($id)
{

    $q = "SELECT `country_name` FROM `data_population_country` WHERE `cca2` = '".$id."' limit 1";
    $result  = Pdo_an::db_fetch_row($q);

return $result->country_name;
}
public static function add_log($id='',$imdb_id='',$name='',$comment='',$log_status=0,$log_type='default')
{
    if (!$id && $imdb_id)
    {
        $id= self::get_id_from_imdbid($imdb_id);
    }
    if ($id && !$imdb_id)
    {
        $imdb_id= self::get_imdb_id_from_id($id);
    }


    $sql = "INSERT INTO `movies_log`(`id`, `movie_id`, `rwt_id`, `name`, `comment`, `status`,  `type`, `last_update`) 
VALUES (NULL,?,?,?,?,?,?)";
    Pdo_an::db_results_array($sql,array($imdb_id,$id,$name,$comment,$log_status,$log_type,time()));


}

public static function get_imdb_parse($content,$show_data='',$id,$array_result)
    {




        $pos = '<script type="application/ld+json">';

        $content = substr($content, strpos($content, $pos));


        $pos = "</script>";

        $script = substr($content, 35, strpos($content, $pos) - 35);

      //  echo $script.PHP_EOL;

        $array = json_decode($script, JSON_FORCE_OBJECT);


        $reg = '#([0-9]+)#';
        $url = $array['url'];
        if (preg_match($reg,$url,$mach))
        {
            $url_number = $mach[1];
        }
        $final_value = sprintf('%07d', $id);


            if ($final_value!=$url_number)
            {
                $comment ='false url '.$final_value.' != '.$url_number.' ';
                echo ($comment);

                self::add_log('',$id,'check urls',$comment,2);

                return [];

            }



        $pos = '<script id="__NEXT_DATA__" type="application/json">';

        $content = substr($content, strpos($content, $pos));


        $pos = "</script>";

        $script = substr($content, 60, strpos($content, $pos) - 60);


        $pos = '},"page"';

        $script = substr($script, 0, strpos($script, $pos) ).'}';



       //echo $script.PHP_EOL;

        $array2 = json_decode($script, JSON_FORCE_OBJECT);





        $array_meta=[];

        $array2['pageProps']['requestContext']='';
        $array2['pageProps']['translationContext']='';
        $array2['pageProps']['cmsContext']='';


            $array2detail = $array2['urqlState'];

            if (!$array2detail)
            {
                $array2detail =$array2['pageProps']['urqlState'];
            }
        if (!$array2detail)
        {
            $array2detail =$array2['pageProps']['mainColumnData'];
        }





            foreach ($array2detail as $i =>$v)
            {
                if ($v['data']['title']) {
                    $final_value = 'tt'.sprintf('%07d', $id);
                    //echo $v['data']['title']['id'].' == '.$final_value.' ';


                    if ($v['data']['title']['id']==$final_value)
                    {
                        $data_movies = $v['data']['title'];
                        $array_meta = array_merge($array_meta, $data_movies);
                    }


                }

            }
      if ($array2detail['id']) {
        $final_value = 'tt'.sprintf('%07d', $id);
        //echo $v['data']['title']['id'].' == '.$final_value.' ';


        if ($array2detail['id']==$final_value)
        {
            $array_meta = array_merge($array_meta, $array2detail);
        }

    }


        $country=[];

if($array_meta["countriesOfOrigin"]["countries"])
{
    foreach ($array_meta["countriesOfOrigin"]["countries"] as $country_data)
    {
       $idc  = $country_data['id'];
       $country_name =self::get_country_name($idc);

       if ($country_name && !in_array($country_name,$country))
       {
           $country[] =$country_name;
       }

    }
}


        if (is_array($array_meta))
        {
           /// self::save_array_meta($array_meta);
        }




        $actor=[];
        $actor_data=[];

        $array_temp=[];
        ///$array_result=[];
        $data_movies=$array_meta;
        $data_movies["moreLikeThisTitles"]='';




        $array_result['title'] =$data_movies["titleText"]["text"];/// $array["name"]; ///$data_movies["originalTitleText"]["text"];

        if (!$array_result['title'])
        {
            $array_result['title'] = $array["name"];
        }


//        if (strstr($array_result['imdb_title'], $array_result['title']))
//        {
//            $comment = $array_result['imdb_title'].'=='.$array_result['title'];
//            self::add_log('',$id,'check titles',$comment,1);
//
//        }
//        else
//        {
//
//            $comment = 'Not match '.$array_result['imdb_title'].'!='.$array_result['title'];
//            self::add_log('',$id,'check titles',$comment,2);
//          //  return [];
//        }







                if ($data_movies['runtime']["seconds"])
                {
                    $array_result['runtime']=$data_movies['runtime']["seconds"];
                }

                if ($data_movies['productionBudget']["budget"]["amount"])$array_result['productionBudget']=$data_movies['productionBudget']["budget"]["amount"];
                if ($data_movies['worldwideGross']["total"]["amount"])$array_result['box_world']=$data_movies['worldwideGross']["total"]["amount"];
                if ($data_movies['lifetimeGross']["total"]["amount"])$array_result['box_usa']=$data_movies['lifetimeGross']["total"]["amount"];


                $production  = $data_movies['production']["edges"];
                foreach ($production as $item=>$value)
                {
                    $cid = $value["node"]["company"]["id"];
                    $production_data[$cid] =$value["node"]["company"]["companyText"]["text"];
                }
                if ($production_data) $array_result['production']=$production_data;

                $lang_data_array=[];
                foreach ($data_movies['spokenLanguages']['spokenLanguages'] as $index=>$lang_data)
                {
                    $lang_data_array[]=  $lang_data['text'];
                }
                if ($lang_data_array)$array_result['language']=implode(',',$lang_data_array);


                foreach ($data_movies['countriesOfOrigin']['countries'] as $counry_data)
                {
                    $country_name = $counry_data['text'];
                    if ($country_name && !in_array($country_name,$country))
                    {
                        $country[] =$country_name;
                    }

                }
                if ($country)$array_result['country']=implode(',',$country);
//var_dump($country);

                if ($data_movies['prestigiousAwardSummary']['nominations'])$array_result['award_nominations'] = $data_movies['prestigiousAwardSummary']['nominations'];
                if ($data_movies['prestigiousAwardSummary']['wins'])$array_result['award_wins'] = $data_movies['prestigiousAwardSummary']['wins'];




                $stars_data = $data_movies['principalCast'][0]['credits'];



                $actor=$array_result["actors"];
                $array_pos = [];
                $array_temp = self::get_actor_array($stars_data,'s',$actor,$actor_data,$array_pos);

                $actor = $array_temp[0];
                $actor_data = $array_temp[1];
                $array_pos= $array_temp[2];

                $main_data = $data_movies['cast']['edges'];

                $array_temp = self::get_actor_array($main_data,'m',$actor,$actor_data,$array_pos);

                $actor = $array_temp[0];
                $actor_data = $array_temp[1];

                $array_pos= $array_temp[2];


    $counts = 0;

    $jse='';
    if ($actor['e'])
    {
        $a=0;
        foreach ($actor['e'] as $n=>$v)
        {
            $jse.=', '.$v;
            if ($a>2)break;
            $a++;
        }
        if ($jse)
        {
           $jse = substr($jse,2);
        }

        $jse.= ' != ';
    }



    if ($actor["e"])
    {
        foreach ($actor['s'] as $ai => $a)
        {
            if ($actor["e"][$ai])
            {
                unset( $actor['e'][$ai]);
                $counts++;

            }
        }
        foreach ($actor['m'] as $ai => $a)
        {
            if ($actor["e"][$ai])
            {
                unset( $actor['e'][$ai]);
                $counts++;
            }
        }
    }
    if (count($actor['s'])<3)
    {
        $counts_star=0;
        foreach ($actor['m'] as $ai => $a)
        {
            if ($counts_star>2)break;

            if (!$actor["s"][$ai])
            {
                $actor["s"][$ai]=$a;
                unset( $actor['m'][$ai]);
                $counts_star++;
            }
        }
    }
    global $debug;
    if ($debug)
    {
     //  var_dump($actor);
    }

    $array_count = count($actor['m'])+count($actor['s']);

    if ($counts!=$array_count)
    {
        if ($actor['s'])
        {
            $jse.=implode(', ',$actor['s']);
        }

        $comment = 'actors not match '.$counts.'!='.$array_count.' '.$jse;

        if (strstr($array_result['imdb_title'], $array_result['title']))
        {
            $comment.='  ('.$array_result['imdb_title'].'=='.$array_result['title'].'}';

        }
        else
        {
            $comment.= ' (Not match '.$array_result['imdb_title'].'!='.$array_result['title'].')';
        }

        self::add_log('',$id,'check actors',$comment,2);

        ///check title


       /// return [];

    }


    $array_result["actors"]=$actor;





//    else if ($counts==$array_count)
//    {
//        $comment = $counts.'=='.$array_count;
//        self::add_log('',$id,'check actors',$comment,1);
//
//    }


            $directors_array  = $data_movies['directors'][0]['credits'];
            $directors=[];

            foreach ($directors_array as $item=>$value)
            {
                $cid = $value["name"]["id"];

          ///      echo $cid.PHP_EOL;
                if ($cid)$directors[]= ((int)preg_replace('/[^0-9]/', '',$cid));
            }
            if ($directors)
            {
                $array_result['director'] = implode(',',$directors);
            }


         //   var_dump($data_movies);

        $writer  = $data_movies['writers'][0]['credits'];
        $writers=[];

        foreach ($writer as $item=>$value)
        {
            $cid = $value["name"]["id"];

          /// echo $cid.PHP_EOL;
            if ($cid)$writers[]= ((int)preg_replace('/[^0-9]/', '',$cid));
        }

        if ($writers)
        {
            $array_result['writer'] = implode(',',$writers);
        }

///var_dump( $array_result['writer']);



        $array_result['type'] = $array["@type"];

        $array_result['genre'] = $array["genre"];
        $array_result['datePublished'] = $array["datePublished"];
        $array_result['image'] = $array["image"];
        $array_result['description'] = $array["description"];
        $array_result['keywords'] = $array["keywords"];
        $array_result['contentRating'] = $array["contentRating"];
        $array_result['Rating'] = $array["aggregateRating"]["ratingValue"];
        $array_result['year'] =$data_movies["releaseYear"]["year"];

        $creator = [];


        if (! $array_result['director']) {
            foreach ($array["director"] as $num => $data) {
                $directors[] = ((int)preg_replace('/[^0-9]/', '', $data["url"]));
            }
            $array_result['director'] = implode(',', $directors);
        }



///var_dump($array["creator"]);

        if ($array["creator"]["@type"]) {
            $creator[$array["creator"]["@type"]] = ((int)preg_replace('/[^0-9]/', '', $array["creator"]["url"]));
            $array_result['creator'] = $creator;
        } else {


            foreach ($array["creator"] as $i => $v) {

                $creator[$v["@type"]] .= ((int)preg_replace('/[^0-9]/', '', $v["url"])) . ',';

            }
            $array_result['creator'] = $creator;
        }


    ///var_dump($array_result["actors"]);

    $arctor_stars = [];

    if ($array["actor"])
    {
        foreach ($array["actor"] as $i => $v) {

            $url= ((int)preg_replace('/[^0-9]/', '', $v["url"])) ;
            $name = $v["name"];

            $array_pos[$url]=$i+1;
            $arctor_stars[$url]=$name;
        }

    }

            ////remove  actors


            if ($arctor_stars && $array_result['actors']['s'])
            {
                foreach ($array_result['actors']['s'] as $id=>$name)
                {
                    if (!$arctor_stars[$id])
                    {
                        if (!$array_result['actors']['m'])
                        {
                            $array_result['actors']['m'] = [];
                        }
                        $array_result['actors']['m']=  [$id=>$name]+$array_result['actors']['m'];
                        unset($array_result['actors']['s'][$id]);
                    }
                }
            }
            else   if (!$arctor_stars && $array_result['actors']['s'])
            {
                $key = array_search(4, $array_pos);
                if ($key)
                {
                    $name = $array_result['actors']['s'][$key];
                    $array_result['actors']['m']=  [$key=>$name]+$array_result['actors']['m'];
                    unset($array_result['actors']['s'][$key]);

                }
            }



   /// var_dump($array_result["actors"]);

    if ($show_data)
    {
        //self::save_array_meta($array_meta);
        return json_encode(array($array2,$array_result,$array_meta));
    }
        $array_result['actor_pos']=$array_pos;
        //var_dump($array_pos);
        ///print_r($array_result);

        return ($array_result);

    }

public static function get_content_imdb($id,$showdata='',$enable_actors=1,$from_archive=0)
{
    $final_value = sprintf('%07d', $id);

    $array_result = [];
    $bytesCount='';



    if ($enable_actors) {

        if ($from_archive)
        {
            $bytesCount = file_get_contents(ABSPATH.'analysis/imdb_gzdata/ma'.$id  );
            $result =gzdecode($bytesCount);
        }


        if (($from_archive && !$result) || !$from_archive)
        {

            $url = "https://www.imdb.com/title/tt" . $final_value . '/fullcredits';
            //echo $url;

                global $RWT_PROXY;
                $result = GETCURL::getCurlCookie($url,$RWT_PROXY);


            if (function_exists('gzencode')) {
                $gzdata = gzencode($result, 9);
               file_put_contents(ABSPATH . 'analysis/imdb_gzdata/ma' . $id, $gzdata);
            }
        }

        $array_result = self::get_imdb_parse_actors($result, $array_result);
    }

    $bytesCount='';
    if ($from_archive)
    {
        $bytesCount = file_get_contents(ABSPATH.'analysis/imdb_gzdata/m'.$id  );
        $result1 =gzdecode($bytesCount);
    }

    if (($from_archive && !$result1) || !$from_archive)
    {
        $url = "https://www.imdb.com/title/tt" . $final_value . '/';


            global $RWT_PROXY;
            $result1 = GETCURL::getCurlCookie($url,$RWT_PROXY);


        if ($result1)
        {
            if (function_exists('gzencode'))
            {
                $gzdata =gzencode($result1,9);
                 file_put_contents(ABSPATH.'analysis/imdb_gzdata/m'.$id  ,$gzdata);
            }
        }
    }

    $array_result = self::get_imdb_parse($result1,$showdata,$id,$array_result);




    if ($showdata)
    {
        return $array_result;
    }




    return ($array_result);
}
public static  function get_imdb_parse_actors($content, $array_result)
{


    $reg_v='#\<meta property=\'og\:title\' content=\"([^\"]+)\" \/\>#';
    if (preg_match($reg_v,$content,$mach))
    {
        $imdb_title = $mach[1];
        $imdb_title = substr($imdb_title,0,strpos($imdb_title,' - IMDb'));

        $array_result['imdb_title']=$imdb_title;
    }


    $pos = '<table class="cast_list">';

    if (strpos($content,$pos)) {


        $content = substr($content, strpos($content, $pos));
        $pos = "</table>";

        $table = substr($content, 0, strpos($content, $pos));

        $array_actrs = explode('</tr>', $table);
        $i = 0;

        $actors_all =[];

        foreach ($array_actrs as $v) {

            $reg_v = '#\/nm([0-9]+)[^\>]+\>([^\<]+)#';
            if (preg_match($reg_v, $v, $mach)) {
                $actor_result = trim(str_replace(PHP_EOL, '', $mach[2]));

                $actor_id = (int)$mach[1];
             if (!isset($array_result['actor_pos'][$actor_id])){
                 $array_result['actor_pos'][$actor_id]=$i;
             }


             $actors_all[$actor_id]=$actor_result;

            }
            $i++;
        }

       //var_dump($array_result['actor_pos']);
        $array_result['actors']['e']=$actors_all;



        $table = substr($content, strpos($content, $pos) + 9);
    }

if (strpos($table, 'Produced by')) {


    $pos = "</table>";
    $table_prod = substr($table, 0, strpos($table, $pos));

    $array_pod = explode('</tr>', $table_prod);
    $array_actors_prod = [];
    foreach ($array_pod as $v) {

        $reg_v = '#nm([0-9]+)#';
        if (preg_match($reg_v, $v, $mach)) {
            $number = $mach[1];

            $reg_v = '#\<td class\=\"credit\"\>([^\<]+)#';
            if (preg_match($reg_v, $v, $mach)) {
                $value = trim($mach[1]);

                $array_actors_prod[(int)$number] = $value;

            }
        }
    }

    if ($array_actors_prod) {
        $array_result['producers'] = $array_actors_prod;
    }
}

//var_dump($array_actors_result);
$pos = "Casting By";

if (strpos($content, $pos)) {
    $table = substr($content, strpos($content, $pos));
    $pos = "</table>";
    $table = substr($table, 0, strpos($table, $pos));
    ///echo $table;
    $reg_v = '#nm([0-9]+)#';
    if (preg_match($reg_v, $table, $mach)) {
        $array_result['cast_director'] = (int)$mach[1];
    }
}


return $array_result;
}


private static function search_data_to_object($data,$debug)
{
    $object=[];

    $pos = strpos($data,'<script id="__NEXT_DATA__" type="application/json">');
    if ($pos) {
        $data = substr($data, $pos);
        $pos2 = strpos($data, ',"nextCursor"');
        $data = substr($data, 51, $pos2 - 51);
        $data = $data . '}}}}';
        $data = mb_convert_encoding($data, 'utf-8', mb_detect_encoding($data));
        $object = json_decode($data, 1);

        if ($debug) {

            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    $error = '';
                    break;
                case JSON_ERROR_DEPTH:
                    $error = 'Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $error = 'Underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $error = 'Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $error = 'Syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                case JSON_ERROR_RECURSION:
                    $error = 'One or more recursive references in the value to be encoded';
                    break;
                case JSON_ERROR_INF_OR_NAN:
                    $error = 'One or more NAN or INF values in the value to be encoded';
                    break;
                case JSON_ERROR_UNSUPPORTED_TYPE:
                    $error = 'A value of a type that cannot be encoded was given';
                default:
                    $error = 'Unknown error';
                    break;
            }
            if ($error) {
                echo $error;
            }
        }


    }
    else
    {
        if ($debug) {
            echo 'cant find pos0<br>';
        }
    }
    return $object;
}

public static  function get_data($key,$type,$debug=0)
    {

        $result_data=[];
        $key = urlencode($key);
        $url ='https://www.imdb.com/find?q='.$key.'&s=tt&ttype='.$type;
        $data = GETCURL::getCurlCookie($url);
       //$data=file_get_contents(ABSPATH.'wp-content/uploads/test.html');
        if ($debug)
        {
           // print_r($data);
        }

        $object = self::search_data_to_object($data,$debug);

        if ($object)
        {
            $results = $object["props"]["pageProps"]["titleResults"]["results"];



            foreach ($results as $val)
            {
                $mid = intval( substr($val['id'],2));
              $result_data[$mid] = $val;
            }

            if ($debug)
            {
              //  print_r($result_data);
            }
        }
        else
            {



       // $elements = self::get_dom("//table[@class='findList']",  $data);
        // print_r($data['body']);


        $regv = '#\<tr[^\>]+\>[^\>]+\>[^\"]+\"([^\"]+)\"[^\<]+\<img src\=\"([^\"]+)\"[^\>]+\>[^\>]+\>[^\>]+\>[^\>]+\>[^\>]+\>([^\<]+)(\<\/a>([^\<]+))*#';
        if (preg_match_all($regv,$data,$mach)) {
            foreach ($mach[0] as $index => $data2) {
                $regv_id = '#\/[a-z]+\/[a-z]+([0-9]+)#';
                if (preg_match($regv_id, $mach[1][$index], $mresult)) {
                    $movie_id = $mresult[1];
                    $movie_id = intval($movie_id);
                }
              //  $result_data[$movie_id] = array($mach[1][$index], $mach[2][$index], $mach[3][$index], $mach[5][$index]);

                $poster='';
                if (strstr($mach[2][$index],'._V1_'))
                {
                    $poster = substr($mach[2][$index],0,strpos($mach[2][$index],'._V1_')).'._V1_.jpg';
                    $result_data[$movie_id]['titlePosterImageModel']['url'] =$poster;
                }


                $result_data[$movie_id]['titleNameText']= $mach[3][$index];

                $result_data[$movie_id]['titleReleaseText'] =($mach[5][$index]);
                $result_data[$movie_id]['id']= 'tt'.sprintf('%07d', $movie_id);

            }
            }
            }


//        [118767] => Array
//    (
//        [0] => /title/tt0118767/?ref_=fn_tt_tt_1
//        [1] => https://m.media-amazon.com/images/M/MV5BOTMyMmIyYjUtYzZkZS00NTIxLTk4ODItNWI4ZWUzNDA5MWY4XkEyXkFqcGdeQXVyMTAzMDg2MjMx._V1_UX32_CR0,0,32,44_AL_.jpg
//        [2] => Брат
//          [3] =>  (1997)
//        )
//        $poster =  $data['titlePosterImageModel']['url'];
//        $postersmall = str_replace('_V1_.jpg','_V1_QL75_UY330_CR1,0,220,330_.jpg',$poster);
//        $posterbig = str_replace('_V1_.jpg','_V1_QL75_UY660_CR1,0,440,660_.jpg',$poster);
//
//        $array_not_enable[$movie_id]=array('link'=>'https://www.imdb.com/title/' . $data['id'] ,
//            'title'=> $data['titleNameText'],
//            'poster'=>$postersmall,
//            'posterbig'=>$posterbig,
//            'desc'=>$data['titleReleaseText'],
//            'cast'=>$data['topCredits'],
//            'type'=>$data['imageType']
//        );

            return $result_data;
    }

public static function get_array_compare()
    {
        $sql = "SELECT * FROM `options` where id =3 limit 1";
        $row = Pdo_an::db_fetch_row($sql);
        $val = $row->val;
        $val = str_replace('\\', '', $val);
        $array_compare_0 = explode("',", $val);
        foreach ($array_compare_0 as $val) {
            $val = trim($val);
            // echo $val.' ';
            $result = explode('=>', $val);
            ///var_dump($result);
            $index = trim(str_replace("'", "", $result[0]));
            $value = trim(str_replace("'", "", $result[1]));

            $regv = '#([A-Za-z\,\(\)\- ]{1,})#';

            if (preg_match($regv, $index, $mach)) {
                $index = $mach[1];
            }


            $index = trim($index);

            $array_compare[$index] = $value;
        }
      return $array_compare;

    }

}