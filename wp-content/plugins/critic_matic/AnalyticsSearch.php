<?php

/**
 * Analytics Search
 *
 * @author brahman
 */
class AnalyticsSearch extends CriticSearch {

    private $max_matches = 100000;
    public $budget_min = 100;
    public $budget_max = 500000;
    public $analytics_filters = array(
        'current' => array('key' => 'year_int', 'name_pre' => 'Current ', 'filter_pre' => 'Current select'),
        'showcast' => array('key' => 'showcast', 'name_pre' => 'Show cast ', 'filter_pre' => 'Show cast'),
    );
    public $facet_data_extend = array(
        'international' => array(
            'title' => 'Box office',
            'tabs' => array('international'),
            'is_parent' => 1,
            'weight' => 20,
            'childs' => array(
                'setup' => array('title' => 'Setup'),
            ),
        ),
        'ethnicity' => array(
            'title' => 'Ethnicity',
            'tabs' => array('ethnicity'),
            'is_parent' => 1,
            'weight' => 20,
            'childs' => array(
                'vis' => array('title' => 'Visualization'),
                'xaxis' => array('title' => 'X-axis'),
                'yaxis' => array('title' => 'Y-axis'),
                'showcast' => array('title' => 'Show cast'),
                'setup' => array('title' => 'Setup'),
                'verdict' => array('title' => 'Race verdict mode'),
                'priority' => array('title' => 'Priority'),
                'weight' => array('title' => 'Weight')
            ),
        ),
        'year' => array(
            'title' => 'Year',
            'tabs' => array('population', 'worldmap'),
            'weight' => 20,
        ),
        'movie' => array(
            'title' => 'Movies',
            'tabs' => array('international', 'ethnicity'),
            'weight' => 30,
        ),
    );

    public function __construct($cm) {
        parent::__construct($cm);
        $this->search_filters = array_merge($this->search_filters, $this->analytics_filters);
        foreach ($this->facet_data_extend as $key => $facet) {
            $this->init_filters($key, $facet);
        }
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
        $ret = parent::get_order_query($sort);
        return $ret;
    }

    public function front_search_international($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = true, $show_meta = true, $widlcard = true, $show_main = true, $show_boxusa = true) {

        // Keywords logic
        $match = '';
        if ($keyword) {
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,year) (%s)'", $search_keywords);
            $match = " AND MATCH(:match)";
        }

        // Main logic
        $this->connect();
        gmi('search connect');

        if ($show_main) {
            // Sort logic
            $order = $this->get_order_query($sort);

            // Filters logic
            $filters['boxworldtrue'] = 1;
            $filters['yearinttrue'] = 1;
            $filters_and = $this->get_filters_query($filters, array(), 'movies', array('current'));

            // Main sql
            $sql = sprintf("SELECT id, title, release, add_time, post_name, type, boxusa, boxworld, boxint, (boxusa/boxworld) AS share, budget, year_int as year, weight() w" . $order['select']
                    . " FROM movie_an WHERE id>0" . $filters_and . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

            $ret = $this->movie_results($sql, $match, $search_query);
            gmi('main sql');
            if (!$show_meta) {
                return $ret;
            }
        }
        // Facets logic
        $facets_arr = array();

        if ($facets) {

            $facets_arr = $this->movies_facets($filters, $match, $search_query, $facets, 'international');

            if ($show_boxusa) {
                // International facet
                $facet = 'international';

                $filters_and = $this->get_filters_query($filters);
                $sql = sprintf("SELECT boxusa as box_usa, boxworld as box_world, year_int as year"
                        . " FROM movie_an WHERE id>0" . $filters_and . $match . " ORDER BY year_int ASC LIMIT 0,%d OPTION max_matches=%d", $this->max_matches, $this->max_matches);

                $international_facet = $this->movies_facet_single_get($sql, $search_query);
                $facets_arr[$facet]['data'] = $international_facet['data'];
                $facets_arr[$facet]['meta'] = $international_facet['meta'];
            }
        }

        $ret['facets'] = $facets_arr;
        return $ret;
    }

    public function front_search_ethnicity($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $ids = array(), $vis = '', $diversity = '', $xaxis = '', $yaxis = '', $facets = true, $show_meta = true, $widlcard = true, $show_main = true, $show_ethnicity = true) {
        // UNUSED
        // Keywords logic
        $match = '';
        if ($keyword) {
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,year) (%s)'", $search_keywords);
            $match = " AND MATCH(:match)";
        }

        // Main logic
        $this->connect();
        gmi('search connect');

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
            $select_and = ", boxint as xdata";
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

        if ($show_main) {
            // Main sql
            $sql = sprintf("SELECT id, title, release, add_time, post_name, type, boxusa, boxworld, boxint, boxprofit, budget, (boxusa/boxworld) AS share, "
                    . "year_int as year, raceu, draceu, rimdb, rrwt, rrt, rrta, rrtg, rating, aurating, weight() w" . $order['select']
                    . " FROM movie_an WHERE id>0" . $filters_and . $filters_need . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

            $ret = $this->movie_results($sql, $match, $search_query);

            gmi('main find query');

            if (!$facets) {
                return $ret;
            }
        }

        // Facets logic      
        $facets_arr = array();
        if ($facets) {
            if (!$ids) {
                $facets_arr = $this->movies_facets($filters, $match, $search_query, $facets, 'ethnicity');
                gmi('facets query');
            }

            if ($show_ethnicity) {
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
            }
        }
        $ret['facets'] = $facets_arr;

        return $ret;
    }

    public function front_search_ethnicity_xy($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $ids = array(), $vis = '', $diversity = '', $xaxis = '', $yaxis = '', $facets = true, $show_meta = true, $widlcard = true, $show_main = true, $show_ethnicity = true) {

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
        $xdata = $this->get_select_and($xaxis, $filters);
        $select_and[$xdata['select']] = 1;
        $filters_need[$xdata['filters']] = 1;

        // X-axis
        $ydata = $this->get_select_and($yaxis, $filters);
        $select_and[$ydata['select']] = 1;
        $filters_need[$ydata['filters']] = 1;

        $select_and_str = implode('', array_keys($select_and));
        $filters_need_str = implode('', array_keys($filters_need));

        if ($show_main) {
            // Main sql
            $sql = sprintf("SELECT id, title, release, add_time, post_name, type, boxusa, boxworld, boxint, boxprofit, budget, (boxusa/boxworld) AS share, "
                    . "year_int as year, raceu, draceu, rimdb, rrwt, rrt, rrta, rrtg, rating, aurating, weight() w" . $order['select']
                    . " FROM movie_an WHERE id>0" . $filters_and . $filters_need_str . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

            $ret = $this->movie_results($sql, $match, $search_query);

            gmi('main find query');

            if (!$show_meta) {
                return $ret;
            }
        }

        $facets_arr = array();
        // Facets logic      
        if ($facets) {
            if (!$ids) {
                $facets_arr = $this->movies_facets($filters, $match, $search_query, $facets, 'ethnicity');
                gmi('facets query');
            }

            if ($show_ethnicity) {
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
            }
        }
        $ret['facets'] = $facets_arr;

        return $ret;
    }

    public function get_movie_races($ids) {
        $sql = sprintf("SELECT id, title, year_int as year, raceu, draceu FROM movie_an WHERE id IN (%s) LIMIT 1000", implode(',', $ids));
        $result = $this->sdb_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $m) {
                $ret[$m->id] = $m;
            }
        }
        return $ret;
    }

    public function get_select_and($axis = '', $filters_enabled = array()) {
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
            $select = ", boxint";
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

            if ($filters_enabled['showcast']) {
                if (is_array($filters_enabled['showcast'])) {
                    if (in_array(4, $filters_enabled['showcast'])) {
                        $prod = true;
                    }
                } else {
                    if ($filters_enabled['showcast'] == 4) {
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

    public function movies_facets($filters, $match, $search_query, $facets = array(), $tab = 'movies') {
        // All facets
        $parent_arr = parent::movies_facets($filters, $match, $search_query, $facets, $tab);
        return $parent_arr;
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

}
