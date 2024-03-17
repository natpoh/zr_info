<?php

if (!defined('ABSPATH')) define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';

class JustWatch
{
    public static function just_watch_api_request($title, $year,$country='US',$language='en')
    {


        $url = 'https://apis.justwatch.com/graphql';
        $data = array(
            'operationName' => 'GetSuggestedTitles',

            'variables' => array(
                'country' =>$country,
                'language' => $language,
                'first' => 4,
                'filter' => array(
                    'searchQuery' => $title . ' (' . $year . ')'
                )
            ),
            'query' => 'query GetSuggestedTitles($country: Country!, $language: Language!, $first: Int!, $filter: TitleFilter) {
popularTitles(country: $country, first: $first, filter: $filter) {
edges {
node {
...SuggestedTitle
__typename
}
__typename
}
__typename
}
}

fragment SuggestedTitle on MovieOrShow {
id
objectType
objectId
content(country: $country, language: $language) {
fullPath
title
originalReleaseYear
posterUrl
fullPath
__typename
}
watchNowOffer(country: $country, platform: WEB) {
id
standardWebURL
package {
id
packageId
__typename
}
__typename
}
offers(country: $country, platform: WEB) {
monetizationType
presentationType
currency
retailPrice(language: $language)
standardWebURL
package {
id
packageId
__typename
}
id
__typename
}
__typename
}'
        );

        $data_string = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Host: apis.justwatch.com',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
            'Accept: */*',
            'Accept-Language: en',
            'Accept-Encoding: deflate, br',
            'Referer: https://www.justwatch.com/',
            'Content-Type: application/json',
            'App-Version: 3.8.2-web-web',
            'DEVICE-ID: Zpb6X-NkEe6V8_KxF8LQWQ',
            'Content-Length: ' . strlen($data_string),
            'Origin: https://www.justwatch.com',
            'Connection: keep-alive',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-site',
            'TE: trailers'
        ));

        $response = curl_exec($ch);
        curl_close($ch);
        $encoded_data = json_decode($response, 1);

        if ($encoded_data)
        {
            $result_data = $encoded_data['data']['popularTitles']['edges'];
        }
        if ($result_data)
        {
            return $result_data;
        }
        else
        {
            return array('error'=>1,'message'=>'request error' ,'data'=>$response);
        }


    }


    public static function update_providers($array)
    {
        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        }

        if (!class_exists('CriticFront')) {
            require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
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

            CreateTsumbs::getThumbLocal_custom(100, 100, $img, $id, 'providers_img');
            CreateTsumbs::getThumbLocal_custom(50, 50, $img, $id, 'providers_img');

            $id = $ma->get_or_create_provider_by_pid($id, $name, $img);
//print "add item - $id\n";
        }


    }

    public static function provider_img($id)
    {
        $patch = '/wp-content/uploads/thumbs/providers_img/100x100/' . $id . '.jpg';
        return $patch;
    }

    public static function get_providers_table()
    {
        $sql = "SELECT * FROM `data_movie_provider` where status = 1";
        $rows = Pdo_an::db_results_array($sql);
        foreach ($rows as $val) {

            $i = self::provider_img($val['pid']);
            $array_providers[$val['pid']] = array('n' => $val['name'], 'i' => $i);
        }

        ///check providers



        return $array_providers;
    }

    public static function get_providers($force = '')
    {


        if (!$force) {
            $sql = "SELECT * FROM `options` where id = 15";
            $rows = Pdo_an::db_fetch_row($sql);
            $array_result = $rows->val;

            if ($array_result) {
                $providers = json_decode($array_result, 1);
                if ($providers['last_update'] > time() - 86400 * 7) {
                    $providers_array = self::get_providers_table();
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

        global $debug;

        if ($debug) {
            !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
            TMDB::var_dump_table(['providers array_result', $array_result]);
        }

        if ($array_result) {


            self::update_providers($array_result);

            $providers = array('last_update' => time());

            $option = json_encode($providers);

            !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
            OptionData::set_option(15, $option, 'get_providers', 1);


//            $providers_array = self::get_providers_table();
//            return json_encode($providers_array);
        }

    }

    public static function save_to_db($movie_id,$found,$movie_title,$status,$country='US')
    {


        $providers_array = [];
        $data_array=[];

        $regv = '#(https\:\/\/watch\.amazon\.com\/detail\?[\.a-z\=\-A-Z0-9]+)(&tag=.+)*#';

     global $debug;

     if ($status==1) {

///{"jw_entity_id":"ts58048","type":"aggregated","monetization_type":"flatrate","provider_id":9,"currency":"USD","urls":{"standard_web":"https:\/\/watch.amazon.com\/detail?asin=B073SGCYZS","deeplink_web":"https:\/\/watch.amazon.com\/watch?asin=B073SGCYZS"},"presentation_type":"hd","element_count":1,"new_element_count":1,"country":"US"},
//            'monetization_type': "irl",
//            'provider_id': 'showtimes',
//            'presentation_type': '',
//            urls: {'standard_web': 'https://www.showtimes.com/Search?query=' + title}

          //  TMDB::var_dump_table($found);

            foreach ($found['offers'] as $i => $data) {

             $pid = $data['package']['packageId'];
             if (!in_array($pid, $providers_array)) {
                 $providers_array[] = $pid;
             }

                $data_array[$i]=[
                    'monetization_type'=>strtolower($data['monetizationType']),
                    'provider_id'=>$data['package']['packageId'],
                    'presentation_type'=>strtolower(trim($data['presentationType'],'_')),
                    'urls'=>['standard_web'=>$data['standardWebURL']],
                    'currency'=>$data['currency'],
                    'retail_price'=>$data['retailPrice'],
                    'country'=>$country
                    ];

             $offers_url = $data['standardWebURL'];

             if (strstr($offers_url, 'justwatch09-20')) {
                 $offers_url = str_replace('justwatch09-20', 'stfuhollywo0a-20', $offers_url);
                 $found['offers'][$i]['standardWebURL']=$offers_url;

             } else if (strstr($offers_url, 'amazon.com')) {
                 $offers_url = preg_replace($regv, '$1&tag=stfuhollywo0a-20', $offers_url);
                 $found['offers'][$i]['standardWebURL']=$offers_url;
             }

         }
     }

     $all_providers =self::get_providers_table();


     $providers_result = [];

        if ($providers_array)
     {
         foreach ($providers_array as $provider)
         {
             if ($all_providers[$provider])
             {
                 $providers_result[$provider]=$all_providers[$provider];

             }
             else
             {
                 if ($debug) echo 'provider ' . $provider . ' not found  <br>';
                 ///update providers

                 $providers = array('last_update' => 0);
                 $option = json_encode($providers);
                 !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
                 OptionData::set_option(15, $option, 'get_providers', 1);
             }


         }
     }
        $result_array =['data'=>$data_array,'providers'=>$providers_result];

    ////update
        $raw_data_string = json_encode($found);
        $data_string = json_encode($result_array);

        $sql = "SELECT id  FROM `just_wach`  WHERE rwt_id = {$movie_id}  LIMIT 1";
        $r = Pdo_an::db_fetch_row($sql);
        $r_id = $r->id;

        if ($r_id) {

            if ($debug) echo 'update ' . $r_id . ' <br>';
            $sql = "UPDATE `just_wach` SET `title`=?,`data`=?,`raw_data`=?,`last_update`=?,`status`=?  WHERE  `just_wach`.`id` = ? ";
            Pdo_an::db_results_array($sql, array($movie_title, $data_string,$raw_data_string,time(),$status,$r_id));


        } else {

            $sql="INSERT INTO `just_wach`(`id`, `tmdb_id`, `rwt_id`, `title`, `data`, `add_time`, `raw_data`, `last_update`, `status`)
                    VALUES (NULL,0,?,?,?,?,?,?,?)";
            if ($debug) echo $sql;
            Pdo_an::db_results_array($sql, array($movie_id,$movie_title,$data_string,time(),$raw_data_string,time(),$status));

        }
        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'update', 'just_wach', array('rwt_id' => $movie_id), 'just_wach', 10, ['skip' => ['id']]);


        if ($providers_array)
        {
            self::check_watch($movie_id,  $providers_array);
        }


        return $result_array;
    }



    public static function prepare_results($result,$mtitle='',$myear='',$movietype='Movie')
    {

        global $debug;
        $array_type = array('TVSeries' => 'SHOW', 'Movie' => 'MOVIE');

            foreach ($result as $item_number => $item) {

               $content =  $item['node']['content'];
               $title = $content['title'];
               $year =$content['originalReleaseYear'];
               $type = $item['node']['objectType'];



                if (($myear && $year && $myear==$year) || (!$myear || !$year) )
                {
                    if ($array_type[$movietype]==$type) {
                        if (TMDB::replace_movie_text($title) == TMDB::replace_movie_text($mtitle)) {
                            return $item['node'];
                        }
                        else
                        {
                            if ($debug)
                            {
                                TMDB::var_dump_table(['title not match'=>$content]);
                            }
                        }
                    }
                    else
                    {
                        if ($debug)
                        {
                            TMDB::var_dump_table(['type not match'=>$type,'data'=>$content]);
                        }
                    }
                }
                else
                {
                if ($debug)
                {
                    TMDB::var_dump_table(['year not match'=>$content]);
                }
                }
            }
        if ($debug)
        {
            TMDB::var_dump_table(['not found'=>$result]);
        }
      return array('error'=>1,'message'=>'not found' ,'data'=>json_encode($result));
    }

    public static function get_just_wach($movie_id = 0, $force = 0, $lang = 'en',$country='US',$cache_time=1)
    {
        $movie_id = intval($movie_id);
        global $debug;

        if (!$movie_id) {
            if ($debug) echo 'movie id not found';
            return [];
        }

        $sql = "SELECT *  FROM `just_wach`  WHERE rwt_id = '{$movie_id}'  LIMIT 1";

        $r = Pdo_an::db_fetch_row($sql);
        $title_cache = $r->title;
        $data_r = $r->data;
        $time_cache = $r->last_update;
        $time_cache_h = date('d.m.Y',$time_cache);
        $status_cache = $r->status;

        if ($data_r)
        {
            $data_cache =  json_decode($data_r,1);
        }


        if ($debug) {
            $data_cache = [];
        }

        if ($status_cache ==1 && ( $data_cache && (time() - $cache_time * 8640) < $time_cache) && !$force && $cache_time>0) {
            $data_cache['status'] = 'cache';
            $data_cache['update'] = $time_cache_h;
            return $data_cache;

        } 
        
        else {
///try update

            $sql = "SELECT * FROM `data_movie_imdb` where `id` ='" . $movie_id . "' limit 1 ";
            $r = Pdo_an::db_fetch_row($sql);

            $movie_title = $r->title;
            $year = $r->year;
            $movie_type = $r->type;

            if ($debug) echo 'try add '.$movie_title.' '.$year.' '.$movie_type.' <br>';

            if ($movie_title) {
                if ($debug) echo 'movie_title=' . $movie_title . '<br>';


                $result = self::just_watch_api_request($movie_title, $year,$country,$lang);

                if ($result['error']==1)
                {
                    if ($debug)
                    {
                        TMDB::var_dump_table($result);
                    }
                ///update last time
                self::save_to_db($movie_id,$result,$movie_title,2,$country);
                    $data_cache['status'] = 'error 1, old cache';
                    $data_cache['update'] = $time_cache_h;
                    return $data_cache;
                }

                if ($result)
                {
                 $found =  self::prepare_results($result,$movie_title,$year,$movie_type);
                }




                if ($found['error']==1)
                {
                    if ($debug)
                    {
                        TMDB::var_dump_table($found);
                    }
                    ///update last time
                    self::save_to_db($movie_id,$found,$movie_title,2);
                    $data_cache['status'] = 'error 2, old cache';
                    $data_cache['update'] = $time_cache_h;
                    return $data_cache;
                }

                ///prepare data

                if ($found)
                {
                    $data = self::save_to_db($movie_id,$found,$movie_title,1);
                    $data['status'] = 'update';
                    $data['update'] =  date('d.m.Y',time());
                    return $data;
                }

            }
            else {
                self::save_to_db($movie_id,['error'=>1,'message'=>'movie title not found' ],'',2);

                if ($debug) echo  ' movie title not found <br>';
                $data_cache['status'] = 'error 3, old cache';
                $data_cache['update'] = $time_cache_h;
                return $data_cache;
            }

        }

    }

    public static function piracy_links($data)
    {
        ///0:Movies and TV;1:All;2:Games;3:Music;4:Books;5:Movies;6:TV;
        $array_type = ['VideoGame' => [1, 2], 'TVSeries' => [0, 1, 6], 'Movie' => [0, 1, 5]];

        $pay_cat = [0 => '"Free"', 1 => 'Free', 2 => 'Irl', 3 => 'Rent', 4 => 'Buy'];

        if ($data) {
            $data_ob = json_decode($data, 1);
        } else {
            $data_ob = [];
        }
        $movie_id = $_POST['id'];
        $movie_id = intval($movie_id);
        $sql = "SELECT `title`, `year`, `type` FROM `data_movie_imdb` where `id` ='" . $movie_id . "' limit 1 ";
        $r = Pdo_an::db_fetch_row($sql);
        $movie_title = $r->title;
        $year = $r->year;
        $type = $r->type;

        $where = '';
        if ($array_type[$type]) {
            foreach ($array_type[$type] as $v) {

                $where .= "OR `type` = " . $v . " ";


            }
            if ($where) {
                $where = " AND (" . substr($where, 3) . ")";
            }
        } else {
            $where = " AND (`type`=0 OR `type`=1) ";
        }

        $sql = "SELECT * FROM `meta_piracy_links` where `enable` =1 " . $where . " order by `category` desc, `name` asc";


        $r = Pdo_an::db_results_array($sql);
        foreach ($r as $row) {

            $url = $row['search_query'];
            $category = $row['category'];
            $namecat = $pay_cat[$category];

            $include_year = $row['include_year'];
            if ($include_year) {
                $movie_title_encoded = urlencode($movie_title . ' ' . $year);
            } else {
                $movie_title_encoded = urlencode($movie_title);
            }

            $url = str_replace('$', $movie_title_encoded, $url);

            $data_ob['data'][] = [
                'monetization_type' => $namecat,
                'provider_id' => 'p_' . $row['id'],
                'currency' => '',
                'retail_price' => '',
                'presentation_type' => '',
                'urls' => ['standard_web' => $url]
            ];

            $data_ob['providers']['p_' . $row['id']] = ['s' => 'fullsize', 'n' => $row['name'], 'i' => $row['logo_url']];
        }

        return json_encode($data_ob);

    }



    public static function delete_provider($id,$movie_id,  $provider)
    {
        $sql = "DELETE FROM `cache_just_wach` WHERE `id`  = ".$id;
        Pdo_an::db_query($sql);
        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'delete', 'cache_just_wach', array('rwt_id' => $movie_id,'provider' => $provider), 'cache_just_wach', 15, ['skip' => ['id']]);

    }

    public static function add_provider($movie_id,  $provider)
    {
        $sql = "INSERT INTO `cache_just_wach` (`id`, `rwt_id`,  `provider`, `last_update`) 
                VALUES ( NULL, ?, ?, ? )";
        Pdo_an::db_results_array($sql,[$movie_id,$provider,time()]);
        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'update', 'cache_just_wach', array('rwt_id' => $movie_id,'provider' => $provider), 'cache_just_wach', 15, ['skip' => ['id']]);
    }

    public static function check_watch($movie_id,  $array_providers=[])
    {
////check enable cache movie
        global $debug;
        if (is_array($array_providers)) {

                $sql = "SELECT * FROM `cache_just_wach` WHERE rwt_id ='" . intval($movie_id) . "'";
                $r = Pdo_an::db_results_array($sql);

                $array_enable =[];

                foreach ($r as $val)
                {
                    $provider = $val['provider'];

                    if (in_array($provider,$array_providers))
                    {
                        //skip
                        $array_enable[]=$provider;
                    }
                    else
                    {
                        ///delete provider
                        self::delete_provider($val['id'],$movie_id, $provider);
                    }

                }

            foreach ($array_providers as $provider ) {

                if (!in_array($provider,$array_enable))
                {
                    self::add_provider($movie_id, $provider);
                }

            }
        }

    }
}