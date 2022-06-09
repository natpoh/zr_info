<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

//Calculate data
!class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';
!class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
!class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';


if (!function_exists('get_feed_pro_templ')) {
    include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/lib_feed_pro.php";
}

/////////audience

class RWT_RATING
{
    public function get_rating_data($array, $encode = 1)
    {
        $result_rating = [];

        foreach ($array as $rwt_id => $data) {


            $result_rating[$rwt_id] = $this->get_rating_movie($rwt_id);
        }
        if ($encode) {
            return json_encode($result_rating);
        } else {
            return ($result_rating);
        }

    }
    private function get_movie_type($rwt_id)
   {
       $sql = "SELECT `type` FROM `data_movie_imdb` WHERE `id` = {$rwt_id} limit 1";
       $row = Pdo_an::db_fetch_row($sql);
       $type = $row->type;
       return strtolower($type);
   }
    private function get_rating_movie($rwt_id)
    {
       /// $gender = $this->get_gender_rating_in_movie($rwt_id);

        $gender = $this->gender_and_diversity_rating($rwt_id);

        $family = $this->ajax_pg_rating($rwt_id);
        $total_rwt = $this->rwt_total_rating($rwt_id);

        if ($gender['diversity_data']) {
            $gender['diversity_data'] = json_decode($gender['diversity_data']);
        }
        $type= $this->get_movie_type($rwt_id);


        $array_result = array('type'=>$type,'male' => $gender['male'], 'female' => $gender['female'], 'diversity' => $gender['diversity'], 'diversity_data' => $gender['diversity_data'], 'family' => $family['pgrating'], 'family_data' => $family['pg_data'],
            'lgbt_warning'=>$family['lgbt_warning'],'lgbt_text'=>$family['lgbt_text'],'woke'=>$family['woke'],'woke_text'=>$family['woke_text'],'total_rating'=>$total_rwt);
        return $array_result;
    }
    public function show_rating_script($array, $wait = '')
    {
        $content_result = $this->get_rating_data($array);


        ?>
        <script type="text/javascript">
            <?php if ($wait) { ?>   document.addEventListener('DOMContentLoaded', function () { <?php }  ?>
                var rating = <?php echo($content_result) ?>;
                jQuery.each(rating, function (a, b) {
                    let rating_content = create_rating_content(b,a);
                    if (rating_content) {
                        jQuery('.movie_container[id="' + a + '"]').append(rating_content);
                    }
                });
                <?php if ($wait) { ?> }); <?php }  ?>
        </script>
        <?php

    }
    public function get_gender_rating_in_movie($rid)
    {
        $rid = intval($rid);
        $sql = "SELECT * FROM `cache_rating` WHERE `movie_id` = {$rid} limit 1";
        $row = Pdo_an::db_results_array($sql);
        return $row[0];
    }
    private function save_gender_data($movie_id, $movie_imdb, $diversity, $diversity_data, $male, $female)
    {
        global $table_prefix;
        $movie_id = intval($movie_id);

        $r_data = $this->get_gender_rating_in_movie($movie_id);

        if ($r_data) {
            //update

            ///check
            if ($r_data['diversity'] ==$diversity && $r_data['male'] ==$male && $r_data['female'] ==$female )
            {
                //skip
            }
            else {

                $sql = "UPDATE `cache_rating` SET
                              `diversity` = ?,   `diversity_data` = ?,    `male` = ?,   `female` = ?
                WHERE `movie_id` = {$movie_id}  ";
                Pdo_an::db_results_array($sql, array($diversity, $diversity_data, $male, $female));

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'cache_rating', array('movie_id' => $movie_id), 'cache_rating',9,['skip'=>['id']]);

            }
        }
        else {
            $sql = "INSERT INTO `cache_rating` (`id`, `movie_id`, `imdb_id`, `diversity`, `diversity_data`, `male`, `female`) 
                    VALUES (NULL, ?, ?, ?, ?, ?, ?);";
            Pdo_an::db_results_array($sql, array($movie_id, $movie_imdb, $diversity, $diversity_data, $male, $female));

            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            Import::create_commit('', 'update', 'cache_rating', array('movie_id' => $movie_id), 'cache_rating',9,['skip'=>['id']]);

        }



    }
    public function gender_and_diversity_rating($movie_id, $movie_imdb = '', $update = '')
    {
        //////new api

        if (!$update) {
            $array_db = $this->get_gender_rating_in_movie($movie_id);

            if ($array_db['diversity'] || $array_db['female'])
            {
             return $array_db;
            }
        }

        !class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';

        if (!$movie_imdb) {
            $movie_imdb = TMDB::get_imdb_id_from_id($movie_id);
        }

        ///get male and female

        if ($movie_imdb) {

            $ethnic = (object)["1" => (object)["crowd" => 1], "2" => (object)["ethnic" => 1], "3" => (object)["jew" => 1], "4" => (object)["face" => 1], "5" => (object)["face2" => 1], "6" => (object)["surname" => 1]];

            ///m_f
            ///diversity

            $data_actors = MOVIE_DATA::get_movie_data_from_db($movie_id, '', 0, array("star", "main"), [], "wj_nw", $ethnic);

            $diversity =$data_actors['data'] ['non-White'];
            $diversity_data = $data_actors['default_data'];


            $data_actors = MOVIE_DATA::get_movie_data_from_db($movie_id, '', 0, array("star", "main"), [], "m_f", $ethnic);

           // if ($update) print_r($data_actors);

            $male = $data_actors["data"]["Male"];
            $female = $data_actors["data"]["Female"];

            $diversity_data = json_encode($diversity_data);

            $this->save_gender_data($movie_id, $movie_imdb, $diversity, $diversity_data, $male, $female);

        }

        return array("diversity" => $diversity, "diversity_data" => $diversity_data, "male" => $male, "female" => $female);

    }
    public  function ajax_pg_rating($movie_id)
    {

        $sql = "SELECT `movie_id` FROM `data_movie_imdb` WHERE `id` = {$movie_id} limit 1";
        $row = Pdo_an::db_fetch_row($sql);
        $imdb_id = $row->movie_id;
        $data=[];
        $array_family = $this->get_family_rating_in_movie($imdb_id);



        $data['mpaa']=$array_family['pg'];

        $certification_countries=$array_family['certification_countries'];
        if ($certification_countries)
        {
            $certification_countries = json_decode($certification_countries,1);
            if ($certification_countries['Russia'])
            {
                $data['mpaa_rus']=$certification_countries['Russia'];

                if (count($data['mpaa_rus'])>1)
                {
                    $reversed = array_reverse($data['mpaa_rus']);
                    $data['mpaa_rus'] =$reversed[0];
                }
            }
        }


        $imdb=$array_family['imdb_rating'];
        if ($imdb)
        {
            $imdb = json_decode($imdb,1);
            $imdb_result =  PgRatingCalculate::max_rating($imdb);
            if ($imdb_result)
            {
                $data['imdb']= $imdb_result;
            }
        }

        if ($array_family['cms_rating'])
        {
            $data['cms_rating']=($array_family['cms_rating']);
        }

        if ($array_family['dove_rating'])
        {
            $data['dove_rating']=($array_family['dove_rating']);
        }

        if ($data)
        {
            $data = json_encode($data);
        }



        return array('pgrating'=>$array_family['rwt_pg_result'],'pg_data'=>$data,
        'lgbt_warning'=>$array_family['lgbt_warning'],'lgbt_text'=>str_replace(',',', ',$array_family['lgbt_text']),
        'woke'=>$array_family['woke'],'woke_text'=>str_replace(',',', ',$array_family['woke_text']),
        );

    }
    public function get_family_rating_in_movie($rid)
    {
        $rid = intval($rid);
        $sql = "SELECT * FROM `data_pg_rating` WHERE `movie_id` = {$rid} limit 1";
        $row = Pdo_an::db_results_array($sql);
        return $row[0];
    }
    public function rwt_audience($id, $type = 1, $update = '')
    {
        return PgRatingCalculate::rwt_audience($id, $type, $update);
    }
    public function rwt_total_rating($id)
    {
        $rating =  PgRatingCalculate::rwt_total_rating($id);
///check and update
        $last_update = $rating['last_update'];

        if ($last_update<time() - 86400*7)
        {
            PgRatingCalculate::add_movie_rating($id);
            $rating =  PgRatingCalculate::rwt_total_rating($id);
        }

        return $rating;
    }


}


//$data = new RWT_RATING;
//
//
////$array = $data->family_rating('449933');
//$array = $data->family_rating('466345');
//var_dump($array);

