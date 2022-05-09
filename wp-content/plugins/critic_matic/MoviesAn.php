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
        'TVseries' => 'TV'
    );
    public $movie_slug = array(
        'Movie' => 'movies',
        'TVSeries' => 'tvseries'
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
        $this->cm = $cm;
        $this->db = array(
            'movie_imdb' => 'data_movie_imdb',
            'actors_all' => 'data_actors_all',
            'movies_meta' => 'search_movies_meta',
            'options' => 'options',
            'data_genre' => 'data_movie_genre',
            'meta_genre' => 'meta_movie_genre',
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
        $sql = sprintf("SELECT * FROM {$this->db['movie_imdb']} WHERE type='%s' AND post_name='%s'", $this->escape($type), $this->escape($slug));
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
        $sql = sprintf("SELECT primaryName FROM {$this->db['actors_all']} WHERE actor_id=%d", (int) $id);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_expired_movies($limit = 10, $expire = 30) {

        $post_and = " AND p.type IN('Movie','TVseries')";

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
            $sql = sprintf("SELECT id FROM {$this->db['movies_meta']} WHERE mid=%d", (int) $mid);
            $meta_exist = $this->db_get_var($sql);


            if ($meta_exist) {
                // Update
                $data = array(
                    'date' => (int) $date
                );
                $this->cm->sync_update_data($data, $meta_exist, $this->db['movies_meta'], $this->cm->sync_data);
            } else {
                // Insert
                $data = array(
                    'mid' => (int) $mid,
                    'date' => (int) $date
                );
                $this->cm->sync_insert_data($data, $this->db['movies_meta'], $this->cm->sync_client, $this->cm->sync_data);
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
                'post_name' => (int) $post_name
            );
            $this->cm->sync_update_data($data, $id, $this->db['movie_imdb'], $this->cm->sync_data);
        }
    }

    public function create_post_name($id, $title, $type) {
        $post_name = $this->create_slug($title);
        //Post name is unique?
        $post_id = $this->get_post_id_by_name($post_name, $type);
        if ($post_id) {
            if ($post_id == $id) {
                //post_name already in db
                return $post_name;
            } else {
                // Add unique id to post name
                // TODO new post name
                $post_name = $post_name . '-' . $id;
            }
        }

        //Add postname to db
        $this->add_post_name($id, $post_name);

        return $post_name;
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

        $query = "SELECT COUNT(*) FROM {$this->db['data_genre']} " . $status_query;
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

        if ($cache) {
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
        $sql = sprintf("SELECT id, name FROM {$this->db['data_genre']} g"
                . " INNER JOIN {$this->db['meta_genre']} m ON m.id = g.gid"
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
            }
            return true;
        }
        return false;
    }

    public function remove_movie_genre($mid, $gid) {
        $sql = sprintf("DELETE FROM {$this->db['meta_genre']} WHERE mid=%d AND gid=%d", (int) $mid, (int) $gid);
        $this->db_query($sql);
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

        $query = "SELECT COUNT(*) FROM {$this->db['data_country']} " . $status_query;
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

        if ($cache) {
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
            }
            return true;
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
     * Actors
     */

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

    public function get_providers_by_type($type = 0) {
        $sql = sprintf("SELECT pid FROM {$this->db['data_provider']} WHERE free=%d AND status=1", $type);
        $result = $this->db_results($sql);
        $ret = array();
        if (sizeof($result)) {
            foreach ($result as $value) {
                $ret[] = $value->pid;
            }
        }
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

        $query = "SELECT COUNT(*) FROM {$this->db['data_provider']} " . $status_query . $free_query;
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
     * Admin functions
     */

    public function get_post_count($post_type = '') {
        $post_and = " AND type IN('Movie','TVseries')";
        if ($post_type && $post_type != 'all') {
            $post_and = sprintf(" AND type='%s'", $post_type);
        }
        $query = "SELECT COUNT(*) FROM {$this->db['movie_imdb']} WHERE id>0" . $post_and;
        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_posts($page = 1, $post_type = '', $orderby = '', $order = 'ASC') {
        $page -= 1;
        $start = $page * $this->perpage;

        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        $post_and = " AND type IN('Movie','TVseries')";
        if ($post_type && $post_type != 'all') {
            $post_and = sprintf(" AND type='%s'", $post_type);
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

        $sql = "SELECT * FROM {$this->db['movie_imdb']} WHERE id>0" . $post_and . $and_orderby . $limit;


        $result = $this->db_results($sql);

        return $result;
    }

    public function get_post_slug($type = '') {
        return $this->movie_slug[$type];
    }

    public function get_post_link($post) {
        return '/' . $this->get_post_slug($post->type) . '/' . $post->post_name;
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

}
