<?php
error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


/*
/content/locales/
/content/providers/
/content/titles/en_US/popular
*/


global $debug;

if (isset($_GET['debug'])) {
    if ($_GET['debug'] == 1) {
        $debug = 1;
    }
}
//$debug=1;

///get providers
function update_providers($array)
{
    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    }

    if (!class_exists('CriticFront')) {
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
    }
    !class_exists('CreateTsumbs') ? include ABSPATH . "analysis/include/create_tsumbs.php" : '';


    $cfront = new CriticFront();
    $ma = $cfront->get_ma();

    //print_r($array);

    foreach ($array as $key => $value) {
        $id = $key;
        $name = $value['n'];
        $img = $value['i'];
        //print "$id, $name, $img\n";

        CreateTsumbs::getThumbLocal_custom(100, 100, $img,$id,'providers_img');
        CreateTsumbs::getThumbLocal_custom(50, 50, $img,$id,'providers_img');

        $id = $ma->get_or_create_provider_by_pid($id, $name, $img);
        //print "add item - $id\n";
    }


}
function provider_img($id)
{
   $patch = WP_SITEURL .'/wp-content/uploads/thumbs/providers_img/100x100/'.$id.'.jpg';
  return $patch;
}
function get_providers_table()
{
    $array_providers =[];
    $sql = "SELECT * FROM `data_movie_provider` where status = 1";
    $rows = Pdo_an::db_results_array($sql);
    foreach ($rows as $val)
    {
        $i=provider_img($val['pid']);
        $array_providers[$val['pid']]=array('n'=>$val['name'],'i'=>$i);

    }
    return $array_providers;
}
function get_providers($force='')
{


    if (!$force) {
        $sql = "SELECT * FROM `options` where id = 15";
        $rows = Pdo_an::db_fetch_row($sql);
        $array_result = $rows->val;

        if ($array_result) {
            $providers = json_decode($array_result, 1);
            if ($providers['last_update'] > time() - 86400 * 7) {
                $providers_array = get_providers_table();
                return json_encode($providers_array);

            }
        }
    }


    $url = 'https://apis.justwatch.com/content/providers/locale/en_US';

    $array_result = [];


    $result = GETCURL::getCurlCookie($url);
    if ($result) {
        try {
            $object = json_decode($result);
        } catch (Exception $ex) {
            return false;
        }
    }

    //var_dump($object);

    foreach ($object as $i => $v) {
        $id = $v->id;
        $name = $v->clear_name;
        $img = $v->icon_url;
        $img = str_replace('{profile}', 's100', $img);
        $img = 'https://images.justwatch.com' . $img;



        $array_result[$id] = array('n' => $name, 'i' => $img);
    }

    if ($array_result) {


        update_providers($array_result);

        $providers = array('last_update' => time());

        $option = json_encode($providers);

        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
        OptionData::set_option(15,$option,'get_providers',1);


        $providers_array = get_providers_table();
        return json_encode($providers_array);
    }

}

/////get tmdb id
function get_just_wach($movie_id='',$force='')
{
    if (!$movie_id) {
        $movie_id = $_POST['id'];
    }
    $movie_id = intval($movie_id);

    global $debug;
    //$debug=1;

    $sql = "SELECT *  FROM `just_wach`  WHERE rwt_id = '{$movie_id}'  LIMIT 1";

    $r = Pdo_an::db_fetch_row($sql);
    $r_id = $r->id;
    $data = $r->data;
    $time = $r->addtime;
    $cache_time = 7;
    if ($debug)
    {
        $data='';
    }

    if  (($data && (time() - $cache_time * 8640) < $time  ) && !$force)
    {
       return $data;
    }
    else
        {
        ///try update cache


        if (!$movie_id) {
            if ($debug) echo 'movie id not found';
            return;
        }

        $array_type = array('TVSeries'=>'show','Movie'=>'movie');

        $sql = "SELECT * FROM `data_movie_imdb` where `id` ='" . $movie_id . "' limit 1 ";
        $r = Pdo_an::db_fetch_row($sql);

        $movie_title = $r->title;
        $year=$r->year;
        $date = $r->release;
        $tmdb_id = $r->tmdb_id;
        $movie_imdb = $r->movie_id;
        $movie_type = $r->type;

        if (!$tmdb_id) {
            !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';


            $tmdb_id = TMDB::get_tmdbid_from_imdbid($movie_imdb);
            if (!$tmdb_id) {
                if ($debug) echo 'tmdb id not found';
                return;
            }

            TMDB::update_tmdb_id($tmdb_id, $movie_imdb);
        }


        if ($date) {
            $date = strtotime($date);
            if (time() - 3600 * 24 * 300 < $date) {
                $cache_time = 7;
            } else {
                $cache_time = 30;
            }
        }
        if (($data && (time() - $cache_time * 8640) < $time) && !$force) {
         return $data;
        }

        if ($debug) echo 'add  <br>';
        $data_request = $data;

        $lang = $_GET['data'];
        if (!$lang) {
            $lang = 'en_US';
        }

        if ($movie_title) {
            if ($debug) echo 'movie_title=' . $movie_title . '<br>';
            $movie_title_request = urlencode($movie_title);


            $url = 'https://apis.justwatch.com/content/titles/' . $lang . '/popular?language=en&body={%22page_size%22:10,%22page%22:1,%22query%22:%22' . $movie_title_request . '%22,%22content_types%22:[%22show%22,%22movie%22]}';
            if ($debug) echo $url.'<br>';

            $resultcurl = GETCURL::getCurlCookie($url);

            if ($resultcurl) {
                try {
                    $object = json_decode($resultcurl);
                } catch (Exception $ex) {
                    return false;
                }
            }

            $i_result = 0;
            if ($tmdb_id != 0) {
                foreach ($object->items as $item_number => $item) {
                    foreach ($item->scoring as $i => $v) {

                        if ($v->provider_type == 'tmdb:id' && $v->value == $tmdb_id) {
                            $i_result = $item_number;
                            if ($debug) echo 'ok ' . $v->value . ' inumber = ' . $i_result;
                            $result = $object->items[$i_result]->offers;
                        }
                    }
                }
            }
            if (!$result)
            {
                !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';



                if ($debug){
                    echo 'echo try find on title and year '.$movie_title.' '.$year.' <br>';
                }



                ////check for title
                foreach ($object->items as $item_number => $item) {




                    $movie_year = $item->original_release_year;
                    $movie_title_find = $item->title;
                    $movie_type_tmdb = $item->object_type;

                    if ($debug)echo 'try '.$movie_title_find.' ' .$movie_year.' '.$movie_type.' <br>';

                    if ($debug) {

                    echo $movie_type_tmdb.'=='.$array_type[$movie_type].' '.$year.'='.$movie_year.' ; '.TMDB::replace_movie_text($movie_title_find).'=='.TMDB::replace_movie_text($movie_title).'<br>';
                    }

                    if ($year && strstr($movie_year, $year)  &&  $movie_type_tmdb==$array_type[$movie_type]  && TMDB::replace_movie_text($movie_title_find) ==TMDB::replace_movie_text($movie_title) ) {
                        $i_result = $item_number;
                        $result = $object->items[$i_result]->offers;

                        if ($debug) echo 'found it<br>';
                        break;
                    }

                    }


               // return;
            }
            if ($debug && !$result)  echo  'not found movie<br> ';

            if ($result) {

                $providers = get_providers();



                if ($providers) {
                    $providers_object = json_decode($providers);
                }
                $arrays_id = [];

                foreach ($result as $value) {
                    $provider_id = $value->provider_id;
                    $arrays_id[$provider_id] = $providers_object->{$provider_id};
                }

                $regv='#(https\:\/\/watch\.amazon\.com\/detail\?[\.a-z\=\-A-Z0-9]+)(&tag=.+)*#';

                foreach ($result as $item => $value) {

                    foreach ($value->urls as $iu => $iv) {
                        //var_dump($iv);
                        if (strstr($iv, 'justwatch09-20')) {
                            $iv = str_replace('justwatch09-20', 'stfuhollywo0a-20', $iv);
                            $result[$item]->urls->{$iu} = $iv;
                        }
                        else if (strstr($iv,'amazon.com'))
                        {
                            $iv=  preg_replace($regv,'$1&tag=stfuhollywo0a-20',$iv);

                            $result[$item]->urls->{$iu} = $iv;
                        }

                    }

                }
                // var_dump($result);
            }
            else {
                $result = '';
                $arrays_id=[];

            }

            if ($result || (!$result && !$data_request)) {


                 $data = json_encode(array('data' => $result, 'providers' => $arrays_id));

                if (!$result) {
                    $addtime = 0;
                } else {
                    $addtime = time();
                }

                $sql = "SELECT *  FROM `just_wach`  WHERE rwt_id = {$movie_id}  LIMIT 1";
                $r = Pdo_an::db_fetch_row($sql);
                $r_id = $r->id;

                if ($r_id) {




                    if ($debug) echo 'update ' . $r_id . ' <br>';

                    $sql = "UPDATE `just_wach` SET `tmdb_id` = '{$tmdb_id}', `rwt_id` = '{$movie_id}',
                            `title` = ?, `data` = ?, `addtime` = '" . $addtime . "' WHERE `just_wach`.`id` = {$r_id}; ";

                    if ($debug) echo $sql . '<br>';
                    Pdo_an::db_results_array($sql,array($movie_title, $data));


                } else {
                    $sql = "INSERT INTO `just_wach` (`id`, `tmdb_id`, `rwt_id`, `title`, `data`, `addtime`) 
                VALUES (NULL, {$tmdb_id}, {$movie_id}, ?, ?, " . $addtime . ");";


                    if ($debug) echo $sql;

                    Pdo_an::db_results_array($sql,array($movie_title, $data));

                }
                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'just_wach', array('rwt_id' => $movie_id), 'just_wach',10,['skip'=>['id']]);


                check_watch($movie_id, $tmdb_id, $data);

                return $data;
            }


            else if (!$result && $data_request) {

                if ($time == 0) {
                    if ($debug) echo 'enable data_request, is null<br>';
                    return '';
                }

                if ($debug) echo 'enable data_request<br>';
                return $data_request;
            }
        } else {
            if ($debug) echo $tmdb_id . ' movie title not found <br>';
            return '';
        }
    }


}

//echo get_providers();
//return;
/////get tmdb id
//echo get_just_wach();


//$providers = get_providers();


$lang = $_POST['language'];
if (!$lang) {
    $lang = 'en_US';
}


///get release date
//echo ' cache_time '.$cache_time;

if (isset($_GET['add_providers']))
{
    set_time_limit(0);
    $data =get_providers(1);
    echo json_encode($data);
    return;
}

if (isset($_GET['force']))
{
    $force = 1;
}

$result = get_just_wach('',$force);
$result =piracy_links($result);
echo $result;
return;


function piracy_links($data){
  ///0:Movies and TV;1:All;2:Games;3:Music;4:Books;5:Movies;6:TV;
    $array_type=['VideoGame' =>[1,2], 'TVSeries'=>[0,1,6],'Movie'=>[0,1,5]];

    $pay_cat =[0=>'"Free"',1=>'Free',2=>'Irl',3=>'Rent',4=>'Buy'];

    if ($data)
    {
        $data_ob = json_decode($data,1);
    }
    else
    {
        $data_ob = [];
    }
    $movie_id = $_POST['id'];
    $movie_id = intval($movie_id);
    $sql = "SELECT `title`, `year`, `type` FROM `data_movie_imdb` where `id` ='" . $movie_id . "' limit 1 ";
    $r = Pdo_an::db_fetch_row($sql);
    $movie_title = $r->title;
    $year = $r->year;
    $type= $r->type;

    $where='';
    if ($array_type[$type] ) {
        foreach ($array_type[$type] as $v) {

                $where .= "OR `type` = " . $v . " ";


        }
        if ($where) {
            $where = " AND (" . substr($where, 3) . ")";
        }
    }
    else {$where = " AND (`type`=0 OR `type`=1) ";}

    $sql = "SELECT * FROM `meta_piracy_links` where `enable` =1 ".$where." order by `category` desc";


    $r = Pdo_an::db_results_array($sql);
    foreach ($r as $row)
    {

        $url  = $row['search_query'];
        $category  = $row['category'];
        $namecat = $pay_cat[$category];

        $include_year = $row['include_year'];
        if ($include_year)
        {
            $movie_title_encoded = urlencode($movie_title.' '.$year);
        }
        else
        {
            $movie_title_encoded = urlencode($movie_title);
        }

        $url = str_replace('$',$movie_title_encoded,$url);

        $data_ob['data'][]= [
        'monetization_type'=> $namecat,
        'provider_id'=> 'p_'.$row['id'],
        'currency'=>  '',
        'retail_price'=>  '',
        'presentation_type'=>  '',
        'urls'=>  ['standard_web'=>  $url]
    ];

        $data_ob['providers']['p_'.$row['id']] = ['s'=> 'fullsize', 'n'=> $row['name'], 'i'=> $row['logo_url']];
    }

return json_encode($data_ob);

}


function add_provider($movie_id, $tmdb_id, $provider)
{

    $sql = "INSERT INTO `cache_just_wach` (`id`, `rwt_id`, `tmdb_id`, `provider`) 
                VALUES ( NULL, " . $movie_id . ", " . $tmdb_id . "," . intval($provider) . ");";
    Pdo_an::db_query($sql);
}

function check_watch($movie_id, $tmdb_id, $data)
{
////check enable cache movie
    $array_provider = [];

    $data_array = json_decode($data, true);

    foreach ($data_array['data'] as $index => $value) {
        $provider_id = $value['provider_id'];
        $array_provider[$provider_id] = 1;

    }
    if (is_array($array_provider)) {
        ////delete lasr value

      if ($movie_id) {
            $w = "rwt_id ='" . intval($movie_id) . "'";
        }
        else if ($tmdb_id) {
            $w = "tmdb_id ='" . intval($tmdb_id) . "'";
        }

        if ($w) {

            $sql = "DELETE FROM `cache_just_wach` WHERE  " . $w;
            Pdo_an::db_query($sql);
        }
        foreach ($array_provider as $provider => $enable) {
            add_provider($movie_id, $tmdb_id, $provider);
        }
    }

}
