<?php

class MoviesSearch extends MoviesAbstractDB {

    private $sps;

    public function __construct($ml) {
        $this->ml = $ml ? $ml : new MoviesLinks();
    }

    private function connect() {
        if ($this->sps) {
            return $this->sps;
        }
        try {
            $this->sps = new PDO("mysql:host=" . SPHINX_SEARCH_HOST . ";dbname=''");
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
        return $this->sps;
    }

    public function search_movies_by_title($keyword = '', $match_title_type = 'm', $year = '', $limit = 20, $type = 'Movie') {

        //Title logic
        if ($match_title_type == 'm') {
            $title_query = sprintf("'@(title) (%s)'", $keyword);
            $match_title = " AND MATCH(:matchtitle)";
        } else {
            $title_query = $keyword;
            $match_title = " AND title=:matchtitle";
        }

        //Year logic
        $match_year = '';
        if ($year) {
            $match_year = sprintf(" AND year='%s'", $year);
        }


        $type_and = '';
        if ($type) {
            $type_and = sprintf(" AND type='%s'", $type);
        }

        $this->connect();

        // Main sql
        $sql = sprintf("SELECT id, title, release, year, runtime, movie_id, tmdb_id, type, weight() w"
                . " FROM movie_an WHERE id>0" . $match_title . $match_year . $type_and . " LIMIT %d ", $limit);
        

        //Get result
        $stmt = $this->sps->prepare($sql);

        if ($match_title) {
            $stmt->bindValue(':matchtitle', $title_query, PDO::PARAM_STR);
        }

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_OBJ);

        $ret = array();
        if ($result) {
            foreach ($result as $item) {
                $ret[$item->id] = $item;
            }
        }

        return $ret;
    }

    public function search_movies_by_id($keyword = '') {
        // Main sql
        $sql = sprintf("SELECT id, title, release, year, runtime, movie_id, tmdb_id, type, weight() w"
                . " FROM movie_an WHERE id=%d", (int) $keyword);
        $result = $this->sdb_results($sql);

        $ret = array();
        if ($result) {
            foreach ($result as $item) {
                $ret[$item->id] = $item;
            }
        }
        return $ret;
    }
    
    public function search_movies_by_imdb($keyword = '') {
        // Main sql
        $sql = sprintf("SELECT id, title, release, year, runtime, movie_id, tmdb_id, type, weight() w"
                . " FROM movie_an WHERE movie_id=%d", (int) $keyword);
        $result = $this->sdb_results($sql);

        $ret = array();
        if ($result) {
            foreach ($result as $item) {
                $ret[$item->id] = $item;
            }
        }
        return $ret;
    }

    public function search_movies_by_tmdb($keyword = '') {
        // Main sql
        $sql = sprintf("SELECT id, title, release, year, runtime, movie_id, tmdb_id, type, weight() w"
                . " FROM movie_an WHERE tmdb_id=%d", (int) $keyword);
        $result = $this->sdb_results($sql);

        $ret = array();
        if ($result) {
            foreach ($result as $item) {
                $ret[$item->id] = $item;
            }
        }
        return $ret;
    }

    public function get_movie_facets($mid=0) {
        if ($mid<=0){
            return array();
        }       
        // Facets logic
        $sql_arr = array();
        $facets_arr = array();

        $facets = array('actor', 'director', 'genre');

        foreach ($facets as $facet) {
            if ($facet == 'actor') {
                $limit = 100;
                $sql_arr[] = sprintf("SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id=%d GROUP BY actor_all ORDER BY cnt DESC LIMIT 0,%d", $mid, $limit);
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'director') {
                $limit = 100;
                $sql_arr[] = sprintf("SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id=%d GROUP BY director_all ORDER BY cnt DESC LIMIT 0,%d", $mid, $limit);
                $sql_arr[] = "SHOW META";
            }else if ($facet == 'genre') {
                $limit = 100;
                $sql_arr[] = sprintf("SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id=%d GROUP BY genre ORDER BY cnt DESC LIMIT 0,%d", $mid, $limit);
                $sql_arr[] = "SHOW META";
            }            
        }        

        if (sizeof($sql_arr)) {
            $sql = implode('; ', $sql_arr);
            
            $this->connect();

            $this->sps->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
            $stmt = $this->sps->prepare($sql);
            $stmt->execute();
            $rows = array();
            do {
                $rows[] = $stmt->fetchAll(PDO::FETCH_OBJ);
            } while ($stmt->nextRowset());

            $i = 0;
            foreach ($facets as $facet) {
                if ($rows[$i] && $rows[$i + 1]) {
                    $facets_arr[$facet]['data'] = $rows[$i];
                    $facets_arr[$facet]['meta'] = $rows[$i + 1];
                }
                $i += 2;
            }
        }
        return $facets_arr;
    }

    public function find_actors($keyword, $ids = array(), $from_actor = true) {

        $ids_and = '';
        if (sizeof($ids)) {
            $ids_and = " AND actor_id IN (" . implode(',', $ids) . ")";
        }

        $match_query = sprintf("'%s'", $keyword);
        $match = " AND MATCH(:match)";

        $this->connect();

        $from = 'actor_all';
        if (!$from_actor) {
            $from = 'director_all';
        }

        $sql = sprintf("SELECT actor_id, name FROM " . $from . " WHERE actor_id>0" . $ids_and . $match . ' LIMIT 1', $keyword);

        //Get result
        $stmt = $this->sps->prepare($sql);
        $stmt->bindValue(':match', $match_query, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);

        $results = array();
        if (sizeof($result)) {
            foreach ($result as $item) {
                $results[$item->actor_id] = $item->name;
            }
        }
        return $results;
    }


 
    //Abstract DB
    public function sdb_query($sql) {
        $this->connect();
        $this->sps->query($sql);
    }

    public function sdb_results($sql, $array = []) {
        $this->connect();
        $sth = $this->sps->prepare($sql);
        $sth->execute($array);
        $data = $sth->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function sdb_multi_results($sql, $array = []) {
        $this->connect();
        $sth = $this->sps->prepare($sql);
        $sth->execute($array);
        do {
            $data[] = $sth->fetchAll(PDO::FETCH_OBJ);
        } while ($sth->nextRowset());
        return $data;
    }

}
