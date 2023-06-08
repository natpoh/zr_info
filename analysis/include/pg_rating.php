<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//File service
!class_exists('FileService') ? include ABSPATH . "analysis/include/FileService.php" : '';
//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

//PgRatingCalculate
!class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';


class PgRating
{



    public static function update_pg_rating_cms($imdb_id = '',$debug='')
    {
        $array_result_data = [];




        if ($imdb_id) {
            $sql = "SELECT *  FROM `data_movie_imdb` where movie_id='{$imdb_id}'";
            $rows = Pdo_an::db_results_array($sql);
        } else {


            $rating_update = array( 50=> 86400*30, 40 =>86400*60, 30=> 86400*120 , 20=> 86400*240, 10=> 86400*360, 0=>86400*500);
            $rows =get_weight_list('data_pg_rating','cms_date',"rwt_id",20,$rating_update);

        }


        //echo $sql;

        $count = count($rows);
        $i = 0;
        foreach ($rows as $r) {

            $rows_data =   self::get_movie_data($r['id']);
            $movie_id = $rows_data['movie_id'];
            $title = $rows_data['title'];
            $type = $rows_data['type'];


            if ($debug){echo 'try get cms '.$title.' '.$movie_id.'<br>';}

            $array_commonsense = self::get_content_commonsense($title, $type, $movie_id,$debug);

            $commonsense_link = $array_commonsense['link'];

            $commonsense_data = $array_commonsense['data'];
            if ($commonsense_data) {
                $commonsense_data = json_encode($commonsense_data);
            }
            $commonsense_comment = $array_commonsense['comment'];
            if ($commonsense_comment) {
                $commonsense_comment = json_encode($commonsense_comment);
            }
            self::check_enable_pg($movie_id);

            if ($commonsense_link) {

                $sql = "UPDATE `data_pg_rating` SET `cms_date` = '" . time() . "',
        `cms_link`=?,
        `cms_rating`=?,
        `cms_rating_desk`=? 
          WHERE `data_pg_rating`.`movie_id` = " . $movie_id;
                Pdo_an::db_results_array($sql, array($commonsense_link, $commonsense_data, $commonsense_comment));

                $array_result_data[]=$movie_id;
                if ($debug)echo 'update cms '.$movie_id.'<br>'.PHP_EOL;




                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'data_pg_rating', array('movie_id' => $movie_id), 'pg_rating',9,['skip'=>['id']]);
                $comment= 'updated';
            } else {
                $sql = "UPDATE `data_pg_rating` SET `cms_date` = '" . time() . "'  WHERE `data_pg_rating`.`movie_id` = " . $movie_id;
                Pdo_an::db_query($sql);
                if ($debug)echo 'not new data cms '.$movie_id.'<br>'.PHP_EOL;

                $comment= 'skip';
            }
            !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
            TMDB::add_log($r['id'],$movie_id,'update movies','update_pg_rating_cms '.$comment,1,'admin');


            $i++;
        }

        return $array_result_data;
    }
    public static function get_movie_data($mid)
    {
        $sql = "SELECT `movie_id`, `title`, `type`  FROM `data_movie_imdb` WHERE data_movie_imdb.id ='{$mid}' limit 1";
        $data = Pdo_an::db_results_array($sql);
        return $data[0];

    }

    public static function update_pg_rating_imdb($movie_id = '',$debug='')
    {
        $array_result_data=[];



        if (!$movie_id) {

            $rating_update = array( 50=> 86400*20, 40 =>86400*40, 30=> 86400*90 , 20=> 86400*180, 10=> 86400*240, 0=>86400*360);
            $rows =get_weight_list('data_pg_rating','imdb_date',"rwt_id",20,$rating_update);

            foreach ($rows as $r) {
                $data =   self::get_movie_data($r['id']);

                $array_id[ $data['movie_id']] = $data['movie_title'];
            }
        } else {
            $array_id[$movie_id] = 1;
        }

        $array_pg_commit =[];

        foreach ($array_id as $movie_id=>$movie_title) {

            if ($debug)echo 'try add imdbid '.$movie_id.' ' .$movie_title.'<br>'.PHP_EOL;

            $final_value = sprintf('%07d', $movie_id);
            $url = "https://www.imdb.com/title/tt" . $final_value . '/parentalguide';


                global $RWT_PROXY;
                $result1 = GETCURL::getCurlCookie($url,$RWT_PROXY);


            $array_result = self::get_imdb_parse_pg($result1, []);
            /////add to db
            $mpaa = $array_result['mpaa'];
            if (!$mpaa) $mpaa = '';

            $cert_contries = $array_result['cert_countries'];
            if ($cert_contries) {
                $cert_contries = json_encode($cert_contries);
            } else {
                $cert_contries = '';
            }

            $imdb_data = $array_result['data'];
            if ($imdb_data) {
                $imdb_data = json_encode($imdb_data);
            }
            $imdb_comment = $array_result['comment'];
            if ($imdb_comment) {
                $imdb_comment = json_encode($imdb_comment);
            }
            $contentrating = '';
            if ($array_result['cert_usa']) {
                $contentrating = $array_result['cert_usa'];
            }


            $id = self::check_enable_pg($movie_id);
            if ($id) {
                ///////update
                $array_insert = array($contentrating, $mpaa, time(), $cert_contries, $imdb_data, $imdb_comment,time());

                ////check before update
                ///
                $sql = "SELECT id FROM data_pg_rating where 
                            `pg`=? AND
                            `certification`=? AND
                            `certification_countries`=? AND
                            `imdb_rating`=? AND id = {$id}";
                $rslt = Pdo_an::db_results_array($sql,array($contentrating, $mpaa, $cert_contries, $imdb_data));
                if ($rslt)
                {
                    $comment ='skip';
                    if ($debug)echo 'skip<br>';
                    ///skip
                }
                else
                {
                    ////update data
                    $sql = "UPDATE `data_pg_rating` set 
                            `pg`=?,
                            `certification`=?,
                            `imdb_date`=?, 
                            `certification_countries`=?, 
                            `imdb_rating`=?,
                            `imdb_rating_desc`=?,
                            `last_update`=?
                            where id = {$id}";
                    Pdo_an::db_results_array($sql, $array_insert);
                    if ($debug)echo 'updated imdbid '.$movie_id.'<br>'.PHP_EOL;

                    $array_pg_commit[$movie_id]=1;

                    if ($debug)echo 'updated<br>';

                    $comment ='updated '.json_encode([$contentrating, $mpaa,  $cert_contries, $imdb_data]);
                }

                 $array_result_data[]=$movie_id;

                 }


            !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
            TMDB::add_log('',$movie_id,'update movies','update_pg_rating_imdb '.$comment,1,'admin');

        }
        foreach ($array_pg_commit as $mid=>$enable)
        {



            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            Import::create_commit('', 'update', 'data_pg_rating', array('movie_id' => $mid), 'pg_rating',9,['skip'=>['id']]);

        }


        return $array_result_data;
    }

    public static function check_enable_pg($movie_id = '',$debug='',$mid='')
    {

        if (!$mid)
        {
            $sql = "SELECT id  FROM `data_movie_imdb` WHERE data_movie_imdb.movie_id ='{$movie_id}' limit 1";
            $data = Pdo_an::db_fetch_row($sql);
            $mid = $data->id;
        }
        if ($mid) {
            $sql = "SELECT id  FROM `data_pg_rating` WHERE `rwt_id` = {$mid} limit 1";
            $row = Pdo_an::db_fetch_row($sql);
            if ($row->id) {
                return $row->id;
            } else {
                $sql = "SELECT id , title, movie_id  FROM `data_movie_imdb` WHERE data_movie_imdb.id ='{$mid}' limit 1";
                $data = Pdo_an::db_fetch_row($sql);
                $movie_id = $data->movie_id;
                $title = $data->title;
                $sql = "INSERT INTO `data_pg_rating` (`id`, `movie_id`, `rwt_id`,`movie_title`) VALUES (NULL, ?, ?, ?)";
                Pdo_an::db_results_array($sql, array($movie_id, $mid, $title));

                $sql = "SELECT id  FROM `data_pg_rating` WHERE `movie_id` = {$movie_id} limit 1";
                $row = Pdo_an::db_fetch_row($sql);
                if ($row->id) {
                    return $row->id;
                }
            }
        } else {
            $sql = "SELECT `data_movie_imdb`.*  FROM `data_movie_imdb` LEFT JOIN data_pg_rating ON data_pg_rating.rwt_id=data_movie_imdb.id
        WHERE data_pg_rating.id IS NULL order by data_movie_imdb.id desc limit 50";
            $rows = Pdo_an::db_results_array($sql);
            foreach ($rows as $data) {
                $movie_id = $data->movie_id;
                $title = $data->title;
                $rwt_id = $data->id;
                $sql = "INSERT INTO `data_pg_rating` (`id`, `movie_id`, `rwt_id`,`movie_title`) VALUES (NULL, ?, ?, ?)";
                Pdo_an::db_results_array($sql, array($movie_id, $rwt_id, $title));
                if ($debug)echo 'adedded new row '.$movie_id.' '.$title.'<br>'.PHP_EOL;
            }

        }
    }



    public static function add_pgrating($mid = '',$debug='')
    {

        $array_result = self::update_pg_rating_imdb($mid, $debug);
        $array_result2 = self::update_pg_rating_cms($mid, $debug);

        $result = array_merge($array_result, $array_result2);

        foreach ($result as $imdb_id)
        {
            PgRatingCalculate::CalculateRating($imdb_id);
        }


    }

    public static function update_pgrating($imdb_id = '',$debug='',$mid='')
    {
        if (!$mid && $imdb_id)
        {
            $sql = "SELECT id  FROM `data_movie_imdb` WHERE data_movie_imdb.movie_id ='{$imdb_id}' limit 1";
            $data = Pdo_an::db_fetch_row($sql);
            $mid = $data->id;
        }
        else if (!$imdb_id && $mid)
        {
            $sql = "SELECT movie_id  FROM `data_movie_imdb` WHERE data_movie_imdb.id ='{$mid}' limit 1";
            $data = Pdo_an::db_fetch_row($sql);
            $imdb_id = $data->movie_id;
        }
        if ($imdb_id)
        {
            self::check_enable_pg($imdb_id,$debug,$mid);
            PgRatingCalculate::CalculateRating($imdb_id,$mid,$debug);
        }

        return;
    }
    public static function get_cms_link($id)
    {
        $rows_data =   self::get_movie_data($id);
        $url='';

        $title = $rows_data['title'];
        $type = $rows_data['type'];

        $array_type = array('Movie' => 'movie', 'TVSeries' => 'tv', 'TVEpisode' => 'tv');
        if ($array_type[$type])
        {
            $type_request = '?f%5B0%5D=field_reference_review_ent_prod%253Atype%3Acsm_movie&f%5B1%5D=field_reference_review_ent_prod%3Atype%3Acsm_' . $array_type[$type];

            $url = "https://www.commonsensemedia.org/search/" . rawurlencode($title) . $type_request;

        }

        return $url;
    }
    private static function exlude_cms_data($array)
    {
        $convert_array = self::rating_cms_array(1);

        $result = [];
        foreach ($array as $data)
        {
            $reg_name = '/id=\"content-grid-item-([a-z -_]+)-score\"/';
            if (preg_match($reg_name,$data,$match))
            {
                $name = $match[1];

            }
            if ($name) {

                $reg_cmnt = '/data-text=\"([^\"]+)\"/';
                if (preg_match($reg_cmnt, $data, $match)) {
                    $content = $match[1];
                    $content=  html_entity_decode($content);
                }


                //stars
                $targetWord = 'icon-circle-solid active';
                $wordCount = substr_count($data, $targetWord);

                if ($convert_array[$name])
                {
                    $name =  $convert_array[$name];
                }


                $result['data'][$name] = $wordCount;
                $result['comment'][$name] = $content;
            }
        }


        return $result;

    }
    public static function rating_cms_array($result =0)
    {
      $rating =  array("educational" => 1, "message" => 1, "role_model" => 1, "sex" => 1, "violence" => 1, "language" => 1, "drugs" => 1, "consumerism" => 1,"diverse"=>1,"drinking"=>1);

      $rating_convert =  array("educational" => "educational","diverse-representation"=>"diverse", "positive-messages"=>"message" , "role-models"=>"role_model", "sex" => "sex" , "violence"=>"violence" , "language" => "language","drinking"=>"drinking" , "drugs"=>"drugs" ,"consumerism"=> "consumerism" );

        $rating_type =   array("educational" => 1, "message" => 1, "role_model" => 1, "sex" => -1, "violence" => -1, "language" => -1, "drugs" => -1, "consumerism" => -1,"diverse"=> -1,"drinking"=> -1);


        if (!$result) return $rating;
        if ($result==1) return $rating_convert;
        if ($result==2) return $rating_type;
    }

    public static function get_content_commonsense($title, $type, $movie_id,$debug='')
    {
        $array_total = [];
        $array_type = array('Movie' => 'movie', 'TVSeries' => 'tv', 'TVEpisode' => 'tv');

        $type_request = '';
        if ($array_type[$type]) {
            $type_request = '?f%5B0%5D=field_reference_review_ent_prod%253Atype%3Acsm_movie&f%5B1%5D=field_reference_review_ent_prod%3Atype%3Acsm_' . $array_type[$type];

            // echo $url;
            $url = "https://www.commonsensemedia.org/search/" . rawurlencode($title) . $type_request;

            if ($debug)echo $url;

            $result1 = GETCURL::getCurlCookie($url);
            ///echo $result1;

            //if ($debug)var_dump($result1);

            //get_content_commonsense($title,$type,$movie_id);
            $reg_v = '/\<a href\=\"\/movie-reviews\/([^\"]+)" class\=\"csm-button\"\>Continue reading\<\/a\>/';
            $reg_v2 = '/\<h3 class=\"review-title\"\>[^\<]+\<a href=\"([^\"]+)\"/';


            // $url = urlencode($url);
            if (preg_match_all($reg_v, $result1, $mach)) {
                foreach ($mach[0] as $i) {

                    if (preg_match($reg_v, $i, $mach_result)) {

                        $array_result_url[] = 'https://www.commonsensemedia.org/movie-reviews/' .$mach_result[1];
                    }
                }
            }
            if (preg_match_all($reg_v2, $result1, $mach)) {
                foreach ($mach[0] as $i) {

                    if (preg_match($reg_v2, $i, $mach_result)) {

                        $array_result_url[] = 'https://www.commonsensemedia.org' .$mach_result[1];
                    }
                }
            }


            if ($debug) var_dump($array_result_url);
            $i = 0;
            if (is_array($array_result_url)) {
                foreach ($array_result_url as $url) {
                    $i++;
                    if ($i > 3 || $array_total) {
                        break;
                    }
                    $url_inner =  $url;
                    if ($debug) echo $url_inner.' ';
                    $result2 = GETCURL::getCurlCookie($url_inner);

                   // if ($debug) echo $result2;
                    $array_total=[];

                    $final_value = sprintf('%07d', $movie_id);
                    if (strstr($result2, 'tt' . $final_value)) {
                        $pos = 'review-view-content-grid';

                        $content = substr($result2, strpos($result2, $pos));
                        // echo $content;
                        $pos2 = 'content-grid-item-parents-need-know';

                        $rating = substr($content, 0, strpos($content, $pos2));
                        //if ($debug) echo $rating;

                        $array_data = explode('<div class="content-grid-item',$rating);


                        $array_total = self::exlude_cms_data($array_data);

                        $array_total['link'] = $url_inner;

                       // if ($debug)var_dump($array_data);

                    }
                }
            }
            if ($debug) var_dump($array_total);
            return $array_total;
        }
    }

    private static function get_imdb_parse_pg($content, $array_result)
    {
///echo $content;

        $pos = '<section id="certificates">';

        $content = substr($content, strpos($content, $pos));

        $pos2 = '<section id="advisory-nudity">';

        $certification = substr($content, 0, strpos($content, $pos2));
//echo $certification;


        $array_cert = [];
        $array_data = [];


        $reg_v = '/\>MPAA\<\/td\>[^\>]+\>([^\>]+)\<\/td\>/';

        if (preg_match($reg_v, $certification, $mach_result)) {

            $array_data['mpaa'] = $mach_result[1];
        }


        $reg_v = '/\?certificates\=[^"]+"\>([^:]+):([^<]+)\<\/a\>/';

        if (preg_match_all($reg_v, $certification, $mach)) {
            foreach ($mach[0] as $i) {

                if (preg_match($reg_v, $i, $mach_result)) {

                    $array_cert[$mach_result[1]][] = $mach_result[2];
                }
            }
        }


        $array_section = array('nudity', 'violence', 'profanity', 'alcohol', 'frightening', '</section>');

        foreach ($array_section as $index => $secton_name) {

            $pos = '<section id="advisory-' . $secton_name . '">';
            if (strpos($content, $pos)) {
                $content = substr($content, strpos($content, $pos));

            }

            $secton_name2 = $array_section[$index + 1];
            if ($secton_name2) {
                if ($secton_name2 == '</section>') {
                    $pos2 = $secton_name2;
                } else {
                    $pos2 = '<section id="advisory-' . $secton_name2 . '">';
                }


                $current = substr($content, 0, strpos($content, $pos2));

                $array_data = self::get_pg_section($current, $array_data, $secton_name);


            }

        }
        if ($array_cert) {
            if ($array_cert["United States"]) {
                foreach ($array_cert["United States"] as $mppa_cert) {
                    if ($mppa_cert) {
                        $array_data['cert_usa'] = $mppa_cert;
                    }

                }
            }
        }

        $array_data['cert_countries'] = $array_cert;
        return $array_data;
//var_dump($array_data);

    }

    private static function get_pg_section($content, $array_data, $secton_name)
    {
///echo $content.'<br><br>';


        $reg_v = '/\<button[^+\>]+\>([A-Za-z]+)\<\/button\>[^+\>]+\>([0-9\,]+)\</';
        $array_result = [];

        if (preg_match_all($reg_v, $content, $mach)) {
            foreach ($mach[0] as $i) {

                if (preg_match($reg_v, $i, $mach_result)) {

                    $array_result[$mach_result[1]] = str_replace(',', '', $mach_result[2]);
                }
            }
        }
        ///var_dump($array_result);


        $reg_v = '/\<button[^+\>]+\>([A-Za-z]+)\<\/button\>[^+\>]+\>([0-9\,]+)\</';
        $array_result = [];

        if (preg_match_all($reg_v, $content, $mach)) {
            foreach ($mach[0] as $i) {

                if (preg_match($reg_v, $i, $mach_result)) {

                    $array_result[$mach_result[1]] = str_replace(',', '', $mach_result[2]);
                }
            }
        }
        $reg_v = '/\<li class\=\"ipl-zebra-list__item\"\>([^\<]+)\<div/';
        $array_comment = [];

        if (preg_match_all($reg_v, $content, $mach)) {
            foreach ($mach[0] as $i) {

                if (preg_match($reg_v, $i, $mach_result)) {

                    $array_comment[] = trim($mach_result[1]);
                }
            }
        }

        $array_data['data'][$secton_name] = $array_result;
        $array_data['comment'][$secton_name] = $array_comment;
        return $array_data;

    }

}