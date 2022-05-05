<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


include(ABSPATH.'wp-content/themes/custom_twentysixteen/template/include/custom_connect.php');
if (!function_exists('wp_custom_cache'))
{
    include(ABSPATH.'wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php');
}

include(ABSPATH.'wp-content/themes/custom_twentysixteen/template/ajax/get_wach.php');



if (!function_exists('get_genre')){
    function get_genre(){
        global $pdo;
        global $table_prefix;
        $found_posts=[];

        $sql = "SELECT " . $table_prefix . "terms.term_id, name, " . $table_prefix . "term_taxonomy . count FROM 
    " . $table_prefix . "term_taxonomy , " . $table_prefix . "terms 
          WHERE " . $table_prefix . "term_taxonomy.taxonomy = 'genre' AND " . $table_prefix . "term_taxonomy.term_id = " . $table_prefix . "terms.term_id 
          ORDER BY " . $table_prefix . "term_taxonomy.count DESC, name";

        //  echo $sql;

        $found_posts= [];
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);
        while ($r = $q->fetch())
        {
            $found_posts[$r['name']]=$r['count'];
        }
        return json_encode($found_posts);
    }
}



if (!function_exists('advanced_select')) {
    function advanced_select($data, $name, $title, $multiple = ' multiple="multiple" ')
    {
        $result = '';

        if (is_array($data)) {
            foreach ($data as $index => $val) {
                    if ($val) {

                    if ($name == 'streaming_services' || $name ==  'movie_type') {
                        $result .= '<option value="' . $index . '">' . $val . '</option>';
                    }
                    else if ($name == 'genre') {

                        $result .= '<option  value="' . $index . '">' . $index . '</option>';
                    }
                    else {
                        if ($name == 'audience_rating' || $name == 'staff_rating') {
                            $val2 = str_replace('-', ' ', $val);
                            $val2 = str_replace('_', ' ', $val2);

                            $result .= '<option value="' . $val . '">' . $val2 . '</option>';
                        } else if ($name == 'critics' || $name == 'review_sort') {
                            $result .= '<option value="' . $index . '">' . $val . '</option>';
                        } else {
                            $result .= '<option value="' . $val . '">' . $val . '</option>';
                        }
                    }
                }
            }
        }

        if ($result || $name == 'cast'|| $name == 'director') {
            $result = '<div class="advanced_title">' . $title . '</div>
                   <div class="advanced_select">
                       <select style="width: 100%" class="advanced_select2 advanced_select_' . $name . '" name="' . $name . '[]" ' . $multiple . '>' . $result . '</select>
                   </div>';
        }

        return $result;
    }
}
if (!function_exists('advanced_search')) {
    function advanced_search()
    {

        global $pdo;
        global $table_prefix;
        $providers_object=[];
        $arraydatameta=[];
        $group_image ='';// wp_get_attachment_url_by_post_name('group-search');

        /////create data
        $genre_data = get_genre();

        if ($genre_data){
            $genre_data = json_decode($genre_data,1);
        }
///var_dump($filters['genre']);
        $genre = advanced_select($genre_data, 'genre', 'Genre',' multiple="multiple" ');

        $content = '<div class="advanced_search_data_block"><h3>Advanced Search</h3><div class="advanced_search_block">' . $genre . '</div>';

        ///release date
        $sql = "SELECT meta_value FROM " . $table_prefix . "postmeta  WHERE meta_key = '_wpmoly_movie_release_date' and meta_value != 0";

        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        while ($val = $q->fetch())
        {
                //var_dump($val);
                $data = date('Y', strtotime($val['meta_value']));
                $year_k = $data . 'x'; // sort
                $arraydata[$year_k] = $data;

                // 80s, 90s, 2000s, etc
                $years = substr($data, 0, 3);
                $years_v = $years . '0s';
                $arraydata[$years_v] = $years_v;

        }



        krsort($arraydata);

        $s_date = advanced_select($arraydata, 'release_date', 'Release date');

        $content .= '<div class="advanced_search_block">' . $s_date . '</div>';


        ///cast
        $arraydatacast[0] = 0;
        /// var_dump($arraydatacast);

        $s_cast = advanced_select($arraydatacast, 'cast', 'Actor(s)');


        $content .= '<div class="advanced_search_block">' . $s_cast . '</div>';



        $director = advanced_select($arraydatacast, 'director', 'Director(s)');


        $content.= '<div class="advanced_search_block">' . $director . '</div>';





        /////get streaming data
        //$providers=get_providers();
       $providers = wp_custom_cache('get_providers', $folder = 'fastcache', $time = 3600 * 24 * 7);

        if ($providers) {
            $providers_object = json_decode($providers);
        }

       /// var_dump($providers_object);
        $array_service=[];

        foreach ($providers_object as $provider_id=>$provider_data)
        {
            $array_service[$provider_id] = $provider_data->n;

        }
        $array_movie_type = array('movie'=>'Movies', 'tvseries'=>'TV');
        $movie_type = advanced_select($array_movie_type, 'movie_type', 'Search type');


        $rating = advanced_select($array_service, 'streaming_services', 'Streaming services');

        $content .= '<div class="advanced_search_block" style="min-width: 25%;padding: 6px;">' . $movie_type . '</div>';
        $content .= '<div class="advanced_search_block" style="min-width: 75%;padding: 6px;">' . $rating . '</div>';

        $content .= '<div class="search_block_rating"><h4>Rating</h4></div>';
        $arrayrating = array(1, 2, 3, 4, 5);
        $rating = advanced_select($arrayrating, 'movie_pg_rating', 'Family Friendly score');
        $content .= '<div class="advanced_search_block">' . $rating . '</div>';

        //_wpmoly_movie_rating
        ////rating
        $arrayrating = array(1, 2, 3, 4, 5);
        $rating = advanced_select($arrayrating, 'movie_rating', 'Star rating');


        $content .= '<div class="advanced_search_block">' . $rating . '</div>';

        ///audience/staff/pro
        ///audience
        $arrayrating = array(
            'rating_vote-1', 'rating_vote-2', 'rating_vote-3',
            'rating_hollywood-0', 'rating_hollywood-1', 'rating_hollywood-2', 'rating_hollywood-3', 'rating_hollywood-4', 'rating_hollywood-5',
            'rating_patriotism-0', 'rating_patriotism-1', 'rating_patriotism-2', 'rating_patriotism-3', 'rating_patriotism-4', 'rating_patriotism-5',
            'rating_misandry-0', 'rating_misandry-1', 'rating_misandry-2', 'rating_misandry-3', 'rating_misandry-4', 'rating_misandry-5',
            'rating_affirmative-0', 'rating_affirmative-1', 'rating_affirmative-2', 'rating_affirmative-3', 'rating_affirmative-4', 'rating_affirmative-5',
            'rating_lgbtq-0', 'rating_lgbtq-1', 'rating_lgbtq-2', 'rating_lgbtq-3', 'rating_lgbtq-4', 'rating_lgbtq-5',
            'rating_god-0', 'rating_god-1', 'rating_god-2', 'rating_god-3', 'rating_god-4', 'rating_god-5');

        $rating = advanced_select($arrayrating, 'audience_rating', 'Audience rating');

        $content .= '<div class="advanced_search_block">' . $rating . '</div>';


        $rating = advanced_select($arrayrating, 'staff_rating', 'Staff rating');

        $content .= '<div class="advanced_search_block">' . $rating . '</div>';


        $content .= '<div class="search_block_rating"><h4>Search critics reviews</h4></div>';

        ////critics
        $arraycritics = array();

        $groups = array(
            'group_pro' => 'Pro',
            'group_staff' => 'Staff',
            'group_audience' => 'Audience'
        );

        $arraycritics = array_merge($arraycritics, $groups);

        $categories = array();
        $categories_mages = array();

        $sql="SELECT ".$table_prefix."terms.* FROM `".$table_prefix."terms` , `".$table_prefix."term_taxonomy`
         WHERE  `".$table_prefix."term_taxonomy`.`term_taxonomy_id` = `".$table_prefix."terms`.term_id
           and `".$table_prefix."term_taxonomy`.`taxonomy` = 'wprss_feed_category'";

        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);
        while ($val = $q->fetch())
        {

            $categories['category_' .$val['slug']] = $val['name'];

            $arraydatameta[$val['name']]=$group_image;
        }

        $arraycritics = array_merge($arraycritics, $categories);


        $sql = "SELECT " . $table_prefix . "posts .post_title, " . $table_prefix . "posts.ID FROM " . $table_prefix . "posts 
          INNER JOIN " . $table_prefix ."postmeta  ON (" . $table_prefix . "postmeta.post_id = " . $table_prefix . "posts.ID and " . $table_prefix . "postmeta.meta_key = 'wprss_is_public')
          WHERE " . $table_prefix . "posts.post_type = 'wprss_feed' and " . $table_prefix . "postmeta.meta_value = '1'
          ORDER BY " . $table_prefix . "posts.post_title";

        ///echo $sql;
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);
        while ($val = $q->fetch())
        {
            $arraycritics[$val['ID']] = $val['post_title'];
        }
        $s_critics = advanced_select($arraycritics, 'critics', 'Movie critics');
        $content .= '<div class="advanced_search_block">' . $s_critics . '</div>';


        //////publish date

        $arraydata = array();

        $sql = "SELECT post_date FROM " . $table_prefix . "posts WHERE post_type = 'wprss_feed_item'";
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);
        while ($val = $q->fetch())
        {
                //var_dump($val);
                $data = date('Y', strtotime($val['post_date']));
                $year_k = $data . 'x'; // sort
                $arraydata[$year_k] = $data;

                // 80s, 90s, 2000s, etc
                $years = substr($data, 0, 3);
                $years_v = $years . '0s';
                $arraydata[$years_v] = $years_v;

        }

        krsort($arraydata);

        // var_dump($arraydata);

        $s_date = advanced_select($arraydata, 'review_publish_date', 'Review publish date');

        $content .= '<div class="advanced_search_block">' . $s_date . '</div>';

        ///relevance
        $arrayrating = array('Proper Review', 'Contains Mention');

        $s_date = advanced_select($arrayrating, 'review_category', 'Relevance');

        $content .= '<div class="advanced_search_block">' . $s_date . '</div>';

        $arrayrating = array(
            'movie_asc' => 'Movie Title (A-Z)',
            'movie_desc' => 'Movie Title (Z-A)',
            'movie_rating_desc' => 'Movie Rating (5-1)',
            'movie_rating_asc' => 'Movie Rating (1-5)',
            'movie_date_last' => 'New movies',
            'movie_date_desc' => 'Release Date (Newest)',
            'movie_date_asc' => 'Release Date (Oldest)',
            'review_date_desc' => 'Review Date (Newest)',
            'review_date_asc' => 'Review Date (Oldest)'
        );

        $s_date = advanced_select($arrayrating, 'review_sort', 'Sort by', '');

        $content .= '<div class="advanced_search_block">' . $s_date . '</div>';

        $content .= '<div style="position: relative;
text-align: right;
display: flex;
margin-top: 12px;
align-items: center;">
                     
                     
                     <button class="reset_searchmovie" style="margin-right: 20px; background-color: #959595;">Reset filter</button>
                     <button class="advanced_searchmovie">Search</button>
                     <div class="advanced_search_ajaxload" style="display: none;width: 50px;">
                         <div class="windows8">
                             <div class="wBall" id="wBall_1">
                                 <div class="wInnerBall"></div>
                             </div>
                             <div class="wBall" id="wBall_2">
                                 <div class="wInnerBall"></div>
                             </div>
                             <div class="wBall" id="wBall_3">
                                 <div class="wInnerBall"></div>
                             </div>
                             <div class="wBall" id="wBall_4">
                                 <div class="wInnerBall"></div>
                             </div>
                             <div class="wBall" id="wBall_5">
                                 <div class="wInnerBall"></div>
                             </div>
                         </div>
                     </div>
                 </div>';

        $content .= '</div>';



        $groups = array(
            'Pro' => $group_image,
            'Staff' => $group_image,
            'Audience' => $group_image
        );

        $arraydatameta = array_merge($arraydatameta, $groups);


        $sql = "SELECT " . $table_prefix . "posts.post_title, " . $table_prefix . "posts.ID, " . $table_prefix . "postmeta.meta_value FROM " . $table_prefix . "posts, " . $table_prefix . "postmeta
WHERE " . $table_prefix . "posts.post_type = 'wprss_feed' AND " . $table_prefix . "posts.ID = " . $table_prefix . "postmeta.post_id AND " . $table_prefix . "postmeta.meta_key = 'wprss_html_before' ";

///echo $sql;


        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);
        while ($val = $q->fetch())
        {

                $meta = $val['meta_value'];
                $regv = "#\<img.+title=\".+src=\"([^\"]+)\"#";

                if (preg_match($regv, $meta, $mach)) {
                    $arraydatameta[$val['post_title']] = $mach[1];
                }
        }

        $arraymetastring = json_encode($arraydatameta);
        $content .= '<div style="display: none" class="critic_data">'.$arraymetastring.'</div>';

        if ($providers_object)
        {
            $providers_string = json_encode($providers_object);
            $content .= '<div style="display: none" class="providers_data">'.$providers_string.'</div>';
        }



        return $content;
    }






}

//echo advanced_search();
//return;

if (isset($_POST['action']))
{
    if ($_POST['action'] =='advanced_search_data')
    {
        echo advanced_search();
        return;

        if (function_exists('wp_custom_cache'))
        {
            echo   wp_custom_cache('advanced_search','fastcache', 3600*24);
        }
        else
        {
            echo advanced_search();
        }

    }
}

