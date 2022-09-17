<?php

class MoviesLinksAn extends MoviesAbstractDBAn {

    public function __construct() {
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
            'movie_rating' => 'data_movie_rating',
            'actors_normalize' => 'data_actors_normalize',
            'actors_imdb' => 'data_actors_imdb',
            'lastnames' => 'data_lastnames',
            'fs_country' => 'data_familysearch_country',
            'meta_fs' => 'meta_familysearch',
        );
    }

    public function get_posts($type = 'a', $get_keys = array(), $limit = 1, $last_id = 0) {
        $type_query = '';
        if ($type == 'm') {
            $type_query = " AND type='Movie'";
        } else if ($type == 't') {
            $type_query = " AND type='TVSeries'";
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

        $sql = "SELECT " . $select . " FROM {$this->db['movie_imdb']} WHERE " . $and_last_id . $type_query . " ORDER BY id ASC" . $and_limit;
        $result = $this->db_results($sql);
        return $result;
    }
    
    public function get_post_ids_by_weight($min_weight=0){
        $ret = array();
        $sql = sprintf("SELECT id FROM {$this->db['movie_imdb']} WHERE weight>%d ORDER BY weight DESC",$min_weight);        
        $result = $this->db_results($sql);
        if ($result){
            foreach ($result as $value) {
                $ret[]=$value->id;
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

        $sql = sprintf("SELECT a.id, a.aid, a.firstname, a.lastname FROM {$this->db['actors_normalize']} a" . $actor_inner . " WHERE " . $and_last_id . $actor_and . " ORDER BY a.id ASC limit %d", (int) $count);

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

}
