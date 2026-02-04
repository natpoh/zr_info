<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


class Data_Loaded
{

    public static function get_keywords($id)
    {

        !class_exists('Movie_Keywords') ? include ABSPATH . "analysis/include/keywords.php" : '';

        $keywords = new Movie_Keywords;

        $data = $keywords->front($id,1);

        return $data;
    }

    public static function get_movie_total_rating($post_id)
    {
        global $included;
        $included=1;

        if (!function_exists('get_movie_rating'))
        {
            include ABSPATH.'wp-content/themes/custom_twentysixteen/template/ajax/movie_rating.php';
        }

      $data =   get_movie_rating($post_id);

        return $data;

    }

    public static function get_data_content($movie_id,$type,$data_type = 'actor_data')
    {

        $result='';
        if ($data_type=='actor_data')
        {
            $actor_type =[];

            if ($type =='stars')
            {
                $actor_type[] = 'star';
            }
            else if ($type =='main') {
                $actor_type[] = 'main';
            } else if ($type =='extra') {
                $actor_type[] = 'extra';
            } else if ($type =='directors') {
                $actor_type[] = 'director';
                $actor_type[] = 'writer';
                $actor_type[] = 'cast_director';
                $actor_type[] = 'producer';

            }

        !class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';

        $content_array = MOVIE_DATA::get_actors_template($movie_id, $actor_type);



        foreach ($content_array as $i=> $data)
        {


          $result.=  $data['content_data'];

        }

        if ($result)
        {
            $result ='<div class="column_content flex scroller">'.$result. '</div>';
        }

        }

     return $result;

    }




}