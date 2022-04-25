<?php

/*
 * TODO 
 * for max results
 * OPTION max_matches=%d
 */

/**
 * Analytics Search
 *
 * @author brahman
 */
class AnalyticsSearch extends CriticSearch {

    private $sps;
    private $max_matches = 100000;
    public $budget_min = 100;
    public $budget_max = 500000;
    public $analytics_filters = array(
        'current' => array('key' => 'year_int', 'name_pre' => 'Current ', 'filter_pre' => 'Current select'),
        'showcast' => array('key' => 'showcast', 'name_pre' => 'Show cast ', 'filter_pre' => 'Show cast'),
        'movie' => array('key' => 'id', 'name_pre' => 'Movie ', 'filter_pre' => 'Movie'),
    );
    public $facets_extend = array(
        'movies' => array('budget', 'movie'),
    );

    public function __construct($cm) {
        parent::__construct($cm);
        $this->search_filters = array_merge($this->search_filters, $this->analytics_filters);
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

    public function get_filters_query($filters = array(), $exlude = array(), $query_type = 'movies', $include = array()) {
        $filters_and = parent::get_filters_query($filters, $exlude, $query_type);

        if (isset($filters['yearintrue'])) {
            $filters_and .= ' AND year_int>0';
        }

        if (isset($filters['boxworldtrue'])) {
            $filters_and .= ' AND boxworld > 0';
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

                if ($key == 'current' && in_array($key, $include)) {
                    $current = $this->get_current_type($value);
                    // Select year  
                    if ($current['type'] == 'y') {
                        $filters_and .= sprintf(" AND year_int=%d", (int) $current['value']);
                    } if ($current['type'] == 'm') {
                        $filters_and .= sprintf(" AND id=%d", (int) $current['value']);
                    }
                } else if ($key == 'budget') {
                    // Release
                    $data_arr = explode('-', $value);
                    $from = ((int) $data_arr[0]) * 1000;
                    $to = ((int) $data_arr[1]) * 1000;
                    $budget_min = $this->budget_min * 1000;
                    $budget_max = $this->budget_max * 1000;

                    if ($from == $to) {
                        if ($from == $budget_min) {
                            $filters_and .= sprintf(" AND budget > 0 AND budget<=%d", $from);
                        } else if ($from == $budget_max) {
                            $filters_and .= sprintf(" AND budget>=%d", $to);
                        } else {
                            $filters_and .= sprintf(" AND budget=%d", $from);
                        }
                    } else {
                        if ($from == $budget_min && $to == $budget_max) {
                            $filters_and .= " AND budget > 0";
                        } else if ($from == $budget_min) {
                            $filters_and .= sprintf(" AND budget > 0 AND budget < %d", $to);
                        } else if ($to == $budget_max) {
                            $filters_and .= sprintf(" AND budget >= %d", $from);
                        } else {
                            $filters_and .= sprintf(" AND budget >=%d AND budget < %d", $from, $to);
                        }
                    }
                } else if ($key == 'movie') {
                    // Movie                 
                    $value = is_array($value) ? $value : array($value);
                    $names = $this->get_movie_names($value);

                    foreach ($value as $id) {
                        $this->search_filters[$key][$id] = array('key' => $id, 'title' => $names[$id]);
                    }
                    $filters_and .= $this->filter_multi_value('id', $value);
                }
            }
        }

        return $filters_and;
    }

    public function get_order_query($sort = array()) {
        //Sort logic
        $order = '';
        $select = '';
        if ($sort) {
            /*
             * key: 'title', 'rating', 'date', 'rel'             
             * type: desc, asc
             */
            $sort_key = $sort['sort'];
            $sort_type = $sort['type'] == 'desc' ? 'DESC' : 'ASC';

            $box_keys = array('boxworld', 'boxusa', 'boxint', 'budget', 'share', 'boxprofit');

            $rating_facets = array_keys($this->rating_facets);
            $rating_facets[] = 'aurating';

            if ($sort_key == 'title') {
                $order = ' ORDER BY title ' . $sort_type;
            } else if ($sort_key == 'date') {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY year_int ' . $sort_type . ', release_ts ' . $sort_type;
                } else {
                    $order = ' ORDER BY year_int_valid ASC';
                    $select = ', IF(year_int>0, year_int, 9999) as year_int_valid';
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
                    $select = ', IF(diversity>0, diversity, 999) as diversity_valid';
                }
            } else if ($sort_key == 'fem') {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY female DESC';
                } else {
                    $order = ' ORDER BY female_valid ASC';
                    $select = ', IF(female>0, female, 999) as female_valid';
                }
            } else if (in_array($sort_key, $box_keys) || in_array($sort_key, $rating_facets)) {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY ' . $sort_key . ' DESC';
                } else {
                    $order = ' ORDER BY ' . $sort_key . '_valid ASC';
                    $select = ', IF(' . $sort_key . '>0, ' . $sort_key . ', 999) as ' . $sort_key . '_valid';
                }
            }
        } else {
            // Default weight
            $order = ' ORDER BY w DESC';
        }
        return array('order' => $order, 'select' => $select);
    }

    public function front_search_international($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = true) {
        $widlcard = true;

        // Keywords logic
        $match = '';
        if ($keyword) {
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,year) (%s)'", $search_keywords);
            $match = " AND MATCH(:match)";
        }

        // Main logic
        $this->connect();

        // Sort logic
        $order = $this->get_order_query($sort);

        // Filters logic
        $filters['boxworldtrue'] = 1;
        $filters['yearinttrue'] = 1;
        $filters_and = $this->get_filters_query($filters, array(), 'movies', array('current'));

        // Main sql
        $sql = sprintf("SELECT id, title, release, add_time, post_name, type, boxusa, boxworld, (boxworld-boxusa) AS boxint, (boxusa/boxworld) AS share, budget, year_int as year, weight() w" . $order['select']
                . " FROM movie_an WHERE id>0" . $filters_and . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

        $ret = $this->movie_results($sql, $match, $search_query);
        if (!$facets) {
            return $ret;
        }

        // Facets logic        
        $facets_arr = $this->movies_facets($filters, $match, $search_query);

        // International facet
        $facet = 'international';

        $filters_and = $this->get_filters_query($filters);
        $sql = sprintf("SELECT boxusa as box_usa, boxworld as box_world, year_int as year"
                . " FROM movie_an WHERE id>0" . $filters_and . $match . " ORDER BY year_int ASC LIMIT 0,%d OPTION max_matches=%d", $this->max_matches, $this->max_matches);

        $international_facet = $this->movies_facet_single_get($sql, $search_query);
        $facets_arr[$facet]['data'] = $international_facet['data'];
        $facets_arr[$facet]['meta'] = $international_facet['meta'];

        $ret['facets'] = $facets_arr;
        return $ret;
    }

    public function front_search_ethnicity($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $ids = array(), $vis = '', $diversity = '', $xaxis = '', $yaxis = '', $facets = true) {
        $widlcard = true;

        // Keywords logic
        $match = '';
        if ($keyword) {
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,year) (%s)'", $search_keywords);
            $match = " AND MATCH(:match)";
        }

        // Main logic
        $this->connect();

        // Sort logic
        $order = $this->get_order_query($sort);

        // Filters logic

        $filters['yearintrue'] = 1;
        $filters_and = $this->get_filters_query($filters, array(), 'movies', array('current'));

        // Ids logic
        if ($ids) {
            $filters_and .= ' AND id IN(' . implode(',', $ids) . ')';
        }

        // Select facet and filters logic
        $select = " id, year_int as year, title, raceu, draceu, boxusa as box_usa, boxworld as box_world";
        $select_and = '';
        $filters_need = '';
        if (!$xaxis) {
            $select_and = ", boxworld as xdata";
            $filters_need .= ' AND boxworld > 0';
        } else if ($xaxis == 'boxdom') {
            $select_and = ", boxusa as xdata";
            $filters_need .= ' AND boxworld > 0';
        } else if ($xaxis == 'boxint') {
            $select_and = ", (boxworld - boxusa) as xdata";
            $filters_need .= ' AND boxworld > 0';
        } else if ($xaxis == 'boxprofit') {
            $select_and = ", boxprofit as xdata";
            $filters_need .= ' AND boxworld > 0 AND budget>0';
        } else if ($xaxis == 'budget') {
            $select_and = ", budget as xdata";
            $filters_need .= ' AND budget > 0';
        } else if ($xaxis == 'release') {
            $select_and = ", release as xdata";
        } else if ($xaxis == 'rimdb') {
            $select_and = ", rimdb as xdata";
            $filters_need .= ' AND rimdb > 0';
        } else if ($xaxis == 'rrwt') {
            $select_and = ", rrwt as xdata";
            $filters_need .= ' AND rrwt > 0';
        } else if ($xaxis == 'rrt') {
            $select_and = ", rrt as xdata";
            $filters_need .= ' AND rrt > 0';
        } else if ($xaxis == 'rrta') {
            $select_and = ", rrta as xdata";
            $filters_need .= ' AND rrta > 0';
        } else if ($xaxis == 'rrtg') {
            $select_and = ", rrtg as xdata";
            $filters_need .= ' AND rrta > 0';
        } else if ($xaxis == 'rating') {
            $select_and = ", rating as xdata";
            $filters_need .= ' AND rating > 0';
        } else if ($xaxis == 'aurating') {
            $select_and = ", aurating as xdata";
            $filters_need .= ' AND aurating > 0';
        } else if ($xaxis == 'actors') {
            $select_and = ", 1 as xdata";
        }

        // Main sql
        $sql = sprintf("SELECT id, title, release, add_time, post_name, type, boxusa, boxworld, (boxworld - boxusa) AS boxint, boxprofit, budget, (boxusa/boxworld) AS share, "
                . "year_int as year, raceu, draceu, rimdb, rrwt, rrt, rrta, rrtg, rating, aurating, weight() w" . $order['select']
                . " FROM movie_an WHERE id>0" . $filters_and . $filters_need . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

        $ret = $this->movie_results($sql, $match, $search_query);

        gmi('main find query');

        if (!$facets) {
            return $ret;
        }
        // Facets logic      
        if (!$ids) {
            $facets_arr = $this->movies_facets($filters, $match, $search_query);
            gmi('facets query');
        }

        // ethnicity
        $facet = 'ethnicity';
        // Filters
        $filters_and = $this->get_filters_query($filters, array('current'));
        // Ids facets logic
        if ($ids) {
            $filters_and .= ' AND id IN(' . implode(',', $ids) . ')';
        }
        $sql = sprintf("SELECT " . $select . $select_and . " FROM movie_an WHERE id>0" . $filters_and . $filters_need . $match . " ORDER BY year_int ASC LIMIT 0,%d OPTION max_matches=%d", $this->max_matches, $this->max_matches);

        $ethnicity_facet = $this->movies_facet_single_get($sql, $search_query);

        gmi('page facet query');

        $facets_arr[$facet]['data'] = $ethnicity_facet['data'];
        $facets_arr[$facet]['meta'] = $ethnicity_facet['meta'];
        $ret['facets'] = $facets_arr;

        return $ret;
    }

    public function front_search_ethnicity_xy($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $ids = array(), $vis = '', $diversity = '', $xaxis = '', $yaxis = '', $facets = true) {
        $widlcard = true;

        // Keywords logic
        $match = '';
        if ($keyword) {
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,year) (%s)'", $search_keywords);
            $match = " AND MATCH(:match)";
        }

        // Main logic
        $this->connect();

        // Sort logic
        $order = $this->get_order_query($sort);

        // Filters logic

        $filters['yearintrue'] = 1;
        $filters_and = $this->get_filters_query($filters, array(), 'movies', array('current'));

        // Ids logic
        if ($ids) {
            $filters_and .= ' AND id IN(' . implode(',', $ids) . ')';
        }

        // Select facet and filters logic
        $select = " id, year_int as year, title";

        $select_and = array();
        $filters_need = array();
        // X-axis
        $xdata = $this->get_select_and($xaxis,$filters);
        $select_and[$xdata['select']] = 1;
        $filters_need[$xdata['filters']] = 1;

        // X-axis
        $ydata = $this->get_select_and($yaxis,$filters);
        $select_and[$ydata['select']] = 1;
        $filters_need[$ydata['filters']] = 1;

        $select_and_str = implode('', array_keys($select_and));
        $filters_need_str = implode('', array_keys($filters_need));

        // Main sql
        $sql = sprintf("SELECT id, title, release, add_time, post_name, type, boxusa, boxworld, (boxworld - boxusa) AS boxint, boxprofit, budget, (boxusa/boxworld) AS share, "
                . "year_int as year, raceu, draceu, rimdb, rrwt, rrt, rrta, rrtg, rating, aurating, weight() w" . $order['select']
                . " FROM movie_an WHERE id>0" . $filters_and . $filters_need_str . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

        $ret = $this->movie_results($sql, $match, $search_query);

        gmi('main find query');

        if (!$facets) {
            return $ret;
        }

        $facets_arr = array();
        // Facets logic      
        if (!$ids) {
            $facets_arr = $this->movies_facets($filters, $match, $search_query);
            gmi('facets query');
        }

        // ethnicity
        $facet = 'ethnicity';
        // Filters
        $filters_and = $this->get_filters_query($filters, array('current'));
        // Ids facets logic
        if ($ids) {
            $filters_and .= ' AND id IN(' . implode(',', $ids) . ')';
        }
        $sql = sprintf("SELECT " . $select . $select_and_str . " FROM movie_an WHERE id>0" . $filters_and . $filters_need_str . $match . " ORDER BY year_int ASC LIMIT 0,%d OPTION max_matches=%d", $this->max_matches, $this->max_matches);

        $ethnicity_facet = $this->movies_facet_single_get($sql, $search_query);

        gmi('page facet query');

        $facets_arr[$facet]['data'] = $ethnicity_facet['data'];
        $facets_arr[$facet]['meta'] = $ethnicity_facet['meta'];
        $ret['facets'] = $facets_arr;

        return $ret;
    }

    public function get_select_and($axis = '',$filters_enabled=array()) {
        $select = '';
        $filters = '';

        if ($axis == 'boxworld') {
            // Box axis
            $select = ", boxworld";
            $filters .= ' AND boxworld > 0';
        } else if ($axis == 'boxdom') {
            $select = ", boxusa";
            $filters .= ' AND boxworld > 0';
        } else if ($axis == 'boxint') {
            $select = ", (boxworld - boxusa) as boxint";
            $filters .= ' AND boxworld > 0';
        } else if ($axis == 'boxprofit') {
            $select = ", boxprofit";
            $filters .= ' AND boxworld > 0 AND budget>0';
        } else if ($axis == 'budget') {
            $select = ", budget";
            $filters .= ' AND budget > 0';
        } else if ($axis == 'release') {
            // Date axis
            $select = ", release";
        } else if ($axis == 'rimdb') {
            // Rating axis
            $select = ", rimdb as rating";
            $filters .= ' AND rimdb > 0';
        } else if ($axis == 'rrwt') {
            $select = ", rrwt as rating";
            $filters .= ' AND rrwt > 0';
        } else if ($axis == 'rrt') {
            $select = ", rrt as rating";
            $filters .= ' AND rrt > 0';
        } else if ($axis == 'rrta') {
            $select = ", rrta as rating";
            $filters .= ' AND rrta > 0';
        } else if ($axis == 'rrtg') {
            $select = ", rrtg as rating";
            $filters .= ' AND rrta > 0';
        } else if ($axis == 'rating') {
            $select = ", rating";
            $filters .= ' AND rating > 0';
        } else if ($axis == 'aurating') {
            $select = ", aurating as rating";
            $filters .= ' AND aurating > 0';
        } else if ($axis == 'actors' || $axis == 'simpson' || $axis == 'mf' || $axis == 'eth' || $axis == 'wjnw' || $axis == 'wjnwj' || $axis == 'wmjnwm' || $axis == 'wmjnwmj') {
            // Race axis
            // Cast filter
            $prod = false;

            if ($filters_enabled['showcast']){
                if (is_array($filters_enabled['showcast'])){
                    if (in_array(4, $filters_enabled['showcast'])){
                        $prod = true;
                    }
                } else {
                    if ($filters_enabled['showcast']==4){
                        $prod = true;
                    }
                }
            }

            $select = ", raceu";
            if ($prod) {
                // Enable production. Default disabled
                $select = ", raceu, draceu";
            }
        }

        return array('select' => $select, 'filters' => $filters);
    }

    public function movies_facets($filters, $match, $search_query) {
        // All facets
        $facet_list = $this->facets['movies'];
        $sql_arr_data = $this->movies_facets_sql($facet_list, $filters, $match);

        foreach ($this->facets_extend['movies'] as $facet) {
            if ($facet == 'budget') {
                $filters_and = $this->get_filters_query($filters, 'budget');
                $sql_arr_data['sql_arr'][] = "SELECT GROUPBY() as id, COUNT(*) as cnt, FLOOR(budget/100000)*100 as bgt FROM movie_an"
                        . " WHERE budget>0" . $filters_and . $match
                        . " GROUP BY bgt ORDER BY budget ASC LIMIT 0,1000";
                $sql_arr_data['sql_arr'][] = "SHOW META";
            } else if ($facet == 'movie') {

                $filters_and = $this->get_filters_query($filters, 'movie');
                $sql_arr_data['sql_arr'][] = "SELECT id, title, year_int as year FROM movie_an"
                        . " WHERE id>0" . $filters_and . $match
                        . " ORDER BY year DESC LIMIT 0,100";
                $sql_arr_data['sql_arr'][] = "SHOW META";
            }
        }

        $facet_list = array_merge($facet_list, $this->facets_extend['movies']);
        $facets_arr = $this->movies_facets_get($facet_list, $sql_arr_data, $match, $search_query);

        return $facets_arr;
    }

    public function movies_facets_get($facet_list, $data_arr, $match, $search_query) {
        $facets_arr = array();
        $sql_arr = $data_arr['sql_arr'];
        $skip = $data_arr['skip'];
        if (sizeof($sql_arr)) {

            $sql = implode('; ', $sql_arr);

            $this->sps->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);

            $stmt = $this->sps->prepare($sql);
            if ($match) {
                $stmt->bindValue(':match', $search_query, PDO::PARAM_STR);
            }
            $stmt->execute();
            $rows = array();
            do {
                $rows[] = $stmt->fetchAll(PDO::FETCH_OBJ);
            } while ($stmt->nextRowset());

            $i = 0;
            foreach ($facet_list as $facet) {
                if (in_array($facet, $skip)) {
                    continue;
                }
                if ($rows[$i] && $rows[$i + 1]) {
                    $facets_arr[$facet]['data'] = $rows[$i];
                    $facets_arr[$facet]['meta'] = $rows[$i + 1];
                }
                $i += 2;
            }
        }
        return $facets_arr;
    }

    public function movies_facet_single_get($sql, $search_query) {
        $stmt = $this->sps->prepare($sql);
        $stmt->bindValue(':match', $search_query, PDO::PARAM_STR);
        $stmt->execute();
        $value = $stmt->fetchAll(PDO::FETCH_OBJ);
        $meta = $this->sps->query("SHOW META")->fetchAll();

        return array('data' => $value, 'meta' => $meta);
    }

    public function movie_results($sql, $match, $search_query) {
        //Get result
        $stmt = $this->sps->prepare($sql);

        if ($match) {
            $stmt->bindValue(':match', $search_query, PDO::PARAM_STR);
        }

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Total found
        $meta = $this->sps->query("SHOW META")->fetchAll();

        $meta_map = array();
        foreach ($meta as $m) {
            $meta_map[$m['Variable_name']] = $m['Value'];
        }
        $total_found = $meta_map['total_found'];

        return array('list' => $result, 'count' => $total_found, 'meta' => $meta);
    }

    public function get_current_type($current = '') {
        $ret = array('type' => '', 'value' => '');
        $current = is_array($current) ? $current[0] : $current;
        if (preg_match('/^([a-z]{1})([0-9\w]+)$/', $current, $match)) {
            $type = $match[1];
            $value = $match[2];
            $ret = array('type' => $type, 'value' => $value);
        }
        return $ret;
    }

    public function get_budget_array() {
        $max_key = $this->budget_max;
        $karay = array();
        $k = $this->budget_min;
        while ($k < $max_key) {
            $karay[] = $k;
            $k = round($k * 1.2, 0);
            $klen = strlen('' . $k);
            $k = round($k / pow(10, $klen - 2), 0) * pow(10, $klen - 2);
        }
        $karay[] = $max_key;
        return $karay;
    }

    public function get_movie_names($ids) {
        $sql = sprintf("SELECT id, title, year_int as year FROM movie_an WHERE id IN (%s) LIMIT 1000", implode(',', $ids));
        $result = $this->sdb_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $m) {
                $ret[$m->id] = $m->title . ' (' . $m->year . ')';
            }
        }
        return $ret;
    }

}
