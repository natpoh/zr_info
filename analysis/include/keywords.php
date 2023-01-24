<?php

!class_exists('TMDBIMPORT') ? include ABSPATH . "analysis/include/tmdb_import.php" : '';

class Movie_Keywords {

    private $array_keys =[];


    private function check_add_key($keys)
    {
        $q = "SELECT `id` FROM `meta_keywords` WHERE `name` = ? ";
        $r = Pdo_an::db_results_array($q,[$keys]);
        if ($r[0])
        {
            return $r[0]['id'];
        }
        else
        {
            $q ="INSERT INTO `meta_keywords`(`id`, `name`) VALUES (NULL,?)";
            $r = Pdo_an::db_results_array($q,[$keys]);
            $id = Pdo_an::last_id();
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


    private function insert_key_request($kid,$mid)
    {
        $q="INSERT INTO `meta_movie_keywords`(`id`, `mid`, `kid`) VALUES (NULL,{$mid},{$kid})";
        $result = Pdo_an::db_query($q);
    }

    private function insert_key_to_movie($kid,$mid){

        if (!$this->check_movie_keys($kid,$mid))
        {
            $this->insert_key_request($kid,$mid);
        }
    }
    private function  add_keys_to_movie($keys,$mid)
    {
        $kid = $this->get_key_id($keys);
        //echo $kid.' ';
        $this->insert_key_to_movie($kid,$mid);
    }
	private function fill_main_keys($array,$mid)
	{

		foreach ($array as $keys)
		{
			// echo $keys.' ';

			$this->add_keys_to_movie($keys,$mid);
		}


	}
    private function fill_keys($content,$mid)
    {
        $regv='/data-item-keyword="([^"]+)"/';
        if (preg_match_all($regv, $content,$match)){

            foreach ($match[1] as $keys)
            {
               // echo $keys.' ';

                $this->add_keys_to_movie($keys,$mid);
            }

        }


    }
    private function update_movie_meta($mid)
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





    }

    public function get_movies_keyword($id='')
    {


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
            if ($content)
            {
            $this->fill_keys($content,$mid);
            }
	        if ($keywords_array)
	        {
		        $this->fill_main_keys($keywords_array,$mid);
	        }

           // echo $content;
            ///update movie meta
            $this->update_movie_meta($mid);
        }

    }

}
