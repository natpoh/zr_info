<?php
set_time_limit(0);
ini_set('display_errors', 'On');
error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


class STRUCTURELIST{

    private static $cf=false;

    public static function get_movie_data($movie_id='')
    {

        $sql = sprintf("SELECT * FROM data_movie_imdb WHERE id=%d", (int) $movie_id);
        $post = Pdo_an::db_fetch_row($sql);

        $thumbs = array([440, 660]);
        $array_tsumb = CreateTsumbs::get_poster_tsumb_fast($movie_id, $thumbs);
        $image = $array_tsumb[0];
        if ($image)
        {
         $array['image']=$image;
        }

        $array['@type']='Movie';
        $name =$post->post_name;
        if ($name)
        {
            $url = WP_SITEURL.'/'.$name;
            $array['url']=$url;
        }


        $array['name']=$post->title;

        $release = $post->release;
        if ($release)
        {
            $array['dateCreated']=$release;
        }
        $array_directors =  self::get_current_meta('meta_movie_director',$movie_id);

        if ($array_directors)
        {

            $director = $array_directors[0]["aid"];
            if ($director)
            {
                $director_name = self::get_actor_name($director);

                if ($director_name)
                {
                    $array['director']["@type"] = "Person";
                    $array['director']['name']=$director_name;
                }
            }
        }
        $rating = self::get_rwt_rating($movie_id);

        $count = self::get_rating_count($movie_id);
        if (!$count)
        {
            $count=1;
        }


        if ($rating>0)
        {
            $array['aggregateRating'] = array(
               "@type"=> "AggregateRating",
               "ratingValue"=> $rating,
                "bestRating"=> "5",
                "ratingCount"=> $count

            );
        }

        ////reviews
        $reviews = self::get_movie_critics($movie_id);

        if ($reviews)
        {
         $array['review']=$reviews;
        }

        return $array;

    }
    public static function get_movie_critics($movie_id)
    {
        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
        }
        if (!self::$cf)
        {
            self::$cf = new CriticFront();
        }

        $array_reviews = [];
        $count=3;
        $results = self::$cf->search_last_critics($movie_id, $count);

        if ($results)
        {
            foreach ($results as $i=>$val)
            {
                $current_review = [];

               if  ($val['content'])
               {
                   if ($val['title'] && !strstr($val['content'],$val['title'] ))
                   {
                       $current_review['reviewBody']=$val['title'].' '.PHP_EOL.$val['content'];
                   }
                   else
                   {
                       $current_review['reviewBody']=$val['content'];
                   }

               }
                if  ($val['author_name'])
               {
                   $current_review['author'] = array(
                       "@type"=> "Person",
                        "name"=>$val['author_name']);
               }
                 if  ($val['rating'] > 0)
               {

            $current_review["reviewRating"]  =array(
            "@type"=> "Rating",
            "ratingValue"=> $val['rating']
            );

               }

                 if ($current_review)
                 {

                     $current_review["@type"]="Review";
                     $array_reviews[]=  $current_review;
                 }

            }
        }
        return $array_reviews;


//        "review": {
//        "@type": "Review",
//              "reviewRating": {
//            "@type": "Rating",
//                "ratingValue": "5"
//              },
//              "author": {
//            "@type": "Person",
//                "name": "John D."
//              },
//              "reviewBody": "Heartbreaking, inpsiring, moving. Bradley Cooper is a triple threat."
//              },

//        "review": [{
//        "@type": "Review",
//        "reviewRating": {
//            "@type": "Rating",
//          "ratingValue": "5"
//        },
//        "author": {
//            "@type": "Person",
//          "name": "John Doe"
//        }
//       },
//      {
//          "@type": "Review",
//        "reviewRating": {
//          "@type": "Rating",
//          "ratingValue": "1"
//        },
//        "author": {
//          "@type": "Person",
//          "name": "Jane Doe"
//        }
//      }],

    }


    public static function get_rating_count($movie_id)
    {
        $q = "SELECT id FROM `cache_rwt_rating` where movie_id = ".$movie_id;
        $r = Pdo_an::db_results_array($q);
        $count = count($r);
        $q = "SELECT id FROM `cache_rwt_rating_staff` where movie_id = ".$movie_id;
        $r = Pdo_an::db_results_array($q);
        $count+= count($r);
        return $count;
    }

    public static function get_rwt_rating($movie_id)
    {
        $sql="SELECT total_rating FROM `data_movie_rating` where movie_id = ".$movie_id;
        $r = Pdo_an::db_fetch_row($sql);
        return $r->total_rating;
    }


    public static function create_movie($movie_id,$json_array=[])
    {

        if ($json_array)
        {
            $position = count($json_array) +1;
        }
        if (!$position)
        {
            $position=1;
        }

        $movie_data = self::get_movie_data($movie_id);
        if ($movie_data)
        {
            $json_array["@type"] ="ListItem";
            $json_array["position"] =$position;
            $json_array["item"] =   $movie_data;
        }

        return  $json_array;

    }

    public static function single_movie_to_json($movie_id)
    {
        $json_array=self::create_movie($movie_id);
        if ($json_array)
        {
         $array =  array(
        "@context"=>"https://schema.org",
        "@type"=> "ItemList",
        "itemListElement"=> $json_array
            );

         $data  =json_encode($array,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
         $content = '<script type="application/ld+json">'.$data.'</script>';

         return $content;
        }
    }

    public static function cache_singe_movie($movie_id)
    {
       $data =  self::single_movie_to_json($movie_id);
    }


    public static function get_actor_name($id)
    {
        $sql = "SELECT `name` FROM `data_actors_imdb` WHERE id =".$id;
        $rows = Pdo_an::db_fetch_row($sql);
        return $rows->name;
    }

    public static function get_current_meta($table,$id,$aid='aid',$type =1)
    {
        $array_result = [];
        $sql = "SELECT * FROM `{$table}` WHERE  mid =".$id." and `type` = ".$type;
        $rows = Pdo_an::db_results_array($sql);
        foreach ($rows as $r)
        {

            $array_result[]=$r;
        }

        return $array_result;

    }

}
function single_movie_list($movie_id='')
{
    if (!$movie_id) {
        if (isset($_GET['id'])) {
            $movie_id = intval($_GET['id']);
        }
    }
    $movie_list  =STRUCTURELIST::single_movie_to_json($movie_id);
    return $movie_list;
}

function get_cache_single_list($movie_id)
{
    //return single_movie_list($movie_id);

    !function_exists('wp_custom_cache') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php" : '';
    $cache = wp_custom_cache('p-'.$movie_id.'_single_movie_list_1', 'fastcache', 86400*7);
    return $cache;
}

?>


