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

if (!function_exists('template_single_movie')) {

    function template_single_movie($id, $title = '', $name = '', $single = '') {
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

                    $cfront = new CriticFront();
                }
                if ($cfront) {
                    $name = $cfront->get_or_create_ma_post_name($id);
                }
            }
        }

        $movie_meta['release_date'] = $post_an->release;
        $movie_meta['overview'] = $post_an->description;
        $movie_meta['genres'] = $post_an->genre;
        $movie_meta['tmdb_id'] = $post_an->tmdb_id;
        $movie_meta['imdb_id'] = $post_an->movie_id;
        $movie_meta['country'] = $post_an->country;
        $movie_meta['runtime'] = $post_an->runtime;


        $production_companies = $post_an->production;
        if ($production_companies) {
            $production_companies_array = [];
            $production_companies = json_decode($production_companies, 1);
            foreach ($production_companies as $c => $n) {
                $production_companies_array[] = $n;
            }
            if ($production_companies_array[0]) {
                $movie_meta['production_companies'] = implode(', ', $production_companies_array);
            }
        }


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
            if (function_exists('site_url')) {
                $site_url = site_url();
            } else {
                $site_url = 'https://' . $_SERVER['HTTP_HOST'];
            }
            $movie_t = strtolower($post_an->type);
            if ($movie_t == 'movie') {
                $movie_t = 'movies';
            }

            $movie_details = 'Movie Details & Credits';
            if ($movie_t == 'movies') {
                $movie_link_desc = 'class="card_movie_type ctype_movies" title="Movie"';

                $movie_details = 'Movie Details & Credits';
            } else if ($movie_t == 'tvseries') {
                $movie_link_desc = 'class="card_movie_type ctype_tvseries" title="TV Show"';
                $movie_details = 'TV Series Details & Credits';
            } else if ($movie_t == 'VideoGame') {
                $movie_link_desc = 'class="card_movie_type ctype_videogame" title="Game"';
                $movie_details = 'Game Details & Credits';
            }

            if ($name) {
                $link_before = '<a href="' . $site_url . '/' . $movie_t . '/' . $name . '/">';
                $ilin_after = '</a>';
            } else {
                $link_before = '';
                $ilin_after = '';
            }
        }


        $content = '';

        $_wpmoly_movie_release_date = $movie_meta['release_date'];



        if ($_wpmoly_movie_release_date) {

            $date = date('F d, Y', strtotime($_wpmoly_movie_release_date));

            $content .= '<span>Release Date: ' . $date . ' </span>';
        }

        $_wpmoly_movie_production_companies = $movie_meta['production_companies'];

        if ($_wpmoly_movie_production_companies) {
            $content .= '<span>| ' . $_wpmoly_movie_production_companies . '</span>';
        }

        $_wpmoly_movie_overview = '<div class="block block_summary"><span>Summary: </span>' . $movie_meta['overview'] . '</div>';


        $_wpmoly_movie_genres = $movie_meta['genres'];


        if ($_wpmoly_movie_genres) {

            $genre_array = explode(',', $_wpmoly_movie_genres);
            $array_genre = [];
            foreach ($genre_array as $val) {
                $val = trim($val);
                //  $array_genre[] = '<a href="' . $site_url . '/genre/' . $val . '/sort_by/movie_asc/">' . $val . '</a>';
                $array_genre[] = $val;
            }
            $genre_string = implode($array_genre, ', ');
            $_wpmoly_movie_genres = '<div class="block"><span>Genres: </span>' . $genre_string . '</div>';
        }

        $_wpmoly_movie_director = $movie_meta['director'];
        if ($_wpmoly_movie_director) {
            $_wpmoly_movie_director = '<div class="block"><span>Director: </span>' . $_wpmoly_movie_director . '</div>';
        }

        $_wpmoly_movie_runtime = $movie_meta['runtime'];
        if ($_wpmoly_movie_runtime) {
            $_wpmoly_movie_runtime = '<div class="block"><span>Runtime: </span>' . format_movie_runtime($_wpmoly_movie_runtime) . '</div>';
        }
        $_wpmoly_movie_country = $movie_meta['country'];
        if ($_wpmoly_movie_country) {
            $_wpmoly_movie_country = explode(',', $_wpmoly_movie_country);
            $array_wpmoly_movie_country = [];
            foreach ($_wpmoly_movie_country as $val) {
                if ($val)
                    $array_wpmoly_movie_country[] = $val;
            }
            $_wpmoly_movie_country = implode($array_wpmoly_movie_country, ', ');
            if (count($array_wpmoly_movie_country) > 1) {
                $countries = 'Countries';
            } else {
                $countries = 'Country';
            }
            $_wpmoly_movie_country = '<div class="block"><span>' . $countries . ': </span>' . $_wpmoly_movie_country . '</div>';
        }
        $_wpmoly_movie_budget = $movie_meta['budget'];
        if ($_wpmoly_movie_budget) {
            $_wpmoly_movie_budget = '<div class="block"><span>Movie budget: </span> $ ' . $_wpmoly_movie_budget . '</div>';
        }

        $_wpmoly_buttom = '';

        ////get wach
        $sql = "SELECT * FROM `just_wach`  where rwt_id='{$id}' and addtime>0";
        $wach_data = Pdo_an::db_fetch_row($sql);
        if ($wach_data->data || strtotime($_wpmoly_movie_release_date) < time() + 86400 * 30) {
            $year_string = '';
            if ($post_an->year) {

                $year_string = ' data-year="' . ($post_an->year) . '" ';
            }

            $_wpmoly_buttom = '<button style="font-size: 18px;" class="watch_buttom" id="' . $id . '" ' . $year_string . ' data-title="' . ($post_an->title) . '" data-type="' . ($post_an->type) . '">Watch Now</button>';
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

                $class = 'ready_to_load';
                $button = '<a href="#" class="button_play_trailer" id="' . $trailer_data . '">Play Trailer</a>';
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

        <div id="<?php echo $id ?>" class="movie_container <?php echo $single_class; ?>">
            <div class="movie_poster">
        <?php echo $link_before; ?>
                <div class="image">
                    <div class="wrapper" style="min-width: 220px;min-height: 330px;">
                        <span <?php echo $movie_link_desc; ?> ></span>
                        <img loading="lazy" class="poster" src="<?php echo $array_tsumb[0]; ?>"
        <?php if ($array_tsumb[1]) { ?> srcset="<?php echo $array_tsumb[0]; ?> 1x, <?php echo $array_tsumb[1]; ?> 2x"<?php } ?> >
                    </div>
                </div>
        <?php echo $ilin_after . '<div class="movie_button_action">' . $_wpmoly_buttom . '<span class="show_more_movie">' . $link_before . 'Show more' . $ilin_after . '</span></div>'; ?>

            </div>
            <div class="movie_watch" style="display: none"></div>
            <div class="movie_description">
                <div class="header_title">
                    <h1 class="entry-title"><?php echo $link_before . $title . $ilin_after; ?></h1><div <?php echo $class_dop; ?> class="play_trailer <?php echo $class ?>" id="<?php echo $id ?>"><?php echo $button; ?></div>
                </div>
                <div class="movie_description_container">
                    <div class="movie_credits_bloc">
                        <div class="mcb_title"><?php echo $movie_details ?></div>
                        <div class="mcb_content"><?php echo $content ?></div>

                    </div>
                    <div class="movie_summary">
        <?php echo $_wpmoly_movie_overview . $_wpmoly_movie_director . $_wpmoly_movie_genres . $_wpmoly_movie_runtime . $_wpmoly_movie_country . $_wpmoly_movie_budget ?>
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


        if ($onlytitle) {
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

