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
    public function get_movie_type($rwt_id)
   {
       $sql = "SELECT `type` FROM `data_movie_imdb` WHERE `id` = {$rwt_id} limit 1";
       $row = Pdo_an::db_fetch_row($sql);
       $type = $row->type;
       return strtolower($type);
   }
    public function get_rating_movie($rwt_id)
    {
       /// $gender = $this->get_gender_rating_in_movie($rwt_id);

        $type= $this->get_movie_type($rwt_id);


        if ($type!='videogame') {
            $gender = $this->gender_and_diversity_rating($rwt_id);
            if ($gender['diversity_data']) {
                $gender['diversity_data'] = json_decode($gender['diversity_data']);
            }
        }


        $family = $this->ajax_pg_rating($rwt_id);
        $total_rwt = $this->rwt_total_rating($rwt_id);
        $indie = $this->box_office($rwt_id);



       $array_result = array('type'=>$type,'male' => $gender['male'], 'female' => $gender['female'], 'diversity' => $gender['diversity'], 'diversity_data' => $gender['diversity_data'], 'family' => $family['pgrating'], 'family_data' => $family['pg_data'],
                'lgbt_warning'=>$family['lgbt_warning'],'lgbt_text'=>$family['lgbt_text'],'woke'=>$family['woke'],'woke_text'=>$family['woke_text'],'total_rating'=>$total_rwt,'indie'=>$indie);




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

        global $debug;
        if ($r_data) {
            //update

            ///check
            if ($r_data['diversity'] ==$diversity && $r_data['male'] ==$male && $r_data['female'] ==$female )
            {
                $sql = "UPDATE `cache_rating` SET `last_update` =?  WHERE `movie_id` = {$movie_id}  ";
                Pdo_an::db_results_array($sql, array(time()));

                if ($debug)echo 'skip '.$sql.'<br>';
            }
            else {
//                echo 'origonal'.PHP_EOL;
//                var_dump($r_data);
//                echo 'modifed'.PHP_EOL;
//                var_dump(array($diversity, $diversity_data, $male, $female));


                $sql = "UPDATE `cache_rating` SET
                              `diversity` = ?,   `diversity_data` = ?,    `male` = ?,   `female` = ? ,`last_update` =?
                WHERE `movie_id` = {$movie_id}  ";
                Pdo_an::db_results_array($sql, array($diversity, $diversity_data, $male, $female,time()));
                if ($debug)echo 'update '.$sql.'<br>';

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'cache_rating', array('movie_id' => $movie_id), 'cache_rating',9,['skip'=>['id']]);
            }
        }
        else {
            $sql = "INSERT INTO `cache_rating` (`id`, `movie_id`, `imdb_id`, `diversity`, `diversity_data`, `male`, `female`,`last_update`) 
                    VALUES (NULL, ?, ?, ?, ?, ?, ?, ? );";
            Pdo_an::db_results_array($sql, array($movie_id, $movie_imdb, $diversity, $diversity_data, $male, $female,time()));

            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            Import::create_commit('', 'update', 'cache_rating', array('movie_id' => $movie_id), 'cache_rating',9,['skip'=>['id']]);

        }



    }
    public function gender_and_diversity_rating($movie_id, $movie_imdb = '', $update = '',$debug='')
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



            $diversity =$data_actors['diversity']['non-White'];
            $diversity_data = $data_actors['default_data'];

            if ($debug) print_r([$diversity,$diversity_data]);

            $data_actors = MOVIE_DATA::get_movie_data_from_db($movie_id, '', 0, array("star", "main"), [], "m_f", $ethnic);

            if ($debug) print_r($data_actors);


            $data_actors =MOVIE_DATA::normalise_array($data_actors);
            if ($debug) print_r($data_actors);
            $male = $data_actors["Male"];
            $female = $data_actors["Female"];

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

        if ($array_family['lgbt_text'] || $array_family['woke_text'])
        {
            !class_exists('Movie_Keywords') ? include ABSPATH . "analysis/include/keywords.php" : '';
            global $keywords_class;
            if (!$keywords_class)
            {

                $keywords_class = new Movie_Keywords;
            }

            if ($array_family['lgbt_text'])            $array_family['lgbt_text'] =   $keywords_class->get_key_link($array_family['lgbt_text']);
            if ($array_family['woke_text'])            $array_family['woke_text'] =   $keywords_class->get_key_link($array_family['woke_text']);
        }


        return array('pgrating'=>$array_family['rwt_pg_result'],'pg_data'=>$data,
        'lgbt_warning'=>$array_family['lgbt_warning'],'lgbt_text'=>$array_family['lgbt_text'] ,
        'woke'=>$array_family['woke'],'woke_text'=>$array_family['woke_text'],
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

    private function get_parent($id)
    {
        $result='';

        $q="SELECT * FROM `data_movie_distributors`  WHERE `id` = ".$id;
        $row = Pdo_an::db_results_array($q);

      if ($row) {
          $wiki = $this->get_wiki_link($row[0]['name'] );

          $result = '<p class="bb_row_after">'.$wiki.'<a target="_blank" href="' . WP_SITEURL . '/search/show_distributor/distributor_' . strtolower($row[0]['id']) . '">(' . $row[0]['name'] . ' subsidiary)</a></p>';
      }
      return $result;
    }
    private function get_wiki_link($name)
    {
        $name_encoded =urlencode($name);
        return '<a class="out_link"  target="_blank" href="https://en.wikipedia.org/w/index.php?search='.$name_encoded.'"></a>';
    }
    public  function get_productions($mid)
    {
        $big_b =0;
        $minor=0;
        $indie=0;
        $data=[];

        $q="SELECT ds.* FROM `data_movie_distributors` as ds  LEFT JOIN meta_movie_distributors as m ON ds.`id` = m.`did`  WHERE (ds.`type` = 1 OR ds.`parent` >0 OR ds.`type` = 2 ) and  m.`mid` = ".$mid;

        $r = Pdo_an::db_results_array($q);
        foreach ($r as $row)
        {

            $parent='';
            if ($row['parent']>0 && $row['type']!=1)
            {
                $parent = $this->get_parent($row['parent']);
            }

            $wiki = $this->get_wiki_link($row['name'] );

            $data[$row['type']][$row['id']]= '<p class="bb_row">'.$wiki.'<a href="' . WP_SITEURL . '/search/show_distributor/distributor_' . strtolower($row['id']) . '">' . $row['name'] . '</a></p>'.$parent;

        }

        return $data;
    }

    public function get_franchise($mid){
        $data=[];
        $first=0;
        $q="SELECT `franchise` FROM `data_movie_indie` WHERE `movie_id`= ".$mid." and `franchise`>0";
        $r= Pdo_an::db_results_array($q);
        $count = count($r);
        foreach ($r as $row)
        {
            $fid = $row['franchise'];

            if ($fid)
            {
                ///check all movies
                $q="SELECT `data_movie_imdb`.`id`  FROM `data_movie_indie`, `data_movie_imdb` WHERE `franchise`= ".$fid." and `data_movie_indie`. `movie_id` = `data_movie_imdb`.`id` order by `data_movie_imdb`.`year` asc limit 1";
                $r= Pdo_an::db_fetch_row($q);

                 if ($mid==$r->id && $mid)
                 {
                     $first=1;
                 }


                $q ="SELECT `name` FROM `data_movie_franchises` WHERE `id` =".$fid;
                $rf =Pdo_an::db_fetch_row($q);
                $name = $rf->name;
                $data[]= '<a target="_blank" href="' . WP_SITEURL . '/search/show_franchise/franchise_' .$fid . '">' .$name. '</a>';
            }

        }
        $count = count($data);
        if ($count)
        {
            $data_string = implode(', ', $data);
            return [$first,$data_string];
        }
        return [0,''];
    }
    private function compare_arrays($firstArray,$secondArray)
    {

        $resultArray = [];

        foreach ($firstArray as $word) {
            if (strpos($word, '*') !== false) {
                $pattern = trim(str_replace('*', '', $word));
                foreach ($secondArray as $index=> $secondWord) {
                    if (strpos($secondWord, $pattern) !== false) {
                        $resultArray[$index] = $secondWord;
                    }
                }
            } else {
                if (in_array(trim($word), $secondArray)) {
                    $index = array_search(trim($word), $secondArray);
                    $resultArray[$index] = $word;
                }
            }
        }
        return $resultArray;
    }

    public function check_keywords($mid)
    {
        $result =[];
        $array_keys = ['lasy_grab'=>'indie_lasy_grab','remake_words'=>'indie_remake_words'];

        !class_exists('Movie_Keywords') ? include ABSPATH . "analysis/include/keywords.php" : '';
        global $keywords_class;
        if (!$keywords_class)
        {
            $keywords_class = new Movie_Keywords;
        }

        $keywords  = $keywords_class->get_keywors_array($mid,1);

        if ($keywords) {
            //get options
            !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';

            foreach ($array_keys as $i => $req) {
                $key_data = OptionData::get_options('', $req);
                if ($key_data)
                {
                    $key_data =  str_replace('\\','',$key_data);
                    $key_ob = explode(',',$key_data);
                }

            $ki= $this->compare_arrays($key_ob, $keywords);

              if ($ki) {
                  $links = $keywords_class->to_key_content($ki,1);
                  $result[$i] = $links;
              }
            }

        }
        return $result;

    }
    public function box_office($mid)
    {
        $mid = intval($mid);
        $sql = "SELECT box_usa,box_world,productionBudget FROM `data_movie_imdb` WHERE `id` = {$mid} and (box_usa >0 OR box_world>0 OR productionBudget>0) limit 1";
        $row = Pdo_an::db_results_array($sql);
        $result = $row[0];
        if ($result['box_world'] && $result['box_usa'] && $result['box_world'] > $result['box_usa'] ){$result['box_intern']=$result['box_world']-$result['box_usa'];}

        $keywords  = $this->check_keywords($mid);
        if ( $keywords)
        {

            $result['recycle']['keywords']=  $keywords;

        }

        [$first,$franchise] = $this->get_franchise($mid);

        if ((!$first && $franchise) || $keywords['lasy_grab'])
        {
            $result['recycle']['enabled']=1;
        }

        if ($franchise || $keywords['remake_words'])
        {
            $result['recycle']['franchise']=$franchise;
        }
        //production
        $production=$this->get_productions($mid);
        if ($production)
        {

            $result['production']=$production;
        }


    return $result;

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

