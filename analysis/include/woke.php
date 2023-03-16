<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

class WOKE
{

    private function get_diverstiy($mid)
    {
        $q = "SELECT * FROM `cache_rating` where movie_id = " . $mid;
        $r = Pdo_an::db_results_array($q);

        $diversity = $r[0]['diversity'];
        $male = $r[0]['male'];
        $female = $r[0]['female'];

        if (!$diversity) {
            //check diversity
            !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : "";
            $data = new RWT_RATING;
            $gender = $data->gender_and_diversity_rating($mid);
            $diversity = $gender['diversity'];
            $male = $gender['male'];
            $female = $gender['female'];
        }

        $gender_count = ($female + $male);
        $gender_data = round(100 * $female / ($gender_count), 0);

        return array('diversity' => $diversity, 'gender' => $gender_data);
    }

    private function get_lgbt($mid)
    {
        $q = "SELECT lgbt_warning, woke, lgbt_text,woke_text   FROM `data_pg_rating` where rwt_id = " . $mid;
        $r = Pdo_an::db_results_array($q);
        $lgbt_text = $r[0]['lgbt_text'];
        $woke_text = $r[0]['woke_text'];
        if ($lgbt_text) {
            $lgbt_array = explode(',', $lgbt_text);
            $lgbt_count = count($lgbt_array);
        }
        if ($woke_text) {
            $woke_array = explode(',', $woke_text);
            $woke_count = count($woke_array);
        }
        return array('lgbt' => $lgbt_count, 'woke' => $woke_count);
    }

    private function rwt_audience($id, $type = 1, $update = '')
    {
        !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
        return PgRatingCalculate::rwt_audience($id, $type, $update);
    }

    private function get_rwt_rating($movie_id)
    {
        $sql = "SELECT total_rating FROM `data_movie_rating` where movie_id = " . $movie_id;
        $r = Pdo_an::db_fetch_row($sql);
        return $r;
    }

    private function get_year($mid)
    {
        $sql = "select `year` from `data_movie_imdb` where  id = " . intval($mid) . " limit 1";

        $rows = Pdo_an::db_fetch_row($sql);
        return $rows->year;

    }

    private function total_rating($mid)
    {
        !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
        $rating = PgRatingCalculate::rwt_total_rating($mid);
        return $rating;
    }

    public function debug_table($a = '', $b = '', $color = '')
    {
        //PgRatingCalculate
        !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
        PgRatingCalculate::debug_table($a, $b, $color);

    }

    private function calculate_custom($imdb_input,$imdb_min,$name,$debug)
    {

        $imdb_cur = round(-($imdb_input-$imdb_min)/(100-$imdb_min)*100,0);

        if($imdb_cur>100)
        {
            $imdb=100;
            $imdb_dop = '; '.$imdb_cur.' > 100; result = 100';
        }

        else if($imdb_cur<-100){
            $imdb=-100;
            $imdb_dop = '; '.$imdb_cur.' < -100; result = -100';
        }
        else{
            $imdb=$imdb_cur;
        }
        $imdb_text=$imdb;

        if($imdb<0){
            $imdb_text = '<span class="red">'.$imdb_cur.'</span>';
        }

        if ($debug) $this->debug_table($name,  '-('.$imdb_input.'-'.$imdb_min.')/(100-'.$imdb_min.')*100 =  '.$imdb_text.$imdb_dop.'% ');
    return [$imdb,$imdb_text];
    }

    public function calculate_rating($mid, $array, $update = 0, $debug = 0,$sync=0)
    {


        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
        $weihgt_total= OptionData::get_options('', 'woke_raiting_weight');
        $weihgt_total = unserialize($weihgt_total);

        $word_weight = $weihgt_total['word_weight'];
        $other = $weihgt_total['other_weight'];
        $weihgt = $weihgt_total['woke'];



        if (!$array) {
            $q = "SELECT * FROM `data_woke` WHERE `mid` = " . $mid;
            $r = Pdo_an::db_results_array($q);
            $array = $r[0];
        }
        $total = 0;
        foreach ($array as $i => $val) {
            if ($val) {
                $total += $weihgt[$i];
            }
        }


        if ($debug) self::debug_table('s');
        if ($debug) self::debug_table('ZR Woke Rating');
        if ($debug) $this->debug_table('Rating weight', $weihgt_total, 'red');
        if ($debug) $this->debug_table('Rating array', $array, 'gray');

        if ($array['diversity']) {
            $diversity = round($array['diversity'], 0);
        }
        if ($debug) $this->debug_table('Diversity', $diversity . '%');

        if ($array['female']) {
            $female = $array['female'];
            if ($debug) $this->debug_table('Female', $female . '% ');
        }
        if ($array['woke']) {
            $woke_input = $array['woke'];

            $woke_percent = round(100 / $word_weight['woke'], 2);

            if ($woke_input > $word_weight['woke']) {
                $woke_input = $word_weight['woke'];

            }
            $woke = round($woke_input * $woke_percent, 0);

            if ($debug) $this->debug_table('Woke', $woke_input . '*' . $woke_percent . '=' . $woke . '% ');
        }
        if ($array['lgbt']) {
            $lgbt_input = $array['lgbt'];

            $lgbt_percent = round(100 / $word_weight['lgbt'], 2);

            if ($lgbt_input > $word_weight['lgbt']) {
                $lgbt_input = $word_weight['lgbt'];

            }
            $lgbt = round($lgbt_input * $lgbt_percent, 0);

            if ($debug) $this->debug_table('Lgbt', $lgbt_input . '*' . $lgbt_percent . '=' . $lgbt . '% ');
        }
///audience
        if ($array['audience']) {
            $audience_input = $array['audience'];

            $audience = 100 - $audience_input * 40;
            $audience_text =$audience;
            if ($audience<0)
            {
                $audience_text = '<span class="red">'.$audience.'</span>';
            }
            if ($debug) $this->debug_table('Audience', '100-' . $audience_input . '*40 =' . $audience_text . '% ');


        }
        if ($array['boycott']) {

         $boycott_input=$array['boycott'];
         if ($boycott_input==1)
         {
             $boycott=-100;
             if ($debug) $this->debug_table('Boycott', 'Pay To Consume =<span class="red">-100%</span>');
         }
            if ($boycott_input==2)
            {
                $boycott=100;
                if ($debug) $this->debug_table('Boycott', 'Skip It = 100%');
            }
//                1 => array('title' => 'Pay To Consume'
//                2 => array('title' => 'Skip It',
//                3 => array('title' => 'Consume If Free',

        }
        if ($array['oweralbs']) {
            $oweralbs_input = $array['oweralbs'];
            $oweralbs = $oweralbs_input*20;

            if ($debug) $this->debug_table('Overall BS',  $oweralbs_input . '*20 =' . $oweralbs . '% ');

        }
        if ($array['rtgap']) {

            $rtgap_input = $array['rtgap'];

            $rtgap_custom = -$rtgap_input*$other['rtgap'];
            $rtgap=$rtgap_custom;


            if ($rtgap_custom>100)
            {
                $rtgap=100;

                $rtgap_dop = '; '.$rtgap_custom.' > 100; result = 100';
            }

            if ($rtgap_custom<-100)
            {
                $rtgap=-100;

                $rtgap_dop = '; '.$rtgap_custom.' < -100; result = -100';
            }

            $rtgap_text=$rtgap;

            if ($rtgap<0)
            {
                $rtgap_text = '<span class="red">'.$rtgap.'</span>';
            }

            if ($debug) $this->debug_table('RT Gap',  '-'.$rtgap_input . '*'.$other['rtgap'].' = ' . $rtgap_text.$rtgap_dop . '%');
        }

        if ($array['rtaudience']) {
        list( $rtaudience,$rtaudience_text) =   $this->calculate_custom($array['rtaudience'],$other['rtaudience'],'RT Audience',$debug);
         }

        if ($array['imdb']) {
            list( $imdb,$imdb_text) =   $this->calculate_custom($array['imdb']*10,$other['imdb'],'IMDB',$debug);
        }

        if ($array['kino']) {
            list( $kino,$kino_text) =   $this->calculate_custom($array['kino'],$other['kino'],'Kinopoisk (RUS)',$debug);
        }
        if ($array['douban']) {
            list( $douban,$douban_text) =   $this->calculate_custom($array['douban'],$other['douban'],'Douban (CN)',$debug);
        }


        //year
        $year = $array['year'];

        if ( $array['year'] < $other['year_start'] ) {

            $year_data_result=0;
            if ($debug) $this->debug_table('Release date ('. $array['year'].')',  $array['year'].' < '.$other['year_start'].'; result = 0');
        }
        else if ( $array['year'] < $other['year'] ) {

        $year_data =  round(1 / (  $other['year'] - $other['year_start'] ),5);

        $year_data_result =  round(($array['year']- $other['year_start']) *$year_data/$weihgt['year'],2);


            if ($debug) $this->debug_table('Release date ('. $array['year'].')',  ' 1 / ( '.$other['year'].' - ' .$other['year_start'].' ) = '.$year_data.'<br>
                                                ('. $array['year'].'-'. $other['year_start'].')*'.$year_data.'/'.$weihgt['year'].'='.$year_data_result);


        } else if ( $array['year'] > $other['year'] ) {

            $curtime = date('Y',time());

            $year_data =  round(1 / (  $curtime - $other['year'] ),5);

            $year_data_result =  round(($array['year']- $other['year']) *$year_data/$weihgt['year'],2)+1;


            if ($debug) $this->debug_table('Release date',  ' 1 / ( '.$other['year'].' - ' .$other['year_start'].' ) = '.$year_data.'<br>
                                                ('. $array['year'].'-'. $other['year_start'].')*'.$year_data.'/'.$weihgt['year'].'+1='.$year_data_result);


        }
        else{

            $year_data_result=1;
            if ($debug) $this->debug_table('Release date',  $array['year'].' = '.$other['year'].'; result = 1');
        }





        $result = round((
                $diversity * $weihgt['diversity'] +
                $female * $weihgt['female'] +
                $woke * $weihgt['woke'] +
                $lgbt * $weihgt['lgbt'] +
                $audience * $weihgt['audience']+
                $boycott * $weihgt['boycott']+
                $oweralbs * $weihgt['oweralbs']+
                $rtgap * $weihgt['rtgap'] +
                $rtaudience * $weihgt['rtaudience'] +
                $imdb * $weihgt['imdb']+
                $kino * $weihgt['kino']+
                $douban * $weihgt['douban']



            ) *($year_data_result)/ $total, 0);


        if ($debug) {

            $result_text = '( ';

            if ($diversity) $result_text .= $diversity . '*' . $weihgt['diversity'];
            if ($female) $result_text .= '+' . $female . '*' . $weihgt['female'];
            if ($woke) $result_text .= '+' . $woke . '*' . $weihgt['woke'];
            if ($lgbt) $result_text .= '+' . $lgbt . '*' . $weihgt['lgbt'];
            if ($audience) $result_text .= '+' . $audience_text . '*' . $weihgt['audience'];
            if ($boycott) $result_text .= '<span class="red">' . $boycott . '*' . $weihgt['boycott'].'</span>';

            if ($oweralbs) $result_text .= '+' . $oweralbs . '*' . $weihgt['oweralbs'];
            if ($rtgap) $result_text .= '+' . $rtgap_text . '*' . $weihgt['rtgap'];
            if ($rtaudience) $result_text .= '+' . $rtaudience_text . '*' . $weihgt['rtaudience'];
            if ($imdb) $result_text .= '+' . $imdb_text . '*' . $weihgt['imdb'];
            if ($kino) $result_text .= '+' . $kino_text . '*' . $weihgt['kino'];
            if ($douban) $result_text .= '+' . $douban_text . '*' . $weihgt['douban'];



            $result_text .= ' )*('.$year_data_result.')/' . $total . '=<b>' . $result . '</b>%';
            $this->debug_table('Total', $result_text);

        }

        if ($result<0)$result=0;
        if ($result>100)$result=100;
        if ($debug) $this->debug_table('Result', $result.'%');
        if ($debug)  self::debug_table('e');

        if (!$woke)$woke=0;
        if (!$lgbt)$lgbt=0;

        if ($update)
        {

         $q="SELECT `id` FROM `data_woke` where `mid`  = ".$mid;
         $r =Pdo_an::db_results_array($q);


            /// diversity	female	woke	lgbt	audience	boycott	oweralbs	rtgap  rtaudience 	imdb	kino	douban	year result	last_update

         if (!$r)
         {

             if (!$total)
             {
                 $q = "INSERT INTO `data_woke`(`id`, `mid`, `result`, `last_update`) 
                VALUES (NULL,'" . $mid . "','" . $result . "','" . time() . "')";
             }
             else {


                 $q = "INSERT INTO `data_woke`(`id`, `mid`, `diversity`, `female`, `woke`, `lgbt`, `audience`, `boycott`, `oweralbs`, `rtgap`, `year`, `rtaudience`, `imdb`, `kino`, `douban`,
                      `woke_result`, `lgbt_result` ,`result`, `last_update`) 
                VALUES (NULL,'" . $mid . "','" . $array['diversity'] . "','" . $array['female'] . "','" . $array['woke'] . "','" . $array['lgbt'] . "','" . $array['audience'] . "',
                '" . $array['boycott'] . "','" . $array['oweralbs'] . "','" . $array['rtgap'] . "','" . $array['year'] . "','" . $array['rtaudience'] . "','" . $array['imdb'] . "','" . $array['kino'] . "','" . $array['douban'] . "',
                '" . $woke . "','" . $lgbt . "','" . $result . "','" . time() . "')";

             }
             $rid  = Pdo_an::db_insert_sql($q);

            // echo ' inserted ';
         }
         else
         {
             if (!$total)
             {
                 $q = "UPDATE `data_woke` SET `last_update`=? WHERE `mid`= ? ";
                 Pdo_an::db_results_array($q, [time(), $mid]);
             }
             else {


                 $q = "UPDATE `data_woke` SET `diversity`=?,
                       `female`=?,`woke`=?,`lgbt`=?,`audience`=?,
                       `boycott`=?,`oweralbs`=?,`rtgap`=?,`year`=?,
                       `rtaudience`=?,`imdb`=?,`kino`=?,`douban`=?,
                       `woke_result`=?,`lgbt_result`=?,`result`=?,
                       `last_update`=? WHERE `mid`= ? ";
                 Pdo_an::db_results_array($q, [$array['diversity'], $array['female'], $array['woke'], $array['lgbt'], $array['audience'], $array['boycott'], $array['oweralbs'],
                     $array['rtgap'], $array['year'], $array['rtaudience'], $array['imdb'], $array['kino'], $array['douban'],
                     $woke, $lgbt, $result, time(), $mid]);
             }
             //echo ' updated ';
         }
         if ($sync)
         {
             !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
             Import::create_commit('', 'update', 'data_woke', array('mid' => $mid), 'woke',10,['skip'=>['id']]);


         }



        }

        return  $result;

    }

    public function get_oweralbs($audience)
    {

        $array = array('affirmative', 'god', 'hollywood', 'lgbtq', 'misandry', 'patriotism');

        $count = 0;
        $total = 0;
        foreach ($audience as $type => $val) {
            if (in_array($type, $array) && $val > 0) {
                $count++;
                $total += $val;
            }
        }
        if ($count) {
            $result = round($total / $count, 0);
        }

        return $result;


    }
    public function zr_woke($mid = 0,$debug=0)
    {

        if ($mid) {
            $where = " data_movie_imdb.id = '" . $mid . "' ";
        } else {
            $where = " data_woke.id IS NULL ";

        }


        $q = "SELECT data_movie_imdb.id FROM `data_movie_imdb` LEFT JOIN data_woke ON (data_movie_imdb.id = data_woke.mid) WHERE " . $where . " LIMIT 1000";
        if (isset($_GET['force'])) {
            $q = " SELECT * FROM `data_woke` ORDER BY `mid` ASC ";
        }
        echo $q;

        $r = Pdo_an::db_results_array($q);
        $count =count($r);
        echo 'total woke not fill ='.$count.'<br>';

        $i=0;
        foreach ($r as $row)
        {

            $mid = $row['id'];

            if (isset($_GET['force'])) {

                $result = $this->calculate_rating($mid, $row, 1, 1);
                echo $i.'/'.$count.' mid = ' . $mid.' result = '.$result.'%<br>' ;
            }
            else
            {

                $result = $this->zr_woke_calc($mid,$debug);
                echo $i.'/'.$count.' mid = ' . $mid.' result = '.$result.'%<br>' ;

                if (function_exists('check_cron_time'))
                {
                    if (check_cron_time())
                    {
                        echo 'end time '.check_cron_time();
                        break;
                    }
                }

            }





            $i++;
        }


    }
    public function zr_woke_calc($mid = 0,$debug=0)
    {






        //get diversity, female
        $gender_data = $this->get_diverstiy($mid);

        $lgbt_count = $this->get_lgbt($mid);

        $audience = $this->rwt_audience($mid, 1);

        ///$rtomatoes =  $this->get_rwt_rating($mid);

        $years = $this->get_year($mid);

        $erating = $this->total_rating($mid);

        $oweralbs = $this->get_oweralbs($audience);






        $array = ['diversity' => $gender_data['diversity'], 'female' => $gender_data['gender'], 'woke' => $lgbt_count['woke'], 'lgbt' => $lgbt_count['lgbt'], 'audience' => $audience['rating'],
            'boycott' => $audience['vote'], 'oweralbs' => $oweralbs, 'rtgap' => $erating['rotten_tomatoes_gap'], 'year' => $years, 'rtaudience' => $erating['rotten_tomatoes_audience'],
            'imdb' => $erating['imdb'], 'kino' => $erating['kinop_rating'], 'douban' => $erating['douban_rating']];

        foreach ($array as $i=>$v)
        {
            if (!$v){$array[$i]=0;}
        }

        /// diversity	female	woke	lgbt	audience	boycott	oweralbs	rtgap	year	rtaudience	imdb	kino	douban	result	last_update

        $result = $this->calculate_rating($mid, $array, 1, $debug);

    return $result;
    }

}