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

        } else {
            $lastupdate = time() - 86400 * 60;

            /////check imdb
            $sql = "SELECT *  FROM `data_movie_imdb` LEFT JOIN data_pg_rating ON data_pg_rating.movie_id=data_movie_imdb.movie_id
    WHERE  (data_pg_rating.cms_date  < " . $lastupdate . "  OR data_pg_rating.cms_date IS NULL )  and data_pg_rating.cms_result IS NULL ORDER BY `data_pg_rating`.`id` ASC  limit 10";
        }


        //echo $sql;
        $rows = Pdo_an::db_results_array($sql);
        $count = count($rows);
        $i = 0;
        foreach ($rows as $r) {
            $movie_id = $r['movie_id'];
            $title = $r['title'];
            $type = $r['type'];


            if ($debug){echo 'try get cms '.$title.' '.$movie_id.'<br>';}

            $array_commonsense = self::get_content_commonsense($title, $type, $movie_id);

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
            } else {
                $sql = "UPDATE `data_pg_rating` SET `cms_date` = '" . time() . "'  WHERE `data_pg_rating`.`movie_id` = " . $movie_id;
                Pdo_an::db_query($sql);
                if ($debug)echo 'not new data cms '.$movie_id.'<br>'.PHP_EOL;
            }
            $i++;
        }

        return $array_result_data;
    }

    public static function update_pg_rating_imdb($movie_id = '',$debug='')
    {
        $array_result_data=[];

        if (!$movie_id) {
            $last_update = time() - 86400*30 ; ///1 mount

            $sql = "SELECT *  FROM `data_pg_rating` WHERE (imdb_date < '{$last_update}' OR imdb_date is NULL ) and movie_id>0 order by id desc  limit 100";
                     $result = Pdo_an::db_results_array($sql);
            foreach ($result as $r) {
                $array_id[ $r['movie_id']] = $r['movie_title'];
            }
        } else {
            $array_id[$movie_id] = 1;
        }

        foreach ($array_id as $movie_id=>$movie_title) {

            if ($debug)echo 'try add imdbid '.$movie_id.' ' .$movie_title.'<br>'.PHP_EOL;

            $final_value = sprintf('%07d', $movie_id);
            $url = "https://www.imdb.com/title/tt" . $final_value . '/parentalguide';
            $result1 = GETCURL::getCurlCookie($url);
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
                $array_insert = array($contentrating, $mpaa, time(), $cert_contries, $imdb_data, $imdb_comment);

                ////update data
                $sql = "UPDATE `data_pg_rating` set 
                            `pg`=?,
                            `certification`=?,
                            `imdb_date`=?, 
                            `certification_countries`=?, 
                            `imdb_rating`=?,
                            `imdb_rating_desc`=?
                            where id = {$id}";
                Pdo_an::db_results_array($sql, $array_insert);
                if ($debug)echo 'updated imdbid '.$movie_id.'<br>'.PHP_EOL;

                $array_result_data[]=$movie_id;
            }
        }
        return $array_result_data;
    }

    public static function check_enable_pg($movie_id = '',$debug='')
    {

        if ($movie_id) {
            $sql = "SELECT id  FROM `data_pg_rating` WHERE `movie_id` = {$movie_id} limit 1";
            $row = Pdo_an::db_fetch_row($sql);
            if ($row->id) {
                return $row->id;
            } else {
                $sql = "SELECT id , title  FROM `data_movie_imdb` WHERE data_movie_imdb.movie_id ='{$movie_id}' limit 1";
                $data = Pdo_an::db_fetch_row($sql);
                $rwt_id = $data->id;
                $title = $data->title;
                $sql = "INSERT INTO `data_pg_rating` (`id`, `movie_id`, `rwt_id`,`movie_title`) VALUES (NULL, ?, ?, ?)";
                Pdo_an::db_results_array($sql, array($movie_id, $rwt_id, $title));

                $sql = "SELECT id  FROM `data_pg_rating` WHERE `movie_id` = {$movie_id} limit 1";
                $row = Pdo_an::db_fetch_row($sql);
                if ($row->id) {
                    return $row->id;
                }
            }
        } else {
            $sql = "SELECT `data_movie_imdb`.*  FROM `data_movie_imdb` LEFT JOIN data_pg_rating ON data_pg_rating.movie_id=data_movie_imdb.movie_id
        WHERE data_pg_rating.id IS NULL order by data_movie_imdb.id desc limit 100";
            $rows = Pdo_an::db_results($sql);
            foreach ($rows as $data) {
                $rwt_id = $data->id;
                $title = $data->title;
                $movie_id = $data->movie_id;
                $sql = "INSERT INTO `data_pg_rating` (`id`, `movie_id`, `rwt_id`,`movie_title`) VALUES (NULL, ?, ?, ?)";
                Pdo_an::db_results_array($sql, array($movie_id, $rwt_id, $title));
                if ($debug)echo 'adedded new row '.$movie_id.' '.$title.'<br>'.PHP_EOL;
            }

        }
    }

    public static function add_pgrating($imdb_id = '',$debug='')
    {
        self::check_enable_pg($imdb_id,$debug);

        $array_result  = self::update_pg_rating_imdb($imdb_id,$debug);
        $array_result2 = self::update_pg_rating_cms($imdb_id,$debug);
        $result = array_merge($array_result, $array_result2);
        if ($imdb_id)
        {
            PgRatingCalculate::CalculateRating($imdb_id);
        }
        else
        {
            foreach ($result as $imdb_id)
            {
                PgRatingCalculate::CalculateRating($imdb_id);
            }
        }
        return;
    }

    public static function get_content_commonsense($title, $type, $movie_id)
    {
        $array_total = [];
        $array_type = array('Movie' => 'movie', 'TVSeries' => 'tv', 'TVEpisode' => 'tv');

        $type_request = '';
        if ($array_type[$type]) {
            $type_request = '?f%5B0%5D=field_reference_review_ent_prod%253Atype%3Acsm_movie&f%5B1%5D=field_reference_review_ent_prod%3Atype%3Acsm_' . $array_type[$type];
        }
        // echo $url;
        $url = "https://www.commonsensemedia.org/search/" . rawurlencode($title) . $type_request;
        $result1 = GETCURL::getCurlCookie($url);
       ///echo $result1;

        //get_content_commonsense($title,$type,$movie_id);
        $reg_v = '/\<a href\=\"\/movie-reviews\/([^\"]+)" class\=\"csm-button\"\>Continue reading\<\/a\>/';

        // $url = urlencode($url);
        if (preg_match_all($reg_v, $result1, $mach)) {
            foreach ($mach[0] as $i) {

                if (preg_match($reg_v, $i, $mach_result)) {

                    $array_result_url[] = $mach_result[1];
                }
            }
        }
        ///  var_dump($array_result_url);
        $i = 0;
        if (is_array($array_result_url)) {
            foreach ($array_result_url as $url) {
                $i++;
                if ($i > 3 || $array_total) {
                    break;
                }
                $url_inner = 'https://www.commonsensemedia.org/movie-reviews/' . $url;
                //  echo $url_inner.' ';
                $result2 = GETCURL::getCurlCookie($url_inner);
                // echo $result2;
                $final_value = sprintf('%07d', $movie_id);
                if (strstr($result2, 'tt' . $final_value)) {
                    $pos = 'field-collection-container clearfix';

                    $content = substr($result2, strpos($result2, $pos));
                    // echo $content;
                    $pos2 = 'pane-node-field-parents-need-to-know';

                    $rating = substr($content, 0, strpos($content, $pos2));
                    //  echo $rating;


                    $reg_v = '/\id\=\"content-grid-item-([a-z_ ]+)\"\>.+\n.+\n.+\<div class\=\"content-grid-rating content-grid-([0-9]+)(.+\<p\>([^\<]+)\<\/p\>)*/';


                    if (preg_match_all($reg_v, $rating, $mach)) {
                        foreach ($mach[0] as $i) {

                            if (preg_match($reg_v, $i, $mach_result)) {


                                $array_total['data'][$mach_result[1]] = $mach_result[2];
                                $array_total['comment'][$mach_result[1]] = $mach_result[4];
                            }
                        }

                        $array_total['link'] = $url_inner;
                    }


                }
            }
        }
//var_dump($array_total);
        return $array_total;

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