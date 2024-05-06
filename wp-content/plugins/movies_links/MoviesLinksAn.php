<?php

class MoviesLinksAn extends MoviesAbstractDBAn {

    public function __construct() {
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            'movie_imdb' => 'data_movie_imdb',
            'actors_all' => 'data_actors_all',
            'movies_meta' => 'search_movies_meta',
            'options' => 'options',
            'data_genre' => 'data_movie_genre',
            'meta_genre' => 'meta_movie_genre',
            'data_platform' => 'data_game_platform',
            'meta_platform' => 'meta_game_platform',
            'data_country' => 'data_movie_country',
            'meta_country' => 'meta_movie_country',
            'data_provider' => 'data_movie_provider',
            'meta_actor' => 'meta_movie_actor',
            'meta_director' => 'meta_movie_director',
            'movie_rating' => 'data_movie_rating',
            'actors_normalize' => 'data_actors_normalize',
            'actors_imdb' => 'data_actors_imdb',
            'lastnames' => 'data_lastnames',
            'fs_country' => 'data_familysearch_country',
            'meta_fs' => 'meta_familysearch',
            'pg_rating' => 'data_pg_rating',
            'erating' => 'data_movie_erating',
            'fchan_posts' => 'data_fchan_posts',
            'reviews_rating' => 'meta_reviews_rating',
            'critic_matic_meta' => $table_prefix . 'critic_matic_posts_meta',
            'distributors' => 'data_movie_distributors',
            'distributors_meta' => 'meta_movie_distributors',
            'franchises' => 'data_movie_franchises',
            'indie' => 'data_movie_indie',
            'woke' => 'data_woke',
            'tmdb' => 'data_movie_tmdb',
            'language_code' => 'data_language_code',
            'meta_movie_boxint' => 'meta_movie_boxint',
            'meta_actor_weight'=>'meta_actor_weight',
        );
    }

    public function get_posts($type = '', $get_keys = array(), $limit = 1, $last_id = 0) {

        $type_and = '';
        if ($type) {
            if (strstr($type, ',')) {
                $type_and = " AND type IN ('" . implode("','", explode(',', $type)) . "')";
            } else {
                $type_and = sprintf(" AND type='%s'", $type);
            }
        }

        $select = '*';
        if ($get_keys) {
            $select = implode(', ', $this->validate_keys($get_keys));
        }
        $and_limit = '';
        if ($limit > 0) {
            $and_limit = ' LIMIT ' . (int) $limit;
        }

        //Last id
        $and_last_id = ' id>0';
        if ($last_id > 0) {
            $and_last_id = sprintf(" id > %d", (int) $last_id);
        }

        $sql = "SELECT " . $select . " FROM {$this->db['movie_imdb']} WHERE " . $and_last_id . $type_and . " ORDER BY id ASC" . $and_limit;
        $result = $this->db_results($sql);
        return $result;
    }

    public function get_post_ids_by_weight($min_weight = 0, $max_weight = 0) {
        $ret = array();
        $sql = sprintf("SELECT id, weight FROM {$this->db['movie_imdb']} WHERE weight>=%d AND weight<%d", $min_weight, $max_weight);
        $result = $this->db_results($sql);
        if ($result) {
            foreach ($result as $value) {
                $ret[$value->id] = $value->weight;
            }
        }
        return $ret;
    }

    public function get_post_ids_by_min_weight($min_weight = 0) {
        $ret = array();
        $sql = sprintf("SELECT id FROM {$this->db['movie_imdb']} WHERE weight>%d ORDER BY weight DESC", $min_weight);
        $result = $this->db_results($sql);
        if ($result) {
            foreach ($result as $value) {
                $ret[] = $value->id;
            }
        }
        return $ret;
    }
    
        public function get_actors_ids_by_min_weight($min_weight = 0) {
        $ret = array();
        $sql = sprintf("SELECT aid FROM {$this->db['meta_actor_weight']} WHERE total_weight>%d ORDER BY total_weight DESC", $min_weight);
        $result = $this->db_results($sql);
        if ($result) {
            foreach ($result as $value) {
                $ret[] = $value->aid;
            }
        }
        return $ret;
    }

    private function validate_keys($get_keys) {
        $valid_keys = array();
        if ($get_keys && sizeof($get_keys)) {
            foreach ($get_keys as $key) {
                if ($key == 'imdb') {
                    $key = 'movie_id';
                }
                $valid_keys[] = $key;
            }
        }
        if (!in_array('id', $valid_keys)) {
            $valid_keys[] = 'id';
        }
        return $valid_keys;
    }

    public function get_movie_by_id($mid) {
        $sql = sprintf("SELECT * FROM {$this->db['movie_imdb']} WHERE id=%d", $mid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_movie_id_by_id($mid) {
        $sql = sprintf("SELECT id FROM {$this->db['movie_imdb']} WHERE id=%d", $mid);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function update_movie_rating($mid = 0, $fields = array()) {
        $update = array();
        if ($fields) {
            foreach ($fields as $key => $value) {
                $update[] = "$key='$value'";
            }
        }
        if ($update) {
            $sql = "UPDATE {$this->db['movie_rating']} SET " . implode(', ', $update) . " WHERE movie_id = " . $mid;
            $this->db_query($sql);

            ////commit
            !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
            Import::create_commit('', 'update', 'data_movie_rating', array('movie_id' => $mid), 'movie_rating', 11, ['skip' => ['id']]);
        }
    }

    /* Actors */

    public function get_actor_by_id($id = 0) {
        $sql = sprintf("SELECT a.id, a.aid, a.firstname, a.lastname, CONCAT('nm', LPAD(a.aid, 7, '0')) AS imdb "
                . "FROM {$this->db['actors_normalize']} a WHERE a.aid=%d", (int) $id);
        $results = $this->db_fetch_row($sql);
        return $results;
    }
    
    public function get_actors_by_weight($count = 100, $last_id = 0) {
        $sql = sprintf("SELECT m.id, m.aid, a.firstname, a.lastname, CONCAT('nm', LPAD(m.aid, 7, '0')) AS imdb "
                . "FROM {$this->db['meta_actor_weight']} m LEFT JOIN {$this->db['actors_normalize']} a ON m.aid=a.aid "
                . "WHERE m.id > %d AND m.total_weight>0 ORDER BY m.id ASC limit %d", (int) $last_id, (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }
    
    public function get_actors($actor_type = 'a', $count = 100, $last_id = 0) {
        /*
          $actor_type
          'a' => 'All',
          's' => 'Stars',
          'm' => 'Main',
          'e' => 'Extra',
          'd' => 'Directors',
         */
        $actor_inner = '';
        $actor_and = '';
        if ($actor_type == 's' || $actor_type == 'm' || $actor_type == 'e') {
            // Default stars
            $actor_type_int = 1;
            if ($actor_type == 'm') {
                // Main
                $actor_type_int = 2;
            } else if ($actor_type == 'e') {
                // Extra
                $actor_type_int = 3;
            }
            $actor_inner = " INNER JOIN {$this->db['meta_actor']} m ON m.aid = a.aid";
            $actor_and = ' AND m.type=' . $actor_type_int;
        } else if ($actor_type == 'd') {
            // Directors
            $actor_inner = " INNER JOIN {$this->db['meta_director']} m ON m.aid = a.aid";
        }

        //Last id
        $and_last_id = ' a.id>0';
        if ($last_id > 0) {
            $and_last_id = sprintf(" a.id > %d", (int) $last_id);
        }

        $sql = sprintf("SELECT a.id, a.aid, a.firstname, a.lastname, CONCAT('nm', LPAD(a.aid, 7, '0')) AS imdb FROM {$this->db['actors_normalize']} a" . $actor_inner . " WHERE " . $and_last_id . $actor_and . " ORDER BY a.id ASC limit %d", (int) $count);

        $results = $this->db_results($sql);
        return $results;
    }

    public function get_actors_normalize_by_name($firstname = '', $lastname = '') {
        $results = array();

        if ($firstname || $lastname) {

            $and_first = '';
            if ($firstname) {
                $and_first = sprintf(" AND a.firstname='%s'", $this->escape($firstname));
            }

            $and_last = '';
            if ($lastname) {
                $and_first = sprintf(" AND a.lastname='%s'", $this->escape($lastname));
            }

            $sql = sprintf("SELECT a.id, a.aid, a.firstname, a.lastname FROM {$this->db['actors_normalize']} a WHERE a.id>0" . $and_first . $and_last . " ORDER BY a.id ASC");
            $results = $this->db_results($sql);
        }
        return $results;
    }

    public function get_actors_by_name($name = '') {
        $sql = sprintf("SELECT id, id as aid, name, birth_name, burn_date FROM {$this->db['actors_imdb']} WHERE name='%s' ORDER BY id ASC", $this->escape($name));
        $results = $this->db_results($sql);
        return $results;
    }

    public function update_pg_rating($data = array(), $movie_id = 0) {
        $pg_rating_id = $this->get_or_create_pg_rating($movie_id);
        $this->sync_update_data($data, $pg_rating_id, $this->db['pg_rating'], true);
    }

    public function get_or_create_pg_rating($movie_id) {
        $sql = sprintf("SELECT movie_id FROM {$this->db['movie_imdb']} WHERE id=%d", $movie_id);
        $pg_id = $this->db_get_var($sql);

        $sql = sprintf("SELECT id FROM {$this->db['pg_rating']} WHERE movie_id=%d", $pg_id);
        $id = $this->db_get_var($sql);
        if ($id) {
            return $id;
        }
        $data = array(
            'movie_id' => $pg_id
        );
        $id = $this->sync_insert_data($data, $this->db['pg_rating'], false, false);

        return $id;
    }

    /*
     * ERating
     */

    public function update_erating($mid = 0, $data = array()) {
        // Get rating      
        $sql = sprintf("SELECT * FROM {$this->db['erating']} WHERE movie_id = %d", (int) $mid);
        $exist = $this->db_fetch_row($sql);
        $data['last_upd'] = $this->curr_time();
        if ($exist) {


            $count_names = array(
                'kinop_count',
                'douban_count',
                'animelist_count',
                'imdb_count',
                'rt_count',
                'rt_aucount',
                'fchan_posts_found',
                'reviews_posts',
                'eiga_count',
                'moviemeter_count',
                'ofdb_count',
                'opencritic_count',
            );
            $total_count = 0;
            foreach ($count_names as $rn) {
                if (isset($data[$rn])) {
                    $total_count += $data[$rn];
                } else {
                    $total_count += $exist->$rn;
                }
            }

            // $data['total_rating'] = $total;
            $data['total_count'] = $total_count;

            if ($exist->date == 0) {
                // Empty date. Bug from 07.09.2023
                $data['date'] = $data['last_upd'];

                $m_data = $this->get_movie_by_id($mid);
                $title = $m_data->title;
                $data['title'] = $title;
            }

            // Update post            
            $this->sync_update_data($data, $exist->id, $this->db['erating'], true, 10);
            CustomHooks::do_action('add_erating', ['mid' => $mid, 'data' => $data]);
        } else {
            // Add post            
            $data['movie_id'] = $mid;
            $data['date'] = $data['last_upd'];

            $m_data = $this->get_movie_by_id($mid);
            $title = $m_data->title;
            $data['title'] = $title;

            $this->sync_insert_data($data, $this->db['erating'], false, true, 10);
            CustomHooks::do_action('add_erating', ['mid' => $mid, 'data' => $data]);
        }
        !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
        PgRatingCalculate::add_movie_rating($mid, '', '', 0, 0, 0);
    }

    public function five_to_ten($camp_rating = 0) {

        /* Update 10-50 rating to 0-100
         * 
         * 1*10 = 10
         * 5*10 = 50
         * Example ratings                     
         * 10-10 = 1 * 2,5 = 0
         * 20-10 = 10 * 2,5 = 25
         * 30-10 = 20 * 2,5 = 50
         * 40-10 = 30 * 2,5 = 75
         * 50-10 = 40 * 2,5 = 100
         */
        $camp_rating = ($camp_rating - 10) * 2.5;
        if ($camp_rating < 0) {
            $camp_rating = 0;
        }

        return $camp_rating;
    }

    public function calculate_total($post) {
        /*
         * kinop_result
         * douban_result
         * fchan_result
         * reviews_result
         * total_rating
         * 'kinop_rating', 'douban_rating', 'animelist_rating', 'imdb_rating', 'rt_rating', 'rt_aurating'
         * 'eiga_rating',
          'moviemeter_rating',
          'metacritic_rating',
          'metacritic_userscore',
         */
        $total = 0;
        $i = 0;
        if ($post->kinop_rating) {
            $total += $post->kinop_rating;
            $i += 1;
        }
        if ($post->douban_rating) {
            $total += $post->douban_rating;
            $i += 1;
        }
        if ($post->animelist_rating) {
            $total += $post->animelist_rating;
            $i += 1;
        }
        if ($post->imdb_rating) {
            $total += $post->imdb_rating;
            $i += 1;
        }
        if ($post->rt_rating) {
            $total += $post->rt_rating;
            $i += 1;
        }
        if ($post->rt_aurating) {
            $total += $post->rt_aurating;
            $i += 1;
        }
        if ($post->eiga_rating) {
            $total += $this->five_to_ten($post->eiga_rating);
            $i += 1;
        }
        if ($post->moviemeter_rating) {
            $total += $this->five_to_ten($post->moviemeter_rating);
            $i += 1;
        }
        if ($post->metacritic_rating) {
            $total += $post->metacritic_rating;
            $i += 1;
        }
        if ($post->metacritic_userscore) {
            $total += $post->metacritic_userscore;
            $i += 1;
        }
        $total_result = (int) round($total / $i, 0);

        return $total_result;
    }

    public function get_rating_movies($last_id = 0, $count = 0) {
        // Get last movie ids
        $sql = sprintf("SELECT r.id, m.fid FROM {$this->db['reviews_rating']} r"
                . " INNER JOIN {$this->db['critic_matic_meta']} m ON m.cid=r.cid WHERE r.id>%d ORDER BY r.id ASC LIMIT %d", $last_id, $count);
        return $this->db_results($sql);
    }

    public function get_review_rating_posts($mid) {
        // Get rating from movies id
        $sql = sprintf("SELECT r.percent as rating FROM {$this->db['reviews_rating']} r"
                . " INNER JOIN {$this->db['critic_matic_meta']} m ON m.cid=r.cid WHERE m.fid=%d", $mid);
        return $this->db_results($sql);
    }

    /*
     * Genre
     */

    public function get_genres_names() {
        $sql = "SELECT id, name, slug, status, weight FROM {$this->db['data_genre']}";
        $result = $this->db_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $value) {
                $ret[$value->name] = $value;
            }
        }
        return $ret;
    }

    public function add_genre_meta($mid = 0, $slug = '') {
        $genre = $this->get_genre_by_slug($slug);
        if ($genre->id) {
            $this->add_movie_genre($mid, $genre->id);
        }
    }

    public function get_genre($id) {
        $sql = sprintf("SELECT name, slug, status, weight FROM {$this->db['data_genre']} WHERE id=%d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_genre_by_slug($slug, $cache = false) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$slug])) {
                return $dict[$slug];
            }
        }

        //Get author id
        $sql = sprintf("SELECT id, name FROM {$this->db['data_genre']} WHERE slug='%s'", $this->escape($slug));
        $result = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$slug] = $result;
        }
        return $result;
    }

    public function add_movie_genre($mid = 0, $gid = 0) {
        // Validate values
        if ($mid > 0 && $gid > 0) {
            //Get meta
            $sql = sprintf("SELECT gid FROM {$this->db['meta_genre']} WHERE mid=%d AND gid=%d", (int) $mid, (int) $gid);
            $meta_exist = $this->db_get_var($sql);
            if (!$meta_exist) {
                //Meta not exist
                $data = array(
                    'mid' => $mid,
                    'gid' => $gid,
                );

                $this->sync_insert_data($data, $this->db['meta_genre'], false, true, 10);
            }
            return true;
        }
        return false;
    }

    public function get_genre_by_name($name, $cache = false) {
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
        $sql = sprintf("SELECT id FROM {$this->db['data_genre']} WHERE name='%s'", $this->escape($name));
        $result = $this->db_get_var($sql);

        if ($cache && $result) {
            $dict[$name] = $result;
        }
        return $result;
    }

    public function get_or_create_genre_by_name($name = '', $cache = false) {
        $id = $this->get_genre_by_name($name, $cache);
        if (!$id) {
            // Create slug
            $slug = $this->create_slug($name);
            // Create the genre
            $data = array(
                'name' => $name,
                'slug' => $slug,
            );
            $id = $this->sync_insert_data($data, $this->db['data_genre'], false, true);
        }
        return $id;
    }

    public function add_game_platform($mid = 0, $gid = 0) {
        // Validate values
        if ($mid > 0 && $gid > 0) {
            //Get meta
            $sql = sprintf("SELECT gid FROM {$this->db['meta_platform']} WHERE mid=%d AND gid=%d", (int) $mid, (int) $gid);
            $meta_exist = $this->db_get_var($sql);
            if (!$meta_exist) {
                //Meta not exist
                $data = array(
                    'mid' => $mid,
                    'gid' => $gid,
                );

                $this->sync_insert_data($data, $this->db['meta_platform'], false, true, 10);
            }
            return true;
        }
        return false;
    }

    /*
     * Platform
     */

    public function get_platform_by_name($name, $cache = false) {
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
        $sql = sprintf("SELECT id FROM {$this->db['data_platform']} WHERE name='%s'", $this->escape($name));
        $result = $this->db_get_var($sql);

        if ($cache && $result) {
            $dict[$name] = $result;
        }
        return $result;
    }

    public function get_or_create_platform_by_name($name = '', $cache = false) {
        $id = $this->get_platform_by_name($name, $cache);
        if (!$id) {
            // Create slug
            $slug = $this->create_slug($name);
            // Create the platform
            $data = array(
                'name' => $name,
                'slug' => $slug,
            );
            $id = $this->sync_insert_data($data, $this->db['data_platform'], false, true);
        }
        return $id;
    }

    /*
     * Distributors
     */

    public function get_distributor_by_name($slug, $cache = false) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$slug])) {
                return $dict[$slug];
            }
        }

        //Get author id
        $sql = sprintf("SELECT * FROM {$this->db['distributors']} WHERE name='%s'", $this->escape($slug));
        $result = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$slug] = $result;
        }
        return $result;
    }

    public function add_movie_distributor($name = '', $link = '') {
        /*
          `date` int(11) NOT NULL DEFAULT '0',
          `name` varchar(255) NOT NULL default '',
          `link` varchar(255) NOT NULL default '',
         */
        $id = 0;
        if ($name) {
            $name = $this->validate_varchar($name);
            $item_exist = $this->get_distributor_by_name($name);
            if (!$item_exist) {
                //Meta not exist
                $data = array(
                    'date' => $this->curr_time(),
                    'name' => $name,
                    'link' => $this->validate_varchar($link),
                );

                $id = $this->sync_insert_data($data, $this->db['distributors'], false, true, 10);
            } else {
                $id = $item_exist->id;
            }
        }
        return $id;
    }

    public function add_distributor_meta($mid, $did, $type = 0) {
        // Meta exist
        $sql = sprintf("SELECT id FROM {$this->db['distributors_meta']} WHERE mid=%d AND did=%d AND type=%d", $mid, $did, $type);
        $exist_id = $this->db_get_var($sql);
        if (!$exist_id) {
            $data = array(
                'mid' => $mid,
                'did' => $did,
                'type' => $type,
            );

            $exist_id = $this->sync_insert_data($data, $this->db['distributors_meta'], false, true, 10);
        }
        return $exist_id;
    }

    /*
     * Franchise
     */

    public function get_franchise_by_name($slug, $cache = false) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$slug])) {
                return $dict[$slug];
            }
        }

        //Get author id
        $sql = sprintf("SELECT * FROM {$this->db['franchises']} WHERE name='%s'", $this->escape($slug));
        $result = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$slug] = $result;
        }
        return $result;
    }

    public function add_movie_franchise($name = '', $link = '') {
        /*
          `date` int(11) NOT NULL DEFAULT '0',
          `name` varchar(255) NOT NULL default '',
          `link` varchar(255) NOT NULL default '',
         */
        $id = 0;
        if ($name) {
            $name = $this->validate_varchar($name);
            $item_exist = $this->get_franchise_by_name($name);
            if (!$item_exist) {
                //Meta not exist
                $data = array(
                    'date' => $this->curr_time(),
                    'name' => $name,
                    'link' => $this->validate_varchar($link),
                );

                $id = $this->sync_insert_data($data, $this->db['franchises'], false, true, 10);
            } else {
                $id = $item_exist->id;
            }
        }
        return $id;
    }

    /*
     * Indie
     */

    public function update_indie($mid = 0, $data = array()) {
        // Get rating      
        /*
          `movie_id` int(11) NOT NULL DEFAULT '0',
          `date` int(11) NOT NULL DEFAULT '0',
          `distributor` int(11) NOT NULL DEFAULT '0',
          `franchise` int(11) NOT NULL DEFAULT '0',
         */
        $sql = sprintf("SELECT * FROM {$this->db['indie']} WHERE movie_id = %d", (int) $mid);
        $exist = $this->db_fetch_row($sql);
        $data['date'] = $this->curr_time();
        if ($exist) {
            // Update post            
            $this->sync_update_data($data, $exist->id, $this->db['indie'], true, 10);
            CustomHooks::do_action('update_indie', ['mid' => $mid, 'data' => $data]);
        } else {
            // Add post            
            $data['movie_id'] = $mid;
            $this->sync_insert_data($data, $this->db['indie'], false, true, 10);
            CustomHooks::do_action('add_indie', ['mid' => $mid, 'data' => $data]);
        }
    }

    /*
     * Woke
     */

    public function update_woke($mid = 0, $data = array()) {
        // Get rating      

        $sql = sprintf("SELECT * FROM {$this->db['woke']} WHERE mid = %d", (int) $mid);
        $exist = $this->db_fetch_row($sql);
        $data['last_update'] = $this->curr_time();
        if ($exist) {
            // Update post            
            $this->sync_update_data($data, $exist->id, $this->db['woke'], true, 10);
            CustomHooks::do_action('update_woke', ['mid' => $mid, 'data' => $data]);
        } else {
            // Add post            
            $data['mid'] = $mid;
            $this->sync_insert_data($data, $this->db['woke'], false, true, 10);
            CustomHooks::do_action('add_woke', ['mid' => $mid, 'data' => $data]);
        }
    }

    /*
     * Woke
     */

    public function update_tmdb($mid = 0, $data = array()) {
        // Get data 
        $sql = sprintf("SELECT * FROM {$this->db['tmdb']} WHERE mid = %d", (int) $mid);
        $exist = $this->db_fetch_row($sql);
        $data['last_update'] = $this->curr_time();
        if ($exist) {
            // Update post            
            $this->sync_update_data($data, $exist->id, $this->db['tmdb'], true, 10);
            CustomHooks::do_action('update_tmdb', ['mid' => $mid, 'data' => $data]);
        } else {
            // Add post            
            $data['mid'] = $mid;
            $this->sync_insert_data($data, $this->db['tmdb'], false, true, 10);
            CustomHooks::do_action('add_tmdb', ['mid' => $mid, 'data' => $data]);
        }
    }

    public function get_or_create_language_by_name($name = '') {
        $sql = sprintf("SELECT * FROM {$this->db['language_code']} WHERE code = '%s'", $name);
        $exist = $this->db_fetch_row($sql);
        if ($exist) {
            $id = $exist->id;
        } else {
            // Create the platform
            $data = array(
                'code' => $name,
            );
            $id = $this->sync_insert_data($data, $this->db['language_code'], false, true);
        }
        return $id;
    }

    /*
     * Country
     */

    public function get_or_create_country_by_name($name = '', $cache = false) {
        $id = $this->get_country_by_name($name, $cache);
        if (!$id) {
            // Create slug
            $slug = $this->create_slug($name);
            // Create the country
            $data = array(
                'name' => $name,
                'slug' => $slug,
            );
            $id = $this->sync_insert_data($data, $this->db['data_country'], false, true);
        }
        return $id;
    }

    public function get_country_by_name($name, $cache = false) {
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
        $sql = sprintf("SELECT id FROM {$this->db['data_country']} WHERE name='%s'", $this->escape($name));
        $result = $this->db_get_var($sql);

        if ($cache && $result) {
            $dict[$name] = $result;
        }
        return $result;
    }

    /*
     * Meta box int
     */

    public function get_meta_box_int($cid, $mid) {
        $sql = sprintf("SELECT * FROM {$this->db['meta_movie_boxint']} WHERE mid=%d AND country=%d", (int) $mid, (int) $cid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function add_meta_box_int($cid, $mid, $total = 0, $debug = false) {
        $id_ob = $this->get_meta_box_int($cid, $mid);
        if (!$id_ob->id) {
            $data = array(
                'mid' => (int) $mid,
                'country' => (int) $cid,
                'total' => (int) $total,
            );
            $id = $this->sync_insert_data($data, $this->db['meta_movie_boxint'], false, true);
        } else {
            $id = $id_ob->id;
            if ($total != $id_ob->total) {
                $data = array(
                    'total' => (int) $total,
                );
                $this->sync_update_data($data, $id, $this->db['meta_movie_boxint'], true);
            }
        }
        return $id;
    }

    public function add_meta_box_int_mojo($cid, $mid, $total_mojo = 0, $debug = false) {
        $id_ob = $this->get_meta_box_int($cid, $mid);
        $data = array();
        $result = 'insert';
        if (!$id_ob->id) {
            $data = array(
                'mid' => (int) $mid,
                'country' => (int) $cid,
                'total_mojo' => (int) $total_mojo,
            );
            $id = $this->sync_insert_data($data, $this->db['meta_movie_boxint'], false, true);
        } else {
            $id = $id_ob->id;
            $result = 'not change';
            if ($total_mojo != $id_ob->total_mojo) {
                $result = 'update';
                $data = array(
                    'total_mojo' => (int) $total_mojo,
                );
                $this->sync_update_data($data, $id, $this->db['meta_movie_boxint'], true);
            }
        }
        if ($debug) {
            print_r(array($result,$id_ob, array($cid, $mid, $total_mojo), $data));
        }
        return $id;
    }

    public function create_slug($string, $glue = '-', $str_lower = true) {
        $string = str_replace('&', ' and ', $string);
        $string = preg_replace("/('|`)/", "", $string);

        $table = array(
            'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
            'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
            'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ü' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b',
            'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r', '/' => '-', ' ' => '-'
        );

        // -- Remove duplicated spaces
        $stripped = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', trim($string));

        // -- Returns the slug
        $slug = strtr($stripped, $table);
        if ($str_lower) {
            $slug = strtolower($slug);
        }
        $slug = preg_replace('~[^\pL\d]+~u', $glue, $slug);

        $slug = preg_replace('/^-/', '', $slug);
        $slug = preg_replace('/-$/', '', $slug);

        return $slug;
    }
}
