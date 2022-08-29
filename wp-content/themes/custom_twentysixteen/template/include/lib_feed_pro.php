<?php
error_reporting(E_ERROR);

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');



  if (!function_exists('pccf_filter')) {
      include ABSPATH.'wp-content/themes/custom_twentysixteen/template/include/pccf_filter.php';
  }

!class_exists('StaffRating') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/get_staff_rating.php" : '';



function lib_feed_pro(){
    return true;
}

function replacelink($content)
{
    $regv='#\<([ ]+)*a([^\>]+)\>#';
    if (preg_match_all($regv,$content,$match))
    {
        foreach ($match[2] as $value)
        {
            if (!strstr($value,'_blank'))
            {
                $content = str_replace($value,' target="_blank" '.$value,$content) ;
            }
        }

    }
    return $content;
}

if (!function_exists('get_wprss_content')){

    function get_wprss_content($id,$type)
    {
        global $table_prefix;
        global $wpdb;
        if ($wpdb)
        {
            $sql = "SELECT `".$type."` FROM `".$table_prefix."wprss_items` where item_id = '".$id."'  LIMIT 1";
            $result  = $wpdb->get_var($sql);
            $result = str_replace("\\","",$result);
            $result = str_replace("\\'","'",$result);
            return  $result;
        }
        else
        {
            global $pdo;
            $sql = "SELECT `".$type."` FROM `".$table_prefix."wprss_items` where item_id = '".$id."'  LIMIT 1";
            $qm = $pdo->prepare($sql);
            $qm->execute();
           $rm = $qm->fetch();
            $result = $rm[$type];

           $result = str_replace("\\","",$result);
           $result = str_replace("\\'","'",$result);
            return  $result;
        }



    }

}
if (!function_exists('get_avatars')) {
    function get_avatars()
    {

        $avatars = [];
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/uploads/avatars/custom/';


        $files = scandir($dir);

        foreach ($files as $val) {
            if ($val != '.' && $val != '..') {
                $regv = '#(\d+)\-(\d+)-128\.[jpgn]+#';
                if (preg_match($regv, $val, $mach)) {
                    $avatars[$mach[2]][$mach[1]] = $val;
                }
            }
        }
        return $avatars;
    }
}
if (!function_exists('get_audience_templ')) {
    function get_audience_templ($result_data, $link, $c_pid, $avatars, $fullsize = '')
    {
        if (!$avatars) {
            $avatars = get_avatars();
        }

        global $pdo;
        global $table_prefix;


        $qm = "SELECT * FROM `" . $table_prefix . "postmeta` WHERE `post_id` = '" . $c_pid . "' ";

        //   echo $qm;

        global $wpdb;

        $result_meta = [];

        if ($wpdb) {

            $result_meta = $wpdb->get_results($qm);

        } else {

            $qm = $pdo->prepare($qm);
            $qm->execute();
            $qm->setFetchMode(PDO::FETCH_OBJ);


            while ($rm = $qm->fetch()) {

                $result_meta[] = $rm;

            }

        }


        $actorstitle = '';


        if ($result_data) {
            $content = $result_data[1];
            $addtime = $result_data[2];
        } else {




            if (function_exists('get_the_title')) {

                $post = get_post($c_pid);

                if ($avatars=='staff') {
                    $content = get_wprss_content($c_pid, 'item_content');
                if (!$content)
                    {
                        $content = $post->post_content;
                    }
                }
                else
                {
                    $content = $post->post_content;
                }

                $addtime = $post->post_date;

            } else if (function_exists('get_post_data')) {

                if ($avatars=='staff') {
                    $content = get_post_data($c_pid, 'item_content', 'item_id', 'wprss_items');
                    $content = str_replace("\\", "", $content);
                    $content = str_replace("\\'", "'", $content);



                    if (!$content)
                    {
                        $content = get_post_data($c_pid, 'post_content', 'ID', 'posts');
                    }

                }
                else
                {
                    $content = get_post_data($c_pid, 'post_content', 'ID', 'posts');
                }


                $addtime = get_post_data($c_pid, 'post_date', 'ID', 'posts');

            }


        }


        $stars = '';
        $hollywood = '';
        $affirmative = '';
        $god = '';
        $lgbtq = '';
        $misandry = '';
        $patriotism = '';
        $vote = '';
        //var_dump(    $result_meta );

        foreach ($result_meta as $rm) {


            if ($rm->meta_key == 'wpcr3_review_name') {
                $actorstitle = $rm->meta_value;
                if (function_exists('pccf_filter')) {
                    $actorstitle = pccf_filter($actorstitle);
                }
            }
            if ($rm->meta_key == 'wpcr3_rating_vote') {
                $vote = $rm->meta_value;
            }
            if ($rm->meta_key == 'wpcr3_review_rating') {
                $stars = $rm->meta_value;
            }
            if ($rm->meta_key == 'wpcr3_review_rating_hollywood') {
                $hollywood = $rm->meta_value;
            }
            if ($rm->meta_key == 'wpcr3_review_rating_affirmative') {
                $affirmative = $rm->meta_value;
            }
            if ($rm->meta_key == 'wpcr3_review_rating_god') {
                $god = $rm->meta_value;
            }
            if ($rm->meta_key == 'wpcr3_review_rating_lgbtq') {
                $lgbtq = $rm->meta_value;
            }
            if ($rm->meta_key == 'wpcr3_review_rating_misandry') {
                $misandry = $rm->meta_value;
            }
            if ($rm->meta_key == 'wpcr3_review_rating_patriotism') {
                $patriotism = $rm->meta_value;
            }

            if ($rm->meta_key == 'wpcr3_review_title') {
                $title = $rm->meta_value;
            }

        }

        ///echo 'wpcr3_review_title='.$title;

        if (!$fullsize) {



            $content = str_replace('<br>', '\n', $content);
            $content = str_replace('</p>', '\n', $content);
          ///  $content = str_replace('</div>', '\n', $content);

            $content = strip_tags($content);

            ///   echo $content;

            if (!isset($_GET['id'])) {
                $content = substr($content, 0, 400);
                $content = rtrim($content, "!,.-");
                if (strstr($content,' '))
                {
                    $content = substr($content, 0, strrpos($content, ' '));
                }

            }

            $content = str_replace('\n', '<br>', $content);
            $regv='#((\<br\>)+)#';
            $content = preg_replace($regv,'<br>',$content);
        }
        else
        {



            //check links
            $content = replacelink($content);

        }

        if (function_exists('pccf_filter')) {
            $content = pccf_filter($content);
        }

        if (function_exists('check_spoiler')) {
            $content = check_spoiler($content);
        }

        if ($vote) {
            $vote = round($vote, 0);
            $vote_data = $vote;
            $vote = rating_images('audience_vote', $vote);
        } else {
            $vote = '';
        }


        if (!$stars) $stars = 0;

        $stars = round($stars, 0);
        $stars_data = $stars;
        $stars = rating_images('rating', $stars);


        if ($hollywood) {
            $hollywood = rating_images('hollywood', $hollywood);
        } else {
            $hollywood = '';
        }
        if ($affirmative) {
            $affirmative = rating_images('affirmative', $affirmative);
        } else {
            $affirmative = '';
        }

        if ($god) {
            $god = rating_images('god', $god);
        } else {
            $god = '';
        }

        if ($lgbtq) {
            $lgbtq = rating_images('lgbtq', $lgbtq);
        } else {
            $lgbtq = '';
        }

        if ($misandry) {
            $misandry = rating_images('misandry', $misandry);
        } else {
            $misandry = '';
        }
        if ($patriotism) {
            $patriotism = rating_images('patriotism', $patriotism);
        } else {
            $patriotism = '';
        }


        if ($content || $title || $vote || $stars || $hollywood || $misandry || $lgbtq || $patriotism || $affirmative || $god) {


            if ($avatars=='staff')
            {
                return '<div class="vote_main"><div class="vote">' . $stars . $vote . $hollywood . $misandry . $lgbtq . $patriotism . $affirmative . $god . '</div>'.$content.'</div>';
            }


            if ($fullsize) {

                if ($title) {
                    $title = '<strong>' . $title . '</strong>';
                }


                $content = '<div class="full_review_content_block">' . $title . '<div class="vote_main"><div class="vote">' . $stars . $vote . $hollywood . $misandry . $lgbtq . $patriotism . $affirmative . $god . '</div>'
                    . $content . '</div></div>';

            }
            else {


                $content =
                    '<a class="icntn" href="' . $link . '">
    <div class="vote_main">
         <div class="vote">' . $stars . $vote . $hollywood . $misandry . $lgbtq . $patriotism . $affirmative . $god . '</div>
         <div class="vote_content"><strong>' . $title . '</strong><br>' . $content . "... </div>
    </div>
</a>";

                //  $content='';
            }

        }



///echo  $content;

        if (!$stars_data) {
            $stars_data =0;
        }


        $array_avatars = $avatars[$stars_data];

        if (is_array($array_avatars)) {
            $rand_keys = array_rand($array_avatars, 1);
            $avatar_user = $array_avatars[$rand_keys];
        }

        if ($avatar_user) {

            $actorsdata = '<div class="a_img_container_audience" style="background: url(' .WP_SITEURL . '/wp-content/uploads/avatars/custom/' . $avatar_user . '); background-size: cover;"></div>';
        } else {
            $actorsdata = '<span></span>';
        }

        $catdata = '';


        //////get link
        $link = $link . '?a=' . $c_pid;

        $ptime = strtotime($addtime);

        $addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);
        //target="_blank" href="' . $link . '"
        if ($fullsize) {


            $actorsresult = '
' . $content . '
<div class="a_post_date">' . $addtime . '</div>
    <div class="amsg_aut">' . $actorsdata . '
        <div class="review_autor_name">' . $actorstitle . '</div>
       
    </div>';


        } else {

            global $enable_reactions;
            if ($enable_reactions)
            {
                if (!function_exists('get_user_reactions'))
                {

                    include ($_SERVER['DOCUMENT_ROOT'].'/wp-content/themes/custom_twentysixteen/template/include/emotiondata.php');

                }

                $reaction_data = get_user_reactions($c_pid);
            }
            else
            {
                $reaction_data='<div class="review_comment_data"></div>';
            }

            $actorsresult =
                '<div class="a_msg">
    <div class="a_msg_i">
        ' . $content . '
        <div class="a_post_date">' . $addtime . '</div>
        <div class="ugol"><div></div></div>
    </div>
        
        <div class="amsg_aut">
            ' . $actorsdata . '
            <div class="review_autor_name">' . $actorstitle . '
                <div class="a_cat">' . $catdata . '</div>
            </div>
            '.$reaction_data.'
        </div>
</div>';

        }
        return $actorsresult;
    }
}

if (!function_exists('wprss_get_feed_source_categories')) {

    function wprss_get_feed_source_categories($source_id, $only_name = false, $separator = null)
    {

        global $pdo;
        global $table_prefix;
        $array = [];

        $source_id = intval($source_id);
        $q = "SELECT term_id FROM `" . $table_prefix . "term_relationships` , `" . $table_prefix . "term_taxonomy`
         WHERE `" . $table_prefix . "term_relationships`.`object_id` = " . $source_id . "
         and `" . $table_prefix . "term_taxonomy`.`term_taxonomy_id` = `" . $table_prefix . "term_relationships`.term_taxonomy_id
          
          and `" . $table_prefix . "term_taxonomy`.`taxonomy` = 'wprss_feed_category'";

        global $wpdb;
        if ($wpdb) {
            $q = $wpdb->get_row($q);

            foreach ($q as $term_id) {
                $sql = "SELECT slug  FROM `" . $table_prefix . "terms`  WHERE term_id = " . $term_id . "  LIMIT 1";
                $slug = $wpdb->get_var($sql);
                $sql = "SELECT name  FROM `" . $table_prefix . "terms`  WHERE term_id = " . $term_id . "  LIMIT 1";
                $name = $wpdb->get_var($sql);

                $array[$term_id]['slug'] = $slug;
                $array[$term_id]['name'] = $name;

            }

        } else if ($pdo) {
            $q = $pdo->prepare($q);
            $q->execute();
            $q->setFetchMode(PDO::FETCH_ASSOC);

            while ($r = $q->fetch()) {


                $slug = get_post_data($r['term_id'], 'slug', 'term_id', 'terms');
                $name = get_post_data($r['term_id'], 'name', 'term_id', 'terms');

                $array[$r['term_id']]['slug'] = $slug;
                $array[$r['term_id']]['name'] = $name;

            }

        }


        return $array;

    }
}

if (!function_exists('rating_images')) {


    function rating_images($type, $rating, $subrating = 0)
   {
     return  StaffRating::rating_images($type, $rating, $subrating = 0);
   }
}

function get_staff_rating($content)
{
$rating =StaffRating::get_staff_rating($content);
return $rating;
}



if (!function_exists('get_feed_pro_templ')) {
    function get_feed_pro_templ($rpid, $addtime, $count, $pid, $stuff = '', $fullsize = '')
    {


        if (!$addtime) {

            if (function_exists('get_post_meta')) {
                $addtime = get_post_meta($rpid, 'wprss_item_imported_date', 1);

            } else if (function_exists('get_post_meta_custom')) {
                $addtime = get_post_meta_custom($rpid, 'wprss_item_imported_date', 1);

            }
        }

        /////check staf from audience

        if (function_exists('get_post_meta')) {
            $enable_audience = get_post_meta($rpid, 'wprss_feed_type', 1);
        } else if (function_exists('get_post_meta_custom')) {
            $enable_audience = get_post_meta_custom($rpid, 'wprss_feed_type', 1);
        }

        if ($enable_audience == 'staff') {
            ///get audience content



            $content = get_audience_templ('', '', $rpid, 'staff', $fullsize);


            //  return $content;
            if (function_exists('get_post_meta')) {
                $crt_id = get_post_meta($rpid, 'wprss_feed_id', 1);
            } else if (function_exists('get_post_meta_custom')) {
                $crt_id = get_post_meta_custom($rpid, 'wprss_feed_id', 1);
            }


        } else {
            if (function_exists('get_post_meta')) {
                $permalink = get_post_meta($rpid, 'wprss_item_permalink', 1);
                $crt_id = get_post_meta($rpid, 'wprss_feed_id', 1);
                ///$content = get_post_meta($rpid, 'wprss_item_content', 1);

                $content = get_wprss_content($rpid, 'item_content');

            } else if (function_exists('get_post_meta_custom')) {
                $permalink = get_post_meta_custom($rpid, 'wprss_item_permalink', 1);
                $crt_id = get_post_meta_custom($rpid, 'wprss_feed_id', 1);

                global $debug;
                if ($debug) gmi('before content ' . $rpid);
                ///$content = get_post_meta_custom($rpid, 'wprss_item_content', 1);
                $content = get_post_data($rpid, 'item_content', 'item_id', 'wprss_items');
                $content = str_replace("\\", "", $content);
                $content = str_replace("\\'", "'", $content);
                global $debug;
                if ($debug) gmi('after content ' . $rpid);
            }


        if ($stuff && $fullsize) {


            //echo $permalink;
            $regv = '#\/\/[^\/]+\/([^\/]+)#';
            if (preg_match($regv, $permalink, $mach)) {
                $post_name = $mach[1];

                //echo $post_name;

                /// $post_name='ben-shapiro-reading-list-2';

                global $pdo_r;
                global $table_prefix2;

                $sql = "SELECT post_content  FROM `" . $table_prefix2 . "posts`  WHERE post_name = ? and post_type = 'post'  and post_status='publish' LIMIT 1";

                ///echo $sql;
                //     var_dump($pdo_r);
                if ($pdo_r) {
                    $qc = $pdo_r->prepare($sql);
                    $qc->execute(array($post_name));
                    $qc->setFetchMode(PDO::FETCH_ASSOC);
                    $rc = $qc->fetch();

                    /// var_dump($r);

                    $content = $rc['post_content'];

                    ////  echo $content;
                    //require ($_SERVER['DOCUMENT_ROOT'].'wp-content/plugins/shortcodes-ultimate/inc/core/shortcodes.php');
                    //$shortcode =  new Su_Shortcodes;

                    if (function_exists('do_shortcode')) {
                        $content = do_shortcode($content);
                        add_filter('strip_shortcodes_tagnames', function ($tags_to_remove) {
                            $tags_to_remove[] = 'wp_google_searchbox';
                            $tags_to_remove[] = 'pt_view';
                            return $tags_to_remove;
                        });
                        $content = strip_shortcodes($content);
                    } else {


                        $regv = '#\[su_spoiler([^\]]+)\]#';
                        if (preg_match_all($regv, $content, $mach)) {
                            /// var_dump($mach);
                            $content = str_replace('[/su_spoiler]', '</div></details>', $content);


                            foreach ($mach[0] as $i => $val) {
                                $rtitle = 'Spoiler';
                                $reg2 = '#title="([^\"]+)#';
                                if (preg_match($reg2, $mach[1][$i], $m2)) {
                                    $rtitle = $m2[1];
                                }
                                $spoiler = '<details><summary>' . $rtitle . '</summary><div>';
                                $content = str_replace($val, $spoiler, $content);

                            }
                        }
                    }
                    $stars = '';
                    $vote = '';
                    $other = '';


                    $regv = '#\[stfu_ratings([0-9a-z\=\"\. ]+)\]#';

                    if (preg_match($regv, $content, $mach)) {

                        $content = str_replace($mach[0], '', $content);

                        $content = replacelink($content);

                        $array = explode(' ', $mach[1]);

                        foreach ($array as $val) {
                            if ($val) {
                                $val = explode('=', $val);


                                $current_type = trim($val[0]);
                                $current_value = trim(str_replace('"', '', $val[1]));
                                $curentpercent = 0;
                                if (strstr($current_value, '.')) {
                                    $current_value_array = explode('.', $current_value);
                                    $current_value = $current_value_array[0];
                                    $curentpercent = 1;
                                }

                                //echo $current_type.' '.$current_value.'<br>';

                                if ($current_type == 'worthwhile') {
                                    $current_type = 'rating';
                                    $stars = rating_images($current_type, $current_value, $curentpercent);
                                } else if ($current_type == 'slider') {
                                    $current_type = 'vote';

                                    if ($current_value == 'pay') {
                                        $current_value = 3;
                                    } else if ($current_value == 'free') {
                                        $current_value = 2;
                                    } else if ($current_value == 'skip') {
                                        $current_value = 1;
                                    }

                                    $vote = rating_images($current_type, $current_value, $curentpercent);
                                } else {
                                    $other .= rating_images($current_type, $current_value, $curentpercent);

                                }


                            }
                        }
                        $content_rating = '<div class="vote">' . $stars . $vote . $other . '</div>';

                        $content = '<div class="vote_main">' . $content_rating . '<div class="vote_content"><br>' . $content . "</div></div>";


                    }


                    /////remove all shortcode

                    $regv = '#\[[^\]]+\]#';
                    $content = preg_replace($regv, '', $content);


                    $content = str_replace('<strong>Other reviews by Libertarian Agnostic:</strong>', '', $content);
                    $content = str_replace('<strong>Search all Staff Reviews from STFU Hollywood:</strong>', '', $content);

                    //  $content=' content ';

                }
//else $content='nldb';

            }


        } else {
            if ($stuff) {

                /////////////////staff

                //    $content = substr($content,$pos);

                $array_rating=get_staff_rating($content);

                $content_rating=$array_rating['data'];


                ///// $ratings = staff_average_ratings();


                // Check if 'Review / Rant:' text exists. If so - get next 2 paragraphs as preview text
                preg_match_all("/Review \/ Rant:<\/div><div>\s*(<p>[^<\/]*<\/p>)\s*(<p>[^<\/]*<\/p>)/", $content, $paragraphs);


                if (empty($paragraphs[1][0]) && empty($paragraphs[2][0])) {

                    //preg_match_all("/(<p>[^<\/]\s*<\/p>)/", $content, $paragraphs);
                    //preg_match_all("/<p>[^<\/](.*)<\/p>/", $content, $paragraphs);
                    preg_match_all('/<p>(.*?)<\\/p>/s', $content, $paragraphs);


                    if (empty($paragraphs[1][0]) && empty($paragraphs[2][0])) {

                        $pos = strpos($content, "Read the rest via");

                        if ($pos !== false)
                            $content = substr($content, 0, $pos);

                        $paragraph1 = '<p class="xxxx" >' . $content . '</p>';
                        $paragraph2 = '';
                    } else {

                        $paragraph1 = $paragraphs[0][0];
                        $paragraph2 = $paragraphs[0][1];

                        //echo "||||";
                        //echo "<textarea>".htmlentities($content)."</textarea>";
                        //echo "||||";

                        //echo htmlentities(print_r($paragraphs,true));
                        //echo htmlentities(print_r($content,true));
                    }
                } else {
                    $paragraph1 = $paragraphs[1][0];
                    $paragraph2 = $paragraphs[2][0];

                }


                //$paragraph1 = strip_tags($paragraph1);
                // $paragraph2 = strip_tags($paragraph2);

                // show only 2 first paragraphs as preview
                $content = $paragraph1 . ' ' . $paragraph2;
                //  $content = substr($content, 0, 400);

                $content = str_replace('<br>', '\n', $content);
                $content = str_replace('</p>', '\n', $content);

                $content = strip_tags($content);

                $content = str_replace('\n', '<br>', $content);
                $regv = '#((\<br\>)+)#';
                $content = preg_replace($regv, '<br>', $content);


                $content = '<div class="vote_main">' . $content_rating . '<div class="vote_content"><br>' . $content . "</div></div>";

                /////////////////////////

            } else {

                /////////pro
                $video = '';


                $regex_pattern = "/(youtube.com|youtu.be)\/(watch)?(\?v=)?(\S+)?/";
                $video_count = 0;
                if (preg_match($regex_pattern, $permalink, $mach)) {


                    if ($fullsize) {
                        $video = '<div class="embed-responsive embed-responsive-16by9"><iframe style="width:100%; height:100%;" src="https://www.youtube.com/embed/' . $mach[4] . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
                    } else {
                        $video = '[Video included in the review]<br>';
                    }

                } else if (strstr($permalink, 'bitchute.com/')) {
                    if ($fullsize) {

                        $permalink = str_replace('/video/', '/embed/', $permalink);

                        $video = '<div class="embed-responsive embed-responsive-16by9"><iframe style="width:100%; height:100%;" src="' . $permalink . '" ></iframe></div>';
                    } else {
                        $video = '[Video included in the review]<br>';
                    }

                    $permalink = str_replace('/embed/', '/video/', $permalink);
                    $content = '';
                }

                $content = str_replace('<br>', '\n', $content);
                $content = str_replace('</p>', '\n', $content);
                $content = str_replace('</div>', '\n', $content);


                $image = '';

                if ($fullsize) {

                    ///////try to find img
                    $regi = '/\<img([^src]+)src="([^"]+)"([^\>]+)*\>/Ui';


                    if (preg_match($regi, $content, $mach)) {

                        if (function_exists('get_poster_tsumb')) {
                            $data = new GETTSUMB;

                            $image = $data->getThumbLocal_custom(640, 0, $mach[2]);

                            if ($image) {
                                $image = '<div style="text-align: center;margin: 10px 0;"><img src="' . $image . '"></div>';
                            }
                        }
                    }


                    $content = strip_tags($content);
                    $content = substr($content, 0, 800);
                } else {
                    $content = strip_tags($content);
                    $content = substr($content, 0, 200);
                }


                $content = rtrim($content, "!,.-");
                if (strstr($content,' ')) {
                    $content = substr($content, 0, strrpos($content, ' '));
                }
                $content = str_replace('\n', '<br>', $content);
                $regv = '#((\<br\>)+)#';
                $content = preg_replace($regv, '<br>', $content);

                $content = $image . $content;
                if ($content) {
                    $content .= '...';
                }

                if (function_exists('get_post_meta')) {
                    $is_autoblur = get_post_meta($crt_id, 'wprss_autoblur')[0];

                } else if (function_exists('get_post_meta_custom')) {
                    $is_autoblur = get_post_meta_custom($crt_id, 'wprss_autoblur', 1);

                }


                if ($is_autoblur && $content) {
                    $content = '[spoiler]' . $content . '[/spoiler]';

                }

                $content = $video . $content;

            }
            if (!$content) {
                ////check video

                if (function_exists('get_the_title')) {

                    $title = get_the_title($rpid);

                } else if (function_exists('get_post_data')) {

                    $title = get_post_data($rpid, 'post_title', 'ID', 'posts');

                }
                $title = strip_tags($title);

                $content = $title;

                if (!$content) {
                    return '';
                }
            }

        }
        if ($content) {
            if (function_exists('check_spoiler')) {
                $content = check_spoiler($content);
            }
            if (function_exists('pccf_filter')) {
                $content = pccf_filter($content);
            }
        }

    }





        if (function_exists('get_post_meta')) {
            $actors = get_post_meta($crt_id, 'wprss_html_before', 1);
        } else if (function_exists('get_post_meta_custom')) {
            $actors = get_post_meta_custom($crt_id, 'wprss_html_before', 1);
        }

        $regv = "#\<img.+title=\".+src=\"([^\"]+)\"#";

        if (preg_match($regv, $actors, $mach)) {


            if (function_exists('get_poster_tsumb')) {
                $data = new GETTSUMB;

                $image = $data->getThumbLocal_custom(100, 100, $mach[1]);
            } else {
                $image = $mach[1];
            }


            $actorsdata = '<div class="a_img_container" style="background: url(' . $image . ');
background-size: cover;"></div>';
        } else {
            $actorsdata = '<span></span>';
        }
        if (function_exists('get_the_title')) {

            $actorstitle = get_the_title($crt_id);

        } else if (function_exists('get_post_data')) {

            $actorstitle = get_post_data($crt_id, 'post_title', 'ID', 'posts');

        }

        if (function_exists('pccf_filter')) {
            $actorstitle = pccf_filter($actorstitle);
        }

        $catdata = '';

        if (function_exists('wprss_get_feed_source_categories')) {

            $feed_categories = wprss_get_feed_source_categories($crt_id);


            foreach ($feed_categories as $id => $category) {

                $catdata .= '<a href="/critics/category_' . $category['slug'] . '/sort_by/movie_asc/"
               title="' . $category['name'] . '">' . $category['name'] . '</a>';

            }

        }

        //////get link
        if (function_exists('get_post')) {

            $post = get_post($rpid);
            $post_name = $post->post_name;

        } else if (function_exists('get_post_data')) {

            $post_name = get_post_data($rpid, 'post_name', 'ID', 'posts');

        }


        //////get link
        if ($post_name) {
            $link =  WP_SITEURL . '/reviews/' . $post_name;
            if ($pid) {
                $link = $link . '/?' . $pid;
            }
        }


//        if ($stuff) {
//
//            $link = $link . '/?s' . $rpid;
//        } else {
//            $link = $link . '/?p' . $rpid;
//
//        }


        $ptime = strtotime($addtime);

        $addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);


        $title_str = '';

        if (function_exists('get_the_title')) {

            $title = get_the_title($rpid);

        } else if (function_exists('get_post_data')) {

            $title = get_post_data($rpid, 'post_title', 'ID', 'posts');

        }
        $title = strip_tags($title);

        if (function_exists('pccf_filter')) {
            $title = pccf_filter($title);
        }



        if ($title != $content && !$stuff) {
            $title_str = '<strong>' . $title . '</strong>';
        }



        if ($fullsize) {

            if ($stuff && $fullsize) {

                $original_link = '<a class="original_link" target="_blank" href="' . $permalink . '">Source Link >></a>';


            } else {
                $original_link = '<a class="original_link" target="_blank" href="' . $permalink . '">Full review >></a>';

            }


            if (strlen($content)>50) {

                if (strlen($content)>4000) {

                 $largest=   ' largest';
                 $after_content ='<a class="expanf_content" href="#">Read more...</a>';
                }

            $actorsresult = '<div class="full_review_content_block'.$largest.'">' . $title_str . $content . '</div>'.$after_content.'<div class="a_post_date">' . $addtime . '</div>' . $original_link . '
 <div class="amsg_aut">' . $actorsdata . '<div class="review_autor_name">' . $actorstitle . '<div class="a_cat">' . $catdata . '</div></div></div>';
        }
        }




        else if ( $link)
        {

            global $enable_reactions;
            if ($enable_reactions)
            {
                if (!function_exists('get_user_reactions'))
                {

                   include ($_SERVER['DOCUMENT_ROOT'].'/wp-content/themes/custom_twentysixteen/template/include/emotiondata.php');

                }
                $reaction_data = get_user_reactions($rpid);
            }
            else
            {
                $reaction_data='<div class="review_comment_data"></div>';
            }


//var_dump(strlen($content));
            $actorsresult = '<div class="a_msg"><div   class="a_msg_i"><a class="icntn" href="' . $link . '">' . $title_str . $content . '</a><div class="a_post_date">' . $addtime . '</div><div class="ugol"><div></div></div></div>
        <div class="amsg_aut">' . $actorsdata . '<div class="review_autor_name">' . $actorstitle . '<div class="a_cat">' . $catdata . '</div></div>'.$reaction_data.'
        </div></div>';




        }
        return $actorsresult;
    }
}

if (!function_exists('wph_cut_by_words')) {
    function wph_cut_by_words($maxlen, $text)
    {
        $len = (mb_strlen($text) > $maxlen) ? mb_strripos(mb_substr($text, 0, $maxlen), ',') : $maxlen;
        $cutStr = mb_substr($text, 0, $len);
        $temp = $cutStr;
        return $temp;
    }
}
function get_small_movie($movie_id)
{
    global $table_prefix;
    global $pdo;
    global $wpdb;


    $sql = "SELECT * FROM " . $table_prefix . "posts WHERE post_type ='movie' and post_status='publish' and ID='" . $movie_id . "'  limit 1";


    if ($wpdb) {
        $r = $wpdb->get_row($sql, ARRAY_A);
        //  var_dump($q);
    } else {


        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        $r = $q->fetch();
    }


    ///  var_dump($r);

    $title = $r['post_title'];
    $link = $r['post_name'];
    $url = '/movies/' . $link;

    $date = '';

    if (function_exists('get_post_meta_custom')) {
        $meta = get_post_meta_custom($r['ID']);

        $cast = $meta['_wpmoly_movie_cast'];
        $date = $meta['_wpmoly_movie_release_date'];
    } else if (function_exists('get_post_meta')) {
        $cast = get_post_meta($r['ID'], '_wpmoly_movie_cast', 1);
        $date = get_post_meta($r['ID'], '_wpmoly_movie_release_date', 1);
    }

    //var_dump($cast);
    //var_dump($date);

    if (function_exists('wph_cut_by_words')) {
        if ($cast) {
            $cast = wph_cut_by_words(50, $cast);
        }

    }


    if ($date) {
        $date = strtotime($date);
        $date = date('Y', $date);
        if (strstr($title, $date)) {
            $date = '';
        } else {
            $date = ' (' . $date . ')';
        }
    }


    if (function_exists('get_poster_tsumb')) {
        $imgcache = get_poster_tsumb($r['ID'], $array_request = array([60, 90]));
    }


    if ($imgcache) {
        $imgsrc = $imgcache[0];
        $img = '<img src="' . $imgsrc . '">';
    }

    $content = '<div class="full_review_movie"><a href="' . $url . '/" >' . $img . '<div><span  class="itm_hdr">' . $title . $date . '</span><span>' . $cast . '</span></div></a></div>';


    return $content;

}

