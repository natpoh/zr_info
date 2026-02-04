<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
!class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';

class FilterCrew
{
    private static $array = array('director' => 'filter_director', 'cast_director' => 'filter_cast_director', 'writer' => 'filter_writer', 'actors' => 'filter_leed_actor');

    private static $ethnic_sort=[];

    public static function ethnic_sort($ethnycity)
    {

                $array_convert_type = MOVIE_DATA::get_array_convert_type();


        foreach ($ethnycity as $order => $data) {
            foreach ($data as $typeb => $enable) {
                if ($enable) {
                    $ethnic_sort[$array_convert_type[$typeb]] = [];
                }
            }
        }
        return $ethnic_sort;
    }


    private function check_actors($id, $gender, $ethnic_need)
    {

        $ethnic=[];
        $sql = "SELECT * FROM `data_actors_meta` where actor_id =" . $id . " " . $gender;
        // echo $sql.'<br>';


        $result = Pdo_an::db_results_array($sql);

        if (count($result)) {

            if ($ethnic_need) {

                foreach ($result as $r) {
                    if ($r['jew']) $ethnic['jew'][$r['actor_id']] = $r['jew'];
                    if ($r['bettaface']) $ethnic['bettaface'][$r['actor_id']] = $r['bettaface'];
                    if ($r['surname']) $ethnic['surname'][$r['actor_id']] = $r['surname'];
                    if ($r['ethnic']) $ethnic['ethnic'][$r['actor_id']] = $r['ethnic'];
                    if ($r['kairos']) $ethnic['kairos'][$r['actor_id']] = $r['kairos'];
                }
                $result_actors = [];

                foreach (self::$ethnic_sort as $key => $value) {
                    $result_actors[$key] = $ethnic[$key];
                }
                if (is_array($result_actors)) {
                    foreach ($result_actors as $i => $v) {
                        if ($v[$id]) {
                            $actor_race = $v[$id];
                            break;
                        }
                    }

                    if (in_array($actor_race, $ethnic_need)) {
                        ///  echo '$actor_race='.$actor_race.'<br>';
                        return array('result' => 1, 'message' => 'ok');
                    }
                }

                return array('result' => 0, 'message' => 'ethnic not found');
            }


        } else {
           /// echo 'no genger<br>';
            return array('result' => 0, 'message' => 'gender not found');
        }

        return array('result' => 1, 'message' => 'ok');

    }


    private function filter_gender($gender, $r, $index, $ethnic)
    {

         $result_array = [];

        $result = $r[$index];
      ///  echo '$index=' . $index . '<br>';
        if ($result) {
            if ($index == 'actors') {
                $actors_array = json_decode($result, JSON_FORCE_OBJECT);

                if ($actors_array['s']) {
                    foreach ($actors_array['s'] as $actor_id => $actor_name) {
                        $result_array[] = $actor_id;

                    }
                } else {
                    $a = 0;
                    foreach ($actors_array as $type => $actos_data) {
                        foreach ($actos_data as $actor_id => $actor_name) {

                            $result_array[] = $actor_id;
                            if ($a >= 2) {
                                break;
                            }
                            $a++;
                        }
                    }
                }


            } else {
                if (strstr($result, ',')) {
                    $result_array = explode(',', $result);
                } else {
                    $result_array[0] = $result;
                }

            }
            //var_dump($result_array);
            foreach ($result_array as $actor) {

               /// echo $actor.'<br>';
                $actor_result = self::check_actors($actor, $gender, $ethnic);
                if ($actor_result['result'] == 1) {
                    return $actor_result;
                }
                else
                {
                 ///   var_dump($actor_result);
                }
            }
            return array('result' => 0, 'message' => 'not found from actors ' . $index . ' ');

        } else {
            return array('result' => 0, 'message' => 'persone ' . $index . ' not found');
        }
    }

    public static function check_filters($r, $data_object,$ethnic_sort)
    {
        self::$ethnic_sort=$ethnic_sort;




        foreach (static::$array as $index => $value) {
            $gender = '';
            $data = $data_object->{$value};
            if ($data) {
                if (in_array('Male', $data)) {
                    $key = array_search('Male', $data);
                    unset($data[$key]);
                    $gender = "and gender = 2 ";
                }
                if (in_array('Female', $data)) {
                    $key = array_search('Female', $data);
                    unset($data[$key]);

                    if ($gender) {
                        $gender = '';
                    } else {
                        $gender = "and gender = 1 ";
                    }


                }
                if (($gender || count($data)) && $r) {

                    $result = self::filter_gender($gender, $r, $index, $data);
                    if ($result['result'] == 0) {
                        return $result;
                    }
                }
            }

        }
        return array('result' => 1, 'message' => 'ok');

    }
}