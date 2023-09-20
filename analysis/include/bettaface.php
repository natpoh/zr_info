<?php

error_reporting('E_ERROR');
set_time_limit(0);


if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('KAIROS') ? include ABSPATH . "analysis/include/kairos.php" : '';

!class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';

if (!defined('MOVIES_LINKS_PLUGIN_DIR')) {
    define('MOVIES_LINKS_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/movies_links/');
}
if (!defined('MOVIES_LINKS_VERSION')) {
    define('MOVIES_LINKS_VERSION', 1);
}

//Movies links rating
if (!class_exists('MoviesLinks')) {
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'TorParser.php' );
}


global $tor_parser;
try {
    $tor_parser = new TorParser();
} catch (Exception $exc) {
    echo $exc->getTraceAsString();
}

class BETTAFACE {

    public static function get_actor_race($base64, $actor_id) {
        global $debug;

        $url = 'https://www.betaface.com/demo.html';

        $result = KAIROS::getCurlCookieface($url, '', '', '');

        //$result = self::get_curl($url);

        $regv = "#'api_key': '([^\,]+),#";


        if (preg_match($regv, $result, $mach)) {
            ///  var_dump($mach[1]);
            $key = substr($mach[1], 0, strlen($mach[1]) - 1);
            ///   echo 'key = ' . $key;
            if ($debug)
            {
                echo 'key = ' . $key."<br>";
            }
        }


        $arrayhead = array(
            'Host: www.betafaceapi.com',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:82.0) Gecko/20100101 Firefox/82.0',
            'Accept: application/json, text/javascript; q=0.01',
            'Accept-Language: en',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Origin: https://www.betaface.com',
            'Connection: keep-alive',
            'Referer: https://www.betaface.com/demo.html',
        );


        $pos_data = array(
            'api_key' => $key,
            'detection_flags' => "cropface,recognition,classifiers",
            'file_base64' => $base64,
            'original_filename' => time() . '.jpg');

        $pos_data = json_encode($pos_data);

        $url = "https://www.betafaceapi.com/api/v2/media";
        $result = KAIROS::getCurlCookieface($url, $pos_data, $arrayhead, '');
        //$result = self::get_curl($url, $arrayhead, $pos_data, true);

        if ($debug) {
        //  var_dump($result);
        }
        if ($result) {

            $arraay = json_decode($result,1);
        }
        if (!$arraay) {
echo 'error';

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
                    $error = 'Syntax error, malformed JSON ';
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

        if ($debug) { !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
            TMDB::var_dump_table($arraay);

        }



        $race = $arraay['media']['faces'][0]['tags'][31]['value'];
        $percent =$arraay['media']['faces'][0]['tags'][31]['confidence'];
        $attractive = $arraay['media']['faces'][0]['tags'][3];
        if ($attractive) {
            $attractive = json_encode($attractive);
        }

        /// var_dump($attractive);

        if ($arraay) {
            $result = json_encode($arraay);

            self::save_array_to_file($actor_id, $result);
        }


        if ($arraay->media) {
            if ($arraay->media->faces == NULL) {
                return array($race, $percent, json_encode(['error' => 'face not found']),2);
            }
        }
        if ($debug) {
            TMDB::var_dump_table(array($race, $percent, $attractive,1));

        }

        return array($race, $percent, $attractive,1);
    }

    private static function fileman($way) {
        if (!file_exists($way))
            if (!mkdir("$way", 0777)) {
                
            }
        return null;
    }

    private static function check_and_create_dir($path) {
        if ($path) {
            $arr = explode("/", $path);

            $path = ABSPATH;
            foreach ($arr as $a) {
                if ($a) {
                    $path = $path . $a . '/';
                    self::fileman($path);
                }
            }
            return null;
        }
    }

    public static function save_array_to_file($id, $result) {
        self::check_and_create_dir('wp-content/uploads/actors_gzdata');


        if (function_exists('gzencode')) {
            $gzdata = gzencode($result, 9);
            file_put_contents(ABSPATH . 'wp-content/uploads/actors_gzdata/ab' . $id, $gzdata);
        }
    }

    public static function add_toracebd($actor_id, $array_race,$status=1) {

        if (self::checkadd($actor_id)) {
            $sql = "UPDATE `data_actors_face` SET `race`=?,`percent`=?,`array`=?,`status`=?,`last_update`=? WHERE `actor_id`=? ";
            Pdo_an::db_results_array($sql, [$array_race[0], $array_race[1], $array_race[2], $status,time(), $actor_id]);
        } else {
            $sql = "INSERT INTO `data_actors_face`(`id`, `actor_id`, `race`, `percent`, `array`,`status`, `last_update`) 
            VALUES (NULL, ? , ? , ? , ? , ? ,? )";
            Pdo_an::db_results_array($sql, [$actor_id, $array_race[0], $array_race[1], $array_race[2],$status, time()]);
        }

        ////create commit
        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'update', 'data_actors_face', array('actor_id' => $actor_id), 'bettaface',9,['skip'=>['id']]);

    }

    public static function checkadd($actor_id) {

        $sql = " SELECT * FROM `data_actors_face` where actor_id= " . $actor_id;
        $r = Pdo_an::db_fetch_row($sql);

        return $r->actor_id;
    }

    public static function get_curl($url, $header_array = array(), $post_vars = array(), $is_post = false) {
        $debug = $_GET['debug'] ? true : false;
        global $tor_parser;
        $header = '';
        $limit = array('h' => 50, 'd' => 1000);
        $curl = true;
        $tor_mode = 0;
        $content = $tor_parser->get_url_content($url, $header, $limit, $curl, $tor_mode, $is_post, $post_vars, $header_array, $debug);

        return $content;
    }

    public static function Prepare($id = '') {

        global $debug;
        if ($debug)
        {
            !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
        }
        ///$last_id = OptionData::get_options(5);

        if ($id) {
            $dop = " and data_actors_imdb.id = " . intval($id)." ";
        } else {
            $dop = "and data_actors_face.id IS NULL"; // and data_actors_imdb.id > " . $last_id;
        }

        $sql = "SELECT `data_actors_imdb`.id  FROM `data_actors_imdb` 
        LEFT JOIN data_actors_face ON (data_actors_face.actor_id=data_actors_imdb.id)
        WHERE ( `data_actors_imdb`.image='Y' OR  `data_actors_imdb`.image_url IS NOT NULL) 
        " . $dop . "  order by data_actors_imdb.id limit 10";


        if ($debug){

            echo $sql.'<br>';
        }

        $rw = Pdo_an::db_results_array($sql);

        foreach ($rw as $r) {

            $actor_id = $r['id'];

            if (!self::checkadd($actor_id) || $id) {
                if ($debug) {
                    echo 'get data from ' . $actor_id . '<br>';
                }
                $img_64 = KAIROS::create_image_64($actor_id, '', 'imdb');

                $number = str_pad($actor_id, 7, '0', STR_PAD_LEFT);
                echo '<img style="width: 200px;" src="/analysis/img_final/' . $number . '.jpg" />';

                ///create_image_64($actor_id);
                if ($img_64) {
                    sleep(1);
                    $array_race = self::get_actor_race($img_64, $actor_id);



                    //////update bd
                    if ($array_race[1] || $array_race[2]) {

                        self::add_toracebd($actor_id, $array_race,$array_race[3]);

                        echo 'add<br>';

                        //update meta
                        self::update_meta($actor_id,$array_race[0]);

                    } else {
                        echo 'error get ethnic data <br>';
                        $array_race = [NULL, NULL, json_encode(['error' => 'error get ethnic data'])];
                        self::add_toracebd($actor_id, $array_race,3);
                    }
                } else {
                    $array_race = [NULL, NULL, json_encode(['error' => 'no img64'])];
                    self::add_toracebd($actor_id, $array_race,4);
                }
            } else
                echo 'actor alredy addeded<br>';
        }
    }
private static function update_meta($aid,$verdict)
{
    global $debug;

    $array_face = array('white' => 'W', 'hispanic' => 'H', 'black' => 'B', 'mideast' => 'M', 'indian' => 'I', 'asian' => 'EA');

    if ($array_face[$verdict]) {
        $verdict_m = $array_face[$verdict];
    }



    if ($verdict_m) {
        !class_exists('INTCONVERT') ? include ABSPATH . "analysis/include/intconvert.php" : '';
        $verdict_n = INTCONVERT::str_to_int($verdict_m) ;

        $sql1 = "UPDATE `data_actors_meta` SET
           `n_bettaface` = '" . $verdict_n. "',
           `last_update` = " . time() . "  WHERE `data_actors_meta`.`actor_id` = '" . $aid . "' and  `n_bettaface`  != '" . $verdict_n. "' ";
        Pdo_an::db_query($sql1);


        if ($debug)echo 'update meta: '.$verdict.' =>'.$verdict_n.'<br>';


        !class_exists('ActorWeight') ? include ABSPATH . "analysis/include/actors_weight.php" : '';
        ActorWeight:: update_actors_verdict($aid );

        !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';
        ACTIONLOG::update_actor_log('n_bettaface','data_actors_meta',$aid);




    }
}
}

if (isset($_GET['bettaface'])) {
    BETTAFACE::Prepare($_GET['bettaface']);
}