<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


require_once(ABSPATH. 'wp-load.php' );


//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//Curl


!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';


class GETTWITTER{



    public static function get_url($id)
    {
        $sql = "SELECT * FROM `data_movie_imdb` where `id` ='" . $id . "' limit 1 ";
        $r = Pdo_an::db_fetch_row($sql);

        $movie_title = $r->title;
        $year=$r->year;

        $verifed = 'filter:verified lang:en ';

        $request = '"'.$movie_title.' '.$year.'"'.$verifed;

        //$request =  http_build_query($request);
        echo $request;

        $title = array('type'=>'search','content'=>$request);

        $atts =array('search'=> '"'.$movie_title.' '.$year.'"');

        $content = ctf_init( $atts );


        echo $content;

        ?>
<link rel='stylesheet' type="text/css" src="<?php echo WP_SITEURL;?>/wp-content/plugins/custom-twitter-feeds-pro/css/ctf-styles.min.css"/>

<?php

        return;

     //   $url = 'https://smashballoon.com/custom-twitter-feeds/demo/?'.$data;

        $url ='https://smashballoon.com/wp-admin/admin-ajax.php';



        $array_request = json_encode(array('search'=>$request));

        $post = array('action'=>'ctf_get_more_posts',
            'shortcode_data'=>'"{\"search\":+\"The+Matrix\"}"',
            'num_needed'=>0,
            'persistent_index'=>1
            );
        var_dump($post);
        $data =  http_build_query($post);

        $result  =GETCURL::getCurlCookie($url,'',$data);
        echo $result;

       // echo $url;


    }





}
