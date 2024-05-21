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
        if ($female)
        {
            $gender_count = ($female + $male);
            $gender_data = round(100 * $female / ($gender_count), 0);

        }
        else
        {
            $gender_data =0;
        }



        return array('diversity' => $diversity, 'gender' => $gender_data);
    }

    private function get_lgbt($mid)
    {
        $q = "SELECT lgbt_warning, woke,qtia_warning,qtia_text, lgbt_text,woke_text   FROM `data_pg_rating` where rwt_id = " . $mid;
        $r = Pdo_an::db_results_array($q);
        $lgbt_text = $r[0]['lgbt_text'];
        $qtia_text = $r[0]['qtia_text'];

        $woke_text = $r[0]['woke_text'];
        if ($qtia_text) {
            $qtia_array = explode(',', $qtia_text);
            $qtia_count = count($qtia_array);
        }
        if ($lgbt_text) {
            $lgbt_array = explode(',', $lgbt_text);
            $lgbt_count = count($lgbt_array);
        }
        if ($woke_text) {
            $woke_array = explode(',', $woke_text);
            $woke_count = count($woke_array);
        }
        return array('lgbt' => $lgbt_count, 'qtia' => $qtia_count,'woke' => $woke_count,'lgbt_text'=>$lgbt_text,'woke_text'=>$woke_text,'qtia_text'=>$qtia_text);
    }

    private function rwt_audience($id, $type = 1, $update = '')
    {
        !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
        return PgRatingCalculate::rwt_audience($id, 1, $update);
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
            'woke'=>'woke and lgbt counted based on the number of words, for example if in the settings we put 5, and in the keywords used 5 words it will be 100% if 1 word, it will be 5%',
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

    private function check_limits($words,$array,$result,$debug=0)
    {
        $combient_word_weight = $array;
        if ($debug) $this->debug_table('Limit data',$combient_word_weight ,'red');

        if ($words==0)
        {
            $res_data =  $combient_word_weight['0 word'];
        }
        else if ($words>5)
        {
            $res_data =  $combient_word_weight['5 word'];
        }
        else
        {
            $res_data =  $combient_word_weight[$words.' word'];
        }
        $lm = explode('-',$res_data);


        if ($debug) $this->debug_table('Words limit', 'word count = '.$words.'; result = '.$res_data.'% ' );

        if ($result<$lm[0]){if ($debug) {$this->debug_table(' ', $result.' < '.$lm[0].'; result = '.$lm[0].'% ' );}$result=$lm[0];}
        if ($result>$lm[1]){if ($debug) {$this->debug_table(' ', $result.' > '.$lm[1].'; result = '.$lm[1].'% ' );}$result=$lm[1];}

        return $result;
    }

    public function calculate_rating($mid, $array, $update = 0, $debug = 0,$sync=0)
    {


        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
        $weihgt_total= OptionData::get_options('', 'woke_rating_weight');

        $weihgt_total = json_decode($weihgt_total,1);


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

//        !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
//        TMDB::var_dump_table($array);

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


            $woke_word_weight = $weihgt_total['woke_word_weight'];

            if ($debug) $this->debug_table('Woke data',$woke_word_weight ,'red');
            if ($debug) $this->debug_table('Woke words', $array['woke_text']);
            $woke_input = $array['woke'];
            if ($woke_input>5)
            {
                $woke=100;

                if ($debug) $this->debug_table('Woke', 'word count > 5; woke = 100% ');
            }
            else if ($woke_input)
            {
                $woke =  $woke_word_weight[$woke_input.' word'];
                if ($debug) $this->debug_table('Woke', 'word count = '.$woke_input.'; woke = '.$woke.'% ' );
            }


        }
        if ($array['lgb'] && $array['qtia']) {
            $lgbt_word_weight = $weihgt_total['lgbt_word_weight'];

            if ($debug) $this->debug_table('LGBTQ data',$lgbt_word_weight ,'red');
            if ($debug) $this->debug_table('LGBTQ words', $array['lgb_text'].','.$array['qtia_text']);
            $lgb_input = $array['lgb']+$array['qtia'];
            if ($lgb_input>5)
            {
                $lgb=100;

                if ($debug) $this->debug_table('LGBTQ', 'word count > 5; lgbt = 100% ');
            }
            else if ($lgb_input)
            {
                $lgb =  $lgbt_word_weight[$lgb_input.' word'];
                if ($debug) $this->debug_table('LGBTQ', 'word count = '.$lgb_input.'; lgbtq = '.$lgb.'% ' );
            }

            $lgbt = $lgb;
        }
        else if ($array['lgb']) {
            $lgbt_word_weight = $weihgt_total['lgbt_word_weight'];

            if ($debug) $this->debug_table('LGB data',$lgbt_word_weight ,'red');
            if ($debug) $this->debug_table('LGB words', $array['lgbt_text']);
            $lgb_input = $array['lgb'];
            if ($lgb_input>5)
            {
                $lgb=100;

                if ($debug) $this->debug_table('LGB', 'word count > 5; lgb = 100% ');
            }
            else if ($lgb_input)
            {
                $lgb =  $lgbt_word_weight[$lgb_input.' word'];
                if ($debug) $this->debug_table('LGB', 'word count = '.$lgb_input.'; lgb = '.$lgb.'% ' );
            }
            $lgbt = $lgb;

        }

        else if ($array['qtia']) {
            $lgbt_word_weight = $weihgt_total['lgbt_word_weight'];

            if ($debug) $this->debug_table('QTIA+ data',$lgbt_word_weight ,'red');
            if ($debug) $this->debug_table('QTIA+  words', $array['qtia_text']);
            $qtia_input = $array['qtia'];
            if ($qtia_input>5)
            {
                $qtia=100;

                if ($debug) $this->debug_table('QTIA+', 'word count > 5; qtia = 100% ');
            }
            else if ($qtia_input)
            {
                $qtia =  $lgbt_word_weight[$qtia_input.' word'];
                if ($debug) $this->debug_table('QTIA+', 'word count = '.$qtia_input.'; qtia = '.$qtia.'% ' );
            }

            $lgbt = $qtia;
        }

        if ($lgbt>100)$lgbt = 100;



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

        ///check limits


         $lgbt_input =  $lgb_input+$qtia_input;


        if ($lgbt_input && $woke_input)
        {
            $words = $lgbt_input+$woke_input;
            $result = $this->check_limits($words,$weihgt_total['combient_word_limit'],$result,$debug);
        }
        else  if ($lgbt_input)
        {
           $result = $this->check_limits($lgbt_input,$weihgt_total['lgbt_word_limit'],$result,$debug);
        }
        else  if ($woke_input)
        {
            $result = $this->check_limits($woke_input,$weihgt_total['woke_word_limit'],$result,$debug);
        }
        else
        {
            $result = $this->check_limits(0,$weihgt_total['woke_word_limit'],$result,$debug);
        }

        if ($result<0)$result=0;
        if ($result>100)$result=100;
        if ($debug) $this->debug_table('Result', $result.'%');
        if ($debug)  self::debug_table('e');

        if (!$woke)$woke=0;
        if (!$lgbt)$lgbt=0;

        $lgbt_words=0;

        if ($array['qtia'] || $array['lgbt'])
        {
            $lgbt_words = $array['qtia'] + $array['lgbt'];
        }

        if ($update)
        {

            if ($debug)echo 'try update<br>';


         $q="SELECT `id`, `mid`,`title`, `country`, `diversity`, `female`, `woke`, `lgbt`,`lgb`,`qtia`, `audience`, `boycott`, `oweralbs`, `rtgap`, `year`, `rtaudience`, `imdb`, `kino`, `douban`,
                      `woke_result`, `lgbt_result` ,`result`  FROM `data_woke` where `mid`  = ".$mid;
         $r =Pdo_an::db_results_array($q);


            /// diversity	female	woke	lgbt	audience	boycott	oweralbs	rtgap  rtaudience 	imdb	kino	douban	year result	last_update

            if (!is_numeric($result)  || !$result)$result=0;
            if (is_nan($result)) {$result=0;}


            if (!$array['diversity'])$array['diversity']=0;
            if (!$array['female'])$array['female']=0;
            if (!$array['woke'])$array['woke']=0;
            if (!$array['lgbt'])$array['lgbt']=0;
            if (!$array['lgb'])$array['lgb']=0;
            if (!$array['qtia'])$array['qtia']=0;



            if (!$array['boycott'])$array['boycott']=0;
            if (!$array['oweralbs'])$array['oweralbs']=0;
            if (!$array['rtgap'])$array['rtgap']=0;
            if (!$array['year'])$array['year']=0;
            if (!$array['rtaudience'])$array['rtaudience']=0;
            if (!$array['imdb'])$array['imdb']=0;
            if (!$array['kino'])$array['kino']=0;
            if (!$array['douban'])$array['douban']=0;
            if (!$woke)$woke=0;
            if (!$lgbt)$lgbt=0;

         if (!$r)
         {




                 if ($debug)echo 'insert data<br>';





                 $q = "INSERT INTO `data_woke`(`id`, `mid`,`title`, `country`, `diversity`, `female`, `woke`, `lgbt`,`lgb`,`qtia`, `audience`, `boycott`, `oweralbs`, `rtgap`, `year`, `rtaudience`, `imdb`, `kino`, `douban`,
                      `woke_result`, `lgbt_result` ,`result`, `last_update`) 
                VALUES (NULL,'" . $mid . "',?, ?, '" . $array['diversity'] . "','" . $array['female'] . "','" . $array['woke'] . "',$lgbt_words, '" . $array['lgbt'] . "', '" . $array['qtia'] . "', '" . $array['audience'] . "',
                '" . $array['boycott'] . "','" . $array['oweralbs'] . "','" . $array['rtgap'] . "','" . $array['year'] . "','" . $array['rtaudience'] . "','" . $array['imdb'] . "','" . $array['kino'] . "','" . $array['douban'] . "',
                '" . $woke . "','" . $lgbt . "','" . $result . "','" . time() . "')";


             $rid  = Pdo_an::db_insert_sql($q,[$array['title'],$array['country']]);

             //echo ' inserted '.$q;

             if ($sync)
             {

                 !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                 Import::create_commit('', 'update', 'data_woke', array('mid' => $mid), 'woke_insert',10,['skip'=>['id']]);

             }
             !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
             TMDB::add_log($mid,0,'update rating','Add woke rating',1,'woke');

         }
         else
         {


             if ($debug)echo 'update data<br>';

                     $shouldUpdate = false;

                     $array_data = [$array['title'],$array['country'],$array['diversity'], $array['female'], $array['woke'], $lgbt_words, $array['lgbt'], $array['qtia'], $array['audience'], $array['boycott'], $array['oweralbs'],
                         $array['rtgap'], $array['year'], $array['rtaudience'], $array['imdb'], $array['kino'], $array['douban'],
                         $woke, $lgbt, $result, time(), $mid];

                     ///check
                     $i=0;
                     foreach ($r[0] as $key => $value) {
                         if ($key!='id' && $key!='mid'){

                             if ( array_key_exists($i, $array_data) && $value != $array_data[$i] ) {
                                 if ($debug) echo 'break '.$key.' => '.$value . ' : ' . $array_data[$i].'<br>';
                                 $shouldUpdate = true;
                                 break;
                             }
                             else
                             {
                                 if ($debug)  echo 'continue '.$key.' => '.$value . ' : ' . $array_data[$i].'<br>';
                             }
                             $i++;

                         }



                     }


                         if ($shouldUpdate)
                         {
                             if ($debug)echo 'updated<br>';

                             $q = "UPDATE `data_woke` SET `title` =?, `country`=?,
                       `diversity`=?,
                       `female`=?,`woke`=?,`lgbt`=?,`lgb`=?,`qtia`=?, `audience`=?,
                       `boycott`=?,`oweralbs`=?,`rtgap`=?,`year`=?,
                       `rtaudience`=?,`imdb`=?,`kino`=?,`douban`=?,
                       `woke_result`=?,`lgbt_result`=?,`result`=?,
                       `last_update`=? WHERE `mid`= ? ";
                             Pdo_an::db_results_array($q,$array_data );


                             $query_formatted = preg_replace_callback('/\?/', function() {
                                 static $i = 0;
                                 return '%s';
                             }, $q);


                             $escaped_data = array_map(function($item) {
                                 return is_numeric($item) ? $item : "'" . addslashes($item) . "'";
                             }, $array_data);


                             $query = vsprintf($query_formatted, $escaped_data);

                             echo $query;

                             if ($sync)
                             {
                                 if ($debug)echo 'synched<br>';

                                 !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                                 Import::create_commit('', 'update', 'data_woke', array('mid' => $mid), 'woke_update',10,['skip'=>['id']]);
                             }
                             !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
                             TMDB::add_log($mid,0,'update rating','woke rating updated',1,'woke');
                         }
                         else{
                                if ($debug)echo 'not need update<br>';
                         }



                 //echo ' updated ';


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
    public function zr_woke($mid = 0,$debug=0,$all='')
    {

        if ($mid) {
            $where = " data_movie_imdb.id = '" . $mid . "' ";
        } else {
            $where = " data_woke.id IS NULL ";

        }
     ///   SELECT data_movie_imdb.id FROM `data_movie_imdb` LEFT JOIN data_woke ON (data_movie_imdb.id = data_woke.mid) WHERE  data_woke.id IS NULL LIMIT 10000

        $q = "SELECT data_movie_imdb.id FROM `data_movie_imdb` LEFT JOIN data_woke ON (data_movie_imdb.id = data_woke.mid) WHERE " . $where . " LIMIT 10000";
        if (isset($_GET['force'])) {
            $q = " SELECT * FROM `data_woke` ORDER BY `mid` ASC ";
        }
        if ($debug)echo $q;

        $r = Pdo_an::db_results_array($q);
        $count =count($r);
        echo 'total woke not fill ='.$count.'<br>';

        $i=0;
        foreach ($r as $row)
        {


                $mid = $row['id'];
                $result = $this->zr_woke_calc($mid,$debug,1,1);
                echo $i.'/'.$count.' mid = ' . $mid.' result = '.$result.'%<br>' ;

                if (function_exists('check_cron_time'))
                {
                    if (check_cron_time())
                    {
                        echo 'end time '.check_cron_time();
                        break;
                    }
                }



            $i++;
        }


    }
    public function zr_woke_calc($mid = 0,$debug=0,$update = 0,$sync =0)
    {

        //get diversity, female
        $gender_data = $this->get_diverstiy($mid);
        $lgbt_count = $this->get_lgbt($mid);
        $audience = $this->rwt_audience($mid, 1);
        ///$rtomatoes =  $this->get_rwt_rating($mid);
        list($title,$years,$country) = $this->get_year($mid);
        $erating = $this->total_rating($mid);
        $oweralbs = $this->get_oweralbs($audience);




        $array = ['title'=>$title,'country'=>$country,'diversity' => $gender_data['diversity'], 'female' => $gender_data['gender'], 'woke' => $lgbt_count['woke'], 'lgb' => $lgbt_count['lgbt'],'lgb_text'=>$lgbt_count['lgbt_text'],'qtia' => $lgbt_count['qtia'],'qtia_text'=>$lgbt_count['qtia_text'],'woke_text'=>$lgbt_count['woke_text'], 'audience' => $audience['rating'],
            'boycott' => $audience['vote'], 'oweralbs' => $oweralbs, 'rtgap' => $erating['rotten_tomatoes_gap'], 'year' => $years, 'rtaudience' => $erating['rotten_tomatoes_audience'],
            'imdb' => $erating['imdb'], 'kino' => $erating['kinop_rating'], 'douban' => $erating['douban_rating']];

        foreach ($array as $i=>$v)
        {
            if (!$v){$array[$i]=0;}
        }

        /// diversity	female	woke	lgbt	audience	boycott	oweralbs	rtgap	year	rtaudience	imdb	kino	douban	result	last_update

        $result = $this->calculate_rating($mid, $array, $update, $debug,$sync);

    return $result;
    }

}