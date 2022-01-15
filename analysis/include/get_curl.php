<?php
if ( !defined('ABSPATH') )
	define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

class GETCURL
{

   public static function getCurlCookie($url = '', $proxy = false, $post='', $headers='')
    {
        $cookiePath = ABSPATH . 'wp-content/uploads/cookies.txt';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);


        if ($proxy) {
            $proxy = '127.0.0.1:8118';

            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }


        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        if ($cookiePath) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath);
        }

        if (strstr($url, 'https')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ($post) {
               curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        //  curl_setopt($ch, CURLOPT_TIMEOUT, 2000);
        curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');

        $response = curl_exec($ch);        
        curl_close($ch);
        return $response;
    }
}