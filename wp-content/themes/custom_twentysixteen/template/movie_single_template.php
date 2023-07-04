<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('CreateTsumbs') ? include ABSPATH . "analysis/include/create_tsumbs.php" : '';

if (!function_exists('format_movie_runtime')) {

    function format_movie_runtime($data, $format = null) {
        if (is_numeric($data)) {
            $format = 'G \h i \m\i\n';
            $output = date($format, mktime(0, 0, $data));

            return $output;
        }
    }

}

class MovieSingle {

    public static function get_franchise($mid){
        $data=[];
        $q="SELECT `franchise` FROM `data_movie_indie` WHERE `movie_id`= ".$mid." and `franchise`>0";
        $r= Pdo_an::db_results_array($q);
        $count = count($r);
        foreach ($r as $row)
        {
            $fid = $row['franchise'];
            if ($fid)
            {
                $q ="SELECT `name` FROM `data_movie_franchises` WHERE `id` =".$fid;
                $rf =Pdo_an::db_fetch_row($q);
                $name = $rf->name;
                $data[]= '<a target="_blank" href="' . WP_SITEURL . '/search/show_franchise/franchise_' .$fid . '">' .$name. '</a>';
            }

        }
        $count = count($data);
        if ($count)
        {
            $s='';
            if ($count>1)$s='s';
            $data_string = implode(', ', $data);
            $data_string= '<div class="block"><span>Franchise'.$s.': '.$data_string.'</span></div>';

            return $data_string;
        }

    }
    private static function get_actor_name($aid) {
        $q = "SELECT `name` FROM `data_actors_imdb` where id =" . $aid;
        $r = Pdo_an::db_fetch_row($q);
        return $r->name;
    }

    public static function get_productions($mid,$type=0)
    {
        $data=[];

        $q="SELECT ds.* FROM `data_movie_distributors` as ds  LEFT JOIN meta_movie_distributors as m ON ds.`id` = m.`did` and m.`type`='".$type."'  WHERE m.`mid` = ".$mid;

        $r = Pdo_an::db_results_array($q);
        foreach ($r as $row)
        {

            if ($type==0)
            {
              $l =   '/search/show_production/production_';
            }
            else if ($type==1)
            {
                $l =   '/search/show_distributor/distributor_';
            }


            $data[]= '<a target="_blank" href="' . WP_SITEURL .$l . strtolower($row['id']) . '">' . $row['name'] . '</a>';

        }

        $data_string = implode(', ', $data);
        return $data_string;
    }

    public static function director_template($id) {

        $content_release = '';


        $director_result = [];
        ////get movie director
        $director_types = array('Director' => 1, 'Writer' => 2, 'Cast director' => 3);
        $sql = "SELECT * FROM meta_movie_director WHERE mid={$id}  and (`type` = 1 OR `type` = 2)";
        $r = Pdo_an::db_results_array($sql);
        foreach ($r as $row) {
            $director_result[$row['type']][$row['aid']] = 1;
        }
        !class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';
        if ($director_result) {
            foreach ($director_types as $name => $type) {
                if ($director_result[$type]) {
                    $s='';
                    $actors = '';
                    if (count($director_result[$type])>1)$s='s';
                    foreach ($director_result[$type] as $aid => $enable) {
                        $actor_name = self::get_actor_name($aid);


                        $pupup_data = MOVIE_DATA::single_actor_template($aid, $name);


                        $actors .= ', <span class="actor_link_block">
<a target="_blank" href="' . WP_SITEURL . '/search/dirall_' . $aid . '">' . $actor_name . '</a> 
<div class="note nte "><div class="btn toggle_show"></div>
                 <div class="nte_show dwn"><div class="nte_in"><div class="nte_cnt"><div class="note_show_content_adata" >' . $pupup_data . '</div></div></div></div>
                 </div>
</span>';
                    }
                    if ($actors) {
                        $actors = substr($actors, 2);
                    }

                    $content_release .= '<div class="block"><span>' . $name .$s. ': </span>' . $actors . '</div>';
                }
            }
        }
        return $content_release;
    }

}

if (!function_exists('template_single_movie')) {

    function template_single_movie($id, $title = '', $name = '', $single = '', $movie_object = '') {
        /////check content

        global $post_an;
        if (!$post_an || $post_an->id != $id) {
            $sql = sprintf("SELECT * FROM data_movie_imdb WHERE id=%d", (int) $id);
            $post_an = Pdo_an::db_fetch_row($sql);
        }

        if (!$title) {
            $title = $post_an->title;
        }
        if (!$name && !$single) {
            $name = $post_an->post_name;
            if (!$name) {
                if (!$name) {
                    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                    }

                    if (!class_exists('CriticFront')) {
                        require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
                    }
                    global $cfront;
                    $cfront = new CriticFront();
                }
                if ($cfront) {
                    $name = $cfront->get_or_create_ma_post_name($id);
                }
            }
        }

        $movie_attr = '';
        // Search values
        $sort_val_theme = '';
        if ($movie_object) {
            $sort_val_theme = $movie_object->sort_val_theme;

            // Rotten tomatoes
            $movie_arr = array(
                'rrt' => $movie_object->rrt,
                'rrta' => $movie_object->rrta,
                'rrtg' => $movie_object->rrtg,
            );
            $string_attrs = array();
            foreach ($movie_arr as $key => $value) {
                $string_attrs[] = $key . "_" . $value;
            }
            $movie_attr = 'data-attr="' . implode("__", $string_attrs) . '"';
        }

        $movie_meta['release_date'] = $post_an->release;
        $movie_meta['overview'] = $post_an->description;
        $movie_meta['genres'] = $post_an->genre;
        $movie_meta['tmdb_id'] = $post_an->tmdb_id;
        $movie_meta['imdb_id'] = $post_an->movie_id;
        $movie_meta['country'] = $post_an->country;
        $movie_meta['runtime'] = $post_an->runtime;
        $movie_meta['mpaa'] = $post_an->contentrating;




        if ($post_an->productionBudget) {
            $movie_meta['budget'] = number_format($post_an->productionBudget);
        }




        $thumbs = array([220, 330], [440, 660]);
        $array_tsumb = array();
        global $cfront;
        if ($cfront) {
            foreach ($thumbs as $thumb) {
                $array_tsumb[] = $cfront->get_thumb_path_full($thumb[0], $thumb[1], $id);
            }
        } else {
            $array_tsumb = CreateTsumbs::get_poster_tsumb_fast($id, $thumbs);
        }


        if ($id) {

            $movie_t = strtolower($post_an->type);
            if ($movie_t == 'movie') {
                $movie_t = 'movies';
            }

            $movie_details = 'Movie Details & Credits';
            if ($movie_t == 'movies') {
                $movie_link_desc = 'class="card_movie_type ctype_movies" title="Movie"';
                $tmd = 'Movie';

                $movie_details = 'Movie Details & Credits';
            } else if ($movie_t == 'tvseries') {
                $movie_link_desc = 'class="card_movie_type ctype_tvseries" title="TV Show"';
                $movie_details = 'TV Series Details & Credits';

                $tmd = 'TV Series';
            } else if ($movie_t == 'videogame') {
                $movie_link_desc = 'class="card_movie_type ctype_videogame" title="Game"';
                $movie_details = 'Game Details & Credits';

                $tmd = 'Game';
            }

            if ($name) {
                $link_before = '<a href="' . WP_SITEURL . '/' . $movie_t . '/' . $name . '/">';
                $ilin_after = '</a>';
            } else {
                $link_before = '';
                $ilin_after = '';
            }
        }


        $content_release = '';

        $_wpmoly_movie_release_date = $movie_meta['release_date'];



        if ($_wpmoly_movie_release_date) {

            $date = date('F d', strtotime($_wpmoly_movie_release_date));
            $date_y = date('Y', strtotime($_wpmoly_movie_release_date));
            $content_release = '<div class="block"><span>Release Date:</span> ' . $date . ', <a href="' . WP_SITEURL . '/search/release_'.$date_y.'-'.$date_y.'">'.$date_y.'</a></div>';
        }

//        $_wpmoly_movie_production_companies = $movie_meta['production_companies'];
//
//        if ($_wpmoly_movie_production_companies) {
//            $content_release .= '| ' . $_wpmoly_movie_production_companies . ' ';
//        }
        $production_companies = MovieSingle::get_productions($id,0);
        if ($production_companies)
        {
            $production_block= '<div class="block"><span>Production:</span> ' .   $production_companies.'</div>';
        }
        $distributors= MovieSingle::get_productions($id,1);
        if ($distributors)
        {
            $distributors_block= '<div class="block"><span>Distributor:</span> ' .   $distributors.'</div>';
        }


        $_wpmoly_movie_overview = '<div class="block block_summary"><span>Summary: </span>' . $movie_meta['overview'] . '</div>';


        $_wpmoly_movie_genres = $movie_meta['genres'];


        if ($_wpmoly_movie_genres) {


            $genre_array = explode(',', $_wpmoly_movie_genres);
            $array_genre = [];

            if (count($genre_array)==1)
            {
                $gstring='Genre';
            }
            else
            {
                $gstring='Genres';
            }
            foreach ($genre_array as $val) {
                $val = trim($val);
                $array_genre[] = '<a target="_blank" href="' . WP_SITEURL . '/search/genre_' . strtolower($val) . '">' . $val . '</a>';
                //$array_genre[] = $val;
            }
            $genre_string = implode(', ', $array_genre);
            $_wpmoly_movie_genres = '<div class="block"><span>'.$gstring.': </span>' . $genre_string . '</div>';
        }

        ///franchise

        $franchise = MovieSingle::get_franchise($id);

//        if ($content_release || $_wpmoly_movie_genres) {
//            $content_release = '<div class="single_flex">' . $content_release .$_wpmoly_movie_genres. '</div>';
//        }


        $director_result = MovieSingle::director_template($id);

        if ($director_result) {


            $_wpmoly_movie_director = '<div class="single_flex">' . $director_result . '</div>';
        }

        $_wpmoly_movie_runtime = $movie_meta['runtime'];
        if ($_wpmoly_movie_runtime) {
            $_wpmoly_movie_runtime = '<div class="block"><span>Runtime: </span>' . format_movie_runtime($_wpmoly_movie_runtime) . '</div>';
        }

        if ($movie_meta['mpaa']){$mpaa = '<div class="block"><span>MPAA: </span>' . $movie_meta['mpaa']. '</div>';}


        $_wpmoly_movie_country = $movie_meta['country'];
        if ($_wpmoly_movie_country) {
            $_wpmoly_movie_country = explode(',', $_wpmoly_movie_country);

            $array_wpmoly_movie_country = [];
            foreach ($_wpmoly_movie_country as $val) {
                if ($val)
                    $array_wpmoly_movie_country[] = '<a target="_blank" href="' . WP_SITEURL . '/search/country_' . str_replace(' ', '-', strtolower($val)) . '">' . $val . '</a>';
            }
            $_wpmoly_movie_country = implode(', ', $array_wpmoly_movie_country);
            if (count($array_wpmoly_movie_country) > 1) {
                $countries = 'Countries';
            } else {
                $countries = 'Country';
            }
            $_wpmoly_movie_country = '<div class="block"><span>' . $countries . ': </span>' . $_wpmoly_movie_country . '</div>';
        }
        $_wpmoly_movie_budget = $movie_meta['budget'];
        if ($_wpmoly_movie_budget) {
            $_wpmoly_movie_budget = '<div class="block"><span>' . $tmd . ' budget: </span> $ ' . $_wpmoly_movie_budget . '</div>';
        }

        if ($post_an->box_usa) {
            $_box_usa = '<div class="block"><span>Domestic: </span> $ ' . number_format($post_an->box_usa) . '</div>';
        }
        if ($post_an->box_world) {
            if ($post_an->box_usa) {

                $bi = intval($post_an->box_world) - intval($post_an->box_usa);
                if ($bi > 0) {
                    $_box_international = '<div class="block"><span>International: </span> $ ' . number_format($bi) . '</div>';
                }
            }
        }

        $_wpmoly_buttom = '';

        ////get wach
        $sql = "SELECT * FROM `just_wach`  where rwt_id='{$id}' and addtime>0";
        $wach_data = Pdo_an::db_fetch_row($sql);


        if ($movie_t == 'videogame') {

            $wb = 'Play';

        }
        else
        {
            $wb = 'Watch';
        }

            if ($wach_data->data || strtotime($_wpmoly_movie_release_date) < time() + 86400 * 30) {
                $year_string = '';
                if ($post_an->year) {

                    $year_string = ' data-year="' . ($post_an->year) . '" ';
                }

                $_wpmoly_buttom = '<button style="font-size: 18px;" class="watch_buttom" id="' . $id . '" ' . $year_string . ' data-title="' . ($post_an->title) . '" data-type="' . ($post_an->type) . '">'.$wb.' Now</button>';
            }



        if ($single) {


            $single_class = ' single_post ';
            $class_movie = '';
            $class_dop = '';
            if ($id) {

                $class = 'check_load';
            } else if ($movie_meta['imdb_id']) {
                $class = 'check_load_imdb';
                $class_dop = ' imdb="' . $movie_meta['imdb_id'] . '" ';

                if ($movie_meta['tmdb_id']) {

                    $class_dop .= ' tmdb="' . $movie_meta['tmdb_id'] . '" ';
                }
            }

            $class = '';

            /////check trailer

            if (!function_exists('check_movie_trailer')) {
                include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/check_movie_trailer.php";
            }
            $trailer_data = last_movie_trailer($post_an->id);

            if ($trailer_data) {


                if ($movie_t == 'videogame') {
                  $plb =   'Gameplay';
                }
                else
                {
                    $plb    ='Play Trailer';
                }


                $class = 'ready_to_load';
                $button = '<a href="#" class="button_play_trailer" id="' . $trailer_data . '">'.$plb.'</a>';
            } else {
                $class = 'check_load';
            }
        } else {
            $year = $post_an->year;
            if ($year && !strstr($title, $year)) {
                $title .= ' (' . $year . ')';
            }
        }
        ?>

        <div id="<?php echo $id ?>" class="movie_container <?php echo $single_class; ?>" <?php echo $movie_attr; ?>>
            <div class="movie_poster">
                <?php echo $link_before; ?>
                <div class="image">
                    <div class="wrapper" style="min-width: 220px;min-height: 330px;">
                        <span <?php echo $movie_link_desc; ?> ></span>
                        <img loading="lazy" class="poster" src="<?php echo $array_tsumb[0]; ?>"
                             <?php if ($array_tsumb[1]) { ?> srcset="<?php echo $array_tsumb[0]; ?> 1x, <?php echo $array_tsumb[1]; ?> 2x"<?php } ?> >
                    </div>
                </div>
                <?php if ($sort_val_theme): ?>
                    <div class="poster-sort-view">
                        <?php print $sort_val_theme ?>
                    </div>
                <?php endif ?>
                <?php echo $ilin_after . '<div class="movie_button_action">' . $_wpmoly_buttom . '<span class="show_more_movie">' . $link_before . 'Show more' . $ilin_after . '</span></div>'; ?>

            </div>
            <div class="movie_watch" style="display: none"></div>
            <div class="movie_description">
                <div class="header_title">
                    <h1 class="entry-title"><?php echo $link_before . $title . $ilin_after; ?></h1><div <?php echo $class_dop; ?> class="play_trailer <?php echo $class ?>" id="<?php echo $id ?>"><?php echo $button; ?></div>
                </div>
                <div class="movie_description_container">


                    <div class="movie_summary">
                        <?php
                        if (function_exists('current_user_can')) {
                            if (current_user_can("administrator")) {
                                print 'Movie <a class="link_adimin_info" target="_blank" href="https://info.antiwoketomatoes.com/wp-admin/admin.php?page=critic_matic_movies&mid=' . $id . '">adimin info</a>.<br />';
                            }
                        }



                        echo $_wpmoly_movie_overview . '<div class="single_grid">'. $content_release.$_wpmoly_movie_genres . $director_result.'</div>
<div class="single_grid">'. $_wpmoly_movie_runtime.$mpaa.'</div>
                               <hr><div class="single_flex">' .
                            $production_block .$distributors_block.
                            '</div>
                        <div class="single_grid">' .
                          $_wpmoly_movie_country .$franchise.
                        '</div>' .
                        '<div class="single_flex">' . $_wpmoly_movie_budget . $_box_usa . $_box_international . '</div>';
                        ?>
                    </div>
                </div>
            </div>
        </div>


        <?php
    }

}
if (!function_exists('template_single_movie_small')) {

    function template_single_movie_small($id, $title = '', $link = '', $onlytitle = '') {

        global $post_an;
        if (!$post_an || $post_an->id != $id) {
            $sql = sprintf("SELECT * FROM data_movie_imdb WHERE id=%d", (int) $id);
            $post_an = Pdo_an::db_fetch_row($sql);
        }
        if (!$post_an)
            return;

        if (!$title) {
            $title = $post_an->title;
        }

        $movie_meta['year'] = $post_an->release;
        $movie_meta['overview'] = $post_an->description;

        if ($movie_meta['year']) {
            $date = strtotime($movie_meta['year']);
            $date = date('Y', $date);
            if (strstr($title, $date)) {
                $date = '';
            } else {
                $date = ' (' . $date . ')';
            }
        }



        $thumbs = array([90, 120]);

        $array_tsumb = CreateTsumbs::get_poster_tsumb_fast($id, $thumbs);


        if ($array_tsumb) {
            $imgsrc = $array_tsumb[0];
            $img = '<img src="' . $imgsrc . '">';
        }

        if ($onlytitle == 2) {
            $content = $title . $date;
        } else if ($onlytitle) {
            $content = '<div class="movie_small">' . $img . '<div class="movie_small_desc">' . $title . $date . '</div></div>';
        } else {
            $content = '<div class="full_review_movie">' . $img . '<div class="movie_link_desc"><span  class="itm_hdr">' . $title . $date . '</span><span>' . $movie_meta['overview'] . '</span></div></div>';
        }

        if ($link) {
            $content = '<a href="' . $link . '">' . $content . '</a>';
        }
        return $content;
    }

}

