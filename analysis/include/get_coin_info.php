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

    public static function get_options($id)
    {

        $sql = "SELECT val FROM `options` where id = " . $id;
        $rows = Pdo_an::db_fetch_row($sql);
        $last_id = $rows->val;

        if (!$last_id) $last_id = 0;
        return $last_id;
    }

    public static function set_option($id, $option)
    {
        if ($option && $id) {

            $sql = "DELETE FROM `options` WHERE `options`.`id` = " . $id;
            Pdo_an::db_query($sql);

            $sql = "INSERT INTO `options`  VALUES ('" . $id . "',?)";
            Pdo_an::db_results_array($sql,array($option));
        }
    }




    public static function get_request()
    {
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
        return $result;
    }
}

$data = GETCOINS::get_request();
echo $data;