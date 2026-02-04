 <?php
 set_time_limit(0);
 if (!defined('ABSPATH'))
     define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

 //DB config
 !defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
 //Abstract DB
 !class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
 //Curl


 !class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';


class GETCOINS
{


    public static function get_coins_template($data)
    {
        $data_obj = json_decode($data['data'],1);
        $summ = $data_obj['fiatAmount'];
        $recipient = $data_obj['nameRecipient'];
        $sender = $data_obj['nameSender'];
        $text =$data_obj['text'];
        $cryptoCurrency =$data_obj['cryptoCurrency'];


        $header = '$'.$summ.' in '.$cryptoCurrency.' to <b>'.$recipient.'</b>';

        if ($text)
        {
            $text =str_replace('\n','<br>',$text);

            $text = '<div class="coins_text_content">'.$text.'</div>';
        }
        if ($sender)
        {
            $sender = '<div class="coins_sender">-<b>'.$sender.'</b></div>';
        }


       $template ='<div class="coins_container"><div class="coins_header">'.$header.'</div><div class="coins_content">'.$text.$sender.'</div></div>';

        return $template;
    }

    public static function front()
    {
        $content ='';

        $q = "SELECT * FROM `cache_donations` order by id limit 20";
        $r = Pdo_an::db_results_array($q);
        foreach ($r as $data )
        {
            $content.=self::get_coins_template($data);
        }

        return $content;

    }


    public static function get_options($id)
    {



        $sql = "SELECT val FROM `options` where id = " . $id;
        $rows = Pdo_an::db_fetch_row($sql);
        $last_id = $rows->val;

        if (!$last_id) $last_id = 0;
        return $last_id;
    }



    public static function get_request()
    {
        set_time_limit(0);

        $request = array(
            'count' => "2",
            'ofs' => "0",
            'req0___data__' => '{"database":"projects/test-428fb/databases/(default)","addTarget":{"query":{"structuredQuery":{"from":[{"collectionId":"messages"}],"where":{"fieldFilter":{"field":{"fieldPath":"public"},"op":"EQUAL","value":{"booleanValue":true}}},"orderBy":[{"field":{"fieldPath":"created"},"direction":"DESCENDING"},{"field":{"fieldPath":"__name__"},"direction":"DESCENDING"}],"limit":10000},"parent":"projects/test-428fb/databases/(default)/documents"},"targetId":2}}'
        );

        $request = http_build_query($request);

        $url = "https://firestore.googleapis.com/google.firestore.v1.Firestore/Listen/channel?database=projects%2Ftest-428fb%2Fdatabases%2F(default)&VER=8&RID=34057&CVER=22&X-HTTP-Session-Id=gsessionid&%24httpHeaders=X-Goog-Api-Client%3Agl-js%2F%20fire%2F8.2.2%0D%0AContent-Type%3Atext%2Fplain%0D%0A&t=1";

        $headers = [
            'Host: firestore.googleapis.com',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:97.0) Gecko/20100101 Firefox/97.0',
            'Accept: */*',
            'Accept-Language: ru',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/x-www-form-urlencoded',
            'Connection: keep-alive',
            'Referer: https://cointr.ee/',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: cross-site',
            'TE: trailers'


        ];

        $result = GETCURL::getCurlCookie($url, '', $request, $headers, 1);
///var_dump($result);

        if ($result) {


            $reg = '#\["c"\,"([^"]+)#';

            if (preg_match($reg, $result, $mach)) {

                $code = trim($mach[1]);
            }
            $regv2 = '#d: (.+)#';
            if (preg_match($regv2, $result, $mach)) {

                $sid = trim($mach[1]);
            }


        }

        $url1 = 'https://firestore.googleapis.com/google.firestore.v1.Firestore/Listen/channel?database=projects%2Ftest-428fb%2Fdatabases%2F(default)&gsessionid=' . $sid . '&VER=8&RID=rpc&SID=' . $code . '&CI=0&AID=0&TYPE=xmlhttp&t=1&read_time=1000';


        $result = GETCURL::getCurlCookie($url1, '', '', $headers);

        $result_ARRAY = explode(']]]',$result);


        //var_dump($result);

        $array_result=[];
        foreach ($result_ARRAY as $r)
        {

            if (strstr($r,'rightwingtomato'))
            {
            ///   var_dump($r);


            $reg_v='/"([a-zA-Z]+)": \{[^"]+"([a-zA-Z]+)": "([^"]+)"/Uis';
            $reg_num ='/([0-9]+)\,\[\{/';

                $num='';

            if (preg_match($reg_num,$r,$mnum)){
                $num = $mnum[1];
            }
                echo 'num:'.$num.PHP_EOL;

            if (preg_match_all($reg_v,$r,$mach))
            {
                foreach ($mach[1] as $index=>$value)
                {
                   $array_result[$num][$value]=$mach[3][$index];

                }
            }
            }
        }
        if ($array_result)
        {


            self::check_results($array_result);
        }
        else
        {
            echo 'not found rightwingtomato, lastnum='.$num;
        }


       // return $array_result;

    }


    public static function check_results($object)
    {
        foreach ($object as $index=>$data)
        {
         ///   var_dump($data);

           if  (  $data["nameRecipient"]=='rightwingtomato' && $data["confirmations"]==2)
           {
               echo 'check '.$index.' ok ;'.PHP_EOL;
               self::add_to_db($index,$data);
           }
           else
           {
               echo 'not enable '.$index.' '.$data["nameRecipient"].'; '.PHP_EOL;
           }

        }
    }
    public static function add_to_db($index,$data)
    {


        $uq = md5($data["document"]. $data["cryptoCurrency"].
        $data["uidSender"].
        $data["created"].
        $data["cryptoAmount"].
        $data["text"]);


       $sql =  "SELECT * FROM `cache_donations` WHERE `uniq_id`='".$uq."' ";
       $r = Pdo_an::db_fetch_row($sql);
       if (!$r)
       {
           $sql  ="INSERT INTO `cache_donations`(`id`, `uniq_id`, `data`, `add_time`) 
                VALUES (NULL,?,?,?)";

           $array = [$uq,json_encode($data),time()];

           Pdo_an::db_results_array($sql,$array);

           !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
           Import::create_commit('', 'update', 'cache_donations', array('uniq_id' => $uq), 'donations',15,['skip'=>['id']]);

       }
       else
       {
           echo  $uq.' already adedded<br>';
       }

    }
}


///$data = GETCOINS::get_request();
