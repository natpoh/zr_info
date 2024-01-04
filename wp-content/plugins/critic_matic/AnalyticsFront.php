<?php

/**
 * Description of AnalyticsFront
 *
 * @author brahman
 */
class AnalyticsFront extends SearchFacets {

    public $cm;
    public $cs;
    public $ma;
    public $search_url = '/analytics';
    public $search_limit = 20;
    public $claster_data = array();
    public $search_tabs = array(
        'international' => array('title' => 'Box Office', 'count' => 0), // international v.s. domestic
        //  'country' => array('title' => 'Box Office breakdown by country', 'count' => 0),
        'ethnicity' => array('title' => 'Data Visualization', 'count' => 0),
        'population' => array('title' => 'World population', 'count' => 0),
        'worldmap' => array('title' => 'Ethnic world map', 'count' => 0),
        'power' => array('title' => 'Buying power', 'count' => 0),
        'powerrace' => array('title' => 'Buying power by race', 'count' => 0),
    );
    public $an_search_sort = array(
        'international' => array(
            'title' => array('title' => 'Title', 'def' => 'asc', 'main' => 1),
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1),
            'boxworld' => array('title' => 'BoxWorld', 'def' => 'desc', 'main' => 1),
            'boxusa' => array('title' => 'BoxUSA', 'def' => 'desc', 'main' => 1),
            'boxint' => array('title' => 'BoxInt', 'def' => 'desc', 'main' => 1),
            'share' => array('title' => 'Share', 'def' => 'desc', 'main' => 1),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1),
        ),
        'ethnicity' => array(
            'title' => array('title' => 'Title', 'def' => 'asc', 'main' => 1),
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1),
            'boxworld' => array('title' => 'Box Office Worldwide', 'def' => 'desc', 'main' => 0),
            'boxusa' => array('title' => 'Box Office Domestic', 'def' => 'desc', 'main' => 0),
            'boxint' => array('title' => 'Box Office Internationally', 'def' => 'desc', 'main' => 0),
            'boxprofit' => array('title' => 'Box Office Profit', 'def' => 'desc', 'main' => 0),
            'budget' => array('title' => 'Budget', 'def' => 'desc', 'main' => 0),
            'share' => array('title' => 'Share', 'def' => 'desc', 'main' => 0),
            'fem' => array('title' => 'Female', 'def' => 'desc', 'main' => 0),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 0),
        ),
    );
    public $def_tab = 'international';
    public $xaxis_def = 'release';
    public $yaxis_def = 'eth';
    // Facets
    public $an_facets = array(
        'international' => array('release', 'budget', 'international', 'setup', 'movie', 'type', 'genre', 'provider', 'providerfree', 'auratings', 'ratings', 'price', 'race', 'dirrace', 'actor', 'country'),
        'ethnicity' => array('release', 'budget', 'ethnicity', 'movie', 'diversity', 'vis', 'xaxis', 'yaxis', 'setup', 'verdict', 'priority', 'weight', 'showcast', 'type', 'genre', 'provider', 'providerfree', 'auratings', 'ratings', 'price', 'race', 'dirrace', 'actor', 'country'),
        'population' => array('year'),
        'worldmap' => array('year'),
    );
    public $race_small = array(
        1 => array('key' => 'w', 'title' => 'White'),
        2 => array('key' => 'ea', 'title' => 'Asian'),
        3 => array('key' => 'h', 'title' => 'Latino'),
        4 => array('key' => 'b', 'title' => 'Black'),
        5 => array('key' => 'i', 'title' => 'Indian'),
        6 => array('key' => 'm', 'title' => 'Arab'),
        7 => array('key' => 'mix', 'title' => 'Mixed / Other'),
        8 => array('key' => 'jw', 'title' => 'Jewish'),
    );
    public $showcast = array(
        1 => 'Stars',
        2 => 'Supporting',
        3 => 'Other',
        4 => 'Production',
    );
    public $current = array(
        'y' => array('title' => 'year'),
        'm' => array('title' => 'movie'),
        'c' => array('title' => 'claster'),
    );
    public $diversity = array(
        'def' => array('title' => 'Default not present'),
        'simpson' => array('title' => 'Simpson\'s Diversity Index'),
        'simpsonmf' => array('title' => 'Simpson\'s Diversity Index plus Male vs Female'),
        'mf' => array('title' => 'Male v.s. Female'),
        'wjnw' => array('title' => 'White (+ Jews ) v.s. non-White'),
        'wjnwj' => array('title' => 'White (- Jews ) v.s. non-White (+ Jews)'),
        'wmjnwm' => array('title' => 'White Male (+ Jews ) v.s. non-White Males (+ Female Whites)'),
        'wmjnwmj' => array('title' => 'White Male (- Jews ) v.s. non-White Males (+ Jews + Female Whites)')
    );
    public $vis = array(
        'def' => array('title' => 'Columns'),
        'scatter' => array('title' => 'Scatter Chart'),
        /* 'bubble' => array('title' => 'Plurality Scatterplot'), */
        'regression' => array('title' => 'Regression line'),
        'bellcurve' => array('title' => 'Bell curve'),
        'plurbellcurve' => array('title' => 'Plurality Bell curve'),
        'scatter' => array('title' => 'Scatter Chart'),
        'line' => array('title' => 'Line Chart'),
            /* 'percountry' => array('title' => 'Average (performance metric) per country') */
    );
    public $axis = array(
        'boxworld' => array('name' => 'bow', 'title' => 'Box Office revenue worldwide', 'atitle' => 'Box Office', 'format' => 'usd', 'infl' => 1),
        'boxint' => array('name' => 'boi', 'title' => 'Box Office revenue internationally', 'atitle' => 'Box Office', 'format' => 'usd', 'infl' => 1),
        'boxdom' => array('name' => 'bod', 'title' => 'Box Office revenue domestic', 'atitle' => 'Box Office', 'format' => 'usd', 'infl' => 1),
        'boxprofit' => array('name' => 'bop', 'title' => 'Box Office revenue profit', 'atitle' => 'Box Office', 'format' => 'usd', 'min' => -1, 'infl' => 1, 'nte' => 'analytics_budget_popup'),
        'budget' => array('name' => 'budget', 'title' => 'Budget', 'atitle' => 'Budget', 'format' => 'usd', 'infl' => 1),
        /* 'dvddom' => array('title' => 'DVD Sales Domestic'), */
        'release' => array('name' => 'date', 'title' => 'Release Date', 'atitle' => 'Movies', 'type' => 'datetime', 'format' => 'date', 'min' => -1),
        'actors' => array('name' => 'actors', 'title' => 'Actors count', 'atitle' => 'Actors', 'races' => 1),
        'rrwt' => array('name' => 'rrwt', 'title' => 'Rating ZR', 'atitle' => 'Rating ZR'),
        'rating' => array('name' => 'rating', 'title' => 'Rating Family Friendly Score', 'atitle' => 'Rating FFS'),
        'aurating' => array('name' => 'aurating', 'title' => 'ZR Audience Score', 'atitle' => 'Rating WORTHWHILE'),
        'rimdb' => array('name' => 'rimdb', 'title' => 'Rating IMDB', 'atitle' => 'Rating IMDB'),
        'rrt' => array('name' => 'rrt', 'title' => 'Rating Rotten Tomatoes', 'atitle' => 'Rating RT'),
        'rrta' => array('name' => 'rrta', 'title' => 'Rating Rotten Tomatoes Audience', 'atitle' => 'Rating RTA'),
        'rrtg' => array('name' => 'rrtg', 'title' => 'Rating Rotten Tomatoes Gap', 'atitle' => 'Rating RTG', 'min' => -1),
        'simpson' => array('name' => 'simpson', 'title' => 'Simpson\'s Diversity Index', 'format' => 'percent', 'races' => 1),
        'simpsonmf' => array('name' => 'simpsonmf', 'title' => 'Simpson\'s Diversity Index plus Male vs Female', 'format' => 'percent', 'races' => 1),
        'mf' => array('name' => 'mf', 'title' => 'Male v.s. Female', 'format' => 'percent', 'max' => 100, 'races' => 1),
        'eth' => array('name' => 'eth', 'title' => 'Ethnicity', 'format' => 'percent', 'max' => 100, 'races' => 1),
        'ethmf' => array('name' => 'ethmf', 'title' => 'Ethnicity plus Male and Female', 'format' => 'percent', 'max' => 100, 'races' => 1),
        'wjnw' => array('name' => 'wjnw', 'title' => 'White (+ Jews ) v.s. non-White', 'format' => 'percent', 'max' => 100, 'races' => 1),
        'wjnwj' => array('name' => 'wjnwj', 'title' => 'White (- Jews ) v.s. non-White (+ Jews)', 'format' => 'percent', 'max' => 100, 'races' => 1),
        'wmjnwm' => array('name' => 'wmjnwm', 'title' => 'White Male (+ Jews ) v.s. non-White Males (+ Female Whites)', 'format' => 'percent', 'max' => 100, 'races' => 1),
        'wmjnwmj' => array('name' => 'wmjnwmj', 'title' => 'White Male (- Jews ) v.s. non-White Males (+ Jews + Female Whites)', 'format' => 'percent', 'max' => 100, 'races' => 1)
    );
    public $setup = array(
        'cwj' => array('title' => 'Combine White & Jew', 'tab' => 'ethnicity'),
        'noclasters' => array('title' => 'No Clusters', 'tab' => 'ethnicity'),
        'inflation' => array('title' => 'Adjust for inflation', 'tab' => 'all', 'note' => 'Our inflation adjustments are calculated using the official CPI records published by the U.S. Department of Labor.<br /><br />Source:<br /><a rel="nofollow" target="_blank" href="https://www.officialdata.org/us/inflation/">https://www.officialdata.org/us/inflation/</a>'),
    );
    public $array_ethnic_data = array(
        // Race
        'w' => array('color' => '#2b908f', 'title' => 'White'),
        'ea' => array('color' => '#90ee7e', 'title' => 'Asian'),
        'da' => array('color' => '#90ee7e', 'title' => 'Dark Asian'),
        'b' => array('color' => '#f45b5b', 'title' => 'Black'),
        'i' => array('color' => '#7798BF', 'title' => 'Indian'),
        'ii' => array('color' => '#aaeeee', 'title' => 'Indigenous'),
        'jw' => array('color' => '#ff0066', 'title' => 'Jewish'),
        'h' => array('color' => '#eeaaee', 'title' => 'Latino'),
        'mix' => array('color' => '#55BF3B', 'title' => 'Mixed / Other'),
        'm' => array('color' => '#DF5353', 'title' => 'Arab'),
        'm_w' => array('color' => '#146d6d', 'title' => 'White male'),
        'm_ea' => array('color' => '#50c33a', 'title' => 'Asian male'),
        'm_da' => array('color' => '#50c33a', 'title' => 'Dark Asian male'),
        'm_b' => array('color' => '#bc3131', 'title' => 'Black male'),
        'm_i' => array('color' => '#4b73a2', 'title' => 'Indian male'),
        'm_ii' => array('color' => '#63c5c5', 'title' => 'Indigenous male'),
        'm_jw' => array('color' => '#b9004a', 'title' => 'Jewish male'),
        'm_h' => array('color' => '#c865c8', 'title' => 'Latino male'),
        'm_mix' => array('color' => '#399722', 'title' => 'Mixed / Other male'),
        'm_m' => array('color' => '#aa5656', 'title' => 'Arab male'),
        'f_w' => array('color' => '#47adac', 'title' => 'White female'),
        'f_ea' => array('color' => '#90ee7e', 'title' => 'Asian female'),
        'f_da' => array('color' => '#90ee7e', 'title' => 'Dark Asian female'),
        'f_b' => array('color' => '#f45b5b', 'title' => 'Black female'),
        'f_i' => array('color' => '#7798BF', 'title' => 'Indian female'),
        'f_ii' => array('color' => '#aaeeee', 'title' => 'Indigenous female'),
        'f_jw' => array('color' => '#ff0066', 'title' => 'Jewish female'),
        'f_h' => array('color' => '#eeaaee', 'title' => 'Latino female'),
        'f_mix' => array('color' => '#55BF3B', 'title' => 'Mixed / Other female'),
        'f_m' => array('color' => '#DF5353', 'title' => 'Arab female'),
        // Race custom
        'wj' => array('color' => '#2b908f', 'title' => 'White (+ Jews )'),
        'nw' => array('color' => '#90ee7e', 'title' => 'non-White'),
        'w-j' => array('color' => '#2b908f', 'title' => 'White (- Jews )'),
        'nwj' => array('color' => '#90ee7e', 'title' => 'non-White (+ Jews)'),
        'wmj' => array('color' => '#2b908f', 'title' => 'White Male (+ Jews )'),
        'nwf' => array('color' => '#90ee7e', 'title' => 'non-Whites ( + Female Whites )'),
        'wm-j' => array('color' => '#2b908f', 'title' => 'White Male (- Jews )'),
        'nw' => array('color' => '#90ee7e', 'title' => 'non-Whites ( + Jews + Female Whites )'),
        // Male
        'male' => array('color' => '#2b908f', 'title' => 'Male'),
        'female' => array('color' => '#90ee7e', 'title' => 'Female'),
        // Box
        'boi' => array('color' => '#2b908f', 'title' => 'Box Office International'),
        'bod' => array('color' => '#90ee7e', 'title' => 'Box Office Domestic'),
        'bow' => array('color' => '#55BF3B', 'title' => 'Box Office World'),
        'bop' => array('color' => '#0006ee', 'title' => 'Box Office revenue profit'),
        'budget' => array('color' => '#7798BF', 'title' => 'Budget'),
        // Other
        'date' => array('color' => '#2b908f', 'title' => 'Movie release'),
        'actors' => array('color' => '#90ee7e', 'title' => 'Actors'),
        'simpson' => array('color' => '#2b908f', 'title' => 'Simpson\'s Index'),
        'simpsonmf' => array('color' => '#2b908f', 'title' => 'Simpson\'s Index MF'),
        'diversity' => array('color' => '#90ee7e', 'title' => 'Simpson\'s Diversity Index'),
        // Ratings
        'rrwt' => array('title' => 'Rating ZR', 'color' => '#2b908f'),
        'rating' => array('title' => 'Rating Family Friendly Score', 'color' => '#2b908f'),
        'aurating' => array('title' => 'ZR Audience Score', 'color' => '#2b908f'),
        'rimdb' => array('title' => 'Rating IMDB', 'color' => '#2b908f'),
        'rrt' => array('title' => 'Rating Rotten Tomatoes', 'color' => '#2b908f'),
        'rrta' => array('title' => 'Rating Rotten Tomatoes Audience', 'color' => '#2b908f'),
        'rrtg' => array('title' => 'Rating Rotten Tomatoes Gap', 'color' => '#2b908f'),
    );
    public $population_keys = array(
        'White' => 'w',
        'Asian' => 'ea',
        'Dark Asian' => 'da',
        'Black' => 'b',
        'Indian' => 'i',
        'Indigenous' => 'ii',
        'Jewish' => 'jw',
        'Latino' => 'h',
        'Mixed / Other' => 'mix',
        'Arab' => 'm',
    );
    public $race_data_priority = array('c', 'e', 'j', 'k', 'b', 'i', 'f', 's');
    public $race_data_setup = array(
        'c' => array('title' => 'Crowdsource', 'titlehover' => 'Crowdsource'),
        'e' => array('title' => 'Ethnicelebs', 'titlehover' => 'Ethnicelebs'),
        'j' => array('title' => 'JewOrNotJew', 'titlehover' => 'JewOrNotJew'),
        'k' => array('title' => 'Kairos', 'titlehover' => 'Facial Recognition by Kairos'),
        'b' => array('title' => 'Betaface', 'titlehover' => 'Facial Recognition by Betaface'),
        'i' => array('title' => 'ForeBears', 'titlehover' => 'ForeBears Surname Analysis'),
        'f' => array('title' => 'FamilySearch', 'titlehover' => 'FamilySearch Surname Analysis'),
        's' => array('title' => 'Surname', 'titlehover' => 'Surname Analysis'),
    );
    public $verdict_mode = array(
        'p' => array('title' => 'Get race by priority'),
        'w' => array('title' => 'Get race by weight'),
    );
    public $race_weight_priority = array(
        'c' => array('w' => 100, 'ea' => 100, 'h' => 100, 'b' => 100, 'i' => 100, 'm' => 100, 'mix' => 100, 'jw' => 100),
        'e' => array('w' => 50, 'ea' => 50, 'h' => 50, 'b' => 50, 'i' => 50, 'm' => 50, 'mix' => 50, 'jw' => 50),
        'j' => array('w' => 1, 'ea' => 1, 'h' => 1, 'b' => 1, 'i' => 1, 'm' => 1, 'mix' => 1, 'jw' => 90),
        'k' => array('w' => 5, 'ea' => 20, 'h' => 20, 'b' => 21, 'i' => 1, 'm' => 1, 'mix' => 1, 'jw' => 1),
        'b' => array('w' => 1, 'ea' => 10, 'h' => 10, 'b' => 10, 'i' => 10, 'm' => 10, 'mix' => 1, 'jw' => 10),
        'i' => array('w' => 1, 'ea' => 22, 'h' => 22, 'b' => 10, 'i' => 22, 'm' => 22, 'mix' => 10, 'jw' => 10),
        'f' => array('w' => 1, 'ea' => 21, 'h' => 21, 'b' => 5, 'i' => 21, 'm' => 21, 'mix' => 5, 'jw' => 5),
        's' => array('w' => 1, 'ea' => 2, 'h' => 2, 'b' => 2, 'i' => 2, 'm' => 2, 'mix' => 2, 'jw' => 2),
        't' => 1,
    );
    public $race_type_calc = array(
        0 => array('title' => 'Summ'),
        1 => array('title' => 'Top'),
    );
    public $max_actors = 200;
    public $max_budget = 200000000;
    public $max_boxprofit = 1000000000;
    public $min_boxprofit = -10000000;

    public function __construct($cm = '', $cs = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->cs = new AnalyticsSearch($this->cm);
        $this->init_search();
    }

    public function get_ma() {
        // Get criti
        if (!$this->ma) {
            // init cma
            if (!class_exists('MoviesAn')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesAn.php' );
            }
            $this->ma = new MoviesAn($this->cm);
        }
        return $this->ma;
    }

    public function get_uf() {
        // Get user filters
        if (!$this->uf) {
            if (!class_exists('UserFilters')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'UserFilters.php' );
            }
            $this->uf = new UserFilters($this->cm);
        }
        return $this->uf;
    }

    public function init_search() {
        parent::init_search();

        $this->filters['current'] = '';
        $this->filters['stacking'] = '';
    }

    public function search_filters($curr_tab = '', $show = false) {
        if (!$curr_tab) {
            $curr_tab = 'international';
        }

        $this->get_fiters_available($curr_tab);
        $filters = $this->get_search_filters();

        $tags = $this->get_filter_tags($filters);
        $ret = $this->render_filter_tags($tags, $show);

        return $ret;
    }

    public function get_filter_tags($filters) {
        $tags = parent::get_filter_tags($filters);

        $minus = false;
        foreach ($filters as $key => $value) {
            if ($key == 'current') {
                $current = $this->cs->get_current_type($value);
                if ($current['type']) {
                    $curr_item = $this->current[$current['type']];
                    $slug = $current['value'];
                    $name = $curr_item['title'] . ' ' . $slug;
                    $title = $this->cs->search_filters[$key]['title'];
                    $tags[] = array('name' => $name, 'type' => $key, 'title' => $title, 'id' => $slug, 'parent' => 'chart_div', 'minus' => $minus);
                }
            } else if ($key == 'showcast') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = $this->showcast[$slug];
                    $title = $this->cs->search_filters[$key]['title'];

                    $tags[] = array('name' => $name, 'type' => $key, 'title' => $title, 'id' => $slug, 'parent' => 'ethnicity', 'minus' => $minus);
                }
            } else if ($key == 'diversity') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = $this->diversity[$slug]['title'];
                    $title = 'Diversity ';

                    $tags[] = array('name' => $name, 'type' => $key, 'title' => $title, 'id' => $slug, 'parent' => 'ethnicity', 'minus' => $minus);
                }
            } else if ($key == 'vis') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = $this->vis[$slug]['title'];
                    $title = 'Visualization ';

                    $tags[] = array('name' => $name, 'type' => $key, 'title' => $title, 'id' => $slug, 'parent' => 'ethnicity', 'minus' => $minus);
                }
            } else if ($key == 'xaxis' || $key == 'yaxis') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = $this->axis[$slug]['title'];
                    $title = 'X-axis ';
                    if ($key == 'yaxis') {
                        $title = 'Y-axis ';
                    }

                    $tags[] = array('name' => $name, 'type' => $key, 'title' => $title, 'id' => $slug, 'parent' => 'ethnicity', 'minus' => $minus);
                }
            } else if ($key == 'setup') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = $this->setup[$slug]['title'];
                    $title = 'Setup ';

                    $tags[] = array('name' => $name, 'type' => $key, 'title' => $title, 'id' => $slug, 'parent' => 'setup', 'minus' => $minus);
                }
            } else if ($key == 'verdict') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = $this->verdict_mode[$slug]['title'];
                    $title = 'Race verdict mode';

                    $tags[] = array('name' => $name, 'type' => $key, 'title' => $title, 'id' => $slug, 'parent' => 'ethnicity', 'minus' => $minus);
                }
            } else if ($key == 'priority') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = $slug;
                    $title = 'Priority ';

                    $tags[] = array('name' => $name, 'type' => $key, 'title' => $title, 'id' => $slug, 'parent' => 'ethnicity', 'minus' => $minus);
                }
            } else if ($key == 'weight') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = $slug;
                    $title = 'Weight id ';

                    $tags[] = array('name' => $name, 'type' => $key, 'title' => $title, 'id' => $slug, 'parent' => 'ethnicity', 'minus' => $minus);
                }
            } else if ($key == 'stacking') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = ucfirst($slug);
                    $title = 'Stacking ';

                    $tags[] = array('name' => $name, 'type' => $key, 'title' => $title, 'id' => $slug, 'parent' => 'international', 'minus' => $minus, 'class' => 'sohf');
                }
            } else if ($key == 'year') {
                $name = $value;
                $slug = $value;
                $tags[] = array('name' => $name, 'type' => $key, 'title' => 'Year ', 'id' => $slug, 'parent' => 'year', 'minus' => $minus);
            }
        }
        return $tags;
    }

    public function find_results($uid = 0, $ids = array(), $show_facets = true, $only_curr_tab = false, $limit = -1, $page = -1, $show_main = true, $show_chart = true, $fields = array()) {
        $result = array();
        $start = 0;
        $page = $this->get_search_page();
        if ($page > 1) {
            $start = ($page - 1) * $this->search_limit;
        }

        $tab_key = $this->get_tab_key();
        $filters = $this->get_search_filters();

        if ($tab_key == 'international') {
            $sort = $this->get_search_sort($tab_key);
            $data = $this->cs->front_search_international($this->keywords, $this->search_limit, $start, $sort, $filters, $show_facets, true, true, $show_main, $show_chart, $fields);
        } else if ($tab_key == 'ethnicity') {
            // Claster facets
            if (!$show_facets && !$ids) {
                $curr_filter = $this->get_filter('current');
                if ($curr_filter) {
                    $current = $this->cs->get_current_type($curr_filter);
                    if ($current['type'] == 'c') {
                        $show_facets = true;
                    }
                }
            }
            $sort = $this->get_search_sort($tab_key);
            $vis = $this->get_filter('vis');
            $diversity = $this->get_filter('diversity');
            $xaxis = $this->get_filter('xaxis', $this->xaxis_def);
            $yaxis = $this->get_filter('yaxis', $this->yaxis_def);
            $data = $this->cs->front_search_ethnicity_xy($this->keywords, $this->search_limit, $start, $sort, $filters, $ids, $vis, $diversity, $xaxis, $yaxis, $show_facets, true, true, $show_main, $show_chart, $fields);
            gmi('find data');
            if ($show_chart) {
                $data['facets'][$tab_key]['data'] = $this->calculate_facet_ethnicity_xy($data['facets'][$tab_key]['data'], $xaxis, $yaxis);
                gmi('calculate data');
            }
        } else if ($tab_key == 'population') {
            $data = $this->get_population_data();
        } else if ($tab_key == 'worldmap') {
            $data = $this->get_worldmap_data();
        } else if ($tab_key == 'power') {
            $data = $this->get_power_data();
        } else if ($tab_key == 'powerrace') {
            $data = $this->get_power_race_data();
        }

        $result[$tab_key] = $data;

        $result['count'] = $data['count'];

        gmi('find result');

        return $result;
    }

    public function get_facet($facet) {
        gmi('find_results');
        $result = array();
        $start = 0;
        $page = $this->get_search_page();
        if ($page > 1) {
            $start = ($page - 1) * $this->search_limit;
        }

        $tab_key = $this->get_tab_key();

        $filters = $this->get_search_filters();
        gmi('get_search_filters');

        $facets = array($facet);
        $result = array();

        if ($tab_key == 'international') {
            $sort = $this->get_search_sort('international');
            $result[$tab_key] = $this->cs->front_search_international($this->keywords, $this->search_limit, $start, $sort, $filters, $facets, true, true, false, false);
        } else if ($tab_key == 'ethnicity') {

            $sort = $this->get_search_sort('ethnicity');
            $vis = $this->get_filter('vis');
            $diversity = $this->get_filter('diversity');
            $xaxis = $this->get_filter('xaxis', $this->xaxis_def);
            $yaxis = $this->get_filter('yaxis', $this->yaxis_def);
            $ids = array();
            $result[$tab_key] = $this->cs->front_search_ethnicity_xy($this->keywords, $this->search_limit, $start, $sort, $filters, $ids, $vis, $diversity, $xaxis, $yaxis, $facets, true, true, false, false);
        } else if ($tab_key == 'population') {
            $result[$tab_key] = $this->get_population_data();
        } else if ($tab_key == 'worldmap') {
            $result[$tab_key] = $this->get_worldmap_data();
        }

        return $result;
    }

    public function get_tab_key() {
        $tab = $this->get_search_tab();
        if (!$tab) {
            // Default search tab
            $tab = 'international';
        }
        return $tab;
    }

    public function get_facet_weight($key) {
        $weight = isset($this->cs->facet_data[$key]['weight']) ? $this->cs->facet_data[$key]['weight'] : -1;
        if ($weight == -1) {
            $weight = isset($this->cs->facet_data_extend[$key]['weight']) ? $this->cs->facet_data_extend[$key]['weight'] : $this->cs->facet_weight_def;
        }
        return $weight;
    }

    public function show_custom_multi_facet($facets_data, $facet) {
        
    }

    public function show_custom_facet($key, $data, $view_more, $count, $total, $facets_data) {
        if ($key == 'international') {
            $this->show_international_facet($data);
        } else if ($key == 'ethnicity') {
            $this->show_ethnicity_facet($data);
        } else if ($key == 'year') {
            $this->show_slider_facet($data, $count, $key, 'population', 'Year', 'Year ');
        } else if ($key == 'movie') {
            $this->show_movie_facet($data, $view_more, $count, $total);
        }
    }

    public function get_filter($name, $def = '') {
        $filter = $def;
        if ($this->filters[$name]) {
            $filter = $this->filters[$name];
            if (is_array($filter)) {
                $filter = $filter[0];
            }
        } else {
            if ($def) {
                $this->facet_filters[$name] = $def;
            }
        }
        return $filter;
    }

    public function get_filter_multi($name) {
        $filter = array();
        if ($this->filters[$name]) {
            $filter = $this->filters[$name];
            if (!is_array($filter)) {
                $filter = array($filter);
            }
        }
        return $filter;
    }

    public function get_filter_priority($priority_string = '') {
        if (!$priority_string) {
            $verdict = $this->get_filter_multi('priority');
            if ($verdict) {
                foreach ($verdict as $id => $slug) {
                    $priority_string = $slug;
                    break;
                }
            }
        }

        $custom = false;
        $is_active = false;
        $priority = array();
        $def_priority = $this->race_data_priority;

        if ($priority_string) {
            if (preg_match_all('/([a-z]{1})([0-1]{1})/', $priority_string, $match)) {
                foreach ($match[1] as $key => $name) {
                    if (in_array($name, $def_priority)) {
                        $active = $match[2][$key];
                        if (!$is_active && $active > 0) {
                            $is_active = true;
                        }
                        $priority[$name] = $active;
                    }
                }
            }

            // Validate
            if (sizeof($priority) != sizeof($def_priority)) {
                foreach ($def_priority as $key) {
                    if (!isset($priority[$key])) {
                        $priority[$key] = 0;
                    }
                }
            }
        }


        if ($priority && $is_active) {
            $custom = true;
        } else {
            // Default priority;
            foreach ($def_priority as $value) {
                $priority[$value] = 1;
            }
        }

        return array('custom' => $custom, 'priority' => $priority);
    }

    /*
     * Retrun priority or array if defaut
     */

    public function get_filter_mode($mode_key = 0) {
        $custom = true;
        $priority = $this->race_weight_priority;

        if (!$mode_key) {
            // Get mode key from filter
            $verdict = $this->get_filter_multi('weight');
            if ($verdict) {
                foreach ($verdict as $id => $slug) {
                    $mode_key = (int) $slug;
                    break;
                }
            }
        }

        if (!$mode_key) {
            $custom = false;
            // Get filter from settings
            $ss = $this->cm->get_settings(true);
            if (isset($ss['an_weightid']) && $ss['an_weightid'] > 0) {
                $mode_key = $ss['an_weightid'];
            }
        }

        if ($mode_key > 0 && $this->is_int($mode_key)) {
            $ma = $this->get_ma();
            $rule = $ma->get_race_rule_by_id($mode_key);
            if ($rule) {
                $priority = json_decode($rule->rule, true);
            }
        }
        return array('priority' => $priority, 'custom' => $custom);
    }

    public function search_tabs($results = array()) {
        $ret = '<ul id="search-tabs" class="tab-wrapper ajload">';
        $tab = $this->get_search_tab();
        foreach ($this->search_tabs as $slug => $item) {
            $title = $item['title'];
            $count = $results[$slug]['count'];
            $data_tab = $slug;
            $tab_active = false;
            if ($slug == $this->def_tab) {
                $slug = '';
                if ($tab == '') {
                    $tab_active = true;
                }
                $url = $this->get_current_search_url(array(), array('tab', 'p', 'sort'));
            } else {
                if ($slug == $tab) {
                    $tab_active = true;
                }
                $url = $this->get_current_search_url(array('tab' => $slug), array('p', 'sort'));
            }
            $tab_class = 'nav-tab';
            if ($tab_active) {
                $tab_class .= ' active';
            }
            $ret .= '<li class="' . $tab_class . '"><a href="' . $url . '" data-id="' . $slug . '" data-tab="' . $data_tab . '">' . $title . '</a></li>';
        }
        $ret .= '</ul>';

        return $ret;
    }

    /*
     * Facets
     */

    public function show_ethnicity_facet($data) {

        $vis = $this->get_filter('vis');
        $xaxis = $this->get_filter('xaxis', $this->xaxis_def);
        $yaxis = $this->get_filter('yaxis', $this->yaxis_def);

        ob_start();

        // Visualization
        $title = 'Visualization';
        $dates = array();
        foreach ($this->vis as $key => $value) {
            $dates[$key] = array('title' => $value['title']);
        }

        $filter = 'vis';

        $ftype = 'ethnicity';
        $this->theme_facet_select($filter, $dates, $title, $ftype);

        // X-axis
        $title = 'X-axis';
        $dates = array();
        foreach ($this->axis as $key => $value) {
            $dates[$key] = array('title' => $value['title']);
        }
        $filter = 'xaxis';

        $this->theme_facet_select($filter, $dates, $title, $ftype);

        // Y-axis
        $title = 'Y-axis';
        $dates = array();
        foreach ($this->axis as $key => $value) {
            $dates[$key] = array('title' => $value['title']);
        }
        $filter = 'yaxis';

        $this->theme_facet_select($filter, $dates, $title, $ftype);

        // Actor type
        $actor_type = $data['actor_type'];
        $dates = array();
        $filter = 'showcast';

        foreach ($this->showcast as $id => $title) {
            $cnt = $actor_type[$id] ? $actor_type[$id] : 0;
            $dates[$id] = array('title' => $title, 'count' => $cnt,);
        }
        $title = 'Show cast';
        $facet_data = array(
            'filter' => $filter,
            'data' => $dates,
            'title' => $title,
            'more' => 0,
            'ftype' => $ftype,
            'minus' => false,
            'show_count' => false,
        );
        $this->theme_facet_multi($facet_data);

        // Setup
        $dates = array();
        $filter = 'setup';

        $tab_key = $this->get_tab_key();

        foreach ($this->setup as $id => $item) {
            $item_tab = $item['tab'];
            if ($item_tab == 'all' || $item_tab == $tab_key) {
                $cnt = 0;
                $title = $item['title'];
                $dates[$id] = array('title' => $title, 'count' => $cnt,);
                if (isset($item['note'])) {
                    $dates[$id]['note'] = $item['note'];
                }
            }
        }

        $title = 'Setup';

        $facet_data = array(
            'filter' => $filter,
            'data' => $dates,
            'title' => $title,
            'more' => 0,
            'ftype' => $filter,
            'minus' => false,
            'show_count' => false,
        );
        $this->theme_facet_multi($facet_data);

        // Race priority               
        $check_default = '';
        $verdict = $this->get_filter_multi('verdict');
        if (in_array('w', $verdict)) {
            // Custom vedrict weight
            $verdict_mode = 'w';
        } else if (in_array('p', $verdict)) {
            // Custom vedrict priority
            $verdict_mode = 'p';
        } else {
            // Default verdict
            $ss = $this->cm->get_settings();
            $verdict_mode = $ss['an_verdict_type'];
        }
        $check_default = $verdict_mode;

        $ver_weight = false;
        if ($verdict_mode == 'w') {
            $ver_weight = true;
        }

        $priority_content = $this->setup_race_priority($ftype, $ver_weight);

        $title = 'Race verdict mode';
        $dates = array();
        foreach ($this->verdict_mode as $key => $value) {
            $dates[$key] = array('title' => $value['title']);
        }
        $filter = 'verdict';

        $this->theme_facet_select($filter, $dates, $title, $ftype, '', '', '', $priority_content, $check_default);

        $content = ob_get_contents();
        ob_end_clean();

        // Facet holder
        $type = 'ethnicity';
        $title = 'Ethnicity';
        if ($content) {
            //Show multifacet
            $collapsed = $this->cs->is_hide_facet($type, $this->filters)
            ?>
            <div id="facets-<?php print $type ?>" class="facets ajload<?php print $this->cs->hide_facet_class($type, $this->filters) ?>">
                <div class="facet-title wacc">
                    <h3 class="title"><?php print $title ?></h3>   
                    <div class="acc">
                        <div class="chevron icon-down-open"></div>
                        <div class="chevronup icon-up-open"></div>
                    </div>
                </div>
                <div class="facets-ch"> 
                    <?php print $content; ?>
                </div>                    
            </div>
            <?php
        }
    }

    public function setup_race_priority($ftype = '', $ver_weight = false) {
        ob_start();
        /*  public $race_data_setup = array(
          'c' => array('title' => 'Crowdsource', 'titlehover' => 'Crowdsource'),
          'e' => array('title' => 'Ethnicelebs', 'titlehover' => 'Ethnicelebs'),
          'j' => array('title' => 'JewOrNotJew', 'titlehover' => 'JewOrNotJew'),
          'k' => array('title' => 'Kairos', 'titlehover' => 'Facial Recognition by Kairos'),
          'b' => array('title' => 'Betaface', 'titlehover' => 'Facial Recognition by Betaface'),
          's' => array('title' => 'Surname', 'titlehover' => 'Surname Analysis')
          ); */
        ?>
        <div class="verdict_cnt">
            <?php
            if ($ver_weight == false):
                $filter = 'priority';
                ?>
                <div class="flex-row">
                    <span class="t">Data Set Priority:</span>            
                    <div class="nte">
                        <div class="btn">?</div>
                        <div class="nte_show">
                            <div class="nte_in">
                                <div class="nte_cnt">
                                    If no data is available from the prioritized data set, the following data set will be used. If no data is available in any of the data sets, then the cast member in question will be excluded from the analysis.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sort_container">
                    <div class="sort_desc">
                        <?php
                        $priority_arr = $this->get_filter_priority();
                        $priority = $priority_arr['priority'];

                        $i = 1;
                        foreach ($priority as $item) {
                            $after = 'th.';
                            if ($i == 1) {
                                $after = 'st.';
                            } else if ($i == 2) {
                                $after = 'nd.';
                            }
                            ?>
                            <div><?php print $i . $after ?></div>
                            <?php
                            $i += 1;
                        }
                        $title = 'Priority ';
                        $select_tag = $this->theme_tag($filter, $title, 'RVALUE', false);
                        ?>
                    </div>
                    <div id="ethnycity_sort" class="sort_data"  data-name="<?php print $filter ?>" data-ftype="<?php print $ftype ?>" data-fname="<?php print $select_tag['name'] ?>" data-ftitle="<?php print $select_tag['title'] ?>">
                        <?php
                        foreach ($priority as $id => $active) {
                            $disabled = '';
                            if ($active == 0) {
                                $disabled = ' disabled';
                            }
                            $item = $this->race_data_setup[$id];
                            if ($item) {
                                ?>
                                <div id="sort-<?php print $id ?>" data-id="<?php print $id ?>" class="sortitem<?php print $disabled ?>" title="<?php print $item['titlehover'] ?>"><span class="srti">&#8661;</span><span class="title"><?php print $item['title'] ?></span><i></i></div>
                                <?php
                            }
                        }
                        ?>                
                    </div>
                </div>
            <?php else: ?>
                <?php
                $filter = 'weight';
                $filter_mode_arr = $this->get_filter_mode();
                $filter_mode = $filter_mode_arr['priority'];

                $filter_titles = array();
                $filter_races = array();
                foreach ($this->race_data_setup as $k => $v) {
                    $filter_titles[$k] = $v['title'];
                }
                foreach ($this->race_small as $k => $v) {
                    $filter_races[$v['key']] = $v['title'];
                }
                ?>            
                <script type="text/javascript">
                    var filter_mode =<?php print json_encode($filter_mode) ?>;
                    var filter_titles =<?php print json_encode($filter_titles) ?>;
                    var filter_races =<?php print json_encode($filter_races) ?>;
                </script>
                <div class="more-popup">Settings</div>
            <?php endif ?>
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function show_international_facet($data) {

        ob_start();

        // Setup
        $dates = array();
        $filter = 'setup';

        $tab_key = $this->get_tab_key();

        foreach ($this->setup as $id => $item) {
            $item_tab = $item['tab'];
            if ($item_tab == 'all' || $item_tab == $tab_key) {
                $cnt = 0;
                $title = $item['title'];
                $dates[$id] = array('title' => $title, 'count' => $cnt,);
                if (isset($item['note'])) {
                    $dates[$id]['note'] = $item['note'];
                }
            }
        }

        $title = 'Setup';

        $facet_data = array(
            'filter' => $filter,
            'data' => $dates,
            'title' => $title,
            'more' => 0,
            'ftype' => $filter,
            'minus' => false,
            'show_count' => false,
        );
        $this->theme_facet_multi($facet_data);

        $content = ob_get_contents();
        ob_end_clean();

        $ftype = 'international';
        // Facet holder
        $type = 'international';
        $title = 'Box office';
        if ($content) {
            //Show multifacet
            $collapsed = $this->cs->is_hide_facet($type, $this->filters)
            ?>
            <div id="facets-<?php print $type ?>" class="facets ajload<?php print $this->cs->hide_facet_class($type, $this->filters) ?>">
                <div class="facet-title wacc">
                    <h3 class="title"><?php print $title ?></h3>   
                    <div class="acc">
                        <div class="chevron icon-down-open"></div>
                        <div class="chevronup icon-up-open"></div>
                    </div>
                </div>
                <div class="facets-ch"> 
                    <?php print $content; ?>
                </div>                    
            </div>
            <?php
        }
    }

    public function show_budget_facet($data, $count, $type, $ftype = 'all', $title = '', $name_pre = '', $filter_pre = '', $icon = '', $max_count = 6, $multipler = 0, $shift = 0) {
        if (!$title) {
            $title = ucfirst($type);
        }

        if (!$filter_pre) {
            $filter_pre = $title;
        }
        $data_count = array();
        $max_key = $this->cs->budget_max;
        $karay = $this->cs->get_budget_array();

        $from = $to = 0;
        // Filters
        $filters = $this->facet_filters[$type];
        if ($filters) {
            $f_arr = explode('-', $filters);
            $from = array_search($f_arr[0], $karay);
            $to = array_search($f_arr[1], $karay);
        }

        // $last = $data[sizeof($data) - 1];
        // $max_count = $last->id;

        $num = 0;
        $k = $karay[$num];
        foreach ($data as $value) {
            $value = (array) $value;
            $id = trim($value['id']);
            $cnt = $value['cnt'];
            if ($id == 0) {
                continue;
            }
            $key = $k;
            if ($id > $max_key) {
                $key = $max_key;
            } else if ($id > $k) {
                while ($id > $k) {
                    $num += 1;
                    $k = $karay[$num];
                }
            }
            $data_count[$key] += $cnt;
        }

        $i = 0;
        $first_item = $i;
        $items = array();
        $keys = array();
        foreach ($karay as $key) {
            $count = isset($data_count[$key]) ? $data_count[$key] : 0;
            $items[$i] = $count;
            $show_key = $this->get_budget_showkey($key);
            $keys[$i] = $show_key;
            $i++;
        }
        $max_item = $i - 1;

        $data_min = $first_item;
        $collapsed = $this->cs->is_hide_facet($type, $this->filters);

        if (sizeof($items) > 1) {
            ?>
            <div id="facet-<?php print $type ?>" class="facet slider-facet ajload<?php print $this->cs->hide_facet_class($type, $this->filters) ?>" data-type="<?php print $ftype ?>">
                <div class="facet-title wacc">                    
                    <?php if ($icon) { ?>
                        <div class="facet-icon"><?php print $icon; ?></div>
                    <?php } ?>
                    <h3 class="title">                        
                        <?php print $title ?>
                    </h3>   
                    <div class="acc">
                        <div class="chevron icon-down-open"></div>
                        <div class="chevronup icon-up-open"></div>
                    </div>
                </div>
                <div class="facet-ch">        
                    <div class="facet-content">
                        <canvas id="<?php print $type ?>-canvas" class="facet-canvas"></canvas>
                        <div id="<?php print $type ?>-slider" class="extend" data-min="<?php print $data_min ?>" data-max="<?php print $max_item ?>" 
                             data-from="<?php print $from ?>" data-to="<?php print $to ?>" data-filter-pre="<?php print $filter_pre ?>" 
                             data-title-pre="<?php print $name_pre ?>" data-multipler="<?php print $multipler ?>" data-shift="<?php print $shift ?>"></div>
                        <div class="select-holder">
                            <div class="select-from">
                                From: 
                                <select id="<?php print $type ?>-from" name="<?php print $type ?>[]">                        
                                    <?php
                                    foreach ($items as $key => $value) {
                                        $data_value = $karay[$key];
                                        $checked = '';
                                        if (!$filters) {
                                            //Last checked
                                            if ($key == $first_item) {
                                                $checked = 'selected ';
                                            }
                                        } else {
                                            if ($key == $from) {
                                                $checked = 'selected ';
                                            }
                                        }
                                        $show_key = $keys[$key];
                                        ?>
                                        <option value="<?php print $key ?>" <?php print $checked ?> data-value="<?php print $data_value ?>"><?php print $show_key ?></option>
                                    <?php } ?>
                                </select> 
                            </div>
                            <div class="select-to">
                                To: 
                                <select id="<?php print $type ?>-to" name="<?php print $type ?>[]">                        
                                    <?php
                                    foreach ($items as $key => $value) {
                                        $data_value = $karay[$key];
                                        $checked = '';
                                        if (!$filters) {
                                            //Last checked
                                            if ($key == $max_item) {
                                                $checked = 'selected ';
                                            }
                                        } else {
                                            if ($key == $to) {
                                                $checked = 'selected ';
                                            }
                                        }
                                        $show_key = $keys[$key];
                                        ?>
                                        <option value="<?php print $key ?>" <?php print $checked ?> data-value="<?php print $data_value ?>"><?php print $show_key ?></option>
                                    <?php } ?>
                                </select>  
                            </div>
                        </div>
                        <input type="hidden" name="<?php print $type ?>" value="<?php print $first_item ?>">
                        <input type="hidden" name="<?php print $type ?>" value="<?php print $max_item ?>">
                        <?php //unset($items[count($items) - 1]);                                       ?>
                        <script type="text/javascript">var <?php print $type ?>_arr =<?php print json_encode($items) ?></script>
                    </div>  
                </div>
            </div>
            <?php
        }
    }

    public function get_budget_showkey($key) {
        $show_key = $key;

        if ($key < 1000) {
            $show_key = $show_key . 'k';
        } else {
            $round = 0;
            if ($key < 10000) {
                $round = 1;
            }
            $show_key = round($show_key / 1000, $round) . 'm';
        }
        return $show_key;
    }

    /*
     * Page content
     */

    public function page_content($data = array(), $tab_key = '') {
        if (isset($data[$tab_key]['list'])) {
            if ($tab_key == 'international') {
                $this->show_movies_table($data, $tab_key);
            } else if ($tab_key == 'ethnicity') {
                $this->show_movies_table($data, $tab_key);
            }
        }
    }

    public function show_movies_table($search_data, $tab_key) {
        $data = $search_data[$tab_key]['list'];

        if ($tab_key == 'ethnicity') {
            $eth_data = isset($search_data[$tab_key]['facets'][$tab_key]['data']) ? $search_data[$tab_key]['facets'][$tab_key]['data'] : array();

            if (!$eth_data && !$this->claster_data) {
                // Ajax sort logic
                $ids = array();
                foreach ($data as $movie) {
                    $ids[] = $movie->id;
                }
                $this->claster_data = $ids;
            }

            if ($this->claster_data && sizeof($this->claster_data) > 1) {
                // Claster logic
                $search_claster_data = $this->find_results(0, $this->claster_data);
            }

            if ($this->claster_data) {
                // New claster data       
                // $data = $search_data[$tab_key]['list'];
                $eth_data = isset($search_claster_data[$tab_key]['facets'][$tab_key]['data']) ? $search_claster_data[$tab_key]['facets'][$tab_key]['data'] : array();
            }
        }


        if (!$data) {
            ?>
            <h2 style="width: 100%; text-align: center" >No results found</h2>
            <?php
            return '';
        }

        // Axises
        $xaxis = $this->get_filter('xaxis', $this->xaxis_def);
        $yaxis = $this->get_filter('yaxis', $this->yaxis_def);
        $axises = array($xaxis, $yaxis);

        // Setup
        $setup = $this->get_filter_multi('setup');
        $inflation = false;
        if (in_array('inflation', $setup)) {
            if (in_array('boxworld', $axises) || in_array('boxint', $axises) || in_array('boxdom', $axises) || in_array('boxprofit', $axises) || in_array('budget', $axises)) {
                $inflation = true;
            }
        }
        // Combine wite and jews
        $combine_wj = false;
        if (in_array('cwj', $setup)) {
            $combine_wj = true;
        }

        $array_movie_bell = array();

        $table_class = 'tidiv';
        $table = array();
        if ($tab_key == 'international') {
            $table = array(
                'Movie' => 'none',
                "Worldwide Box Office" => '',
                "Domestic Box Office" => '',
                "International Box Office" => '',
                "Domestic Share %" => ''
            );
        } else if ($tab_key == 'ethnicity') {

            $array_movie_bell = $eth_data['array_movie_bell'];
            $table = array(
                "Movie" => 'none',
            );

            $xtitle = $this->axis[$xaxis]['title'] ? $this->axis[$xaxis]['title'] : 'None';
            $ytitle = $this->axis[$yaxis]['title'] ? $this->axis[$yaxis]['title'] : 'None';

            //nte
            $xtitle .= $this->axis[$xaxis]['nte'] ? ' <span data-value="' . $this->axis[$xaxis]['nte'] . '" class="nte_info"></span>' : '';
            $ytitle .= $this->axis[$yaxis]['nte'] ? ' <span data-value="' . $this->axis[$yaxis]['nte'] . '" class="nte_info"></span>' : '';

            $table[$xtitle] = '';
            $table[$ytitle] = '';
        }
        if ($inflation) {
            $table["Inflation"] = "";
        }
        $table["More"] = "";

        if (!$table) {
            return '';
        }

        $this->print_mob_styles($table, $table_class);
        ?>        
        <table class="analytics_table rspv <?php print $table_class ?>">
            <thead>
                <tr>
                    <?php
                    foreach ($table as $key => $value) {
                        if ($value == 'none') {
                            print '<th colspan="2">' . $key . '</th>';
                        } else {
                            print '<th class="a_center">' . $key . '</th>';
                        }
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($data as $item):
                    $imod = 0;
                    if ($inflation) {
                        $imod = $this->get_inflation_modifer($item->year);
                    }
                    if ($tab_key == 'international') {
                        ?>
                        <tr>
                            <td colspan="2" class="movie_clmn"><?php $this->theme_movie_item($item); ?></td>
                            <td class="a_right">
                                <?php
                                print '$' . number_format($item->boxworld);
                                if ($inflation) {
                                    print $this->theme_page_inflation($item->boxworld, $imod);
                                }
                                ?>
                            </td>
                            <td class="a_right"><?php
                                print '$' . number_format($item->boxusa);
                                if ($inflation) {
                                    print $this->theme_page_inflation($item->boxusa, $imod);
                                }
                                ?></td>
                            <td class="a_right"><?php
                                print '$' . number_format($item->boxint);
                                if ($inflation) {
                                    print $this->theme_page_inflation($item->boxint, $imod);
                                }
                                ?></td>
                            <td class="a_right"><?php print round(($item->share * 100), 2) ?></td>
                            <?php if ($inflation) { ?>
                                <td class="a_right"><?php print ((round($imod, 2) * 100) - 100); ?></td>
                            <?php } ?>
                            <td class="more wacc"><div class="acc collapsed" data-more="<?php print $item->id ?>"><div class="chevron icon-down-open"></div><div class="chevronup icon-up-open"></div></div></td>
                        </tr>
                        <?php
                    } else if ($tab_key == 'ethnicity') {
                        ?> 
                        <tr>
                            <td colspan="2" class="movie_clmn"><?php $this->theme_movie_item($item); ?></td>
                            <td class="a_right"><?php
                                print $this->theme_axis_data($xaxis, $array_movie_bell[$item->id]['xdata'], $inflation);
                                ?></td>
                            <td class="a_right"><?php
                                print $this->theme_axis_data($yaxis, $array_movie_bell[$item->id]['ydata'], $inflation);
                                ?></td>
                            <?php if ($inflation) { ?>
                                <td class="a_right"><?php print ((round($imod, 2) * 100) - 100); ?>%</td>
                            <?php } ?>
                            <td class="more wacc"><div class="acc collapsed" data-more="<?php print $item->id ?>"><div class="chevron icon-down-open"></div><div class="chevronup icon-up-open"></div></div></td>
                        </tr>
                        <?php
                    }
                    ?>
                <?php endforeach; ?>
            </tbody>    
        </table>            
        <?php
        print $this->pagination($search_data[$tab_key]['count']);
    }

    public function theme_axis_data($axis = '', $data = '', $inflation = false) {
        $ret = $data;
        if (!is_array($data)) {
            return $ret;
        }

        if ($axis == 'release') {
            // Date
            $ret = date('Y-m-d', array_pop($data) / 1000);
        } else if ($axis == 'boxworld' || $axis == 'boxint' || $axis == 'boxdom' || $axis == 'boxprofit' || $axis == 'budget') {
            // Box
            $box_arr = array_pop($data);
            if ($box_arr['d']) {
                $box = $this->k_m_b_generator($box_arr['d']);

                $ret = '$ ' . $box;
                if ($inflation) {
                    $boxi = $this->k_m_b_generator($box_arr['i']);
                    $ret = $ret . '<br />Adjusted for inflation: $ ' . $boxi;
                }
            }
        } else if ($axis == 'mf' || $axis == 'eth' || $axis == 'ethmf' || $axis == 'wjnw' || $axis == 'wjnwj' || $axis == 'wmjnwm' || $axis == 'wmjnwmj' || $axis == 'simpson' || $axis == 'simpsonmf' || $axis == 'actors') {
            // Race
            if ($axis == 'simpson' || $axis == 'simpsonmf' || $axis == 'actors') {
                $ret = array_pop($data);
            } else if ($axis == 'mf') {
                $ret_arr = array();

                if ($data['male']) {
                    $ret_arr[] = 'Male: ' . $data['male']['t'] . '&nbsp;(' . $data['male']['p'] . '%)';
                }
                if ($data['female']) {
                    $ret_arr[] = 'Female: ' . $data['female']['t'] . '&nbsp;(' . $data['female']['p'] . '%)';
                }
                if ($ret_arr) {
                    $ret = implode('<br />', $ret_arr);
                }
            } else if ('eth') {
                // Ethnicity
                $ret_arr = array();
                $data_sort = array();

                foreach ($data as $key => $value) {
                    $data_sort[$key] = $value['t'];
                }
                arsort($data_sort);
                foreach ($data_sort as $key => $value) {
                    $name = $this->array_ethnic_data[$key]['title'];
                    $ret_arr[] = $name . ': ' . $data[$key]['t'] . '&nbsp;(' . $data[$key]['p'] . '%)';
                }
                $ret = implode('<br />', $ret_arr);
            } else if ('ethmf') {
                // Ethnicity male and female
                $ret_arr = array();
                $data_sort = array();

                foreach ($data as $key => $value) {
                    $data_sort[$key] = $value['t'];
                }
                arsort($data_sort);
                foreach ($data_sort as $key => $value) {
                    $name = $this->array_ethnic_data[$key]['title'];
                    $ret_arr[] = $name . ': ' . $data[$key]['t'] . '&nbsp;(' . $data[$key]['p'] . '%)';
                }
                $ret = implode('<br />', $ret_arr);
            }
        } else if ($axis == 'rimdb' || $axis == 'rrwt' || $axis == 'rrt' || $axis == 'rrta' || $axis == 'rrtg' || $axis == 'rating' || $axis == 'aurating') {
            // Rating
            $ret = array_pop($data);
        }
        return $ret;
    }

    public function k_m_b_generator($num) {
        $minus = false;
        if ($num < 0) {
            $minus = true;
            $num = abs($num);
        }
        if ($num > 999 && $num < 99999) {
            $num = round($num / 1000, 2) . " K";
        } else if ($num > 99999 && $num < 999999) {
            $num = round($num / 1000, 2) . " K";
        } else if ($num > 999999 && $num < 999999999) {
            $num = round($num / 1000000, 2) . " M";
        } else if ($num > 999999999 && $num < 999999999999) {
            $num = round($num / 1000000000, 2) . " B";
        } else if ($num > 999999999999) {
            $num = round($num / 1000000000000, 2) . " T";
        }
        if ($minus) {
            $num = '-' . $num;
        }
        return $num;
    }

    public function theme_page_inflation($box, $imod) {
        if ($imod > 1 && $box > 0) {
            return '<br />Adjusted for inflation: $' . number_format($box * $imod);
        }
    }

    public function theme_movie_item($item) {
        $link = $this->get_movie_link($item->type, $item->post_name);
        $img = $this->get_image_link($item->id, $item->add_time, '90x120');
        ?>
        <div class="an_movie">
            <a class="an_poster" href="<?php print $link ?>">   
                <img loading="lazy" class="an_poster" srcset="<?php print $img ?> 1x" >                                
            </a>
            <div class="an_content">
                <a class="title" href="<?php print $link ?>"><?php print $item->title ?></a><br />
                <span class="year"><?php print $item->year ?></span>
            </div>  
        </div> 
        <?php
    }

    /*
     * Page facets
     */

    public function page_facet($data = array(), $tab_key = '') {
        if ($data['count']) {
            if ($tab_key == 'international') {
                $this->show_page_facet_international($data, $tab_key);
            } else if ($tab_key == 'ethnicity') {
                $this->show_page_facet_ethnicity_xy($data, $tab_key);
            } else if ($tab_key == 'population') {
                $this->show_page_facet_population($data, $tab_key);
            } else if ($tab_key == 'worldmap') {
                $this->show_page_facet_worldmap($data, $tab_key);
            } else if ($tab_key == 'power') {
                $this->show_page_facet_power($data, $tab_key);
            } else if ($tab_key == 'powerrace') {
                $this->show_page_facet_powerrace($data, $tab_key);
            }
        }
    }

    /*
     * Get page facets
     */

    public function get_page_facet($data = array(), $tab_key = '', $preview_limit = 0) {
        $ret = array();

        if ($tab_key == 'international') {
            $ret = $this->show_page_facet_international($data, $tab_key, true, $preview_limit);
        } else if ($tab_key == 'ethnicity') {
            $ret = $this->show_page_facet_ethnicity_xy($data, $tab_key, true, $preview_limit);
        } else if ($tab_key == 'population') {
            $ret = $this->show_page_facet_population($data, $tab_key, true, $preview_limit);
        } else if ($tab_key == 'worldmap') {
            $ret = $this->show_page_facet_worldmap($data, $tab_key, true, $preview_limit);
        } else if ($tab_key == 'power') {
            $ret = $this->show_page_facet_power($data, $tab_key, true, $preview_limit);
        } else if ($tab_key == 'powerrace') {
            $ret = $this->show_page_facet_powerrace($data, $tab_key, true, $preview_limit);
        }

        return $ret;
    }

    public function show_page_facet_international($search_data, $tab_key = '', $api_mode = false, $preview_limit = 0) {

        $data = $search_data[$tab_key]['facets'][$tab_key]['data'];
        $select_movies_count = $search_data[$tab_key]['count'];
        $movies_count = sizeof($data);

        if (!$data) {
            return '';
        }

        // Current filters
        $current = '';
        $curryear = '';
        $curr_filter = $this->get_filter('current');
        if ($curr_filter) {
            $current = $this->cs->get_current_type($curr_filter);
            if ($current['type'] == 'y') {
                $curryear = $current['value'];
            }
        }

        /*
         * [0] => stdClass Object ( [box_usa] => 205881154 [box_world] => 520881154 [share] => 0.395256 [year] => 1991
         */

        $total = array();
        $select_total = array();

        $setup = $this->get_filter_multi('setup');
        $inflation = false;
        if (in_array('inflation', $setup)) {
            $inflation = true;
        }

        foreach ($data as $item) {
            $year = $item->year;
            $imod = 1;
            if ($inflation) {
                $imod = $this->get_inflation_modifer($year);
            }

            $boxusa = round(((int) $item->box_usa) * $imod, 0);
            $boxworld = round(((int) $item->box_world) * $imod, 0);
            $array_years[$year]['bod'] += $boxusa;

            $int = 0;
            if ($boxworld > $boxusa) {
                $int = ($boxworld - $boxusa);
            }

            $array_years[$year]['boi'] += $int;

            $total['bod'] += $boxusa;
            $total['boi'] += $int;

            if ($curryear && $curryear == $year) {
                $select_total['bod'] += $boxusa;
                $select_total['boi'] += $int;
            }
        }

        $result_data = array();
        $array_result = array();

        ksort($array_years);
        if (is_array($array_years)) {
            $counter = 0;
            foreach ($array_years as $index => $val) {
                arsort($val);

                foreach ($val as $val_type => $count) {
                    if (!$count)
                        $count = 0;
                    if ($api_mode) {
                        $array_result[$val_type][] = array("x" => $index, "y" => $count);
                    } else {
                        $array_result[$val_type][] = "{x:" . $index . ",y:" . $count . "}";
                    }
                }
                $counter += 1;
                if ($preview_limit > 0 && $counter >= $preview_limit) {
                    break;
                }
            }

            foreach ($array_result as $val_type => $result) {
                $ethnic = $this->array_ethnic_data[$val_type];

                if ($api_mode) {
                    $result_data[] = $this->graphic_api($ethnic['title'], $ethnic['color'], $result);
                } else {
                    $result_data[] = $this->graphic_default($ethnic, $result);
                }
            }
        }

        $current_filter = 'current';
        $current_title = $this->cs->search_filters[$current_filter]['title'];
        $ftype = 'international';
        $graph_title = 'Box Office by year';
        $y_axis = 'Total Box Office';
        $stacking = 'normal';
        if ($this->filters['stacking']) {
            $stacking = 'percent';
        }

        // Api result
        if ($api_mode) {

            $ret = array(
                'title' => $graph_title,
                'yaxis' => $y_axis,
                'stacking' => $stacking,
                'results' => $result_data,
            );
            return $ret;
        }

        $vis = '';
        ?>
        <script type="text/javascript">
                            var search_extend_data = [<?php echo implode('', $result_data); ?>];
        </script>
        <div id="chart_div" 
             data-tab="<?php print $tab_key ?>" 
             data-vis="<?php print $vis ?>" 
             data-graph-title="<?php print $graph_title ?>" 
             data-y="<?php print $y_axis ?>">           
        </div>

        <div class="change_stack"></div>

        <?php
        $boi = $total['boi'];
        $bod = $total['bod'];
        $sboi = $sbod = 0;
        if ($curryear) {
            $sboi = $select_total['boi'];
            $sbod = $select_total['bod'];
        }
        $title = "Box office";
        $collapsed = "";
        $type = "boxofficetable";
        $current_parent = 'chart_div';
        $filter_tag = $this->theme_tag($current_filter, $current_title, 'RVALUE', false);
        ?>
        <div id="select-current"  
             data-name="<?php print $current_filter ?>" 
             data-ftype="<?php print $current_parent ?>"              
             data-ftitle="<?php print $filter_tag['title'] ?>"
             data-fname="<?php print $filter_tag['name'] ?>">         
                 <?php
                 if ($curryear) {
                     print '<h3>Current year: ' . $curryear . '</h3>';
                     print '<p>Select movies count: ' . $select_movies_count . '. Total: ' . $movies_count . '.</p>';
                 } else {
                     print '<p>Movies count: ' . $movies_count . '</p>';
                 }
                 ?> <div id="facet-<?php print $type ?>" class="facet ajload<?php print $this->cs->hide_facet_class($type, $this->filters) ?>">
                <div class="facet-title wacc">
                    <h3 class="title"><?php print $title ?></h3>   
                    <div class="acc">
                        <div class="chevron icon-down-open"></div>
                        <div class="chevronup icon-up-open"></div>
                    </div>
                </div>
                <div class="facet-ch">
                    <?php
                    print $this->show_box_office_table($boi, $bod, $movies_count, $sboi, $sbod, $select_movies_count, $curryear);
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function show_page_facet_ethnicity_xy($search_data, $tab_key = '', $api_mode = false, $preview_limit = 0) {

        $data = $search_data[$tab_key]['facets'][$tab_key]['data'];

        if (!$data) {
            return '';
        }

        $vis = $this->get_filter('vis');
        if (!$vis) {
            $vis = 'column';
        }
        $xaxis = $this->get_filter('xaxis', $this->xaxis_def);
        $yaxis = $this->get_filter('yaxis', $this->yaxis_def);

        $array_movie_bell = $data['array_movie_bell'];
        $movies_count = $data['movies_count'];
        $races_count = $data['races_count'];
        $total_calc = $data['total_ret'];

        // Current select
        $curryear = $data['curryear'];
        $currmovie = $data['currmovie'];
        $currclaster = $data['currclaster'];

        // Setup
        $setup = $this->get_filter_multi('setup');
        $clasters = true;
        $inflation = false;
        if (in_array('noclasters', $setup)) {
            $clasters = false;
        }
        if (in_array('inflation', $setup)) {
            $inflation = true;
        }


        $simpson_data = array();
        $result_in = array();
        $ytotal = array();
        $claster_ids = array();
        $i = 0;
        $j = 0;

        // Main for from all movies array
        foreach ($array_movie_bell as $mid => $item) {
            // Get data
            $xdata = $item['xdata'];
            $ydata = $item['ydata'];
            $title = $item['title'];
            $year = $item['year'];
            $titley = addslashes($title) . ' (' . $year . ')';

            foreach ($xdata as $xname => $x_val) {
                foreach ($ydata as $yname => $y_val) {
                    if ($vis == 'column' || $vis == 'line') {
                        // Columns                        
                        $xfilter_arr = $this->get_axis_filter($xaxis, $x_val, $year, $inflation, 'p', $clasters);
                        $yfilter_arr = $this->get_axis_filter($yaxis, $y_val, $year, $inflation, 't');
                        $xfilter = $xfilter_arr['filter'];
                        $xfilter = str_replace('-', 'm', $xfilter);
                        $xfilter = str_replace('.', 't', $xfilter);
                        $xval = $xfilter_arr['data'];
                        $yval = $yfilter_arr['data'];
                        $result_in[$xname][$yname][$xfilter]['x'] = $xval;
                        $result_in[$xname][$yname][$xfilter]['y'] += $yval;
                        $result_in[$xname][$yname][$xfilter]['c'] += 1;
                        $ytotal[$xfilter] += $yval;
                        $claster_ids[$xfilter][] = $mid;
                    } else {
                        if ($clasters) {
                            $xfilter_arr = $this->get_axis_filter($xaxis, $x_val, $year, $inflation, 'p', $clasters);
                            $yfilter_arr = $this->get_axis_filter($yaxis, $y_val, $year, $inflation, 'p', $clasters);
                            $xfilter = $xfilter_arr['filter'];
                            $yfilter = $yfilter_arr['filter'];

                            $xfilter = str_replace('-', 'm', $xfilter);
                            $xfilter = str_replace('.', 't', $xfilter);
                            $yfilter = str_replace('-', 'm', $yfilter);
                            $yfilter = str_replace('.', 't', $yfilter);

                            $key_filter = 'x' . $xfilter . 'y' . $yfilter;

                            $result_in[$xname][$yname][$key_filter]['x'] = $xfilter_arr['data'];
                            $result_in[$xname][$yname][$key_filter]['y'] = $yfilter_arr['data'];
                            $result_in[$xname][$yname][$key_filter]['c'] += 1;
                            $result_in[$xname][$yname][$key_filter]['t'][] = $titley;
                            $result_in[$xname][$yname][$key_filter]['id'][] = $mid;

                            $claster_ids[$key_filter][] = $mid;
                        } else {
                            // Create results for scatter chart
                            $xfilter_arr = $this->get_axis_filter($xaxis, $x_val, $year, $inflation, 'p');
                            $yfilter_arr = $this->get_axis_filter($yaxis, $y_val, $year, $inflation, 'p');
                            $result_in[$xname][$yname][] = "{x:" . $xfilter_arr['data'] . ",y:" . $yfilter_arr['data'] . ",title:'" . $titley . "',id:'" . $mid . "'}";
                        }
                    }
                }
            }
        }

        $show_type = 'scatter';
        if ($vis == 'column') {
            $show_type = 'column';
        } if ($vis == 'line') {
            $show_type = '';
        }


        $result_data = array();
        $size_x = sizeof($result_in);
        $size_y = 1;

        // Create results for clasters
        foreach ($result_in as $xname => $xarr) {
            $xitem = $this->array_ethnic_data[$xname];
            $size_y = sizeof($xarr);
            $title = $xitem['title'];
            $color = $xitem['color'];

            foreach ($xarr as $yname => $result) {
                $yitem = $this->array_ethnic_data[$yname];

                if ($size_x > 1 && $size_y > 1) {
                    $title = $xitem['title'] . ' / ' . $yitem['title'];
                } else if ($size_y > 1) {
                    $title = $yitem['title'];
                    $color = $yitem['color'];
                }

                if ($vis == 'column' || $vis == 'line') {
                    // Columns
                    $result_item = array();
                    $counter = 0;
                    foreach ($result as $key_filter => $cdata) {
                        $ydata = $cdata['y'];
                        $xdata = $cdata['x'];
                        $actors_total = $ytotal[$key_filter];
                        $movies_total = $cdata['c'];

                        if ($yaxis == 'boxworld' || $yaxis == 'boxint' || $yaxis == 'boxdom' || $yaxis == 'boxprofit' || $yaxis == 'budget') {
                            // Total for box
                        } else if ($yaxis == 'mf' || $yaxis == 'eth' || $yaxis == 'ethmf' || $yaxis == 'wjnw' || $yaxis == 'wjnwj' || $yaxis == 'wmjnwm' || $yaxis == 'wmjnwmj') {
                            // Percent for actors

                            if ($actors_total > 0) {
                                $ydata = round(100 * $cdata['y'] / $actors_total, 2);
                            }
                        } else {
                            // Average per movie

                            if ($movies_total > 0) {
                                $ydata = round($cdata['y'] / $movies_total, 2);
                            }
                        }
                        $type = 'c';
                        if ($xaxis == 'release') {
                            $type = 'y';
                        }

                        $result_item[] = "{x:" . $xdata . ",y:" . $ydata . ",id:'" . $key_filter . "',t:'" . $type . "'}";
                        $counter += 1;
                        if ($preview_limit > 0 && $counter >= $preview_limit) {
                            break;
                        }
                    }
                    $result = $result_item;
                } else {

                    if ($clasters) {
                        $result_item = array();
                        $counter = 0;
                        foreach ($result as $key_filter => $cdata) {
                            $count = $cdata['c'];
                            if ($count == 1) {
                                $titley = $cdata['t'][0];
                                $mid = $cdata['id'][0];
                                $type = 'm';
                            } else {
                                $ctitle = array();
                                $movies = 0;
                                foreach ($cdata['t'] as $movie_title) {
                                    $ctitle[$movie_title] = $movie_title;
                                    $movies += 1;
                                    if ($movies >= 3) {
                                        break;
                                    }
                                }
                                ksort($ctitle);
                                if ($count > 3) {
                                    $ctitle[] = 'and ' . ($count - 3) . ' movies.';
                                }
                                $titley = implode(',<br />', $ctitle);
                                $mid = $key_filter;
                                $type = "c";
                            }
                            if ($api_mode) {
                                $result_item[] = array("x" => $cdata['x'], "y" => $cdata['y'], "title" => $titley, "id" => $mid, "t" => $type);
                            } else {
                                $result_item[] = "{x:" . $cdata['x'] . ",y:" . $cdata['y'] . ", title:'" . $titley . "', id:'" . $mid . "', t:'" . $type . "'}";
                            }
                            $counter += 1;
                            if ($preview_limit > 0 && $counter >= $preview_limit) {
                                break;
                            }
                        }
                        $result = $result_item;
                    }
                }

                if ($api_mode) {
                    $result_data[] = $this->graphic_api($title, $color, $result);
                } else {
                    $result_data[] = $this->graphic_xy($title, $color, $result, $show_type);
                }
            }
        }


        $ftype = 'ethnicity';
        $x_title = 'No title';

        if ($xaxis) {
            $x_title = $this->axis[$xaxis]['title'];
            $xa_title = $this->axis[$xaxis]['atitle'];
            $xaxist = isset($this->axis[$xaxis]['type']) ? $this->axis[$xaxis]['type'] : '';
            $xformat = isset($this->axis[$xaxis]['format']) ? $this->axis[$xaxis]['format'] : '';
            $xmin = isset($this->axis[$xaxis]['min']) ? $this->axis[$xaxis]['min'] : 0;
            $xmax = isset($this->axis[$xaxis]['max']) ? $this->axis[$xaxis]['max'] : -1;
        }

        if ($yaxis) {
            $y_title = $this->axis[$yaxis]['title'];
            $ya_title = $this->axis[$yaxis]['atitle'];
            $yaxist = isset($this->axis[$yaxis]['type']) ? $this->axis[$yaxis]['type'] : '';
            $yformat = isset($this->axis[$yaxis]['format']) ? $this->axis[$yaxis]['format'] : '';
            $ymin = isset($this->axis[$yaxis]['min']) ? $this->axis[$yaxis]['min'] : 0;
            $ymax = isset($this->axis[$yaxis]['max']) ? $this->axis[$yaxis]['max'] : -1;
        }

        $graph_title = $x_title . ' / ' . $y_title;

        $stacking = 'normal';
        if ($this->filters['stacking']) {
            $stacking = 'percent';
        }

        // Api result
        if ($api_mode) {
            $ret = array(
                'title' => $graph_title,
                'xtitle' => $x_title,
                'xatitle' => $xa_title,
                'xaxis' => $xaxis,
                'xaxist' => $xaxist,
                'xformat' => $xformat,
                'xsize' => $size_x,
                'xmin' => $xmin,
                'xmax' => $xmax,
                'ytitle' => $y_title,
                'yatitle' => $ya_title,
                'yaxis' => $yaxis,
                'yaxist' => $yaxist,
                'yformat' => $yformat,
                'ysize' => $size_y,
                'ymin' => $ymin,
                'ymax' => $ymax,
                'results' => $result_data,
            );
            return $ret;
        }

        // Graphic view
        // https://api.highcharts.com/highcharts/
        ?>
        <script type="text/javascript">
            var search_extend_data = [<?php echo implode(',', $result_data); ?>];
        </script>   
        <div id="chart_div" 
             data-tab="<?php print $tab_key ?>" 
             data-vis="<?php print $vis ?>"              
             data-title="<?php print $graph_title ?>" 

             data-xtitle="<?php print $x_title ?>"             
             data-xatitle="<?php print $xa_title ?>"             
             data-xaxis="<?php print $xaxis ?>" 
             data-xaxist="<?php print $xaxist ?>" 
             data-xformat="<?php print $xformat ?>" 
             data-xsize="<?php print $size_x ?>" 
             data-xmin="<?php print $xmin ?>" 
             data-xmax="<?php print $xmax ?>" 

             data-ytitle="<?php print $y_title ?>"             
             data-yatitle="<?php print $ya_title ?>"             
             data-yaxis="<?php print $yaxis ?>"             
             data-yaxist="<?php print $yaxist ?>" 
             data-yformat="<?php print $yformat ?>" 
             data-ysize="<?php print $size_y ?>" 
             data-ymin="<?php print $ymin ?>" 
             data-ymax="<?php print $ymax ?>" 

             class="" >
        </div>
        <div class="swap_xy">Swap the "x" and "y" axes</div>
        <?php
        // Current claster logic
        $ids = array();
        if ($currmovie) {
            $select_movies_count = 1;
            $ids = array($currmovie);
        } else if ($currclaster) {
            if (isset($claster_ids[$currclaster])) {
                $ids = $claster_ids[$currclaster];
                $select_movies_count = sizeof($ids);
            }
        }
        $this->claster_data = $ids;

        // Info table
        $current_filter = 'current';
        $current_title = $this->cs->search_filters[$current_filter]['title'];

        $current_parent = 'chart_div';
        $filter_tag = $this->theme_tag($current_filter, $current_title, 'RVALUE', false);
        ?>
        <div id="select-current"                  
             data-name="<?php print $current_filter ?>" 
             data-ftype="<?php print $current_parent ?>"              
             data-ftitle="<?php print $filter_tag['title'] ?>"
             data-fname="<?php print $filter_tag['name'] ?>"
             class="select-current" >

            <?php
            // Title logic
            $curr_title = '';
            $movies_count_title = '';
            if ($curryear) {
                // Year
                $curr_title = 'year: ' . $curryear;
            } else if ($currclaster) {
                // Claster
                $curr_title = 'claster: ' . $currclaster;
            } else if ($currmovie) {
                // Movie
                $currmovie_item = $array_movie_bell[$currmovie];
                if ($currmovie_item) {
                    $movie_title = addslashes($currmovie_item['title']) . ' (' . $currmovie_item['year'] . ')';
                }
                $curr_title = 'movie: ' . $movie_title;
            }
            if ($select_movies_count) {
                $movies_count_title = 'Select movies count: ' . number_format($select_movies_count) . '. Total: ' . number_format($movies_count) . '. ';
            } else {
                $movies_count_title = 'Movies count: ' . number_format($movies_count) . '. ';
                if ($races_count) {
                    // All cast show only
                    $movies_count_title .= 'Cast count: ' . number_format($races_count) . '.';
                }
            }

            if ($curr_title) {
                ?>
                <h3>Current <?php print $curr_title ?></h3> 
                <?php
            }
            if ($movies_count_title) {
                ?>
                <p><?php print $movies_count_title ?></p> 
            <?php } ?>  

            <?php
            $this->get_axis_table($xaxis, $total_calc, $movies_count, $races_count, $inflation);
            $this->get_axis_table($yaxis, $total_calc, $movies_count, $races_count, $inflation);
            ?>
        </div>
        <?php
    }

    public function show_page_facet_population($search_data, $tab_key = '', $api_mode = false, $preview_limit = 0) {
        $data = $search_data[$tab_key];
        $result_data = array();

        // Filters
        $years = $this->get_filter('year');

        $from = 0;
        $to = 9999;
        if ($years) {
            $y_arr = explode('-', $years);
            $from = $y_arr[0] ? $y_arr[0] : 0;
            $to = $y_arr[1] ? $y_arr[1] : 9999;
        }

        $array_total = $data['array_total'];
        $array_world = $data['array_world'];

        foreach ($array_total as $name => $data) {

            ksort($data);
            $result = array();

            $counter = 0;
            foreach ($data as $year => $summ) {
                $summ = round($summ, 0);
                $world = $array_world[$year];
                $wpercent = round(($summ / $world) * 100, 2);
                $result[] = "{ x: " . $year . ", y: " . $summ . ",world:'" . $world . "' ,wpercent: '" . $wpercent . "'}";
                $counter += 1;
                if ($preview_limit > 0 && $counter >= $preview_limit) {
                    break;
                }
            }

            $name_key_theme = $this->population_keys[$name];
            $ethnic = $this->array_ethnic_data[$name_key_theme];

            if ($api_mode) {
                $result_data[] = $this->graphic_api($ethnic['title'], $ethnic['color'], $result);
            } else {
                $result_data[] = $this->graphic_world($ethnic, $result);
            }
        }

        $graph_title = 'World population';
        $x_title = 'Total';
        $y_axis = 'Year';

        // Api result
        if ($api_mode) {

            $ret = array(
                'title' => $graph_title,
                'xtitle' => $x_title,
                'yaxis' => $y_axis,
                'results' => $result_data,
            );
            return $ret;
        }
        ?>
        <script type="text/javascript">
            var search_extend_data = [<?php echo implode('', $result_data); ?>];
        </script>
        <div id="chart_div" 
             data-tab="<?php print $tab_key ?>" 
             data-graph-title="<?php print $graph_title ?>" 
             data-y="<?php print $y_axis ?>" 
             data-xtitle="<?php print $x_title ?>" 
             data-from="<?php print $from ?>" 
             data-to="<?php print $to ?>" >
        </div>

        <div id="select-current">
        </div>
        <?php
    }

    public function show_page_facet_worldmap($search_data, $tab_key = '', $api_mode = false, $preview_limit = 0) {
        $data = $search_data[$tab_key];
        $result_data = array();

        // Filters
        $years = $this->get_filter('year');

        $from = 0;
        $to = 9999;
        if ($years) {
            $y_arr = explode('-', $years);
            $from = $y_arr[0] ? $y_arr[0] : 0;
            $to = $y_arr[1] ? $y_arr[1] : 9999;
        }

        $array_movie_bell = $data['array_movie_bell'];

        foreach ($array_movie_bell as $name => $data) {
            $result_in = array();
            $counter = 0;
            foreach ($data as $cca2 => $val) {
                if ($api_mode) {
                    $result_in[] = array("name" => $val[0], "code2" => $cca2, "value" => $val[2], "year" => $val[3], "content" => $val[1]);
                } else {
                    $result_in[] = "{ name: '" . $val[0] . "', code2: '" . $cca2 . "', value: '" . $val[2] . "', year:'" . $val[3] . "', content:'" . $val[1] . "'}";
                }
                $counter += 1;
                if ($preview_limit > 0 && $counter >= $preview_limit) {
                    break;
                }
            }

            $name_key_theme = $this->population_keys[$name];
            $ethnic = $this->array_ethnic_data[$name_key_theme];

            if ($api_mode) {
                $result_data[] = $this->graphic_api($name, $ethnic['color'], $result_in);
            } else {

                $result_data [] = "{ data: [" . implode(',', $result_in) . "],
                        joinBy: ['iso-a2', 'code2'],
                        name: '" . $name . "',
                        tooltip: {
                            headerFormat: '',
                            pointFormat: '<p>{point.name}</p><br><p>{point.content}</p>'
                        },                
                        color: '" . $ethnic['color'] . "',                
                        },";
            }
        }

        //arsort($array_race);

        $graph_title = 'Ethnic world map';

        // Api result
        if ($api_mode) {

            $ret = array(
                'title' => $graph_title,
                'results' => $result_data,
            );
            return $ret;
        }
        ?>
        <script type="text/javascript">
            var search_extend_data = [<?php echo implode('', $result_data); ?>];
        </script>
        <div id="chart_div" 
             data-tab="<?php print $tab_key ?>" 
             data-graph-title="<?php print $graph_title ?>" 
             data-from="<?php print $from ?>" 
             data-to="<?php print $to ?>" >
        </div>
        <div id="select-current">
        </div>
        <?php
    }

    public function show_page_facet_power($search_data, $tab_key = '', $api_mode = false, $preview_limit = 0) {
        $data = $search_data[$tab_key];

        $data_power = $data['data_power'];
        $all_data_min = $data['data_min'];
        $all_data_max = $data['data_max'];
        $per_capita_min = $data['per_capita_min'];
        $per_capita_max = $data['per_capita_max'];

        $result_in = '';
        $result_in_all = '';
        $r_sort = array();
        $r_sort_all = array();
        foreach ($data_power as $cca2 => $val) {
            $r_sort[$cca2] = $val[1];
            $r_sort_all[$cca2] = $val[2];
            $result_c[$cca2] = "{name:'" . $val[4] . "', y: " . round($val[1], 0) . ", country: '" . $val[0] . "', code2: '" . $cca2 . "'}";
            $result_c_all[$cca2] = "{name:'" . $val[4] . "', y: " . round($val[2], 0) . ", country: '" . $val[0] . "', code2: '" . $cca2 . "'}";
            $result_in .= "{ name: '" . $val[0] . "', code2: '" . $cca2 . "', value: '" . $val[1] . "', year:'" . $val[3] . "', total:'" . $val[2] . "'},";
            $result_in_all .= "{ name: '" . $val[0] . "', code2: '" . $cca2 . "', value: '" . $val[2] . "', year:'" . $val[3] . "', total:'" . $val[1] . "'},";
        }


        arsort($r_sort);
        arsort($r_sort_all);

        $search_extend_c = array();
        $search_extend_c_all = array();
        foreach ($r_sort as $key => $value) {
            $search_extend_c[] = $result_c[$key];
        }
        foreach ($r_sort_all as $key => $value) {
            $search_extend_c_all[] = $result_c_all[$key];
        }

        // Api result
        if ($api_mode) {

            $ret = array();
            return $ret;
        }
        ?>
        <script type="text/javascript">
            var search_extend_c = [<?php echo implode(',', $search_extend_c); ?>];
            var search_extend_c_all = [<?php echo implode(',', $search_extend_c_all); ?>];
            var search_extend_data = [<?php echo $result_in; ?>];
            var search_extend_data_all = [<?php echo $result_in_all; ?>];
        </script>
        <div id="chart_div" 
             data-tab="<?php print $tab_key ?>"   
             data-min="<?php print $per_capita_min ?>"   
             data-max="<?php print $per_capita_max ?>" 
             data-amin="<?php print $all_data_min ?>"   
             data-amax="<?php print $all_data_max ?>">
        </div>
        <div id="chart_div_2"></div>
        <div class="change_stack"></div>
        <div id="select-current">
        </div>
        <?php
    }

    public function show_page_facet_powerrace($search_data, $tab_key = '', $api_mode = false, $preview_limit = 0) {
        $data = $search_data[$tab_key];

        $result_in = '';
        $result_in_all = '';
        $result_in_pop = '';
        $result_in_pop_all = '';

        $array_total = $data['array_total'];
        $yearmin = $data['yearmin'];

        arsort($array_total['t']);
        foreach ($array_total['t'] as $race => $count) {
            $name_key_theme = $this->population_keys[$race];
            $ethnic = $this->array_ethnic_data[$name_key_theme];
            $result_in_all .= "{name:'" . $race . "', y: " . round($count, 0) . ", color: '" . $ethnic['color'] . "'},";
            $result_in_pop_all .= "{name:'" . $race . "', y: " . round($array_total['pop_p'][$race], 0) . ",content:'Population " . $array_total['pop_p'][$race] . "'}, ";
        }

        $array_temp = [];
        foreach ($array_total['p'] as $race => $count) {
            $pop = $array_total['i'][$race];
            $count = $count / $pop;
            $array_temp[$race] = round($count, 0);
        }

        arsort($array_temp);
        foreach ($array_temp as $race => $count) {
            $name_key_theme = $this->population_keys[$race];
            $ethnic = $this->array_ethnic_data[$name_key_theme];
            $result_in .= "{name:'" . $race . "', y: " . $count . ", color: '" . $ethnic['color'] . "'}, ";
            $result_in_pop .= "{name:'" . $race . "', y: " . round($array_total['pop_p'][$race], 0) . ",content:'Population " . $array_total['pop_p'][$race] . "'}, ";
        }
        // Api result
        if ($api_mode) {
            $ret = array();
            return $ret;
        }
        ?>
        <script type="text/javascript">
            var search_extend_c = [<?php echo $result_in_pop; ?>];
            var search_extend_c_all = [<?php echo $result_in_pop_all; ?>];
            var search_extend_data = [<?php echo $result_in; ?>];
            var search_extend_data_all = [<?php echo $result_in_all; ?>];
        </script>
        <div id="chart_div" 
             data-tab="<?php print $tab_key ?>"
             data-yearmin="<?php print $yearmin ?>"
             >
        </div>        
        <div class="change_stack"></div>
        <div id="select-current">
        </div>
        <?php
    }

    public function get_axis_table($axis = '', $total_calc = array(), $movies_count = 0, $races_count = 0, $inflation = false) {
        /*
          print '<pre>';
          print_r($total_calc);
          print '</pre>';
         */
        $mob = false;
        $table = array();
        $after = '';
        if ($axis == 'eth' || $axis == 'wjnw' || $axis == 'wjnwj' || $axis == 'wmjnwm' || $axis == 'wmjnwmj') {
            if ($total_calc['race'][$axis]) {
                // Race
                $table = array(
                    'Race' => array('t' => 's'),
                    'Total' => array('t' => 'd'),
                    'Percent' => array('t' => 'p'),
                    'Average' => array('t' => 'd')
                );
                $races_data = $total_calc['race'][$axis];
                arsort($races_data);
                $i = 0;
                if ($races_data) {
                    foreach ($races_data as $key => $cnt) {
                        $name_key = $this->race_small[$key]['key'];
                        $theme_key = $this->theme_name_key_diversity($name_key, $axis);
                        $theme_item = $this->array_ethnic_data[$theme_key];
                        $ititle = $theme_item['title'];
                        $average = 0;
                        $percent = 0;
                        if ($races_count > 0) {
                            $percent = round(100 * $cnt / $races_count, 2);
                            $average = round($cnt / $movies_count, 0);
                        }
                        $table['Race']['d'][$i] = $ititle;
                        $table['Total']['d'][$i] = $cnt;
                        $table['Percent']['d'][$i] = $percent;
                        $table['Average']['d'][$i] = $average;

                        $i += 1;
                    }
                    // Total
                    $table['Race']['d'][$i] = 'Total';
                    $table['Total']['d'][$i] = $races_count;
                    $table['Percent']['d'][$i] = 100;
                    $table['Average']['d'][$i] = round($races_count / $movies_count, 0);
                }
            }
        } else if ($axis == 'mf') {
            // Race
            if ($total_calc['mf']) {
                $table = array(
                    'Gender' => array('t' => 's'),
                    'Total' => array('t' => 'd'),
                    'Percent' => array('t' => 'p'),
                    'Average' => array('t' => 'd')
                );
                $i = 0;
                foreach ($total_calc['mf'] as $gender => $cnt) {
                    $theme_item = $this->array_ethnic_data[$gender];
                    $ititle = $theme_item['title'];

                    $average = 0;
                    $percent = 0;
                    if ($races_count > 0) {
                        $percent = round(100 * $cnt / $races_count, 2);
                        $average = round($cnt / $movies_count, 0);
                    }

                    $table['Gender']['d'][$i] = $ititle;
                    $table['Total']['d'][$i] = $cnt;
                    $table['Percent']['d'][$i] = $percent;
                    $table['Average']['d'][$i] = $average;
                    $i += 1;
                }

                // Total
                $table['Gender']['d'][$i] = 'Total';
                $table['Total']['d'][$i] = $races_count;
                $table['Percent']['d'][$i] = 100;
                $table['Average']['d'][$i] = round($races_count / $movies_count, 0);
            }
        } else if ($axis == 'actors') {
            if ($movies_count) {
                $table = array(
                    'Name' => array('t' => 's'),
                    'Total' => array('t' => 'd'),
                    'Average' => array('t' => 'd')
                );

                $theme_key = $this->axis[$axis]['name'];
                $theme_item = $this->array_ethnic_data[$theme_key];
                $ititle = $theme_item['title'];
                // Total
                $table['Name']['d'][] = $ititle;
                $table['Total']['d'][] = $races_count;
                $table['Average']['d'][] = round($races_count / $movies_count, 0);
            }
        } else if ($axis == 'simpson') {
            if ($total_calc['race'][$axis]) {
                $races_data = $total_calc['race'][$axis];
                arsort($races_data);

                $table = array(
                    'Race' => array('t' => 's'),
                    'Frequency' => array('t' => 'd'),
                    'Percent' => array('t' => 'p'),
                    'ni(ni-1)' => array('t' => 'd'),
                );
                $total_simpson = 0;
                $i = 0;
                foreach ($races_data as $key => $cnt) {
                    $name_key = $this->race_small[$key]['key'];

                    $theme_key = $this->theme_name_key_diversity($name_key, $axis);

                    $theme_item = $this->array_ethnic_data[$theme_key];

                    $ititle = $theme_item['title'];
                    $average = 0;
                    $percent = 0;
                    if ($races_count > 0) {
                        $percent = round(100 * $cnt / $races_count, 2);
                        $average = round($cnt / $movies_count, 0);
                    }
                    $simpson = $cnt * ($cnt - 1);
                    $total_simpson += $simpson;

                    $table['Race']['d'][$i] = $ititle;
                    $table['Frequency']['d'][$i] = $cnt;
                    $table['Percent']['d'][$i] = $percent;
                    $table['ni(ni-1)']['d'][$i] = $simpson;

                    $i += 1;
                }

                // Total
                $table['Race']['d'][$i] = 'Total';
                $table['Frequency']['d'][$i] = $races_count;
                $table['Percent']['d'][$i] = 100;
                $table['ni(ni-1)']['d'][$i] = $total_simpson;

                $race_diversity_total = 0;
                if ($total_simpson > 0 && $races_count > 0) {
                    $race_diversity_total = 1 - round($total_simpson / ($races_count * ($races_count - 1)), 2);
                }

                $after = '<p>Simpson\'s Diversity = 1 - (' . $total_simpson . ' / (' . $races_count . '*(' . $races_count . '-1))) = <b>' . $race_diversity_total . '</b></p>';
            }
        } else if ($axis == 'simpsonmf') {

            if ($total_calc['racemf'][$axis]) {
                $races_gender_data = $total_calc['racemf'][$axis];

                $table = array(
                    'Race' => array('t' => 's'),
                    'Frequency' => array('t' => 'd'),
                    'Percent' => array('t' => 'p'),
                    'ni(ni-1)' => array('t' => 'd'),
                );
                $total_simpson = 0;
                $i = 0;
                $races_data = array();
                foreach ($races_gender_data as $gender => $race) {
                    if ($gender == 'none') {
                        continue;
                    }
                    foreach ($race as $key => $cnt) {
                        $name_key = $this->race_small[$key]['key'];

                        $theme_key = $this->theme_name_key_diversity($name_key, $axis);
                        $theme_item = $this->array_ethnic_data[$theme_key];
                        $ititle = $theme_item['title'];
                        $gender_name = $ititle . ' ' . $gender;
                        $races_data[$gender_name] = $cnt;
                    }
                }
                arsort($races_data);

                foreach ($races_data as $ititle => $cnt) {

                    $average = 0;
                    $percent = 0;
                    if ($races_count > 0) {
                        $percent = round(100 * $cnt / $races_count, 2);
                        $average = round($cnt / $movies_count, 0);
                    }
                    $simpson = $cnt * ($cnt - 1);
                    $total_simpson += $simpson;

                    $table['Race']['d'][$i] = $ititle;
                    $table['Frequency']['d'][$i] = $cnt;
                    $table['Percent']['d'][$i] = $percent;
                    $table['ni(ni-1)']['d'][$i] = $simpson;

                    $i += 1;
                }

                // Total
                $table['Race']['d'][$i] = 'Total';
                $table['Frequency']['d'][$i] = $races_count;
                $table['Percent']['d'][$i] = 100;
                $table['ni(ni-1)']['d'][$i] = $total_simpson;

                $race_diversity_total = 0;
                if ($total_simpson > 0 && $races_count > 0) {
                    $race_diversity_total = 1 - round($total_simpson / ($races_count * ($races_count - 1)), 2);
                }

                $after = '<p>Simpson\'s Diversity = 1 - (' . $total_simpson . ' / (' . $races_count . '*(' . $races_count . '-1))) = <b>' . $race_diversity_total . '</b></p>';
            }
        } else if ($axis == 'ethmf') {
            if ($total_calc['racemf'][$axis]) {
                $races_gender_data = $total_calc['racemf'][$axis];
                // Race
                $table = array(
                    'Race' => array('t' => 's'),
                    'Total' => array('t' => 'd'),
                    'Percent' => array('t' => 'p'),
                    'Average' => array('t' => 'd')
                );

                $races_data = array();
                foreach ($races_gender_data as $gender => $race) {
                    if ($gender == 'none') {
                        continue;
                    }
                    foreach ($race as $key => $cnt) {
                        $name_key = $this->race_small[$key]['key'];
                        $theme_key = $this->theme_name_key_diversity($name_key, $axis);
                        $theme_item = $this->array_ethnic_data[$theme_key];
                        $ititle = $theme_item['title'];
                        $gender_name = $ititle . ' ' . $gender;
                        $races_data[$gender_name] = $cnt;
                    }
                }

                arsort($races_data);
                $i = 0;
                if ($races_data) {
                    foreach ($races_data as $ititle => $cnt) {
                        $average = 0;
                        $percent = 0;
                        if ($races_count > 0) {
                            $percent = round(100 * $cnt / $races_count, 2);
                            $average = round($cnt / $movies_count, 0);
                        }
                        $table['Race']['d'][$i] = $ititle;
                        $table['Total']['d'][$i] = $cnt;
                        $table['Percent']['d'][$i] = $percent;
                        $table['Average']['d'][$i] = $average;

                        $i += 1;
                    }
                    // Total
                    $table['Race']['d'][$i] = 'Total';
                    $table['Total']['d'][$i] = $races_count;
                    $table['Percent']['d'][$i] = 100;
                    $table['Average']['d'][$i] = round($races_count / $movies_count, 0);
                }
            }
        } else if ($axis == 'boxworld' || $axis == 'boxint' || $axis == 'boxdom' || $axis == 'boxprofit' || $axis == 'budget') {
            // Box data

            $theme_key = $this->axis[$axis]['name'];
            $theme_item = $this->array_ethnic_data[$theme_key];

            if (isset($total_calc[$axis][$theme_key])) {

                $table = array(
                    'Name' => array('t' => 's'),
                    'Total' => array('t' => 'dl'),
                    'Average' => array('t' => 'dl')
                );
                if ($inflation) {
                    $table['Total + Inflation'] = array('t' => 'dl');
                    $table['Average + Inflation'] = array('t' => 'dl');
                    $mob = true;
                }

                $ititle = $theme_item['title'];
                $theme_data = $total_calc[$axis][$theme_key];
                $item_data = $theme_data['d'];
                $average = round($item_data / $movies_count, 0);
                $table['Name']['d'][] = $ititle;
                $table['Total']['d'][] = $item_data;
                $table['Average']['d'][] = $average;
                if ($inflation) {
                    $item_idata = $theme_data['i'];
                    $iaverage = round($item_idata / $movies_count, 0);
                    $table['Total + Inflation']['d'][] = $item_idata;
                    $table['Average + Inflation']['d'][] = $iaverage;
                }
            }
        } else if ($axis == 'rimdb' || $axis == 'rrwt' || $axis == 'rrt' || $axis == 'rrta' || $axis == 'rrtg' || $axis == 'rating' || $axis == 'aurating') {
            // Rating
            $theme_key = $this->axis[$axis]['name'];
            $theme_item = $this->array_ethnic_data[$theme_key];

            if (isset($total_calc[$axis][$theme_key])) {
                $item_data = $total_calc[$axis][$theme_key];
                $table = array(
                    'Name' => array('t' => 's'),
                    'Average' => array('t' => 'd')
                );
                $ititle = $theme_item['title'];
                $average = round($item_data / $movies_count, 2);
                $table['Name']['d'][] = $ititle;
                $table['Average']['d'][] = $average;
            }
        }

        // Theme table

        $this->theme_axis_table($axis, $table, $mob, $after);
    }

    public function theme_axis_table($axis = '', $table = array(), $mob = false, $after = '') {
        if (!$table) {
            return;
        }
        /* print '<pre>';
          print_r($table);
          print '</pre>';
         */
        $title = $this->axis[$axis]['title'];
        $table_class = $axis . 'table';
        $nte = isset($this->axis[$axis]['nte']) ? ' <span data-value="' . $this->axis[$axis]['nte'] . '" class="nte_info"></span>' : '';
        ?>

        <div id="facet-<?php print $table_class ?>" class="facet ajload<?php print $this->cs->hide_facet_class($table_class, $this->filters) ?>">
            <div class="facet-title wacc">
                <h3 class="title"><?php
        print $title;
        print $nte;
        ?></h3>   
                <div class="acc">
                    <div class="chevron icon-down-open"></div>
                    <div class="chevronup icon-up-open"></div>
                </div>
            </div>
            <div class="facet-ch">
                <?php
                if ($mob) {
                    $this->print_mob_styles($table, $table_class);
                }
                ?>
                <table class="analytics_table <?php
        if ($mob) {
            print 'rspv';
        }
                ?> <?php print $table_class ?>">
                    <thead>
                        <tr>
                            <?php
                            foreach ($table as $name => $item) {
                                print '<th class="a_center">' . $name . '</th>';
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $table_count = sizeof(reset($table)['d']);
                        for ($i = 0; $i < $table_count; $i++) {
                            ?>
                            <tr>
                                <?php
                                foreach ($table as $name => $item) {
                                    $class = 'a_right';
                                    $type = $item['t'];
                                    $value = $item['d'][$i];
                                    if ($type == 's') {
                                        $class = 'a_left';
                                    } else if ($type == 'p') {
                                        $value = $value . ' %';
                                    } else if ($type == 'd') {
                                        $value = $this->k_m_b_generator($value);
                                    } else if ($type == 'dl') {
                                        $value = '$ ' . $this->k_m_b_generator($value);
                                    }
                                    ?>
                                    <td class="<?php print $class ?>"><?php print $value ?></td>
                                    <?php
                                }
                                ?>                                                        
                            </tr>    
                        <?php } ?>                        
                    </tbody>
                </table>
                <?php
                if ($after) {
                    print $after;
                }
                ?>
            </div>                    
        </div>
        <?php
    }

    public function show_box_office_table($boi, $bod, $movies_count, $sboi, $sbod, $select_movies_count, $curryear = 0, $currmovie = 0, $currclaster = 0) {

        // Total
        $bow = $boi + $bod;
        $share = round(100 * $bod / $bow, 2);

        // Average
        $bowa = round($bow / $movies_count, 0);
        $boda = round($bod / $movies_count, 0);
        $boia = round($boi / $movies_count, 0);

        // Titles
        $calculate_title = array('Total', 'Average');
        $title_bow = array($bow, $bowa);
        $title_bod = array($bod, $boda);
        $title_boi = array($boi, $boia);
        $title_share = array($share, $share);

        $i = 0;
        if ($curryear || $currmovie || $currclaster) {
            // Select

            $sbow = $sboi + $sbod;
            $sshare = round(100 * $sbod / $sbow, 2);

            // Select Average
            $sbowa = round($sbow / $select_movies_count, 0);
            $sboda = round($sbod / $select_movies_count, 0);
            $sboia = round($sboi / $select_movies_count, 0);

            // Select Average diff
            $sbowad = round((100 * $sbowa / $bowa) - 100, 1);
            $sbodad = round((100 * $sboda / $boda) - 100, 1);
            $sboiad = round((100 * $sboia / $boia) - 100, 1);
            $sshared = round((100 * $sshare / $share) - 100, 1);

            // Titles
            $slug = 'Year';
            if ($currmovie) {
                $slug = 'Movie';
            } else if ($currclaster) {
                $slug = 'Claster';
            }
            $calculate_title = array('Total ' . $slug, 'Average ' . $slug, 'Average Total');
            $title_bow = array($sbow, array($sbowa, $sbowad), $bowa);
            $title_bod = array($sbod, array($sboda, $sbodad), $boda);
            $title_boi = array($sboi, array($sboia, $sboiad), $boia);
            $title_share = array($sshare, array($sshare, $sshared), $share);
            if ($currmovie) {
                $calculate_title = array(1 => 'Total ' . $slug, 2 => 'Average Total');
                $i = 1;
            }
        }

        $table_class = 'fidiv';
        $table = array(
            "Calculate" => $calculate_title,
            "Worldwide Box Office, $" => $title_bow,
            "Domestic Box Office, $" => $title_bod,
            "International Box Office, $" => $title_boi,
            "Domestic Share, %" => $title_share,
        );
        $this->print_mob_styles($table, $table_class);
        ?>
        <table class="analytics_table rspv <?php print $table_class ?>">
            <thead>
                <tr>
                    <?php
                    foreach ($table as $key => $value) {
                        print '<th class="a_center">' . $key . '</th>';
                    }
                    ?>                        
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($calculate_title as $value) {
                    ?>
                    <tr>
                        <?php
                        foreach ($table as $key => $value) {
                            $val = $value[$i];
                            if (!is_string($val)) {
                                if (is_array($val)) {
                                    $percent = $val[1];
                                    $percent_str = '';
                                    if ($percent != 0) {
                                        if ($percent > 0) {
                                            $percent_str = ' (<span class="plus">+' . $percent . '%</span>)';
                                        } else {
                                            $percent_str = ' (<span class="minus">' . $percent . '%</span>)';
                                        }
                                    }
                                    $val = number_format($val[0]) . $percent_str;
                                } else {
                                    $val = number_format($val);
                                }
                            }
                            print '<td class="a_right">' . $val . '</td>';
                        }
                        ?>                        
                    </tr>
                    <?php
                    $i += 1;
                }
                ?>                  
            </tbody>
        </table>
        <?php
    }

    private function graphic_xy($title, $color, $result, $type = 'scatter') {
        $name = '';
        if ($title) {
            $name = "name: '" . addslashes($title) . "',";
        }
        $result_data = "{" . $name . "
                  type:'" . $type . "',
                  color: '" . $color . "', 
                  turboThreshold:0,
                  marker: {radius: 3,},
                  data: [" . implode(',', $result) . "]}";

        return $result_data;
    }

    private function graphic_default($ethnic, $result) {
        $result_data = "{   name: '" . addslashes($ethnic['title']) . "',
                             data: [" . implode(',', $result) . "],
                               color: '" . $ethnic['color'] . "',
                             },";
        return $result_data;
    }

    private function graphic_regression($ethnic, $result, $regression = '') {
        $result_data = "{
                  name: '" . addslashes($ethnic['title']) . "',
                  type:'scatter',
                  color: '" . $ethnic['color'] . "', 
                  turboThreshold:0,
                         marker: {
                            radius: 3,
                        
                        },
                  data: [" . implode(',', $result) . "]},";

        $result_data_bell = '';
        if ($regression) {
            $result_data_bell = "{
                  name: '" . addslashes($ethnic['title']) . " Regression',
                  type:'spline',
                            color: '" . $ethnic['color'] . "',
                    turboThreshold:0,
                  data: [" . implode(',', $regression) . "]},";
        }

        return array('data' => $result_data, 'data_bell' => $result_data_bell);
    }

    private function graphic_bell($ethnic, $result) {
        $result_data = "{
                        id: '" . addslashes($ethnic['title']) . "',
                        name: '" . addslashes($ethnic['title']) . "',
                        type:'scatter',
                        color: '" . $ethnic['color'] . "', 
                        turboThreshold:0,    
                        visible:false,
                        data: [" . implode(',', $result) . "]},";

        $result_data_bell = "{
                        name: '" . addslashes($ethnic['title']) . " ',
                        type: 'bellcurve',
                        xAxis: 0,
                        yAxis: 1,
                            marker: {
                                enabled: false
                            },
                        baseSeries: '" . addslashes($ethnic['title']) . "',
                        zIndex: -1,
                        pointsInInterval: 5,
                        color: '" . $ethnic['color'] . "', 
                        intervals: 4,                       
                    },";
        return array('data' => $result_data, 'data_bell' => $result_data_bell);
    }

    private function graphic_world($ethnic, $result) {
        $result_data = "{
                  name: '" . addslashes($ethnic['title']) . "',
                  type: 'spline',
                  color: '" . $ethnic['color'] . "', 
                  marker: {enabled: false},
                  turboThreshold:0,
                  data: [" . implode(',', $result) . "]},";
        return $result_data;
    }

    private function graphic_api($title = '', $color = '', $result = array()) {
        $ret = array(
            'title' => $title,
            'data' => $result,
        );
        if ($color) {
            $ret['color'] = $color;
        }
        return $ret;
    }

    public function calculate_facet_ethnicity_xy($data, $xaxis = '', $yaxis = '') {
        if (!$data) {
            return '';
        }

        $array_movie_bell = array();
        $movies_count = 0;
        $races_count = 0;
        $total_ret = array();

        // Current filters
        $current = '';
        $curryear = '';
        $currmovie = '';
        $currclaster = '';
        $curr_filter = $this->get_filter('current');
        if ($curr_filter) {
            $current = $this->cs->get_current_type($curr_filter);
            if ($current['type'] == 'y') {
                $curryear = $current['value'];
            } else if ($current['type'] == 'm') {
                $currmovie = $current['value'];
            } else if ($current['type'] == 'c') {
                $currclaster = $current['value'];
            }
        }
        // Cast filter
        $showcast = $this->get_filter_multi('showcast');
        $show_cast_valid = $this->get_show_cast_valid($showcast);

        // Priority                       
        $verdict = $this->get_filter_multi('verdict');
        if (in_array('w', $verdict)) {
            // Custom vedrict weight
            $verdict_mode = 'w';
        } else if (in_array('p', $verdict)) {
            // Custom vedrict priority
            $verdict_mode = 'p';
        } else {
            // Default verdict
            $ss = $this->cm->get_settings();
            $verdict_mode = $ss['an_verdict_type'];
        }

        // Custom priority
        $priority = '';

        $ver_weight = false;
        if ($verdict_mode == 'w') {
            // Weights logic
            $ver_weight = true;
            $weights_arr = $this->get_filter_mode();
            if ($weights_arr['custom']) {
                $priority = $weights_arr['priority'];
            }
        } else {
            // Priority logic
            $priority_arr = $this->get_filter_priority();
            if ($priority_arr['custom']) {
                $priority = $priority_arr['priority'];
            }
        }

        // Combine wite and jews
        $setup = $this->get_filter_multi('setup');
        $combine_wj = false;
        if (in_array('cwj', $setup)) {
            $combine_wj = true;
        }

        // Inflation
        $inflation = false;
        $box_names = array('boxworld', 'boxint', 'boxdom', 'boxprofit', 'budget');
        if (isset($this->axis[$xaxis]['infl']) || isset($this->axis[$yaxis]['infl'])) {
            if (in_array('inflation', $setup)) {
                $inflation = true;
            }
        }

        // Race is active
        $race_active = false;
        $axises = array();
        $xaxis_race = false;
        if (isset($this->axis[$xaxis]['races'])) {
            $race_active = true;
            $axises[] = $xaxis;
            $xaxis_race = true;
        }
        $yaxis_race = false;
        if (isset($this->axis[$yaxis]['races'])) {
            $axises[] = $yaxis;
            $race_active = true;
            $yaxis_race = true;
        }

        gmi('for before');

        foreach ($data as $item) {
            $year = $item->year;
            $id = $item->id;
            $title = $item->title;

            // Inflation logic
            $imod = 1;
            if ($inflation) {
                $imod = $this->get_inflation_modifer($year);
            }

            // Race logic
            $total_item_race = array();
            $total_item_race_mf = array();
            $total_mf = array();

            if ($race_active) {
                //Gender
                $races = explode(',', $item->raceu);
                if ($show_cast_valid[4]) {
                    // Enable production                
                    if ($item->draceu) {
                        $draces = explode(',', $item->draceu);
                        $races = array_merge($races, $draces);
                    }
                }

                $item_race_count = 0;
                foreach ($races as $race) {
                    // Actor type                                      
                    $actor_type_code = substr($race, 1, 1);
                    if (!$this->validate_show_cast($actor_type_code, $show_cast_valid)) {
                        continue;
                    }

                    // Race code
                    /*
                     * (
                     * r.n_ethnic+
                     * r.n_jew*10+
                     * r.n_kairos*100+
                     * r.n_bettaface*1000+
                     * r.n_surname*10000+
                     * r.n_crowdsource*100000+\
                     * r.n_verdict*1000000+
                     * 
                     * m.type*10000000+
                     * r.gender*100000000
                     * )*10000000000+m.aid \
                     */

                    // Verdict
                    if ($priority) {
                        // Custom rules
                        if ($ver_weight) {
                            // Weight logic
                            $race_code = $this->custom_weight_race_code($race, $priority);
                        } else {
                            // Priority logic
                            $race_code = $this->custom_priority_race_code($race, $priority);
                        }
                    } else {
                        if ($ver_weight) {
                            // Weight logic
                            $race_code = (int) substr($race, 3, 1);
                        } else {
                            // Priority logic
                            $race_code = (int) substr($race, 2, 1);
                        }
                    }

                    if (!$race_code) {
                        continue;
                    }

                    $actor_gender = substr($race, 0, 1);

                    $name_gender = 'none';
                    if ($actor_gender == 1 || $actor_gender == 2) {
                        if ($actor_gender == 1) {
                            $name_gender = 'female';
                        } else {
                            $name_gender = 'male';
                        }
                        $total_mf[$name_gender] += 1;
                    }
                    foreach ($axises as $akey) {
                        $race_code_div = $this->get_race_code_diversity($race_code, $akey, $actor_gender, $combine_wj);
                        $total_item_race[$akey][$race_code_div] += 1;
                        $total_item_race_mf[$akey][$name_gender][$race_code_div] += 1;
                        $total_ret['race'][$akey][$race_code_div] += 1;
                        $total_ret['racemf'][$akey][$name_gender][$race_code_div] += 1;
                    }

                    $item_race_count += 1;
                }
                // Total count
                $total_ret['mf']['male'] += $total_mf['male'];
                $total_ret['mf']['female'] += $total_mf['female'];
                $races_count += $item_race_count;
            }


            // Get x-data
            $xdata = $this->get_axis_data($xaxis, $item, $total_item_race, $total_item_race_mf, $total_mf, $item_race_count, $inflation, $imod);

            // Get y-data
            $ydata = $this->get_axis_data($yaxis, $item, $total_item_race, $total_item_race_mf, $total_mf, $item_race_count, $inflation, $imod);

            // Total
            if (!$xaxis_race) {
                foreach ($xdata as $data_key => $data_val) {
                    if (is_array($data_val)) {
                        foreach ($data_val as $data_ckey => $data_cval) {
                            $total_ret[$xaxis][$data_key][$data_ckey] += $data_cval;
                        }
                    } else {
                        $total_ret[$xaxis][$data_key] += $data_val;
                    }
                }
            }
            if (!$yaxis_race) {
                foreach ($ydata as $data_key => $data_val) {
                    if (is_array($data_val)) {
                        foreach ($data_val as $data_ckey => $data_cval) {
                            $total_ret[$yaxis][$data_key][$data_ckey] += $data_cval;
                        }
                    } else {
                        $total_ret[$yaxis][$data_key] += $data_val;
                    }
                }
            }

            if ($xdata && $ydata) {
                $array_movie_bell[$id]['xdata'] = $xdata;
                $array_movie_bell[$id]['ydata'] = $ydata;
                $array_movie_bell[$id]['title'] = $title;
                $array_movie_bell[$id]['year'] = $year;
                $movies_count += 1;
            }
        }

        gmi('for after');

        return array(
            'array_movie_bell' => $array_movie_bell,
            'movies_count' => $movies_count,
            'races_count' => $races_count,
            'total_ret' => $total_ret,
            'curryear' => $curryear,
            'currmovie' => $currmovie,
            'currclaster' => $currclaster,
        );
    }

    public function get_axis_data($axis = '', $item = '', $total_item_race = array(), $total_item_race_mf = array(), $total_mf = array(), $item_race_count = 0, $inflation = false, $imod = 1) {

        $ret = array();
        $name = 'none';
        if ($axis == 'release') {
            $data = $item->release;
            if (!$data) {
                $data = '01-01-' . $item->year;
            }
            $data = (int) strtotime($data);
            $data = $data . '000';
            $name = $this->axis[$axis]['name'];
            $ret[$name] = $data;
        } else if ($axis == 'budget') {
            $data = $item->budget;
            $max_buget = $this->max_budget;
            if ($data > $max_buget) {
                $data = $max_buget;
            }
            $name = $this->axis[$axis]['name'];
            $ret[$name]['d'] = $data;
            if ($inflation) {
                $ret[$name]['i'] = round($data * $imod, 0);
            }
        } else if ($axis == 'boxprofit') {
            $data = $item->boxprofit;
            $max = $this->max_boxprofit;
            $min = $this->min_boxprofit;
            if ($data > $max) {
                $data = $max;
            } else if ($data < $min) {
                $data = $min;
            }
            $name = $this->axis[$axis]['name'];
            $ret[$name]['d'] = $data;
            if ($inflation) {
                $ret[$name]['i'] = round($data * $imod, 0);
            }
        } else if ($axis == 'boxworld' || $axis == 'boxint' || $axis == 'boxdom') {
            if ($axis == 'boxworld') {
                $data = $item->boxworld;
            } else if ($axis == 'boxint') {
                $data = $item->boxint;
            } else if ($axis == 'boxdom') {
                $data = $item->boxusa;
            }
            $name = $this->axis[$axis]['name'];
            $ret[$name]['d'] = $data;
            if ($inflation) {
                $ret[$name]['i'] = round($data * $imod, 0);
            }
        } else if ($axis == 'mf' || $axis == 'eth' || $axis == 'ethmf' || $axis == 'wjnw' || $axis == 'wjnwj' || $axis == 'wmjnwm' || $axis == 'wmjnwmj' || $axis == 'simpson' || $axis == 'simpsonmf' || $axis == 'actors') {

            if ($axis == 'actors') {
                $name = $this->axis[$axis]['name'];
                $ret[$name] = $item_race_count;
            } else if ($item_race_count && $total_item_race[$axis]) {
                if ($axis == 'simpson') {
                    $race_diversity = 0;
                    $ttl = 0;
                    foreach ($total_item_race[$axis] as $r => $c) {
                        if ($c > 0) {
                            $race_diversity += $c * ($c - 1);
                            $ttl += $c;
                        }
                    }
                    $race_diversity_total = 0;
                    if ($ttl > 0 && $race_diversity > 0) {
                        $race_diversity_total = 1 - (round($race_diversity / ($ttl * ($ttl - 1)), 2));
                    }
                    $name = $this->axis[$axis]['name'];
                    $ret[$name] = $race_diversity_total;
                } else if ($axis == 'mf') {
                    foreach ($total_mf as $key => $value) {
                        $ret_percent = 100;
                        if ($item_race_count) {
                            $ret_percent = round(100 * $value / $item_race_count, 2);
                        }
                        $ret[$key]['t'] = $value;
                        $ret[$key]['p'] = $ret_percent;
                    }
                } else if ($axis == 'simpsonmf') {
                    $race_diversity = 0;
                    $ttl = 0;

                    foreach ($total_item_race_mf[$axis] as $gender => $race_item) {
                        foreach ($race_item as $r => $c) {
                            if ($c > 0) {
                                $race_diversity += $c * ($c - 1);
                                $ttl += $c;
                            }
                        }
                    }
                    $race_diversity_total = 0;
                    if ($ttl > 0 && $race_diversity > 0) {
                        $race_diversity_total = 1 - (round($race_diversity / ($ttl * ($ttl - 1)), 2));
                    }
                    $name = $this->axis[$axis]['name'];
                    $ret[$name] = $race_diversity_total;
                } else if ($axis == 'ethmf') {

                    foreach ($total_item_race_mf[$axis] as $gender => $race_item) {
                        foreach ($race_item as $key => $value) {
                            if ($gender == 'none') {
                                continue;
                            }
                            $name_key = $this->race_small[$key]['key'];
                            $theme_key = $this->theme_name_key_diversity($name_key, $axis);
                            if ($gender == 'male') {
                                $theme_key = 'm_' . $theme_key;
                            } else {
                                $theme_key = 'f_' . $theme_key;
                            }
                            $ret_percent = 100;
                            if ($item_race_count) {
                                $ret_percent = round(100 * $value / $item_race_count, 2);
                            }

                            $ret[$theme_key]['t'] = $value;
                            $ret[$theme_key]['p'] = $ret_percent;
                        }
                    }
                } else {
                    foreach ($total_item_race[$axis] as $key => $value) {
                        $name_key = $this->race_small[$key]['key'];
                        $theme_key = $this->theme_name_key_diversity($name_key, $axis);
                        $ret_percent = 100;
                        if ($item_race_count) {
                            $ret_percent = round(100 * $value / $item_race_count, 2);
                        }

                        $ret[$theme_key]['t'] = $value;
                        $ret[$theme_key]['p'] = $ret_percent;
                    }
                }
            }
        } else if ($axis == 'rimdb' || $axis == 'rrwt' || $axis == 'rrt' || $axis == 'rrta' || $axis == 'rrtg' || $axis == 'rating' || $axis == 'aurating') {
            $data = $item->rating;
            $data = round($data / 10, 1);

            if ($axis == 'rrt' || $axis == 'rrta' || $axis == 'rrtg' || $axis == 'aurating') {
                $data = $data * 10;
                if ($axis == 'rrtg') {
                    $data = $data - 100;
                }
            }
            $name = $this->axis[$axis]['name'];
            $ret[$name] = $data;
        }

        return $ret;
    }

    public function get_axis_filter($axis = '', $data = '', $year = '', $inflation = false, $data_type = 'p', $clasters = false) {
        $filter = $data;

        if ($axis == 'rimdb' || $axis == 'rrwt' || $axis == 'rrt' || $axis == 'rrta' || $axis == 'rrtg' || $axis == 'rating' || $axis == 'aurating') {
            // Ratings
            if ($clasters) {
                $data = round($data, 1);
            }
            $filter = round($data, 1) * 10;

            if ($axis == 'rrt' || $axis == 'rrta' || $axis == 'rrtg' || $axis == 'aurating') {
                $data = round($data, 0);
                $filter = $data;
                if ($axis == 'rrtg') {
                    $filter = $data + 100;
                }
            }
        } else if ($axis == 'release') {
            // Date
            $filter = $year;
            if ($clasters) {
                $release = '01-01-' . $year;
                $time = (int) strtotime($release);
                $data = $time * 1000;
            }
        } else if ($axis == 'actors') {
            // Actors
            if ($data > $this->max_actors) {
                $data = $this->max_actors;
            }
            $filter = $data;
        } else if ($axis == 'boxworld' || $axis == 'boxint' || $axis == 'boxdom' || $axis == 'budget' || $axis == 'boxprofit') {
            // Box            
            $box_data = $data['d'];

            if ($inflation) {
                $box_data = $data['i'];
            }

            $data = $box_data;

            $append = 10;
            $delimiter = 10000000;
            if ($axis == 'budget') {
                $delimiter = 1000000;
                $append = 1;
            } else if ($axis == 'boxprofit') {
                $append = 1;
            }
            if ($clasters) {
                $data = round($box_data / $delimiter, 0) * $delimiter;
            }
            $filter = $append * $data / $delimiter;
        } else if ($axis == 'mf' || $axis == 'eth' || $axis == 'ethmf' || $axis == 'wjnw' || $axis == 'wjnwj' || $axis == 'wmjnwm' || $axis == 'wmjnwmj') {
            // Race

            $data = isset($data[$data_type]) ? $data[$data_type] : 0;
            if ($clasters) {
                $data = round($data, 0);
            }
            $filter = round($data, 0);
        } else if ($axis == 'simpson' || $axis == 'simpsonmf') {
            // Race
            $filter = round($data * 100, 0);
        }
        return array('data' => $data, 'filter' => $filter);
    }

    private function column_axis_valid($data, $axis, $actors, $movies) {
        $ydata = $cdata['y'];
        if ($yaxis == 'boxworld' || $yaxis == 'boxint' || $yaxis == 'boxdom' || $yaxis == 'boxprofit' || $yaxis == 'budget') {
            
        } else if ($yaxis == 'mf' || $yaxis == 'eth' || $yaxis == 'ethmf' || $yaxis == 'wjnw' || $yaxis == 'wjnwj' || $yaxis == 'wmjnwm' || $yaxis == 'wmjnwmj') {
            $yt = $ytotal[$key_filter];
            $ydata = 0;
            if ($yt > 0) {
                $ydata = round(100 * $cdata['y'] / $yt, 2);
            }
        } else {
            $yt = $cdata['c'];
            $ydata = 0;
            if ($yt > 0) {
                $ydata = $cdata['y'] / $yt;
            }
        }
    }

    private function get_show_cast_valid($showcast = array()) {
        $show_cast_valid = array(1 => 1, 2 => 1, 3 => 1, 4 => 0);
        if ($showcast) {
            $show_cast_valid = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
            foreach ($showcast as $value) {
                if ($value == 1) {
                    $show_cast_valid[1] = 1;
                }
                if ($value == 2) {
                    $show_cast_valid[2] = 1;
                }
                if ($value == 3) {
                    $show_cast_valid[3] = 1;
                }
                if ($value == 4) {
                    $show_cast_valid[4] = 1;
                }
            }
        }
        return $show_cast_valid;
    }

    private function get_race_code_diversity($race_code, $diversity, $actor_gender, $combine_wj = false) {
        if ($diversity == 'wjnw') {
            // White (+ Jews ) v.s. non-White
            if ($race_code == 8 || $race_code == 1) {
                $race_code = 1;
            } else {
                $race_code = 7;
            }
        } else if ($diversity == 'wjnwj') {
            // White (- Jews ) v.s. non-White (+ Jews)
            if ($race_code != 1) {
                $race_code = 7;
            }
        } else if ($diversity == 'wmjnwm') {
            // White Male (+ Jews ) v.s. non-White Males (+ Female Whites)
            if ($race_code == 8 || $race_code == 1) {
                if ($actor_gender == 2) {
                    $race_code = 1;
                } else {
                    $race_code = 7;
                }
            } else {
                $race_code = 7;
            }
        } else if ($diversity == 'wmjnwmj') {
            // White Male (- Jews ) v.s. non-White Males (+ Jews + Female Whites)
            if ($race_code == 1) {
                if ($actor_gender == 2) {
                    $race_code = 1;
                } else {
                    $race_code = 7;
                }
            } else {
                $race_code = 7;
            }
        }

        if ($combine_wj && $race_code == 8) {
            $race_code = 1;
        }

        return $race_code;
    }

    private function validate_show_cast($actor_type_code, $show_cast_valid) {
        if ($actor_type_code == 1 && !$show_cast_valid[1] ||
                $actor_type_code == 2 && !$show_cast_valid[2] ||
                $actor_type_code == 3 && !$show_cast_valid[3] ||
                $actor_type_code == 4 && !$show_cast_valid[4]) {
            return false;
        }
        return true;
    }

    public function get_population_data() {
        $ma = $this->get_ma();
        $population = $ma->get_population();
        /*
         * Population
          [0] => stdClass Object
          (
          [id] => 1
          [country_name] => Aruba
          [official] => Aruba
          [cca2] => AW
          [cca3] => ABW
          [population_data] => {"2020":106766,"2019":106314,"2050":108716,"2030":110247,"2015":104341,"2010":101669,"2000":90853,"1990":62149,"1980":60096,"1970":59063}
          [populatin_by_year] => {"1960":54211,"1961":55438,"1962":56225,"1963":56695,"1964":57032,"1965":57360,"1966":57715,"1967":58055,"1968":58386,"1969":58726,"1970":59063,"1971":59440,"1972":59840,"1973":60243,"1974":60528,"1975":60657,"1976":60586,"1977":60366,"1978":60103,"1979":59980,"1980":60096,"1981":60567,"1982":61345,"1983":62201,"1984":62836,"1985":63026,"1986":62644,"1987":61833,"1988":61079,"1989":61032,"1990":62149,"1991":64622,"1992":68235,"1993":72504,"1994":76700,"1995":80324,"1996":83200,"1997":85451,"1998":87277,"1999":89005,"2000":90853,"2001":92898,"2002":94992,"2003":97017,"2004":98737,"2005":100031,"2006":100834,"2007":101222,"2008":101358,"2009":101455,"2010":101669,"2011":102046,"2012":102560,"2013":103159,"2014":103774,"2015":104341,"2016":104872,"2017":105366,"2018":105845,"2019":106314,"2020":null}
          [region] => Americas
          [subregion] => Caribbean
          [latlng] => <a target="_blank" href="https://www.google.com/maps/@12.5,-69.96666666,10z">12.5,-69.96666666</a>
          [ethnicdata] => Aruban 66%, Colombian 9.1%, Dutch 4.3%, Dominican 4.1%, Venezuelan 3.2%, Curacaoan 2.2%, Haitian 1.5%, Surinamese 1.2%, Peruvian 1.1%, Chinese 1.1%, other 6.2%
          [ethnic_array] => {"Aruban":66,"Colombian":9.1,"Dutch":4.3,"Dominican":4.1,"Venezuelan":3.2,"Curacaoan":2.2,"Haitian":1.5,"Surinamese":1.2,"Peruvian":1.1,"Chinese":1.1,"Caribbean":6.2}
          [ethnic_array_result] => {"Mixed \/ Other":72.3,"Latino":13.4,"Indigenous":6.2,"White":5.5,"Black":1.5,"Asian":1.1}
          [jew_data] =>
          )
         */

        // Filters
        $years = $this->get_filter('year');
        $from = 0;
        $to = 0;
        if ($years) {
            $y_arr = explode('-', $years);
            $from = $y_arr[0] ? $y_arr[0] : 0;
            $to = $y_arr[1] ? $y_arr[1] : 0;
        }

        $array_country = array();
        $array_total = array();
        $array_world = array();
        $array_total_country = array();
        $year_facet = array();
        $current_year = date('Y', time());

        foreach ($population as $item) {

            $country = $item->country_name;

            // $array_result = json_decode($item->ethnic_array);

            $population_data_result = array();
            if ($item->population_data) {
                $population_data_result = json_decode($item->population_data, JSON_FORCE_OBJECT);
            }

            $populatin_by_year_result = array();
            if ($item->populatin_by_year) {
                $populatin_by_year_result = json_decode($item->populatin_by_year, JSON_FORCE_OBJECT);
            }

            $populatin_result = array();
            if ($population_data_result) {
                foreach ($population_data_result as $year => $data) {
                    if ($year > $current_year) {
                        $populatin_result[$year] = $data;
                    }
                }
            }

            if ($populatin_by_year_result) {
                foreach ($populatin_by_year_result as $year => $data) {
                    if ($data > 0) {
                        $populatin_result[$year] = $data;
                    }
                }
            }

            if ($item->ethnic_array_result) {
                $ethnic_array_result = json_decode($item->ethnic_array_result, JSON_FORCE_OBJECT);
            }

            if ($populatin_result) {
                foreach ($populatin_result as $year => $summ) {
                    $array_total_country[$year][$country] += $summ;
                    $year_facet[$year] += $summ;
                    if ($from && $year < $from) {
                        continue;
                    }

                    if ($to && $year > $to) {
                        continue;
                    }

                    foreach ($ethnic_array_result as $race => $count) {
                        if ($count > 0) {
                            $summ_result = ($count * $summ / 100);
                            $array_total[$race][$year] += $summ_result;
                            $array_world[$year] += $summ_result;
                            $array_country[$year][$race][$country] += $summ_result;
                        }
                    }
                }
            }
        }
        $count = sizeof($population);

        $data = array(
            'array_country' => $array_country,
            'array_total' => $array_total,
            'array_world' => $array_world,
            'array_total_country' => $array_total_country,
            'count' => $count,
        );
        $data['facets']['year']['data'] = $year_facet;

        return $data;
    }

    public function get_worldmap_data() {
        $ma = $this->get_ma();
        $population = $ma->get_population();
        $array_compare = $ma->get_array_compare();

        // Filters
        $years = $this->get_filter('year');
        $from = 0;
        $to = 0;
        if ($years) {
            $y_arr = explode('-', $years);
            $from = $y_arr[0] ? $y_arr[0] : 0;
            $to = $y_arr[1] ? $y_arr[1] : 0;
        }

        $year_facet = array();
        $populatin_result = array();
        $array_country_data = array();
        $array_country = array();
        $array_race = array();
        $array_movie_bell = array();
        $current_year = date('Y', time());

        $array_code = array('XK' => 'KV');

        foreach ($population as $item) {

            $country = $item->country_name;
            $cca3 = $item->cca3;
            $cca2 = $item->cca2;

            if ($array_code[$cca2]) {
                $cca2 = $array_code[$cca2];
            }

            $ethnic_array_result = $item->ethnic_array_result;
            if ($ethnic_array_result) {
                $ethnic_array_result = (array) json_decode($ethnic_array_result, JSON_FORCE_OBJECT);
                $key = array_keys($ethnic_array_result);
                $value = $key[0];

                $ethnic_country = '';
                foreach ($ethnic_array_result as $race => $count) {
                    $ethnic_country .= $race . ' : ' . $count . '<br>';
                }
            }

            $population_data = $item->population_data;
            $population_data_result = (array) json_decode($population_data);

            $populatin_by_year = $item->populatin_by_year;
            $populatin_by_year_result = (array) json_decode($populatin_by_year);

            $populatin_result = array();

            foreach ($population_data_result as $year => $data) {
                if ($year > $current_year) {
                    $populatin_result[$year] = $data;
                }
            }

            foreach ($populatin_by_year_result as $year => $data) {
                if ($data > 0) {
                    $populatin_result[$year] = $data;
                }
            }

            $last_summ = 0;
            $last_year = 0;

            foreach ($populatin_result as $year => $summ) {
                $year_facet[$year] += $summ;

                if ($from && $year < $from) {
                    continue;
                }

                if ($to && $year > $to) {
                    continue;
                }



                $last_summ = $summ;
                $last_year = $year;
            }

            if ($value) {
                // $array_movie_bell[$value][$cca2] = array($country, $ethnic_country, $last_summ, $last_year);
            }

            $ethnic_array = $item->ethnic_array;
            $array_result = (array) json_decode($ethnic_array);
            $content = array();
            $arry_total = array();

            arsort($array_result);

            $next = 0;

            foreach ($array_result as $index => $val) {

                $index = trim($index);
                $index = strtolower($index);
                $index = ucfirst($index);

                if ($array_compare[$index]) {
                    $race = $array_compare[$index];

                    $arry_total[$race] += $val;
                    $next = 1;
                } else if ($array_compare[$country]) {
                    $array_race[$index] = $array_compare[$country];
                    $race = $array_compare[$country];
                    $arry_total[$race] += $val;
                    $next = 1;
                } else {
                    $array_country[$country][$index]++;
                    $array_country_data[$country] = $item->region . ' ' . $item->subregion . ' ' . $item->latlng;
                }
                $content[$index] += $val;
            }
            arsort($content);
            $content_string = $ethnic_country . '<br>----- Ethnic data -----<br>';
            foreach ($content as $race => $val) {
                $content_string .= $race . ' (' . $array_compare[$race] . ') : ' . $val . ' %<br>';
            }
            arsort($arry_total);
            $key = array_keys($arry_total);
            $value = $arry_total[$key[0]];

            if ($next) {
                $array_movie_bell[$key[0]][$cca2] = array($country, $content_string, $last_summ, $last_year);
                // echo $country . ' ' . $key[0] . '-' . $value . '<br>';
            }
        }

        $count = sizeof($population);
        $data = array(
            'array_movie_bell' => $array_movie_bell,
            'count' => $count,
        );
        $data['facets']['year']['data'] = $year_facet;
        return $data;
    }

    public function get_power_data() {
        $ma = $this->get_ma();
        $population = $ma->get_population('', true);

        $data_power = [];
        $per_capita_max = 0;
        $per_capita_min = -1;

        $all_data_max = 0;
        $all_data_min = -1;

        $array_code = array('XK' => 'KV');

        foreach ($population as $item) {

            $country = $item->name;
            $cca2 = $item->cca2;
            $cca3 = strtolower($item->cca3);

            if ($array_code[$cca2]) {
                $cca2 = $array_code[$cca2];
            }

            $per_capita = $item->per_capita;

            if ($per_capita > $per_capita_max) {
                $per_capita_max = $per_capita;
            }
            if ($per_capita_min == -1 || $per_capita < $per_capita_min) {
                $per_capita_min = $per_capita;
            }

            $total = $item->total;

            if ($total > $all_data_max) {
                $all_data_max = $total;
            }
            if ($all_data_min == -1 || $total < $all_data_min) {
                $all_data_min = $total;
            }

            $date = $item->date;
            $data_power[$cca2] = array($country, $per_capita, $total, $date, $cca3);
        }

        $count = sizeof($population);
        $data = array(
            'data_power' => $data_power,
            'data_min' => $all_data_min,
            'data_max' => $all_data_max,
            'per_capita_min' => $per_capita_min,
            'per_capita_max' => $per_capita_max,
            'count' => $count,
        );

        return $data;
    }

    public function get_power_race_data() {

        $ma = $this->get_ma();
        $population = $ma->get_population('', true);

        $array_total = [];
        $yearmin = 0;
        $yaerstart = 2010;

        foreach ($population as $item) {
            $country = $item->name;
            $cca2 = $item->cca2;
            $per_capita = $item->per_capita;
            $total = $item->total;

            $year = $item->date;
            if ($year > $yearmin) {
                $yearmin = $year;
            }

            $populatin_by_year = $item->populatin_by_year;
            $populatin_by_year_result = json_decode($populatin_by_year, JSON_FORCE_OBJECT);
            $pop = $populatin_by_year_result[$yaerstart];
            $ethnic_array_result = $item->ethnic_array_result;

            if ($ethnic_array_result && $per_capita) {
                $ethnic_array_result = json_decode($ethnic_array_result, JSON_FORCE_OBJECT);
                foreach ($ethnic_array_result as $e => $count) {
                    $summ_population = ($total / $per_capita) * ($count / 100);
                    $summ_country = $summ_population * $per_capita;
                    $array_total['p'][$e] += $summ_country;
                    $array_total['t'][$e] += $count * $total / 100;
                    $array_total['i'][$e] += $summ_population;
                    $array_total['pop_p'][$e] += $count * $pop / 100;
                    $array_total['pop_all'][$e] += $pop;
                }
            }
        }

        $count = sizeof($population);
        $data = array(
            'array_total' => $array_total,
            'yearmin' => $yearmin,
            'count' => $count,
        );

        return $data;
    }

    public function get_race_data($year = '', $race = '') {
        $ma = $this->get_ma();
        $population = $ma->get_population();

        $array_total = array();
        $array_country_data = array();
        $ethnic_array_result = array();
        $ethnic_array = array();
        $array_total_summ = array();
        $current_year = date('Y', time());
        $content = '';
        $country_titles = array();

        foreach ($population as $item) {
            $country = $item->country_name;
            $country_key = $item->cca2;
            $country_titles[$country_key] = $country;

            $population_data_result = array();
            if ($item->population_data) {
                $population_data_result = json_decode($item->population_data, JSON_FORCE_OBJECT);
            }

            $populatin_by_year_result = array();
            if ($item->populatin_by_year) {
                $populatin_by_year_result = json_decode($item->populatin_by_year, JSON_FORCE_OBJECT);
            }

            $ethnic_array_result = array();
            if ($item->ethnic_array_result) {
                $ethnic_array_result = json_decode($item->ethnic_array_result, JSON_FORCE_OBJECT);
            }

            $summ = 0;
            $data = $population_data_result[$year];

            if ($year > $current_year) {
                $summ = $data;
            }
            $data = $populatin_by_year_result[$year];

            if ($data > 0) {
                $summ = $data;
            }

            $array_total_summ['world'] += $summ;

            $count = $ethnic_array_result[$race];

            if ($count > 0) {
                $summ_result = $count * $summ / 100;
                $array_total[$country_key] += $summ;

                foreach ($ethnic_array_result as $i => $v) {
                    $ethnic_array[$i] += $count * $v / 100;
                }

                $array_country_data[$country_key] = array('ethnic' => $ethnic_array_result);
            }
        }

        arsort($array_total);
        $id = 0;
        arsort($ethnic_array);

        foreach ($array_total as $country_key => $summ) {
            $id++;
            $cnt = '';
            if (is_array($ethnic_array_result)) {

                foreach ($ethnic_array as $e => $enable) {
                    $percent = $array_country_data[$country_key]['ethnic'][$e];
                    if (!$percent)
                        $percent = 0;

                    if ($e == $race) {
                        $cnt .= '<td class="a_right">' . $percent . '</td>'
                                . '<td class="a_right">' . number_format(round($percent * $summ) / 100) . '</td>';
                        $array_total_summ[$e] += round($percent * $summ) / 100;
                    }
                }
            }
            if ($summ) {
                $content .= '<tr>'
                        . '<td class="a_right">' . $id . '</td>'
                        . '<td class="a_right">' . $country_titles[$country_key] . '</td>'
                        . '<td class="a_right">' . number_format(round($summ)) . '</td>' . $cnt . ''
                        . '<td class="a_right more wacc"><div class="acc collapsed" data-more="' . $country_key . '"><div class="chevron icon-down-open"></div><div class="chevronup icon-up-open"></div></div></td>'
                        . '</tr>';
            }
        }

        $colspan = 4;

        $titles = array('№' => 1, 'Country' => 1, 'Population' => 1);

        foreach ($ethnic_array as $e => $enable) {
            if ($e == $race) {
                $titles[$race . ' %'] = 1;
                $titles[$race . ' total'] = 1;
                $colspan += 2;
            }
        }
        $titles['More'] = 10;

        $table_class = 'rracedata';
        $this->print_mob_styles($titles, $table_class);

        $footer_inner = '';
        foreach ($array_total_summ as $e => $summ) {
            $summ = round($summ);
            $world = $array_total_summ['world'];
            $percent = 100;
            if ($world) {
                $percent = round(($summ / $world) * 100, 2);
            }
            $footer_inner .= '<td class="a_right">' . $percent . '</td>'
                    . '<td class="a_right">' . number_format($summ) . '</td>';
        }

        $footer = '<tr>'
                . '<td class="a_right">Total</td>' . $footer_inner . '<td>&nbsp;</td>'
                . '</tr>';

        echo '<h3>' . $race . ' (year: ' . $year . ')</h3>';
        echo '<table  class="antable analytics_table rspv ' . $table_class . '">'
        . '<thead>'
        . '<tr>';
        foreach ($titles as $title => $count) {
            $class = '';
            if ($count == 10) {
                $class = ' class="more" ';
            }
            print '<th' . $class . '>' . $title . '</th>';
        }
        echo '</tr>'
        . '</thead>'
        . '<tbody>' . $content . '</tbody>
    <tfoot>' . $footer . '</tfoot>
    </table>';
    }

    public function get_country_data($country_key = '', $cur_year = '', $from = 0, $to = 0) {

        $ma = $this->get_ma();
        $item = $ma->get_population($country_key);
        $array_compare = $ma->get_array_compare();

        /*
          stdClass Object
          (
          [id] => 101
          [country_name] => India
          [official] => Republic of India
          [cca2] => IN
          [cca3] => IND
          [population_data] => {"2020":1380004385,"2019":1366417754,"2050":1639176033,"2030":1503642322,"2015":1310152403,"2010":1234281170,"2000":1056575549,"1990":873277798,"1980":698952844,"1970":555189792}
          [populatin_by_year] => {"1960":450547679,"1961":459642165,"1962":469077190,"1963":478825608,"1964":488848135,"1965":499123324,"1966":509631500,"1967":520400576,"1968":531513824,"1969":543084336,"1970":555189792,"1971":567868018,"1972":581087256,"1973":594770134,"1974":608802600,"1975":623102897,"1976":637630087,"1977":652408776,"1978":667499806,"1979":682995354,"1980":698952844,"1981":715384993,"1982":732239504,"1983":749428958,"1984":766833410,"1985":784360008,"1986":801975244,"1987":819682102,"1988":837468930,"1989":855334678,"1990":873277798,"1991":891273209,"1992":909307016,"1993":927403860,"1994":945601831,"1995":963922588,"1996":982365243,"1997":1000900030,"1998":1019483581,"1999":1038058156,"2000":1056575549,"2001":1075000085,"2002":1093317189,"2003":1111523144,"2004":1129623456,"2005":1147609927,"2006":1165486291,"2007":1183209472,"2008":1200669765,"2009":1217726215,"2010":1234281170,"2011":1250288729,"2012":1265782790,"2013":1280846129,"2014":1295604184,"2015":1310152403,"2016":1324509589,"2017":1338658835,"2018":1352617328,"2019":1366417754,"2020":null}
          [region] => Asia
          [subregion] => Southern Asia
          [latlng] => <a target="_blank" href="https://www.google.com/maps/@20,77,6z">20,77</a>
          [ethnicdata] => Indo-Aryan 72%, Dravidian 25%, Mongoloid and other 3%
          [ethnic_array] => {"Indo":72,"Dravidian":25,"Mongoloid":3}
          [ethnic_array_result] => {"Dark Asian":97,"Asian":3}
          [jew_data] => {"core":"4900","connected":"6000","enlarged":"7000","eligible":"8000","official":"4650"}
          )
         */

        if (!$item) {
            return '';
        }

        $name = $country_key;
        $cca2 = $item->cca2;
        $jew_data = $item->jew_data;
        $cca3 = $item->cca3;

        $population_data = $item->population_data;
        $population_data_result = (array) json_decode($population_data);

        $populatin_by_year = $item->populatin_by_year;
        $populatin_by_year_result = (array) json_decode($populatin_by_year);

        $populatin_result = array();
        $current_year = date('Y', time());

        foreach ($population_data_result as $year => $data) {
            if ($year >= $from && $year <= $to) {
                $populatin_result[$year] = $data;
            }
        }

        foreach ($populatin_by_year_result as $year => $data) {
            if ($data > 0) {
                if ($year >= $from && $year <= $to) {
                    $populatin_result[$year] = $data;
                }
            }
        }

        echo '<h1 style="margin-top: 20px"><span><img style="width: 50px" src="/analysis/country_data/' . strtolower($cca3) . '.svg"/></span> ' . $item->country_name . '</h1>';
        $data_array = $item->ethnic_array_result;
        echo '<p style="font-size: 15px;">Ethnic: ' . $item->ethnicdata . '</p>';
        echo '<a class="source_link" target="_blank" href="https://www.cia.gov/library/publications/the-world-factbook/fields/400.html#' . $cca2 . '">Source: https://www.cia.gov</a><br><br>';

        // show ethnic
        $ethnic_array = $item->ethnic_array;
        $actor_heder = array('Ethnic Groups' => 1);
        $actor_result = '';
        $actor_result_year = '';
        $actor_race_type = '';

        if ($ethnic_array) {
            $array_result = (array) json_decode($ethnic_array);

            $content = '';
            $arry_total = [];
            arsort($array_result);
            $next = 0;
            if (!$cur_year) {
                $cur_year = array_key_first($populatin_result);
            }
            foreach ($array_result as $index => $val) {

                $index = trim($index);
                $index = strtolower($index);
                $index = ucfirst($index);

                if ($array_compare[$index]) {
                    $race = $array_compare[$index];
                } else if ($array_compare[$item->country_name]) {
                    $race = $array_compare[$item->country_name];
                }

                $actor_heder[$index] = 1;
                $actor_race_type .= '<td>' . $race . '</td>';
                $actor_result .= '<td>' . $val . '%</td>';

                if ($cur_year) {
                    $actor_result_year .= '<td>' . number_format($val * $populatin_result[$cur_year] / 100) . '</td>';
                }
            }
            $table_class = 'rcompare';
            $this->print_mob_styles($actor_heder, $table_class);
            $actor_content_race = '<table  class="analytics_table rspv ' . $table_class . '">
                <thead>
                    <tr><th>' . implode('</th><th>', array_keys($actor_heder)) . '</th></tr>
                </thead>
                <tbody>
                    <tr class="actor_data"><td>Result</td>' . $actor_race_type . '</tr>
                    <tr class="actor_data"><td>Percent</td>' . $actor_result . '</tr>';
            if ($cur_year) {
                $actor_content_race .= '<tr class="actor_data"><td>Total population by year (' . $cur_year . ')</td>' . $actor_result_year . '</tr>';
            }
            $actor_content_race .= '</tbody></table>';
            echo $actor_content_race;
        }

        if ($jew_data) {
            $data = (array) json_decode($jew_data);
            $actor_heder = array('Jewish Population' => 1);
            $actor_result = '';
            $actor_result_year = '';
            if (!$cur_year) {
                $cur_year = array_key_first($populatin_result);
            }
            foreach ($data as $name => $jew_count) {

                if ($jew_count > 0) {
                    if ($populatin_result) {
                        $population = $populatin_result[$cur_year];
                        if ($population && $jew_count) {
                            $jew_percent = ($jew_count / $population) * 100;
                            $jew_percent = round($jew_percent, 4);
                        }
                    }
                }

                $actor_heder[$name] = 1;
                $actor_result .= '<td>' . $jew_percent . '%</td>';
                if ($cur_year) {
                    $actor_result_year .= '<td>' . number_format($jew_percent * $populatin_result[$cur_year] / 100) . '</td>';
                }
            }

            $table_class = 'rjew';
            $this->print_mob_styles($actor_heder, $table_class);

            $actor_content_jew = '<table  class="analytics_table rspv ' . $table_class . '">
                <thead>
                    <tr><th>' . implode('</th><th>', array_keys($actor_heder)) . '</th></tr>
                </thead>
                <tbody>
                    <tr class="actor_data"><td>Percent</td>' . $actor_result . '</tr>
                    <tr class="actor_data"><td>Total population by year (' . $cur_year . ')</td>' . $actor_result_year . '</tr>
                </tbody>
                </table>';
            echo $actor_content_jew;
            echo '<a class="source_link" target="_blank" href="https://en.wikipedia.org/wiki/Jewish_population_by_country">Source: https://en.wikipedia.org/wiki/Jewish_population_by_country</a><br><br>';
        }

        if ($data_array) {
            $actor_heder = array('Final Results' => 1);
            $actor_result = '';
            $actor_result_year = '';
            $data = (array) json_decode($data_array);
            foreach ($data as $name => $summ) {
                $actor_heder[$name] = 1;
                $actor_result .= '<td>' . $summ . '%</td>';
                $actor_result_year .= '<td>' . number_format($summ * $populatin_result[$cur_year] / 100) . '</td>';
            }

            $table_class = 'rethnic';
            $this->print_mob_styles($actor_heder, $table_class);
            $actor_content = '<table  class="analytics_table rspv ' . $table_class . '">
                <thead>
                    <tr><th>' . implode('</th><th>', array_keys($actor_heder)) . '</th></tr>
                </thead>
                <tbody>
                    <tr class="actor_data"><td>Percent</td>' . $actor_result . '</tr>';
            if ($cur_year) {
                $actor_content .= '<tr class="actor_data"><td>Total population by year (' . $cur_year . ')</td>' . $actor_result_year . '</tr>';
            }
            $actor_content .= '</tbody></table>';
            echo $actor_content . '<br>';
        }

        $result_in = '';
        ksort($populatin_result);
        foreach ($populatin_result as $year => $summ) {
            $summ = round($summ, 0);
            $result_in .= "{ x: " . $year . ", y: " . $summ . " },";
        }

        $result_data .= "{
                    name: '" . $item->country_name . " population',
                    type: 'spline',                  
                    marker: {            enabled: false        },
                    turboThreshold:0,
                    data: [" . $result_in . "]},";

        // graph
        ?>
        <div id="country_div_<?php echo $item->id ?>" style="width: 100%; height: 400px"></div>
        <br>
        <?php
        echo '<a class="source_link" target="_blank" href="https://worldpopulationreview.com/">Source: https://worldpopulationreview.com</a><br>
        <a class="source_link" target="_blank" href="https://datatopics.worldbank.org/world-development-indicators/themes/people.html">Source: https://datatopics.worldbank.org</a><br><br>';
        ?>
        <script type="text/javascript">

            Highcharts.chart('country_div_<?php echo $item->id ?>', {
                chart: {
                    zoomType: 'xy',
                },
                title: {
                    text: '<?php echo $item->country_name ?> population'
                },

                xAxis: {
                    title: {
                        text: 'Year',

                    },
                },
                yAxis: {
                    title: {
                        text: 'Total',

                    },
                },
                legend: {
                    enabled: false
                },

                plotOptions: {
                    series: {
                        cursor: 'pointer',
                    },

                },

                series: [<?php echo $result_data; ?>]
            });

        </script>
        <?php
    }

    public function get_movie($mid) {
        // Select movie
        if ($mid) {
            global $post_id;
            $post_id = $mid;
            if (!function_exists('template_single_movie')) {
                require ABSPATH . 'wp-content/themes/custom_twentysixteen/template/movie_single_template.php';
            }
            template_single_movie($mid, '', '', 1);
            include ABSPATH . 'wp-content/themes/custom_twentysixteen/template/actors_template_single.php';
            show_actors_template_single_cache();
        }
    }

    public function print_mob_styles($table = array(), $table_class = 'rsdiv') {
        ?>
        <style  type='text/css' media='all'>
        <?php
        print '@media only screen and (max-width: 760px), (min-device-width: 768px) and (max-device-width: 1024px)  {';
        $i = 1;
        foreach ($table as $name => $count) {
            if ($count != 'none') {
                print "." . $table_class . " td:nth-of-type(" . $i . "):before { content: \"" . $name . "\"; }\n";
            }
            $i += 1;
        }
        print '}';
        ?>
        </style>
        <?php
    }

    public function theme_name_key_diversity($name_key = '', $diversity = '') {
        if ($diversity == 'wjnw') {
            if ($name_key == 'w') {
                $name_key = 'wj';
            } else {
                $name_key = 'nw';
            }
        } else if ($diversity == 'wjnwj') {
            if ($name_key == 'w') {
                $name_key = 'w-j';
            } else {
                $name_key = 'nwj';
            }
        } else if ($diversity == 'wmjnwm') {
            if ($name_key == 'w') {
                $name_key = 'wmj';
            } else {
                $name_key = 'nwf';
            }
        } else if ($diversity == 'wmjnwmj') {
            if ($name_key == 'w') {
                $name_key = 'wm-j';
            } else {
                $name_key = 'nw';
            }
        }
        return $name_key;
    }

    public function custom_priority_race_code($race = 0, $priority = array()) {
        /*
          // Other codes
          $race_setup = array();
          $race_setup['crowdsource'] = (int) substr($race, 3, 1);
          $race_setup['ethnic'] = (int) substr($race, 8, 1);
          $race_setup['jew'] = (int) substr($race, 7, 1);
          $race_setup['kairos'] = (int) substr($race, 6, 1);
          $race_setup['bettaface'] = (int) substr($race, 5, 1);
          $race_setup['surname'] = (int) substr($race, 4, 1);
         * 
          'c' => array('title' => 'Crowdsource', 'titlehover' => 'Crowdsource'),
          'e' => array('title' => 'Ethnicelebs', 'titlehover' => 'Ethnicelebs'),
          'j' => array('title' => 'JewOrNotJew', 'titlehover' => 'JewOrNotJew'),
          'k' => array('title' => 'Kairos', 'titlehover' => 'Facial Recognition by Kairos'),
          'b' => array('title' => 'Betaface', 'titlehover' => 'Facial Recognition by Betaface'),
          's' => array('title' => 'Surname', 'titlehover' => 'Surname Analysis')
         */

        $race_code = 0;
        if ($priority) {
            foreach ($priority as $key => $active) {
                if ($active > 0) {
                    $race_code = $this->get_race_by_key($race, $key);
                    if ($race_code) {
                        break;
                    }
                }
            }
        }

        return $race_code;
    }

    public function custom_weight_race_code($race = 0, $race_weight = array(), $onlydata = 0, $debugs = '') {
        if ($debugs) {

            if (function_exists('var_dump_table')) {
                var_dump_table(array('race' => $race, 'race_weight' => $race_weight));
            }
        }
        /*
          // Other codes
          $race_setup = array();
          $race_setup['crowdsource'] = (int) substr($race, 3, 1);
          $race_setup['ethnic'] = (int) substr($race, 8, 1);
          $race_setup['jew'] = (int) substr($race, 7, 1);
          $race_setup['kairos'] = (int) substr($race, 6, 1);
          $race_setup['bettaface'] = (int) substr($race, 5, 1);
          $race_setup['surname'] = (int) substr($race, 4, 1);
         * 
          public $race_weight_priority = array(
          'c' => array('w' => 1, 'ea' => 1, 'h' => 1, 'b' => 1, 'i' => 1, 'm' => 1, 'mix' => 1, 'jw' => 1),
          'e' => array('w' => 1, 'ea' => 1, 'h' => 1, 'b' => 1, 'i' => 1, 'm' => 1, 'mix' => 1, 'jw' => 1),
          'j' => array('w' => 1, 'ea' => 1, 'h' => 1, 'b' => 1, 'i' => 1, 'm' => 1, 'mix' => 1, 'jw' => 1),
          'k' => array('w' => 1, 'ea' => 1, 'h' => 1, 'b' => 1, 'i' => 1, 'm' => 1, 'mix' => 1, 'jw' => 1),
          'b' => array('w' => 1, 'ea' => 1, 'h' => 1, 'b' => 1, 'i' => 1, 'm' => 1, 'mix' => 1, 'jw' => 1),
          'i' => array('w' => 1, 'ea' => 1, 'h' => 1, 'b' => 1, 'i' => 1, 'm' => 1, 'mix' => 1, 'jw' => 1),
          'f' => array('w' => 1, 'ea' => 1, 'h' => 1, 'b' => 1, 'i' => 1, 'm' => 1, 'mix' => 1, 'jw' => 1),
          's' => array('w' => 1, 'ea' => 1, 'h' => 1, 'b' => 1, 'i' => 1, 'm' => 1, 'mix' => 1, 'jw' => 1)
          );
         */

        $race_code_ret = 0;
        $type_calc = 0;
        $result_summ = array();
        $result_top = array();
        $debug = array();
        if ($race_weight) {
            // Type calc
            if (isset($race_weight['t'])) {
                $type_calc = $race_weight['t'];
            }
            foreach ($race_weight as $key => $row) {
                if ($key == 't') {
                    continue;
                }
                $score = 0;

                $race_code = $this->get_race_by_key($race, $key);

                if ($race_code) {
                    $race_code_key = $this->race_small[$race_code]['key'];
                    $score = $row[$race_code_key];

                    // Plus logic
                    if ($score > 0) {
                        $result_summ[$race_code] += $score;
                        $result_top[$key] = $score;
                    }
                }
                $debug[$key] = array('race' => $race_code, 'key' => $race_code_key, 'score' => $score);

                if ($debugs) {

                    if (function_exists('var_dump_table')) {
                        var_dump_table([$key, $row, $race_code, $race_code_key, $score]);
                    } else {
                        var_dump([$key, $row, $race_code, $race_code_key, $score]);
                    }
                }
            }
        }
        if ($debugs) {
            if (function_exists('var_dump_table')) {
                var_dump_table(['debug', $debug]);
            } else {
                var_dump(['debug', $debug]);
            }
        }
        if ($type_calc == 0 && $result_summ) {
            arsort($result_summ);
            $race_code_ret = array_key_first($result_summ);
        } else if ($type_calc == 1 && $result_top) {
            arsort($result_top);
            $calc_id = array_key_first($result_top);
            $race_code_ret = $debug[$calc_id]['race'];
        }
        if ($onlydata) {
            return array('type_calc' => $type_calc, 'race_code_ret' => $race_code_ret, 'result' => $debug, 'race_weight' => $race_weight);
        }

        return $race_code_ret;
    }

    public function get_race_by_key($race = 0, $key = 0) {
        $race_code = 0;
        //print $race."\n";
        if ($key == 'c') {
            $race_code = (int) substr($race, 4, 1);
        } else if ($key == 's') {
            $race_code = (int) substr($race, 5, 1);
        } else if ($key == 'b') {
            $race_code = (int) substr($race, 6, 1);
        } else if ($key == 'k') {
            $race_code = (int) substr($race, 7, 1);
        } else if ($key == 'j') {
            $race_code = (int) substr($race, 8, 1);
        } else if ($key == 'e') {
            $race_code = (int) substr($race, 9, 1);
        } else if ($key == 'f') {
            $race_code = (int) substr($race, 10, 1);
        } else if ($key == 'i') {
            $race_code = (int) substr($race, 11, 1);
        }

        return $race_code;
    }

    public function movie_autocomplite($keyword, $count) {
        $start = 0;
        //$page = $this->get_search_page();
        /* if ($page > 1) {
          $start = ($page - 1) * $this->search_limit;
          } */

        //$tab_key = $this->get_tab_key();
        $filters = $this->get_search_filters();
        //$sort = $this->get_search_sort($tab_key);
        $sort = array();
        //$this->keywords
        unset($filters['movie']);
        $search_limit = 6;
        $tab_key = $this->get_tab_key();

        $facets = false;
        if ($tab_key == 'international') {
            $data = $this->cs->front_search_international($keyword, $search_limit, $start, $sort, $filters, $facets, true, true, true, false);
        } else {
            $data = $this->cs->front_search_ethnicity_xy($keyword, $search_limit, $start, $sort, $filters, array(), '', '', '', '', $facets, true, true, true, false);
        }

        $filter = 'movie';
        $list = $data['list'];
        $ret = array();
        if ($list) {
            /*
              [id] => 11650
              [title] => Jiminy Glick in Lalawood
              [release] => 2005-04-25
              [add_time] => 1647726640
              [post_name] => jiminy-glick-in-lalawood
              [type] => Movie
              [boxusa] => 36039
              [boxworld] => 36039
              [boxint] => 0
              [share] => 1.0
              [budget] => 0
              [year] => 2004
              [w] => 1588
             */
            foreach ($list as $item) {
                $title = $item->title;
                $data_title = $title . ' (' . $item->year . ')';
                $ret[$item->id] = array('title' => $title, 'data_title' => $data_title, 'count' => $item->year);
            }
        }

        $this->theme_facet_autocomplite($ret, $filter);
    }

    public function get_movie_link($type, $post_name) {


        if ($type == 'TVSeries') {
            $link = 'tvseries';
        } else if ($type == 'VideoGame') {
            $link = 'videogame';
        } else if ($type == 'Movie') {
            $link = 'movies';
        } else {
            $link = $type;
        }

        return "/$link/$post_name/";
    }

    public function get_image_link($id = '', $last_update = '', $resolution = 540) {
        $current_site = 'https://zeitgeistreviews.com';
        $cache_site = 'https://img.zeitgeistreviews.com';

        if (defined('LOCALCACHEIMAGES')) {
            if (LOCALCACHEIMAGES == 1) {
                $cache_site = 'https://img.4aoc.ru';
                $current_site = 'https://zeitgeistreviews.com';
            } else if (LOCALCACHEIMAGES == 2) {
                $cache_site = 'https://img2.zeitgeistreviews.com';
                $current_site = 'https://zeitgeistreviews.com';
            }
        }

        $result = $cache_site . '/webp/' . $resolution . '/' . $current_site . '/analysis/create_image/m_' . $id . '_v' . $last_update . '.jpg.webp';
        return $result;
    }

    /*
     * Inflation array
     * https://www.officialdata.org/us/inflation/
     * https://www.officialdata.org/us-economy
     */

    public function get_inflation_modifer($year) {

        static $cpi;
        if (is_null($cpi)) {
            $ma = $this->get_ma();
            $cpi = $ma->get_all_cpi();
        }

        if ($year < 1913) {
            $year = 1913;
        }

        // Last element
        $last_year_cpi = end($cpi);

        $modifer = 1;
        if ($cpi[$year]) {
            $inflation_year_cpi = $cpi[$year];
            $modifer = $last_year_cpi / $inflation_year_cpi;
        }
        return $modifer;
    }

    public function get_id_by_rules($rules) {
        $ma = $this->get_ma();
        $rules_id = $ma->get_or_create_race_rule_id($rules);
        return $rules_id;
    }

    public function show_table_weight_priority($priority = array()) {
        $filter_titles = array();
        $filter_races = array();
        foreach ($this->race_data_setup as $k => $v) {
            $filter_titles[$k] = $v['title'];
        }
        foreach ($this->race_small as $k => $v) {
            $filter_races[$v['key']] = $v['title'];
        }
        $cbody = '';
        $chead = '<th colspan="2">DataSet / Verdict</th>';
        $head_ex = false;

        foreach ($priority as $i => $v) {
            if ($i == 't') {
                continue;
            }
            $cbody .= '<tr id="' . $i . '">';

            $cbody .= '<td colspan="2">' . $filter_titles[$i] . '</td>';
            foreach ($v as $j => $val) {
                if (!$head_ex) {
                    $chead .= '<th>' . $filter_races[$j] . '</th>';
                }
                $cbody .= '<td class="col">' . $val . '</td>';
            }
            $head_ex = true;
            $cbody .= '</tr>';
        }

        $ctable = '<table class="wp-list-table widefat striped table-view-list"><thead><tr>' . $chead . '</tr></thead><tbody>' . $cbody . '</tbody></table>';

        $ptype = $this->race_type_calc[$priority['t']];
        print '<p><b>' . $ptype['title'] . '</b> - Calculate type</p>';
        print $ctable;
    }

    public function get_movies_race_data($movies = array(), $showcast = array(1, 2), $ver_weight = false, $priority = array(), $debug = false) {
        // 1. Get movies list
        $m_list = $this->cs->get_movie_races($movies);
        if ($debug) {
            print_r($m_list);
        }
        // 2. Calculate actor race
        if (!$mode_key) {
            // Get from settings
            $ss = $this->cm->get_settings(false);
            if (isset($ss['an_weightid']) && $ss['an_weightid'] > 0) {
                $mode_key = $ss['an_weightid'];
            }
        }


        $ret = array();
        if ($m_list) {
            $show_cast_valid = $this->get_show_cast_valid($showcast);
            foreach ($m_list as $key => $movie) {
                $races = explode(',', $movie->raceu);
                $draces = explode(',', $movie->draceu);
                $races_all = array_merge($races, $draces);

                $ret[$key]['m'] = $movie;
                if ($races_all) {
                    $ret[$key]['races'] = $this->get_race_by_priority($races, $show_cast_valid, $priority, $ver_weight);
                }
            }
        }
        return $ret;
    }

    public function get_race_by_priority($races = array(), $show_cast_valid = array(), $priority = array(), $ver_weight = false) {
        $ret = array();
        if (!$races) {
            return $ret;
        }
        foreach ($races as $race) {
            if ($race == 0) {
                continue;
            }
            // Actor type
            $actor_type_code = substr($race, 1, 1);
            if (!$this->validate_show_cast($actor_type_code, $show_cast_valid)) {
                continue;
            }
            //Gender
            $actor_gender = substr($race, 0, 1);

            $race_code = 0;
            // Verdict
            if ($priority) {
                // Custom rules
                if ($ver_weight) {
                    // Weight logic
                    $race_code = $this->custom_weight_race_code($race, $priority);
                } else {
                    // Priority logic
                    $race_code = $this->custom_priority_race_code($race, $priority);
                }
            } else {
                if ($ver_weight) {
                    // Weight logic
                    $race_code = (int) substr($race, 3, 1);
                } else {
                    // Priority logic
                    $race_code = (int) substr($race, 2, 1);
                }
            }
            if ($race_code) {
                $ret[$race] = array('verdict' => $race_code, 'type' => $actor_type_code, 'gender' => $actor_gender);
            }
        }
        return $ret;
    }

    public function get_uid() {
        $uid = -1;
        if (function_exists('wp_get_current_user')) {
            $wpUser = wp_get_current_user();
            $uid = $wpUser->ID;
        }
        return $uid;
    }

    public function get_user_search_filter($uid = 0, $search_url = '') {
        $ret = 0;
        if ($uid > 0 && $search_url != '/search') {
            $uf = $this->cm->get_uf();
            $ret = $uf->get_user_filter($uid, $search_url);
        }
        return $ret;
    }
}
