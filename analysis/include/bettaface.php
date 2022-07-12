<?php
error_reporting('E_ERROR');
set_time_limit(0);


if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('KAIROS') ? include ABSPATH . "analysis/include/kairos.php" : '';

!class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';


//Movies links rating
if (!function_exists('include_movies_links')) {
    include ABSPATH . 'wp-content/plugins/movies_links/movies_links.php';
}

include_movies_links();

$ml = new MoviesLinks();

if (!class_exists('MoviesLinks')) {
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'TorParser.php' );
}


class BETTAFACE
{

    public static function get_actor_race($base64,$actor_id)
    {
        $url = 'https://www.betaface.com/demo.html';

        //$result = KAIROS::getCurlCookieface($url, '', '', '');

        $result =self::get_curl($url,'',false,false);

        $regv = "#'api_key': '([^\,]+),#";

        if (preg_match($regv, $result, $mach)) {
            ///  var_dump($mach[1]);
            $key = substr($mach[1], 0, strlen($mach[1]) - 1);
            ///   echo 'key = ' . $key;
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
        ///$result = KAIROS::getCurlCookieface($url, $pos_data, $arrayhead, '');
        $result =self::get_curl($url,$arrayhead,$pos_data,true);

        var_dump($result);

        if ($result) {

            $arraay = json_decode($result);

        }





        $race = $arraay->media->faces[0]->tags[31]->value;
        $percent = $arraay->media->faces[0]->tags[31]->confidence;
        $attractive =$arraay->media->faces[0]->tags[3];
        if ($attractive)
        {
            $attractive = json_encode($attractive);
        }

       /// var_dump($attractive);

        if ($arraay) {
            $result = json_encode($arraay);

            self::save_array_to_file($actor_id,$result);
        }


        if ($arraay->media)
        {
            if ($arraay->media->faces==NULL)
            {
                return array($race, $percent, json_encode(['error'=>'face not found'] ));
            }
        }


        return array($race, $percent, $attractive);

    }


    private static function fileman($way)
    {
        if (!file_exists($way))
            if (!mkdir("$way", 0777)) {
            }
        return null;
    }
    private static function check_and_create_dir($path)
    {
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

    public static function save_array_to_file($id,$result)
    {
        self::check_and_create_dir('wp-content/uploads/actors_gzdata');


        if (function_exists('gzencode')) {
            $gzdata = gzencode($result, 9);
            file_put_contents(ABSPATH . 'wp-content/uploads/actors_gzdata/ab' . $id, $gzdata);
        }

    }

    public static function add_toracebd($actor_id, $array_race)
    {

        if (self::checkadd($actor_id))
        {
            $sql = "UPDATE `data_actors_face` SET `race`=?,`percent`=?,`array`=?,`last_update`=? WHERE `actor_id`=? ";
            Pdo_an::db_results_array($sql, [ $array_race[0], $array_race[1], $array_race[2], time(),$actor_id]);
        }
        else
        {
            $sql = "INSERT INTO `data_actors_face`(`id`, `actor_id`, `race`, `percent`, `array`, `last_update`) 
            VALUES (NULL, ? , ? , ? , ? , ? )";
            Pdo_an::db_results_array($sql, [$actor_id, $array_race[0], $array_race[1], $array_race[2], time()]);

        }

    }

    public static function checkadd($actor_id)
    {

        $sql = " SELECT * FROM `data_actors_face` where actor_id= " . $actor_id;
        $r = Pdo_an::db_fetch_row($sql);

        return $r->actor_id;
    }

    public static function get_curl($url,$header,$post_vars,$is_post)
{




$tp = new TorParser();


// Example post vars
$post_vars = array(
    'id'=>1,
    'string'=>'test'
);


$content = $tp->get_url_content($url, $header, array(), true, 0, $is_post, $post_vars, true);

return $content;
}


    public static function Prepare($id = '')
    {

        ///$last_id = OptionData::get_options(5);

        if ($id) {
            $dop = " and data_actors_face.actor_id = " . intval($id);
        } else {
            $dop = "and data_actors_face.id IS NULL";// and data_actors_imdb.id > " . $last_id;
        }

        $sql = "SELECT `data_actors_imdb`.id  FROM `data_actors_imdb` 
        LEFT JOIN data_actors_face ON (data_actors_face.actor_id=data_actors_imdb.id)

        WHERE ( `data_actors_imdb`.image='Y' ) 
        " . $dop . "  order by data_actors_imdb.id limit 1";


        $rw = Pdo_an::db_results_array($sql);

        foreach ($rw as $r) {

            $actor_id = $r['id'];

            if (!self::checkadd($actor_id)  || $id )
            {

                echo 'get data from ' . $actor_id . '<br>';

                $img_64 = KAIROS::create_image_64($actor_id, '', 'imdb');

                $number = str_pad($actor_id, 7, '0', STR_PAD_LEFT);
                echo '<img src="/analysis/img_final/' . $number . '.jpg" />';

                ///create_image_64($actor_id);
                if ($img_64) {
                    sleep(1);
                    $array_race = self::get_actor_race($img_64,$actor_id);



                    //////update bd
                    if ($array_race[1] || $array_race[2]) {

                        self::add_toracebd($actor_id, $array_race);

                        echo 'add<br>';

                       /// OptionData::set_option(5, $actor_id, 'bettaface_last_id');

                    }
                    else {
                        echo 'error get ethnic data <br>';
                        $array_race = [NULL,NULL,json_encode(['error'=>'error get ethnic data'])];
                        self::add_toracebd($actor_id, $array_race);

                    }

            } else
                {
                    $array_race = [NULL,NULL,json_encode(['error'=>'no img64'])];
                    self::add_toracebd($actor_id, $array_race);

                }
        } else echo 'actor alredy addeded<br>';

    }
    }
}


if (isset($_GET['bettaface'])) {
    BETTAFACE::Prepare($_GET['bettaface']);
}