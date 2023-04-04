<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SearchFacets
 *
 * @author brahman
 */
class SearchFacets extends AbstractDB {

    // Critic matic
    public $cm;
    // Critic search
    public $cs;
    // Movies an
    private $ma = '';
    public $search_tabs = array(
        'movies' => array('title' => 'Movies and TV', 'count' => 0),
        'critics' => array('title' => 'Reviews', 'count' => 0)
    );
    // Search sort: /sort_title_desc
    public $search_sort = array(
        'movies' => array(
            'title' => array('title' => 'Title', 'def' => 'asc', 'main' => 1, 'group' => 'def'),
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'woketitle' => array('title' => 'Forced Diversity', 'is_title' => 1, 'group' => 'woke'),
            'div' => array('title' => 'Diversity %', 'def' => 'desc', 'group' => 'woke'),
            'fem' => array('title' => 'Female %', 'def' => 'desc', 'group' => 'woke'),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
        ),
        'critics' => array(
            'title' => array('title' => 'Title', 'def' => 'asc', 'main' => 1, 'group' => 'def'),
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'pop' => array('title' => 'Emojis', 'def' => 'desc', 'group' => 'woke', 'main' => 1,),
            'mw' => array('title' => 'Weight', 'def' => 'desc', 'main' => 1, 'group' => 'def')
        )
    );
    public $sort_range = array(
        'Default' => 'def',
        'Rating' => 'rating',
        'Popularity' => 'pop',
        'Diversity' => 'woke',
    );
    public $def_tab = 'movies';
    // Facets
    public $facets = array(
        'movies' => array('release', 'budget', 'type', 'genre', 'provider', 'providerfree', 'auratings', 'ratings', 'price', 'race', 'dirrace',
            'actor', 'actorstar', 'actormain', 'mkw',
            'dirall', 'dir', 'dirwrite', 'dircast', 'dirprod',
            'country', 'lgbt', 'woke', /* 'rf' */),
        'critics' => array('release', 'type', 'author', 'state', 'movie', 'genre', 'auratings', 'tags', 'from', /*'related',*/)
    );
    public $facets_no_data = array('ratings', 'auratings', 'state');
    public $hide_facets = array('country', 'directors');
    public $facet_filters = array();
    // Search filters    
    public $search_limit = 20;
    public $max_matches = 1000;
    public $search_url = '/search';
    public $page = 1;
    public $keywords = '';
    public $tab = '';
    public $filters = array(
        'p' => '',
        'tab' => '',
        'sort' => '',
        'actor' => '',
        'actorstar' => '',
        'actormain' => '',
        'dirall' => '',
        'dir' => '',
        'dirwrite' => '',
        'dircast' => '',
        'dirprod' => '',
        'author' => '',
        'budget' => '',
        'expand' => '',
        'cast' => '',
        'director' => '',
        'country' => '',
        'from' => '',
        'movie' => '',
        'provider' => '',
        'price' => '',
        'release' => '',
        'state' => '',
        'type' => '',
        'tags' => '',
        'year' => ''
    );
    private $minus_filters = array('genre', 'mkw', /* 'rf', */ 'country');

    public function __construct($cm = '', $cs = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->cs = $cs ? $cs : new CriticSearch($this->cm);
        $this->init_search();
    }

    public function init_search() {
        // Add minus filters
        foreach ($this->minus_filters as $name) {
            $this->filters[$name] = '';
            $this->filters['minus-' . $name] = '';
        }

        // Add race filters
        foreach ($this->cs->facets_race_cast as $facet => $item) {
            $this->filters[$facet] = '';
            $this->filters['minus-' . $facet] = '';
        }
        // Add race directors filters
        foreach ($this->cs->facets_race_directors as $facet => $item) {
            $this->filters[$facet] = '';
            $this->filters['minus-' . $facet] = '';
        }

        // Add gender filters
        foreach ($this->cs->facets_gender as $facet => $item) {
            $this->filters[$facet] = '';
            $this->filters['minus-' . $facet] = '';
        }

        // Add gender dirs filters
        foreach ($this->cs->facets_gender_dir as $facet => $item) {
            $this->filters[$facet] = '';
            $this->filters['minus-' . $facet] = '';
        }
        // Add rating filters
        $this->search_sort['movies']['ratingtitle'] = array('title' => 'Ratings', 'is_title' => 1, 'group' => 'rating');
        foreach ($this->cs->rating_facets as $facet => $item) {
            $this->filters[$facet] = '';
            if ($facet != 'rrwt') {
                $this->hide_facets[] = $facet;
            }
            //Sort
            $def_sort = isset($item['sort']) ? $item['sort'] : 'desc';
            $main = isset($item['main']) ? 1 : 0;
            $this->search_sort['movies'][$facet] = array('title' => $item['title'], 'def' => $def_sort, 'main' => $main, 'group' => $item['group'], 'icon' => $item['icon']);
        }

        // Add popularity sort        
        $this->search_sort['movies']['poptitle'] = array('title' => 'Most Talked About', 'is_title' => 1, 'group' => 'pop');
        foreach ($this->cs->popularity_facets as $facet => $item) {
            $this->filters[$facet] = '';

            //Sort
            $def_sort = isset($item['sort']) ? $item['sort'] : 'desc';
            $main = isset($item['main']) ? 1 : 0;
            $this->search_sort['movies'][$facet] = array('title' => $item['title'], 'def' => $def_sort, 'main' => $main, 'group' => $item['group'], 'icon' => $item['icon']);
        }

        // Add audience filters
        $au_rating_title = array('title' => 'Audience Ratings', 'is_title' => 1, 'group' => 'woke');
        $this->search_sort['movies']['auratingtitle'] = $au_rating_title;
        $this->search_sort['critics']['auratingtitle'] = $au_rating_title;
        foreach ($this->cs->audience_facets as $facet => $item) {
            $this->filters[$facet] = '';
            if ($facet != 'auvote') {
                $this->hide_facets[] = $facet;

                //Sort
                $def_sort = isset($item['sort']) ? $item['sort'] : 'desc';
                $main = isset($item['main']) ? 1 : 0;

                $append = array('title' => $item['title'], 'def' => $def_sort, 'main' => $main, 'icon' => $item['icon'], 'group' => $item['group']);
                $this->search_sort['movies'][$facet] = $append;
                $this->search_sort['critics'][$facet] = $append;
            }
        }
    }

    public function init_scripts() {
        wp_enqueue_script('critic_search', get_template_directory_uri() . '/js/critic_search.js', array('jquery'), CRITIC_MATIC_VERSION, true);
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

    /*
     * Front search logic
     */

    public function need_redirect() {
        $ret = false;
        if (isset($_GET['s'])) {
            $ret = true;
        }
        return $ret;
    }

    public function init_search_get_fiters() {
        if (isset($_GET['s'])) {
            $this->keywords = strip_tags(stripslashes($_GET['s']));
        }
        foreach ($this->filters as $key => $value) {
            if (isset($_GET[$key])) {
                if (is_array($_GET[$key])) {
                    $this->filters[$key] = array();
                    foreach ($_GET[$key] as $value) {
                        $this->filters[$key][] = strip_tags(stripslashes($value));
                    }
                } else {
                    $this->filters[$key] = strip_tags(stripslashes($_GET[$key]));
                }
            }
        }
        // release logic
        if (isset($this->filters['release'])) {
            $this->filters['release'] = $this->validate_release_value($this->filters['release']);
        }
        // budget
        if (isset($this->filters['budget'])) {
            $this->filters['budget'] = $this->validate_release_value($this->filters['budget']);
        }
        // year
        if (isset($this->filters['year'])) {
            $this->filters['year'] = $this->validate_release_value($this->filters['year']);
        }
        // Rating
        foreach ($this->cs->rating_facets as $key => $value) {
            if (isset($this->filters[$key])) {
                $this->filters[$key] = $this->validate_rating_value($key);
            }
        }

        // Audience rating
        foreach ($this->cs->audience_facets as $key => $value) {
            if ($key == 'auvote') {
                continue;
            }
            if (isset($this->filters[$key])) {
                $this->filters[$key] = $this->validate_rating_value($key);
            }
        }


        // exclude logic
        if ($this->filters['minus-race']) {
            foreach ($this->filters['minus-race'] as $key => $item) {
                if (isset($this->filters['race']) && in_array($item, $this->filters['race'])) {
                    unset($this->filters['minus-race'][$key]);
                }
            }
        }
    }

    public function init_search_filters() {
        $url = $_SERVER['REQUEST_URI'];
        $url_arr = explode('/', $url);
        if (sizeof($url_arr) > 2) {
            for ($i = 2; $i < sizeof($url_arr); $i++) {
                //Find filter
                if (preg_match('/^([^_]+)_/', $url_arr[$i], $match)) {
                    $key = $match[1];
                    $value = preg_replace('/^[^_]+_/', '', $url_arr[$i]);
                    if (isset($this->filters[$key])) {
                        if (strstr($value, '_')) {
                            $slug_arr = array();
                            $values = explode('_', $value);
                            foreach ($values as $slug) {
                                $clear_slug = strip_tags(stripslashes(urldecode($slug)));
                                if ($clear_slug) {
                                    $slug_arr[] = $clear_slug;
                                }
                            }
                            $slug = $slug_arr;
                        } else {
                            $slug = strip_tags(stripslashes(urldecode($value)));
                        }
                        if ($slug) {
                            $this->filters[$key] = $slug;
                            continue;
                        }
                    }
                }

                //Find keyword
                if ($i == 2) {
                    $this->keywords = strip_tags(stripslashes(urldecode($url_arr[$i])));
                }
            }
        }
    }

    public function get_search_page() {
        if ($this->filters['p']) {
            return $this->filters['p'];
        }
        return 1;
    }

    public function get_search_tab() {
        return $this->filters['tab'];
    }

    public function get_tab_key() {
        $tab = $this->get_search_tab();
        if (!$tab) {
            // Default search tab
            $tab = 'movies';
        }
        return $tab;
    }

    public function get_default_search_sort($tab) {
        // if keywords exist, def relevance
        $sort = 'date';

        if ($tab == 'critics' && $this->filters['movie']) {
            $sort = 'mw';
        }

        if ($this->keywords) {
            $sort = 'rel';
        }

        $type = $this->search_sort[$tab][$sort]['def'] ? $this->search_sort[$tab][$sort]['def'] : 'desc';
        return array('sort' => $sort, 'type' => $type);
    }

    public function reverse_sort_type($type) {
        if ($type == 'asc') {
            return 'desc';
        }
        return 'asc';
    }

    public function get_search_sort($tab) {
        $curr_sort = $this->filters['sort'];
        if (!$curr_sort) {
            return $this->get_default_search_sort($tab);
        }

        if (strstr($curr_sort, '-')) {
            $curr_sort_arr = explode('-', $curr_sort);
            $key = $curr_sort_arr[0];
            $values = array('asc', 'desc');
            $value = in_array($curr_sort_arr[1], $values) ? $curr_sort_arr[1] : '';
        } else {
            $key = $curr_sort;
            $value = '';
        }

        $sort_available = $this->get_sort_available($tab);

        if (isset($sort_available[$key])) {
            if (!$value) {
                $value = $sort_available[$key]['def'];
            }
            return array('sort' => $key, 'type' => $value);
        }

        return '';
    }

    public function get_search_keywords() {
        return $this->keywords;
    }

    public function theme_search_url($search_url = '', $search_text = '', $inc = '') {
        if ($search_url) {
            ?>
            <div id="search-url" data-id="<?php print $search_url ?>" data-title="<?php print $search_text ?>" data-inc="<?php print $inc ?>"></div>      
            <?php
        }
    }

    public function get_search_filters() {
        // Get active filters
        $filters = array();
        foreach ($this->filters as $key => $value) {
            if ($value) {
                $filters[$key] = $value;
            }
        }
        return $filters;
    }

    public function get_main_tab() {
        return $this->get_current_search_url(array(), array('tab'));
    }

    public function get_tv_tab() {
        return $this->get_current_search_url(array('tab' => 'tv'));
    }

    public function get_critics_tab() {
        return $this->get_current_search_url(array('tab' => 'critics'));
    }

    public function get_current_search_url($include = array(), $exclude = array()) {
        $filters = $this->get_search_filters();
        if (sizeof($include)) {
            foreach ($include as $key => $value) {
                $filters[$key] = $value;
            }
        }

        $keyword_str = '';
        if ($this->keywords) {
            $keyword_str = '/' . urlencode($this->keywords);
        }

        $fiters_str = '';
        $i = 0;
        if (sizeof($filters)) {
            $fiters_str .= "/";
            foreach ($filters as $key => $value) {
                if (in_array($key, $exclude)) {
                    continue;
                }
                if ($i > 0) {
                    $fiters_str .= "/";
                }
                if (is_array($value)) {
                    $value = implode('_', $value);
                }
                if ($value) {
                    $fiters_str .= $key . "_" . $value;
                    $i += 1;
                }
            }
        }
        return $this->search_url . $keyword_str . $fiters_str;
    }

    private function validate_release_value($value) {
        // Release logic
        $ret = '';
        if ($value[0] && $value[1]) {
            if ($value[0] != $value[1]) {
                sort($value);
            }
            $ret = implode('-', $value);
        }
        return $ret;
    }

    private function validate_rating_value($key) {
        // Rating logic
        $value = $this->filters[$key];
        $ret = '';
        if (isset($value[0]) && isset($value[1])) {
            if ($value[0] != $value[1]) {
                sort($value);
            }
            $ret = implode('-', $value);
        }
        return $ret;
    }

    public function find_results($ids = array(), $show_facets = true) {
        $result = array();
        $start = 0;
        $page = $this->get_search_page();
        if ($page > 1) {
            $start = ($page - 1) * $this->search_limit;
        }

        $tab_key = $this->get_tab_key();

        $filters = $this->get_search_filters();

        $is_movie = $is_critic = false;
        if ($tab_key == 'movies') {
            $is_movie = true;
        } else if ($tab_key == 'critics') {
            $is_critic = true;
        }
        $facets = false;

        // Find movies in AN base
        $type = 'Movie';
        $sort = $this->get_search_sort('movies');
        if ($show_facets) {
            $facets = $is_movie ? true : false;
        }
        $result['movies'] = $this->cs->front_search_movies_multi($this->keywords, $this->search_limit, $start, $sort, $filters, $facets);

        //Critics
        $sort = $this->get_search_sort('critics');
        if ($show_facets) {
            $facets = $is_critic ? true : false;
        }
        $result['critics'] = $this->cs->front_search_critics_multi($this->keywords, $this->search_limit, $start, $sort, $filters, $facets);
        // Movie weight logic        
        if ($sort['sort'] == 'mw') {
            $result['critics']['list'] = $this->sort_critic_mv_result($result['critics']['list'], $this->search_limit, $start, $filters, $sort);
        }

        $result['count'] = $result['movies']['count'] + $result['critics']['count'];
        return $result;
    }

    public function get_sort_available($tab) {
        $sort_available = $this->search_sort[$tab];
        if (!$this->keywords) {
            unset($sort_available['rel']);
        }
        if (!$this->filters['movie']) {
            unset($sort_available['mw']);
        }

        return $sort_available;
    }

    public function get_fiters_available($tab) {
        $filters = $this->get_search_filters();
        $available = isset($this->facets[$tab]) ? $this->facets[$tab] : array();
        $available = array_merge($available, array_keys($this->cs->facets_race_cast));
        $available = array_merge($available, array_keys($this->cs->facets_race_directors));
        $available = array_merge($available, array_keys($this->cs->facets_gender));
        $available = array_merge($available, array_keys($this->cs->facets_gender_dir));
        $available = array_merge($available, array_keys($this->cs->audience_facets));
        $available = array_merge($available, array_keys($this->cs->rating_facets));

        $facet_filters = array();
        foreach ($available as $key) {
            if (isset($filters[$key])) {
                $facet_filters[$key] = $filters[$key];
            }
            $key_minus = 'minus-' . $key;
            if (isset($filters[$key_minus])) {
                $facet_filters[$key_minus] = $filters[$key_minus];
            }
        }
        $this->facet_filters = $facet_filters;

        return $facet_filters;
    }

    public function search_filters($curr_tab = '') {
        if (!$curr_tab) {
            $curr_tab = 'movies';
        }

        $this->get_fiters_available($curr_tab);
        $filters = $this->get_search_filters();

        $tags = $this->get_filter_tags($filters);
        $ret = $this->render_filter_tags($tags);

        return $ret;
    }

    public function get_filter_tags($filters) {
        $audience_facets = array_keys($this->cs->audience_facets);
        $rating_facets = array_keys($this->cs->rating_facets);
        $tags = array();
        foreach ($filters as $key => $value) {

            $minus = false;
            if (strstr($key, 'minus-')) {
                $key = str_replace('minus-', '', $key);
                $minus = true;
            }

            // Show valid fiter
            $ma = $this->get_ma();
            /*
             * All
             */
            if ($key == 'type') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters['type'][$slug]['title']) ? $this->cs->search_filters['type'][$slug]['title'] : $slug;
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'tab' => 'all', 'minus' => $minus);
                }
            } else if ($key == 'release') {
                $name = $value;
                $slug = $value;
                $tags[] = array('name' => $name, 'type' => $key, 'name_pre' => 'Release ', 'id' => $slug, 'tab' => 'all', 'minus' => $minus);
            } else if ($key == 'genre') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $genre = $ma->get_genre_by_slug($slug, true);
                    $this->cs->search_filters['genre'][$slug]['key'] = $genre->id;
                    $tags[] = array('name' => $genre->name, 'type' => $key, 'id' => $slug, 'tab' => 'all', 'minus' => $minus);
                }
            } else if (in_array($key, $audience_facets)) {
                $name = $value;
                if (strstr($name, '-')) {
                    $name_arr = explode('-', $value);
                    $name = (((int) $name_arr[0])) . '-' . (((int) $name_arr[1]));
                }
                $slug = $value;
                $name_pre = $this->cs->audience_facets[$key]['name_pre'];
                $filter_pre = $this->cs->audience_facets[$key]['filter_pre'];

                if ($key == 'auvote') {
                    $name = isset($this->cs->search_filters['auvote'][$slug]['title']) ? $this->cs->search_filters['auvote'][$slug]['title'] : $slug;
                }

                $tags[] = array('name' => $name, 'type' => $key, 'type_title' => $filter_pre, 'name_pre' => $name_pre, 'id' => $slug, 'tab' => 'all', 'minus' => $minus);
            }
            /*
             * Movies
             */ else if ($key == 'provider') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $prov = $ma->get_provider_by_slug($slug, true);
                    $this->cs->search_filters['provider'][$slug]['key'] = $prov->pid;
                    $tags[] = array('name' => $prov->name, 'type' => $key, 'id' => $slug, 'tab' => 'movies', 'minus' => $minus);
                }
            } else if ($key == 'price') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters['price'][$slug]['title']) ? $this->cs->search_filters['price'][$slug]['title'] : $slug;
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'tab' => 'movies', 'minus' => $minus);
                }
            } else if ($key == 'race' || isset($this->cs->facets_race_cast[$key])) {
                // Race
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters['race'][$slug]['title']) ? $this->cs->search_filters['race'][$slug]['title'] : $slug;
                    $name_pre = isset($this->cs->facets_race_cast[$key]) ? $this->cs->facets_race_cast[$key]['name_pre'] : '';
                    $type_title = isset($this->cs->facets_race_cast[$key]) ? $this->cs->facets_race_cast[$key]['title'] : ucfirst($key);
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'tab' => 'movies', 'type_title' => $type_title, 'minus' => $minus, 'name_pre' => $name_pre);
                }
            } else if ($key == 'dirrace' || isset($this->cs->facets_race_directors[$key])) {
                // Race director
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters['race'][$slug]['title']) ? $this->cs->search_filters['race'][$slug]['title'] : $slug;
                    $name_pre = isset($this->cs->facets_race_directors[$key]) ? $this->cs->facets_race_directors[$key]['name_pre'] : '';
                    $type_title = isset($this->cs->facets_race_directors[$key]) ? $this->cs->facets_race_directors[$key]['title'] : 'All directors race';
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'tab' => 'movies', 'type_title' => $type_title, 'minus' => $minus, 'name_pre' => $name_pre);
                }
            } else if ($key == 'gender' || isset($this->cs->facets_gender[$key])) {
                // Gender
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters['gender'][$slug]['title']) ? $this->cs->search_filters['gender'][$slug]['title'] : $slug;
                    $name_pre = isset($this->cs->facets_gender[$key]) ? $this->cs->facets_gender[$key]['name_pre'] : '';
                    $type_title = isset($this->cs->facets_gender[$key]) ? $this->cs->facets_gender[$key]['title'] : ucfirst($key);
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'tab' => 'movies', 'type_title' => $type_title, 'minus' => $minus, 'name_pre' => $name_pre);
                }
            } else if (isset($this->cs->facets_gender_dir[$key])) {
                // Gender dir
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters['gender'][$slug]['title']) ? $this->cs->search_filters['gender'][$slug]['title'] : $slug;
                    $name_pre = isset($this->cs->facets_gender_dir[$key]) ? $this->cs->facets_gender_dir[$key]['name_pre'] : '';
                    $type_title = isset($this->cs->facets_gender_dir[$key]) ? $this->cs->facets_gender_dir[$key]['title'] : ucfirst($key);
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'tab' => 'movies', 'type_title' => $type_title, 'minus' => $minus, 'name_pre' => $name_pre);
                }
            } else if ($key == 'actor' || $key == 'actorstar' || $key == 'actormain') {
                $value = is_array($value) ? $value : array($value);
                $type_title = isset($this->cs->actor_filters[$key]) ? $this->cs->actor_filters[$key]['title'] : '';
                $name_pre = isset($this->cs->actor_filters[$key]) ? $this->cs->actor_filters[$key]['name_pre'] : '';
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters[$key][$slug]['title']) ? $this->cs->search_filters[$key][$slug]['title'] : $slug;
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'type_title' => $type_title, 'name_pre' => $name_pre, 'tab' => 'movies', 'minus' => $minus);
                }
            } else if ($key == 'dirall' || $key == 'dir' || $key == 'dirwrite' || $key == 'dircast' || $key == 'dirprod') {
                $value = is_array($value) ? $value : array($value);
                $type_title = isset($this->cs->director_filters[$key]) ? $this->cs->director_filters[$key]['title'] : '';
                $name_pre = isset($this->cs->director_filters[$key]) ? $this->cs->director_filters[$key]['name_pre'] : '';
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters[$key][$slug]['title']) ? $this->cs->search_filters[$key][$slug]['title'] : $slug;
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'type_title' => $type_title, 'name_pre' => $name_pre, 'tab' => 'movies', 'minus' => $minus);
                }
            } else if ($key == 'country') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $country = $ma->get_country_by_slug($slug, true);
                    $this->cs->search_filters['country'][$slug]['key'] = $genre->id;
                    $tags[] = array('name' => $country->name, 'type' => $key, 'id' => $slug, 'tab' => 'all', 'minus' => $minus);
                }
            } else if (in_array($key, $rating_facets)) {
                $name = $value;
                $multipler = isset($this->cs->rating_facets[$key]['multipler']) ? $this->cs->rating_facets[$key]['multipler'] : 0;
                $shift = isset($this->cs->rating_facets[$key]['shift']) ? $this->cs->rating_facets[$key]['shift'] : 0;
                if (strstr($name, '-')) {
                    $name_arr = explode('-', $value);
                    $name_from = (int) $name_arr[0];
                    $name_to = (int) $name_arr[1];

                    if ($multipler) {
                        $name_from = $name_from / $multipler;
                        $name_to = $name_to / $multipler;
                    }
                    if ($shift) {
                        $name_from = $name_from + $shift;
                        $name_to = $name_to + $shift;
                    }

                    $name = $name_from . '-' . $name_to;
                }
                $slug = $value;
                $name_pre = $this->cs->rating_facets[$key]['name_pre'];
                $filter_pre = $this->cs->rating_facets[$key]['filter_pre'];

                $tags[] = array('name' => $name, 'type' => $key, 'type_title' => $filter_pre, 'name_pre' => $name_pre, 'id' => $slug, 'tab' => 'movies', 'minus' => $minus);
            } /* else if ($key == 'rf') {
              $value = is_array($value) ? $value : array($value);
              foreach ($value as $slug) {
              $name = isset($this->cs->search_filters['rf'][$slug]['title']) ? $this->cs->search_filters['rf'][$slug]['title'] : $slug;
              $tags[] = array('name' => $name, 'type' => $key, 'type_title' => 'Rating filter', 'id' => $slug, 'tab' => 'movies', 'minus' => $minus);
              }
              } */ else if ($key == 'mkw') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters[$key][$slug]['title']) ? $this->cs->search_filters[$key][$slug]['title'] : $slug;
                    $tags[] = array('name' => $name, 'type' => $key, 'type_title' => 'Keywords', 'id' => $slug, 'tab' => 'movies', 'minus' => $minus);
                }
            }
            /*
             * Critics
             */ else if ($key == 'author') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters['author_type'][$slug]['title']) ? $this->cs->search_filters['author_type'][$slug]['title'] : $slug;
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'tab' => 'critics', 'minus' => $minus);
                }
            } else if ($key == 'tags') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $tag = $this->cm->get_tag_by_slug($slug);
                    if ($tag) {
                        $this->cs->search_filters['tags'][$slug]['key'] = $tag->id;
                        $tags[] = array('name' => $tag->name, 'type' => $key, 'id' => $slug, 'tab' => 'critics', 'minus' => $minus);
                    }
                }
            } else if ($key == 'state') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters['state'][$slug]['title']) ? $this->cs->search_filters['state'][$slug]['title'] : $slug;
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'tab' => 'critics', 'minus' => $minus);
                }
            } else if ($key == 'movie') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters[$key][$slug]['title']) ? $this->cs->search_filters[$key][$slug]['title'] : $slug;
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'tab' => 'critics', 'minus' => $minus);
                }
            } else if ($key == 'from') {
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $slug) {
                    $name = isset($this->cs->search_filters[$key][$slug]['title']) ? $this->cs->search_filters[$key][$slug]['title'] : $slug;
                    $tags[] = array('name' => $name, 'type' => $key, 'id' => $slug, 'tab' => 'critics', 'minus' => $minus);
                }
            }
        }
        return $tags;
    }

    public function theme_sort_val($sort_val = '') {
        $filters = $this->get_search_filters();
        $ret = '';
        if (isset($filters['sort'])) {
            $exclude = array('rating');
            $curr_sort = explode('-', $filters['sort'])[0];
            if (in_array($curr_sort, $exclude)) {
                return $ret;
            }
            // Popularity
            if (isset($this->cs->popularity_facets[$curr_sort])) {
                $title = $this->cs->popularity_facets[$curr_sort]['titlesm'];
                $value = $this->theme_count_value($sort_val);
                $ret = "$value - $title";
            } else if (isset($this->cs->rating_facets[$curr_sort])) {
                // Rating
                $title = $this->cs->rating_facets[$curr_sort]['titlesm'];
                $multipler = $this->cs->rating_facets[$curr_sort]['multipler'];
                $rating = $sort_val;
                if ($curr_sort == 'rrtg') {
                    $rating -= 100;
                }

                if ($multipler) {
                    $rating = round($rating / $multipler, 2);
                }
                $ret = "$rating - $title";
            } else if (isset($this->cs->audience_facets[$curr_sort])) {
                $title = $this->cs->audience_facets[$curr_sort]['titlesm'];
                $ret = "$sort_val - $title";
            }
        }

        return $ret;
    }

    public function theme_count_value($num) {
        $sizes = array("", "k", "m", "mm");
        $ret = '';
        foreach ($sizes as $uint) {
            if ($num < 1000) {
                $ret = $uint;
                break;
            }
            $num /= 1000;
        }
        return round($num, 0) . $ret;
    }

    public function sort_critic_mv_result($list, $limit, $start, $filters, $sort) {
        $list_ret = array();
        if ($list) {
            /*
             *     [0] => stdClass Object
              (
              [id] => 96009
              [date_add] => 1642805546
              [w] => 1
              [author_type] => 2
              )
             */

            $items = array();
            $audience = array();
            foreach ($list as $item) {
                $items[$item->id] = $item;
                if ($item->author_type == 2) {
                    $audience[] = $item->id;
                }
            }

            $fid = 0;
            if ($filters['movie']) {
                if (is_array($filters['movie'])) {
                    $fid = (int) $filters['movie'][0];
                } else {
                    $fid = (int) $filters['movie'];
                }
            }


            $meta_weights = $this->cm->get_critics_meta_weights($fid);
            $ids_sort = array();
            foreach ($items as $id => $critic) {
                $weight = $meta_weights[$id] ? $meta_weights[$id] : 0;
                if (!$weight && in_array($id, $audience)) {
                    // Default weight for all audience
                    $weight = 100;
                }
                $ids_sort[$id] = $weight;
            }




            // Sort logic
            if ($sort['type'] == 'desc') {
                arsort($ids_sort);
            } else {
                asort($ids_sort);
            }


            $ids_keys = array_keys($ids_sort);

            $end = $start + $limit;


            for ($i = $start; $i < $end; $i += 1) {
                if (isset($ids_keys[$i])) {
                    $key = $ids_keys[$i];
                    if (isset($items[$key])) {
                        $list_ret[] = $items[$key];
                    }
                }
            }
        }
        return $list_ret;
    }

    public function render_filter_tags($tags) {
        $ret = '';
        $curr_tab = $this->get_tab_key();
        if (sizeof($tags)) {
            $ret = '<div id="search-filters" class="tab-' . $curr_tab . '"><span>Filters: </span>';
            $ret .= '<ul class="filters-wrapper">';
            foreach ($tags as $tag) {
                $minus_class = '';
                $type = $tag['type'];
                if ($tag['minus'] === true) {
                    $minus_class = ' fminus';
                    $type = 'minus-' . $type;
                }
                $type_title = isset($tag['type_title']) ? $tag['type_title'] : ucfirst($tag['type']);
                if ($minus_class) {
                    $type_title = 'Minus - ' . $type_title;
                }
                $pre = isset($tag['name_pre']) ? $tag['name_pre'] : '';
                $ret .= '<li id="' . $type . '-' . $tag['id'] . '" class="filter f-' . $tag['tab'] . $minus_class . '" data-type="' . $type . '" data-id="' . $tag['id'] . '" title="' . $type_title . ' is ' . $tag['name'] . '">' . $pre . $tag['name'] . '<span class="close"></span></li>';
            }

            $ret .= '</ul>';
            $ret .= '</div>';
        }
        return $ret;
    }

    private function get_filter_multi_value($value) {
        // UNUSED
        $filters = array();
        if (strstr($value, '-')) {
            $p_arr = explode('-', $value);
            foreach ($p_arr as $item) {
                // if ($this->is_int($item)) {
                $filters[] = $item;
                // }
            }
        } else {
            // if ($this->is_int($value)) {
            $filters[] = $value;
            // }
        }
        return $filters;
    }

    public function search_sort($curr_tab = '') {
        if (!$curr_tab) {
            $curr_tab = 'movies';
        }

        $sort_tab = $this->get_search_sort($curr_tab);
        if (!$sort_tab) {
            $sort_tab = $this->get_default_search_sort($curr_tab);
        }

        $sort = $sort_tab['sort'];
        $type = $sort_tab['type'];
        $rev_type = $this->reverse_sort_type($type);
        $sort_available = $this->get_sort_available($curr_tab);
        $def_sort = $this->get_default_search_sort($curr_tab);

        $main_sort = array();
        $more_sort = array();
        $more_active = array();
        $tab_class = '';
        if ($sort_available) {

            foreach ($sort_available as $slug => $item) {
                $title = $item['title'];
                if (isset($item['is_title']) && $item['is_title'] == 1) {
                    //title                    
                    $sort_item = array(
                        'tab_class' => $tab_class,
                        'slug' => $slug,
                        'title' => $title,
                        'type' => 'title',
                    );
                    $more_sort[$item['group']][] = $sort_item;
                    continue;
                }

                $item_sort = $item['def'];

                $tab_active = false;

                $search_slug = $slug;

                if ($slug == $sort) {
                    $tab_active = true;
                    //Reverse slug

                    if ($type == $item_sort) {
                        $search_slug = $slug . '-' . $rev_type;
                    } else {
                        $item_sort = $type;
                    }
                }

                $sort_icon = '<span class="desc"></span>';
                if ($item_sort == 'asc') {
                    $sort_icon = '<span class="asc"></span>';
                }

                $url = $this->get_current_search_url(array('sort' => $search_slug), array('p'));

                $tab_class = 'nav-tab';
                if ($tab_active) {
                    $tab_class .= ' active';
                }

                $type_def = '';
                if ($slug == $def_sort['sort'] && $item_sort == $def_sort['type']) {
                    $item_sort = '';
                    $type_def = $def_sort['type'];
                }

                $icon = (isset($item['icon'])) ? '<span class="sort-icon"><i class="' . $item['icon'] . '"></i></span>' : '';

                $sort_item = array(
                    'tab_class' => $tab_class,
                    'icon' => $icon,
                    'url' => $url,
                    'slug' => $slug,
                    'item_sort' => $item_sort,
                    'type_def' => $type_def,
                    'title' => $title,
                    'sort_icon' => $sort_icon,
                    'type' => 'link',
                );
                if (isset($item['main']) && $item['main'] == 1) {
                    $main_sort[$item['group']][] = $sort_item;
                } else {
                    $more_sort[$item['group']][] = $sort_item;
                    if ($tab_active) {
                        $more_active[$item['group']] = true;
                    }
                }
            }
        }
        $ret = '<div id="search-sort" class="search-sort ajload">';
        if ($main_sort) {
            $ret .= '<span class="sort-title">Sort by: </span>';
            $ret .= '<ul class="sort-wrapper">';

            foreach ($this->sort_range as $title => $key) {
                $group = $main_sort[$key];
                if ($key == 'def') {
                    foreach ($group as $item) {
                        $ret .= $this->get_sort_link($item);
                    }
                }
                if ($more_sort[$key]) {
                    $group_childs = '';
                    foreach ($more_sort[$key] as $child) {
                        $group_childs .= $this->get_sort_link($child);
                    }
                    $more_sort_content = '<ul class="sort-wrapper more ' . $key . '">' . $group_childs . '</ul>';
                    $more_active_class = $more_active[$key] ? ' mact' : '';
                    $more = '<div class="sort-more' . $more_active_class . '" title="' . $title . '">' . $this->get_nte('<i></i>', $more_sort_content, true) . '</div>';

                    $group_item = $group[0];
                    $group_item['sort_icon'] .= $more;
                    $group_item['tab_class'] .= ' group';

                    $ret .= $this->get_sort_link($group_item);
                }
            }

            $ret .= '</ul>';
        }
        $ret .= '</div>';

        return $ret;
    }

    private function get_sort_link($item) {
        if ($item['type'] == 'title') {
            $sort_item = '<li class="' . $item['tab_class'] . ' title ' . $item['slug'] . '">' . $item['title'] . '</li>';
        } else {
            $sort_item = '<li class="' . $item['tab_class'] . '">' . $item['icon'] . '<a href="' . $item['url'] . '" data-sort="' . $item['slug'] . '" data-type="' . $item['item_sort'] . '" data-def="' . $item['type_def'] . '">' . $item['title'] . '</a> ' . $item['sort_icon'] . '</li>';
        }
        return $sort_item;
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
            $ret .= '<li class="' . $tab_class . '"><a href="' . $url . '" data-id="' . $slug . '" data-tab="' . $data_tab . '">' . $title . ' <span class="count">(' . $count . ')</span></a></li>';
        }
        $ret .= '</ul>';

        return $ret;
    }

    public function search_form() {
        ?>
        <form action="/search" method="get" >
            <div class="customsearch_container cm_api">
                <div class="customsearch_component__inner">
                    <input type="search" class="customsearch_input" name="s"  placeholder="Search Movies, TV,  Reviews" autocomplete="off">  
                    <a class="customsearch_container__advanced-search-button search-filters-btn" href="/search" title="Advanced Search"></a>
                    <button class="customsearch_component__button" type="submit" title="Search"></button>   
                    <div class="advanced_search_ajaxload"></div>

                    <div class="advanced_search_menu advanced_search_hidden">
                        <div class="advanced_search_first"></div>
                        <div class="advanced_search_data advanced_search_hidden"></div>
                    </div>
                </div>
            </div>
        </form>
        <?php
    }

    public function pagination($total_count = 0, $prev = 4) {
        $result = '<div id="pagination" class="pt-cv-wrapper ajload">';
        if ($total_count > 0) {
            $per_page = $this->search_limit;
            $page = $this->get_search_page();
            if ($total_count > $this->max_matches) {
                $total_count = $this->max_matches;
            }

            $first = $page - $prev;
            if ($first < 1)
                $first = 1;

            $last = $page + $prev;
            if ($last > ceil($total_count / $per_page)) {
                $last = ceil($total_count / $per_page);
            }

            $result .= '<ul class="pt-cv-pagination pagination">';

            if ($page > 1) {
                $y = $page - 1;
                $link = $this->get_current_search_url(array('p' => $y));
                $result .= "<li class=\"cv-pageitem-prev\"><a id='previous' data-id='$y' title='Go to previous page' href=\"" . $link . "\"><</a></li> ";
            }

            $y = 1;

            if ($first > 1) {
                $link = $this->get_current_search_url(array('p' => $y));
                $result .= "<li class='cv-pageitem-number'><a id='p_$y' data-id='$y' title='Go to page $y' href=\"" . $link . "\">1</a></li> ";
            }
            $y = $first - 1;

            if ($first > 6) {
                $result .= "<li class=\"cv-pageitem-number\"><a>...</a></li> ";
            } else {
                for ($i = 2; $i < $first; $i++) {
                    $link = $this->get_current_search_url(array('p' => $i));
                    $result .= "<li class='cv-pageitem-number'><a id='p_$i' data-id='$i' title='Go to page $i'  href=\"" . $link . "\">$i</a></li> ";
                }
            }

            for ($i = $first; $i < $last + 1; $i++) {
                $link = $this->get_current_search_url(array('p' => $i));
                if ($i == $page) {
                    $result .= "<li class=\"cv-pageitem-number active\"><a id='p_$i' data-id='$i' title='Current page is $i' href=\"" . $link . "\">$i</a></li> ";
                } else {
                    $result .= "<li class='cv-pageitem-number'><a id='p_$i' data-id='$i' title='Go to page $i' href=\"" . $link . "\">$i</a></li> ";
                }
            }

            $y = $last + 1;

            if ($last < ceil($total_count / $per_page) && ceil($total_count / $per_page) - $last > 0) {
                $result .= "<li class=\"cv-pageitem-number\"><a>...</a></li> ";
            }

            $e = ceil($total_count / $per_page);

            if ($last < ceil($total_count / $per_page)) {
                $link = $this->get_current_search_url(array('p' => $e));
                $result .= "<li  title='Go to page $e' class='cv-pageitem-number'><a id='p_$e' data-id='$e' href=\"" . $link . "\">$e</a></li>";
            }

            if ($page < $last) {
                $y = $page + 1;
                $link = $this->get_current_search_url(array('p' => $y));
                $result .= "<li class=\"cv-pageitem-next\"><a id='nextpage'  data-id='$y' title='Go to next page' href=\"" . $link . "\">></a></li> ";
            }

            $result .= '</ul>';
        }
        $result .= '</div>';
        return ($result);
    }

    /*
     * Facets
     */

    public function show_facets($facets_data, $tab_key) {

        if (sizeof($facets_data) && isset($this->facets[$tab_key])) {
            $items = array();
            foreach ($this->facets[$tab_key] as $key) {
                if (in_array($key, $this->facets_no_data)) {
                    $items[$key] = 1;
                } else if (isset($facets_data[$key])) {
                    $items[$key] = $facets_data[$key];
                }
            }

            foreach ($items as $key => $value) {
                if ($value == 1) {
                    //Multi facets
                    if ($key == 'ratings') {
                        $this->show_rating_facet($facets_data);
                    } else if ($key == 'auratings') {
                        $this->show_audience_facet($facets_data);
                    } else if ($key == 'state') {
                        $this->show_state_facet($facets_data);
                    }
                    continue;
                }
                if (!isset($value['data'])) {
                    continue;
                }
                $data = $value['data'];
                $count = sizeof($data);
                if (!$count) {
                    continue;
                }

                $total = $this->get_meta_total_found($value['meta']);

                $view_more = ($total > $count) ? $total : 0;

                // All

                if ($key == 'release') {
                    $this->show_slider_facet($data, $count, $key, 'all', 'Release', 'Release ');
                } else if ($key == 'genre') {
                    $this->show_genre_facet($data, $view_more);
                } else if ($key == 'type') {
                    $this->show_type_facet($data);
                }

                // Movies 
                else if ($key == 'provider') {
                    $providerfree = isset($items['providerfree']) ? $items['providerfree'] : '';
                    $this->show_provider_facet($data, $count, $key, $providerfree);
                } else if ($key == 'actor') {
                    // $this->show_actor_facet($data, $view_more);
                } else if ($key == 'race') {
                    $this->show_race_facet($data, $view_more, $key, 'movies', $facets_data);
                } else if ($key == 'dirrace') {
                    //Race directors                    
                    $this->show_director_facet($data, $view_more, $key, 'movies', $facets_data);
                } else if ($key == 'country') {
                    $this->show_country_facet($data, $view_more);
                } else if ($key == 'budget') {
                    $this->show_slider_facet($data, $count, $key, 'movies', 'Budget', 'Budget ', '', '', 100);
                } else if ($key == 'mkw') {
                    $this->show_keyword_facet($data, $view_more, $key, 'movies', $facets_data);
                }

                // Critic facets
                else if ($key == 'author') {
                    $this->show_author_facet($data);
                } else if ($key == 'tags') {
                    $this->show_tags_facet($data, $view_more);
                } else if ($key == 'from') {
                    $this->show_from_author_facet($data, $view_more);
                } else if ($key == 'movie') {
                    $this->show_movie_facet($data, $view_more, $count, $total);
                }
            }
        } else {
            print '<p id="no-facets">No available filters found.</p>';
        }
    }

    private function get_meta_total_found($meta) {
        $meta_map = array();
        $total = 0;
        if ($meta && is_array($meta)) {
            foreach ($meta as $m) {
                $m = (array) $m;
                $meta_map[$m['Variable_name']] = $m['Value'];
            }
            $total = isset($meta_map['total_found']) ? $meta_map['total_found'] : 0;
        }
        return $total;
    }

    public function show_slider_facet($data, $count, $type, $ftype = 'all', $title = '', $name_pre = '', $filter_pre = '', $icon = '', $max_count = 6, $multipler = 0, $shift = 0) {
        if (!$title) {
            $title = ucfirst($type);
        }

        if (!$filter_pre) {
            $filter_pre = $title;
        }

        $y = 0;
        $from = $to = 0;

        // Filters
        $filters = isset($this->facet_filters[$type]) ? $this->facet_filters[$type] : array();
        if ($filters) {
            $f_arr = explode('-', $filters);
            $from = $f_arr[0];
            $to = $f_arr[1];
            if ($multipler) {
                //$from = $from / $multipler;
                //$to = $to / $multipler;
            }
        }

        if ($type == 'release') {
            $last = $data[sizeof($data) - 1];
            $max_count = $last->id;
        }

        $data_count = array();

        if ($type == 'year') {
            ksort($data);
            $data_count = $data;
            $y = array_key_first($data);
            $max_count = array_key_last($data);
            $max_item = $max_count;
        } else {
            $max_item = 0;
            foreach ($data as $value) {
                $value = (array) $value;
                $id = trim($value['id']);
                $cnt = $value['cnt'];
                if ($id == 0) {
                    continue;
                }
                $key = $id + $y;
                $data_count[$key] = $cnt;
                $max_item = $key;
            }
        }

        $items = array();
        $first_item = -1;

        while ($y <= $max_count) {
            if (isset($data_count[$y])) {
                $items[$y] = $data_count[$y];
                if ($first_item == -1) {
                    $first_item = $y;
                }
            } else {
                if ($first_item != -1) {
                    $items[$y] = 0;
                }
            }
            $y += 1;
            if ($y > $max_item) {
                break;
            }
        }

        if ($first_item == -1) {
            $first_item = 0;
        }

        $data_min = $first_item;
        $collapsed = in_array($type, $this->hide_facets) ? ' collapsed' : '';

        if (sizeof($items) > 1) {
            ?>
            <div id="facet-<?php print $type ?>" class="facet slider-facet ajload<?php print $collapsed ?>" data-type="<?php print $ftype ?>">
                <div class="facet-title">                    
                    <?php if ($icon) { ?>
                        <div class="facet-icon"><?php print $icon; ?></div>
                    <?php } ?>
                    <h3 class="title">                        
                        <?php print $title ?>
                    </h3>   
                    <div class="acc">
                        <div class="chevron"></div>
                        <div class="chevronup"></div>
                    </div>
                </div>
                <div class="facet-ch">        
                    <div class="facet-content">
                        <canvas id="<?php print $type ?>-canvas" class="facet-canvas"></canvas>
                        <div id="<?php print $type ?>-slider" data-min="<?php print $data_min ?>" data-max="<?php print $max_item ?>" 
                             data-from="<?php print $from ?>" data-to="<?php print $to ?>" data-filter-pre="<?php print $filter_pre ?>" 
                             data-title-pre="<?php print $name_pre ?>" data-multipler="<?php print $multipler ?>" data-shift="<?php print $shift ?>"></div>
                        <div class="select-holder">
                            <div class="select-from">
                                From: 
                                <select id="<?php print $type ?>-from" name="<?php print $type ?>[]">                        
                                    <?php
                                    foreach ($items as $key => $value) {

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
                                        $show_key = $key;
                                        if ($multipler > 0) {
                                            $show_key = $key / $multipler;
                                        }
                                        if ($shift) {
                                            $show_key = $show_key + $shift;
                                        }
                                        ?>
                                        <option value="<?php print $key ?>" <?php print $checked ?>><?php print $show_key ?></option>
                                    <?php } ?>
                                </select> 
                            </div>
                            <div class="select-to">
                                To: 
                                <select id="<?php print $type ?>-to" name="<?php print $type ?>[]">                        
                                    <?php
                                    foreach ($items as $key => $value) {
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
                                        $show_key = $key;
                                        if ($multipler > 0) {
                                            $show_key = $key / $multipler;
                                        }
                                        if ($shift) {
                                            $show_key = $show_key + $shift;
                                        }
                                        ?>
                                        <option value="<?php print $key ?>" <?php print $checked ?>><?php print $show_key ?></option>
                                    <?php } ?>
                                </select>  
                            </div>
                        </div>
                        <input type="hidden" name="<?php print $type ?>" value="<?php print $first_item ?>">
                        <input type="hidden" name="<?php print $type ?>" value="<?php print $max_item ?>">
                        <?php //unset($items[count($items) - 1]);         ?>
                        <script type="text/javascript">var <?php print $type ?>_arr =<?php print json_encode($items) ?></script>
                    </div>  
                </div>
            </div>
            <?php
        }
    }

    public function show_audience_facet($data) {
        ob_start();
        foreach ($this->cs->audience_facets as $key => $value) {

            $rating_data = $data[$key]['data'];
            if ($rating_data) {
                $count = sizeof($rating_data);
                $icon = '<span class="' . $value['icon'] . '"></span>';
                $name_pre = $value['name_pre'];
                $filter_pre = $value['filter_pre'];

                if ($key == 'auvote') {
                    $this->show_suggestion_facet($rating_data, $count, $key, 'all', $value['title'], $name_pre, $filter_pre, $icon);
                } else {
                    $this->show_slider_facet($rating_data, $count, $key, 'all', $value['title'], $name_pre, $filter_pre, $icon);
                }
            }
        }
        $content = ob_get_contents();
        ob_end_clean();

        $type = 'audience';
        $title = 'Audience';
        if ($content) {
            //Show multifacet
            $collapsed = in_array($type, $this->hide_facets) ? ' collapsed' : '';
            ?>
            <div id="facets-<?php print $type ?>" class="facets ajload<?php print $collapsed ?>">
                <div class="facet-title">
                    <h3 class="title"><?php print $title ?></h3>   
                    <div class="acc">
                        <div class="chevron"></div>
                        <div class="chevronup"></div>
                    </div>
                </div>
                <div class="facets-ch"> 
                    <?php print $content; ?>
                </div>                    
            </div>
            <?php
        }
    }

    public function show_rating_facet($data) {
        ob_start();
        foreach ($this->cs->rating_facets as $key => $value) {

            $rating_data = $data[$key]['data'];
            if ($rating_data) {
                $count = sizeof($rating_data);
                $icon = '';
                $name_pre = $value['name_pre'];
                $filter_pre = $value['filter_pre'];
                $max_count = isset($this->cs->rating_facets[$key]['max_count']) ? $this->cs->rating_facets[$key]['max_count'] : 100;
                $multipler = isset($this->cs->rating_facets[$key]['multipler']) ? $this->cs->rating_facets[$key]['multipler'] : 0;
                $shift = isset($this->cs->rating_facets[$key]['shift']) ? $this->cs->rating_facets[$key]['shift'] : 0;
                $this->show_slider_facet($rating_data, $count, $key, 'movies', $value['title'], $name_pre, $filter_pre, $icon, $max_count, $multipler, $shift);
            }
        }
        // Woke and lgbt UNUSED
        /*
          $lgbt_cnt = 0;
          if ($data['lgbt']['data'][1]) {
          $lgbt_cnt = $data['lgbt']['data'][1]->cnt;
          }
          $voke_cnt = 0;
          if ($data['woke']['data'][1]) {
          $voke_cnt = $data['woke']['data'][1]->cnt;
          }

          $dates = array();

          $rf = array(
          'lgbt' => $lgbt_cnt,
          'woke' => $voke_cnt,
          );

          foreach ($this->cs->search_filters['rf'] as $key => $item) {
          if ($rf[$key]) {
          $dates[$key] = array('title' => $item['title'], 'count' => $rf[$key], 'type_title' => 'Rating filter', 'name_pre' => '', 'filter' => 'rf');
          }
          }

          $filter = 'rf';
          $title = 'Filters';
          $minus = true;
          $this->theme_facet_multi($filter, $dates, $title, 0, 'movies', $minus);
         */

        $content = ob_get_contents();
        ob_end_clean();

        $type = 'ratings';
        $title = 'Ratings';
        if ($content) {
            //Show multifacet
            $collapsed = in_array($type, $this->hide_facets) ? ' collapsed' : '';
            ?>
            <div id="facets-<?php print $type ?>" class="facets ajload<?php print $collapsed ?>">
                <div class="facet-title">
                    <h3 class="title"><?php print $title ?></h3>   
                    <div class="acc">
                        <div class="chevron"></div>
                        <div class="chevronup"></div>
                    </div>
                </div>
                <div class="facets-ch"> 
                    <?php print $content; ?>
                </div>                    
            </div>
            <?php
        }
    }

    public function show_type_facet($data) {

        //Get types
        $dates = array();
        foreach ($data as $value) {
            $id = trim($value->id);
            $cnt = $value->cnt;
            if ($id) {
                foreach ($this->cs->search_filters['type'] as $key => $item) {
                    if ($item['key'] == $id) {
                        $dates[$key] = array('title' => $item['title'], 'count' => $cnt);
                    }
                }
            }
        }

        $filter = 'type';
        $title = 'Types';
        $this->theme_facet_multi($filter, $dates, $title);
    }

    public function show_suggestion_facet($data, $count, $type, $ftype = 'all', $title = '', $name_pre = '', $filter_pre = '', $icon = '') {

        //Get types
        $dates = array();
        foreach ($data as $value) {
            $id = trim($value->id);
            $cnt = $value->cnt;
            if ($id) {
                foreach ($this->cs->search_filters['auvote'] as $key => $item) {
                    if ($item['key'] == $id) {
                        $dates[$key] = array('title' => $item['title'], 'count' => $cnt, 'name_pre' => $name_pre, 'type_title' => $filter_pre);
                    }
                }
            }
        }

        $sort_keys = array('pay', 'free', 'skip');
        $dates_sort = array();
        foreach ($sort_keys as $key) {
            if ($dates[$key]) {
                $dates_sort[$key] = $dates[$key];
            }
        }

        $dates = $dates_sort;

        $filter = 'auvote';
        if ($dates) {
            $this->theme_facet_multi($filter, $dates, $title, 0, 'all', false, '', $icon);
        }
    }

    public function show_genre_facet($data, $more) {

        // Get genres
        $ma = $this->get_ma();
        $keys = array();
        foreach ($data as $value) {
            $keys[] = $value->id;
        }
        $genres = $ma->get_genres_by_ids($keys);
        $dates = array();
        foreach ($data as $value) {
            $key = $value->id;
            if (isset($genres[$key])) {
                $item = $genres[$key];
                $dates[$item->slug] = array('title' => $item->name, 'count' => $value->cnt);
            }
        }
        ksort($dates);
        $filter = 'genre';
        $title = 'Genres';
        $minus = true;
        $ftype = 'all';
        $this->theme_facet_multi($filter, $dates, $title, $more, $ftype, $minus);
    }

    public function show_country_facet($data, $more) {

        //Get countries
        $ma = $this->get_ma();
        $keys = array();
        foreach ($data as $value) {
            $keys[] = $value->id;
        }
        $countries = $ma->get_countries_by_ids($keys);

        $dates = array();
        foreach ($data as $value) {
            $key = $value->id;
            if (isset($countries[$key])) {
                $item = $countries[$key];
                if (!$item->name) {
                    continue;
                }
                $dates[$item->slug] = array('title' => $item->name, 'count' => $value->cnt);
            }
        }
        ksort($dates);
        $filter = 'country';
        $title = 'Country';
        $ftype = 'movies';
        $minus = true;
        $this->theme_facet_multi($filter, $dates, $title, $more, $ftype, $minus);
    }

    public function show_race_facet($data, $more, $filter = 'race', $ftype = 'movies', $facets = array()) {
        $title = 'Actor Demographic(s)';
        $dates = array();
        $active_filter = $this->cs->get_active_race_facet($this->filters);

        if ($filter != $active_filter) {
            if (isset($facets['race_cast'])) {
                $data = $facets['race_cast']['data'];
            } else {
                $data = array();
            }

            $filter = $active_filter;
        }

        // Race
        $type_title = isset($this->cs->facets_race_cast[$filter]) ? $this->cs->facets_race_cast[$filter]['title'] : ucfirst($filter);
        $name_pre = isset($this->cs->facets_race_cast[$filter]) ? $this->cs->facets_race_cast[$filter]['name_pre'] : '';
        foreach ($data as $value) {
            $id = (int) trim($value->id);
            $cnt = $value->cnt;
            if ($id) {
                foreach ($this->cs->search_filters['race'] as $key => $item) {
                    if ($item['key'] == $id) {
                        $dates[$key] = array('title' => $item['title'], 'count' => $cnt, 'type_title' => $type_title, 'name_pre' => $name_pre, 'filter' => $filter);
                    }
                }
            }
        }

        asort($dates);

        // Gender
        $gender_data = array();
        $gender_filter = $this->cs->race_gender[$active_filter];
        if (isset($facets['gender_cast'])) {
            $gender_data = $facets['gender_cast']['data'];
        } else {
            $gender_data = array();
        }

        $type_title = isset($this->cs->facets_gender[$gender_filter]) ? $this->cs->facets_gender[$gender_filter]['title'] : ucfirst($gender_filter);
        $name_pre = isset($this->cs->facets_gender[$gender_filter]) ? $this->cs->facets_gender[$gender_filter]['name_pre'] : '';

        if ($gender_data) {
            $dates[] = array('title' => 'Gender', 'type_title' => 'header');
            foreach ($gender_data as $value) {
                $id = (int) trim($value->id);
                $cnt = $value->cnt;
                if ($id) {
                    foreach ($this->cs->search_filters['gender'] as $key => $item) {
                        if ($item['key'] == $id) {
                            $dates[$key] = array('title' => $item['title'], 'count' => $cnt, 'type_title' => $type_title, 'name_pre' => $name_pre, 'filter' => $gender_filter);
                        }
                    }
                }
            }
        }

        $minus = true;
        $tabs_arr = $this->cs->get_cast_tabs();
        $def_tab = $this->cs->get_default_cast_tab();

        // Tabs
        $tabs = $this->facet_tabs($tabs_arr, $filter, $def_tab, 'cast');

        ob_start();
        $this->theme_facet_multi($filter, $dates, $title, $more, $ftype, $minus);

        // Actors
        $dates = array();
        $data = array();


        $filter = isset($this->cs->facets_race_cast[$active_filter]) ? $this->cs->facets_race_cast[$active_filter]['filter'] : 'actor';

        $name_pre = $this->cs->actor_filters[$filter]['name_pre'];
        $type_title = $this->cs->actor_filters[$filter]['title'];
        $filter_name = $this->cs->actor_filters[$filter]['placeholder'];

        $count = 0;
        if (isset($facets['actors'])) {
            $data = $facets['actors']['data'];
            $count = sizeof($data);
        }

        if ($data) {

            // Total
            $total = $this->get_meta_total_found($facets['actors']['meta']);
            $view_more = ($total > $count) ? $total : 0;

            $ids = array();
            foreach ($data as $value) {
                $ids[] = (int) trim($value->id);
            }

            $names = $this->cs->get_actor_names($ids);

            foreach ($data as $value) {
                $id = (int) trim($value->id);
                $name = isset($names[$id]) ? $names[$id] : $id;
                $cnt = $value->cnt;
                $dates[$id] = array('title' => $name, 'count' => $cnt, 'name_pre' => $name_pre, 'type_title' => $type_title);
            }

            $title = 'Search actors';

            /*
             * $active_filter
             * race
             * starrace
             * mainrace
             */


            $ftype = 'movies';
            $this->theme_facet_multi_search($filter, $dates, $title, $view_more, $ftype, 0, $filter_name);
        }
        $content = ob_get_contents();
        ob_end_clean();

        $type = 'actors';
        $title = 'Actors';
        if ($content) {
            //Show multifacet
            $collapsed = in_array($type, $this->hide_facets) ? ' collapsed' : '';
            ?>
            <div id="facets-<?php print $type ?>" class="facets ajload<?php print $collapsed ?>">
                <div class="facet-title">
                    <h3 class="title"><?php print $title ?></h3>   
                    <div class="acc">
                        <div class="chevron"></div>
                        <div class="chevronup"></div>
                    </div>
                </div>
                <div class="facets-ch"> 
                    <?php print $tabs; ?>
                    <?php print $content; ?>
                </div>                    
            </div>
            <?php
        }
    }

    public function show_director_facet($data, $more, $filter = 'dirrace', $ftype = 'movies', $facets = array()) {
        $tabs_arr = $this->cs->get_director_tabs();
        $def_tab = $this->cs->get_default_director_tab();

        $dates = array();
        $type_title = 'All directors race';
        $active_filter = $this->cs->get_active_director_facet($this->filters);

        if ($filter != $active_filter) {
            if (isset($facets['race_dir'])) {
                $data = $facets['race_dir']['data'];
            } else {
                $data = array();
            }

            $filter = $active_filter;
        }
        $type_title = isset($this->cs->facets_race_directors[$filter]) ? $this->cs->facets_race_directors[$filter]['title'] : $type_title;
        $name_pre = isset($this->cs->facets_race_directors[$filter]) ? $this->cs->facets_race_directors[$filter]['name_pre'] : '';

        /*
          $tabs_arr = array(
          'all' => array('facet' => 'dirrace', 'title' => 'All'),
          'directors' => array('facet' => 'dirsrace', 'title' => 'Directors'),
          'writers' => array('facet' => 'writersrace', 'title' => 'Writers'),
          'cast-directors' => array('facet' => 'castdirrace', 'title' => 'Casting Directors'),
          'producers' => array('facet' => 'producerrace', 'title' => 'Producers'),
          );

         */
        $title = 'Production Demographic(s)';
        $search_title = 'Search production';
        foreach ($tabs_arr as $key => $value) {
            if ($key == 'all') {
                continue;
            }
            if ($value['facet'] == $filter) {
                $title = $value['title'];
                $title .= ' Demographic(s)';
                $search_title = 'Search ' . strtolower($value['title']);
                break;
            }
        }


        foreach ($data as $value) {
            $id = (int) trim($value->id);
            $cnt = $value->cnt;
            if ($id) {
                foreach ($this->cs->search_filters['race'] as $key => $item) {
                    if ($item['key'] == $id) {
                        $dates[$key] = array('title' => $item['title'], 'count' => $cnt, 'type_title' => $type_title, 'name_pre' => $name_pre, 'filter' => $filter);
                    }
                }
            }
        }

        // Gender dir

        $gender_dir_data = array();
        $gender_dir_filter = $this->cs->race_gender_dir[$active_filter];

        if (isset($facets['gender_dir'])) {
            $gender_dir_data = $facets['gender_dir']['data'];
        } else {
            $gender_dir_data = array();
        }

        $type_title = isset($this->cs->facets_gender_dir[$gender_dir_filter]) ? $this->cs->facets_gender_dir[$gender_dir_filter]['title'] : ucfirst($gender_dir_filter);
        $name_pre = isset($this->cs->facets_gender_dir[$gender_dir_filter]) ? $this->cs->facets_gender_dir[$gender_dir_filter]['name_pre'] : '';

        if ($gender_dir_data) {
            $dates[] = array('title' => 'Gender', 'type_title' => 'header');
            foreach ($gender_dir_data as $value) {
                $id = (int) trim($value->id);
                $cnt = $value->cnt;
                if ($id) {
                    foreach ($this->cs->search_filters['gender'] as $key => $item) {
                        if ($item['key'] == $id) {
                            $dates[$key] = array('title' => $item['title'], 'count' => $cnt, 'type_title' => $type_title, 'name_pre' => $name_pre, 'filter' => $gender_dir_filter);
                        }
                    }
                }
            }
        }

        $minus = true;


        $tabs = $this->facet_tabs($tabs_arr, $filter, $def_tab, 'director', 'facet', array(), true);

        ob_start();
        $this->theme_facet_multi($filter, $dates, $title, $more, $ftype, $minus);

        // Director names
        $dates = array();
        $data = array();

        $filter = isset($this->cs->facets_race_directors[$active_filter]) ? $this->cs->facets_race_directors[$active_filter]['filter'] : 'dirs';

        $name_pre = $this->cs->director_filters[$filter]['name_pre'];
        $type_title = $this->cs->director_filters[$filter]['title'];
        $filter_name = $this->cs->director_filters[$filter]['placeholder'];

        $count = 0;
        if (isset($facets['dirs'])) {
            $data = $facets['dirs']['data'];
            $count = sizeof($data);
        }

        if ($data) {

            // Total
            $total = $this->get_meta_total_found($facets['dirs']['meta']);
            $view_more = ($total > $count) ? $total : 0;

            $ids = array();
            foreach ($data as $value) {
                $ids[] = (int) trim($value->id);
            }

            $names = $this->cs->get_actor_names($ids);

            foreach ($data as $value) {
                $id = (int) trim($value->id);
                $name = isset($names[$id]) ? $names[$id] : $id;
                $cnt = $value->cnt;
                $dates[$id] = array('title' => $name, 'count' => $cnt, 'name_pre' => $name_pre, 'type_title' => $type_title);
            }


            $ftype = 'movies';
            $this->theme_facet_multi_search($filter, $dates, $search_title, $view_more, $ftype, 0, $filter_name);
        }
        $content = ob_get_contents();
        ob_end_clean();

        $type = 'directors';
        $title = 'Production';
        if ($content) {
            //Show multifacet
            $collapsed = in_array($type, $this->hide_facets) ? ' collapsed' : '';
            ?>
            <div id="facets-<?php print $type ?>" class="facets ajload<?php print $collapsed ?>">
                <div class="facet-title">
                    <h3 class="title"><?php print $title ?></h3>   
                    <div class="acc">
                        <div class="chevron"></div>
                        <div class="chevronup"></div>
                    </div>
                </div>
                <div class="facets-ch"> 
                    <?php print $tabs; ?>
                    <?php print $content; ?>
                </div>                    
            </div>
            <?php
        }
    }

    public function show_keyword_facet($data, $more, $filter = 'mkw', $ftype = 'movies', $facets_data = array()) {
        $dates = array();
        if ($data) {
            $ids = array();

            foreach ($data as $value) {
                $ids[] = $value->id;
            }

            $titles = $this->cs->get_keywords_titles($ids);

            foreach ($data as $value) {
                $id = $value->id;
                $name = isset($titles[$id]) ? $titles[$id] : $id;
                $cnt = $value->cnt;
                $dates[$id] = array('title' => $name, 'count' => $cnt);
            }
        }

        $title = 'Keywords';

        $quick_find = true;
        $this->theme_facet_multi($filter, $dates, $title, $more, $ftype, true, '', '', true, true, 0, $quick_find);
    }

    private function facet_tabs($tabs = array(), $active_facet = '', $def_tab = '', $filter_name = '', $filter_type = 'facet', $inactive = array(), $column = false) {
        ob_start();
        ?>
        <ul id="<?php print $filter_name ?>-tabs" class="tab-wrapper facet-tabs<?php
        if ($column) {
            print ' column';
        }
        ?>" data-filter="<?php print $filter_name ?>"><?php
                foreach ($tabs as $slug => $item) {
                    $is_active = '';
                    $is_default = '';

                    if ($inactive && in_array($item[$filter_type], $inactive)) {
                        continue;
                    }

                    if ($item[$filter_type] == $active_facet) {
                        $is_active = ' active';
                    }
                    if ($def_tab == $item[$filter_type]) {
                        $is_default = ' default';
                        $include = array();
                        $exclude = array($filter_name);
                    } else {
                        $include = array($filter_name => $slug);
                        $exclude = array();
                    }

                    $url = $this->get_current_search_url($include, $exclude);
                    ?><li class="nav-tab<?php print $is_active . $is_default ?>" data-id="<?php print $slug ?>"><a href="<?php print $url ?>"><?php print $item['title'] ?></a></li><?php }
                ?></ul>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function show_actor_facet($data, $more) {
        // UNUSED

        $dates = array();

        $ids = array();
        foreach ($data as $value) {
            $ids[] = (int) trim($value->id);
        }

        $names = $this->cs->get_actor_names($ids);

        foreach ($data as $value) {
            $id = (int) trim($value->id);
            $name = isset($names[$id]) ? $names[$id] : $id;
            $cnt = $value->cnt;
            $dates[$id] = array('title' => $name, 'count' => $cnt);
        }


        $filter = 'actor';
        $title = 'Actor';
        $ftype = 'movies';
        $this->theme_facet_multi_search($filter, $dates, $title, $more, $ftype);
    }

    public function show_provider_facet($data, $count, $filter, $providerfree) {


// Provider price filter
        $price_filter = 'price';
        $price_title = 'Provider price';
        $ftype = 'movies';

        $cnt = array('free' => 0);
        $check_price = '';
        foreach ($cnt as $key => $val) {
            $checked = $this->facet_checked($price_filter, $key);
            if ($checked) {
                if ($check_price) {
                    $check_price = '';
                    break;
                }
                $check_price = $key;
            }
        }

        $keys_free = array();
        if (isset($providerfree['data'])) {
            foreach ($providerfree['data'] as $value) {
                $keys_free[] = $value->id;
                $cnt['free'] += $value->cnt;
            }
        }

        $keys = array();

        foreach ($data as $value) {
            $keys[] = $value->id;
            if ($keys_free && in_array($value->id, $keys_free)) {
                continue;
            }
        }

        if ($check_price == 'free') {
            $keys = $keys_free;
            $count = sizeof($keys);
        }

        $free_tab = '';
        if ($cnt['free'] > 0) {

            $price_dates = array();
            foreach ($cnt as $key => $val) {
                $item = $this->cs->search_filters['price'][$key];
                $price_dates[$key] = array('title' => $item['title'], 'count' => $val);
            }
            ob_start();
            $this->theme_facet_multi($price_filter, $price_dates, $price_title, 0, $ftype, false);
            $free_tab = ob_get_contents();
            ob_end_clean();
            $free_tab = preg_replace('/^.*(<ul.*<\/ul>).*$/s', "$1", $free_tab);
            $free_tab = str_replace('facet-content', 'facet-content free-watch', $free_tab);
        }
//Provider filter

        $expand = isset($this->filters['expand']) ? $this->filters['expand'] : '';
        $limit = $expand == 'provider' ? 200 : 10;

//Get providers
        $ma = $this->get_ma();

        $providers = $ma->get_providers_list($keys);

        $dates = array();
        $sort = array();
        $names = array();
        foreach ($data as $value) {
            $key = $value->id;
            if (isset($providers[$key])) {
                $item = $providers[$key];
                $dates[$item->slug] = array('title' => $item->name, 'count' => $value->cnt, 'pid' => $item->pid);
                if ($item->weight > 0) {
                    $sort[$item->slug] = $item->weight;
                }
            }
        }

        if (sizeof($sort)) {
            ksort($sort);
            arsort($sort);
            $sorted_data = array();
            $i = 0;
            foreach ($sort as $key => $value) {
                if ($expand != 'provider' && ($i > $limit)) {
                    break;
                }
                $sorted_data[$key] = $dates[$key];
                $i += 1;
            }
            ksort($dates);
            foreach ($dates as $key => $value) {
                if ($expand != 'provider' && $i > $limit) {
                    break;
                }
                if (!isset($sorted_data[$key])) {
                    $sorted_data[$key] = $dates[$key];
                }
                $i += 1;
            }

            $dates = $sorted_data;
        }
        $more = 0;
        if ($count > $i) {
            $more = $count;
        }

        $title = 'Provider';
        $ftype = 'movies';

        $this->theme_facet_multi($filter, $dates, $title, $more, $ftype, false, $free_tab);
    }

    public function show_author_facet($data) {
        $dates = array();

        foreach ($data as $value) {
            $id = (int) $value->id;
            $cnt = $value->cnt;
            if ($id >= 0) {
                foreach ($this->cs->search_filters['author_type'] as $key => $item) {
                    if ($item['key'] == $id) {
                        $dates[$key] = array('title' => $item['title'], 'count' => $cnt);
                    }
                }
            }
        }

        ksort($dates);

        $filter = 'author';
        $title = 'Author';
        $this->theme_facet_multi($filter, $dates, $title);
    }

    public function show_tags_facet($data, $more) {
        $keys = array();
        foreach ($data as $value) {
            $keys[] = (int) $value->id;
        }
        $tags = $this->cm->get_tags_by_ids($keys);

        $dates = array();
        foreach ($data as $value) {
            $id = (int) $value->id;
            $cnt = $value->cnt;
            if (isset($tags[$id])) {
                $slug = $tags[$id]->slug;
                $title = $tags[$id]->name;
                $dates[$slug] = array('title' => $title, 'count' => $cnt);
            }
        }

        ksort($dates);

        $filter = 'tags';
        $title = 'Tags';
        $ftype = 'critics';
        $this->theme_facet_multi($filter, $dates, $title, $more, $ftype);
    }

    public function show_from_author_facet($data, $more) {
        $keys = array();
        $filter = 'from';

        foreach ($data as $value) {
            $keys[] = (int) $value->id;
        }
        $authors = $this->cm->get_authors_by_ids($keys);

        $dates = array();
        $titles = array();
        $sort_dates = array();
        foreach ($data as $value) {
            $id = (int) $value->id;
            $cnt = $value->cnt;
            if (isset($authors[$id])) {
                $slug = $id;
                $title = $authors[$id]->name;
                $titles[$title . '-' . $id] = $id;
                $dates[$slug] = array('title' => $title, 'count' => $cnt);
            }
        }
        ksort($titles);
        foreach ($titles as $key => $id) {
            $sort_dates[$id] = $dates[$id];
        }
        $dates = $sort_dates;


        $title = 'From author';
        $ftype = 'critics';
        $this->theme_facet_multi($filter, $dates, $title, $more, $ftype);
    }

    public function show_state_facet($facets_data) {

        // Get state
        $dates = array();
        $data = isset($facets_data['state']['data']) ? $facets_data['state']['data'] : array();
        //$other_cnt = isset($facets_data['related']['data'][0]->cnt) ? $facets_data['related']['data'][0]->cnt : 0;

        if ($data) {
            foreach ($data as $value) {
                $id = trim($value->id);
                $cnt = $value->cnt;
                if ($id) {
                    foreach ($this->cs->search_filters['state'] as $key => $item) {
                        if ($item['key'] == $id) {
                            $dates[$key] = array('title' => $item['title'], 'count' => $cnt);
                        }
                    }
                }
            }
        }

        /*if ($other_cnt) {
            $other_item = $this->cs->search_filters['state']['related'];
            $dates['related'] = array('title' => $other_item['title'], 'count' => $other_cnt);
        }*/
        $filter = 'state';
        $title = 'Relevance';
        $this->theme_facet_multi($filter, $dates, $title);
    }

    public function show_movie_facet($data, $more, $count, $total) {

        /*
          [id] => 67088
          [title] => The Contractor
          [release] => 2022-04-01
          [add_time] => 1648360086
          [post_name] => the-contractor
          [type] => Movie
          [boxusa] => 0
          [boxworld] => 572148
          [boxint] => 572148
          [share] => 0
          [budget] => 0
          [year] => 2022
          [w] => 1
         */
        $filter = 'movie';
        $max_count = 100;
        $filters = $this->get_search_filters();
        $expanded = (isset($this->filters['expand']) && $this->filters['expand'] == $filter) ? true : false;

        if (!$expanded) {
            $data = array();
        }


        $dates = array();
        if ($data) {
            foreach ($data as $value) {
                $id = $value->id;
                $name = $value->title . '. (' . $value->year . ')';
                $cnt = 0;
                $dates[$id] = array('title' => $name, 'count' => $cnt);
            }
        }


        $title = 'Movies';
        $ftype = 'critics';

        $this->theme_facet_multi_search($filter, $dates, $title, $total, $ftype, $max_count);
    }

    public function movie_autocomplite($keyword, $count) {
        $start = 0;
        //$page = $this->get_search_page();
        /* if ($page > 1) {
          $start = ($page - 1) * $this->search_limit;
          } */

        $tab_key = $this->get_tab_key();
        $filters = $this->get_search_filters();
        $facets = false;

        //$sort = $this->get_search_sort($tab_key);
        $sort = array();
        //$this->keywords
        unset($filters['movie']);
        $search_limit = 6;

        if ($tab_key == 'critics') {
            $data = $this->cs->front_search_critic_movies($keyword, $search_limit, $start, $sort, $filters, $facets);
        } else {
            $data = $this->cs->front_search_movies_multi($keyword, $search_limit, $start, $sort, $filters, $facets);
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

    public function theme_facet_multi($filter, $data, $title, $more = 0, $ftype = 'all', $minus = false, $tabs = '', $icon = '', $show_count = true, $show_and = true, $max_count = 0, $quick_find = false) {
        $expanded = (isset($this->filters['expand']) && $this->filters['expand'] == $filter) ? true : false;
        $collapsed = in_array($filter, $this->hide_facets) ? ' collapsed' : '';
        if ($max_count == 0) {
            $max_count = $this->cs->facet_max_limit;
        }
        ?>
        <div id="facet-<?php print $filter ?>" class="facet ajload<?php print $collapsed ?>" data-type="<?php print $ftype ?>">
            <div class="facet-title">
                <?php if ($icon) { ?>
                    <div class="facet-icon"><?php print $icon; ?></div>
                <?php } ?>
                <h3 class="title"><?php print $title ?></h3>   
                <div class="acc">
                    <div class="chevron"></div>
                    <div class="chevronup"></div>
                </div>
            </div>
            <?php if ($quick_find) { ?>
                <div class="facet-quickfind">
                    <input type="search" class="autocomplite" data-type="<?php print $filter ?>" data-count="<?php print $more ?>" value="" placeholder="Quick find" ac-type="qf">                    
                </div>          
            <?php } ?>
            <div class="facet-ch">
                <?php
                if ($tabs) {
                    print $tabs;
                }
                $keys = array();
                ?>
                <?php if (sizeof($data)): ?>
                    <ul class="facet-content">                   
                        <?php foreach ($data as $key => $item): ?>
                            <?php
                            $type_title = isset($item['type_title']) ? $item['type_title'] : '';
                            if ($type_title == 'header') {
                                print '<li><b class="local_title">' . $item['title'] . '</b></li>';
                                continue;
                            }

                            $name_pre = isset($item['name_pre']) ? $item['name_pre'] : '';
                            $local_filter = isset($item['filter']) ? $item['filter'] : $filter;
                            $checked = $this->facet_checked($local_filter, $key);

                            $checked_minus = '';
                            if ($minus) {
                                $minus_filter = 'minus-' . $local_filter;
                                $checked_minus = $this->facet_checked($minus_filter, $key);
                            }
                            if ($checked || $checked_minus) {
                                $keys[] = $key;
                            }
                            ?>
                            <li class="checkbox"> 
                                <?php
                                if ($minus):
                                    ?>
                                    <div class="flex-row multi_pm">
                                        <label class="plus<?php print $checked ? ' active' : ''  ?>" data-type="<?php print $type_title ?>">
                                            <input type="checkbox" name="<?php print $local_filter ?>[]" data-name="<?php print $local_filter ?>" class="plus" data-title="<?php print $item['title'] ?>" data-title-pre="<?php print $name_pre ?>" value="<?php print $key ?>" <?php print $checked ? 'checked' : ''  ?> >                                                      
                                        </label>
                                        <label class="minus<?php print $checked_minus ? ' active' : ''  ?>" data-type="<?php print $type_title ? 'Minus - ' . $type_title : ''  ?>">
                                            <input type="checkbox" name="<?php print $minus_filter ?>[]" data-name="<?php print $minus_filter ?>" class="minus" data-title="<?php print $item['title'] ?>" data-title-pre="<?php print $name_pre ?>" value="<?php print $key ?>" <?php print $checked_minus ? 'checked' : ''  ?> >                          
                                        </label>
                                        <span class="t"><?php print $item['title'] ?>
                                            <?php if ($show_count) { ?>
                                                <span class="cnt">(<?php print $item['count'] ?>)</span>
                                            <?php } ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <?php if ($local_filter == 'provider'):
                                        ?>
                                        <label class="flex-row with-img" data-type="<?php print $type_title ?>">                                        
                                            <img alt="<?php print $item['title'] ?>" src="/wp-content/uploads/thumbs/providers_img/50x50/<?php print $item['pid'] ?>.jpg" width="25" height="25">
                                            <span class="t"><?php print $item['title'] ?>
                                                <?php if ($show_count) { ?>
                                                    <span class="cnt">(<?php print $item['count'] ?>)</span>
                                                <?php } ?>
                                            </span>
                                            <input type="checkbox" name="<?php print $local_filter ?>[]" data-name="<?php print $local_filter ?>" class="plus" data-title="<?php print $item['title'] ?>" data-title-pre="<?php print $name_pre ?>" value="<?php print $key ?>" <?php print $checked ? 'checked' : ''  ?> >                                                      
                                        </label>
                                    <?php else: ?>
                                        <label class="flex-row" data-type="<?php print $type_title ?>">
                                            <input type="checkbox" name="<?php print $local_filter ?>[]" data-name="<?php print $local_filter ?>" class="plus" data-title="<?php print $item['title'] ?>" data-title-pre="<?php print $name_pre ?>" value="<?php print $key ?>" <?php print $checked ? 'checked' : ''  ?> >                                                      
                                            <span class="t"><?php print $item['title'] ?>
                                                <?php if ($show_count) { ?>
                                                    <span class="cnt">(<?php print $item['count'] ?>)</span>
                                                <?php } ?>                                                
                                            </span>
                                            <?php if (isset($item['note'])) { ?>
                                                <div class="nte">
                                                    <div class="btn">?</div>
                                                    <div class="nte_show">
                                                        <div class="nte_in">
                                                            <div class="nte_cnt">
                                                                <?php print $item['note'] ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </label>
                                    <?php endif ?>
                                <?php endif ?>
                            </li>
                        <?php endforeach; ?>   
                        <?php
                        if ($show_and) {
                            $not_list = $this->get_facet_checked_not_in_list($filter, $keys);
                            if (sizeof($not_list)) {
                                ?>
                                <li>And: <li>
                                    <?php
                                    foreach ($not_list as $k) {
                                        $key = $k['key'];
                                        $type = $k['type'];
                                        $name = isset($this->cs->search_filters[$filter][$key]['title']) ? $this->cs->search_filters[$filter][$key]['title'] : $key;
                                        $this->checkbox_list_item($key, $filter, $name, 0, true, $minus, $type, $name_pre);
                                    }
                                }
                            }
                            ?>
                    </ul>

                    <?php if ($expanded): ?>
                        <div class="more active" title="Collapse" data-id="<?php print $filter ?>">Collapse</div>
                        <?php
                    elseif ($more):
                        $expand_text = 'Expand all: ' . $more;
                        if ($more == -1) {
                            $expand_text = 'Expand';
                            $more = 'more';
                        } else if ($more > $max_count) {
                            $expand_text = 'Expand first: ' . $max_count;
                            print '<p>Total found: ' . $more . '</p>';
                        }
                        ?>
                        <div class="more" title="Show <?php print $more ?> filters" data-id="<?php print $filter ?>"><?php print $expand_text ?></div>
                    <?php endif ?>

                <?php else: ?>
                    <div class="facet-content">
                        <p>No data avaliable</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function theme_facet_multi_search($filter, $data, $title, $more = 0, $ftype = 'all', $max_count = 0, $filter_name = '') {
        if ($max_count == 0) {
            $max_count = $this->cs->facet_max_limit;
        }
        $expanded = (isset($this->filters['expand']) && $this->filters['expand'] == $filter) ? true : false;
        $collapsed = in_array($filter, $this->hide_facets) ? ' collapsed' : '';
        $keys = array();

        if (!$filter_name) {
            $filter_name = $filter;
        }
        ?>
        <div id="facet-<?php print $filter ?>" class="facet ajload<?php print $collapsed ?>" data-type="<?php print $ftype ?>">
            <div class="facet-title">
                <h3 class="title"><?php print $title ?></h3>   
                <div class="acc">
                    <div class="chevron"></div>
                    <div class="chevronup"></div>
                </div>
            </div>
            <div class="facet-ch">
                <div class="facet-ac">
                    <input type="search" class="autocomplite" data-type="<?php print $filter ?>" data-count="<?php print $more ?>" value="" placeholder="Search <?php print $filter_name ?>" ac-type="ac">
                    <div class="ac-holder" data-type="<?php print $ftype ?>"></div>
                </div>
                <ul class="facet-content">
                    <?php
                    if ($data) {
                        foreach ($data as $key => $item) {
                            $checked = false;
                            if ($this->facet_checked($filter, $key)) {
                                $checked = true;
                                $keys[] = $key;
                            }
                            $name_pre = isset($item['name_pre']) ? $item['name_pre'] : '';
                            $type_title = isset($item['type_title']) ? $item['type_title'] : '';
                            $this->checkbox_list_item($key, $filter, $item['title'], $item['count'], $checked, false, 'p', $name_pre, $type_title);
                        }
                    }
                    $not_list = $this->get_facet_checked_not_in_list($filter, $keys);
                    if (sizeof($not_list)) {
                        ?>
                        <li>And: <li>
                            <?php
                            foreach ($not_list as $k) {
                                $key = $k['key'];
                                $name = isset($this->cs->search_filters[$filter][$key]['title']) ? $this->cs->search_filters[$filter][$key]['title'] : $key;
                                $this->checkbox_list_item($key, $filter, $name, 0, true);
                            }
                        }
                        ?>
                </ul>
                <?php if ($expanded): ?>
                    <div class="more active" title="Collapse" data-id="<?php print $filter ?>">Collapse</div>
                    <?php
                elseif ($more):
                    $expand_text = 'Expand all: ' . $more;
                    if ($more > $max_count) {
                        $expand_text = 'Expand first: ' . $max_count;
                        print '<p>Total found: ' . $more . '</p>';
                    }
                    ?>
                    <div class="more" title="Show <?php print $more ?> filters" data-id="<?php print $filter ?>"><?php print $expand_text ?></div>
                <?php endif ?>
            </div>
        </div>
        <?php
    }

    public function theme_facet_select($filter, $data, $title, $ftype = 'all', $name_pre = '', $tabs = '', $icon = '', $footer = '', $check_default = '') {
        ?>
        <div id="facet-<?php print $filter ?>" class="facet ajload" data-type="<?php print $ftype ?>">
            <div class="facet-title">
                <?php if ($icon) { ?>
                    <div class="facet-icon"><?php print $icon; ?></div>
                <?php } ?>
                <h3 class="title"><?php print $title ?></h3>   
                <div class="acc">
                    <div class="chevron"></div>
                    <div class="chevronup"></div>
                </div>
            </div>
            <div class="facet-ch">
                <?php
                if ($tabs) {
                    print $tabs;
                }
                ?>
                <?php if (sizeof($data)): ?>
                    <select autocomplete="off" class="facet-content facet-select" name="<?php print $filter ?>" data-name-pre="<?php print $name_pre ?>">

                        <?php foreach ($data as $key => $item): ?>
                            <?php
                            $checked = false;
                            if ($check_default) {
                                if ($check_default == $key) {
                                    $checked = true;
                                }
                            } else {
                                $checked = $this->facet_checked($filter, $key);
                            }
                            ?>
                            <option value="<?php print $key ?>" data-title="<?php print $item['title'] ?>" <?php print $checked ? 'selected' : ''  ?>><?php print $item['title'] ?></option>
                        <?php endforeach; ?> 
                    </select>
                <?php else: ?>
                    <div class="facet-content">
                        <p>No data avaliable</p>
                    </div>
                <?php endif; ?>
                <?php
                if ($footer) {
                    print $footer;
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function checkbox_list_item($key, $filter, $title, $count, $checked, $minus = false, $type = 'p', $name_pre = '', $type_title = '') {

        if ($minus):
            $minus_filter = 'minus-' . $filter;
            $checked_plus = 1;
            $checked_minus = 0;
            if ($type == 'm') {
                $checked_plus = 0;
                $checked_minus = 1;
            }
            ?>
            <li class="checkbox"> 
                <div class="flex-row multi_pm">
                    <label class="plus<?php print $checked_plus ? ' active' : ''  ?>">
                        <input type="checkbox" name="<?php print $filter ?>[]" data-name="<?php print $filter ?>" data-title-pre="<?php print $name_pre ?>" class="plus" data-title="<?php print $title ?>" value="<?php print $key ?>" <?php print $checked_plus ? 'checked' : ''  ?> >                                                      
                    </label>

                    <label class="minus<?php print $checked_minus ? ' active' : ''  ?>">
                        <input type="checkbox" name="<?php print $minus_filter ?>[]" data-name="<?php print $minus_filter ?>" data-title-pre="<?php print $name_pre ?>" class="minus" data-title="<?php print $title ?>" value="<?php print $key ?>" <?php print $checked_minus ? 'checked' : ''  ?> >                          
                    </label>

                    <span class="t"><?php print $title ?>
                        <?php if ($count) { ?>
                            <span class="cnt">(<?php print $count ?>)</span> 
                        <?php } ?>
                    </span>
                </div>
            </li>
        <?php else: ?>
            <li class="checkbox"> 
                <label class="flex-row" data-type="<?php print $type_title ?>">
                    <input type="checkbox" name="<?php print $filter ?>[]" data-name="<?php print $filter ?>" data-title-pre="<?php print $name_pre ?>" data-title="<?php print $title ?>" value="<?php print $key ?>"<?php print $checked ? ' checked' : ''  ?> >                          
                    <span class="t"><?php print $title ?>
                        <?php if ($count) { ?>
                            <span class="cnt">(<?php print $count ?>)</span>
                        <?php } ?>
                    </span>
                </label>
            </li>

        <?php
        endif;
    }

    public function movie_quickfilter($keyword = '', $count = 0, $filter = '') {
        // Get facet witch keyword

        if ($filter == 'mkw') {
            // Mkw quick filter logic
            $names = $this->cs->find_keywords_ids($keyword);

            $expand = isset($this->filters['expand']) ? $this->filters['expand'] : '';
            $limit = $expand == $filter ? $this->cs->facet_max_limit : $this->cs->facet_limit;


            if ($names) {
                $facets = array($filter);
                $filters = $this->get_search_filters();

                $keys = array_keys($names);

                $last_limit = $this->cs->facet_limit;
                $last_max_limit = $this->cs->facet_max_limit;
                $this->cs->facet_limit = 10000;
                $this->cs->facet_max_limit = 10000;

                $this->cs->filter_custom_and[$filter] = " AND ANY(mkw) IN(" . implode(',', $keys) . ")";
                $result = $this->cs->front_search_movies_multi($this->keywords, $facets, 0, array(), $filters, $facets, true, true, false);

                $this->cs->facet_limit = $last_limit;
                $this->cs->facet_max_limit = $last_max_limit;

                if (isset($result['facets'][$filter]['data'])) {
                    $data = array();
                    if (sizeof($result['facets'][$filter]['data'])) {

                        $i = 0;
                        foreach ($result['facets'][$filter]['data'] as $item) {

                            if (isset($names[$item->id])) {
                                $data[] = $item;
                                $i += 1;
                            }

                            if ($i >= $limit) {
                                break;
                            }
                        }
                    }

                    // Total
                    $total = $this->get_meta_total_found($result['facets'][$filter]['meta']);

                    $view_more = (count($data) < $last_limit) ? 0 : -1;

                    $this->show_keyword_facet($data, $view_more, $filter, 'movies', $result['facets']);
                }
            }
        }
    }

    public function actor_autocomplite($keyword, $count, $type = 'actor') {

        $filters = $this->get_search_filters();
        if ($type == 'actor') {
            $actor_facet = 'actors';
            $facet_active = $this->cs->get_active_race_facet($filters);
            $filter = isset($this->cs->facets_race_cast[$facet_active]) ? $this->cs->facets_race_cast[$facet_active]['filter'] : '';
            $race_name = $this->cs->facets_race_cast[$facet_active]['name'];
            $name_pre = $this->cs->actor_filters[$filter]['name_pre'];
            $type_title = $this->cs->actor_filters[$filter]['title'];
        } else {
            $actor_facet = 'dirs';
            $facet_active = $this->cs->get_active_director_facet($filters);
            $filter = isset($this->cs->facets_race_directors[$facet_active]) ? $this->cs->facets_race_directors[$facet_active]['filter'] : '';
            $race_name = $this->cs->facets_race_directors[$facet_active]['name'];
            $name_pre = $this->cs->director_filters[$filter]['name_pre'];
            $type_title = $this->cs->director_filters[$filter]['title'];
        }

        $facets = array($actor_facet);

        $max_count = 10;
        if ($count < 1000) {
            // Get facet and filter it
            $this->cs->facet_limit = 1000;
            $this->cs->facet_max_limit = 1000;
            $result = $this->cs->front_search_movies_multi($this->keywords, $facets, 0, array(), $filters, $facets, true, true, false);
            $ids = array();
            $count = array();
            if (isset($result['facets'][$actor_facet]['data'])) {
                if (sizeof($result['facets'][$actor_facet]['data'])) {
                    foreach ($result['facets'][$actor_facet]['data'] as $item) {
                        $ids[] = $item->id;
                        $count[$item->id] = $item->cnt;
                    }
                }
            }
            if ($ids) {
                $ret = array();
                $names = $this->cs->find_actors($keyword, $ids);
                if ($names) {
                    foreach ($names as $id => $name) {
                        $cnt = $count[$id];
                        $ret[$id] = array('title' => $name, 'count' => $cnt);
                    }
                    $this->theme_facet_autocomplite($ret, $filter, $name_pre, $type_title);
                }
            }
        } else {
            // Get names and filter the facet
            $names = $this->cs->find_actors($keyword);

            if ($names) {
                $keys = array_keys($names);
                $this->cs->facet_limit = 100;
                $this->cs->facet_max_limit = 100;

                $this->cs->filter_actor_and = " AND ANY(" . $race_name . ") IN(" . implode(',', $keys) . ")";
                $result = $this->cs->front_search_movies_multi($this->keywords, $facets, 0, array(), $filters, $facets, true, true, false);

                $ret = array();
                if (isset($result['facets'][$actor_facet]['data'])) {
                    if (sizeof($result['facets'][$actor_facet]['data'])) {
                        $i = 0;
                        foreach ($result['facets'][$actor_facet]['data'] as $item) {
                            if (isset($names[$item->id])) {
                                $name = $names[$item->id];
                                $ret[$item->id] = array('title' => $name, 'count' => $item->cnt);
                                $i += 1;
                            }
                            if ($i >= $max_count) {
                                break;
                            }
                        }
                        $this->theme_facet_autocomplite($ret, $filter, $name_pre, $type_title);
                    }
                }
            }
        }
    }

    public function theme_facet_autocomplite($data, $filter, $name_pre = '', $type_title = '') {
        if (sizeof($data)) {
            ?>
            <ul class="ac-result">
                <?php
                foreach ($data as $key => $item) {
                    $title = $item['title'];
                    $data_title = $item['data_title'] ? $item['data_title'] : $title;
                    ?>
                    <li class="checkbox">
                        <label class="flex-row" data-type="<?php print $type_title ?>">
                            <input type="checkbox" name="<?php print $filter ?>[]" data-name="<?php print $filter ?>" data-title-pre="<?php print $name_pre ?>" data-title="<?php print $data_title ?>" value="<?php print $key ?>" <?php print $this->facet_checked($filter, $key) ? 'checked' : ''  ?> >                          
                            <span class="t"><?php print $title ?>
                                <span class="cnt">(<?php print $item['count'] ?>)</span></span>
                        </label>
                    </li>
                <?php } ?>
            </ul>
            <?php
        }
    }

    private function get_facet_checked_not_in_list($filter, $keys) {
        $ret = array();
        $aviable_filters = array(
            'p' => $filter,
            'm' => 'minus-' . $filter
        );
        foreach ($aviable_filters as $k => $f) {
            if (isset($this->facet_filters[$f])) {
                $filters = $this->facet_filters[$f];
                if (!is_array($filters)) {
                    $filters = array($filters);
                }
                foreach ($filters as $key) {
                    if (!in_array($key, $keys)) {
                        $ret[] = array('key' => $key, 'type' => $k);
                    }
                }
            }
        }

        return $ret;
    }

    private function facet_checked($filter, $key) {
        if (isset($this->facet_filters[$filter])) {
            if (is_array($this->facet_filters[$filter])) {
                if (in_array($key, $this->facet_filters[$filter])) {
                    return true;
                }
            } else {
                if ($this->facet_filters[$filter] == $key) {
                    return true;
                }
            }
        }
        return false;
    }

    public function get_nte($btn = '', $content = '', $down = false) {
        $down_class = "";
        if ($down) {
            $down_class = " dwn";
        }
        return '<div class="nte"><div class="btn">' . $btn . '</div>'
                . '<div class="nte_show' . $down_class . '"><div class="nte_in"><div class="nte_cnt">' . $content . '</div></div></div>'
                . '</div>';
    }

}
