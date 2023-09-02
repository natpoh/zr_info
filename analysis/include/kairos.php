<?php
error_reporting('E_ERROR');
set_time_limit(0);

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//File service
!class_exists('FileService') ? include ABSPATH . "analysis/include/FileService.php" : '';
//FileLog
!class_exists('FileLog') ? include ABSPATH . "analysis/include/log.php" : '';


class KAIROS
{
    public static function getCurlCookieface($url, $b = '', $arrayhead = '',$proxy ='')
    {
        $cookiePath = ABSPATH . 'cookies/cookies.txt';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($proxy)
        {
            $proxy='127.0.0.1:8118';
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }

        if ($arrayhead) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayhead);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);


        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath);

        if (strstr($url, 'https')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ($b) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $b);

        }
        curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

private  function get_imdb_actor_image($content)
{


$regv = "/image_src\' href\=\\\"([^\\\"]+)/";

if (preg_match($regv, $content, $mach)) {

    $image = trim($mach[1]);
    if (!strstr($image, 'imdb_logo')) {
        $array_result['image'] = $image;
    }
}

return $array_result;

}

    public static  function create_image_64($imgid,$imgsource='',$type = 'imdb')
    {
        global $debug;
        $base64='';

        $number = str_pad($imgid, 7, '0', STR_PAD_LEFT);

        if (!$imgsource && $type == 'tmdb')
        {
            $imgsource = $_SERVER['DOCUMENT_ROOT'] . '/analysis/img_final_tmdb/' . $number . '.jpg';
        }
        else if (!$imgsource && $type == 'crowd')
        {
            $imgsource = $_SERVER['DOCUMENT_ROOT'] . '/analysis/img_final_crowd/' . $number . '.jpg';
        }
        else if (!$imgsource && $type == 'imdb') {
            $imgsource = $_SERVER['DOCUMENT_ROOT'] . '/analysis/img_final/' . $number . '.jpg';
        }




if (file_exists($imgsource)) {
    ///echo 'try get from ' . $imgsource;
    $data = file_get_contents($imgsource);
    $base64 = base64_encode($data);
}
else if ($type == 'imdb'){

    if ($debug)echo 'try copy image: ';
    $q="SELECT `image_url` FROM `data_actors_imdb` WHERE `id`=".$imgid;
    $image = Pdo_an::db_get_data($q,'image_url');
    if ($image)
    {
     if ($debug)echo ' '.$image.' ';


        $uploaded =    self::check_image_on_server($imgid, $image);

        if ($uploaded)
        {
            echo 'success<br>';
            $imgsource = $_SERVER['DOCUMENT_ROOT'] . '/analysis/img_final/' . $number . '.jpg';
            $data = file_get_contents($imgsource);
            $base64 = base64_encode($data);
        }
        else
        {
            echo  'false<br>';
        }

    }else
    {
        if ($debug)echo 'image not found <br>';
    }


}
else if ($type == 'tmdb')
{
    ///try get file
    //$imgid
    ///echo 'try get file ';
    self::load_tmd_image($imgid);

    if (file_exists($imgsource)) {
        ///echo 'try get from ' . $imgsource;
        $data = file_get_contents($imgsource);
        $base64 = base64_encode($data);
    }

}


return $base64;
}


public function kairos_api($image)
{

    // set variables
    $queryUrl = "http://api.kairos.com/detect";
    $imageObject = '{"image":"'.$image.'"}';
   // $APP_ID = "66b12c87";
   // $APP_KEY = "2a04a82fb7224cf9861de88ee9c2b3ee";
    $APP_ID = "a1e68edc";
    $APP_KEY = "a2241c792541738774d5c9b273a08939";
    $request = curl_init($queryUrl);

// set curl options
    curl_setopt($request, CURLOPT_POST, true);
    curl_setopt($request,CURLOPT_POSTFIELDS, $imageObject);
    curl_setopt($request, CURLOPT_HTTPHEADER, array(
            "Content-type: application/json",
            "app_id:" . $APP_ID,
            "app_key:" . $APP_KEY
        )
    );

    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($request);

// show the API response
    //echo $response;

// close the session
    curl_close($request);
    return $response;
}

    public function get_actor_race($base64)
    {
        global $debug;

        $result =  self::kairos_api($base64);

//        $arrayhead = array(
//            'Host: demo.kairos.com',
//            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
//            'Accept: text/plain, */*; q=0.01',
//            'Accept-Language: ru',
//            'Referer: https://demo.kairos.com/detect',
//            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
//            'X-Requested-With: XMLHttpRequest',
//            'Origin: https://demo.kairos.com',
//            'Alt-Used: demo.kairos.com',
//            'Connection: keep-alive'
//        );
//
//        $urlImageSrc=$base64;
//
//        $pos_data = array(
//            "image"   => $urlImageSrc,
//            "minHeadScale" =>'.015',
//            "selector"=> 'frontal'
//        );
//
//        $pos_data = json_encode($pos_data);
//        $pos_data =http_build_query(array('imgObj' => $pos_data));
//
//
//
//        $url = "https://demo.kairos.com/detect/send-to-api";
//
//
//        $result = static::getCurlCookieface($url, $pos_data, $arrayhead,0);





        if ($result) {
            $arraay = json_decode($result);
        }
        if ($debug)
        {
            !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
            TMDB::var_dump_table($arraay);
        }

        $race = $arraay->images[0]->faces[0]->attributes;

        // var_dump($race);

        $array_result['asian']=$race->asian;
        $array_result['black']=$race->black;
        $array_result['hispanic']=$race->hispanic;
        $array_result['white']=$race->white;
        $array_result['other']=$race->other;

        arsort($array_result);

        if ($race )
        {
            return $array_result;
        }
        else
        {
            ///echo 'error parse<br>';
            return $arraay;
        }

    }
    public static function get_actor_id($id,$result_image)
    {
        global $debug;

        $img_64 = static::create_image_64($id,'',$result_image);
        if ($img_64) {

            if ($debug)echo '<img style="width:100px" src="data:image/png;base64,'.$img_64.'" alt="'.$id.'">';

            sleep(1);
            $array_race = static::get_actor_race($img_64);
            return $array_race;
        }
        else
        {

            return array('error'=>'img file not found');
        }

    }

   public  function curl_save($actor_id, $url, $path = 'img_final')
   {
       $final_value = sprintf('%07d', $actor_id);
       $dir = ABSPATH . "analysis/" . $path . "/" . $final_value . ".jpg";

       $result = static::getCurlCookieface($url);
       if ($result!='Not Found')
       {
           file_put_contents($dir, $result);

           ///sync

           !class_exists('SyncHost') ? include ABSPATH . "analysis/include/SyncHost.php" : '';
           $path =  $path . "/" . $final_value . ".jpg";
           SyncHost::push_file_analysis($path);

           return 1;
       }
       else
       {
           return  0;
       }

   }

   public static function load_tmd_image($id)
   {
       $sql = "SELECT `profile_path` FROM `data_actors_tmdb`  WHERE actor_id ='" . $id . "'  LIMIT 1";


       $rows = Pdo_an::db_fetch_row($sql);
       if ($rows->profile_path) {
           $image = "https://www.themoviedb.org/t/p/w600_and_h900_bestv2" . ($rows->profile_path);
          /// echo $image;
           $image_add = KAIROS::check_image_on_server($id, $image, '_tmdb');
          /// echo '$image_add='.$image_add;
           ///try copy images
       }


       return $image_add;
   }


    public function   check_image_on_server($actor_id, $image = '', $tmdb = '')
{
//echo '$image='.$image;

    $final_value = sprintf('%07d', $actor_id);

    if ($tmdb) {
        $path = 'img_final'.$tmdb;
    } else {
    $path = 'img_final';

}


$dir = $_SERVER['DOCUMENT_ROOT'] . "/analysis/" . $path . "/" . $final_value . ".jpg";
if (file_exists($dir)) {
    //echo ' file already exists ';
    return 1;
} else if ($image) {
    ///add image

    $RS = static::curl_save($actor_id, $image, $path);

   // echo ' saved to /analysis/'.$path.'/' . $final_value . '.jpg ';
    return $RS;
}


return 0;


}

public static function add_actors_from_tmdb($id)
{
    ////not used
return;


//    ///try copy image from tmdb;
//    $final_value = sprintf('%07d',$id);
//    echo 'try copy image from tmdb<br>';
//
//    $gender = '';
//    $tmdb_id = '';
//    $image = '';
//    $image_add = '';
//
//    $url = "https://api.themoviedb.org/3/find/nm" . $final_value . "?api_key=1dd8ba78a36b846c34c76f04480b5ff0&language=en-US&external_source=imdb_id";
//    // echo $url.PHP_EOL;
//    $result = static::getCurlCookieface($url);
//    if ($result) {
//        $result = json_decode($result);
//        if ($result->person_results) {
//            $person = $result->person_results[0];
//
//            if ($person->profile_path) {
//                $image = "https://www.themoviedb.org/t/p/w600_and_h900_bestv2" . $person->profile_path;
//                $image_add =static::check_image_on_server($id, $image, 1);
//                ///try copy images
//
//            }
//            if ($person->id) {
//                $tmdb_id = $person->id;
//            }
//            if ($person->gender) {
//                $gender = $person->gender;
//
//            }
//
//            if ($tmdb_id) {
//                echo 'UPDATE '. $tmdb_id . ' ' . $gender . ' ' . $image.'<br>';
//
//                $sql1 = "UPDATE `data_actors_meta` SET
//                              `tmdb_id` = '" . intval($tmdb_id) . "',
//                              `tmdb_img` = '" . intval($image_add) . "',
//                              `gender` = '" . intval($gender) . "',
//                               `last_update` = ".time()."
//
//                   WHERE `data_actors_meta`.`actor_id` = '" . $id . "'";
//                Pdo_an::db_query($sql1);
//
//                ///set logs
//                !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';
//                ACTIONLOG::update_actor_log('tmdb_id');
//                ACTIONLOG::update_actor_log('tmdb_image');
//                ACTIONLOG::update_actor_log('gender');
//            }
//
//        }
//
//        if ($image_add==1)
//        {
//            $img_patch = $_SERVER['DOCUMENT_ROOT'] . "/analysis/img_final_tmdb/" . $final_value . ".jpg";
//            if (file_exists($img_patch))
//            {
//                return $img_patch;
//            }
//        }
//
//    }

}

    public function check_tmdb_image($id)
    {

        $final_value = sprintf('%07d',$id);
        $img_patch = $_SERVER['DOCUMENT_ROOT'] . "/analysis/img_final_tmdb/" . $final_value . ".jpg";
            if (file_exists($img_patch))
            {
                return $img_patch;
            }
    }


    public static function prepare_arrays($rows,$result_image='imdb')
    {
            global $debug;

            foreach ($rows as $row) {
                $error_message = array();

                $id = $row->id;

                if ($debug)echo $result_image.' id='.$id.'<br>';


                $kairos =  KAIROS::get_actor_id($id,$result_image);


                if ($debug)
                {
                    !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
                    TMDB::var_dump_table($kairos);
                }


                ///////////update kairos data
                if ($kairos)
                {
                    if (isset($kairos->Errors))
                    {
                        $error_message[$result_image] = json_encode($kairos->Errors);
                        $kairos=[];
                    }
                    if (isset($kairos['error']))
                    {
                        $error_message[$result_image] = $kairos['error'];
                        $kairos=[];
                        if ($debug) echo ($kairos['error']);
                    }

                }
                //var_dump(array($id,$kairos,$result_image,$error_message));


                static::save_data_to_db($id,$kairos,$result_image,$error_message);

                //return;

                if (function_exists('check_cron_time'))
                {
                    if (check_cron_time())break;
                }

            }
    }


    public  static function check_actors($id)
    {
global $debug;
        $kairos=[];
        $dop='';
        if ($id)
        {
            $dop = " and data_actors_imdb.id='".$id."' ";
        }


        //////requst update crowd data
        $sql="SELECT `data_actors_imdb`.id  FROM `data_actors_imdb` 
                LEFT JOIN data_actors_crowd_race ON data_actors_crowd_race.actor_id=data_actors_imdb.id
                LEFT JOIN data_actors_crowd ON data_actors_crowd.actor_id=data_actors_imdb.id
                WHERE (
                    data_actors_crowd.image IS NOT NULL and data_actors_crowd.status =1 and data_actors_crowd.loaded =1 
                    and data_actors_crowd_race.id IS NULL ) ".$dop." limit 10";
        $rows = Pdo_an::db_results($sql);
        self::prepare_arrays($rows,'crowd');


        if (function_exists('check_cron_time'))
        {
            if (check_cron_time())return;
        }



        //////requst update tmdb data
        $sql="SELECT `data_actors_imdb`.id  FROM `data_actors_imdb` 
                LEFT JOIN data_actors_tmdb_race ON data_actors_tmdb_race.actor_id=data_actors_imdb.id
                LEFT JOIN data_actors_tmdb ON data_actors_tmdb.actor_id=data_actors_imdb.id
                WHERE (
                    data_actors_tmdb.profile_path IS NOT NULL and data_actors_tmdb.status =1 
                    and data_actors_tmdb_race.id IS NULL ) ".$dop." limit 10";
        $rows = Pdo_an::db_results($sql);



        self::prepare_arrays($rows,'tmdb');

        if (function_exists('check_cron_time'))
        {
            if (check_cron_time())return;
        }



        ////default request for emtpy data
        $sql = "SELECT `data_actors_imdb`.id  FROM `data_actors_imdb` 
        LEFT JOIN data_actors_race ON data_actors_race.actor_id=data_actors_imdb.id
        WHERE (data_actors_race.id IS NULL and (`data_actors_imdb`.`image`= 'Y' OR `data_actors_imdb`.`image_url` IS NOT NULL) ) ".$dop." limit 30";
        if ($debug)echo $sql;


        $rows = Pdo_an::db_results($sql);

        self::prepare_arrays($rows,'imdb');


    }



public function get_verdict($kairos)
{

   $array_face = array('white' => 'W', 'hispanic' => 'H', 'black' => 'B',  'asian' => 'EA');

   arsort($kairos);
   $key = array_keys($kairos);
   $verdict =$key[0];
   if ($kairos[$verdict]!=0)
   {
   if (!$verdict)
   {$verdict='';
   }
   else
   {
       //echo '$verdict='.$verdict;
       $verdict =strtolower($verdict);

       $verdict =$array_face[$verdict];
      // echo '$verdict2='.$verdict;

   }
   }
   else
   {
       $verdict='';
   }



    return $verdict;
}
public static function save_data_to_db($id,$kairos,$result_image,$error_message)
{
    global $debug;

    if ($error_message)
    {
        $error_message  =json_encode($error_message);
    }
    else
    {
        $error_message='';
    }
    if ($kairos)
    {
        $verdict = static::get_verdict($kairos);
    }
    else
    {
        $verdict='';
    }

   //var_dump($kairos);

    $asian=0;
if ($kairos['asian']){  $asian=  $kairos['asian'];  }
    $black=0;
    if ($kairos['black']){  $black=  $kairos['black'];  }
    $hispanic=0;
    if ($kairos['hispanic']){  $hispanic=  $kairos['hispanic'];  }
    $white=0;
    if ($kairos['white']){  $white=  $kairos['white'];  }


    if ($result_image=='tmdb')
    {
        $table = 'data_actors_tmdb_race';
    }
    else if ($result_image=='crowd')
    {
        $table = 'data_actors_crowd_race';
    }
    else
    {
        $table ='data_actors_race';
    }

    $q ="SELECT * FROM `data_actors_race` WHERE`actor_id`=".$id;
    $r = Pdo_an::db_results_array($q);
    if ($r)
    {
        $sql="UPDATE `".$table."` SET `actor_id`=?,`Asian`=?,`Black`=?,`Hispanic`=?,`White`=?,`kairos_verdict`=?,`img_type`=?,`error_msg`=?,`last_update`=? WHERE `actor_id`=".$id;

        if ($debug)echo 'update<br>';
    }
    else
    {
        $sql ="INSERT INTO `".$table."` (`id`, `actor_id`, `Asian`, `Black`, `Hispanic`, `White`, `kairos_verdict`, `img_type`, `error_msg`, `last_update`) 
VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
        if ($debug)

        {
            echo 'insert<br>';
        }

    }
    $array_result = array($id,$asian,$black,$hispanic,$white,$verdict,$result_image,$error_message,time());
    if ($debug)

    {
        !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
        echo $sql.'<br>';
        TMDB::var_dump_table([$sql,$array_result]);
    }
    // var_dump($array_result);
    Pdo_an::db_results($sql,$array_result);

    ///commit
    !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';
    ACTIONLOG::update_actor_log('kairos_add',$table,$id);

    !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
    Import::create_commit('', 'update', $table, array('actor_id' => $id), 'kairos_race',9,['skip'=>['id']]);

}


}

//if (isset($_GET['update_table']))
//{
//
//    if ($_GET['update_table']=='add_new_rows_kairos') {
//        $sql = "ALTER TABLE `data_actors_race` ADD `kairos_verdict` VARCHAR(100) NOT NULL AFTER `White`, ADD `img_type` VARCHAR(10) NOT NULL AFTER `kairos_verdict`, ADD `error_msg` TEXT NOT NULL AFTER `img_type`, ADD `last_update` INT NOT NULL AFTER `error_msg`; ";
//        Pdo_an::db_query($sql);
//        $sql = "ALTER TABLE `data_actors_race` ADD INDEX(`kairos_verdict`);";
//        Pdo_an::db_query($sql);
//        $sql ="ALTER TABLE `data_actors_race` CHANGE `id` `id` INT(20) NOT NULL AUTO_INCREMENT, CHANGE `Asian` `Asian` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `Black` `Black` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `Hispanic` `Hispanic` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `White` `White` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `kairos_verdict` `kairos_verdict` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `img_type` `img_type` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `error_msg` `error_msg` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, CHANGE `last_update` `last_update` INT(11) NULL DEFAULT NULL; ";
//        Pdo_an::db_query($sql);
//        $sql="ALTER TABLE `data_actors_race` CHANGE `Asian` `Asian` FLOAT NULL DEFAULT NULL, CHANGE `Black` `Black` FLOAT NULL DEFAULT NULL, CHANGE `Hispanic` `Hispanic` FLOAT NULL DEFAULT NULL, CHANGE `White` `White` FLOAT NULL DEFAULT NULL; ";
//        Pdo_an::db_query($sql);
//    }
//
//}





