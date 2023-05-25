<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('TMDBIMPORT') ? include ABSPATH . "analysis/include/tmdb_import.php" : '';

class Movie_Keywords {

    private $array_keys =[];

    public function to_key_content($result,$notag =0)
    {
        $content ='';
      foreach ($result as $id=>$name)
      {
          $keylink= WP_SITEURL.'/search/mkw_'.$id;

          if (!$notag)
          {$cs='class="keyword"';}
          else
          {
              $cb=', ';
          }
          $content.=$cb.'<a '.$cs.' href="'.$keylink.'" >'.$name.'</a>';

      }
      if ($notag) {
       if ($content)$content =substr($content,2);

          return $content;
      }
        $content = '<div class="keyword_container">'.$content.'</div>';

        return $content;


    }

    private function get_keys_from_movie($mid,$only_keywords=0)
    {
        $array_keys=[];
        $keywords_array=[];

        $sql ="SELECT `data_movie_imdb`.`keywords` FROM `data_movie_imdb` 
        WHERE    `data_movie_imdb`.`id` = ".$mid." limit 1";

        $array_request =Pdo_an::db_results_array($sql);
        $keywords = $array_request[0]['keywords'];

            if ($keywords) {
                if (strstr($keywords, ',')) {
                    $keywords_array = explode(',', $keywords);
                } else {
                    $keywords_array[] = $keywords;
                }
            }
            if ($only_keywords)
            {
             return  $keywords_array;
            }


            if ($keywords_array)
            {
                foreach ($keywords_array as $key)
                {
                    $kid = $this->check_add_key($key, 0);
                    if ($kid)
                    {
                        $array_keys  [$kid]=$key;
                    }
                }
            }
            return $array_keys;

    }

    public function front($mid)
    {
        ///get movie keywors
        $array_keys = $this->get_movie_keys($mid);
        $sql='';
        if ($array_keys) {
            foreach ($array_keys as $row) {
                $kid = $row['kid'];
                $sql .= "or id =" . $kid . " ";

            }
            if ($sql) {
                $sql = substr($sql, 2);
                $q = "SELECT * FROM `meta_keywords` where " . $sql;
                $data = Pdo_an::db_results_array($q);
                if ($data)
                {
                    foreach ($data as $dr)
                    {
                        $result[$dr['id']]=$dr['name'];
                    }
                }


            }
        }
        else
        {
            //old method
            $result =   $this->get_keys_from_movie($mid);

        }

        if ($result)
        {   asort($result);

            $content = $this->to_key_content($result);
        }

        echo $content;

    }
    public function get_keywors_array($mid,$get_id='')
    {
        $result=[];
        ///get movie keywors
        $array_keys = $this->get_movie_keys($mid);
        $sql='';
        if ($array_keys) {
            foreach ($array_keys as $row) {
                $kid = $row['kid'];
                $sql .= "or id =" . $kid . " ";

            }
            if ($sql) {
                $sql = substr($sql, 2);
                $q = "SELECT * FROM `meta_keywords` where " . $sql;
                $data = Pdo_an::db_results_array($q);
                if ($data)
                {
                    foreach ($data as $dr)
                    {
                        if ($get_id)
                        {
                            $result[$dr['id']]=$dr['name'];
                        }
                        else
                        {
                            $result[]=$dr['name'];
                        }



                    }
                }


            }
        }
        else
        {
            if ($get_id)
            {
                return ;
            }
            //old method
            $result =   $this->get_keys_from_movie($mid,1);

        }

        return $result;

    }


    private function check_add_key($keys, $update  =1)
    {
        $q = "SELECT `id` FROM `meta_keywords` WHERE `name` = ? ";
        $r = Pdo_an::db_results_array($q,[$keys]);
        if ($r[0])
        {
            return $r[0]['id'];
        }
        else if ($update)
        {
            $q ="INSERT INTO `meta_keywords`(`id`, `name`) VALUES (NULL,?)";
            $id = Pdo_an::db_insert_sql($q,[$keys]);

            return $id;
        }

    }


    private function get_key_id($keys)
    {
        if ($this->array_keys[$keys])
        {
        return $this->array_keys[$keys];

        }
        else
        {
            $id = $this->check_add_key($keys);

            $this->array_keys[$keys] = $id;

            return $id;
        }
    }

    private function check_movie_keys($kid,$mid)
    {

    $q ="SELECT `id` FROM `meta_movie_keywords` WHERE `kid` = ".$kid." and mid = ".$mid;
    $row = Pdo_an::db_results_array($q);
    if ($row)return 1;

    }

    private function get_movie_keys($mid)
    {

        $q ="SELECT * FROM `meta_movie_keywords` WHERE  mid = ".$mid;

        $row = Pdo_an::db_results_array($q);
        if ($row)return $row;

    }

    private function insert_key_request($kid,$mid)
    {
        $q="INSERT INTO `meta_movie_keywords`(`id`, `mid`, `kid`) VALUES (NULL,{$mid},{$kid})";
        $result = Pdo_an::db_query($q);
    }

    private function insert_key_to_movie($kid,$mid){

        if (!$this->check_movie_keys($kid,$mid))
        {
            $this->insert_key_request($kid,$mid);
            return 1;
        }
    }
    private function  add_keys_to_movie($keys,$mid)
    {
        $kid = $this->get_key_id($keys);
        //echo $kid.' ';
        $insert = $this->insert_key_to_movie($kid,$mid);
        return $insert;
    }
	private function fill_main_keys($array,$mid)
	{
    $count=0;

		foreach ($array as $keys)
		{
			// echo $keys.' ';

			$res = $this->add_keys_to_movie($keys,$mid);

            if ($res)$count++;
		}
    return $count;

	}
    private function fill_keys($content,$mid)
    {
        $count=0;

        $regv='/data-item-keyword="([^"]+)"/';
        if (preg_match_all($regv, $content,$match)){

            foreach ($match[1] as $keys)
            {

                global $debug;
                if ($debug)echo $keys.' ';
                $res = $this->add_keys_to_movie($keys,$mid);

                if ($res)$count++;
            }

        }
        else
        {

        $regv='/ \"rowTitle\"\:\"([^\"]+)\"/';
        if (preg_match_all($regv, $content,$match)){

            foreach ($match[1] as $keys)
            {
                global $debug;
                if ($debug)echo $keys.' ';
                $res = $this->add_keys_to_movie($keys,$mid);
                if ($res)$count++;
            }
        }
        }

        return $count;
    }
    private function update_movie_meta($mid,$count=0)
    {


    $q="SELECT `id` FROM `meta_movie_keywords_update` WHERE `mid` ={$mid}";
    if (!Pdo_an::db_results_array($q))
    {
        $q="INSERT INTO `meta_movie_keywords_update`(`id`, `mid`, `last_update`) VALUES (NULL,{$mid},".time().")";
        $r = Pdo_an::db_results_array($q);
        echo ' inserted <br>';
    }
    else
    {
        $q="UPDATE `meta_movie_keywords_update` SET `last_update`= ".time()." WHERE `mid` = {$mid}";
        $r = Pdo_an::db_results_array($q);

        echo ' updated <br>';
    }


        !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
        TMDB::add_log($mid,'','update movies','Total updated '.$count,1,'meta_movie_keywords_update');

    }
    public function parse_imdb_keywords($id)
    {
        !class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

        $final_value = sprintf('%07d', $id);
        $url = "https://www.imdb.com/title/tt" . $final_value . '/keywords?ref_=tt_stry_kw';

        global $debug;
        global $RWT_PROXY;
        $result = GETCURL::getCurlCookie($url,$RWT_PROXY);

        if ($debug)var_dump($result);

        $data = TMDB::actor_data_to_object($result,$debug);

        var_dump($data);

    }

    public function get_movies_keyword($id='')
    {

     //  $res =$this->parse_imdb_keywords($id);
      // return;

// 1.w50  Last 30 days (30)
// 2. w40 Last year  and rating 3-5 (250)
// 3. w30  Last 3 year and rating 4-5 (200)
//4. w20 All time and rating 4-5 (3500)
//5. w10 Last 3 year (4000)
//6. w0 Other (27000)

        $rating_update = array( 50=> 86400*7, 40 =>86400*30, 30=> 86400*60 , 20=> 86400*90, 10=> 86400*180, 0=>86400*360);

        if ($id)
        {
            $where=" data_movie_imdb.id = ".intval($id);
        }
        else
        {
            $where='meta_movie_keywords_update.id IS NULL';

            foreach ($rating_update as $w =>$period){
                $time = time()-$period;
                $where.=" OR (`meta_movie_keywords_update`.last_update < ".$time." and  `data_movie_imdb`.`weight` =".$w." ) ";
            }
        }

////get movie list
	    $sql ="SELECT `data_movie_imdb`.`id`,`data_movie_imdb`.`keywords`, `meta_movie_keywords_update`.last_update FROM `data_movie_imdb` left join `meta_movie_keywords_update` 
       ON `data_movie_imdb`.`id`= meta_movie_keywords_update.mid
        WHERE     ".$where." order by `data_movie_imdb`.`weight` desc LIMIT 200";

        $array_request =Pdo_an::db_results_array($sql);
        foreach ($array_request as $r)
        {
            $mid  =$r['id'];
            ///get keyword data
            $last_update=$r['last_update'];
	        $keywords =$r['keywords'];
	        if ($keywords)
	        {
		        if (strstr($keywords,','))
		        {
			        $keywords_array=explode(',',$keywords);
		        }
		        else
		        {
			        $keywords_array[]=$keywords;
		        }


	        }
            echo 'try get '.$mid. ' last_update '.date('H:i Y.m.d',$last_update).'<br>';

            $content =  TMDBIMPORT::get_data_from_archive(17,$mid,$last_update);
            global $debug;
            if ($debug )echo $content;
            $count=0;
            if ($content)
            {
            $this->fill_keys($content,$mid);
            }
	        if ($keywords_array)
	        {
		      $count =   $this->fill_main_keys($keywords_array,$mid);
	        }

           // echo $content;
            ///update movie meta
            $this->update_movie_meta($mid,$count);
        }

    }

}
