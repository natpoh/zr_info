<?php

/**
 * Custom functions for familysearch.org parser
 *
 * @author brahman
 */
class Familysearch extends MoviesAbstractDBAn {

    private $ml = '';
    public $sort_pages = array('id', 'lastname', 'topcountryname');

    //put your code here
    public function __construct($ml) {
        $this->ml = $ml ? $ml : new MoviesLinks();
        $this->db = array(
            'movie_imdb' => 'data_movie_imdb',
            'data_country' => 'data_movie_country',
            'meta_country' => 'meta_movie_country',
            'meta_actor' => 'meta_movie_actor',
            'meta_director' => 'meta_movie_director',
            'actors_normalize' => 'data_actors_normalize',
            'actors_imdb' => 'data_actors_imdb',
            'lastnames' => 'data_lastnames',
            'fs_country' => 'data_familysearch_country',
            'meta_fs' => 'meta_familysearch',
            'population' => 'data_population_country',
        );
    }

    public function get_posts($page = 1, $orderby = '', $order = 'ASC', $perpage = 30) {
        // Get lastnames
        $page -= 1;
        $start = $page * $perpage;

        $limit = '';
        if ($perpage > 0) {
            $limit = " LIMIT $start, " . $perpage;
        }

        //Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY l.id DESC";
        }



        $query = "SELECT l.id, l.lastname, c.country as topcountryname"
                . " FROM {$this->db['lastnames']} l"
                . " INNER JOIN {$this->db['fs_country']} c ON c.id=l.topcountry"
                . $and_orderby . $limit;


        $result = $this->db_results($query);
        return $result;
    }

    public function get_posts_count() {
        $sql = "SELECT COUNT(id) FROM {$this->db['lastnames']}";
        $cnt = $this->db_get_var($sql);
        return $cnt;
    }

    public function get_lastname_id($name = '') {
        $sql = sprintf("SELECT id FROM {$this->db['lastnames']} WHERE lastname='%s' limit 1", $this->escape($name));
        $id = $this->db_get_var($sql);
        return $id;
    }

    public function create_lastname($name = '', $country = 0) {
        $sql = sprintf("INSERT INTO {$this->db['lastnames']} (lastname,topcountry) VALUES ('%s',%d)", $this->escape($name), $country);
        $this->db_query($sql);
        //Get the id
        $id = $this->getInsertId('id', $this->db['lastnames']);
        return $id;
    }

    public function add_country_meta($last_name_id = 0, $c_id = 0, $t = 0) {
        $sql = sprintf("INSERT INTO {$this->db['meta_fs']} (nid,cid,ccount) VALUES (%d,%d,%d)", $last_name_id, $c_id, $t);
        $this->db_query($sql);
    }

    public function get_or_create_country($name = '') {
        //Get from cache
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }

        if (isset($dict[$name])) {
            return $dict[$name];
        }

        //Get author id
        $sql = sprintf("SELECT id FROM {$this->db['fs_country']} WHERE country='%s'", $this->escape($name));
        $id = $this->db_get_var($sql);

        if (!$id) {
            $id = $this->create_country_by_name($name);
        }
        // Add to cache
        $dict[$name] = $id;

        return $id;
    }

    public function get_countries_by_lasnameid($lastname_id) {
        $sql = sprintf("SELECT m.ccount, c.country FROM {$this->db['meta_fs']} m"
                . " INNER JOIN {$this->db['fs_country']} c ON c.id=m.cid"
                . " WHERE nid=%d", (int) $lastname_id);
        $results = $this->db_results($sql);
        $ret = array();
        if ($results) {
            foreach ($results as $item) {
                $ret[$item->country] = $item->ccount;
            }
        }
        arsort($ret);
        return $ret;
    }

    public function get_country_name($id) {
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }

        if (isset($dict[$id])) {
            return $dict[$id];
        }

        //Get author id
        $sql = sprintf("SELECT country FROM {$this->db['fs_country']} WHERE id=%d", (int) $id);
        $country = $this->db_get_var($sql);

        // Add to cache
        $dict[$id] = $country;

        return $country;
    }

    public function create_country_by_name($name) {
        $sql = sprintf("INSERT INTO {$this->db['fs_country']} (country) VALUES ('%s')", $this->escape($name));
        $this->db_query($sql);
        //Get the id
        $id = $this->getInsertId('id', $this->db['fs_country']);
        return $id;
    }

    public function get_country_races($country, $count) {
        $population = $this->get_population();
        $ret = array();
        if (isset($population[$country])){
            foreach ($population[$country] as $race => $percent) {
                $ret[$race]=round(($percent*$count)/100,0);
            }
        }
        return $ret;
    }

    public function get_population() {
        static $population;
        if ($population) {
            return $population;
        }

        $ret = array();
        $sql = "SELECT country_name, ethnic_array_result FROM {$this->db['population']}";
        $results = $this->db_results($sql);
        if ($results){
            foreach ($results as $item) {
                $ret[$item->country_name]= json_decode($item->ethnic_array_result);
            }
        }
        $population = $ret;
        return $population;
    }

}
