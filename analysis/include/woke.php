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
        $sql = "select `year`, `title`,`country` from `data_movie_imdb` where  id = " . intval($mid) . " limit 1";

        $rows = Pdo_an::db_results_array($sql);
        return [$rows[0]['title'],$rows[0]['year'],$rows[0]['country']];

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

    private function calculate_custom($imdb_input,$imdb_min,$imb_max=90,$name,$debug)
    {

        $imdb_cur = round(-($imdb_input-$imdb_min)/($imb_max-$imdb_min)*$imb_max,0);

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
        if ($debug){$qs= $this->details('imdb');}
        if ($debug) $this->debug_table($name,  '-('.$imdb_input.'-'.$imdb_min.')/('.$imb_max.'-'.$imdb_min.')*'.$imb_max.' =  '.$imdb_text.$imdb_dop.'% '.$qs);
    return [$imdb,$imdb_text];
    }
    private function details($data,$a='',$b='')
    {
        $array = ['diversity'=>'diversity counts only for countries from the list below, this is done so that there are no false positives for countries such as India or Japan, you can add other white countries to this list.
        for a list of countries, you can see here:   <a href="https://zeitgeistreviews.com/analytics/tab_population" target="_blank">https://zeitgeistreviews.com/analytics/tab_population</a>',
            'woke'=>'woke and lgbt counted based on the number of words, for example if in the settings we put 5, and in the keywords used 5 words it will be 100% if 1 word, it will be 20%',
'boycott'=>'The boycott and boycott are considered to be so:
if there is a boycott and it is Pay To Consume, the rating will be -100
if the boycott is Skip It, the rating will be added to 100
so this rating can be either -100 or 100, 

Audience can also be from -100 to 100, it is calculated by formula:
150 - audience_input * 50;
if the audience is 5, then the result will be 100
if 1, then the result will be -100

if Consume If Free, then the boycott and audiences are not counted
',
            'oweralbs'=>'oweralbs is always positive it counts as input*20
so for value 5 the result will be 5*20=100',
            'rtgap'=>'rt gap is calculated by the formula -value * rtgap coefficient
Example: rt gap =18 then 
result = -18*2 = -36%
or rt gap =60 then 
--60*2 = 120; 120 > 100; result = 100%',
            'imdb'=>'this rating is calculated using the formula
-($imdb_input-$imdb_min)/($rating_max-$imdb_min)*$rating_max

so for example if we have rating_max =90, imdb_min=70 and imdb_input=87
the result would be
-(87-70)/(90-70)*90 = -77%
and for rating 49
-(49-70)/(90-70)*90 = 95%

this rating ranges from -100 to 100',
            'year'=>'
            The date coefficient is calculated from the period of the movie from '.$a.', if the movie is older, the coefficient is lower, if younger, the coefficient is higher.
             To adjust the coefficient, a correction factor of '.$b.' is used. 
The date coefficient is multiplied by the total result of all coefficients.
            Here\'s an example of how the year is calculated
Release date (1995) 1 / ( 2010 - 1959 ) = 0.01961
(1995-1959)*0.01961/2=0.35

Release date  (2015)  1 / ( 2023 - 2010 ) = 0.07692
(2015-2010)*0.07692/2+1=1.19'


        ];



      return  '<details class="details_info" ><summary>?</summary>'.$array[$data].'</details>';
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
///'title'=>$title,'country'=

        if ($array['diversity']) {
            $diversity = round($array['diversity'], 0);


            if ($debug){$qs= $this->details('diversity');}


        if ($debug) $this->debug_table('Diversity', $diversity . '% '.$qs);

        $country_weight = $weihgt_total['country']['diversity_country_list'];
        if ($country_weight)
        {
            $country_weight_array = explode(',',$country_weight);
        }


        if ($array['country']) {

            if ($debug)$this->debug_table('Country list', $country_weight_array,'red');

            $cdata = $array['country'];
            ///check country
            if (strstr($cdata,',')){$countries =explode(',',$cdata);}
            else{
                $countries[]=$cdata;
            }

            if ($debug)
            {
                $this->debug_table('Check country', $countries );

            }

            $intersect = array_intersect($country_weight_array, $countries);

            if (!$intersect)
            {

                if ($debug)
                {
                    $this->debug_table('Country not found ' );
                    $this->debug_table('Diversity set to', '0%' );
                }
                $diversity = 0;

            }
            else
            {
                if ($debug)  $this->debug_table('Country found ', $intersect );


            }

        }
        }


        if ($array['female']) {
            $female = $array['female'];
            if ($debug) $this->debug_table('Female', $female . '% ');
        }
        if ($array['woke'] || $array['lgbt'])
        {
            if ($debug){$qs= $this->details('woke');}
        }


        if ($array['woke']) {



            $woke_input = $array['woke'];

            $woke_percent = round(100 / $word_weight['woke'], 2);

            if ($woke_input > $word_weight['woke']) {
                $woke_input = $word_weight['woke'];

            }
            $woke = round($woke_input * $woke_percent, 0);

            if ($debug) $this->debug_table('Woke', $woke_input . '*' . $woke_percent . '=' . $woke . '% '.$qs);
        }
        if ($array['lgbt']) {
            $lgbt_input = $array['lgbt'];

            $lgbt_percent = round(100 / $word_weight['lgbt'], 2);

            if ($lgbt_input > $word_weight['lgbt']) {
                $lgbt_input = $word_weight['lgbt'];

            }
            $lgbt = round($lgbt_input * $lgbt_percent, 0);

            if ($debug) $this->debug_table('Lgbt', $lgbt_input . '*' . $lgbt_percent . '=' . $lgbt . '% '.$qs);
        }
///audience
        $boycott=0;
        $boycott_text=0;
        if ($array['boycott']) {
            if ($debug){$qs= $this->details('boycott');}
            $boycott_input=$array['boycott'];

            if ($boycott_input==1)
            {
                if ($debug) $this->debug_table('Boycott', 'Pay To Consume <span class="red">-100</span>% '.$qs);

                $boycott=-100;
                $boycott_text='<span class="red">-100</span>';
            }
            else if ($boycott_input==2)
            {
                if ($debug) $this->debug_table('Boycott', 'Skip It 100% '.$qs);

                $boycott=100;
                $boycott_text=$boycott;
            }
            else if ($boycott_input==3)
            {
                if ($debug) $this->debug_table('Boycott', 'Consume If Free; rating is not calculated '.$qs);

                $boycott=0;
            }
            if ($boycott_input==1 || $boycott_input==2) {
                if ($array['audience']) {
                    $audience_input = $array['audience'];

                    $audience = 150 - $audience_input * 50;
                    $audience_text = $audience;
                    if ($audience < 0) {
                        $audience_text = '<span class="red">' . $audience . '</span>';
                    }
                    if ($debug) $this->debug_table('Audience', '150-' . $audience_input . '*50 =' . $audience_text . '% ');

                }
            }


//                1 => array('title' => 'Pay To Consume'
//                2 => array('title' => 'Skip It',
//                3 => array('title' => 'Consume If Free',

        }
        if ($array['oweralbs']) {

            if ($debug){$qs= $this->details('oweralbs');}

            $oweralbs_input = $array['oweralbs'];
            $oweralbs = $oweralbs_input*20;

            if ($debug) $this->debug_table('Overall BS',  $oweralbs_input . '*20 =' . $oweralbs . '% '.$qs);

        }
        if ($array['rtgap']) {
            if ($debug){$qs= $this->details('rtgap');}
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

                $rtgap_dop = '; '.$rtgap_custom.' < -100; result = <span class="red">-100</span>';
            }

            $rtgap_text=$rtgap;

            if ($rtgap<0)
            {
                $rtgap_text = '<span class="red">'.$rtgap.'</span>';
            }
            if ($debug) $this->debug_table('RT Gap',  '-'.$rtgap_input . '*'.$other['rtgap'].' = ' . $rtgap_custom.$rtgap_dop . '% '.$qs);

        }

        if ($array['rtaudience']) {
        list( $rtaudience,$rtaudience_text) =   $this->calculate_custom($array['rtaudience'],$other['rtaudience'],$other['rating_max'],'RT Audience',$debug);
         }

        if ($array['imdb']) {
            list( $imdb,$imdb_text) =   $this->calculate_custom($array['imdb']*10,$other['imdb'],$other['rating_max'],'IMDB',$debug);
        }

        if ($array['kino']) {
            list( $kino,$kino_text) =   $this->calculate_custom($array['kino'],$other['kino'],$other['rating_max'],'Kinopoisk (RUS)',$debug);
        }
        if ($array['douban']) {
            list( $douban,$douban_text) =   $this->calculate_custom($array['douban'],$other['douban'],$other['rating_max'],'Douban (CN)',$debug);
        }


        //year
        $year = $array['year'];
        if ($debug){$qs= $this->details('year',$other['year'],$weihgt['year']);}
        if ( $array['year'] < $other['year_start'] ) {

            $year_data_result=0;
            if ($debug) $this->debug_table('Release date ('. $array['year'].')',  $array['year'].' < '.$other['year_start'].'; result = 0');
        }
        else if ( $array['year'] < $other['year'] ) {

        $year_data =  round(1 / (  $other['year'] - $other['year_start'] ),5);

        $year_data_result =  round(($array['year']- $other['year_start']) *$year_data/$weihgt['year'],2);


            if ($debug) $this->debug_table('Release date ('. $array['year'].')',  ' 1 / ( '.$other['year'].' - ' .$other['year_start'].' ) = '.$year_data.'<br>
                                                ('. $array['year'].'-'. $other['year_start'].')*'.$year_data.'/'.$weihgt['year'].'='.$year_data_result.' '.$qs);


        } else if ( $array['year'] > $other['year'] ) {

            $curtime = date('Y',time());

            $year_data =  round(1 / (  $curtime - $other['year'] ),5);

            $year_data_result =  round(($array['year']- $other['year']) *$year_data/$weihgt['year'],2)+1;


            if ($debug) $this->debug_table('Release date  ('. $array['year'].')',  ' 1 / ( '.$curtime.' - ' .$other['year'].' ) = '.$year_data.'<br>
                                                ('. $array['year'].'-'. $other['year'].')*'.$year_data.'/'.$weihgt['year'].'+1='.$year_data_result.' '.$qs);


        }
        else{

            $year_data_result=1;
            if ($debug) $this->debug_table('Release date',  $array['year'].' = '.$other['year'].'; result = 1 '.$qs);
        }


        $result = round((
                $diversity * $weihgt['diversity'] +
                $female * $weihgt['female'] +
                $woke * $weihgt['woke'] +
                $lgbt * $weihgt['lgbt'] +

                $boycott * $weihgt['boycott']+
                $audience * $weihgt['audience']+

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

            if ($boycott) $result_text .= '+' . $boycott_text . '*' . $weihgt['boycott'].'</span>';
            if ($audience) $result_text .= '+' . $audience_text . '*' . $weihgt['audience'];

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
                 $q = "INSERT INTO `data_woke`(`id`, `mid`,`title`, `country`, `result`, `last_update`) 
                VALUES (NULL,'" . $mid . "',?, ?   '" . $result . "','" . time() . "')";
             }
             else {


                 $q = "INSERT INTO `data_woke`(`id`, `mid`,`title`, `country`, `diversity`, `female`, `woke`, `lgbt`, `audience`, `boycott`, `oweralbs`, `rtgap`, `year`, `rtaudience`, `imdb`, `kino`, `douban`,
                      `woke_result`, `lgbt_result` ,`result`, `last_update`) 
                VALUES (NULL,'" . $mid . "',?, ?, '" . $array['diversity'] . "','" . $array['female'] . "','" . $array['woke'] . "','" . $array['lgbt'] . "','" . $array['audience'] . "',
                '" . $array['boycott'] . "','" . $array['oweralbs'] . "','" . $array['rtgap'] . "','" . $array['year'] . "','" . $array['rtaudience'] . "','" . $array['imdb'] . "','" . $array['kino'] . "','" . $array['douban'] . "',
                '" . $woke . "','" . $lgbt . "','" . $result . "','" . time() . "')";

             }
             $rid  = Pdo_an::db_insert_sql($q,[$array['title'],$array['country']]);

            // echo ' inserted ';

             if ($sync)
             {

                 !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                 Import::create_commit('', 'update', 'data_woke', array('mid' => $mid), 'woke',10,['skip'=>['id']]);

             }

         }
         else
         {



                 if (!$total) {

                     $q = "UPDATE `data_woke` SET `title` =?, `country`=?, `last_update`=? WHERE `mid`= ? ";
                     Pdo_an::db_results_array($q, [$array['title'],$array['country'],time(), $mid]);
                 }
                 else {

                     $q = "UPDATE `data_woke` SET `title` =?, `country`=?,
                       `diversity`=?,
                       `female`=?,`woke`=?,`lgbt`=?,`audience`=?,
                       `boycott`=?,`oweralbs`=?,`rtgap`=?,`year`=?,
                       `rtaudience`=?,`imdb`=?,`kino`=?,`douban`=?,
                       `woke_result`=?,`lgbt_result`=?,`result`=?,
                       `last_update`=? WHERE `mid`= ? ";
                     Pdo_an::db_results_array($q, [$array['title'],$array['country'],$array['diversity'], $array['female'], $array['woke'], $array['lgbt'], $array['audience'], $array['boycott'], $array['oweralbs'],
                         $array['rtgap'], $array['year'], $array['rtaudience'], $array['imdb'], $array['kino'], $array['douban'],
                         $woke, $lgbt, $result, time(), $mid]);

                 }
                 //echo ' updated ';

             if ($update==2)
             {
                 if ( $array['woke_result']!=$woke || $array['lgbt_result']!=$lgbt || $array['result']!=$result )
                 {
                     if ($sync)
                     {
                         !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                         Import::create_commit('', 'update', 'data_woke', array('mid' => $mid), 'woke',10,['skip'=>['id']]);
                     }
                 }
             }
             else
             {
                 if ($sync)
                 {
                     !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                     Import::create_commit('', 'update', 'data_woke', array('mid' => $mid), 'woke',10,['skip'=>['id']]);
                 }
             }
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
        //echo $q;

        $r = Pdo_an::db_results_array($q);
        $count =count($r);
        echo 'total woke not fill ='.$count.'<br>';

        $i=0;
        foreach ($r as $row)
        {



            if (isset($_GET['force'])) {
                $mid = $row['mid'];
                $result = $this->calculate_rating($mid, $row, 2, 0);
                echo $i.'/'.$count.' mid = ' . $mid.' result = '.$result.'%<br>' ;
            }
            else
            {
                $mid = $row['id'];
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
        list($title,$years,$country) = $this->get_year($mid);
        $erating = $this->total_rating($mid);
        $oweralbs = $this->get_oweralbs($audience);




        $array = ['title'=>$title,'country'=>$country,'diversity' => $gender_data['diversity'], 'female' => $gender_data['gender'], 'woke' => $lgbt_count['woke'], 'lgbt' => $lgbt_count['lgbt'], 'audience' => $audience['rating'],
            'boycott' => $audience['vote'], 'oweralbs' => $oweralbs, 'rtgap' => $erating['rotten_tomatoes_gap'], 'year' => $years, 'rtaudience' => $erating['rotten_tomatoes_audience'],
            'imdb' => $erating['imdb'], 'kino' => $erating['kinop_rating'], 'douban' => $erating['douban_rating']];

        foreach ($array as $i=>$v)
        {
            if (!$v){$array[$i]=0;}
        }

        /// diversity	female	woke	lgbt	audience	boycott	oweralbs	rtgap	year	rtaudience	imdb	kino	douban	result	last_update

        $result = $this->calculate_rating($mid, $array, 1, $debug,1);

    return $result;
    }

}