<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

class DISQUS_DATA
{

    public static $key = 'Zt8xSiTUeoQuBLJ060aEdofTRBzQRTq6uMkn5Xwm5GsNZzTyatx37i9valgksE5B'; // TODO replace with your Disqus secret key from http://disqus.com/api/applications/
    public static $forum = 'hollywoodstfu'; // Disqus shortname

    public static function get_trehead($id)
    {
        $sql = "SELECT * FROM `cache_disqus_treheads` WHERE `trehead_id`='" . $id . "' limit 1";

        $r = Pdo_an::db_results_array($sql);
        return $r[0];

    }

    public static function format_interval($interval, $granularity = 2) {
        $units = array('1 year|@count years' => 31536000, '1 week|@count weeks' => 604800, '1 day|@count days' => 86400, '1 hour|@count hours' => 3600, '1 min|@count min' => 60, '1 sec|@count sec' => 1);
        $output = '';
        foreach ($units as $key => $value) {
            $key = explode('|', $key);
            if ($interval >= $value) {
                $floor = floor($interval / $value);
                $output .= ($output ? ' ' : '') . ($floor == 1 ? $key[0] : str_replace('@count', $floor, $key[1]));
                $interval %= $value;
                $granularity--;
            }

            if ($granularity == 0) {
                break;
            }
        }

        return $output ? $output : '0 sec';
    }
    public static function get_parents_name($id)
    {
        $sql="SELECT * FROM `cache_disqus_comments` WHERE `disqus_id` =".$id." limit 1";
        $r= Pdo_an::db_fetch_row($sql);
       if ($r)
       {
           $data =$r->data;
           if ($data)
           {
               $data = json_decode($data);
               return $data->author->name;
           }
       }




    }

    public static function add_template($data)
    {
        $inner_content = '';

        $trehead = $data['thread'];
        $trehead_data = self::get_trehead($trehead);

        $tr_data = $data["data"];
        $add_time = $data["add_time"];

        if ($tr_data) {
            $comment = json_decode($tr_data);

        }

        $parent = $comment->parent;

        if ($parent)
        {
            //get parent data
            $parent_name=self::get_parents_name($parent);
        }

        $link = $trehead_data["link"];

        $media = $comment->media;
        $message = $comment->message;
        $reg_v = '#<a.+title="([^"]+)"[^\<]+\<\/a\>#Uis';

        $array_replace = [];

        if (preg_match_all($reg_v, $message, $mach)) {
            ///var_dump($mach);
            foreach ($mach[0] as $i => $v) {
                $array_replace[$mach[1][$i]] = $v;
            }
        }


        if ($media) {
            foreach ($media as $mdata) {

                if ($mdata->html && $mdata->mediaType == 3) {
                    //  $content.=  $mdata->html;
                }
                if ($mdata->url && ($mdata->mediaType == 1 || $mdata->mediaType == 2)) {

                    if ($array_replace[$mdata->url]) {
                        $content_data = '<img  alt="' . $mdata->title . '" src="' . $mdata->url . '"/>';
                        $message = str_replace($array_replace[$mdata->url], $content_data, $message);
                    }
                }
            }
        }
        if (!function_exists('pccf_filter')) {
            include(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/include/pccf_filter.php');
        }

        if (function_exists('pccf_filter')) {
            $message = pccf_filter($message);
        }

        $content = '<p>' . $message . '</p>';


        $actorstitle = $comment->author->name;
        $addtime = $comment->createdAt;
        $ptime = strtotime($addtime);


        if (function_exists('pccf_filter')) {
            $actorstitle = pccf_filter($actorstitle);
        }


        $addtime_title = date('M', $ptime) . ' ' . date('jS Y', $ptime);
        $addtime = self::format_interval(time() - $ptime, 1) . ' ago';


        $img = $comment->author->avatar->cache;


        $finalResults = '<div class="disqus_main_block">
    <div class="disqus_block">
        <div class="disqus_autor" ><a target="_blank" href="' . $comment->author->profileUrl . '"><img class="disqus_autor_image" src="' . $img . '" /></a></div>
         <div class="disqus_message">
            <div class="disqus_autor_name">
            <a target="_blank" href="' . $comment->author->profileUrl . '">' . $actorstitle . '</a>
            <a  class="disqus_addtime" href="' . $link . '" title="' . $addtime_title . '">' . $addtime . '</a>
            </div><div class="disqus_content">' . $content . '<div class="disqus_see_more"><div class="disqus_see_more_text">see more</div></div></div>
            <div class="disqus_content_bottom"><a  href="' . $link . '#reply-' . $comment->id . '" class="disqus_reply">Reply</a><a  href="' . $link . '#disquss_container" class="disqus_view">View</a></div>
        </div>
         
    </div>
        ' . $inner_content . '</div>';

/// $code = base_convert($id, 10, 36);
//   $finalResults = '<div class="a_msg_container"><iframe src="https://embed.disqus.com/p/' . $code . '" style="width: 100%; min-height: 250px"  seamless="seamless" scrolling="no" frameborder="0" allowtransparency="true"></iframe>' . $inner_content . '</div>';


        $result = self::to_container($finalResults, $trehead_data,$parent_name);


        return $result;
    }

    public static function to_container($inner_data, $trehead_data,$parent_name='')
    {
        $link_id = $trehead_data["post_id"];
        $comment_type = $trehead_data["type"];
        $link = $trehead_data["link"];

        if (!function_exists('template_single_movie_small')) {
            ///try find movies
            include(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/movie_single_template.php');
        }


        if ($parent_name)
        {
            $movie_parents= '@ '.$parent_name.'\'s ';
        }

        if ($comment_type == 'movie') {
            ////movie

            $movie_block = $movie_parents.template_single_movie_small($link_id, '', $link, 2);

        }
        else if ($comment_type == 'post') {
            $new_link = $link;
            if (preg_match('#\:\/\/[^\/]+\/(.+)#',$link,$mach))
            {
                $new_link = $mach[1];
            }



            $movie_block = '<div class="review_block">'.$movie_parents.'<a href="' . $link . '">/' . $new_link . '</a></div>';
        }
        else if ($comment_type == 'critics') {

            //get critic data

            if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
            }

            if (!class_exists('CriticFront')) {
                require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
            }


            global $cfront;
            $cfront = new CriticFront();


            $critic_data = $cfront->cm->get_post($link_id);
            $movie_id = $critic_data->top_movie;
            $critic_type = $critic_data->type;

            $critic_id = $critic_data->aid;
            $author = $cfront->cm->get_author($critic_id);
            $a_name = $author->name;

            if (function_exists('pccf_filter')) {
                $a_name = pccf_filter($a_name);
            }

            /*
              Author type
              0 => 'Staff',
              1 => 'Pro',
              2 => 'Audience'
             */
            $array_type = array(0 => 'Staff',
                1 => 'Critic',
                2 => 'Audience'
            );


            $movie_block = '';
            if ($link_id) {
                $movie_block = template_single_movie_small($movie_id, '', $link, 2);
                $movie_block = trim($movie_block);
            }

            if (!$movie_parents)
            {
                $movie_parents=  '@ '.$a_name.'\'s ';
            }


            $movie_block = '<div class="review_block review_' . $array_type[$critic_type] . '">' . $movie_parents. '"' . $movie_block . '" '.$array_type[$critic_type].' Review</div>';
        }




        $finalResults_big = '<div class="big_block_comment">' . $movie_block . '<div>' . $inner_data . '</div></div>';



        return $finalResults_big;
    }

    public static function get_comment_from_db($count = 10,$pos=0)
    {

        $result = '';


        $sql = "SELECT * FROM `cache_disqus_comments` where is_deleted  =  0 ORDER BY `add_time` desc limit ".$pos.', ' . $count;


        $r1 = Pdo_an::db_results_array($sql);
        if ($r1) {
            foreach ($r1 as $i => $data) {


                $result .= self::add_template($data);


            }
        }
        $result.='<div style="display: none" class="next_cursor">' .( $pos+$count ) . '</div>';
        return $result;

    }


    public static function add_to_db($id, $thread, $data)
    {

        $add_time = $data['createdAt'];
        $add_time = strtotime($add_time);
        //print_r($data);
        $datastring = json_encode($data);
        $deleted = $data['isDeleted'];

        // echo '<br>';

        $sql = "SELECT * FROM `cache_disqus_comments` WHERE `disqus_id`='" . $id . "'  limit 1";
        $r = Pdo_an::db_fetch_row($sql);
        if (!$r) {
            echo 'add '.$id.'<br>';
            $db_code = md5($data);
            $sql = "INSERT INTO `cache_disqus_comments`(`id`, `disqus_id`, `thread`, `data`, `add_time`,`msg_code`,`is_deleted`,`last_update`) 
            VALUES (NULL," . intval($id) . "," . intval($thread) . ",?,?,?,?," . time() . ")";
            Pdo_an::db_results_array($sql, [$datastring, $add_time,$db_code,$deleted]);

            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            Import::create_commit('', 'update', 'cache_disqus_comments', array('disqus_id' => $id), 'disqus', 4, ['skip' => ['id']]);
        }
        else
        {
            echo 'already add '.$id.' =>'.$r->id.'<br>';

            if ($data['isDeleted']) {

                if (!$r->is_deleted)
                {

                echo 'delete '.$id.'<br>';

                $sql ="UPDATE `cache_disqus_comments` SET `is_deleted` =1, `last_update` = ?   WHERE `cache_disqus_comments`.`id` = ".$r->id;
                Pdo_an::db_results_array($sql, [time()]);

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'cache_disqus_comments', array('disqus_id' => $id), 'disqus', 4, ['skip' => ['id']]);
                }

            }
            else if ($data['isEdited']) {
                $db_code = md5($datastring);
                ///update
                //check update date

                if ($db_code != $r->msg_code)
                {
                    echo 'update '.$id.'<br>';
                    echo $db_code.'!= '.$r->msg_code.'<br>';
                    //update
                    $sql ="UPDATE `cache_disqus_comments` SET `data` =?, `msg_code` = ? , `last_update` =? WHERE `cache_disqus_comments`.`id` = ".$r->id;
                    Pdo_an::db_results_array($sql, [$datastring, $db_code,time()]);

                    !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                    Import::create_commit('', 'update', 'cache_disqus_comments', array('disqus_id' => $id), 'disqus', 4, ['skip' => ['id']]);

                }
            }
        }
    }

    public static function prepare_array($response)
    {

       foreach ($response as $i=>$v)
       {
        $thread = $v['thread'];
        $id = $v['id'];
        self::add_to_db($id,$thread,$v);
       }
    }

    public static function get_trehead_data($thread)
    {

        $url = 'https://disqus.com/api/3.0/threads/details.json';

        $endpoint = $url . '?api_secret=' . self::$key . '&forum=' . self::$forum . '&thread=' . $thread ;
        $result = GETCURL::getCurlCookie($endpoint);

        $r = json_decode($result,1);
        $link = $r['response']['link'];
        $idn = $r['response']['identifiers']['0'];
        $data = $r['response'];
        return array('link'=>$link,'idn'=>$idn,'data'=>$data);

    }


    public static function update_trheads($data,$thread)
    {
        $link  = $data['link'];
        $idn  = $data['idn'];
        $data  =json_encode($data['data']);

        ////add to db
        $sql="SELECT * FROM `cache_disqus_treheads` WHERE `trehead_id `='".$thread."' limit 1";
        $r = Pdo_an::db_fetch_row($sql);

        $post_id = null;
        $type = null;
        $count = 0;

        //get post_id

        if (strstr($idn,' '))
        {
            $post_id = substr($idn,0,strpos($idn,' '));
            $post_id = trim($post_id);
        }
        else
        {
            $post_id = $idn;
        }
        $post_id = intval($post_id);

        ////get type

        if (strstr($link,'/critics/'))
        {
            $type = 'critics';
        }
        else if (strstr($link,'/movies/') || strstr($link,'/tvseries/') )
        {
            $type = 'movie';
        }
        else
        {
            $type = 'post';
        }



        if ($r)
        {
            //update

        }
        else
        {

            $sql="INSERT INTO `cache_disqus_treheads`(`id`, `post_id`, `type`, `trehead_id`, `idn`, `link`, `count`, `data`, `last_update`) 
                        VALUES (NULL,?,?,?,?,?,?,?,?)";

            $array = [$post_id,$type,$thread,$idn,$link,$count,$data,time()];

            Pdo_an::db_results_array($sql,$array);
        }



    }

    public static function check_treheads()
    {

        $sql = "SELECT cache_disqus_comments.thread from  cache_disqus_comments 
    left join cache_disqus_treheads on cache_disqus_treheads.trehead_id =cache_disqus_comments.thread
WHERE cache_disqus_treheads.id is NULL";

     //   echo $sql;

        $rows = Pdo_an::db_results_array($sql);
        foreach ($rows as $r)
        {
            $thread  =  $r['thread'];
            $data  = self::get_trehead_data($thread);
            self::update_trheads($data,$thread);

        }


    }

    public static function set_count($thread)
    {

        $sql="SELECT COUNT(*) as count FROM cache_disqus_comments where `thread` = '{$thread}'";
        $r = Pdo_an::db_fetch_row($sql);
        $count =$r->count;
        if (!$count)$count=null;

        ///select count
        ///
        $sql ="SELECT `count` FROM `cache_disqus_treheads` WHERE `trehead_id` ='{$thread}' limit 1";
        $r1 = Pdo_an::db_fetch_row($sql);

        if ($r1->count<$count)
        {

            ///update
            $sql = "UPDATE `cache_disqus_treheads` SET `count` = '".$count."' WHERE `cache_disqus_treheads`.`trehead_id` = {$thread}; ";
            Pdo_an::db_results_array($sql);

            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            Import::create_commit('', 'update', 'cache_disqus_treheads', array('trehead_id' => $thread), 'disqus',4,['skip'=>['id']]);

        }

    }

    public static function disqus_comments($limit=50,$cursor='') {


       //$limit = '10'; // The number of comments you want to show
    $thread = '6846668'; // Same as your disqus_identifier
    $url = 'https://disqus.com/api/3.0/forums/listPosts.json';
   // $url='https://disqus.com/api/3.0/threads/list.json';
//&thread='.$thread.

        if (!$limit)
        {
            $limit=10;
        }
        $cursoradd='';

        if ($cursor) {
            $cursoradd = '&cursor=' . $cursor;
        }

    $endpoint = $url . '?api_secret=' . self::$key . '&forum=' . self::$forum . '&include=approved&include=deleted&order=desc&limit=' . $limit . $cursoradd;
        //echo $endpoint.'<br>';
    $result = GETCURL::getCurlCookie($endpoint);
    ///$code =json_decode($result,1);
//        header('Content-Type: application/json');
//        echo $result;
//
//        return;

        $code =json_decode($result,1);

        $response =  $code['response'];

        self::prepare_array($response);

        self::check_treheads();

        foreach ($response as $i=>$v) {
            $thread = $v['thread'];

            ////update count

            self::set_count($thread);
        }

       $new_cursor= $code['cursor']['next'];
        if ($new_cursor && $limit>=100 && $new_cursor!=$cursor)
        {
            self::disqus_comments($limit,$new_cursor);
        }
        else
        {
           /// echo $new_cursor.'='.$cursor.'<br>';
        }

//    header('Content-Type: application/json');
//    echo   $result;


}




}