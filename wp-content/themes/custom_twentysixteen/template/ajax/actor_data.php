<?php
error_reporting('E_ALL');
ini_set('display_errors', 'On');

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

!class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';


function get_actors_data()
{
    if (isset($_GET['id'])) {
        include('../video_item_template_single.php');
        $movie_id = $_GET['id'];
        $movie_id = intval($movie_id);

        include(ABSPATH . 'analysis/get_data.php');

        $actor_type = [];
            if (isset($_GET['stars']))
            {
                $actor_type[] = 'star';
            }
            else if (isset($_GET['main'])) {
                $actor_type[] = 'main';
            } else if (isset($_GET['extra'])) {
                $actor_type[] = 'extra';
            } else if (isset($_GET['directors'])) {
                $actor_type[] = 'director';
                $actor_type[] = 'writer';
                $actor_type[] = 'cast_director';
                $actor_type[] = 'producer';
            }
        if (!$actor_type) {
            $actor_type = array('star', 'main', 'extra', 'directors', 'writer', 'cast_director', 'producer');
        }

        $content_array['result'] = MOVIE_DATA::get_actors_template($movie_id, $actor_type);

        $content_array['count'] = count($content_array['result']);
        $content_array['tmpl'] = 'actors';
        $content_array['type'] = 'actors_data';


        $content_string = json_encode($content_array);
        echo $content_string;

        return;

    }
}

echo get_actors_data();

