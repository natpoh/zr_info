<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

class DISQUS{

    public static $key = 'Zt8xSiTUeoQuBLJ060aEdofTRBzQRTq6uMkn5Xwm5GsNZzTyatx37i9valgksE5B'; // TODO replace with your Disqus secret key from http://disqus.com/api/applications/
    public static $forum = 'hollywoodstfu'; // Disqus shortname


    public static function add_to_db($id,$thread,$data)
    {
        $data =json_encode($data);

        $sql="SELECT * FROM `cache_disqus_comments` WHERE `disqus_id`='".$id."' limit 1";
        $r = Pdo_an::db_fetch_row($sql);
        if (!$r)
        {
            $sql ="INSERT INTO `cache_disqus_comments`(`id`, `disqus_id`, `thread`, `data`, `last_update`) 
            VALUES (NULL,".intval($id).",".intval($thread).",?,".time().")";
            Pdo_an::db_results_array($sql,[$data]);


            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            Import::create_commit('', 'update', 'cache_disqus_comments', array('disqus_id' => $id), 'disqus',4,['skip'=>['id']]);

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

        ///"25971 https://rightwingtomatoes.com/critics/25971-Pro-Soiled_Sinema-Medea_1988/?meta=32055"
        ///"25971 https://rightwingtomatoes.com/critics/25971-Pro-Soiled_Sinema-Medea_1988/?meta=32055"


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

    $endpoint = $url . '?api_secret=' . self::$key . '&forum=' . self::$forum . '&limit=' . $limit . $cursoradd;
        echo $endpoint.'<br>';
    $result = GETCURL::getCurlCookie($endpoint);
    ///$code =json_decode($result,1);

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