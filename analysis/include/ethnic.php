<?php

class Ethinc
{

    public static function normalise_array($array)
    {
        $totalsumm = 0;


        foreach ($array as $index => $val) {
            $totalsumm += $val;
        }
        if ($totalsumm) {
            foreach ($array as $index => $val) {

                $array_result[$index] = round($val * 100 / $totalsumm, 2);


            }

            return $array_result;


        }
        return $array;
    }

    public static function prepare_ethnic($ethnic)
    {
        if (strstr($ethnic, ',')) {
            $array_race = explode(',', $ethnic);

        } else {
            $array_race[0] = $ethnic;
        }
        foreach ($array_race as $index => $val) {
            $val = trim($val);
            $reg = '#\([^\)]+\)#';
            $val = preg_replace($reg, ' ', $val);
            $reg = '#\[[^\]]+\]#';
            $val = preg_replace($reg, ' ', $val);

            $array_race[$index] = $val;
        }

        return $array_race;

    }

    public static function update_verdict_meta($id='')
    {
        global $debug;
        self::check_verdict($id);

        $array_min = array('Asian' => 'EA', 'White' => 'W', 'Latino' => 'H', 'Black' => 'B', 'Arab' => 'M', 'Dark Asian' => 'I', 'Jewish' => 'JW', 'Other' => 'MIX', 'Mixed / Other' => 'MIX', 'Indigenous' => 'IND', 'Not a Jew' => 'NJW', 'Sadly, not' => 'NJW');

        if ($id)
        {
            $sql = "select * from data_actors_ethnic where actor_id=".$id;
        }
        else
        {
            $sql = "select * from data_actors_ethnic where actor_id>0 ";
        }

        $array_movie = Pdo_an::db_results_array($sql);

        foreach ($array_movie as $movie_data) {
            $verdict_result='';

           $actor_id=  $movie_data['actor_id'];
            $verdict=  $movie_data['verdict'];
            if ($verdict)
            {
                $verdict_result = $array_min[$verdict];

            }

            if ($verdict_result)
            {



                if ($debug)
                {
                    echo 'update meta: '.$verdict.'=>'.$verdict_result.' '.PHP_EOL;
                }

                $sql1 = "UPDATE `data_actors_meta` SET  n_ethnic ='".self::intconvert($verdict_result)."' ,`last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` = '" . $actor_id . "'";
                Pdo_an::db_query($sql1);

                !class_exists('ActorWeight') ? include ABSPATH . "analysis/include/actors_weight.php" : '';
                ActorWeight:: update_actors_verdict($actor_id,1 ,1);
            }
            else
            {
                if ($debug)
            {
                 echo 'not found '.$verdict.PHP_EOL;
            }
               // echo 'not found '.$verdict.PHP_EOL;
            }

        }

    }
    public static function intconvert($data)
    {
        $result=0;

        $array_int_convert = array('W'=>1,'EA'=>2,'H'=>3,'B'=>4,'I'=>5,'M'=>6,'MIX'=>7,'JW'=>8,'NJW'=>9,'IND'=>10);

        if ($array_int_convert[$data])
        {
            $result = $array_int_convert[$data];
        }

        return $result;
    }
    public static function check_verdict($id='')
    {
        if ($id)
        {
            $where = " and actor_id = {$id} ";
        }

        $sql = "select id, ethnic_decode from data_actors_ethnic where ethnic_decode IS NOT NULL and verdict = '' ".$where." ";
        $array_movie = Pdo_an::db_results_array($sql);

        foreach ($array_movie as $movie_data) {

            $rid = $movie_data['id'];
            $data = $movie_data['ethnic_decode'];
            Ethinc::addverdict($rid,json_decode($data,1));

        }
    }


    public static function addverdict($id,$result_array,$debug='')
{

    if (is_array($result_array))
    {
    arsort($result_array) ;


       $keys =   array_keys($result_array);
       $race =  $keys[0];
       $count = $result_array[$race];

       ////Indigenous
       if ($race!='Indigenous' && $result_array['Indigenous']>0)
       {
           ///remove Indigenous
           unset($result_array['Indigenous']);
           self::addverdict($id,$result_array);
           return;
       }

       if ($race=='Jewish' &&  $count>=30)
       {
            $verdict = $race;
       }
        if ($race=='Black' &&  $count>=70)
        {
            $verdict = $race;
        }

       else  if ($count==100 && $race!='Indigenous')
       {

               $verdict = $race;

       }
       else
       {
           $verdict='Other';
       }


        if ($debug)
        {
            echo 'verdict: '.$verdict.' <br>';

        }

       if ($verdict)
       {

           $sql = "select * from data_actors_ethnic where id =".$id;
           $r = Pdo_an::db_results_array($sql);
           $verdict_old = $r[0]['verdict'];
           $actor_id = $r[0]['actor_id'];

           if ($verdict !=$verdict_old )
           {
           $sql = "UPDATE `data_actors_ethnic` SET `verdict` = ? WHERE `data_actors_ethnic`.`id` ={$id} ";
           Pdo_an::db_results_array($sql, array($verdict));

           if ($actor_id)
           {
               !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
               Import::create_commit('', 'update', 'data_actors_ethnic', array('actor_id' => $actor_id), 'data_actors_ethnic',10,['skip'=>['id']]);
           }
           }
       }

    }
}

    public static function prepare_ethnic_race($race,$array_compare)
    {
        $race = strtolower($race);
        if (strstr($race, '/')) {
            $race = substr($race, 0, strpos($race, '/'));
        }

        $array_remove_word = array('some', 'possibly', 'likely', 'and', 'other', 'distant', 'remote', 'with', 'more', 'along', 'about', 'well',  'what', 'race','convert');

        $array_trim_word = array(' and', ' or', ')', '(', '[', ']', 'with small', 'small','-');
        foreach ($array_trim_word as $word) {
            if (strstr($race, $word)) {
                $race = substr($race, 0, strpos($race, $word));
            }
        }


        foreach ($array_remove_word as $word) {
            if ($race == $word) {
                $race = '';
            }

        }

        $race = trim($race);

        if ($array_compare[ucfirst($race)])
        {
            return ucfirst($race);
        }
        else
        {
            foreach ($array_remove_word as $word) {
                if (strstr($race, $word)) {
                    $race = str_replace($word,'',$race);
                    $race = trim($race);
                }
            }

            if ($array_compare[ucfirst($race)])
            {
                return ucfirst($race);
            }
            else if (strstr($race,' '))
            {
                $race_a = explode(' ',$race);
                foreach ($race_a as $race_m)
                {
                    $race_m = ucfirst(trim($race_m));
                    if ($array_compare[$race_m])
                    {
                        $race=$race_m;
                    }
                }
            }
            return ucfirst($race);
        }
    }

    public static function foreach_ethnic($ethnic_result_data, $array_compare, $result_array, $array_notfound, $percent = '')
    {
        foreach ($ethnic_result_data as $i => $race) {


            $percent_p = 1;

            if ($percent) {
                $percent_p = $race;
                $race=$i;
            }


            $race = trim($race);
            $race = preg_replace('#([\*0-9\/]+)#',' ',$race);
            $race = ucfirst($race);

            global $debug;

            if ($debug)
            {
                echo $race.'<br>';
            }


            if ($array_compare[$race]) {
                $result_array[$array_compare[$race]] += $percent_p;
            } else {
                $race = self::prepare_ethnic_race($race,$array_compare);
                if ($array_compare[$race]) {
                    $result_array[$array_compare[$race]] += $percent_p;
                } else if ($race) {
                    $array_notfound[$race]++;
                }
            }
        }
        return array($result_array, $array_notfound);
    }

    public static function set_actors_ethnic($aid='',$force =0,$debug=0)
    {
        if ($force)
        {
            $sql = "UPDATE `data_actors_ethnic` SET `verdict` ='' ";
           Pdo_an::db_results_array($sql);

        }

        global $array_compare;
        if (!$array_compare) {
            $array_compare = TMDB::get_array_compare();

        }
        $where='';

        if ($aid)
        {
            $where = " where actor_id = {$aid} ";
        }
        else if ($force){
            $where = '';
        }
        else{

            $where = " where verdict is NULL and actor_id > 0 and last_update_verdict < ".(time()-86400*7)." limit 100";
        }


//var_dump($array_compare);
        $array_notfound = [];
        $sql = "select * from data_actors_ethnic ".$where;
        if ($debug) echo $sql.'<br>';
        $array_movie = Pdo_an::db_results_array($sql);
        $array_ethnic_result = [];
        foreach ($array_movie as $movie_data) {
            $id = $movie_data['id'];
            $ethnic = $movie_data['Ethnicity'];
            $tags = $movie_data['Tags'];
            $tags = strtolower($tags);
            $ethnic = strtolower($ethnic);


            if ($debug)
            {
                echo 'ethnic: '.$ethnic.'<br>';
            }
                if ($ethnic) {
                    $regv = '#((.+)\(father\)(.+)\(mother\))|((.+)\(mother\)(.+)\(father\))#Uis';
                    $reg_v = '#(([0-9\.]+)\% ([A-Za-z- ]+))#';
                    $regv_3 ='#\*father(.+)\n\*mother(.+)#';

//                    if (strstr($ethnic, 'jewish')) {
//                        $array_ethnic_result[$id] = array('jewish');
//                    } else
                        if (preg_match($regv, $ethnic, $mach)) {
                        if ($mach[1]) {
                            $array_ethnic_result[$id]['parents'] = array('f' => self::prepare_ethnic($mach[2]), 'm' => self::prepare_ethnic($mach[3]));
                        } else if ($mach[4]) {
                            $array_ethnic_result[$id]['parents'] = array('f' => self::prepare_ethnic($mach[6]), 'm' => self::prepare_ethnic($mach[5]));
                        }
                    }
                    else if (preg_match($regv_3, $ethnic, $mach)) {
                        if ($mach[1]) {
                            $array_ethnic_result[$id]['parents'] = array('f' => self::prepare_ethnic($mach[1]), 'm' => self::prepare_ethnic($mach[2]));
                        }
                    }


                        else if (preg_match_all($reg_v, $ethnic, $mach)) {
                        $array_race = [];
                        foreach ($mach as $i => $mach_data) {
                            $count = $mach[2][$i];
                            $ethnic_val = $mach[3][$i];
                            $array_race[$ethnic_val]+=$count;
                        }

                        $array_ethnic_result[$id]['percent'] = $array_race;
                    } else if (strstr($ethnic, ',')) {
                        $array_race = explode(',', $ethnic);
                        $array_ethnic_result[$id] = $array_race;

                    } else {
                        $ethnic = trim($ethnic);


                        if (strstr($ethnic, ' ')) {

                            $reg = '#\([^\)]+\)#';
                            $ethnic = preg_replace($reg, '', $ethnic);
                            $reg = '#\[[^\]]+\]#';
                            $ethnic = preg_replace($reg, '', $ethnic);

                            if (strstr($ethnic, ' and')) {
                                $array_ethnic_result[$id] = explode(' and', $ethnic);
                            } else {
                                $array_ethnic_result[$id] = array($ethnic);

                            }

                        } else {

                            $array_ethnic_result[$id] = array($ethnic);
                        }
                    }
                } else if (!$ethnic && $tags) {
//                    if (strstr($tags, 'jewish')) {
//                        $array_ethnic_result[$id] = array('jewish');
//                    }
//                    else
                    {
                        $array_ethnic_result[$id]['tags'] = self::prepare_ethnic($tags);
                    }


                }

            if ($debug)
            {

                !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
                echo 'ethnic_result_data: <br>';
                TMDB::var_dump_table($ethnic);
                echo '<br>';
            }

                $ethnic_result_data=$array_ethnic_result[$id] ;
                $result_array = [];

            if ($debug)
            {

                TMDB::var_dump_table($ethnic_result_data);

            }

                if ($ethnic_result_data['tags']) {

                    $array_result_data = self::foreach_ethnic($ethnic_result_data['tags'], $array_compare, $result_array, $array_notfound);
                    $result_array = $array_result_data[0];
                    $array_notfound = $array_result_data[1];
                    $result_array = self::normalise_array($result_array);


                } else if ($ethnic_result_data['parents']) {


                    $array_result_data = self::foreach_ethnic($ethnic_result_data['parents']['f'], $array_compare, $result_array, $array_notfound);
                    $result_array['f'] = self::normalise_array($array_result_data[0]);
                    $array_notfound = $array_result_data[1];
                    $array_result_data = self::foreach_ethnic($ethnic_result_data['parents']['m'], $array_compare, [], $array_notfound);
                    $result_array['m'] = self::normalise_array($array_result_data[0]);
                    $array_notfound = $array_result_data[1];
                    $result_array_temp = [];
                    //print_r($result_array);
                    foreach ($result_array as $type => $pdata) {
                        foreach ($pdata as $race => $count) {
                            $result_array_temp[$race] += $count;
                        }
                    }
                    $result_array = self::normalise_array($result_array_temp);
                    // print_r($result_array);

                } else if ($ethnic_result_data['percent']) {

                    $array_result_data = self::foreach_ethnic($ethnic_result_data['percent'], $array_compare, $result_array, $array_notfound, 1);

                    $result_array = $array_result_data[0];
                    $array_notfound = $array_result_data[1];
                    $result_array = self::normalise_array($result_array);

                } else {
                    // print_r($ethnic_result_data);



                    $array_result_data = self::foreach_ethnic($ethnic_result_data, $array_compare, $result_array, $array_notfound);
                    $result_array = $array_result_data[0];
                    $array_notfound = $array_result_data[1];
                    $result_array = self::normalise_array($result_array);


                }

            if ($debug)
            {
                echo 'result_array: <br>';


                    TMDB::var_dump_table($result_array);


                echo '<br>';
            }


                if ($result_array)
                {
                    $sql = "UPDATE `data_actors_ethnic` SET `ethnic_result` = ?, `ethnic_decode` = ? , last_update_verdict	=? WHERE `data_actors_ethnic`.`id` ={$id} ";
                    Pdo_an::db_results_array($sql, array(json_encode($ethnic_result_data),json_encode($result_array),time()));

                    self::addverdict($id,$result_array,$debug);

                   self::update_verdict_meta($aid);

                    !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';
                    ACTIONLOG::update_actor_log('add_actors_ethnic','data_actors_meta',$aid );
                }
                else
                {
                    $sql = "UPDATE `data_actors_ethnic` SET  last_update_verdict  =? WHERE `data_actors_ethnic`.`id` ={$id} ";
                    Pdo_an::db_results_array($sql, array(time()));
                }

                if (function_exists('check_cron_time'))
                {
                    if (check_cron_time())
                    {
                        break;
                    }

                }

        }

        if ($debug)
        {
            echo '<br>' . PHP_EOL;
        }

        arsort($array_notfound);


        if ($debug && $array_notfound)
        {

            echo 'not found';
            TMDB::var_dump_table($array_notfound);
        }


        ///create verdict
//        if ($force) {
//            self::update_verdict_meta($id);
//        }
    }


}