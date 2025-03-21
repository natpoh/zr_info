<?php

/**
 * Description of MediaFront
 *
 * @author brahman
 */
class MediaFront extends CriticFront {

    public $search_url = '/newmedia';
    public $def_tab = 'critics';
    public $search_tabs = array(
        'critics' => array('title' => 'Reviews', 'count' => 0),
    );

    public function __construct($cm = '', $cs = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->cs = $cs ? $cs : new MediaSearch($this->cm);
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            //CM
            'posts' => $table_prefix . 'critic_matic_posts',
            'meta' => $table_prefix . 'critic_matic_posts_meta',
            'tags' => $table_prefix . 'critic_matic_tags',
            'tag_meta' => $table_prefix . 'critic_matic_tag_meta',
            'authors' => $table_prefix . 'critic_matic_authors',
            'authors_meta' => $table_prefix . 'critic_matic_authors_meta',
            'rating' => $table_prefix . 'critic_matic_rating',
            //CA
            'movies_meta' => $table_prefix . 'critic_movies_meta',
            //CF
            'feed_meta' => $table_prefix . 'critic_feed_meta',
            // WP
            'wp_postmeta' => DB_PREFIX_WP . 'postmeta',
        );
        $this->init_search();
    }

    public function find_results($uid = 0, $ids = array(), $show_facets = true,$show_count = true, $only_curr_tab = false, $limit = -1, $page = -1, $show_main = true, $show_chart = true, $fields = array()) {
        gmi('find_results');
        $result = array();
        $start = 0;
        $search_page = ($page != -1) ? $page : $this->get_search_page();
        $search_limit = ($limit != -1) ? $limit : $this->search_limit;

        if ($search_page > 1) {
            $start = ($search_page - 1) * $search_limit;
        }

        $tab_key = $this->get_tab_key();
        $filters = $this->get_search_filters();
        $filters['wp_uid'] = $uid;

        gmi('get_search_filters');

        $is_critic = false;
        if ($tab_key == 'critics') {
            $is_critic = true;
        }
        $facets = false;
        $critics_count = 0;

        if ($is_critic && $only_curr_tab || !$only_curr_tab) {
            // Critics
            $sort = $this->get_search_sort('critics');
            if ($show_facets) {
                $facets = $is_critic ? true : false;
            }

            $result['critics'] = $this->cs->front_search_media_multi($this->keywords, $search_limit, $start, $sort, $filters, $facets, true, false, array(), $show_main);
            $critics_count = $result['critics']['count'];

            if ($critics_count == 0 && $this->keywords && $is_critic && $show_main) {
                if ($this->get_clear_filters($filters)) {
                    // Try to find without filters
                    $no_filters = $this->cs->front_search_media_multi($this->keywords, $search_limit, $start, $sort, array(), false);
                    if ($no_filters['count'] > 0) {
                        $result['critics']['no_filters_count'] = $no_filters['count'];
                    }
                }
            }

            gmi('front_search_media_multi');
            // Movie weight logic        
            if (isset($sort['sort']) && $sort['sort'] == 'mw') {
                $result['critics']['list'] = $this->sort_critic_mv_result($result['critics']['list'], $search_limit, $start, $filters, $sort);
            }
        }

        $result['count'] = $critics_count;
        return $result;
    }

    public function cache_get_media($critic_id, $date_add, $movie_id = 0, $author_upd = 0) {
        // UNUSED
        if ($this->cache_results) {
            $arg = array();
            $arg['critic_id'] = $critic_id;
            $arg['date_add'] = $date_add;
            $arg['movie_id'] = $movie_id;
            $filename = "c-$critic_id-$date_add-$movie_id-$author_upd";

            $str = ThemeCache::cache('cache_get_media_string', false, $filename, 'critics', $this, $arg);
            return unserialize($str);
        } else {
            return $this->get_media($critic_id, $date_add);
        }
    }

    public function cache_get_media_string($arg) {
        return serialize($this->get_media($arg['critic_id'], $arg['date_add'], $arg['movie_id']));
    }

    public function get_media($critic_id, $date_add = 0, $movie_id = 0) {
        // UNUSED
        // Get critic post
        $critic = $this->cm->get_post_and_author($critic_id);
        /* Post data
         * [id] => 34631
          [date] => 1626993125
          [date_add] => 1627034418
          [status] => 1
          [type] => 1
          [link_hash] => 149d794b10948f5e72c39fb0d910236c01f418be
          [link] => https://www.amren.com/news/2021/07/why-disneylands-jungle-cruise-cultural-changes-arent-just-woke-theyre-necessary/
          [title] => Why Disneyland’s Jungle Cruise Cultural Changes Aren’t Just ‘Woke’ — They’re Necessary
          [content]
          [top_movie] => 451337
          [aid] => 31
          [fmcid] => 78
         */

        $is_staff = true;

        /*
          Author type
          0 => 'Staff',
          1 => 'Critic',
          2 => 'Audience'
         */
        if ($critic->author_type != 0) {
            $is_staff = false;
        }

        $is_audience = false;
        if ($critic->author_type == 2) {
            $is_audience = true;
        }

        /*
         * Get movie
         */

        if ($movie_id) {
            $top_movie = $movie_id;
        } else {
            $top_movie = $critic->top_movie;
            // $top_movie = $this->cm->get_top_movie($critic->id);
        }

        if ($top_movie) {

            $ma = $this->get_ma();
            $movie = $ma->get_post($top_movie);

            $poster_link_90 = $this->get_thumb_path_full(90, 120, $top_movie);
            $poster_link_small = $this->get_thumb_path_full(220, 330, $top_movie);
            $poster_link_big = $this->get_thumb_path_full(440, 660, $top_movie);

            // Cast
            $cast_obj = json_decode($movie->actors);
            $cast = $this->get_cast_string($cast_obj, 50);

            // Title
            $title = $movie->title;

            global $slug;
            // Post name
            $slug = $ma->get_post_slug($movie->type);
            $url = $ma->get_movie_link($movie);
        }
        // $content = '';
        /*
         * $critic - critic object
         * $movie - movie object
         */
        if ($is_audience) {
            $content = $this->get_audience_templ($critic);
        } else {
            $content = $this->get_feed_templ($critic, $top_movie, $is_staff);
        }

        $ret = array(
            'pid' => $critic_id,
            'content_pro' => $content,
            'date' => $critic->date,
            'm_id' => 0,
        );

        if ($top_movie) {
            $ret['cast'] = $cast;
            $ret['link'] = $url;
            $ret['title'] = $title;
            $ret['genre'] = $movie->genre;
            $ret['release'] = $movie->release;
            $ret['poster_link_small'] = $poster_link_small;
            $ret['poster_link_big'] = $poster_link_big;
            $ret['poster_link_90'] = $poster_link_90;
            $ret['m_id'] = $top_movie;
            $ret['type'] = $slug;
        }

        return $ret;
    }
}
