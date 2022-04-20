<?php

/*
 * One time transit all critic posts from old to new database
 * TODO
 * Find feeds posts
 * For each post add new cm post
 * Add meta to connect posts
 * Add admin page to show post connects
 * 
 * 
 * Post content:
 *  wp_bcw98b_wprss_items
 *  
 * Post meta. Link, Source, date
  wprss_item_permalink	https://www.pluggedin.com/blog/plugged-in-picks-tv...
  wprss_item_movies
  wprss_item_imported_date	2021-06-18 7:25:20
  wprss_item_enclosure
  wprss_item_author	Paul Asay
  wprss_feed_url	https://pluggedin.focusonthefamily.com/blog/movies...
  wprss_feed_source	Plugged In
  wprss_feed_import_id	NULL
  wprss_feed_id	6432
  wprss_feed_category
  wprss_feed_allow	review_all
 * 
 * Get tags.
 * 
 * Other
 * wp_bcw98b_cache_wprss_feed_item_fast
 */

class CriticTransit extends AbstractDB {

    private $cm;
    private $ma;
    private $db = array();
    public $post_category = array(
        0 => 'None',
        1 => 'Proper Review',
        2 => 'Contains Mention',
        3 => 'Related Article'
    );

    public function __construct($cm) {
        $this->cm = $cm;
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            'rwt_meta' => $table_prefix . 'critic_matic_meta',
            'meta' => $table_prefix . 'critic_matic_posts_meta',
            'cm_wpposts_meta' => $table_prefix . 'critic_matic_wpposts_meta',
            'wp_posts' => $table_prefix . 'posts',
            'postmeta' => $table_prefix . 'postmeta',
            'wprss_items' => $table_prefix . 'wprss_items',
            'movie_rss_category' => $table_prefix . 'movie_rss_category',
            'actors' => 'data_actors_all',
            'actor_name' => 'actor_name_unique',
            'actors_imdb' => 'data_actors_imdb',
            'actors_gender_auto' => 'data_actor_gender_auto',
            //Staff db
            'staff_posts' => DB_PREFIX_STF . 'posts',
            'staff_postmeta' => DB_PREFIX_STF . 'postmeta',
            'staff_users' => DB_PREFIX_STF . 'users'
        );
    }

    public function get_ma() {
        // Get criti
        if (!$this->ma) {
            //init cma
            if (!class_exists('MoviesAn')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesAn.php' );
            }
            $this->ma = new MoviesAn();
        }
        return $this->ma;
    }

    public function transit_video_cron($count = 100, $debug = false, $force = false) {
        //1. Get post from db transcripts
        //2. Add critic post
    }

    public function transit_an_post_slug($count = 100, $debug = false) {
        // get an noslug posts
        $ma = $this->get_ma();
        $cfront = new CriticFront($this->cm);
        $no_name_posts = $ma->get_posts_without_post_name($count);
        if ($debug) {
            print_r($no_name_posts);
        }
        if (sizeof($no_name_posts)) {
            /*
              [id] => 1
              [rwt_id] => 36299
              [title] => Extremedays
              [type] => Movie
             */
            foreach ($no_name_posts as $item) {
                $post_name = $cfront->get_or_create_ma_post_name($item->id, $item->rwt_id, $item->title, $item->type);
                if ($debug) {
                    print_r($item);
                    print "Post name: $post_name\n";
                }
            }
        }
    }

    public function transit_providers() {
        return;
//        $ma = $this->get_ma();
//        $providers = $ma->get_providers_list();
//        if (!$providers) {
//            //Import providers
//            $p_opt = $ma->get_providers_from_option();
//            $data = $p_opt->data;
//            foreach ($data as $key => $value) {
//                $id = $key;
//                $name = $value->n;
//                $img = $value->i;
//                print "$id, $name, $img\n";
//                $id = $ma->get_or_create_provider_by_pid($id, $name, $img);
//                print "add item - $id\n";
//            }
//        }
    }

    public function transit_actors($count = 100, $debug = false) {
        // ger movies
        $ma = $this->get_ma();
        $posts_no_meta = $ma->get_movies_no_actors_meta($count);

        if ($debug) {
            print_r($posts_no_meta);
        }

        // transit ganres
        if (sizeof($posts_no_meta)) {
            foreach ($posts_no_meta as $post) {
                $mid = $post->id;
                $actors = $post->actors;
                //Get post genres
                if ($actors) {
                    $to_add = array();
                    if ($actors) {
                        $actors_obj = (array) json_decode($actors);
                        if (!$actors_obj) {

                            if ($mid) {
                                !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
                                $movie_id = TMDB::get_imdb_id_from_id($mid);
                                if ($movie_id) {
                                    $array_movie = TMDB::get_content_imdb($movie_id);
                                    $add = TMDB::addto_db_imdb($movie_id, $array_movie);
                                    continue;
                                }
                            }
                        }
                        $actor_types = array('s' => 1, 'm' => 2, 'e' => 3);
                        foreach ($actor_types as $tstr => $tkey) {
                            if (isset($actors_obj[$tstr])) {
                                foreach ($actors_obj[$tstr] as $key => $value) {
                                    if (!isset($to_add[$key])) {
                                        $to_add[$key] = $tkey;
                                    }
                                }
                            }
                        }
                    }

                    if (sizeof($to_add)) {
                        if ($debug) {
                            print_r($to_add);
                        }

                        // Add actors
                        foreach ($to_add as $id => $type) {
                            $ma->add_movie_actor($mid, $id, $type);

                            if ($debug) {
                                print "Add move actor: $mid, $id, $type\n";
                            }
                        }
                    }
                }
            }
        }
    }

    public function actor_slug($count = 100, $debug = false) {
        $ma = $this->get_ma();
        $actors_no_slug = $ma->get_actors_no_slug($count);

        if ($debug) {
            print_r($actors_no_slug);
        }

        if (sizeof($actors_no_slug)) {
            foreach ($actors_no_slug as $item) {
                $slug = $ma->create_slug($item->name);

                if ($slug) {
                    $slug_u = $slug;
                    $i = 0;
                    while (true) {
                        $exist = $ma->get_actor_by_slug($slug_u);
                        if (!$exist) {
                            break;
                        }
                        $i += 1;
                        $slug_u = $slug . "-" . $i;
                    }
                    $slug = $slug_u;
                } else {
                    $slug = $item->actor_id;
                }

                if ($debug) {
                    print "$item->name - $slug\n";
                }
                $ma->update_actor_slug($item->actor_id, $slug);
            }
        }
    }

    public function transit_directors($count = 100, $debug = false) {
        // ger movies
        $ma = $this->get_ma();
        $director_types = array('director' => 1, 'writer' => 2, 'cast_director' => 3, 'producers' => 4);
        $posts_no_meta = $ma->get_movies_no_director_meta($count);

        // transit ganres
        if (sizeof($posts_no_meta)) {
            foreach ($posts_no_meta as $post) {


                $mid = $post->id;
                if ($debug) {
                    echo 'mid=' . $mid . '<br>';
                }


                foreach ($director_types as $dtype => $did) {

                    $directors = $post->{$dtype};
                    //Get post genres
                    if ($directors) {

                        if ($debug) {
                            echo 'try type ' . $dtype . '(' . $did . ') <br>';
                        }
                        $to_add = array();

                        if ($dtype == 'producers') {
                            //json
                            $producers_array = json_decode($directors, 1);

                            foreach ($producers_array as $id => $name) {
                                $dir_clear = (int) trim($id);
                                if ($dir_clear) {
                                    $to_add[] = $dir_clear;
                                }
                            }
                        } else {
                            if (strstr($directors, ',')) {
                                $dir_arr = explode(',', $directors);
                            } else {
                                $dir_arr = array($directors);
                            }


                            foreach ($dir_arr as $dir) {
                                $dir_clear = (int) trim($dir);
                                if ($dir_clear) {
                                    $to_add[] = $dir_clear;
                                }
                            }
                        }
                        if (sizeof($to_add)) {
                            if ($debug) {
                                echo 'to add db<br>';
                                print_r($to_add);
                                echo '----<br>';
                            }

                            // Add actors
                            foreach ($to_add as $dir) {
                                $ma->add_movie_director($mid, $dir, $did);

                                if ($debug) {
                                    print "Add move " . $dtype . ": $mid, $dir\n<br>";
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function transit_genres($count = 100, $debug = false) {
        // ger movies
        $ma = $this->get_ma();
        $posts_no_meta = $ma->get_movies_no_genre_meta($count);

        if ($debug) {
            print_r($posts_no_meta);
        }

        // transit ganres
        if (sizeof($posts_no_meta)) {
            foreach ($posts_no_meta as $post) {
                $mid = $post->id;
                $genres = $post->genre;
                //Get post genres
                if ($genres) {
                    $to_add = array();
                    if (strstr($genres, ',')) {
                        $explode = explode(',', $genres);
                        foreach ($explode as $value) {
                            $trim = trim($value);
                            if ($trim) {
                                $to_add[] = trim($trim);
                            }
                        }
                    } else {
                        $to_add[] = trim($genres);
                    }

                    if (sizeof($to_add)) {
                        if ($debug) {
                            print_r($to_add);
                        }
                        // Add genres
                        foreach ($to_add as $name) {
                            $gid = $ma->get_or_create_genre_by_name($name);
                            $ma->add_movie_genre($mid, $gid);

                            if ($debug) {
                                print "Add move genre: $name, $mid, $gid\n";
                            }
                        }
                    }
                }
            }
        }
    }

    public function transit_countries($count = 100, $debug = false) {
        // ger movies
        $ma = $this->get_ma();
        $posts_no_meta = $ma->get_movies_no_country_meta($count);

        if ($debug) {
            print_r($posts_no_meta);
        }

        // transit ganres
        if (sizeof($posts_no_meta)) {
            foreach ($posts_no_meta as $post) {
                $mid = $post->id;
                $countrys = $post->country;
                //Get post countrys
                if ($countrys) {
                    $to_add = array();
                    if (strstr($countrys, ',')) {
                        $explode = explode(',', $countrys);
                        foreach ($explode as $value) {
                            $trim = trim($value);
                            if ($trim) {
                                $to_add[] = trim($trim);
                            }
                        }
                    } else {
                        $to_add[] = trim($countrys);
                    }

                    if (sizeof($to_add)) {
                        if ($debug) {
                            print_r($to_add);
                        }
                        // Add countrys
                        foreach ($to_add as $name) {
                            $gid = $ma->get_or_create_country_by_name($name);
                            $ma->add_movie_country($mid, $gid);

                            if ($debug) {
                                print "Add movie country: $name, $mid, $gid\n";
                            }
                        }
                    }
                }
            }
        }
    }

    /*
     * Transit meta to new db
     * `id` int(11) unsigned NOT NULL auto_increment,
      `fid` int(11) NOT NULL DEFAULT '0',
      `type` int(11) NOT NULL DEFAULT '0',
      `state` int(11) NOT NULL DEFAULT '0',
      `cid` int(11) NOT NULL DEFAULT '0',
      `rating` int(11) NOT NULL DEFAULT '0',
     */

    public function transit_an_meta($count = 100, $debug = false) {
        // get meta 
        $sql = sprintf("SELECT m.id, m.cid, m.fid, m.type, m.state, m.rating "
                . "FROM {$this->db['rwt_meta']} m "
                . "LEFT JOIN {$this->db['meta']} pm ON pm.cid = m.cid "
                . "WHERE pm.id is NULL limit %d", (int) $count);
        $results = $this->db_results($sql);

        if (sizeof($results)) {
            $ma = $this->get_ma();
            // find an id for meta
            /*
              [id] => 1
              [cid] => 2
              [fid] => 33709
              [type] => 1
              [state] => 1
              [rating] => 0
             */
            foreach ($results as $item) {
                if ($debug) {
                    print_r($item);
                }
                $fid = 0;
                $state = $item->state;
                $movie_an_id = $ma->get_post_id_by_rwt_id($item->fid);
                if ($movie_an_id) {
                    if ($debug) {
                        print('Add meta. movie_an id: ' . $movie_an_id . "\n");
                    }
                    $fid = $movie_an_id;
                    // Create a new meta
                } else {
                    if ($debug) {
                        print("Can not get movie_an. New trash meta \n");
                    }
                    // Create a new trash meta
                    // Unaproved
                    $state = 0;
                }

                $sql = sprintf("INSERT INTO {$this->db['meta']} (fid,type,state,cid,rating) "
                        . "VALUES (%d,%d,%d,%d,%d)", (int) $fid, (int) $item->type, (int) $state, (int) $item->cid, (int) $item->rating);
                $this->db_query($sql);
            }
        }
    }

    /*
     * import authors
     */

    public function import_authors() {

        // Import authors if is empty
        $authors_count = $this->cm->get_authors_count();
        if ($authors_count) {
            return;
        }

        $sql = "SELECT * FROM {$this->db['wp_posts']} WHERE post_type = 'wprss_feed'";
        $result = $this->db_results($sql);
        if (sizeof($result)) {
            foreach ($result as $item) {

                //Get meta
                $meta = get_post_meta($item->ID);

                // Author options
                $wprss_autoblur = isset($meta['wprss_autoblur'][0]) ? (int) $meta['wprss_autoblur'][0] : 0;
                $status = isset($meta['wprss_is_public'][0]) ? (int) $meta['wprss_is_public'][0] : 0;

                $options = array(
                    'autoblur' => $wprss_autoblur
                );

                //Author
                $author = 0;
                $author_name = trim($item->post_title);
                if ($author_name) {
                    $author_type = $meta['wprss_feed_from'][0] == 0 ? 0 : 1;
                    $author = $this->cm->get_or_create_author_by_name($author_name, $author_type, $status, $options);
                }

                //Add post tags             
                $tags = $this->get_wp_terms($item->ID);

                //print_r($tags);
                /*
                 * [0] => WP_Term Object
                  (
                  [term_id] => 114601
                  [name] => #Christian
                  [slug] => christian
                  [term_group] => 0
                  [term_taxonomy_id] => 114599
                  [taxonomy] => wprss_feed_category
                  [description] =>
                  [parent] => 0
                  [count] => 36
                  [filter] => raw
                  )
                 */
                if ($tags && sizeof($tags)) {
                    foreach ($tags as $tag) {
                        $name = trim($tag->name);
                        $slug = trim($tag->slug);
                        $tag_id = $this->cm->get_or_create_tag_id($name, $slug);
                        if ($tag_id) {
                            $this->cm->add_author_tag($author, $tag_id);
                        }
                    }
                }
                //print_r($item);
                //print_r($meta);
            }
        }
    }

    public function transit_staff($count = 10, $debug = false, $force_update = false) {
        if (class_exists('Pdo_stf')) {
            // TODO select where date < last post date

            $last_transit_id = $this->get_option('last_transit_staff_id', 0);
            if ($force_update) {
                $last_transit_id = 0;
            }


            $sql = sprintf("SELECT p.ID, p.post_title, p.post_date, p.post_name, p.post_content, u.display_name "
                    . "FROM {$this->db['staff_posts']} p INNER JOIN {$this->db['staff_users']} u ON u.ID = p.post_author "
                    . "WHERE p.post_type = 'post' AND p.post_status='publish' AND p.post_password = '' AND p.ID > %d "
                    . "ORDER BY p.ID ASC LIMIT %d", (int) $last_transit_id, (int) $count);
            $results = Pdo_stf::db_results($sql);
            $last_id = 0;
            if (sizeof($results)) {
                foreach ($results as $post) {

                    /* $meta_sql = sprintf("SELECT meta_key, meta_value FROM {$this->db['staff_postmeta']} WHERE post_id = %d", $post->ID);
                      $meta_results = Pdo_stf::db_results($meta_sql);
                      print_r($meta_results);
                      print_r($post);
                      /*
                      [ID] => 4
                      [post_title] => Bio
                      [post_date] => 2017-01-31 19:35:10
                      [post_name] => libertarian_agnostic
                      [display_name] => Libertarian Agnostic
                     */
                    $last_id = $post->ID;
                    if ($debug) {
                        print "last_id => $last_id\n";
                    }
                    $author_name = $post->display_name;
                    // Skip STFU
                    if ($author_name == 'STFU Hollywood') {
                        continue;
                    }

                    $date = strtotime($post->post_date);
                    $title = trim($post->post_title);
                    $content = trim($post->post_content);

                    // Skip norating posts
                    if (!preg_match('#\[stfu_ratings([^\]]+)\]#', $content, $mach)) {
                        continue;
                    }

                    if (!$title) {
                        if ($content) {
                            $title = $this->crop_text(sanitize_text_field($content), 100);
                        }
                    }
                    $link = 'https://oc.rightwingtomatoes.com/' . $post->post_name . '/';

                    // 0 - import
                    $type = 0;

                    if ($debug) {
                        print "$date, $type, $link, $title\n";
                    }

                    // Get this post
                    $link_hash = $this->cm->link_hash($link);

                    //Check the post already in db
                    $old_post = $this->cm->get_post_by_link_hash($link_hash);
                    if ($old_post) {
                        if ($debug) {
                            print "The post already exist: $link\n";
                        }
                        if ($force_update) {
                            //update post                            
                            $this->cm->update_post($old_post->id, $date, $old_post->status, $link, $title, $content, $type);
                            print "Update post: $link\n";
                        }
                    } else {
                        if ($debug) {
                            print "Add new post: $link\n";
                        }

                        //Add post to db
                        $cm_id = $this->cm->add_post($date, $type, $link, $title, $content);

                        if ($cm_id > 0) {

                            //Add post author (source)
                            // 0 - staff
                            $author_type = 0;
                            $author_id = $this->cm->get_or_create_author_by_name($author_name, $author_type);
                            if ($author_id) {
                                //Add post author
                                $this->cm->add_post_author($cm_id, $author_id);
                            }
                            $this->cm->hook_update_post($cm_id);

                            // Add post rating
                            $rating_data = $this->cm->get_post_rating($cm_id);
                            if (!$rating_data) {
                                //one time transit rating
                                $rating_data = $this->cm->transit_post_rating($cm_id, $content);
                            }
                            if ($debug) {
                                print_r($rating_data);
                            }
                        }
                    }
                }

                update_option('last_transit_staff_id', $last_id);
            }
        }
    }

    public function transit_audience($limit = 10, $debug = false) {
        $to_transit = $this->get_audience_posts($limit);
        $ma = $this->get_ma();

        if (sizeof($to_transit)) {
            foreach ($to_transit as $post) {
                //Transit the data to new db
                $transit = true;

                if ($debug) {
                    print "<h2>$post->post_title</h2>\n";
                    print_r($post);
                    /*
                     * stdClass Object
                      (
                      [ID] => 40165
                      [post_author] => 1
                      [post_date] => 2018-02-16 17:45:05
                      [post_date_gmt] => 2018-02-16 17:45:05
                      [post_content] => <p>I watched this movie when I was a kid. I love it so much. Just recently I rewatched it again and I can tell for sure this is one of my favorite movies. This is kind of movies you watch many times and you don’t get tired of it. Actors are great! I love them both (Rachel McAdams and Rob Schneider). I recommend you to watch The Hot Chick. You will spend your time great :)</p>
                      [post_title] => Tulip @ 02/16/2018 05:45
                      [post_excerpt] =>
                      [post_status] => publish
                      [comment_status] => closed
                      [ping_status] => closed
                      [post_password] =>
                      [post_name] => tulip-02-16-2018-0545
                      [to_ping] =>
                      [pinged] =>
                      [post_modified] => 2021-02-07 01:07:38
                      [post_modified_gmt] => 2021-02-07 01:07:38
                      [post_content_filtered] =>
                      [post_parent] => 0
                      [guid] => https://just.stfuhollywood.com/?post_type=wpcr3_review&#038;p=40165
                      [menu_order] => 0
                      [post_type] => wpcr3_review
                      [post_mime_type] =>
                      [comment_count] => 0
                      [id] =>
                      [pid] =>
                      [cid] =>
                      )
                     */
                }
                $pid = $post->ID;

                //Get post meta
                $meta = get_post_custom($post->ID);
                if ($debug) {
                    print_r($meta);
                    /*
                     * Array
                      (
                      [wpcr3_review_ip] => Array
                      (
                      [0] => 109.238.80.72
                      )

                      [wpcr3_review_post] => Array
                      (
                      [0] => 36641
                      )

                      [wpcr3_review_name] => Array
                      (
                      [0] => Tulip
                      )

                      [wpcr3_review_email] => Array
                      (
                      [0] =>
                      )

                      [wpcr3_review_rating] => Array
                      (
                      [0] => 5
                      )

                      [wpcr3_review_rating_hollywood] => Array
                      (
                      [0] => 0
                      )

                      [wpcr3_review_rating_patriotism] => Array
                      (
                      [0] => 0
                      )

                      [wpcr3_review_rating_misandry] => Array
                      (
                      [0] => 0
                      )

                      [wpcr3_review_rating_affirmative] => Array
                      (
                      [0] => 0
                      )

                      [wpcr3_review_rating_lgbtq] => Array
                      (
                      [0] => 0
                      )

                      [wpcr3_review_rating_god] => Array
                      (
                      [0] => 0
                      )

                      [wpcr3_review_title] => Array
                      (
                      [0] => Very kind and funny movie!
                      )

                      [user_virgin] => Array
                      (
                      [0] => 6
                      )

                      [post_grid_post_settings] => Array
                      (
                      [0] => a:10:{s:9:"post_skin";s:4:"flat";s:19:"custom_thumb_source";s:101:"https://rightwingtomatoes.com/wp-content/plugins/post-grid/assets/frontend/css/images/placeholder.png";s:17:"font_awesome_icon";s:0:"";s:23:"font_awesome_icon_color";s:7:"#737272";s:22:"font_awesome_icon_size";s:4:"50px";s:17:"custom_youtube_id";s:0:"";s:15:"custom_vimeo_id";s:0:"";s:21:"custom_dailymotion_id";s:0:"";s:14:"custom_mp3_url";s:0:"";s:20:"custom_soundcloud_id";s:0:"";}
                      )

                      [lazyload_thumbnail_quality] => Array
                      (
                      [0] => default
                      )

                      [wpcr3_rating_vote] => Array
                      (
                      [0] => 1
                      )

                      )
                     */
                }
                $cm_id = 0;

                $title = trim($meta['wpcr3_review_title'][0]);
                $content = trim($post->post_content);

                if (!$title) {
                    if ($content) {
                        $title = $this->crop_text(sanitize_text_field($content), 100);
                    }
                }

                if (!$title) {
                    $transit = false;
                }

                if ($transit) {
                    //Transit post
                    $date = strtotime($post->post_date);
                    $movie_rwt = (int) $meta['wpcr3_review_post'][0];

                    $movie_id = $ma->get_post_id_by_rwt_id($movie_rwt);

                    // Import
                    $type = 0;
                    $link = '';

                    if ($debug) {
                        print "$date, $type, $title\n";
                        print $content;
                    }
                    // Add post to db
                    $cm_id = $this->cm->add_post($date, $type, $link, $title, $content, $movie_id);
                }

                //Post added. Add a meta data
                if ($cm_id > 0) {
                    $author_name = trim($meta['wpcr3_review_name'][0]);
                    //Add post author (source)
                    //Audience
                    if (!$author_name) {
                        $author_name = 'None';
                    }

                    $author_type = 2;
                    $author_id = $this->cm->get_or_create_author_by_name($author_name, $author_type);
                    if ($author_id) {
                        //Add post author
                        $this->cm->add_post_author($cm_id, $author_id);

                        //Add post meta
                        // Proper review
                        $movie_cat = 1;

                        //Approve
                        $state = 1;

                        if ($debug) {
                            print "$movie_id, $movie_cat, $state, $cm_id\n";
                        }
                        //Add post movie meta
                        $this->cm->add_post_meta($movie_id, $movie_cat, $state, $cm_id);
                    }

                    $options = $this->cm->get_rating_from_postmeta($meta);

                    $this->cm->add_rating($cm_id, $options);
                }

                //Update wppost meta
                $this->update_post_meta($pid, $cm_id);
            }
        }
    }

    /*
     * Transit wprrss posts from wp_posts to critic posts.
     */

    public function transit_posts($limit = 10, $debug = false) {
        // UNUSED
        // Import authors
        // $this->import_authors();

        $to_transit = $this->get_nometa_posts($limit);

        if (sizeof($to_transit)) {
            foreach ($to_transit as $post) {
                //Transit the data to new db
                $transit = true;
                if ($debug) {
                    print "<h2>$post->post_title</h2>\n";
                    print_r($post);
                }
                $pid = $post->ID;

                //Get post meta
                $wprss_feed_id = (int) get_post_meta($pid, 'wprss_feed_id', true);
                $wprss_item_permalink = trim(get_post_meta($pid, 'wprss_item_permalink', true));

                $cm_id = 0;

                // Post exist
                $link_hash = '';
                $link = $wprss_item_permalink;
                if ($link) {
                    $link_hash = $this->cm->link_hash($link);
                    //Check the post already in db
                    $post_exist = $this->cm->get_post_by_link_hash($link_hash);
                    if ($post_exist) {
                        $cm_id = $post_exist->id;
                        $transit = false;
                        if ($debug) {
                            print "Post exist, update meta.\n";
                        }
                    }
                }

                if ($transit) {

                    //Pro
                    $author_type = 1;
                    $author_name = 'No name';

                    if ($wprss_feed_id) {
                        // Get author post
                        $author_post = $this->get_wp_post($wprss_feed_id);
                        if (isset($author_post->post_title)) {
                            $author_name = trim($author_post->post_title);
                        }
                    }
                    if ($debug) {
                        print "Author_name: $author_name\n";
                    }
                    // print "$wprss_feed_id $wprss_item_permalink $author_name\n";
                    //Post content
                    $post_content = $this->get_wprss_content($pid);
                    //print $post_content;
                    //Validate fields
                    //State    
                    //Default unapprove
                    $state = 0;

                    $wprss_feed_state = get_post_meta($pid, 'wprss_feed_allow', true);
                    if ($debug) {
                        print "State: $wprss_feed_state\n";
                    }
                    $movies = array();
                    //$movies_arr = array();
                    //$wprss_feed_category = '';
                    //if state is 'approve_all', get post meta
                    if ($wprss_feed_state == 'approve_all') {
                        $state = 1;
                        //Get movies from rss_db
                        //Movie links by rss id
                        $movies = $this->get_movies_by_rss_id($pid);
                        if ($debug) {
                            print_r($movies);
                        }
                        /*
                          //Get movies from meta
                          //Movies links
                          $wprss_item_movies = get_post_meta($pid, 'wprss_item_movies', true);
                          if ($wprss_item_movies) {
                          $movies_arr = explode('|||', $wprss_item_movies);
                          print_r($movies_arr);
                          }
                          //Movie link
                          $wprss_item_movie = get_post_meta($pid, 'wprss_item_movie', true);
                          print "Movie: $wprss_item_movie\n";

                          $user_virgin = get_post_meta($pid, 'user_virgin', true);
                          print "Uservirgin: $user_virgin\n";

                          //Category
                          $wprss_feed_category = get_post_meta($pid, 'wprss_feed_category', true);
                          print "Category: $wprss_feed_category\n\n\n";
                         */
                    }
                }

                //continue;

                if ($transit) {
                    //Transit post
                    $date = strtotime($post->post_date);
                    //print date('Y-m-d H:i:s',$date);

                    $content = trim($post_content);
                    $title = trim($post->post_title);
                    if (!$title) {
                        if ($content) {
                            $title = $this->crop_text(sanitize_text_field($content), 100);
                        }
                    }
                    $type = 0;
                    if ($debug) {
                        print "$date, $type, $link, $title\n";
                    }
                    //print $content;
                    //Add post to db
                    $cm_id = $this->cm->add_post($date, $type, $link, $title, $content);
                }

                //Post added. Add a meta data
                if ($transit && $cm_id > 0) {

                    //Add post author (source)
                    $author_id = $this->cm->get_or_create_author_by_name($author_name, $author_type);
                    if ($author_id) {
                        //Add post author
                        $this->cm->add_post_author($cm_id, $author_id);

                        //Add post meta
                        if (sizeof($movies)) {
                            /*
                             * Example movie object:                        
                              (
                              [id] => 6634
                              [title] => Girls Trip
                              [rss_id] => 3330
                              [category] => Proper Review
                              )
                             */
                            foreach ($movies as $movie) {
                                $movie_id = $this->get_movie_id_by_name($movie->title);
                                $movie_cat = $this->get_post_category_by_name($movie->category);
                                if (!$movie_cat) {
                                    $movie_cat = 0;
                                }
                                if ($debug) {
                                    print "$movie_id, $movie_cat, $state, $cm_id\n";
                                }
                                //Add post movie meta
                                $this->cm->add_post_meta($movie_id, $movie_cat, $state, $cm_id);
                            }
                        }
                    }
                }
                //Update wppost meta
                $this->update_post_meta($pid, $cm_id);
            }
        }
    }

    public function transit_secret_key($debug = false) {
        // Transit secret key to new authors
        $sql = "SELECT * FROM {$this->db['wp_posts']} WHERE post_type = 'wprss_feed'";
        $result = $this->db_results($sql);
        if (sizeof($result)) {
            foreach ($result as $item) {

                //Get meta
                $meta = get_post_meta($item->ID);
                if (isset($meta['wprss_secret_key'][0])) {
                    $secret = $meta['wprss_secret_key'][0];
                    $author_name = trim($item->post_title);

                    if ($author_name) {
                        $author_type = $meta['wprss_feed_from'][0] == 0 ? 0 : 1;
                        $author = $this->cm->get_author_by_name($author_name, false, $author_type);

                        if ($author) {
                            $options = unserialize($author->options);
                            $options['secret'] = $secret;
                            $author->options = $options;

                            if ($debug) {
                                print_r($author);
                            }
                            //Update author options                            
                            $this->cm->update_author($author);
                        }
                    }
                }
            }
        }
    }

    /*
     * Get key for movie post category
     */

    private function get_post_category_by_name($name) {
        $key = array_search($name, $this->post_category);
        return $key;
    }

    private function get_movie_id_by_name($title) {
        $sql = sprintf("SELECT ID FROM {$this->db['wp_posts']} WHERE post_title = '%s'", $title);
        $result = $this->cm->db_get_var($sql);
        return $result;
    }

    private function get_movies_by_rss_id($id) {
        $sql = sprintf("SELECT * FROM {$this->db['movie_rss_category']} WHERE rss_id = %d", $id);
        $result = $this->cm->db_results($sql);
        return $result;
    }

    /*
     * Get terms from wordpress
     */

    private function get_wp_post($id) {
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }

        if (isset($dict[$id])) {
            return $dict[$id];
        }

        $post = get_post($id);

        $dict[$id] = $post;

        return $post;
    }

    /*
     * Get terms from wordpress
     */

    private function get_wp_terms($id) {
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }

        if (isset($dict[$id])) {
            return $dict[$id];
        }

        $tags = get_the_terms($id, 'wprss_feed_category');

        $dict[$id] = $tags;

        return $tags;
    }

    /*
     * Get the wprss content
     */

    private function get_wprss_content($item_id) {
        //item_id
        $sql = sprintf("SELECT item_content FROM {$this->db['wprss_items']} WHERE item_id = %d", $item_id);
        $result = $this->cm->db_get_var($sql);
        return $result;
    }

    /*
     * Add meta for every transit posts
     */

    private function update_post_meta($pid, $cid) {
        // Validate values
        if ($pid >= 0 && $cid >= 0) {
            //Get post meta
            $sql = sprintf("SELECT pid FROM {$this->db['cm_wpposts_meta']} WHERE pid='%d'", $pid);
            $meta_exist = $this->cm->db_get_var($sql);
            if (!$meta_exist) {
                //Meta not exist
                $sql = sprintf("INSERT INTO {$this->db['cm_wpposts_meta']} (pid,cid) VALUES (%d,%d)", (int) $pid, (int) $cid);
                $this->cm->db_query($sql);
            }
            return true;
        }
        return false;
    }

    /*
     * Get wp posts, that not present in critic matic
     * 
     * Example result:
      (
      [ID] => 448240
      [post_date] => 2019-08-07 00:00:00
      [post_title] => Lucker the Necrophagous
      )
     */

    private function get_nometa_posts($limit = 10) {
        $sql = sprintf("SELECT p.ID, p.post_date, p.post_title, p.post_status "
                . "FROM {$this->db['wp_posts']} p "
                . "LEFT JOIN {$this->db['cm_wpposts_meta']} m ON p.ID = m.pid "
                . "WHERE p.post_type = 'wprss_feed_item' "
                . "AND m.pid is NULL "
                . "AND p.post_status not in ('trash')"
                . "ORDER BY p.ID ASC limit %d", $limit);
        $results = $this->cm->db_results($sql);
        return $results;
    }

    private function get_audience_posts($limit = 10) {
        $sql = sprintf("SELECT * "
                . "FROM {$this->db['wp_posts']} p "
                . "LEFT JOIN {$this->db['cm_wpposts_meta']} m ON p.ID = m.pid "
                . "WHERE p.post_type = 'wpcr3_review' "
                . "AND m.pid is NULL "
                . "AND p.post_status not in ('trash')"
                . "ORDER BY p.ID ASC limit %d", $limit);
        $results = $this->cm->db_results($sql);
        return $results;
    }

    public function crop_text($text = '', $length = 10, $tchk = true) {
        if (strlen($text) > $length) {
            $pos = strpos($text, ' ', $length);
            if ($pos != null)
                $text = substr($text, 0, $pos);
            if ($tchk) {
                $text = $text . '...';
            }
        }
        return $text;
    }

    /* Actor transit */

    public function actor_gener_auto($count = 10, $debug = false, $force = false) {
        $sql = sprintf("SELECT a.id, a.name FROM {$this->db['actors_imdb']} a LEFT JOIN {$this->db['actors_gender_auto']} g ON g.actor_id = a.id"
                . " WHERE g.id is null AND a.id>0 ORDER BY a.id ASC limit %d", (int) $count);
        $dbresults = $this->db_results($sql);

        if ($debug) {
            print_r($dbresults);
        }

        $ma = $this->get_ma();
        $names = array();
        if (sizeof($dbresults)) {
            foreach ($dbresults as $item) {
                if ($item->name) {
                    $first_name_arr = explode(' ', $item->name);
                    $first_name = isset($first_name_arr[0]) ? $first_name_arr[0] : $first_name;
                    $first_name_clear = trim($ma->create_slug($first_name, '-'));
                    $first_name_clear = preg_replace('/[^a-z]+/', '', $first_name_clear);
                    if ($first_name_clear) {
                        $names[$item->id] = $first_name_clear;
                    }
                }
            }
        }
        if ($names) {
            if ($debug) {
                print_r($names);
            }
            //Get unique names
            $unames = array();
            foreach ($names as $name) {
                $unames[$name] = $name;
            }

            //http://rightwingtomatoes.com:8008/?names=ilya,katz&p=ds1bfgFe_23_KJDS-F
            $qnames = implode(',', $unames);

            $p = 'ds1bfgFe_23_KJDS-F';
            //$domain = 'http://rightwingtomatoes.com:8008/';
            $domain = 'http://info.antiwoketomatoes.com:8008/';
            $url = $domain . '?p=' . $p . '&names=' . $qnames;
            if ($debug) {
                print_r($url . "\n");
            }

            $responce = file_get_contents($url);

            $data = array();
            if ($responce) {
                $result = json_decode($responce);
                if ($debug) {
                    print_r($result);
                }
                if ($result) {
                    foreach ($result->names as $key => $name) {
                        $data[$name] = array(
                            'g' => $result->gender[$key],
                            'k' => $result->k[$key]
                        );
                    }
                } else {
                    if ($debug) {
                        print_r('Empty responce');
                    }
                    exit();
                }
            } else {
                if ($debug) {
                    print_r('Error responce');
                }
                exit();
            }

            if ($debug) {
                print_r($data);
            }
        }

        foreach ($dbresults as $item) {
            $aid = $item->id;

            // Update item
            $gender = 0;
            $k = 0;
            if (isset($names[$aid])) {
                $name = $names[$aid];
                if (isset($data[$name])) {
                    $data_gender = $data[$name];
                    $gender = $data_gender['g'] == 'm' ? 1 : 2;
                    $k = (int) ((float) $data_gender['k'] * 100);
                    if ($debug) {
                        print "add $aid, $name, $gender, $k\n";
                    }
                }
            }

            $sql = sprintf("INSERT INTO {$this->db['actors_gender_auto']} (actor_id,gender,k) VALUES (%d,%d,%d)", $aid, (int) $gender, (int) $k);
            $this->db_query($sql);
        }
    }

    public function actor_transit_first_name($count = 10, $debug = false, $force = false) {
        $option_name = 'name_unique_id';
        $last_id = get_option($option_name, 0);

        $sql = sprintf("SELECT id, primaryName, gender FROM {$this->db['actors']} WHERE id>%d AND gender is not null ORDER BY id ASC limit %d", (int) $last_id, (int) $count);
        $results = $this->db_results($sql);

        $last = end($results);


        if ($debug) {
            print_r($results);
            print 'last_id: ' . $last->id . "\n";
        }


        update_option($option_name, $last->id);

        $ma = $this->get_ma();
        if (sizeof($results)) {
            //Update names
            $add = 0;
            foreach ($results as $item) {
                $first_name_arr = explode(' ', $item->primaryName);
                $first_name = isset($first_name_arr[0]) ? $first_name_arr[0] : $first_name;
                $first_name_clear = trim($ma->create_slug($first_name, '-'));
                if (strstr($first_name_clear, '-')) {
                    $first_name_clear = '';
                }
                if ($first_name_clear) {
                    if (preg_match('/[0-9]+/', $first_name_clear)) {
                        $first_name_clear = '';
                    }
                }
                if ($first_name_clear) {
                    $gender = 0;
                    if ($item->gender == 'm') {
                        $gender = 1;
                    } else if ($item->gender == 'f') {
                        $gender = 2;
                    }
                    if ($gender) {
                        $ret = $this->create_unique_author_name($first_name_clear, $gender, $debug);
                        if ($ret) {
                            $add += 1;
                        }
                    }
                }
            }
            if ($debug) {
                print "Added rows: $add\n";
            }
        }
    }

    public function create_unique_author_name($name = '', $gender = 0, $debug = false) {

        $id_exist = $this->get_actor_by_name($name);
        $add = 'Exist';
        $ret = false;
        if (!$id_exist) {
            $sql = sprintf("INSERT INTO {$this->db['actor_name']} (name,gender) VALUES ('%s',%d)", $name, (int) $gender);
            $this->db_query($sql);
            $add = 'Add';
            $ret = true;
        }

        if ($debug) {
            print "$add: $name, $gender\n";
        }
        return $ret;
    }

    public function get_actor_by_name($name, $cache = true) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$name])) {
                return $dict[$name];
            }
        }
        //Get author id
        $sql = sprintf("SELECT id FROM {$this->db['actor_name']} WHERE name='%s'", $name);
        $author = $this->db_get_var($sql);

        if ($cache && $author) {
            $dict[$name] = $author;
        }
        return $author;
    }

    public function export_csv() {
        $sql = "SELECT name, gender FROM {$this->db['actor_name']}";
        $results = $this->db_results($sql);
        print sizeof($results);
        $path = ABSPATH . 'wp-content/uploads/actor_gender.csv';
        $content = '';
        foreach ($results as $item) {
            $content .= "$item->name,$item->gender\n";
        }
        file_put_contents($path, $content);
        print ' done';
    }

}
