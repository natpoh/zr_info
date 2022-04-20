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
        $sql = sprintf("SELECT id, title, release, year, runtime, movie_id, tmdb_id, weight() w"
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

    public function search_movies_by_imdb($keyword = '') {
        // Main sql
        $sql = sprintf("SELECT id, title, release, year, runtime, movie_id, tmdb_id, weight() w"
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
        $sql = sprintf("SELECT id, title, release, year, runtime, movie_id, tmdb_id, weight() w"
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

    public function get_movie_facets($mid) {
        // Facets logic
        $sql_arr = array();
        $facets_arr = array();

        $facets = array('actor', 'director');

        foreach ($facets as $facet) {
            if ($facet == 'actor') {
                $limit = 100;
                $sql_arr[] = sprintf("SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id=%d GROUP BY actor ORDER BY cnt DESC LIMIT 0,%d", $mid, $limit);
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'director') {
                $limit = 100;
                $sql_arr[] = sprintf("SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id=%d GROUP BY director ORDER BY cnt DESC LIMIT 0,%d", $mid, $limit);
                $sql_arr[] = "SHOW META";
            }
        }

        if (sizeof($sql_arr)) {
            $sql = implode('; ', $sql_arr);

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

        $from = 'actor';
        if (!$from_actor) {
            $from = 'director';
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

    private function wildcards_maybe_query($keyword, $wildcars = true, $mode = ' MAYBE ') {
        $keyword = trim($keyword);

        $match_query = $keyword;
        if ($wildcars) {
            $match_query = "($keyword)|($keyword*)";
        }

        if (strstr($keyword, " ")) {
            // Multi keywords
            $keyword_arr = explode(' ', $keyword);
            $match_query_arr = array();
            foreach ($keyword_arr as $value) {
                if ($wildcars) {
                    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                        $value = "(($value)|($value*))";
                    }
                }
                $match_query_arr[] = $value;
            }
            $match_query = implode($mode, $match_query_arr);
        }
        return $match_query;
    }

    private function get_order_query($sort = array()) {
        //Sort logic
        $order = '';
        if ($sort) {
            /*
             * key: 'title', 'rating', 'date', 'rel'             
             * type: desc, asc
             */
            $sort_key = $sort['sort'];
            $sort_type = $sort['type'] == 'desc' ? 'DESC' : 'ASC';
            if ($sort_key == 'title') {
                $order = ' ORDER BY title ' . $sort_type;
            } else if ($sort_key == 'date') {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY year_int ' . $sort_type . ', release_ts ' . $sort_type;
                } else {
                    $order = ' ORDER BY year_int_valid ASC';
                }
            } else if ($sort_key == 'rel') {
                $order = ' ORDER BY w ' . $sort_type;
            } else if ($sort_key == 'rating') {
                $order = ' ORDER BY rating ' . $sort_type;
            } else if ($sort_key == 'div') {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY diversity DESC';
                } else {
                    $order = ' ORDER BY diversity_valid ASC';
                }
            } else if ($sort_key == 'fem') {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY female DESC';
                } else {
                    $order = ' ORDER BY female_valid ASC';
                }
            }
        } else {
            // Default weight
            $order = ' ORDER BY w DESC';
        }
        return $order;
    }

    private function get_filters_query($filters = array(), $exlude = array(), $query_type = 'movies') {
        // Filters logic
        $filters_and = '';
        if (!isset($filters['release'])) {
            $release = date('Y', time());
            $filters['release'] = '0-' . ($release + 1);
        }
        if (sizeof($filters)) {
            foreach ($filters as $key => $value) {
                if (is_array($exlude)) {
                    if (in_array($key, $exlude)) {
                        continue;
                    }
                } else if ($key == $exlude) {
                    continue;
                }

                $minus = false;
                if (strstr($key, 'minus-')) {
                    $key = str_replace('minus-', '', $key);
                    $minus = true;
                }

                if ($query_type == 'movies' || $query_type == 'critics') {
                    if ($key == 'genre') {
                        // Genre
                        $ma = $this->get_ma();
                        $value = is_array($value) ? $value : array($value);
                        foreach ($value as $slug) {
                            $genre = $ma->get_genre_by_slug($slug, true);
                            $this->search_filters[$key][$slug] = array('key' => $genre->id, 'title' => $genre->name);
                        }
                        $filters_and .= $this->filter_multi_value($key, $value, true);
                    } else if ($key == 'type') {
                        // Type
                        $filters_and .= $this->filter_multi_value('type', $value);
                    } else if ($key == 'release') {
                        // Release
                        $dates = explode('-', $value);
                        $release_from = (int) $dates[0];
                        $release_to = (int) $dates[1];
                        $filters_and .= sprintf(" AND year_int >=%d AND year_int < %d", $release_from, $release_to);
                    }
                }

                if ($query_type == 'movies') {
                    if ($key == 'provider') {
                        // Provider
                        $ma = $this->get_ma();
                        $value = is_array($value) ? $value : array($value);
                        foreach ($value as $slug) {
                            $prov = $ma->get_provider_by_slug($slug, true);
                            $this->search_filters[$key][$slug] = array('key' => $prov->pid, 'title' => $prov->name);
                        }
                        $filters_and .= $this->filter_multi_value($key, $value, true);
                    } else if ($key == 'price') {
                        // Provider price
                        $ma = $this->get_ma();
                        $pay_type = 1;
                        $list = $ma->get_providers_by_type($pay_type);
                        $filters_and .= $this->filter_multi_value('provider', $list, true);
                    } else if ($key == 'actor') {
                        // Actor                 
                        $value = is_array($value) ? $value : array($value);
                        $names = $this->get_actor_names($value);
                        foreach ($value as $id) {
                            $this->search_filters[$key][$id] = array('key' => $id, 'title' => $names[$id]);
                        }
                        $filters_and .= $this->filter_multi_value('actor', $value);
                    } else if ($key == 'rating') {
                        // Rating
                        $dates = explode('-', $value);
                        $from = (int) $dates[0];
                        $to = (int) $dates[1];
                        $filters_and .= sprintf(" AND rating >=%d AND rating < %d", $from, $to);
                    } else if ($key == 'country') {
                        // Country
                        $ma = $this->get_ma();
                        $value = is_array($value) ? $value : array($value);
                        foreach ($value as $slug) {
                            $country = $ma->get_country_by_slug($slug, true);
                            $this->search_filters[$key][$slug] = array('key' => $country->id, 'title' => $country->name);
                        }
                        $filters_and .= $this->filter_multi_value($key, $value, true);
                    } else if ($key == 'race' || isset($this->facets_race_cast[$key])) {
                        // Race 
                        $filters_and .= $this->filter_multi_value($key, $value, true, $minus);
                    } else if ($key == 'dirrace' || isset($this->facets_race_directors[$key])) {
                        // Race directors
                        $filters_and .= $this->filter_multi_value($key, $value, true, $minus);
                    } else if ($key == 'gender' || isset($this->facets_gender[$key])) {
                        // Gender 
                        $filters_and .= $this->filter_multi_value($key, $value, true, $minus);
                    } else if ($key == 'dirgender' || isset($this->facets_gender_dir[$key])) {
                        // Gender dirs
                        $filters_and .= $this->filter_multi_value($key, $value, true, $minus);
                    }
                } else if ($query_type == 'critics') {
                    if ($key == 'author') {
                        // Author
                        $filters_and .= $this->filter_multi_value('author_type', $value);
                    } else if ($key == 'from') {
                        // From author
                        $value = is_array($value) ? $value : array($value);
                        foreach ($value as $slug) {
                            // Todo get author by slug
                            $this->search_filters[$key][$slug] = array('key' => $slug, 'title' => $slug);
                        }
                        $filters_and .= $this->filter_multi_value('aid', $value, true);
                    } else if ($key == 'tags') {
                        // Tags                       
                        $value = is_array($value) ? $value : array($value);
                        foreach ($value as $slug) {
                            $tag = $this->cm->get_tag_by_slug($slug);
                            $this->search_filters[$key][$slug] = array('key' => $tag->id, 'title' => $tag->name);
                        }
                        $filters_and .= $this->filter_multi_value($key, $value, true);
                    } else if ($key == 'state') {
                        // Type
                        $filters_and .= $this->filter_multi_value('state', $value);
                    }
                }
            }
        }
        return $filters_and;
    }

    private function filter_multi_value($key, $value, $multi = false, $not = false, $any = true, $not_all = true) {
        $filters_and = '';
        $and = 'ANY';
        if (!$any) {
            $and = 'ALL';
        }

        $and_not = 'ALL';
        if (!$not_all) {
            $and_not = 'ANY';
        }

        if (is_array($value) && sizeof($value) == 1) {
            $value = $value[0];
        }

        if (is_array($value)) {
            $provider_valid_arr = array();
            foreach ($value as $item) {
                $filter = $this->get_search_filter($key, $item);
                if ($filter !== '') {
                    $provider_valid_arr[] = $filter;
                }
            }
            if (sizeof($provider_valid_arr)) {
                if (!$not) {
                    if ($multi) {
                        // https://sphinxsearch.com/bugs/view.php?id=2627
                        $filters_and .= sprintf(" AND $and(%s) IN (%s)", $key, implode(',', $provider_valid_arr));
                    } else {
                        $filters_and .= sprintf(" AND %s IN (%s)", $key, implode(',', $provider_valid_arr));
                    }
                } else {
                    // Filter not
                    if ($multi) {
                        foreach ($provider_valid_arr as $filter) {
                            $filters_and .= sprintf(" AND $and_not(%s)!=%s", $key, $filter);
                        }
                    } else {
                        foreach ($provider_valid_arr as $filter) {
                            $filters_and .= sprintf(" AND %s!=%s", $key, $filter);
                        }
                    }
                }
            }
        } else {
            $filter = $this->get_search_filter($key, $value);
            if ($filter !== '') {
                if (!$not) {
                    if ($multi) {
                        $filters_and .= sprintf(" AND $and(%s)=%s", $key, $filter);
                    } else {
                        $filters_and .= sprintf(" AND %s=%s", $key, $filter);
                    }
                } else {
                    // Filter not
                    if ($multi) {
                        $filters_and .= sprintf(" AND ALL(%s)!=%s", $key, $filter);
                    } else {
                        $filters_and .= sprintf(" AND %s!=%s", $key, $filter);
                    }
                }
            }
        }
        return $filters_and;
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
