<?php

/**
 * Logic for movies in analytics db
 *
 * @author brahman
 */
class MoviesAn extends AbstractDBAn {

    private $db;
    // Critic matic
    private $cm;
    /*
     * Movies
     */
    public $movies_weight_upd_interval = 1;
    public $perpage = 30;
    public $movie_state = array(
        1 => 'Approved',
        2 => 'Auto',
        0 => 'Unapproved'
    );
    public $movie_rating = array(
        0 => 'Zero rating',
        1 => 'Non zero rating',
    );
    public $movie_type = array(
        'Movie' => 'Movie',
        'TVseries' => 'TV',
        'PodcastSeries' => 'Podcast',
        'VideoGame' => 'Game',
    );
    public $movie_slug = array(
        'Movie' => 'movies',
        'TVSeries' => 'tvseries',
        'PodcastSeries' => 'podcastseries',
        'VideoGame' => 'videogame'
    );
    public $movie_tabs = array(
        'home' => 'View'
    );
    public $sort_pages = array('add_time', 'free', 'id', 'date', 'year', 'title', 'name', 'slug', 'status', 'type', 'weight');

    /*
     * Genres
     */
    public $genre_status = array(
        1 => 'Publish',
        0 => 'Draft',
        2 => 'Trash'
    );
    public $genres_tabs = array(
        'home' => 'Genres list',
        'add' => 'Add a new genre',
    );
    public $genre_tabs = array(
        'home' => 'View',
        'edit' => 'Edit',
        'trash' => 'Trash',
    );

    /*
     * Providers
     */
    public $provider_status = array(
        1 => 'Publish',
        0 => 'Draft',
        2 => 'Trash'
    );
    public $provider_free_status = array(
        0 => 'Pay',
        1 => 'Free',
    );
    public $providers_tabs = array(
        'home' => 'Providers list',
        'add' => 'Add a new provider',
    );
    public $provider_tabs = array(
        'home' => 'View',
        'edit' => 'Edit',
        'trash' => 'Trash',
    );

    /*
     * Country
     */
    public $country_status = array(
        1 => 'Publish',
        0 => 'Draft',
        2 => 'Trash'
    );
    public $countries_tabs = array(
        'home' => 'Countries list',
        'add' => 'Add a new country',
    );
    public $country_tabs = array(
        'home' => 'View',
        'edit' => 'Edit',
        'trash' => 'Trash',
    );

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->db = array(
            'movie_imdb' => 'data_movie_imdb',
            'actors_imdb' => 'data_actors_imdb',
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
            'actors' => 'data_actors_all',
            'population' => 'data_population_country',
            'power' => 'data_buying_power',
            'options' => 'options',
            'cpi' => 'data_cpi',
            'title_slugs' => 'data_movie_title_slugs',
            'race_rule' => 'data_an_race_rule',
            'cache_nf_keywords' => 'cache_nf_keywords',
            'erating' => 'data_movie_erating',
            'franchises' => 'data_movie_franchises',
            'distributors' => 'data_movie_distributors',
            'hook_movie_upd' => 'hook_movie_upd',
            'meta_movie_keywords' => 'meta_movie_keywords',
            'meta_keywords' => 'meta_keywords',
            'language_code' => 'data_language_code',
        );
        $this->timer_start();
        $this->get_perpage();
    }

    public function get_cast($id) {
        $sql = sprintf("SELECT actors FROM {$this->db['movie_imdb']} WHERE id=%d", (int) $id);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_post($id) {
        $sql = sprintf("SELECT * FROM {$this->db['movie_imdb']} WHERE id=%d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_post_by_slug($slug = '', $type = 'Movie') {
        $sql = sprintf("SELECT * FROM {$this->db['movie_imdb']} WHERE type='%s' AND post_name='%s' ORDER BY id ASC LIMIT 1", $this->escape($type), $this->escape($slug));
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_post_name($id) {
        $sql = sprintf("SELECT post_name FROM {$this->db['movie_imdb']} WHERE id=%d", (int) $id);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_post_id_by_name($post_name = '', $type = '') {
        $sql = sprintf("SELECT id FROM {$this->db['movie_imdb']} WHERE post_name='%s' AND type='%s'", $this->escape($post_name), $this->escape($type));
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_post_name_by_old_slug($post_name = '') {
        $sql = sprintf("SELECT newslug FROM {$this->db['title_slugs']} WHERE oldslug='%s'", $this->escape($post_name));
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_post_id_by_rwt_id($rwt_id = 0) {
        $sql = sprintf("SELECT id FROM {$this->db['movie_imdb']} WHERE rwt_id=%d", (int) $rwt_id);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_posts_without_post_name($limit = 100) {
        $sql = sprintf("SELECT id, rwt_id, title, type FROM {$this->db['movie_imdb']} WHERE post_name IS NULL OR post_name='' limit %d", (int) $limit);
        $result = $this->db_results($sql);
        return $result;
    }

    public function get_actor($id) {
        $sql = sprintf("SELECT primaryName FROM {$this->db['actors']} WHERE actor_id=%d", (int) $id);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_expired_movies($limit = 10, $expire = 30) {

        $post_and = " AND p.type IN('Movie','TVseries','VideoGame','PodcastSeries')";

        $expire_sec = $expire * 86400;
        $date_expire = time() - $expire_sec;
        $expire_and = sprintf(' AND m.date IS NULL OR m.date < %d', (int) $date_expire);

        // Get movies that meta is old
        $sql = sprintf("SELECT p.* FROM {$this->db['movie_imdb']} p "
                . "LEFT JOIN {$this->db['movies_meta']} m ON p.id = m.mid "
                . "WHERE p.id>0" . $post_and . $expire_and . " ORDER BY p.id DESC LIMIT %d", (int) $limit);

        $movies = $this->db_results($sql);
        return $movies;
    }

    public function update_movies_meta($mid = 0, $date = -1) {
        if ($date == -1) {
            $date = time();
        }
        // Validate values
        if ($mid > 0) {
            //Get post meta
            $sql = sprintf("SELECT id, date FROM {$this->db['movies_meta']} WHERE mid=%d", (int) $mid);
            $meta_exist = $this->db_fetch_row($sql);

            if ($meta_exist) {
                if ($date == 0 && $meta_exist->date == 0) {
                    // continue reset meta
                } else {
                    // Update
                    $data = array(
                        'date' => (int) $date
                    );
                    // $this->cm->sync_update_data($data, $meta_exist, $this->db['movies_meta'], false);
                    $this->db_update($data, $this->db['movies_meta'], $meta_exist->id);
                }
            } else {
                // Insert
                $data = array(
                    'mid' => (int) $mid,
                    'date' => (int) $date
                );

                //$this->cm->sync_insert_data($data, $this->db['movies_meta'], false);
                $this->db_insert($data, $this->db['movies_meta']);
            }
            return true;
        }
        return false;
    }

    public function get_movie_last_update($mid) {
        $sql = sprintf("SELECT date FROM {$this->db['movies_meta']} WHERE mid=%d", (int) $mid);
        $date = $this->db_get_var($sql);
        return $date;
    }

    public function get_movies_last_update() {

        $sql = "SELECT add_time FROM {$this->db['movie_imdb']} WHERE add_time>0 ORDER by add_time DESC limit 1";

        $result = $this->db_get_var($sql);
        return $result;
    }

    public function reset_movie_meta_date($mid) {
        $this->update_movies_meta($mid, 0);
    }

    public function add_post_name($id, $post_name) {
        // Get post name
        $name_exist = $this->get_post_name($id);
        // Add post name
        if (!$name_exist) {
            $data = array(
                'post_name' => $post_name
            );
            $this->cm->sync_update_data($data, $id, $this->db['movie_imdb'], $this->cm->sync_data);
        }
    }

    public function create_post_name($id, $title, $type, $year) {
        $title_decode = htmlspecialchars_decode($title);
        $new_post_name = $this->create_slug($title_decode);
        if (!$new_post_name) {
            $new_post_name = $id;
        }
        // Post name exist?
        $exist = $this->get_post_by_slug($new_post_name, $type);
        if ($exist && $exist->id != $id) {
            $new_post_name = $new_post_name . '-' . $year;
            $exist2 = $this->get_post_by_slug($new_post_name, $type);
            if ($exist2 && $exist2->id != $id) {
                $new_post_name = $new_post_name . '-' . $id;
            }
        }

        //Add postname to db
        $this->add_post_name($id, $new_post_name);

        return $new_post_name;
    }

    /*
     * Genre
     */

    public function get_genre($id) {
        $sql = sprintf("SELECT name, slug, status, weight FROM {$this->db['data_genre']} WHERE id=%d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_genres($status = -1, $page = 1, $orderby = '', $order = 'ASC') {
        $page -= 1;
        $start = $page * $this->perpage;

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE status = " . (int) $status;
        }

        //Sort
        $and_orderby = '';

        if ($orderby && in_array($orderby, $this->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY name ASC";
        }


        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        $sql = "SELECT id, status, name, slug, weight FROM {$this->db['data_genre']} " . $status_query . $and_orderby . $limit;

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_all_genres() {
        $sql = sprintf("SELECT id, name, slug, status, weight FROM {$this->db['data_genre']} WHERE status!=2 ORDER BY name ASC");
        $result = $this->db_results($sql);
        return $result;
    }

    public function get_genres_states() {
        $count = $this->get_genres_count();
        $feed_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->genre_status as $key => $value) {
            $feed_states[$key] = array(
                'title' => $value,
                'count' => $this->get_genres_count($key));
        }
        return $feed_states;
    }

    public function genre_actions($exclude = array()) {
        foreach ($this->genre_tabs as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }
            $feed_actions[$key] = array('title' => $value);
        }
        return $feed_actions;
    }

    public function get_genre_status($status) {
        return isset($this->genre_status[$status]) ? $this->genre_status[$status] : 'None';
    }

    public function get_genres_count($status = -1) {
        // Custom status
        $status_trash = 2;
        $status_query = " WHERE status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE status = " . (int) $status;
        }

        $query = "SELECT COUNT(id) FROM {$this->db['data_genre']} " . $status_query;
        $result = $this->db_get_var($query);
        return $result;
    }

    public function genre_edit_validate($form_state) {

        if (isset($form_state['trash'])) {
            // Trash
        } else {
            // Edit
            if ($form_state['name'] == '') {
                return __('Enter the genre name');
            }

            if ($form_state['slug'] == '') {
                return __('Enter the genre slug');
            }
        }

        $nonce = wp_verify_nonce($_POST['critic-feeds-nonce'], 'critic-feeds-options');
        if (!$nonce) {
            return __('Error validate nonce');
        }

        return true;
    }

    public function genre_edit_submit($form_state) {
        $result_id = 0;
        $status = (int) $form_state['status'];
        $name = $this->escape($form_state['name']);
        $slug = $this->escape($form_state['slug']);
        $weight = (int) ($form_state['weight']);

        $data = array(
            'status' => (int) $status,
            'weight' => (int) $weight,
            'name' => $name,
            'slug' => $slug,
        );

        if ($form_state['id']) {
            $id = (int) $form_state['id'];
            //EDIT           
            $this->cm->sync_update_data($data, $id, $this->db['data_genre'], $this->cm->sync_data);
            $result_id = $id;
        } else {
            //ADD
            $result_id = $this->cm->sync_insert_data($data, $this->db['data_genre'], $this->cm->sync_client, $this->cm->sync_data);
        }

        return $result_id;
    }

    public function trash_genre($form_state) {
        $result = 0;
        $status = isset($form_state['status']) ? $form_state['status'] : 0;

        if ($form_state['id']) {
            // To trash
            $id = $form_state['id'];
            $data = array(
                'status' => (int) $status,
            );
            $this->cm->sync_update_data($data, $id, $this->db['data_genre'], $this->cm->sync_data);
            $result = $id;
        }
        return $result;
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

    public function get_genre_by_id($id, $cache = false) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }

        //Get author id
        $sql = sprintf("SELECT name FROM {$this->db['data_genre']} WHERE id=%d", (int) $id);
        $result = $this->db_get_var($sql);

        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }

    public function get_genres_by_ids($ids = array()) {
        $sql = sprintf("SELECT id, name, slug FROM {$this->db['data_genre']} WHERE id IN(%s)", implode(',', $ids));
        $result = $this->db_results($sql);
        $ret = array();
        if (sizeof($result)) {
            foreach ($result as $item) {
                $ret[$item->id] = $item;
            }
        }
        return $ret;
    }

    public function get_movie_genres($mid) {
        $sql = sprintf("SELECT g.id, g.name FROM {$this->db['data_genre']} g"
                . " INNER JOIN {$this->db['meta_genre']} m ON m.gid = g.id"
                . " WHERE m.mid=%d", $mid);
        $result = $this->db_results($sql);
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
            $id = $this->cm->sync_insert_data($data, $this->db['data_genre'], $this->cm->sync_client, $this->cm->sync_data);
        }
        return $id;
    }

    public function update_genre_slug($id, $slug) {
        $genre = $this->get_genre_by_id($id);
        if ($genre) {
            $data = array(
                'slug' => (int) $slug,
            );
            $this->cm->sync_update_data($data, $id, $this->db['data_genre'], $this->cm->sync_data);
        }
    }

    public function get_movies_no_genre_meta($count) {
        $sql = sprintf("SELECT p.id, p.genre FROM {$this->db['movie_imdb']} p "
                . "LEFT JOIN {$this->db['meta_genre']} m ON p.id = m.mid "
                . "WHERE p.genre !='' AND m.id is NULL limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
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
                $id = $this->cm->sync_insert_data($data, $this->db['meta_genre'], $this->cm->sync_client, $this->cm->sync_data);
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public function remove_movie_genre($mid, $gid) {
        $sql = sprintf("DELETE FROM {$this->db['meta_genre']} WHERE mid=%d AND gid=%d", (int) $mid, (int) $gid);
        $this->db_query($sql);
    }

    public function bulk_remove_movie_genres($mid, $gids) {

        foreach ($gids as $gid) {
            $data = array(
                'mid' => $mid,
                'gid' => $gid,
            );
            $this->sync_delete_multi($data, $this->db['meta_genre'], true, 10);
        }
    }

    /*
     * Platform
     */

    public function get_platform_by_slug($slug, $cache = false) {
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
        $sql = sprintf("SELECT id, name FROM {$this->db['data_platform']} WHERE slug='%s'", $this->escape($slug));
        $result = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$slug] = $result;
        }
        return $result;
    }

    public function get_platforms_by_ids($ids = array()) {
        $sql = sprintf("SELECT id, name, slug FROM {$this->db['data_platform']} WHERE id IN(%s)", implode(',', $ids));
        $result = $this->db_results($sql);
        $ret = array();
        if (sizeof($result)) {
            foreach ($result as $item) {
                $ret[$item->id] = $item;
            }
        }
        return $ret;
    }

    /*
     * Country
     */

    public function get_country($id) {
        $sql = sprintf("SELECT name, slug, status, weight FROM {$this->db['data_country']} WHERE id=%d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_countries($status = -1, $page = 1, $orderby = '', $order = 'ASC') {
        $page -= 1;
        $start = $page * $this->perpage;

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE status = " . (int) $status;
        }

        //Sort
        $and_orderby = '';

        if ($orderby && in_array($orderby, $this->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY name ASC";
        }


        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        $sql = "SELECT id, status, name, slug, weight FROM {$this->db['data_country']} " . $status_query . $and_orderby . $limit;

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_countries_states() {
        $count = $this->get_countries_count();
        $feed_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->country_status as $key => $value) {
            $feed_states[$key] = array(
                'title' => $value,
                'count' => $this->get_countries_count($key));
        }
        return $feed_states;
    }

    public function country_actions($exclude = array()) {
        foreach ($this->country_tabs as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }
            $feed_actions[$key] = array('title' => $value);
        }
        return $feed_actions;
    }

    public function get_country_status($status) {
        return isset($this->country_status[$status]) ? $this->country_status[$status] : 'None';
    }

    public function get_countries_count($status = -1) {
        // Custom status
        $status_trash = 2;
        $status_query = " WHERE status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE status = " . (int) $status;
        }

        $query = "SELECT COUNT(id) FROM {$this->db['data_country']} " . $status_query;
        $result = $this->db_get_var($query);
        return $result;
    }

    public function country_edit_validate($form_state) {

        if (isset($form_state['trash'])) {
            // Trash
        } else {
            // Edit
            if ($form_state['name'] == '') {
                return __('Enter the country name');
            }

            if ($form_state['slug'] == '') {
                return __('Enter the country slug');
            }
        }

        $nonce = wp_verify_nonce($_POST['critic-feeds-nonce'], 'critic-feeds-options');
        if (!$nonce) {
            return __('Error validate nonce');
        }

        return true;
    }

    public function country_edit_submit($form_state) {
        $result_id = 0;
        $status = (int) $form_state['status'];
        $name = $this->escape($form_state['name']);
        $slug = $this->escape($form_state['slug']);
        $weight = (int) ($form_state['weight']);
        $data = array(
            'status' => $status,
            'weight' => $weight,
            'name' => $name,
            'slug' => $slug,
        );
        if ($form_state['id']) {
            $id = (int) $form_state['id'];
            //EDIT
            $this->cm->sync_update_data($data, $id, $this->db['data_country'], $this->cm->sync_data);
            $result_id = $id;
        } else {
            //ADD
            $result_id = $this->cm->sync_insert_data($data, $this->db['data_country'], $this->cm->sync_client, $this->cm->sync_data);
        }

        return $result_id;
    }

    public function trash_country($form_state) {
        $result = 0;
        $status = isset($form_state['status']) ? $form_state['status'] : 0;

        if ($form_state['id']) {
            // To trash
            $id = $form_state['id'];
            $data = array(
                'status' => $status
            );
            $this->cm->sync_update_data($data, $id, $this->db['data_country'], $this->cm->sync_data);
            $result = $id;
        }
        return $result;
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

    public function get_country_by_slug($slug, $cache = false) {
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
        $sql = sprintf("SELECT id, name FROM {$this->db['data_country']} WHERE slug='%s'", $this->escape($slug));
        $result = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$slug] = $result;
        }
        return $result;
    }

    public function get_country_by_id($id, $cache = false) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }

        //Get author id
        $sql = sprintf("SELECT name FROM {$this->db['data_country']} WHERE id=%d", (int) $id);
        $result = $this->db_get_var($sql);

        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }

    public function get_countries_by_ids($ids = array()) {
        $sql = sprintf("SELECT id, name, slug FROM {$this->db['data_country']} WHERE id IN(%s)", implode(',', $ids));
        $result = $this->db_results($sql);
        $ret = array();
        if (sizeof($result)) {
            foreach ($result as $item) {
                $ret[$item->id] = $item;
            }
        }
        return $ret;
    }

    public function get_movie_countries($mid) {
        $sql = sprintf("SELECT id, name FROM {$this->db['data_country']} g"
                . " INNER JOIN {$this->db['meta_country']} m ON m.id = g.cid"
                . " WHERE m.mid=%d", $mid);

        $result = $this->db_results($sql);
        return $result;
    }

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
            $id = $this->cm->sync_insert_data($data, $this->db['data_country'], $this->cm->sync_client, $this->cm->sync_data);
        }
        return $id;
    }

    public function update_country_slug($id, $slug) {
        $country = $this->get_country_by_id($id);
        if ($country) {
            $data = array(
                'slug' => $slug
            );
            $this->cm->sync_update_data($data, $id, $this->db['data_country'], $this->cm->sync_data);
        }
    }

    public function get_movies_no_country_meta($count) {
        // UNUSED DEPRECATED
        $sql = sprintf("SELECT p.id, p.country FROM {$this->db['movie_imdb']} p "
                . "LEFT JOIN {$this->db['meta_country']} m ON p.id = m.mid "
                . "WHERE p.country !='' AND m.id is NULL limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }

    public function add_movie_country($mid = 0, $cid = 0) {
        // Validate values
        if ($mid > 0 && $cid > 0) {
            //Get meta
            $sql = sprintf("SELECT cid FROM {$this->db['meta_country']} WHERE mid=%d AND cid=%d", (int) $mid, (int) $cid);
            $meta_exist = $this->db_get_var($sql);
            if (!$meta_exist) {
                //Meta not exist
                $data = array(
                    'mid' => $mid,
                    'cid' => $cid,
                );
                $id = $this->cm->sync_insert_data($data, $this->db['meta_country'], $this->cm->sync_client, $this->cm->sync_data);
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public function remove_movie_country($mid, $cid = '') {
        if ($cid) {
            $sql = sprintf("DELETE FROM {$this->db['meta_country']} WHERE mid=%d AND cid=%d", (int) $mid, (int) $cid);
        } else {
            $sql = sprintf("DELETE FROM {$this->db['meta_country']} WHERE mid=%d", (int) $mid);
        }

        $this->db_query($sql);
    }

    public function get_countries_list($pids = array()) {
        $pid_and = '';
        if (sizeof($pids)) {
            $pid_and = sprintf(" AND pid IN(%s)", implode(',', $pids));
        }
        $sql = "SELECT pid, name, slug, weight FROM {$this->db['data_country']} WHERE id>0" . $pid_and;
        $result = $this->db_results($sql);

        $ret = array();
        if (sizeof($result)) {
            foreach ($result as $item) {
                $ret[$item->pid] = $item;
            }
        }
        return $ret;
    }

    /*
     * language_code
     */

    public function get_lanuage_by_slug($slug, $cache = false) {
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
        $sql = sprintf("SELECT id, title FROM {$this->db['language_code']} WHERE code='%s'", $this->escape($slug));
        $result = $this->db_fetch_row($sql);

        if (!$result->title) {
            $result->title = $this->getLanguageByCode($slug);
        }

        if ($cache) {
            $dict[$slug] = $result;
        }
        return $result;
    }

    public function get_all_languauges() {
        $sql = "SELECT id, code, title FROM {$this->db['language_code']}";
        $result = $this->db_results($sql);
        $ret = array();
        if (sizeof($result)) {
            foreach ($result as $item) {
                if (!$item->title) {
                    $item->title = $this->getLanguageByCode($item->code);
                }
                $ret[$item->id] = $item;
            }
        }
        return $ret;
    }

    public function getLanguageByCode($code) {
        if (!$code)
            return '';
        $names = array(
            "aa" => "Afar",
            "ab" => "Abkhazian",
            "af" => "Afrikaans",
            "am" => "Amharic",
            "ar" => "Arabic",
            "ar-ae" => "Arabic (U.A.E.)",
            "ar-bh" => "Arabic (Bahrain)",
            "ar-dz" => "Arabic (Algeria)",
            "ar-eg" => "Arabic (Egypt)",
            "ar-iq" => "Arabic (Iraq)",
            "ar-jo" => "Arabic (Jordan)",
            "ar-kw" => "Arabic (Kuwait)",
            "ar-lb" => "Arabic (Lebanon)",
            "ar-ly" => "Arabic (libya)",
            "ar-ma" => "Arabic (Morocco)",
            "ar-om" => "Arabic (Oman)",
            "ar-qa" => "Arabic (Qatar)",
            "ar-sa" => "Arabic (Saudi Arabia)",
            "ar-sy" => "Arabic (Syria)",
            "ar-tn" => "Arabic (Tunisia)",
            "ar-ye" => "Arabic (Yemen)",
            "as" => "Assamese",
            "ay" => "Aymara",
            "az" => "Azeri",
            "ba" => "Bashkir",
            "be" => "Belarusian",
            "bg" => "Bulgarian",
            "bh" => "Bihari",
            "bi" => "Bislama",
            "bm" => "Bambara",
            "bn" => "Bengali",
            "bo" => "Tibetan",
            "br" => "Breton",
            "bs" => "Bosnian",
            "ca" => "Catalan",
            "co" => "Corsican",
            "cs" => "Czech",
            "cy" => "Welsh",
            "da" => "Danish",
            "de" => "German",
            "de-at" => "German (Austria)",
            "de-ch" => "German (Switzerland)",
            "de-li" => "German (Liechtenstein)",
            "de-lu" => "German (Luxembourg)",
            "div" => "Divehi",
            "dz" => "Bhutani",
            "el" => "Greek",
            "en" => "English",
            "en-au" => "English (Australia)",
            "en-bz" => "English (Belize)",
            "en-ca" => "English (Canada)",
            "en-gb" => "English (United Kingdom)",
            "en-ie" => "English (Ireland)",
            "en-jm" => "English (Jamaica)",
            "en-nz" => "English (New Zealand)",
            "en-ph" => "English (Philippines)",
            "en-tt" => "English (Trinidad)",
            "en-us" => "English (United States)",
            "en-za" => "English (South Africa)",
            "en-zw" => "English (Zimbabwe)",
            "eo" => "Esperanto",
            "es" => "Spanish",
            "es-ar" => "Spanish (Argentina)",
            "es-bo" => "Spanish (Bolivia)",
            "es-cl" => "Spanish (Chile)",
            "es-co" => "Spanish (Colombia)",
            "es-cr" => "Spanish (Costa Rica)",
            "es-do" => "Spanish (Dominican Republic)",
            "es-ec" => "Spanish (Ecuador)",
            "es-es" => "Spanish (España)",
            "es-gt" => "Spanish (Guatemala)",
            "es-hn" => "Spanish (Honduras)",
            "es-mx" => "Spanish (Mexico)",
            "es-ni" => "Spanish (Nicaragua)",
            "es-pa" => "Spanish (Panama)",
            "es-pe" => "Spanish (Peru)",
            "es-pr" => "Spanish (Puerto Rico)",
            "es-py" => "Spanish (Paraguay)",
            "es-sv" => "Spanish (El Salvador)",
            "es-us" => "Spanish (United States)",
            "es-uy" => "Spanish (Uruguay)",
            "es-ve" => "Spanish (Venezuela)",
            "et" => "Estonian",
            "eu" => "Basque",
            "fa" => "Farsi",
            "fi" => "Finnish",
            "fj" => "Fiji",
            "fo" => "Faeroese",
            "fr" => "French",
            "fr-be" => "French (Belgium)",
            "fr-ca" => "French (Canada)",
            "fr-ch" => "French (Switzerland)",
            "fr-lu" => "French (Luxembourg)",
            "fr-mc" => "French (Monaco)",
            "fy" => "Frisian",
            "ga" => "Irish",
            "gd" => "Gaelic",
            "gl" => "Galician",
            "gn" => "Guarani",
            "gu" => "Gujarati",
            "ha" => "Hausa",
            "he" => "Hebrew",
            "hi" => "Hindi",
            "hr" => "Croatian",
            "hu" => "Hungarian",
            "hy" => "Armenian",
            "ia" => "Interlingua",
            "id" => "Indonesian",
            "ie" => "Interlingue",
            "iu"=>"Inuktitut",
            "ik" => "Inupiak",
            "in" => "Indonesian",
            "is" => "Icelandic",
            "it" => "Italian",
            "it-ch" => "Italian (Switzerland)",
            "iw" => "Hebrew",
            "ja" => "Japanese",
            "ji" => "Yiddish",
            "jw" => "Javanese",
            "ka" => "Georgian",
            "kk" => "Kazakh",
            "kl" => "Greenlandic",
            "km" => "Cambodian",
            "kn" => "Kannada",
            "ko" => "Korean",
            "kok" => "Konkani",
            "ks" => "Kashmiri",
            "ku" => "Kurdish",
            "ky" => "Kirghiz",
            "kz" => "Kyrgyz",
            "la" => "Latin",
            "lb"=>"Luxembourgish",
            "ln" => "Lingala",
            "lo" => "Laothian",
            "ls" => "Slovenian",
            "lt" => "Lithuanian",
            "lv" => "Latvian",
            "mg" => "Malagasy",
            "mi" => "Maori",
            "mk" => "FYRO Macedonian",
            "ml" => "Malayalam",
            "mn" => "Mongolian",
            "mo" => "Moldavian",
            "mr" => "Marathi",
            "ms" => "Malay",
            "mt" => "Maltese",
            "my" => "Burmese",
            "na" => "Nauru",
            "nb" => "Norwegian (Bokmal)",
            "nb-no" => "Norwegian (Bokmal)",
            "ne" => "Nepali (India)",
            "nl" => "Dutch",
            "nl-be" => "Dutch (Belgium)",
            "nn-no" => "Norwegian",
            "no" => "Norwegian (Bokmal)",
            "oc" => "Occitan",
            "om" => "(Afan)/Oromoor/Oriya",
            "or" => "Oriya",
            "pa" => "Punjabi",
            "pl" => "Polish",
            "ps" => "Pashto/Pushto",
            "pt" => "Portuguese",
            "pt-br" => "Portuguese (Brazil)",
            "qu" => "Quechua",
            "rm" => "Rhaeto-Romanic",
            "rn" => "Kirundi",
            "ro" => "Romanian",
            "ro-md" => "Romanian (Moldova)",
            "ru" => "Russian",
            "ru-md" => "Russian (Moldova)",
            "rw" => "Kinyarwanda",
            "sa" => "Sanskrit",
            "sb" => "Sorbian",
            "sd" => "Sindhi",
            "sg" => "Sangro",
            "sh" => "Serbo-Croatian",
            "si" => "Singhalese",
            "sk" => "Slovak",
            "sl" => "Slovenian",
            "sm" => "Samoan",
            "sn" => "Shona",
            "so" => "Somali",
            "sq" => "Albanian",
            "sr" => "Serbian",
            "ss" => "Siswati",
            "st" => "Sesotho",
            "su" => "Sundanese",
            "sv" => "Swedish",
            "sv-fi" => "Swedish (Finland)",
            "sw" => "Swahili",
            "sx" => "Sutu",
            "syr" => "Syriac",
            "ta" => "Tamil",
            "te" => "Telugu",
            "tg" => "Tajik",
            "th" => "Thai",
            "ti" => "Tigrinya",
            "tk" => "Turkmen",
            "tl" => "Tagalog",
            "tn" => "Tswana",
            "to" => "Tonga",
            "tr" => "Turkish",
            "ts" => "Tsonga",
            "tt" => "Tatar",
            "tw" => "Twi",
            "uk" => "Ukrainian",
            "ur" => "Urdu",
            "us" => "English",
            "uz" => "Uzbek",
            "vi" => "Vietnamese",
            "vo" => "Volapuk",
            "wo" => "Wolof",
            "xh" => "Xhosa",
            "xx"=>"N/A",
            "yi" => "Yiddish",
            "yo" => "Yoruba",
            "zh" => "Chinese",
            "cn" => "Chinese (China)",
            "zh-cn" => "Chinese (China)",
            "zh-hk" => "Chinese (Hong Kong SAR)",
            "zh-mo" => "Chinese (Macau SAR)",
            "zh-sg" => "Chinese (Singapore)",
            "zh-tw" => "Chinese (Taiwan)",
            "zu" => "Zulu",
        );
        if (isset($names[$code]))
            return $names[$code];
        else
            return $code;
    }

    /*
     * Actors
     */

    public function get_actors($movie_id = 0, $type = 1) {
        $sql = sprintf("SELECT name FROM {$this->db['actors_imdb']} a "
                . "INNER JOIN {$this->db['meta_actor']} m ON m.aid = a.id "
                . "WHERE m.mid=%d AND m.type=%d", $movie_id, $type);
        $results = $this->db_results($sql);

        return $results;
    }

    public function get_movies_no_actors_meta($count) {
        $sql = sprintf("SELECT p.id, p.actors FROM {$this->db['movie_imdb']} p "
                . "LEFT JOIN {$this->db['meta_actor']} m ON p.id = m.mid "
                . "WHERE p.actors !='' AND m.id is NULL limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }

    public function add_movie_actor($mid = 0, $id = 0, $type = 0) {
        // Validate values
        if ($mid > 0 && $id > 0) {
            //Get meta
            $sql = sprintf("SELECT mid FROM {$this->db['meta_actor']} WHERE mid=%d AND aid=%d", (int) $mid, (int) $id);
            $meta_exist = $this->db_get_var($sql);
            if (!$meta_exist) {
                //Meta not exist
                $data = array(
                    'mid' => $mid,
                    'aid' => $id,
                    'type' => $type,
                );
                $this->cm->sync_insert_data($data, $this->db['meta_actor'], $this->cm->sync_client, $this->cm->sync_data);
            }
            return true;
        }
        return false;
    }

    public function get_actors_no_slug($count) {
        $sql = sprintf("SELECT actor_id, primaryName as name FROM {$this->db['actors']}"
                . " WHERE slug='' limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }

    public function get_actor_by_slug($slug) {
        $sql = sprintf("SELECT actor_id, primaryName as name FROM {$this->db['actors']}"
                . " WHERE slug='%s'", $slug);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_actor_by_actor_id($actor_id) {
        $sql = sprintf("SELECT id, actor_id, primaryName as name FROM {$this->db['actors']}"
                . " WHERE actor_id=%d", $actor_id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function update_actor_slug($actor_id, $slug) {
        $id = $this->get_actor_by_actor_id($actor_id);
        if ($id) {
            $data = array(
                'slug' => $slug
            );
            $this->cm->sync_update_data($data, $id, $this->db['actors'], $this->cm->sync_data);
        }
    }

    /*
     * Directors
     */

    public function get_directors($movie_id = 0, $type = -1) {

        $and_type = '';
        if ($type != -1) {
            $and_type = " AND m.type=" . (int) $type;
        }

        $sql = sprintf("SELECT name FROM {$this->db['actors_imdb']} a "
                . "INNER JOIN {$this->db['meta_director']} m ON m.aid = a.id "
                . "WHERE m.mid=%d" . $and_type, $movie_id);
        $results = $this->db_results($sql);
        return $results;
    }

    public function get_movies_no_director_meta($count) {
        $sql = sprintf("SELECT * FROM {$this->db['movie_imdb']}  limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }

    public function add_movie_director($mid = 0, $id = 0, $type = 0) {
        // Validate values
        if ($mid > 0 && $id > 0) {
            //Get meta
            $sql = sprintf("SELECT mid FROM {$this->db['meta_director']} WHERE mid=%d AND aid=%d AND type=%d", (int) $mid, (int) $id, (int) $type);
            $meta_exist = $this->db_get_var($sql);
            if (!$meta_exist) {
                //Meta not exist
                $data = array(
                    'mid' => $mid,
                    'aid' => $id,
                    'type' => $type,
                );
                $this->cm->sync_insert_data($data, $this->db['meta_director'], $this->cm->sync_client, $this->cm->sync_data);
            }
            return true;
        }
        return false;
    }

    public function create_slug($string, $glue = '-') {
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
        $slug = strtolower(strtr($stripped, $table));
        $slug = preg_replace('~[^\pL\d]+~u', $glue, $slug);

        $slug = preg_replace('/^-/', '', $slug);
        $slug = preg_replace('/-$/', '', $slug);

        return $slug;
    }

    /*
     * Providers
     */

    public function get_provider($id) {
        $sql = sprintf("SELECT pid, name, slug, status, weight, free, image FROM {$this->db['data_provider']} WHERE id=%d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_providers($status = -1, $page = 1, $orderby = '', $order = 'ASC', $free = -1) {
        $page -= 1;
        $start = $page * $this->perpage;

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE status = " . (int) $status;
        }

        // Free
        $free_query = "";
        if ($free != -1) {
            $free_query = " AND free = " . (int) $free;
        }

        //Sort
        $and_orderby = '';

        if ($orderby && in_array($orderby, $this->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY name ASC";
        }


        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        $sql = "SELECT pid, id, status, name, slug, weight, free, image FROM {$this->db['data_provider']} " . $status_query . $free_query . $and_orderby . $limit;

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_providers_by_type($type = 0, $cache = true) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$type])) {
                return $dict[$type];
            }
        }
        $sql = sprintf("SELECT pid FROM {$this->db['data_provider']} WHERE free=%d AND status=1", $type);
        $result = $this->db_results($sql);
        $ret = array();
        if (sizeof($result)) {
            foreach ($result as $value) {
                $ret[] = $value->pid;
            }
        }
        $dict[$type] = $ret;

        return $ret;
    }

    public function get_providers_states() {
        $count = $this->get_providers_count();
        $feed_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->provider_status as $key => $value) {
            $feed_states[$key] = array(
                'title' => $value,
                'count' => $this->get_providers_count($key));
        }
        return $feed_states;
    }

    public function get_providers_free_status($type) {
        $count = $this->get_providers_count($type);
        $feed_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->provider_free_status as $key => $value) {
            $feed_states[$key] = array(
                'title' => $value,
                'count' => $this->get_providers_count($type, $key));
        }
        return $feed_states;
    }

    public function provider_actions($exclude = array()) {
        foreach ($this->provider_tabs as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }
            $feed_actions[$key] = array('title' => $value);
        }
        return $feed_actions;
    }

    public function get_provider_status($status) {
        return isset($this->provider_status[$status]) ? $this->provider_status[$status] : 'None';
    }

    public function get_providers_count($status = -1, $free = -1) {
        // Custom status
        $status_trash = 2;
        $status_query = " WHERE status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE status = " . (int) $status;
        }

        $free_query = "";
        if ($free != -1) {
            $free_query = " AND free = " . (int) $free;
        }

        $query = "SELECT COUNT(id) FROM {$this->db['data_provider']} " . $status_query . $free_query;
        $result = $this->db_get_var($query);
        return $result;
    }

    public function provider_edit_validate($form_state) {

        if (isset($form_state['trash'])) {
            // Trash
        } else {
            // Edit
            if ($form_state['name'] == '') {
                return __('Enter the provider name');
            }

            if ($form_state['slug'] == '') {
                return __('Enter the provider slug');
            }
        }

        $nonce = wp_verify_nonce($_POST['critic-feeds-nonce'], 'critic-feeds-options');
        if (!$nonce) {
            return __('Error validate nonce');
        }

        return true;
    }

    public function provider_edit_submit($form_state) {
        $result_id = 0;
        $status = (int) $form_state['status'];
        $pid = (int) $form_state['pid'];
        $free = (int) $form_state['free'];
        $name = $this->escape($form_state['name']);
        $slug = $this->escape($form_state['slug']);
        $image = $this->escape($form_state['image']);
        $weight = (int) ($form_state['weight']);

        $data = array(
            'pid' => $pid,
            'status' => $status,
            'weight' => $weight,
            'name' => $name,
            'slug' => $slug,
            'free' => $free,
            'image' => $image,
        );

        if ($form_state['id']) {
            $id = (int) $form_state['id'];
            //EDIT           
            $this->cm->sync_update_data($data, $id, $this->db['data_provider'], $this->cm->sync_data);
            $result_id = $id;
        } else {
            //ADD
            $result_id = $this->cm->sync_insert_data($data, $this->db['data_provider'], $this->cm->sync_client, $this->cm->sync_data);
        }

        return $result_id;
    }

    public function trash_provider($form_state) {
        $result = 0;
        $status = isset($form_state['status']) ? $form_state['status'] : 0;

        if ($form_state['id']) {
            // To trash
            $id = $form_state['id'];
            $data = array(
                'status' => $status,
            );
            $this->cm->sync_update_data($data, $id, $this->db['data_provider'], $this->cm->sync_data);
            $result = $id;
        }
        return $result;
    }

    public function get_providers_list($pids = array()) {
        $pid_and = '';
        if (sizeof($pids)) {
            $pid_and = sprintf(" AND pid IN(%s)", implode(',', $pids));
        }
        $sql = "SELECT pid, name, slug, image, free, weight FROM {$this->db['data_provider']} WHERE id>0" . $pid_and;
        $result = $this->db_results($sql);

        $ret = array();
        if (sizeof($result)) {
            foreach ($result as $item) {
                $ret[$item->pid] = $item;
            }
        }
        return $ret;
    }

    public function get_provider_by_pid($pid) {
        $sql = sprintf("SELECT pid, name, slug, free FROM {$this->db['data_provider']} WHERE pid=%d", (int) $pid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_provider_by_slug($slug, $cache = false) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$slug])) {
                return $dict[$slug];
            }
        }

        $sql = sprintf("SELECT pid, name, free, image FROM {$this->db['data_provider']} WHERE slug='%s'", $this->escape($slug));
        $result = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$slug] = $result;
        }
        return $result;
    }

    public function get_or_create_provider_by_pid($pid = 0, $name = '', $img = '') {
        $result = $this->get_provider_by_pid($pid);

        $id = 0;
        if ($result) {
            $id = $result->pid;
        }
        if (!$id) {
            // Create slug
            $slug = $this->create_slug($name);
            $exist = $this->get_provider_by_slug($slug);
            if ($exist) {
                $slug = $slug . '-' . $pid;
            }
            // TODO slug is unique?
            // Create the genre
            $data = array(
                'pid' => $pid,
                'name' => $name,
                'slug' => $slug,
                'image' => $img,
            );
            $id = $this->cm->sync_insert_data($data, $this->db['data_provider'], $this->cm->sync_client, $this->cm->sync_data);
        }
        return $id;
    }

    /*
     * Population
     */

    public function get_population($country_key = '', $power = false) {
        $country_and = "";
        if ($country_key) {
            $country_and = sprintf(" AND cca2='%s'", $country_key);
        }

        $select_and = '';
        $join = '';
        if ($power) {
            $join .= " INNER JOIN {$this->db['power']} pw ON p.cca2 = pw.cca2";
            $select_and .= ", pw.per_capita, pw.total, pw.date, pw.name";
        }

        $sql = "SELECT p.*" . $select_and . " FROM {$this->db['population']} p" . $join . " WHERE p.id>0" . $country_and;

        $results = $this->db_results($sql);
        if ($country_key) {
            $results = isset($results[0]) ? $results[0] : array();
        }
        return $results;
    }

    public function get_array_compare() {
        $sql = "SELECT * FROM {$this->db['options']} where id =3 limit 1";
        $row = $this->db_fetch_row($sql);
        $val = $row->val;
        $val = str_replace('\\', '', $val);
        $array_compare_0 = explode("',", $val);
        foreach ($array_compare_0 as $val) {
            $val = trim($val);
            // echo $val.' ';
            $result = explode('=>', $val);
            ///var_dump($result);
            $index = trim(str_replace("'", "", $result[0]));
            $value = trim(str_replace("'", "", $result[1]));

            $regv = '#([A-Za-z\,\(\)\- ]{1,})#';

            if (preg_match($regv, $index, $mach)) {
                $index = $mach[1];
            }


            $index = trim($index);

            $array_compare[$index] = $value;
        }
        return $array_compare;
    }

    /*
     * Franchise
     */

    public function get_franchises() {

        $sql = "SELECT id, name FROM {$this->db['franchises']}";
        $result = $this->db_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $item) {
                $ret[$item->id] = $item->name;
            }
        }
        return $ret;
    }

    public function get_franchises_by_ids($ids, $cache = true) {
        $ids_str = implode(',', $ids);
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$ids_str])) {
                return $dict[$ids_str];
            }
        }

        $sql = sprintf("SELECT id, name FROM {$this->db['franchises']} WHERE id IN(%s)", $ids_str);
        $result = $this->db_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $item) {
                $ret[$item->id] = $item->name;
            }
        }

        if ($cache) {
            $dict[$ids_str] = $ret;
        }
        return $ret;
    }

    /*
     * Distributor
     */

    public function get_distributors() {

        $sql = "SELECT id, name FROM {$this->db['distributors']}";
        $result = $this->db_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $item) {
                $ret[$item->id] = $item->name;
            }
        }
        return $ret;
    }

    public function get_distributors_by_ids($ids, $cache = true) {
        $ids_str = implode(',', $ids);
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$ids_str])) {
                return $dict[$ids_str];
            }
        }

        $sql = sprintf("SELECT id, name FROM {$this->db['distributors']} WHERE id IN(%s)", $ids_str);
        $result = $this->db_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $item) {
                $ret[$item->id] = $item->name;
            }
        }

        if ($cache) {
            $dict[$ids_str] = $ret;
        }
        return $ret;
    }

    /*
     * Admin functions
     */

    public function get_post_count($post_type = '') {
        $post_and = " AND type IN('Movie','TVseries','VideoGame','PodcastSeries')";
        if ($post_type && $post_type != 'all') {
            $post_and = sprintf(" AND type='%s'", $post_type);
        }
        $query = "SELECT COUNT(id) FROM {$this->db['movie_imdb']} WHERE id>0" . $post_and;
        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_posts($page = 1, $post_type = '', $orderby = '', $order = 'ASC', $need_year = false) {
        $page -= 1;
        $start = $page * $this->perpage;

        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        $post_and = " AND type IN('Movie','TVseries','VideoGame','PodcastSeries')";
        if ($post_type && $post_type != 'all') {
            $post_and = sprintf(" AND type='%s'", $post_type);
        }

        $year_and = '';
        if ($need_year) {
            $year_and = ' AND year>0';
        }

        //Sort
        $and_orderby = '';

        if ($orderby && in_array($orderby, $this->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY id DESC";
        }

        $sql = "SELECT * FROM {$this->db['movie_imdb']} WHERE id>0" . $year_and . $post_and . $and_orderby . $limit;

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_post_slug($type = '') {
        return $this->movie_slug[$type];
    }

    public function get_post_link($post) {
        return '/' . $this->get_post_slug($post->type) . '/' . $post->post_name;
    }

    public function get_movie($id) {
        $sql = sprintf("SELECT * FROM {$this->db['movie_imdb']} WHERE ID=%d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_movie_name_by_id($id) {
        $sql = sprintf("SELECT title, year FROM {$this->db['movie_imdb']} WHERE ID=%d", (int) $id);
        $result = $this->db_fetch_row($sql);
        $ret = '';
        if ($result) {
            $ret = $result->title . ' (' . $result->year . ')';
        }
        return $ret;
    }

    private function get_an_options($id, $cache = true) {

        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }

        $sql = sprintf("SELECT val FROM {$this->db['options']} WHERE id=%d", (int) $id);
        $result = $this->db_get_var($sql);

        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }

    private function get_perpage() {
        $this->perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : $this->perpage;
        return $this->perpage;
    }

    /*
     * Erating
     */

    public function get_movie_erating($mid) {
        $sql = sprintf("SELECT * FROM {$this->db['erating']} WHERE movie_id=%d", (int) $mid);
        $results = $this->db_fetch_row($sql);
        return $results;
    }

    public function update_erating($id, $data) {
        $this->sync_update_data($data, $id, $this->db['erating'], true, 10);
    }

    /*
     * Cpi
     */

    public function add_cpi($cpi, $year, $type = 0) {
        $exist = $this->get_cpi($year, $type);
        // Add cpi
        if (!$exist) {
            // ADD
            $data = array(
                'type' => $type,
                'year' => $year,
                'cpi' => $cpi,
            );
            $id = $this->cm->sync_insert_data($data, $this->db['cpi'], $this->cm->sync_client, $this->cm->sync_data);
        }
    }

    public function get_cpi($year, $type) {
        $sql = sprintf("SELECT type, year, cpi FROM {$this->db['cpi']} WHERE year=%d AND type=%d", $year, $type);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_all_cpi($type = 0) {
        $sql = sprintf("SELECT type, year, cpi FROM {$this->db['cpi']} WHERE type=%d", $type);
        $results = $this->db_results($sql);
        $ret = array();
        foreach ($results as $result) {
            $ret[$result->year] = $result->cpi;
        }
        ksort($ret);
        return $ret;
    }

    /*
     * Race rule
     */

    public function get_race_rule_by_id($id) {
        $sql = sprintf("SELECT * FROM {$this->db['race_rule']} WHERE id=%d limit 1", $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_or_create_race_rule_id($rules = array()) {
        $rule_hash = $this->get_race_rules_hash($rules);
        $result = $this->get_race_rule_by_hash($rule_hash);
        if ($result) {
            $id = $result->id;
        } else {
            $id = $this->insert_race_rule($rules);
        }

        return $id;
    }

    public function get_race_rule_by_hash($rule_hash) {
        $sql = sprintf("SELECT * FROM {$this->db['race_rule']} WHERE rule_hash='%s' limit 1", $rule_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function insert_race_rule($rules = array()) {
        $rule_str = json_encode($rules);
        $rule_hash = $this->get_race_rules_hash($rules);
        $data = array(
            'rule' => $rule_str,
            'rule_hash' => $rule_hash,
        );
        $is_client = false;
        $sync = true;
        $id = $this->sync_insert_data($data, $this->db['race_rule'], $is_client, $sync, 5);
        return $id;
    }

    public function get_race_rules_hash($data = array()) {
        $data_str = json_encode($data);
        $hash = md5($data_str);
        return $hash;
    }

    /* Movies weight */

    public function update_movies_weight($debug = false) {
        $date = $this->curr_time();
        $upd_interval = $this->movies_weight_upd_interval;
        $weight_upd = $date - ($upd_interval * 86400);
        // 1. Update w50. Last 30 days (30)       
        $w = 50;
        $curr_ym = date("Y-m", $date);
        $releases = array();
        for ($i == 1; $i < 32; $i++) {
            $j = $i;
            if ($i < 10) {
                $j = "0" . $i;
            }
            $releases[] = "'" . $curr_ym . '-' . $j . "'";
        }

        $sql_select = sprintf("SELECT * FROM {$this->db['movie_imdb']} WHERE weight_upd<%d AND `release` IN(" . implode(',', $releases) . ")", $weight_upd);
        $sql = sprintf("UPDATE {$this->db['movie_imdb']} SET weight=%d, weight_upd=%d WHERE weight_upd<%d AND `release` IN(" . implode(',', $releases) . ")", $w, $date, $weight_upd);
        if ($debug) {
            print $sql_select . "<br />";
            print $sql . "<br />";
        }
        $this->db_query($sql);

        // 2. Last year and rating 3-5 (250)
        $w = 40;
        $curr_y = date("Y", $date);
        $sql_select = sprintf("SELECT p.* FROM {$this->db['movie_imdb']} p INNER JOIN data_pg_rating r ON r.movie_id = p.movie_id WHERE p.weight_upd<%d AND p.year>=%d AND r.rwt_pg_result>2", $weight_upd, $curr_y);
        $sql = sprintf("UPDATE {$this->db['movie_imdb']} p INNER JOIN data_pg_rating r ON r.movie_id = p.movie_id SET p.weight=%d, p.weight_upd=%d WHERE p.weight_upd<%d AND p.year>=%d AND r.rwt_pg_result>2", $w, $date, $weight_upd, $curr_y);
        if ($debug) {
            print $sql_select . "<br />";
            print $sql . "<br />";
        }
        $this->db_query($sql);

        // 3. Last 3 year and rating 4-5 (200)
        $w = 30;
        $years = array();
        for ($i = 0; $i < 3; $i++) {
            $years[] = $curr_y - $i;
        }
        $sql_select = sprintf("SELECT p.* FROM {$this->db['movie_imdb']} p INNER JOIN data_pg_rating r ON r.movie_id = p.movie_id WHERE p.weight_upd<%d AND p.year IN(" . implode(',', $years) . ") AND r.rwt_pg_result>3", $weight_upd);
        $sql = sprintf("UPDATE {$this->db['movie_imdb']} p INNER JOIN data_pg_rating r ON r.movie_id = p.movie_id SET p.weight=%d, p.weight_upd=%d WHERE p.weight_upd<%d AND p.year IN(" . implode(',', $years) . ") AND r.rwt_pg_result>3", $w, $date, $weight_upd);
        if ($debug) {
            print $sql_select . "<br />";
            print $sql . "<br />";
        }
        $this->db_query($sql);

        // 4. All time and rating 4-5 (3500)
        $w = 20;
        $sql_select = sprintf("SELECT p.* FROM {$this->db['movie_imdb']} p INNER JOIN data_pg_rating r ON r.movie_id = p.movie_id WHERE p.weight_upd<%d AND r.rwt_pg_result>3", $weight_upd);
        $sql = sprintf("UPDATE {$this->db['movie_imdb']} p INNER JOIN data_pg_rating r ON r.movie_id = p.movie_id SET p.weight=%d, p.weight_upd=%d WHERE p.weight_upd<%d AND r.rwt_pg_result>3", $w, $date, $weight_upd);
        if ($debug) {
            print $sql_select . "<br />";
            print $sql . "<br />";
        }
        $this->db_query($sql);

        // 5. Last 3 year (4000)
        $w = 10;
        $sql_select = sprintf("SELECT p.* FROM {$this->db['movie_imdb']} p WHERE p.weight_upd<%d AND p.year IN(" . implode(',', $years) . ")", $weight_upd);
        $sql = sprintf("UPDATE {$this->db['movie_imdb']} p SET p.weight=%d, p.weight_upd=%d WHERE p.weight_upd<%d AND p.year IN(" . implode(',', $years) . ")", $w, $date, $weight_upd);
        if ($debug) {
            print $sql_select . "<br />";
            print $sql . "<br />";
        }
        $this->db_query($sql);

        // 6. Other (27000)
        $w = 0;
        $sql_select = sprintf("SELECT p.* FROM {$this->db['movie_imdb']} p WHERE p.weight_upd<%d", $weight_upd);
        $sql = sprintf("UPDATE {$this->db['movie_imdb']} p SET p.weight=%d, p.weight_upd=%d WHERE p.weight_upd<%d", $w, $date, $weight_upd);
        if ($debug) {
            print $sql_select . "<br />";
            print $sql . "<br />";
        }
        $this->db_query($sql);
    }

    /* Nf kewords */

    public function get_nf_keywords($mid) {
        $sql = sprintf("SELECT date, keywords FROM {$this->db['cache_nf_keywords']} WHERE mid=%d", $mid);
        $result = $this->db_fetch_row($sql);

        if ($result) {
            $live_time = 86400 * 30;
            $curr_time = $this->curr_time();
            $max_date = $curr_time - $live_time;
            $date = $result->date;
            if ($date > $max_date) {
                $keywords = $result->keywords;
                return $keywords;
            }
        }

        return '';
    }

    public function add_nf_keywords($keywords, $mid) {
        $sql = sprintf("SELECT id FROM {$this->db['cache_nf_keywords']} WHERE mid=%d", $mid);
        $exist_id = $this->db_get_var($sql);
        $curr_time = $this->curr_time();
        $data = array(
            "date" => $curr_time,
            "keywords" => $keywords,
        );
        if ($exist_id) {
            $this->db_update($data, $this->db['cache_nf_keywords'], $exist_id);
        } else {
            $data['mid'] = $mid;
            $this->db_insert($data, $this->db['cache_nf_keywords']);
        }
    }

    /*
     * Movie keywords
     */

    public function get_movie_keywords($mid = 0) {

        $sql = sprintf("SELECT k.id, k.name FROM {$this->db['meta_movie_keywords']} m"
                . " INNER JOIN {$this->db['meta_keywords']} k ON k.id = m.kid WHERE m.mid=%d", $mid);
        $data = $this->db_results($sql);
        return $data;
    }

    /*
     * hook movie upd
     */

    public function hook_actors_movie($actors_ids = array(), $debug = false) {
        if (!is_array($actors_ids)) {
            $actors_ids = array($actors_ids);
        }
        if ($debug) {

            print_r(array('hook_actors_movie', $actors_ids));
        }

        $sql = "SELECT mid FROM {$this->db['meta_actor']} WHERE aid IN(" . implode(',', $actors_ids) . ")";
        $results = $this->db_results($sql);
        $mids = array();
        if ($results) {
            foreach ($results as $movie) {
                $mids[$movie->mid] = $movie->mid;
            }
        }
        if ($debug) {
            print_r($mids);
        }
        $this->add_movies_upd(array_keys($mids), $debug);
    }

    /*
     * Hook add movie to update list
     */

    public function hook_add_movies($mids = array(), $debug = false) {
        if (!is_array($mids)) {
            $mid_int = (int) $mids;
            if ($mid_int) {
                $mids = array($mid_int);
            }
        }
        $this->add_movies_upd($mids, $debug);
    }

    /*
     * Add movies to update list
     */

    private function add_movies_upd($mids = array(), $debug = false) {
        if ($debug) {
            print_r(array('add_movies_upd', $mids));
        }
        if ($mids) {
            // Update exist movies
            $sql = "SELECT mid FROM {$this->db['hook_movie_upd']} WHERE mid IN(" . implode(',', $mids) . ")";
            $results = $this->db_results($sql);
            $mids_toupd = array();

            if ($results) {
                foreach ($results as $movie) {
                    $mids_toupd[$movie->mid] = 1;
                }
                if ($debug) {
                    print_r(array('to update', $mids_toupd));
                }
                // Update movies
                $sql = "UPDATE {$this->db['hook_movie_upd']} SET need_upd=1 WHERE mid IN(" . implode(',', array_keys($mids_toupd)) . ")";
                $this->db_query($sql);
            }
            // Add new movies            
            foreach ($mids as $mid) {
                if (!isset($mids_toupd[$mid])) {
                    // Add a movie
                    $data = array(
                        'mid' => $mid,
                        'need_upd' => 1,
                    );
                    if ($debug) {
                        print_r(array('to add', $mid));
                    }
                    $this->db_insert($data, $this->db['hook_movie_upd']);
                }
            }
        }
    }

    public function run_movie_hook_cron($count = 10, $expire = 60, $debug = false, $force = false) {
        $curr_time = $this->curr_time();
        $exp_date = $curr_time - $expire * 60;
        $sql = sprintf("SELECT mid FROM {$this->db['hook_movie_upd']} WHERE need_upd=1 AND last_upd < %d ORDER BY last_upd ASC LIMIT %d", $exp_date, $count);
        $results = $this->db_results($sql);
        $mids = array();
        if ($results) {
            foreach ($results as $item) {
                $mids[] = $item->mid;
            }
        }
        if ($debug) {
            print_r($mids);
        }

        if ($mids) {
            // UPDATE mids need_upd
            $curr_time = $this->curr_time();
            $sql = sprintf("UPDATE {$this->db['hook_movie_upd']} SET need_upd=0, last_upd=%d WHERE mid IN(" . implode(',', $mids) . ")", $curr_time);
            $this->db_query($sql);

            // Add hooks here
            // Movies Simpson
            $ms = $this->cm->get_ms();
            $ms->hook_update_movies($mids, $debug);

            // Movies Actors
            $mac = $this->cm->get_mac();
            $mac->hook_update_movies($mids, $debug);
            // Movies Directors
            $mdirs = $this->cm->get_mdirs();
            $mdirs->hook_update_movies($mids, $debug);
        }
    }
}
