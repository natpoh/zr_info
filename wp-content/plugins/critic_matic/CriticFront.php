<?php
/*
 * Critic matic front class
 */

class CriticFront extends SearchFacets {

    public $new_api = true;
    public $movies_an = true;
    public $cache_results = true;
    public $enable_reactions = true;
    // Critic matic
    public $cm;
    // Critic search
    public $cs;
    // Critic emotions
    public $ce;
    // Watch list
    public $wl;
    // Movies an    
    private $ma = '';
    public $ca;
    public $uf;
    public $thumb_class;
    private $db = array();
    // Show hollywood bs rating
    private $show_hollywood = false;

    public function __construct($cm = '', $cs = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->cs = $cs ? $cs : new CriticSearch($this->cm);
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

    public function get_ca() {
        // Get critic audience
        if (!$this->ca) {
            // init cma
            if (!class_exists('CriticAudience')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticAudience.php' );
            }
            $this->ca = new CriticAudience($this->cm);
        }
        return $this->ca;
    }

    public function get_ce() {
        // DEPRECATED
        if (!$this->ce) {
            if (!class_exists('CriticEmotions')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticEmotions.php' );
            }
            $this->ce = new CriticEmotions($this->cm);
        }
        return $this->ce;
    }

    /*
     * Critic functions
     */

    public function theme_last_posts($a_type = -1, $limit = 10, $movie_id = 0, $start = 0, $tags = array(), $meta_type = array(), $min_rating = 0, $vote = 0, $search = false, $min_au = 0, $max_au = 0, $unique = 0, $vote_type = 0) {

        if ($movie_id || $unique == 0) {
            // If vote = 0 - last post, show all posts
            if ($search && !$movie_id) {
                $posts_arr = $this->cs->get_last_critics($a_type, $limit, $movie_id, $start, $tags, $meta_type, $min_rating, $vote, $min_au, $max_au, $vote_type);
                $posts = $posts_arr['list'] ? $posts_arr['list'] : [];
            } else {
                $posts = $this->get_last_posts($a_type, $limit, $movie_id, $start, $tags, $meta_type, $min_rating, $vote, $min_au, $max_au, $vote_type);
            }
        } else {
            // Get unique authors
            $unique_authors = 1;

            $authors = array();
            $posts = array();
            if ($search) {
                if ($a_type == 2) {

                    $unique_limit = 100;
                    $posts_arr = $this->cs->get_last_critics($a_type, $unique_limit, $movie_id, $start, $tags, $meta_type, $min_rating, $vote, $min_au, $max_au, $vote_type);
                    $posts = $posts_arr['list'] ? $posts_arr['list'] : [];
                    if ($posts) {
                        $unique_authors = array();
                        foreach ($posts as $item) {
                            if (!$unique_authors[$item->author_name]) {
                                $unique_authors[$item->author_name] = $item;
                            }
                            if (sizeof($unique_authors) >= $limit) {
                                break;
                            }
                        }
                        $posts = $unique_authors;
                    }
                } else {
                    $unique_limit = 10;
                    $posts_arr = $this->cs->get_last_critics($a_type, $unique_limit, $movie_id, $start, $tags, $meta_type, $min_rating, $vote, $min_au, $max_au, $vote_type, $unique_authors);
                    $authors = $posts_arr['list'] ? $posts_arr['list'] : [];

                    if ($authors) {
                        $author_limit = 1;
                        foreach ($authors as $author) {
                            $posts_arr = $this->cs->get_last_critics($a_type, $author_limit, $movie_id, $start, $tags, $meta_type, $min_rating, $vote, $min_au, $max_au, $vote_type, 0, $author->aid);
                            $post = $posts_arr['list'] ? $posts_arr['list'] : [];
                            if ($post) {
                                $posts[$post[0]->date] = $post[0];
                            }
                        }
                        krsort($posts);
                    }
                }
            } else {
                $unique_limit = 100;
                $posts = $this->get_last_posts($a_type, $unique_limit, $movie_id, $start, $tags, $meta_type, $min_rating, $vote, $min_au, $max_au, $vote_type);

                if ($posts) {
                    $unique_authors = array();
                    foreach ($posts as $item) {
                        if (!$unique_authors[$item->author_name]) {
                            $unique_authors[$item->author_name] = $item;
                        }
                        if (sizeof($unique_authors) >= $limit) {
                            break;
                        }
                    }
                    $posts = $unique_authors;
                }
            }
        }

        $items = array();
        if (sizeof($posts)) {
            foreach ($posts as $item) {
                $top_movie = $movie_id;
                if ($top_movie == 0) {
                    $top_movie = $item->top_movie;
                    // $top_movie = $this->cm->get_top_movie($item->id);
                }
                $item_theme = '';

                if ($this->cache_results) {
                    // print_r($item->aid);
                    $author_last_upd = isset($item->author_last_upd) ? $item->author_last_upd : $this->cm->get_author_last_upd($item->aid);
                    // print_r($author_last_upd);
                    // exit;
                    $item_theme = $this->cache_get_top_movie_critic($item->id, $item->date, $top_movie, $author_last_upd);
                } else {
                    $item_theme = $this->get_top_movie_critic($item->id, $item->date, $top_movie);
                }
                if ($item_theme) {
                    $items[] = $item_theme;
                }
            }
        }
        return $items;
    }

    public function get_last_posts($a_type = -1, $limit = 10, $movie_id = 0, $start = 0, $tags = array(), $meta_type = array(), $min_rating = 0, $vote = 0, $min_au = 0, $max_au = 0, $vote_type = 0, $mtype = 0) {
        $and_author = '';
        if ($a_type != -1) {
            $and_author = sprintf(' AND a.type = %d', $a_type);
        }

        $movie_inner = '';
        $movie_and = '';
        if ($movie_id > 0 || $meta_type || $min_rating || $max_rating) {
            $movie_inner = " INNER JOIN {$this->db['meta']} m ON m.cid = p.id";
        }
        if ($movie_id > 0) {
            $movie_and = sprintf(" AND m.fid=%d AND m.state!=0", (int) $movie_id);
        } else if ($meta_type || $min_rating || $max_rating) {
            $movie_and = sprintf(" AND m.fid=p.top_movie AND m.state!=0", (int) $movie_id);
        }

        $min_rating_and = '';
        if ($min_rating) {
            $min_rating_and = sprintf(' AND (m.rating>=%d OR m.state=1)', $min_rating);
        }

        $meta_type_and = '';
        if ($meta_type) {
            $meta_type_and = ' AND m.type IN(' . implode(',', $meta_type) . ')';
        }

        // Odrer by rating desc
        $custom_order = '';
        if ($movie_id > 0) {
            $custom_order = ' m.type ASC, ';
        }


        $mtype_and = '';
        if ($mtype > 0) {
            $mtype_and = sprintf(' AND m.type=%d', $mtype);
        }

        // Tag logic
        $tag_inner = '';
        $tag_and = '';
        if ($tags) {
            $tag_inner = " INNER JOIN {$this->db['tag_meta']} t ON t.cid = a.id";
            if (is_array($tags)) {
                $tag_and = " AND t.tid IN (" . implode(',', $tags) . ")";
            } else {
                $tag_and = sprintf(" AND t.tid=%d", (int) $tags);
            }
        }

        // Vote logic
        $vote_inner = '';
        $vote_and = '';
        if ($vote > 0) {
            $vote_inner = " LEFT JOIN {$this->db['rating']} r ON r.cid = p.id";
            $vote_and = sprintf(" AND r.vote=%d", $vote);
        }

        // Vote type:
        $vote_type_and = '';
        $and_select = '';

        if ($vote_type > 0) {
            if (!$vote_inner) {
                $vote_inner = " LEFT JOIN {$this->db['rating']} r ON r.cid = p.id";
            }

            if ($vote_type == 1) {
                /*
                  1 => array('title' => 'Pay To Watch!'),
                  2 => array('title' => 'Skip It'),
                  3 => array('title' => 'Watch If Free')
                  Positive
                  5     (watch if free or pay to watch )
                  4     (watch if free or pay to watch )
                  >3.7   (watch if free or pay to watch )
                  >=3    (pay to watch only)
                 * 
                 */

                $vote_type_and = " AND IF(r.rating > 3.7 AND r.vote IN (1,3),1,IF(r.rating>=3 AND r.vote=1,1,0))=1 ";
            } if ($vote_type == 2) {
                /*
                  Negative
                  3 stars (Watch If Free)
                  3 stars (skip it)
                 *  OR (r.rating=3 AND r.vote!=1)
                 * 
                  2 stars
                  1 stars
                  0 stars
                 */
                $vote_type_and = " AND r.rating < 2.3";
                // $and_select = ", IF(r.rating < 3,1,0) AS filter ";
                // $vote_type_and = " AND filter=1";
            }
        }

        // Hide home author
        $author_show_type = '';
        if ($movie_id == 0) {
            $author_show_type = ' AND a.show_type!=1';
        }

        $sql = sprintf("SELECT p.id, p.date_add, p.date, p.top_movie, a.name AS author_name, a.last_upd AS author_last_upd, a.date_add AS author_date_add" . $and_select . " FROM {$this->db['posts']} p"
                . " INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id"
                . " INNER JOIN {$this->db['authors']} a ON a.id = am.aid" . $movie_inner . $tag_inner . $vote_inner
                . " WHERE p.top_movie > 0 AND p.status=1" . $mtype_and . $and_author . $author_show_type . $movie_and . $tag_and . $min_rating_and . $meta_type_and . $vote_and . $vote_type_and . " ORDER BY" . $custom_order . " p.date DESC LIMIT %d, %d", (int) $start, (int) $limit);

        //print $sql;

        $results = $this->db_results($sql);
        //p_r(array($sql, $results));
        return $results;
    }

    public function get_post_count($a_type, $movie_id = 0, $tag_id = 0, $vote = 0, $min_rating = 0, $min_au = 0, $max_au = 0, $vote_type = 0) {
        $and_author = '';
        if ($a_type != -1) {
            $and_author = sprintf(' AND a.type = %d', $a_type);
        }

        $movie_inner = '';
        $movie_and = '';
        if ($movie_id > 0 || $min_rating) {
            $movie_inner = " INNER JOIN {$this->db['meta']} m ON m.cid = p.id";
            $movie_and = sprintf(" AND m.fid=%d AND m.state!=0", (int) $movie_id);
        }

        // Tag logic
        $tag_inner = '';
        $tag_and = '';
        if ($tag_id > 0) {
            $tag_inner = " INNER JOIN {$this->db['tag_meta']} t ON t.cid = a.id";
            $tag_and = sprintf(" AND t.tid=%d", (int) $tag_id);
        }

        // Vote logic
        $vote_inner = '';
        $vote_and = '';
        if ($vote > 0) {
            $vote_inner = " LEFT JOIN {$this->db['rating']} r ON r.cid = p.id";
            $vote_and = sprintf(" AND r.vote=%d", $vote);
        }

        $min_rating_and = '';
        if ($min_rating) {
            $min_rating_and = sprintf(' AND m.rating>=%d', $min_rating);
        }

        $min_au_and = '';
        if ($min_au) {
            $min_au_and = sprintf(' AND m.rating<=%d', $min_au);
        }

        $man_au_and = '';
        if ($max_au) {
            $man_au_and = sprintf(' AND m.rating<=%d', $max_au);
        }
        // Vote type:
        $vote_type_and = '';

        if ($vote_type > 0) {
            if (!$vote_inner) {
                $vote_inner = " LEFT JOIN {$this->db['rating']} r ON r.cid = p.id";
            }

            if ($vote_type == 1) {
                /*
                  Positive
                  5 stars
                  4 stars
                  3 stars (pay to watch)
                 */
                //$vote_type_and = " AND(r.rating IN(4,5) OR (r.rating=3 AND r.vote=1))";
                // $vote_type_and = " AND (r.rating >=3.5 AND r.vote IN(1,2)) OR (r.rating >= 3 AND r.vote=3))";
                $vote_type_and = " AND IF(r.rating>=3.5 AND r.vote IN (1,3),1,IF(r.rating>=3 AND r.vote=1,1,0))=1 ";
            } if ($vote_type == 2) {
                /*
                  Negative
                  3 stars (Watch If Free)
                  3 stars (skip it)
                  2 stars
                  1 stars
                  0 stars
                 */
                //$vote_type_and = " AND(r.rating IN(0,1,2) OR (r.rating=3 AND r.vote!=1))";
                $vote_type_and = " AND r.rating < 3";
            }
        }
        $sql = "SELECT COUNT(p.id) FROM {$this->db['posts']} p"
                . " INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id"
                . " INNER JOIN {$this->db['authors']} a ON a.id = am.aid" . $movie_inner . $tag_inner . $vote_inner
                . " WHERE p.top_movie > 0 AND p.status=1" . $and_author . $movie_and . $min_rating_and . $tag_and . $vote_and . $vote_type_and;

        $results = $this->db_get_var($sql);
        return $results;
    }

    public function get_audience_post_count($id = 0, $cache = true) {
        //Get from cache
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }

        // 1 - pay, 2 - skip
        $votes = array('p' => 1, 'n' => 2, 'a' => 0);
        $result = array();
        foreach ($votes as $key => $vote) {

            $post_count = $this->get_post_count(2, $id, 0, 0, 0, 0, 0, $vote);
            $result[$key] = $post_count;
        }

        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }

    public function admin_edit_link($id = 0, $type = 'critic') {
        $link = '';
        if ($this->cm->user_can) {
            if (!class_exists('CriticMaticAdmin')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMaticAdmin.php' );
            }
            $cma = new CriticMaticAdmin($this->cm, array());

            if ($type == 'critic') {
                $link = $cma->theme_post_link($id, 'Edit');
            }
        }
        return $link;
    }

    public function cache_get_top_movie_critic($critic_id, $date_add, $movie_id = 0, $author_upd = 0) {
        if ($this->cache_results) {
            $arg = array();
            $arg['critic_id'] = $critic_id;
            $arg['date_add'] = $date_add;
            $arg['movie_id'] = $movie_id;
            $filename = "c-$critic_id-$date_add-$movie_id-$author_upd";

            $str = ThemeCache::cache('get_top_movie_critic_string', false, $filename, 'critics', $this, $arg);
            return unserialize($str);
        } else {
            return $this->get_top_movie_critic($critic_id, $date_add);
        }
    }

    public function get_top_movie_critic_string($arg) {
        return serialize($this->get_top_movie_critic($arg['critic_id'], $arg['date_add'], $arg['movie_id']));
    }

    public function get_top_movie_critic($critic_id, $date_add = 0, $movie_id = 0) {
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

    public function cache_single_critic_content($critic_id, $movie_id = 0, $date_add = 0, $author_upd = 0) {
        $arg = array();
        $arg['critic_id'] = $critic_id;
        $arg['date_add'] = $date_add;
        $arg['movie_id'] = $movie_id;
        $filename = "p-$critic_id-$date_add-$movie_id-$author_upd";
        if ($this->cache_results) {
            $str = ThemeCache::cache('get_single_critic_content_arg', false, $filename, 'critic_posts', $this, $arg);
        } else {
            $str = $this->get_single_critic_content($critic_id, $movie_id);
        }
        return $str;
    }

    public function get_single_critic_content_arg($arg) {
        return $this->get_single_critic_content($arg['critic_id'], $arg['movie_id']);
    }

    public function get_single_critic_content($critic_id, $movie_id = 0) {

        $content = '';
        $movie_templ = '';

        $ma = $this->get_ma();
        if ($movie_id) {
            $movie = $ma->get_post($movie_id);
            $movie_templ = $this->get_small_movie_templ($movie);
        }

        $post = $this->cm->get_post_and_author($critic_id);

        if ($post->author_type == 0) {
            // Staff
            $content = $this->get_feed_templ($post, $movie_id, true, true);
        } else if ($post->author_type == 1) {
            // Pro
            $content = $this->get_feed_templ($post, $movie_id, false, true);
        } else if ($post->author_type == 2) {
            // Audience
            $content = $this->get_audience_templ($post, '', true);
        }

        return $movie_templ . $content;
    }

    public function get_feed_templ($critic = '', $top_movie = '', $stuff = false, $fullsize = false) {

        $permalink = $critic->link;
        if (!$permalink) {
            // Create local permalink
            $permalink = $this->get_critic_url($critic);
        }
        $title = $critic->title;

        if (!$title) {
            return;
        }

        $content = $critic->content;
        if (!$content) {
            $content = $title;
        }

        // Find transcriptions
        $time_codes = array();
        $desc_results = array();
        // if ($critic->type == 4) {
        if ($top_movie) {
            if (strstr($content, '<div class="transcriptions">')) {
                if ($fullsize) {
                    $codes_arr = $this->find_transcriptions($top_movie, $critic->id, $content);
                    $time_codes = $codes_arr['time_codes'];
                    $desc_results = $codes_arr['desc_results'];
                }
                // $content = preg_replace('/<div class="transcriptions">.*<\/div>/Us', '', $content);
                // Remove the content from posts witch transcriptions
                $content = '';
            }
        }
        // }
        // Get meta state

        $info_link = '';
        $meta_type = '';

        if ($top_movie) {
            $meta_state = $this->cm->get_critic_meta_state($critic->id, $top_movie);
            $info_link = $this->get_info_link($critic->id, $top_movie, $meta_state->state);
            $meta_type = $this->cm->get_post_category_name($meta_state->type);
        }

        $wp_core = '';
        // Get the content for full size post from third db for all staff posts
        if ($content) {
            if ($stuff && $fullsize) {
                $content = $this->filter_full_staff_content($content, $critic->id);
            } else {
                if ($stuff) {
                    // Staff content     
                    if (strstr($content, '[su_')) {
                        $wp_core = ' wp_core';
                    }

                    $content = $this->staff_content_filter($content, $critic->id, $fullsize);
                } else {
                    // Pro content                     
                    $content = $this->pro_content_filter($content, $critic, $permalink, $fullsize);
                }
            }
            // Other filters
            if ($content) {
                if ($critic->blur) {
                    $content = $this->spoiler_content($content);
                } else {
                    $content = $this->check_spoiler($content);
                }
                $content = $this->pccf_filter($content);
            }

            if ($stuff) {
                // remove all shortcode
                $regv = '#\[[^\]]+\]#';
                $content = preg_replace($regv, '', $content);
            }
        }

        if ($desc_results) {
            $content = '<p>' . implode('</p><p>', $desc_results) . '</p>';
        }

        // Author image
        $author = $this->cm->get_author($critic->aid);
        $author_options = unserialize($author->options);

        $cav = $this->cm->get_cav();
        //$author_img = $cav->get_pro_avatar($author->avatar_name);
        $author_img = $cav->get_pro_thumb(100, 100, $author->avatar_name);
        // print $author_img;
        // $author_img = $author_options['image'];

        $actorsdata = '';
        if ($author_img) {
            $actorsdata = '<div class="a_img_container" style="background: url(' . $author_img . '); background-size: cover;"></div>';
            /* try {                
              $image = $this->get_local_thumb(100, 100, $author_img);
              $actorsdata = '<div class="a_img_container" style="background: url(' . $image . '); background-size: cover;"></div>';
              } catch (Exception $exc) {

              } */
        }
        if (!$actorsdata) {
            // Empty image
            $actorsdata = '<div class="a_img_def"></div>';
        }


        // Author name
        $author_title = $author->name;
        $author_title = $this->pccf_filter($author_title);
        $author_link = '/search/tab_critics/from_' . $author->id;
        $author_title_link = '<a href="' . $author_link . '">' . $author_title . '</a>';

        $actorsdata_link = '<a href="' . $author_link . '">' . $actorsdata . '</a>';

        // Tags
        $catdata = '';
        $max_tags = 3;
        $tags = $this->cm->get_author_tags($author->id);
        if (sizeof($tags)) {
            $tags_count = 1;
            foreach ($tags as $tag) {
                $catdata .= $this->get_tag_link($tag->slug, $tag->name);
                $tags_count += 1;
                if ($tags_count > 3) {
                    break;
                }
            }
        }

        // Link to full post
        $link = $this->get_critic_url($critic);
        if ($top_movie) {
            if ($top_movie != $critic->top_movie) {
                // Add movie id to post
                $link = $link . '?meta=' . $top_movie;
            }
        }

        // Time
        $ptime = $critic->date;
        $critic_addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

        // Custom rating
        $custom_rating = $this->get_custom_critic_rating($critic, true);

        if ($custom_rating) {
            $custom_rating = '<span class="title-rating">' . $custom_rating . '</span>';
        }

        // Title
        $title_str = '';
        $title = strip_tags($title);
        $title = $this->pccf_filter($title);

        if ($title != $content && !$stuff) {
            $title_str = '<strong class="review-title"><span class="title-name">' . $title . '</span>' . $custom_rating . '</strong>';
        }


        $review_bottom = '<div class="review_bottom"><div class="r_type">' . $meta_type . '</div><div class="r_right"><div class="r_date">' . $critic_addtime . '</div>' . $info_link . '</div></div>';

        // Find video link
        $video_link = $this->find_video_link($permalink, $critic->id);

        if ($fullsize) {

            if ($stuff && $fullsize) {

                $original_link = '<a class="original_link" target="_blank" href="' . $permalink . '">Source Link >></a>';
            } else {
                $original_link = '<a class="original_link" target="_blank" href="' . $permalink . '">Full review >></a>';
            }

            // Embed
            $embed = $this->find_embed($permalink, $critic->id);

            $video_embed = isset($video_link['video']) ? $video_link['video'] : '';

            if ($time_codes) {
                $time_text = '<div class="timecodes">' . implode('<br />', $time_codes) . '</div>';
            }

            $content = $time_text . $content;

            // if (strlen($content) > 10) {

            if (strlen($content) > 4000) {
                $largest = ' largest';
                $after_content = '<a class="expanf_content" href="#">Read more...</a>';
            }

            if ($fullsize == 2) {
                $actorsresult = $video_embed . $embed . $title_str . $content . $review_bottom . $original_link .
                        '<div class="em_hold"></div><div class="amsg_aut">' . $actorsdata_link . '<div class="review_autor_name">' . $author_title_link . '<div class="a_cat">' . $catdata . '</div></div></div>';
            } else {
                $actorsresult = '<div class="full_review_content_block' . $largest . '">' . $video_embed . $embed . $title_str . $content . '</div>' . $after_content . $review_bottom . $original_link . '
 <div class="em_hold"></div><div class="amsg_aut">' . $actorsdata_link . '<div class="review_autor_name">' . $author_title_link . '<div class="a_cat">' . $catdata . '</div></div></div>';
            }
            // }
        } else if ($link) {

            $reaction_data = $this->get_user_reactions($critic->id);

            /* $time_text = '';
              if ($time_codes) {
              $i = 0;
              foreach ($time_codes as $key => $value) {
              $time_text .= $value . '<br />';
              $i += 1;
              if ($i >= 5) {
              break;
              }
              }
              } */

            $video_img = (isset($video_link['img']) && $video_link['img'] != '') ? '<div class="embed-responsive embed-responsive-16by9 ' . $video_link['type'] . '"><img class="video_img" src="' . $video_link['img'] . '" /><div class="video"><b><i class="icon-play"></i></b></div></div>' : '';
            if ($video_img) {
                $content = '';
            }


            $actorsresult = '<div class="a_msg">'
                    . '<div   class="a_msg_i">'
                    . '<a class="icntn' . $wp_core . '" href="' . $link . '">' . $video_img . $title_str . $content . '</a>'
                    . $review_bottom
                    . '<div class="ugol"><div></div></div></div>
        <div class="em_hold"></div><div class="amsg_aut">' . $actorsdata_link . '<div class="review_autor_name">' . $author_title_link . '<div class="a_cat">' . $catdata . '</div>'
                    . '</div>' . $reaction_data . '</div></div>';
        }
        return $actorsresult;
    }

    public function get_custom_critic_rating($critic, $full = false) {

        $rating_text = '';
        if ($critic->top_movie) {
            try {
                if ($critic->link_id == 177) {
                    // CherryPicks
                    $ma = $this->get_ma();

                    $erating = $ma->get_movie_erating($critic->top_movie);

                    if ($erating->thecherrypicks_rating) {
                        $woke_rating = $erating->thecherrypicks_rating;
                        $woke_color = 1;
                        if ($woke_rating > 30) {
                            $woke_color = 2;
                        }
                        if ($woke_rating > 60) {
                            $woke_color = 3;
                        }
                        $rating_text = ' <span title="CherryPicks woke: ' . $woke_rating . '" class="rating-review woke-color-' . $woke_color . '">' . $woke_rating . '%</span>';
                    }
                } else if ($critic->link_id == 178) {
                    // Bechdeltest
                    $ma = $this->get_ma();

                    $woke = $ma->get_movie_woke($critic->top_movie);
                    if ($woke->bechdeltest > 0) {
                        $woke_text = '';
                        $woke_small = '';
                        $woke_color = 1;
                        $filters = $this->cs->search_filters['bechdeltest'];
                        foreach ($filters as $filter) {
                            if ($woke->bechdeltest == $filter['key']) {
                                $woke_text = $filter['title'];
                                $woke_small = $filter['title-small'];
                                $woke_color = $filter['color'];
                                break;
                            }
                        }
                        if ($woke_text) {
                            if ($full) {
                                $woke_small = $woke_text;
                            }
                            $rating_text = ' <span title="Bechdeltest: ' . $woke_text . '" class="rating-review woke-color-' . $woke_color . '">' . $woke_small . '</span>';
                        }
                    }
                } else if ($critic->link_id == 176) {
                    // worthitorwoke.com
                    $ma = $this->get_ma();
                    $woke = $ma->get_movie_woke($critic->top_movie);
                    $woke_text = 'Not woke';
                    $woke_color = 1;
                    if ($woke->worthit > 0) {
                        $filters = $this->cs->search_filters['worthit'];
                        foreach ($filters as $filter) {
                            if ($woke->worthit == $filter['key']) {
                                $woke_text = $filter['title'];
                                $woke_color = $filter['color'];
                                break;
                            }
                        }
                    }

                    $rating_text = ' <span title="WorthitOrWoke: ' . $woke_text . '" class="rating-review woke-color-' . $woke_color . '">' . $woke_text . '</span>';
                } else if ($critic->link_id == 166) {
                    // mediaversity
                    $ma = $this->get_ma();

                    $erating = $ma->get_movie_erating($critic->top_movie);
                    if ($erating->mediaversity_grade) {
                        $clear_rating = strtolower(preg_replace('#[^a-zA-Z]+#', '', $erating->mediaversity_grade));
                        $filters = $this->cs->search_filters['mediaversity'];
                        $woke_color = $filters[$clear_rating] ? $filters[$clear_rating]['color'] : 1;
                        $rating_text = ' <span title="Mediaversity: ' . $erating->mediaversity_grade . '" class="rating-review woke-color-' . $woke_color . '">' . $erating->mediaversity_grade . '</span>';
                    }
                } else if ($critic->link_id == 179) {
                    // Wokernot
                    $ma = $this->get_ma();

                    $woke = $ma->get_movie_woke($critic->top_movie);

                    if ($woke->wokeornot > 0) {
                        $woke_rating = $woke->wokeornot;
                        $woke_color = 1;
                        if ($woke_rating > 30) {
                            $woke_color = 2;
                        }
                        if ($woke_rating > 60) {
                            $woke_color = 3;
                        }
                        $rating_text = ' <span title="Woke r\' Not: ' . $woke_rating . '" class="rating-review woke-color-' . $woke_color . '">' . $woke_rating . '%</span>';
                    }
                }
            } catch (Exception $exc) {
                
            }
        }
        return $rating_text;
    }

    public function get_tag_link($slug, $name) {
        // Old api
        // $tag = '<a href="/critics/category_' . $slug . '/" title="' . $name . '">' . $name . '</a>';
        // Search api
        $tag = '<a href="/search/tab_critics/tags_' . $slug . '" title="' . $name . '">' . $name . '</a>';

        return $tag;
    }

    public function find_transcriptions($top_movie = 0, $cid = 0, $content = '') {
        $timecodes = array();
        if ($top_movie && $cid && $content) {
            $ma = $this->get_ma();
            $movie = $ma->get_post($top_movie);

            $debug = true;
            $ids = array($cid);

            $critics_search = $this->cs->search_critics($movie, $debug, $ids);

            $fields = array('content', 'cast content', 'year content', 'runtime content', 'director content');
            $types = array('valid', 'other');
            $results = '';
            foreach ($types as $type) {
                if (isset($critics_search[$type][$cid])) {
                    foreach ($fields as $field) {
                        if (isset($critics_search[$type][$cid]['debug'][$field])) {
                            $results .= $critics_search[$type][$cid]['debug'][$field];
                        }
                    }
                }
            }

            $desc_results = array();

            if ($results) {
                if (preg_match_all('/<b>([^<]+)<\/b>/', $results, $match)) {
                    $unique_words = array();
                    foreach ($match[1] as $value) {
                        if (strlen($value) > 2) {
                            $unique_words[$value] = $value;
                        }
                    }

                    $desc = preg_replace('/<div class="transcriptions">.*<\/div>/Us', '', $content);
                    $desc = preg_replace('/<br[^>]*>/', "\n", $desc);
                    $desc = strip_tags($desc);

                    if ($unique_words) {
                        foreach ($unique_words as $key => $value) {
                            $w_arr = explode(' ', $key);
                            $reg = '/<span data-time="([0-9]+\:[0-9]+\:[0-9]+)[^"]*">([^<]*)(' . implode('(?: |<[\/]*span[^>]*>)', $w_arr) . ')([^<]*)/';
                            if (preg_match_all($reg, $content, $match)) {
                                for ($i = 0; $i < sizeof($match[0]); $i++) {
                                    $time_str = $match[1][$i];
                                    $time_sec = strtotime($time_str) - strtotime('TODAY');

                                    $time_str_small = preg_replace('/^00\:/', '', $time_str);

                                    $timecodes[$time_sec] = $time_str_small . ' "... ' . $match[2][$i] . ' <b>' . $match[3][$i] . '</b> ' . $match[4][$i] . ' ..."';
                                }
                            }

                            $reg_desc = '/.*' . $value . '.*/';
                            if (preg_match_all($reg_desc, $desc, $match)) {
                                for ($i = 0; $i < sizeof($match[0]); $i++) {
                                    $result = $match[0][$i];
                                    $desc_results[] = str_replace($value, '<b>' . $value . '</b>', $result);
                                }
                            }
                        }
                    }
                }
            }
        }

        ksort($timecodes);

        return array('time_codes' => $timecodes, 'desc_results' => $desc_results);
    }

    public function get_info_link($cid = 0, $mid = 0, $meta_state = 0) {
        $add_class = '';
        /*
          State:
          1 => 'Approved',
          2 => 'Auto',
          0 => 'Unapproved'
         */
        $add_class = ' u_state';
        $title = 'Movie review binding info';
        if ($meta_state == 2 || $meta_state == 3) {
            $add_class = ' a_state';
            $title = 'This review was attached automatically by our robot';
        }

        $link = '<div data-value="' . $cid . '" data-movie="' . $mid . '" class="a_info' . $add_class . '" title="' . $title . '"></div>';
        return $link;
    }

    public function find_embed($link = '', $cid = 0) {
        $ret = '';
        if (strstr($link, 'twitter.com')) {
            // Try to get embed for crowdsource
            $link_hash = $this->link_hash($link);
            $crowd_data = $this->cm->get_critic_crowd($link_hash);
            if ($crowd_data) {
                $embed = $crowd_data->embed_content;
                if ($embed) {
                    $ret = $embed;
                }
            }
        }
        return $ret;
    }

    public function find_video_link($link, $cid = 0, $only_get = false) {
        $ret = array();
        // https://www.bitchute.com/embed/kntoSwUiKY4T/
        if (preg_match('/bitchute\.com\/(?:embed|video)\/([a-zA-Z0-9\-_]+)/', $link, $match)) {
            if (count($match) > 1) {
                $code = $match[1];
                $embed = 'https://www.bitchute.com/embed/' . $code;
                $ret['video'] = $this->embed_video($embed);
                $ret['img'] = $this->get_bitchute_img($code, $cid, $only_get);
                $ret['type'] = 'bitchute';
            }
        } else if ((strstr($link, 'youtube') || strstr($link, 'youtu.be'))) {
            if (preg_match('#//www\.youtube\.com/embed/([a-zA-Z0-9\-_]+)#', $link, $match) ||
                    preg_match('#//(?:www\.|)youtube\.com/(?:v/|watch\?v=|watch\?.*v=|embed/)([a-zA-Z0-9\-_]+)#', $link, $match) ||
                    preg_match('#//youtu\.be/([a-zA-Z0-9\-_]+)#', $link, $match)) {
                if (count($match) > 1) {
                    $embed = 'https://www.youtube.com/embed/' . $match[1];
                    $ret['video'] = $this->embed_video($embed);
                    $ret['img'] = 'https://img.youtube.com/vi/' . $match[1] . '/hqdefault.jpg';
                    $ret['type'] = 'youtube';
                }
            }
        } else if (strstr($link, 'https://odysee.com/')) {
            // https://odysee.com/@Blackpilled:b/onlypands:9
            // "https://odysee.com/$/embed/onlypands/957cb76fa7b324ba528effbe18412dd2c7b68712?r=7SxiDSy5WmXCoYKTUH3nDhJax2LtpNEq"


            $ret_arr = $this->get_odysee($link, $cid, $only_get);
            $ret['img'] = '';
            $ret['type'] = 'odysee';
            if ($ret_arr) {
                $ret['video'] = $this->embed_video($ret_arr['embed']);
                $ret['img'] = $ret_arr['img'];
            }
        }

        // cache_img
        /* if ($ret['img']) {
          $ret['img'] = $this->cache_img($ret['img']);
          } */

        return $ret;
    }

    public function get_odysee($link, $cid, $only_get = false) {
        $ret = array();
        if ($cid > 0) {
            // Get from db
            $db_data = $this->cm->get_thumb($cid);
            if ($db_data) {
                $ret = json_decode($db_data, true);
            }
        }
        if (!$ret && !$only_get) {
            // Parse data
            $cp = $this->cm->get_cp();
            //$proxy = '107.152.153.239:9942';

            $proxy = $this->cm->get_parser_proxy(true);
            $proxy_text = '';
            if ($proxy) {
                $proxy_num = array_rand($proxy);
                $proxy_text = trim($proxy[$proxy_num]);
            }
            $data = $cp->get_proxy($link, $proxy_text, $headers);

            if ($data) {
                // Embed
                $embed = '';
                if (preg_match('/"embedUrl": "([^"]+)"/', $data, $match)) {
                    $embed = $match[1];
                }
                $img = '';

                if (preg_match('/"url": "(https:\/\/thumbnails\.odycdn\.com\/[^"]+)"/', $data, $match)) {
                    $img = $match[1];
                }

                if ($img && $embed) {
                    $ret = array(
                        'img' => $img,
                        'embed' => $embed
                    );
                    if ($cid > 0) {
                        // Save to db
                        $to_db = json_encode($ret);
                        $this->cm->add_thumb($cid, $to_db);
                    }
                }
            }
        }
        return $ret;
    }

    public function get_bitchute_img($code = '', $cid = 0, $only_get = false) {

        $img = '';

        if ($cid > 0) {
            // Get from db
            $img = $this->cm->get_thumb($cid);
        }

        if (!$img && !$only_get) {
            // Parse thumb
            $cp = $this->cm->get_cp();
            //$proxy = '107.152.153.239:9942';            
            $proxy = $this->cm->get_parser_proxy(true);
            $proxy_text = '';
            if ($proxy) {
                $proxy_num = array_rand($proxy);
                $proxy_text = trim($proxy[$proxy_num]);
            }

            $b_link = 'https://www.bitchute.com/video/' . $code . '/';
            $data = $cp->get_proxy($b_link, $proxy_text, $headers);

            if ($data) {
                if (preg_match('/<meta property="og:image" content="([^"]+)"/', $data, $match)) {
                    $img = $match[1];
                }
            }

            if ($img && $cid > 0) {
                // Save to db
                $this->cm->add_thumb($cid, $img);
            }
        }
        return $img;
    }

    public function embed_video($link) {
        $code = '<div class="embed-responsive embed-responsive-16by9">'
                . '<iframe class="embed-responsive-item" src="' . $link . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
                . '</div><br />';
        return $code;
    }

    public function get_audience_templ($critic = array(), $avatars = '', $fullsize = false) {


        $title = $critic->title;

        if (!$title) {
            $title = ' ';
        }
        $content = $critic->content;
        if (!$content) {
            $content = $title;
        }

        $aid = $critic->aid;
        $author = $this->cm->get_author($aid);

        // Author name
        $author_title = $author->name;
        $author_title = $this->pccf_filter($author_title);

        // WP avatar
        $wp_avatar = '';
        $wp_uid = $author->wp_uid;
        $cav = $this->cm->get_cav();
        if ($wp_uid) {
            $wp_avatar = $cav->get_author_avatar($author, 64);
        }
        $author_admin_img = '';

        if (!$avatars && !$wp_avatar) {
            // Author image

            $author_options = unserialize($author->options);
            $author_img = $author_options['image'];

            if ($author_img) {
                try {
                    $image = $this->get_local_thumb(100, 100, $author_img);
                    $author_admin_img = '<div class="a_img_container" style="background: url(' . $image . '); background-size: cover;"></div>';
                } catch (Exception $exc) {
                    
                }
            }
            if (!$author_admin_img) {
                // Empty image
                $avatars = $this->get_avatars();
            }
        }

        $umeta = '';
        if ($wp_uid) {
            // User profile link
            $uc = $this->cm->get_uc();
            $wp_user = $uc->getUserById($wp_uid);

            $author_link = $uc->get_user_profile_link($wp_user->url);
            $ucarma_class = ($wp_user->carma < 0) ? " minus" : " plus";
            $umeta = '<div class="umeta' . $ucarma_class . '">
                    <span class="urating" ><i class="icon-star"></i>' . (int) $wp_user->rating . '</span>                   
                </div>';
        } else {
            // Search 
            $author_link = '/search/tab_critics/from_' . $author->id;
        }
        $author_title_link = '<a href="' . $author_link . '">' . $author_title . '</a>';

        // Tags
        $catdata = '';
        $max_tags = 3;
        $tags = $this->cm->get_author_tags($author->id);
        if (sizeof($tags)) {
            $tags_count = 1;
            foreach ($tags as $tag) {
                $catdata .= $this->get_tag_link($tag->slug, $tag->name);
                $tags_count += 1;
                if ($tags_count > 3) {
                    break;
                }
            }
        }

        if ($catdata) {
            $catdata = '<div class="a_cat">' . $catdata . '</div>';
        }

        // Time
        $ptime = $critic->date;
        $critic_addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

        // Link to full post
        $link = $this->get_critic_url($critic);

        // Rating
        $theme_rating = '';
        $rating = $this->cm->get_post_rating($critic->id);
        if ($rating) {
            $theme_rating = $this->theme_rating($rating, $fullsize);
        }
        $ip = isset($rating['ip']) ? $rating['ip'] : '';

        // Country

        $country_img = $this->theme_country_flag_by($ip);
        /* $country_data = $this->cm->get_geo_flag_by_ip($ip);
          if ($country_data['path']) {
          $country_name = $country_data['name'];
          $country_img = '<div class="nte cflag" title="' . $country_name . '">
          <div class="nbtn"><img src="' . $country_data['path'] . '" /></div>
          <div class="nte_show"><div class="nte_in"><div class="nte_cnt">
          This review was posted from ' . $country_name . ' or from a VPN in ' . $country_name . '.
          </div></div></div></div>';
          }
         */
        $short_codes_exist_class = '';
        $wp_core = '';

        if (!$fullsize) {
            if (strstr($content, '[su_')) {
                // Remove su spoilers
                $wp_core = ' wp_core';

                $regv = '#\[su_([^\]]+)\].+\[/su_[\w\d]+\]#Us';
                if (preg_match_all($regv, $content, $mach)) {
                    // var_dump($mach);              
                    foreach ($mach[0] as $i => $val) {
                        $rtitle = '';
                        $reg2 = '#title="([^\"]+)#';
                        if (preg_match($reg2, $mach[1][$i], $m2)) {
                            $rtitle = $m2[1];
                        }

                        $content = str_replace($val, $rtitle, $content);
                    }
                }
                // Remove all custom tags
                $regv = '#\[su_[^\]]+\]#Us';
                $content = preg_replace($regv, '', $content);
            }

            $content = $this->format_content($content, 400);
        } else {
            // Check links
            $content = $this->replacelink($content);

            // Active links
            $content = $this->active_links($content, true, true);

            if (strstr($content, '[su_')) {

                $short_codes_exist_class = ' short_codes_enabled';

                // Check short codes
                if (function_exists('do_shortcode')) {
                    $content = do_shortcode($content);
                    $content = strip_shortcodes($content);
                }
            }
            /*
              // Check su_spoilers
              $regv = '#\[su_spoiler([^\]]+)\]#';
              if (preg_match_all($regv, $content, $mach)) {
              // var_dump($mach);
              $content = str_replace('[/su_spoiler]', '</div></details>', $content);

              foreach ($mach[0] as $i => $val) {
              $rtitle = 'Spoiler';
              $reg2 = '#title="([^\"]+)#';
              if (preg_match($reg2, $mach[1][$i], $m2)) {
              $rtitle = $m2[1];
              }
              $spoiler = '<details><summary>' . $rtitle . '</summary><div>';
              $content = str_replace($val, $spoiler, $content);
              }
              } */
        }

        $content = $this->pccf_filter($content);
        $title = $this->check_spoiler($title);
        $title = $this->pccf_filter($title);

        if ($critic->blur) {
            $content = $this->spoiler_content($content);
        } else {
            $content = $this->check_spoiler($content);
        }

        if ($content || $title || $theme_rating) {

            /* if ($avatars == 'staff') {
              return '<div class="vote_main">' . $theme_rating . '</div>' . $content . '</div>';
              } */

            if ($fullsize) {
                if ($title) {
                    $title = '<strong class="review-title">' . $title . '</strong>';
                }

                $content = '<div class="full_review_content_block' . $short_codes_exist_class . '">' . $title . '<div class="vote_main">' . $theme_rating . $content . '</div></div>';
            } else {
                $content = '<a class="icntn' . $wp_core . '" href="' . $link . '">
    <div class="vote_main">' . $theme_rating . '<div class="vote_content"><strong>' . $title . '</strong><br>' . $content . "</div>
    </div>
</a>";
            }
        }

        $stars_data = 0;
        if ($rating) {
            $stars_data = $rating['r'];
        }

        $actorsdata = '';

        if ($wp_avatar) {
            $actorsdata = $wp_avatar;
        } else if ($author_admin_img) {
            $actorsdata = $author_admin_img;
        } else if ($avatars) {
            $array_avatars = $avatars[intval($stars_data)];

            if (is_array($array_avatars)) {
                $avatar_user = $cav->get_avatar_rand_key($array_avatars, $critic->id);
                //$rand_keys = array_rand($array_avatars, 1);
                //$avatar_user = $array_avatars[$rand_keys];
            }
            if ($avatar_user) {
                $actorsdata = '<div class="a_img_container_audience" style="background: url(' . WP_SITEURL . '/wp-content/uploads/avatars/custom/' . $avatar_user . '); background-size: cover;"></div>';
            }
        }

        if (!$actorsdata) {
            $actorsdata = '<span></span>';
        }

        $actorsdata_link = '<a href="' . $author_link . '">' . $actorsdata . '</a>';

        // get link
        // $link = $link . '?a=' . $c_pid;

        $review_bottom = '<div class="review_bottom"><div class="r_type"></div><div class="r_right"><div class="r_date">' . $critic_addtime . '</div>' . $country_img . '</div></div>';

        if ($fullsize) {

            $actorsresult = '
' . $content . $review_bottom . '<div class="em_hold"></div><div class="amsg_aut">' . $actorsdata_link . '
        <div class="review_autor_name">' . $author_title_link . $umeta . $catdata . '</div>
       
    </div>';
        } else {
            $reaction_data = $this->get_user_reactions($critic->id);

            $actorsresult = '<div class="a_msg">
    <div class="a_msg_i">
        ' . $content . $review_bottom . '<div class="ugol"><div></div></div>
    </div>
        <div class="em_hold"></div>
        <div class="amsg_aut">
            ' . $actorsdata_link . '
            <div class="review_autor_name">' . $author_title_link . $umeta . $catdata . '</div>
            ' . $reaction_data . '
        </div>
</div>';
        }
        return $actorsresult;
    }

    public function find_staff_rating($content) {
        // DEPRECATED UNUSED
        // Get rating code    
        $content_rating = '';
        $regv = '#\[stfu_ratings([^\]]+)\]#';
        if (preg_match($regv, $content, $mach)) {
            $content = str_replace($mach[0], '', $content);
            $content = $this->replacelink($content);
            $array = explode(' ', $mach[1]);
            foreach ($array as $val) {
                if ($val) {
                    $val = explode('=', $val);
                    $current_type = trim($val[0]);
                    $current_value = trim(str_replace('"', '', $val[1]));
                    $curentpercent = 0;
                    if (strstr($current_value, '.')) {
                        $current_value_array = explode('.', $current_value);
                        $current_value = $current_value_array[0];
                        $curentpercent = 1;
                    }
                    if ($current_type == 'worthwhile') {
                        $current_type = 'rating';
                        $stars = $this->rating_images($current_type, $current_value, $curentpercent);
                    } else if ($current_type == 'slider') {
                        $current_type = 'vote';

                        if ($current_value == 'pay') {
                            $current_value = 1;
                        } else if ($current_value == 'free') {
                            $current_value = 3;
                        } else if ($current_value == 'skip') {
                            $current_value = 2;
                        }

                        $vote = $this->rating_images($current_type, $current_value, $curentpercent);
                    } else {
                        if ($current_value == 0) {
                            continue;
                        }
                        $other .= $this->rating_images($current_type, $current_value, $curentpercent);
                    }
                }
            }
            $content_rating = '<div class="vote">' . $stars . $vote . $other . '</div>';
        }
        return $content_rating;
    }

    public function theme_rating($rating, $fullsize = true) {

        $stars = $rating['r'];
        $hollywood = $rating['h'];
        $affirmative = $rating['a'];
        $god = $rating['g'];
        $lgbtq = $rating['l'];
        $misandry = $rating['m'];
        $patriotism = $rating['p'];
        $vote = $rating['v'];

        $vote_key = 'vote';
        if ($vote) {
            $vote = round($vote, 0);
            $vote = $this->rating_images($vote_key, $vote);
        } else {
            $vote = '';
        }

        if (!$stars)
            $stars = 0;

        $stars = round($stars, 1);
        $stars_data = $stars;

        $stars = $this->rating_images('rating', $stars);

        if ($this->show_hollywood && $hollywood) {
            $hollywood = $this->rating_images('hollywood', $hollywood);
        } else {
            $hollywood = '';
        }
        if ($affirmative) {
            $affirmative = $this->rating_images('affirmative', $affirmative);
        } else {
            $affirmative = '';
        }

        if ($god) {
            $god = $this->rating_images('god', $god);
        } else {
            $god = '';
        }

        if ($lgbtq) {
            $lgbtq = $this->rating_images('lgbtq', $lgbtq);
        } else {
            $lgbtq = '';
        }

        if ($misandry) {
            $misandry = $this->rating_images('misandry', $misandry);
        } else {
            $misandry = '';
        }
        if ($patriotism) {
            $patriotism = $this->rating_images('patriotism', $patriotism);
        } else {
            $patriotism = '';
        }

        $ret = '<div class="vote">' . $stars . $vote;
        if ($fullsize) {
            $ret .= $hollywood . $misandry . $lgbtq . $patriotism . $affirmative . $god;
        }
        $ret .= '</div>';
        return $ret;
    }

    public function get_critic_slug($post) {
        // TODO refactor
        return $this->cm->get_critic_slug($post);
    }

    public function get_critic_url($post) {
        $slug = $this->get_critic_slug($post);
        $link = WP_SITEURL . '/critics/' . $slug . '/';
        return $link;
    }

    public function get_avatars() {
        return $this->cm->get_avatars();
    }

    /*
     * Movies
     */

    public function ajax_load_movie($id = 0, $add_time = 0) {
        if (!$id) {
            return '';
        }
        $item = new stdClass();
        $item->id = $id;
        $item->add_time = $add_time;
        $cache_item = $this->theme_movie_item($item);

        print $cache_item;
    }

    public function ajax_load_movie_rating($ids = array()) {
        if (!$ids) {
            return '';
        }
        $ids_data = array();
        foreach ($ids as $id) {
            $ids_data[$id] = $id;
        }
        !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';
        $RWT_RATING = new RWT_RATING;
        $rating_data = $RWT_RATING->get_rating_data($ids_data, 0);
        // Watchlist
        $wl = $this->cm->get_wl();
        $watchlists = $wl->get_watch_blocks($ids);

        $ret = array();
        foreach ($ids as $id) {
            $ret[$id] = array(
                'rating' => isset($rating_data[$id]) ? $rating_data[$id] : array(),
                'watchlist' => isset($watchlists[$id]) ? $watchlists[$id] : array(),
            );
        }

        print json_encode($ret);
    }

    public function render_movies_list($data = array(), $echo = true) {
        /*
          stdClass Object
          (
          [id] => 14899
          [rwt_id] => 39545
          [title] => Terminator 2: Judgment Day
          [release] => 1991-07-03
          [type] => Movie
          [year] => 1991
          [add_time] => 1696122678
          [post_name] => terminator-2-judgment-day
          [w] => 1577
          [rrt] => 93
          [rrta] => 95
          [rrtg] => 102
          [movie_id] => 103064
          )
         */
        $items = array();
        foreach ($data as $item) {
            //print_r($item);
            // Get item from cache            
            $cache_item = $this->theme_movie_item($item, true);

            if ($cache_item) {
                // Item exist on cache
                if ($echo) {
                    print $cache_item;
                } else {
                    $items[] = $cache_item;
                }
            } else {
                // Default tempalate
                $def_item = $this->default_movie_template($item);
                if ($echo) {
                    print $def_item;
                } else {
                    $items[] = $def_item;
                }
            }
        }
        return $items;
    }

    public function theme_movie_item($item, $only_get = false) {
        if ($this->cache_results) {
            $item_theme = $this->cache_theme_movie_item_get($item, $only_get);
        } else {
            if (!$only_get) {
                $item_theme = $this->theme_movie_item_get($item);
            }
        }
        return $item_theme;
    }

    public function cache_theme_movie_item_get($item, $only_get = false) {
        $arg = (array) $item;
        $filename = "m-{$item->id}-{$item->add_time}";
        $str = ThemeCache::cache('theme_movie_item_get', false, $filename, 'movies', $this, $arg, $only_get);
        return $str;
    }

    public function theme_movie_item_get($arg) {
        // Theme single movie item for cache
        ob_start();
        global $post_an;

        $movie = (object) $arg;
        $ma = $this->get_ma();
        $post_an = $ma->get_post($movie->id);
        $id = $post_an->id;
        $title = $post_an->title;
        $name = $post_an->post_name;
        $post_type = strtolower($post_an->type);

        $user_blocks = array();
        $movie_object = array();

        if ($post_type == 'movie' || $post_type == 'tvseries' || $post_type == 'videogame') {
            if (!function_exists('template_single_movie')) {
                include(ABSPATH . "wp-content/themes/custom_twentysixteen/template/movie_single_template.php");
            }
            template_single_movie($id, $title, $name, '', $movie_object, $user_blocks);
        }
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function default_movie_template($item) {
        ob_start();
        $id = $item->id;
        $title = $item->title;
        $year = $item->year;
        $add_time = $item->add_time;
        $post_name = $item->post_name;

        $movie_t = strtolower($item->type);
        if ($movie_t == 'movie') {
            $movie_t = 'movies';
        }
        $tmd_s = $movie_t;
        $movie_details = 'Movie Details & Credits';
        if ($movie_t == 'movies') {
            $movie_link_desc = 'class="card_movie_type ctype_movies" title="Movie"';
            $tmd = 'Movie';
            $tmd_s = 'movie';

            $movie_details = 'Movie Details & Credits';
        } else if ($movie_t == 'tvseries') {
            $movie_link_desc = 'class="card_movie_type ctype_tvseries" title="TV Show"';
            $movie_details = 'TV Series Details & Credits';

            $tmd = 'TV Series';
            $tmd_s = 'show';
        } else if ($movie_t == 'videogame') {
            $movie_link_desc = 'class="card_movie_type ctype_videogame" title="Game"';
            $movie_details = 'Game Details & Credits';

            $tmd = 'Game';
            $tmd_s = 'game';
        } else {
            $movie_t = 'title';
            $movie_link_desc = 'class="card_movie_type ctype_other" title="Title"';
            $movie_details = 'Details & Credits';

            $tmd = 'Other';
            $tmd_s = 'title';
        }

        $link = $this->get_simple_movie_link($post_name, $item->type);
        $link_before = '<a href="' . $link . '">';
        $link_after = '</a>';

        $thumbs = array([220, 330], [440, 660]);
        $array_tsumb = array();

        foreach ($thumbs as $thumb) {
            $array_tsumb[] = $this->get_thumb_path_full($thumb[0], $thumb[1], $id, $add_time);
        }
        ?>
        <div id="movie-<?php echo $id ?>" class="movie_container movie_block loadblock" data-id="<?php echo $id; ?>" data-func="movie_cache" data-replace=".movie_button_action,.movie_description" data-keys="<?php echo $add_time; ?>">
            <div class="movie_poster">
                <?php echo $link_before; ?>
                <div class="image">
                    <div class="wrapper">
                        <span <?php echo $movie_link_desc; ?> ></span>
                        <img loading="lazy" class="poster" src="<?php echo $array_tsumb[0]; ?>"
                             <?php if ($array_tsumb[1]) { ?> srcset="<?php echo $array_tsumb[0]; ?> 1x, <?php echo $array_tsumb[1]; ?> 2x"<?php } ?> >
                    </div>
                </div>
                <?php echo $link_after; ?>
                <div class="movie_button_action"></div>
            </div>
            <div class="movie_watch" style="display: none"></div>
            <div class="movie_description">
                <div class="header_title">
                    <h1 class="entry-title">
                        <?php echo $link_before; ?>
                        <?php echo $title . ' (' . $year . ')' ?>
                        <?php echo $link_after; ?>
                    </h1>
                </div>

                <div class="movie_description_container">
                    <div class="movie_summary">
                        <div class="user_blocks"></div>
                    </div>
                </div>
            </div>
            <div class="rating_holder"></div>
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function get_simple_movie_link($post_name, $type) {
        $movie_t = strtolower($type);
        if ($movie_t == 'movie') {
            $movie_t = 'movies';
        }
        $link = '/' . $movie_t . '/' . $post_name . '/';
        return $link;
    }

    public function get_small_movie_templ($movie, $external_link = '') {
        if (!$movie) {
            return '';
        }

        $id = $movie->id;

        // Cast
        $cast_obj = json_decode($movie->actors);
        $cast = $this->get_cast_string($cast_obj, 50);

        // Title
        $title = $movie->title;

        // Post name

        $ma = $this->get_ma();
        $slug = $ma->get_post_slug($movie->type);
        $url = $ma->get_movie_link($movie);

        // release
        $release = $movie->release;
        if ($release) {
            $release = strtotime($release);
            $release = date('Y', $release);
            if (strstr($title, $release)) {
                $release = '';
            } else {
                $release = ' (' . $release . ')';
            }
        }


        $poster_link_90 = $this->get_thumb_path_full(90, 120, $id);

        $array_type = array('tvseries' => 'TV series');
        $item_type = $array_type[$slug];
        if (!$item_type)
            $item_type = ucfirst($slug);

        $img = '<img src="' . $poster_link_90 . '">';

        if ($external_link) {
            $external_link = ' target="_blank" ';
        }
        $content = '<div class="full_review_movie"><a ' . $external_link . ' href="' . $url . '/" >' . $img . '<div><span  class="itm_hdr">' . $title . $release . '</span><span class="item_type">' . $item_type . '</span><span>' . $cast . '</span></div></a></div>';

        return $content;
    }

    /*
     * Movies db an
     */

    public function get_or_create_ma_post_name($id = 0, $rwt_id = 0, $title = '', $type = '') {

        $ma = $this->get_ma();
        $post_name = $ma->get_post_name($id);

        if (!$post_name) {

            if (!$rwt_id || !$title || !$type) {
                $post = $ma->get_post($id);
                $rwt_id = $rwt_id ? $rwt_id : $post->rwt_id;
                $title = $title ? $title : $post->title;
                $type = $type ? $type : $post->type;
            }

            // Create it
            if (!$post_name) {
                // Type: Movie, TVseries
                $year = $post->year;
                $post_name = $ma->create_post_name($id, $title, $type, $year);
            }
        }
        return $post_name;
    }

    public function template_single_movie_small_an($item, $no_links = '') {
        $ma = $this->get_ma();
        $id = $item->id;
        $rwt_id = $item->rwt_id;
        $title = $item->title;
        $type = $item->type;

        if (strtolower($type) == 'movie') {
            $slug = 'movies';
        } else {
            $slug = strtolower($type);
        }

        $array_type = array('tvseries' => 'TV series');
        $item_type = $array_type[$slug];
        if (!$item_type)
            $item_type = ucfirst($slug);


        $url = $ma->get_movie_link($item);

        $date = $item->year;

        // Cast
        $cast_obj = json_decode($ma->get_cast($id));
        $cast = $this->get_cast_string($cast_obj, 50);

        if ($date) {
            if (strstr($title, $date)) {
                $date = '';
            } else {
                $date = ' (' . $date . ')';
            }
        }

        $img = '<img src="' . $this->get_thumb_path_full(90, 120, $id) . '">';

        if ($no_links) {
            $content = '<div class="full_review_movie movie_touch" id="' . $id . '">' . $img . '<div class="movie_link_desc"><span  class="itm_hdr">' . $title . $date . '</span><span class="item_type">' . $item_type . '</span><span>' . $cast . '</span></div></div>';
        } else {
            $content = '<div class="full_review_movie"><a href="' . $url . '/" class="movie_link" >' . $img . '<div class="movie_link_desc"><span  class="itm_hdr">' . $title . $date . '</span><span class="item_type">' . $item_type . '</span><span>' . $cast . '</span></div></a></div>';
        }

        return $content;
    }

    public function get_cast_string($cast_data, $len = 50) {
        $cast = '';
        $to_add = array();

        if (isset($cast_data->s) && sizeof((array) $cast_data->s)) {
            foreach ($cast_data->s as $value) {
                $to_add[] = $value;
            }
        }

        if (isset($cast_data->m) && sizeof((array) $cast_data->m)) {
            foreach ($cast_data->s as $value) {
                $to_add[] = $value;
            }
        }

        if ($to_add) {
            $cast = implode(', ', $to_add);
        }

        if ($cast) {
            $cast = $this->cm->crop_text($cast, 50, false);
            $cast = trim($cast, ',');
        }
        return $cast;
    }

    /*
     * Critic filters 
     */

    public function format_content($content = '', $crop_len = 0, $max_p = 0) {
        // Remove tags
        $content = preg_replace('/<script.*\/script>/Uis', '', $content);
        $content = preg_replace('/<style.*\/style>/Uis', '', $content);
        $content = preg_replace('/<!--.*-->/Uis', '', $content);
        $content = preg_replace('/<!\[CDATA\[.*\]\]>/Uis', '', $content);

        $content = str_replace('<br>', '\n', $content);
        $content = str_replace('<br/>', '\n', $content);
        $content = str_replace('<br />', '\n', $content);
        $content = str_replace('</p>', '\n', $content);
        $content = str_replace('</div>', '\n', $content);
        $content = strip_tags($content);

        $content = rtrim($content, "!,.-");

        // Crop
        if ($crop_len) {
            $content = $this->cm->crop_text($content, $crop_len);
        }

        $content_arr = explode('\n', $content);
        if (sizeof($content_arr)) {
            $new_content = array();
            $i = 0;
            foreach ($content_arr as $row) {
                $new_content[$i] .= $row . ' ';
                if (strlen($row) > 10) {
                    $i += 1;
                }
                if ($max_p && $i >= $max_p - 1) {
                    break;
                }
            }
            if (sizeof($new_content) > 1) {
                $content = '<p>' . implode('</p><p>', $new_content) . '</p>';
            } else {
                $content = '<p>' . $new_content[0] . '</p>';
            }
        } else {
            $content = "<p>$content</p>";
        }
        return $content;
    }

    private function filter_full_staff_content($content, $cid = 0) {

        if (function_exists('do_shortcode')) {
            $content = do_shortcode($content);
            add_filter('strip_shortcodes_tagnames', function ($tags_to_remove) {
                $tags_to_remove[] = 'wp_google_searchbox';
                $tags_to_remove[] = 'pt_view';
                return $tags_to_remove;
            });
            $content = strip_shortcodes($content);
        } else {
            $regv = '#\[su_spoiler([^\]]+)\]#';
            if (preg_match_all($regv, $content, $mach)) {
                // var_dump($mach);
                $content = str_replace('[/su_spoiler]', '</div></details>', $content);

                foreach ($mach[0] as $i => $val) {
                    $rtitle = 'Spoiler';
                    $reg2 = '#title="([^\"]+)#';
                    if (preg_match($reg2, $mach[1][$i], $m2)) {
                        $rtitle = $m2[1];
                    }
                    $spoiler = '<details><summary>' . $rtitle . '</summary><div>';
                    $content = str_replace($val, $spoiler, $content);
                }
            }
        }
        $stars = '';
        $vote = '';
        $other = '';

        // Rating
        // Get rating from db
        $rating = $this->cm->get_post_rating($cid, true);
        if ($rating) {
            $content_rating = $this->theme_rating($rating);
        }

        if (!$content_rating) {
            // UNUSED
            // $content_rating = $this->find_staff_rating($content);
        }

        $content = '<div class="vote_main">' . $content_rating . '<div class="vote_content"><br>' . $content . "</div></div>";

        $content = str_replace('<strong>Other reviews by Libertarian Agnostic:</strong>', '', $content);
        $content = str_replace('<strong>Search all Staff Reviews from STFU Hollywood:</strong>', '', $content);

        //  $content=' content ';

        return $content;
    }

    private function staff_content_filter($content, $cid = 0, $fullsize = true) {

        // Rating
        // Get rating in db
        $rating = $this->cm->get_post_rating($cid);
        if ($rating) {
            $content_rating = $this->theme_rating($rating, $fullsize);
        }

        if (!$content_rating) {
            // Find rating string in the content. UNUSED
            // $content_rating = $this->find_staff_rating($content);
        }


        $content = $this->format_content($content, 400, 2);

        $content = '<div class="vote_main">' . $content_rating . '<div class="vote_content">' . $content . "</div></div>";
        return $content;
    }

    private function pro_content_filter($content = '', $critic = '', $permalink = '', $fullsize = '') {
        $video = '';
        /*
          $regex_pattern = "/(youtube.com|youtu.be)\/(watch)?(\?v=)?(\S+)?/";
          if (preg_match($regex_pattern, $permalink, $mach)) {
          if ($fullsize) {
          $video = '<div class="embed-responsive embed-responsive-16by9"><iframe style="width:100%; height:100%;" src="https://www.youtube.com/embed/' . $mach[4] . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
          } else {
          $video = '[Video included in the review]<br>';
          }
          } else if (strstr($permalink, 'bitchute.com/')) {
          if ($fullsize) {
          $permalink = str_replace('/video/', '/embed/', $permalink);

          $video = '<div class="embed-responsive embed-responsive-16by9"><iframe style="width:100%; height:100%;" src="' . $permalink . '" ></iframe></div>';
          } else {
          $video = '[Video included in the review]<br>';
          }

          $permalink = str_replace('/embed/', '/video/', $permalink);
          $content = '';
          }
         */
        $image = '';

        $crop_len = 200;
        if ($fullsize) {
            $crop_len = 800;
            // try to find img
            $regi = '/<img[^>]+src="([^"]+)"/Ui';

//            if (preg_match($regi, $content, $mach)) {
//
//                $image = $this->get_local_thumb(640, 0, $mach[1]);
//
//                if ($image) {
//                    $image = '<div style="text-align: center;margin: 10px 0;"><img src="' . $image . '"></div>';
//                }
//            }
        }

        $content = $this->format_content($content, $crop_len);
        $content = $image . $content;

        // Autoblur. TODO validate autoblur in critic content page, critics list, last critics
        $is_autoblur = false;
        if ($critic && $critic->author_options) {
            $author_options = unserialize($critic->author_options);
            if (isset($author_options['autoblur']) && $author_options['autoblur'] == 1) {
                $is_autoblur = true;
            }
        }

        if ($is_autoblur && $content) {
            $content = '[spoiler]' . $content . '[/spoiler]';
        }


        return $content;
    }

    public function pccf_filter($text) {
        $cc = $this->cm->get_cc();
        $clear_data = $cc->validate_content($text);
        return $clear_data['content'];
    }

    public function pccf_filter_old($text) {

        $valprev = '';
        $tmp = $this->get_option('pccf_options');

        if ($tmp) {
            // echo  $tmp['txtar_keywords'];

            $exclude_id_list = $tmp['txt_exclude'];
            $exclude_id_array = explode(', ', $exclude_id_list);

            $wildcard_filter_type = $tmp['rdo_word'];
            $wildcard_char = $tmp['drp_filter_char'];

            if ($wildcard_char == 'star') {
                $wildcard = '*';
            } else {
                if ($wildcard_char == 'dollar') {
                    $wildcard = '$';
                } else {
                    if ($wildcard_char == 'question') {
                        $wildcard = '?';
                    } else {
                        if ($wildcard_char == 'exclamation') {
                            $wildcard = '!';
                        } else {
                            if ($wildcard_char == 'hyphen') {
                                $wildcard = '-';
                            } else {
                                if ($wildcard_char == 'hash') {
                                    $wildcard = '#';
                                } else {
                                    if ($wildcard_char == 'tilde') {
                                        $wildcard = '~';
                                    } else {
                                        $wildcard = '';
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $filter_type = $tmp['rdo_case'];
            $db_search_string = $tmp['txtar_keywords'];

            $keywords = array_map('trim', explode(',', $db_search_string));    // explode and trim whitespace
            $keywords = array_unique($keywords);    // get rid of duplicates in the keywords textbox
            $whole_word = $tmp['rdo_strict_filtering'] == 'strict_off' ? false : true;

            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);    // remove whitespace chars from start/end of string
                if (strlen($keyword) > 2) {
                    $replacement = $this->censor_word($wildcard_filter_type, $keyword, $wildcard);
                    if ($filter_type == "insen") {
                        $text = $this->str_replace_word_i($keyword, $replacement, $text, $wildcard_filter_type, $keyword, $wildcard, $whole_word);
                    } else {
                        $text = $this->str_replace_word($keyword, $replacement, $text, $whole_word);
                    }
                }
            }

            return $text;
        }
    }

    public function censor_word($wildcard_filter_type, $keyword, $wildcard) {

        if ($wildcard_filter_type == 'first') {
            $keyword = substr($keyword, 0, 1) . str_repeat($wildcard, strlen(substr($keyword, 1)));
        } else {
            if ($wildcard_filter_type == 'all') {
                $keyword = str_repeat($wildcard, strlen(substr($keyword, 0)));
            } else {
                $keyword = substr($keyword, 0, 1) . str_repeat($wildcard, strlen(substr($keyword, 2))) . substr($keyword, -1, 1);
            }
        }

        return $keyword;
    }

    public function str_replace_word($needle, $replacement, $haystack, $whole_word = true) {
        $needle = str_replace('/', '\\/', preg_quote($needle));    // allow '/' in keywords
        $pattern = $whole_word ? "/\b$needle\b/" : "/$needle/";
        $haystack = preg_replace($pattern, $replacement, $haystack);

        return $haystack;
    }

    public function str_replace_word_i($needle, $replacement, $haystack, $wildcard_filter_type, $keyword, $wildcard, $whole_word = true) {

        $needle = str_replace('/', '\\/', preg_quote($needle));    // allow '/' in keywords
        $pattern = $whole_word ? "/\b$needle\b/i" : "/$needle/i";
        $haystack = preg_replace_callback(
                $pattern, function ($m) use ($wildcard_filter_type, $keyword, $wildcard) {
                    return $this->censor_word($wildcard_filter_type, $m[0], $wildcard);
                }, $haystack);

        return $haystack;
    }

    public function active_links($content, $find_video = false, $find_images = false, $no_follow = false) {
        // $pattern = '# (https://[\w\d-_\.]+)( |\n) #i';

        $pattern = '/(((http|https)\:\/\/)|(www\.|))[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\:[0-9]+)?(\/\S*)?/';

        if (preg_match_all($pattern, $content, $match, PREG_PATTERN_ORDER)) {
            // print_r($match);
            $i = 0;
            foreach ($match[0] as $link) {

                $need_replace = true;
                $valid_link = $link;
                $first = $match[1][$i];
                if ($first == 'www.') {
                    $valid_link = 'https://' . $link;
                } else if ($first == '') {
                    $valid_link = 'https://' . $link;
                    $need_replace = false;
                }
                $theme_link = '';

                if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $link)) {
                    $theme_link = '<img src="https://zeitgeistreviews.com/wp-content/themes/custom_twentysixteen/images/placeholder.png" srcset="' . $valid_link . '" loading="lazy" />';
                } else if ($find_video) {
                    $video_link = $this->find_video_link($valid_link);
                    if ($video_link) {
                        $theme_link = $video_link['video'];
                        if (strstr($content, '="' . $link) || strstr($content, '=\'' . $link)) {
                            $theme_link = '';
                        }
                    }
                } else {
                    if ($need_replace) {
                        $no_follow_rel = '';
                        if ($no_follow) {
                            $no_follow_rel = ' rel="nofollow"';
                        }
                        $theme_link = '<a href="' . $valid_link . '" target="_blank"' . $no_follow_rel . '>' . $valid_link . '</a>';
                    }
                }

                if ($theme_link) {
                    $content = str_replace($link, $theme_link, $content);
                }

                $i++;
            }
        }
        return $content;
    }

    public function replacelink($content) {
        $regv = '#<a([^\>]+)\>#';
        if (preg_match_all($regv, $content, $match)) {
            foreach ($match[1] as $value) {
                if (!strstr($value, '_blank')) {
                    $content = str_replace($value, ' target="_blank" ' . $value, $content);
                }
            }
        }
        return $content;
    }

    public function get_spoiler_data($content) {
        $rand = mt_rand();
        return '<spoiler class="spoiler_default" id="spoiler-' . $rand . '" style="display: block;">' . $content . '</spoiler>';
    }

    public function check_spoiler($content = null) {

        if (strstr($content, '[spoiler]')) {
            if (!strstr($content, '[/spoiler]')) {
                $content = $content . '[/spoiler]';
            }

            if (preg_match_all('/\[spoiler\](.+)\[\/spoiler\]/Us', $content, $match)) {
                for ($i = 0; $i < sizeof($match[0]); $i++) {
                    $full_sp = $match[0][$i];
                    $text_sp = $match[1][$i];
                    $new_content = $this->spoiler_content($text_sp);
                    $content = str_replace($full_sp, $new_content, $content);
                }
            }
        }
        return $content;
    }

    public function spoiler_content($content) {
        $rand = mt_rand();
        $new_content = '<spoiler class="spoiler_default" id="spoiler-' . $rand . '">' . $content . '</spoiler>';
        return $new_content;
    }

    /*
     * Dinamic poster logic
     * 
     * Add to ngnix:
      ###
      ### Dinamic thumbs support.
      ###
      location ^~ /wp-content/uploads/thumbs/poster/ {
      access_log off;
      log_not_found off;
      expires    30d;
      add_header X-Header "DT Generator 1.0";
      try_files  $uri @index;
      }

      location @index {
      rewrite ^/(.*)$ /index.php last;
      }
     */

    public function get_thumb_og_images($id) {

        !class_exists('RWTimages') ? include ABSPATH . "analysis/include/rwt_images.php" : '';
        $time = RWTimages::get_last_time($id);
        return RWTimages::get_simple_image_link('m_' . $id, 640, $time);
    }

    public function get_last_movie_update($id) {
        !class_exists('RWTimages') ? include ABSPATH . "analysis/include/rwt_images.php" : '';
        $time = RWTimages::get_last_time($id);
        return $time;
    }

    public function get_thumb_path_full($w, $h, $id, $time = 0) {

        !class_exists('RWTimages') ? include ABSPATH . "analysis/include/rwt_images.php" : '';

        if (!$time) {
            $time = RWTimages::get_last_time($id);
        }
        return RWTimages::get_image_link('m_' . $id, $w . 'x' . $h, '', $time);

        /// return '/' . $this->get_thumb_path() . $w . 'x' . $h . '/' . $id . '.jpg';
    }

    public function get_thumb_path() {
        return 'wp-content/uploads/thumbs/poster/';
    }

    public function dinamic_poster($w, $h, $id) {
        // 1. Try to get poster by url
        $url = ABSPATH . $this->get_thumb_path() . $w . 'x' . $h . '/' . $id . '.jpg';
        $this->show_thumb($url);

        // 2. If not found create thumb
        !class_exists('CreateTsumbs') ? include ABSPATH . "analysis/include/create_tsumbs.php" : '';
        $array_tsumb = CreateTsumbs::get_poster_tsumb($id, array([$w, $h]), '', 'poster');
        if (isset($array_tsumb[0])) {
            $url = ABSPATH . preg_replace('|http[^/]+//[^/]+/|', '', $array_tsumb[0]);
            $this->show_thumb($url);
        }

        // 3. If source not found redirect to default thumb url
        $default_url = '/wp-content/themes/custom_twentysixteen/images/empty_image.svg';
        wp_redirect($default_url, 301);
    }

    private function show_thumb($url) {
        if (file_exists($url)) {
            $size = getimagesize($url);
            $fp = fopen($url, "rb");
            if ($size && $fp) {
                header("Content-type: {$size['mime']}");
                fpassthru($fp);
                exit;
            }
        }
        return false;
    }

    /*
     * Home scrolls
     */

    public function get_scroll($type = '', $movie_id = 0, $vote = 1, $search = false) {

        static $last_posts_id = '';
        static $last_movies_id = '';
        static $last_author_id = '';

        $content = '';

        if ($type == 'video_scroll' || $type == 'tv_scroll' || $type == 'games_scroll') {
            if (!$last_movies_id) {
                $ma = $this->get_ma();
                $last_movies_id = $ma->get_movies_last_update();
            }

            if ($type == 'video_scroll') {
                if ($this->cache_results) {
                    $filename = "scroll-vid-$last_movies_id";
                    $content = ThemeCache::cache('get_video_scroll', false, $filename, 'def', $this);
                } else {
                    $content = $this->get_video_scroll();
                }
            } else if ($type == 'tv_scroll') {
                if ($this->cache_results) {
                    $filename = "scroll-tv-$last_movies_id";
                    $content = ThemeCache::cache('get_tv_scroll', false, $filename, 'def', $this);
                } else {
                    $content = $this->get_tv_scroll($last_movies_id);
                }
            } else if ($type == 'games_scroll') {
                if ($this->cache_results) {
                    $filename = "scroll-games-$last_movies_id";
                    $content = ThemeCache::cache('get_games_scroll', false, $filename, 'def', $this);
                } else {
                    $content = $this->get_games_scroll($last_movies_id);
                }
            }
        }
        if ($type == 'review_scroll' || $type == 'stuff_scroll' || $type == 'audience_scroll') {
            if (!$last_posts_id) {
                $last_posts_id = $this->cm->get_posts_last_update();
            }

            if (!$last_author_id) {
                $last_author_id = $this->cm->get_author_last_update();
            }


            $arg = array(
                'movie_id' => $movie_id
            );

            if ($type == 'review_scroll') {
                if ($this->cache_results) {
                    $filename = "scroll-rev-$last_posts_id-$movie_id-$last_author_id";
                    $content = ThemeCache::cache('get_review_scroll', false, $filename, 'def', $this, $arg);
                } else {
                    $content = $this->get_review_scroll($arg);
                }
            } else if ($type == 'stuff_scroll') {
                if ($this->cache_results) {
                    $filename = "scroll-stf-$last_posts_id-$movie_id-$last_author_id";
                    $content = ThemeCache::cache('get_stuff_scroll', false, $filename, 'def', $this, $arg);
                } else {
                    $content = $this->get_stuff_scroll($arg);
                }
            } else if ($type == 'audience_scroll') {
                $arg['vote'] = $vote;
                if ($search) {
                    $arg['search'] = 1;
                }
                if ($this->cache_results) {
                    $filename = "scroll-aud-$last_posts_id-$vote-$movie_id-$last_author_id";
                    $content = ThemeCache::cache('get_audience_scroll', false, $filename, 'def', $this, $arg);
                } else {
                    $content = $this->get_audience_scroll($arg);
                }
            }
        }

        return $content;
    }

    public function append_watch_list_scroll_data($data = '') {
        // Try to get movies ids

        try {
            $mids = [];
            if (is_string($data)) {
                $json_data = json_decode($data);
                $mids = $json_data->mids;
            } else
                $mids = $data['mids'];

            if ($mids) {
                arsort($mids);

                // Get watchlists                
                $user = $this->cm->get_current_user();
                if ($user->ID) {
                    $wl = $this->cm->get_wl();
                    $in_list = $wl->in_def_lists($user->ID, $mids);
                    if ($in_list) {

                        if (is_string($data)) {
                            $json_data->watchlist = $in_list;
                            $data = json_encode($json_data);
                        } else {
                            $data['watchlist'] = $in_list;
                        }
                    }
                }
            }
        } catch (Exception $exc) {
            
        }
        return $data;
    }

    public function get_video_scroll() {
        ob_start();
        require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/ajax/video_scroll.php');
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function get_tv_scroll() {
        ob_start();

        !class_exists('TV_Scroll') ? require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/ajax/tv_scroll.php') : '';
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function get_games_scroll() {
        ob_start();
        require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/ajax/games_scroll.php');

        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function get_review_scroll($arg = array()) {
        $movie_id = $arg['movie_id'] ? $arg['movie_id'] : 0;
        $content = $this->get_review_scroll_data($movie_id);
        return $content;
    }

    public function get_stuff_scroll($arg = array()) {
        $movie_id = $arg['movie_id'] ? $arg['movie_id'] : 0;
        $content = $this->get_stuff_scroll_data($movie_id);
        return $content;
    }

    public function get_audience_scroll($arg = array()) {
        $vote = $arg['vote'] ? $arg['vote'] : 0;
        $movie_id = $arg['movie_id'] ? $arg['movie_id'] : 0;
        $search = $arg['search'] ? true : false;
        $content = $this->get_audience_scroll_data($movie_id, $vote, $search);
        return $content;
    }

    public function get_review_scroll_data($movie_id = 0, $tags = array(), $posts = null, $link = '') {
        $a_type = 1;
        $limit = 10;
        $start = 0;

        global $site_url;
        if (!$site_url)
            $site_url = WP_SITEURL . '/';

        // Get settings
        $ss = $this->cm->get_settings();
        $min_rating = $ss['posts_rating'];
        $meta_type = array();
        if ($ss['posts_type_1']) {
            $meta_type[] = 1;
        }
        if ($ss['posts_type_2']) {
            $meta_type[] = 2;
        }
        if ($ss['posts_type_3']) {
            $meta_type[] = 3;
        }

        $ss = $this->cm->get_settings();
        $unique = $ss['critics_unique'];

        if (!$posts) {
            $posts = $this->theme_last_posts($a_type, $limit, $movie_id, $start, $tags, $meta_type, $min_rating, 0, true, 0, 0, $unique);
        }

        $count = $this->get_post_count($a_type, $movie_id);
        $content = array();
        $array_movies = [];
        if (sizeof($posts)) {
            //Get $video_template
            if ($movie_id) {
                require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/video_item_template_single.php');
            } else {
                require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/video_item_template.php');
            }
            //Reactions
            $this->enable_reactions = false;
            $pids = array();
            $orders = 0;
            foreach ($posts as $post) {
                $post['order'] = $orders;
                $content['result'][$post['date'] . '_' . $post['pid']] = $post;
                $pids[] = $post['pid'];
                $array_movies[$post['m_id']] = 1;
                $orders++;
            }

            // Link more

            if ($count > $limit) {

                if (!$link) {
                    $link = '/search/tab_critics/author_critic/state_proper_contains';

                    if ($movie_id) {
                        $link .= '/movie_' . $movie_id;
                    }
                }


                $title = 'Load more<br>Critic Reviews';
                $content['result']['0_0'] = array('link' => $link, 'title' => $title, 'genre' => 'load_more', 'poster_link_small' => '', 'poster_link_big' => '', 'content_pro' => '');
            }


            if (!class_exists('RWT_RATING')) {
                require ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php";
            }

            $RWT_RATING = new RWT_RATING();
            $rating = $RWT_RATING->get_rating_data($array_movies, 0);

            $content['rating'] = $rating;

            $content['count'] = count($content['result']);
            $content['tmpl'] = $video_template;
            $ce = $this->cm->get_ce();
            $content['reaction'] = $ce->get_emotions_counts_all($pids);

            // Print json
            //    return json_encode($content);
        }
        $content['mid'] = $movie_id;
        $content['mids'] = array_keys($array_movies);
        ///google cse
        !class_exists('Gsearch') ? include ABSPATH . "analysis/include/gsearch.php" : '';
        $gserch = new Gsearch();
        $content['gdata'] = $gserch->get_data($movie_id, 1);

        return json_encode($content);
        return '';
    }

    public function get_movie_title($movie_id) {
        $q = "select title from data_movie_imdb where id =" . $movie_id;
        $r = Pdo_an::db_fetch_row($q);
        return $r->title;
    }

    public function get_stuff_scroll_data($movie_id = 0) {
        global $site_url;
        if (!$site_url) {
            $site_url = WP_SITEURL . '/';
        }


        $a_type = 0;
        $limit = 10;
        $posts = $this->theme_last_posts($a_type, $limit, $movie_id);
        $count = $this->get_post_count($a_type, $movie_id);
        $content = array();

        if (sizeof($posts)) {
            //Get $video_template
            if ($movie_id) {
                require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/video_item_template_single.php');
            } else {
                require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/video_item_template.php');
            }

            //Reactions
            $this->enable_reactions = false;
            $pids = array();
            $array_movies = [];
            foreach ($posts as $post) {
                $content['result'][$post['date'] . '_' . $post['pid']] = $post;
                $pids[] = $post['pid'];
                $array_movies[$post['m_id']] = 1;
            }

            // Link more
            if ($count > $limit) {

                // Old api
                // $link = '/critics/group_staff';
                // New api
                $link = '/search/tab_critics/author_staff';

                if ($movie_id) {
                    // Old api
                    /* $ma = $this->get_ma();
                      $ma_id = $movie_id; //$ma->get_post_id_by_rwt_id($movie_id);
                      $movie = $ma->get_post($ma_id);
                      if ($movie) {
                      $slug = $this->get_or_create_ma_post_name($ma_id, $movie->rwt_id, $movie->title, $movie->type);
                      $type_slug = $ma->get_post_slug($movie->type);
                      $link = $site_url . 'critics/group_staff/' . $type_slug . '/' . $slug;
                      } */
                    // New api
                    $link = '/search/tab_critics/author_staff/movie_' . $movie_id;
                }

                $title = 'Load more<br>Staff Reviews';
                $content['result']['0_0'] = array('link' => $link, 'title' => $title, 'genre' => 'load_more', 'poster_link_small' => '', 'poster_link_big' => '', 'content_pro' => '');
            }
            if (!class_exists('RWT_RATING')) {
                require ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php";
            }
            $RWT_RATING = new RWT_RATING();
            $rating = $RWT_RATING->get_rating_data($array_movies, 0);

            $content['rating'] = $rating;
            $content['count'] = count($content['result']);
            $content['tmpl'] = $video_template;
            $ce = $this->cm->get_ce();
            $content['reaction'] = $ce->get_emotions_counts_all($pids);

            // Print json
            return json_encode($content);
        }
        return '';
    }

    public function get_audience_scroll_data($movie_id = 0, $vote_type = 1, $search = false) {
        global $site_url;
        if (!$site_url)
            $site_url = WP_SITEURL . '/';

        /* Author type
          0 => 'Staff',
          1 => 'Critic',
          2 => 'Audience'
         */

        $a_type = 2;
        $limit = 10;

        $ss = $this->cm->get_settings();
        $unique = $ss['audience_unique'];

        if ($vote_type == 1 || $vote_type == 2) {
            // $vote_type = 1; Positive
            // $vote_type = 2; Negative
            $unique = $ss['audience_top_unique'];
        }

        // Vote to rating  
        $min_au = 0;
        $max_au = 0;
        $vote = 0;
        /* if ($vote_type == 1) {
          // UNUSED

          // pay
          $min_au = 3;
          $vote = 0;
          $unique = 1;
          } else if ($vote_type == 2) {

          // skip
          $max_au = 2;
          $vote = 0;
          $unique = 1;
          } */

        $min_rating = 0;
        $array_movies = array();

        $posts = $this->theme_last_posts($a_type, $limit, $movie_id, 0, 0, array(), $min_rating, $vote, $search, $min_au, $max_au, $unique, $vote_type);
        $count = $this->get_post_count($a_type, $movie_id, 0, $vote, $min_rating, $min_au, $max_au, $vote_type);
        //print_r($vote);
        $content = array();

        if (sizeof($posts)) {
            //Get $video_template
            if ($movie_id) {
                require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/video_item_template_single.php');
            } else {
                require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/video_item_template.php');
            }

            //Reactions
            $this->enable_reactions = false;
            $pids = array();

            foreach ($posts as $post) {
                $content['result'][$post['date'] . '_' . $post['pid']] = $post;
                $pids[] = $post['pid'];
                $array_movies[$post['m_id']] = 1;
            }
            global $site_url;
            // Link more
            if ($count > $limit) {
                // Old api
                // $link = '/critics/group_audience';
                // New api
                $link = '/search/tab_critics/author_audience';

                if ($movie_id) {
                    // New api
                    $link = '/search/tab_critics/author_audience/movie_' . $movie_id;
                }

                if ($vote_type == 1) {
                    $link .= '/auvote_pay';
                } else if ($vote_type == 2) {
                    $link .= '/auvote_skip';
                }

                $title = 'Load more<br>Audience Reviews';
                $content['result']['0_0'] = array('link' => $link, 'title' => $title, 'genre' => 'load_more', 'poster_link_small' => '', 'poster_link_big' => '', 'content_pro' => '');
            }

            if (!class_exists('RWT_RATING')) {
                require ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php";
            }
            $RWT_RATING = new RWT_RATING();
            $rating = $RWT_RATING->get_rating_data($array_movies, 0);

            $content['rating'] = $rating;
            $content['count'] = count($content['result']);
            $content['tmpl'] = $video_template;
            $ce = $this->cm->get_ce();
            $content['reaction'] = $ce->get_emotions_counts_all($pids);
            $content['mids'] = array_keys($array_movies);

            // Print json
            return json_encode($content);
        }
        return '';
    }

    public function search_last_critics($mid = 0, $count = 10) {

        $keyword = '';
        $limit = $count;
        $start = 0;
        $sort = array(
            'sort' => 'date',
            'type' => 'desc'
        );
        $filters = array(
            'movie' => $mid,
                /// 'author'=> 2
        );

        $facets = false;
        $show_meta = false;
        $widlcard = false;
        $fields = array(
            'title',
            'content',
            'author_name',
            'aurating',
        );

        $critic_data = $this->cs->front_search_critics_multi($keyword, $limit, $start, $sort, $filters, $facets, $show_meta, $widlcard, $fields);
        $results = array();
        if ($critic_data) {
            foreach ($critic_data as $item) {
                $id = $item->id;
                $content = $this->format_content($item->content, 400);
                $post = new stdClass();
                $post->author_type = $item->author_type;
                $post->author_name = trim(strip_tags($item->author_name));
                $post->title = trim(strip_tags($item->title));
                // $url = $this->get_critic_url($post);

                $results[$id]['title'] = $post->title;
                $results[$id]['content'] = $content;
                // $results[$id]['url'] = $url;
                $results[$id]['author_name'] = $post->author_name;
                // $results[$id]['author_type'] = $post->author_type;
                $results[$id]['rating'] = $item->aurating;
                // $results[$id]['date'] = $item->date_add;
            }
        }
        return $results;
    }

    /*
     * User widgets
     */

    public function get_posts_widget_by_wpuid($wp_uid = 0, $perpage = 10) {
        $posts = $this->get_last_posts_by_wpuid($wp_uid);
        $content = '';
        if ($posts) {
            /*
             * Array ( [0] => stdClass Object ( [id] => 144712 [date] => 1667222826 [date_add] => 1667238777 [status] => 1 [type] => 2 [link_hash] => [link] => [title] => 11223dsdd 3333 [content] => ddfdf 3333 [top_movie] => 69796 [blur] => 0 [view_type] => 0 [aid] => 1023 ) )
             */
            ob_start();
            ?>
            <div class="simple list-group list-group-flush items">
                <?php
                foreach ($posts as $post) {

                    $critic = $this->cm->get_post_and_author($post->id);

                    $permalink = $critic->link;
                    if (!$permalink) {
                        // Create local permalink
                        $permalink = $this->get_critic_url($critic);
                    }
                    $title = $critic->title;
                    $top_movie = $critic->top_movie;

                    if ($top_movie) {
                        $meta_state = $this->cm->get_critic_meta_state($critic->id, $top_movie);
                        $info_link = $this->get_info_link($critic->id, $top_movie, $meta_state->state);
                        $meta_type = $this->cm->get_post_category_name($meta_state->type);
                    }


                    // Link to full post
                    $link = $this->get_critic_url($critic);

                    // Time
                    $ptime = $critic->date;
                    $critic_addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

                    // Title
                    $title_str = '';
                    $title = strip_tags($title);
                    $title = $this->pccf_filter($title);

                    // Movie
                    $ma = $this->get_ma();
                    if ($top_movie) {
                        $movie = $ma->get_post($top_movie);

                        // Title
                        $mtitle = $movie->title;

                        // release
                        $release = $movie->release;
                        if ($release) {
                            $release = strtotime($release);
                            $release = date('Y', $release);
                            if (strstr($mtitle, $release)) {
                                $release = '';
                            } else {
                                $release = ' (' . $release . ')';
                            }
                        }

                        $poster_link_90 = $this->get_thumb_path_full(90, 120, $top_movie);
                    }
                    ?>
                    <div class="item d-flex justify-content-between list-group-item list-group-item-nopadding">
                        <a href="<?php print $link ?>" title="<?php print $title ?>" class="d-flex list-group-item list-group-item-action list-group-item-noborder" > 

                            <img class="d-flex me-3" srcset="<?php print $poster_link_90 ?>" alt="<?php print $mtitle ?>">

                            <div class="desc">
                                <h5><?php print $mtitle . $release ?></h5>
                                <p><?php print $title ?></p>
                            </div>

                        </a>                           
                    </div>                    
                <?php } ?>
            </div>

            <?php
            $content = ob_get_contents();
            ob_end_clean();
        }
        return $content;
    }

    public function get_posts_page_by_wpuid($wp_uid = 0, $owner = 0, $perpage = 10, $page = 1) {
        $posts = $this->get_last_posts_by_wpuid($wp_uid, $perpage, $page);
        $content = '';
        if ($posts) {
            /*
             * Array ( [0] => stdClass Object ( [id] => 144712 [date] => 1667222826 [date_add] => 1667238777 [status] => 1 [type] => 2 [link_hash] => [link] => [title] => 11223dsdd 3333 [content] => ddfdf 3333 [top_movie] => 69796 [blur] => 0 [view_type] => 0 [aid] => 1023 ) )
             */
            ob_start();
            ?>
            <div class="simple list-group list-group-flush items<?php
            if ($owner) {
                print " owner";
            }
            ?>" data-id="0">         
                     <?php
                     foreach ($posts as $post) {

                         $critic = $this->cm->get_post_and_author($post->id);

                         $permalink = $critic->link;
                         if (!$permalink) {
                             // Create local permalink
                             $permalink = $this->get_critic_url($critic);
                         }
                         $title = $critic->title;
                         $top_movie = $critic->top_movie;

                         if ($top_movie) {
                             $meta_state = $this->cm->get_critic_meta_state($critic->id, $top_movie);
                             $info_link = $this->get_info_link($critic->id, $top_movie, $meta_state->state);
                             $meta_type = $this->cm->get_post_category_name($meta_state->type);
                         }

                         // Link to full post
                         $link = $this->get_critic_url($critic);

                         // Time
                         $ptime = $critic->date;
                         $critic_addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

                         // Title
                         $title_str = '';
                         $title = strip_tags($title);
                         $title = $this->pccf_filter($title);

                         // Movie
                         $ma = $this->get_ma();
                         if ($top_movie) {
                             $movie = $ma->get_post($top_movie);

                             // Title
                             $mtitle = $movie->title;

                             // release
                             $release = $movie->release;
                             if ($release) {
                                 $release = strtotime($release);
                                 $release = date('Y', $release);
                                 if (strstr($mtitle, $release)) {
                                     $release = '';
                                 } else {
                                     $release = ' (' . $release . ')';
                                 }
                             }

                             $poster_link_90 = $this->get_thumb_path_full(90, 120, $top_movie);
                         }
                         ?>
                    <div class="item d-flex justify-content-between list-group-item list-group-item-nopadding" data-id="<?php print $critic->id ?>">
                        <a href="<?php print $link ?>" title="<?php print $title ?>" class="d-flex list-group-item list-group-item-action list-group-item-noborder" > 

                            <img class="d-flex me-3" srcset="<?php print $poster_link_90 ?>" alt="<?php print $mtitle ?>">

                            <div class="desc">
                                <h5><?php print $mtitle . $release ?></h5>
                                <p><?php print $title ?></p>
                            </div>

                        </a>
                        <?php if ($owner): ?>                                            
                            <div class="ellipsis-menu dropdown cnt-reviews">
                                <span class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="icon icon-ellipsis-vert" ></i></span>
                                <ul class="dropdown-menu list-menu">    
                                    <li class="nav-tab" data-act="editrev" data-id="<?php print $top_movie ?>" data-cid="<?php print $critic->id ?>">Edit Review</li>
                                    <li class="nav-tab" data-act="delrev" data-id="<?php print $top_movie ?>" data-cid="<?php print $critic->id ?>">Delete Review</li>
                                </ul>
                            </div>                            
                        <?php endif; ?>
                    </div>
                <?php } ?>
            </div>

            <?php
            $content = ob_get_contents();
            ob_end_clean();
        }
        return $content;
    }

    public function get_last_posts_by_wpuid($wp_uid = 0, $perpage = 10, $page = 1) {
        $author = $this->cm->get_author_by_wp_uid($wp_uid, true);
        $posts = array();
        if ($author) {
            $order = 'DESC';
            $orderby = '';
            $q_req = array(
                'status' => 1,
                'aid' => $author->id,
            );
            $posts = $this->cm->get_posts($q_req, $page, $perpage, $orderby, $order);
        }
        return $posts;
    }

    public function get_reviews_count_by_wpuser($wp_uid = 0) {
        $author = $this->cm->get_author_by_wp_uid($wp_uid, true);
        $count = 0;
        if ($author) {
            $q_req = array(
                'status' => 1,
                'aid' => $author->id,
            );
            $count = $this->cm->get_posts($q_req, 1, 0, '', 'ASC', true);
        }

        return $count;
    }

    public function get_movie_data($title = '', $year = 0, $debug = false) {
        $mid = $this->cs->get_zr_movie_id($title, $year, $debug);

        $data = array();
        if ($mid) {
            $ma = $this->get_ma();
            $post = $ma->get_post($mid);

            if ($post) {
                $keywords = $this->get_nf_keywords($post, $debug);
                // Weight logic
                $weight = $this->get_min_weight($post);
                $data = array(
                    'mid' => $mid,
                    'keywords' => $keywords,
                    'weight' => $weight
                );
            }
        }
        return $data;
    }

    public function get_nf_keywords($post, $debug = false) {
        /*
         * stdClass Object
          (
          [id] => 72749
          [movie_id] => 12530246
          [rwt_id] => 0
          [tmdb_id] => 715931
          [title] => Emancipation
          [post_name] => emancipation
          [type] => Movie
          [genre] => Action,Thriller
          [release] => 2022-12-09
          [year] => 2022
          [country] => United States
          [language] => English
          [production] => {"co0546168":"Apple TV+","co0719257":"CAA Media Finance","co0035535":"Escape Artists"}
          [actors] =>
          [producers] =>
          [director] =>
          [cast_director] =>
          [writer] =>
          [box_usa] =>
          [box_world] =>
          [productionBudget] => 120000000
          [keywords] => psychological thriller,killed,freedom
          [description] => A runaway slave forges through the swamps of Louisiana on a tortuous journey to escape plantation owners that nearly killed him.
          [data] => {"imdb_title":"Emancipation (2022)","image":"https:\/\/m.media-amazon.com\/images\/M\/MV5BN2RiY2RmMjItMDc1My00ZmViLWJkM2YtZjExNDI5MGM2ZWNiXkEyXkFqcGdeQXVyODk4OTc3MTY@._V1_.jpg","year":2022,"creator":{"Organization":"546168,719257,35535,","Person":"171651,"}}
          [contentrating] => R
          [rating] => 5.4
          [add_time] => 1670774410
          [runtime] => 7920
          [weight] => 0
          [weight_upd] => 0
          )
         */

        $keywords = '';
        $filter_title = $this->filter_text($post->title);

        if (strstr($filter_title, ' ')) {
            $title_arr = explode(" ", $filter_title);
            $filter_title = "=" . implode(" =", $title_arr);
        } else {
            $filter_title = "=" . $filter_title;
        }
        $title = '"' . $filter_title . '"';

        //$title = $this->filter_text($post->title);

        $keywords = $title;

        $filelds = array('review');
        // Year
        $year = (int) $post->year;
        if ($year) {
            $filelds[$year] = $year;
        }

        $ma = $this->get_ma();

        // Search Director
        $directors = $ma->get_directors($post->id);
        if ($directors) {
            $max_directors = 3;
            foreach ($directors as $director) {
                $name = $director->name;
                $i = 0;
                if ($name) {
                    if ($i > $max_directors) {
                        break;
                    }
                    $filelds[$name] = '"' . $this->filter_text($name) . '"';
                    $i += 1;
                }
            }
        }


        // Actors
        $actors = $ma->get_actors($post->id);

        if ($actors) {
            $max_actors = 3;
            foreach ($actors as $actor) {
                $name = isset($actor->name) ? $actor->name : '';
                $i = 0;
                if ($name) {
                    if ($i > $max_actors) {
                        break;
                    }
                    $filelds[$name] = '"' . $this->filter_text($name) . '"';
                    $i += 1;
                }
            }
        }


        $production = array();
        if ($post->production) {
            $p_obj = json_decode($post->production);
            if ($p_obj) {
                $i = 0;
                $max_prod = 3;
                foreach ($p_obj as $p) {
                    if ($i > $max_prod) {
                        break;
                    }
                    $filelds[$p] = '"' . $this->filter_text($p) . '"';
                    $i += 1;
                }
            }
        }

        if ($filelds) {
            $keywords .= ' MAYBE (' . implode('|', $filelds) . ')';
        }

        return $keywords;
    }

    public function filter_text($text = '') {
        $text = strip_tags($text);
        $text = preg_replace('/[^a-zA-Z0-9\']+/', ' ', $text);
        $text = trim(preg_replace('/  /', ' ', $text));
        return $text;
    }

    private function get_min_weight($post) {
        $min_title_weight = 20;
        $min_weight = 1000;
        $title_weight = $post->title_weight;
        if ($title_weight < $min_title_weight) {
            $min_weight = 3000;
        }
        return $min_weight;
    }

    public function related_newsfilter_movies($movie_id, $debug = false) {
        $ma = $this->get_ma();
        $movie_data = $ma->get_post($movie_id);

        $view_rows = 5;
        $results = '';
        if ($movie_data) {
            $search_text = '"' . $movie_data->title . '"';
            $results = $this->cs->find_in_newsfilter($movie_data, $view_rows, $debug);
        }

        $ns_link = "https://curatedinfo.org/search/" . urlencode($search_text);

        /*
         *                         (
          [id] => 297208
          [cid] => 1375
          [pdate] => 1669692781
          [date] => 1669687263
          [link] => /evening-news/
          [site] => https://www.cbsnews.com/
          [type] => 0
          [bias] => 4
          [biastag] => 0
          [description] =>
          [t] => CBS Evening News - Full episodes, interviews, breaking news, videos and online stream - CBS News
          [c] =>  ...  risk" of lava flows to review their preparation measures. Houston closes ...  him in his newest film, "Emancipation," so soon after the infamous ...
          [w] => 1602
         */

        if ($results['list']) {
            $total_count = $results['count'];
            ?>
            <div class="ns_related">
                <div class="column_header">
                    <h2>Curatedinfo.org</h2>
                    <h3><a href="<?php print $ns_link ?>">"<?php print $movie_data->title ?>"</a></h3>
                </div>                        
                <?php
                // Bias facet
                $this->show_bias_facet($results['facets']);

                foreach ($results['list'] as $item) {
                    $theme_url = $this->theme_item_url($item);

                    $cats_arr = array('bias', 'biastag');

                    $cats_arr = array(
                        'bias' => array('title' => 0),
                        'biastag' => array('title' => 0, 'show_tags' => 1),
                    );

                    $tags = $this->theme_search_tags($item, $cats_arr, $ns_link);
                    ?>
                    <div class="ns_item">
                        <h3 class="tile"><?php print $item->t ?></h3>
                        <div class="url">
                            <?php print $theme_url ?>
                        </div>  
                        <p class="content"><?php print $item->c ?></p>  
                        <div class="meta">
                            <span class="p-date block">
                                <time><?php
                                    print date('d.m.Y H:i', $item->date);
                                    ?></time>
                            </span>

                            <span class="p-cat block">
                                <?php
                                if ($tags) {
                                    print implode(' ', $tags);
                                }
                                ?>
                            </span>

                            <?php if ($item->nresult) { ?>
                                <span class="p-rating block">
                                    Rating: <span class="rt_color-<?php print $item->nresult ?>"><?php print $item->nresult ?></span>/5
                                </span>
                            <?php } ?>
                        </div>
                    </div><?php
                }
                ?>
                <?php if ($total_count > $view_rows) { ?>
                    <h3 class="ns_all"><a href="<?php print $ns_link ?>">Show all related posts: <?php print $total_count ?></a></h3>
                    <?php
                }
                ?>

            </div>
            <?php
        }
    }

    public function show_bias_facet($facet_data = array()) {

        // Rating data
        $total_rating = array();
        if ($facet_data['biasrating']['data']) {
            foreach ($facet_data['biasrating']['data'] as $item) {
                $total_rating[$item->id] = (int) round($item->nresults / $item->cnt, 0);
            }
        }

        //Get types
        $dates = array();

        // Facet titles
        $data = isset($facet_data['bias']['data']) ? $facet_data['bias']['data'] : array();

        $facet_titles = array(
            2 => 'Far left',
            3 => 'Left',
            4 => 'Center Left',
            5 => 'Center',
            6 => 'Center Right',
            7 => 'Right',
            8 => 'Far right',
        );

        if ($data) {
            $ids = array();
            foreach ($data as $value) {
                $id = trim($value->id);
                $cnt = $value->cnt;
                $ids[$id]['cnt'] = $cnt;
                $ids[$id]['rating'] = isset($total_rating[$id]) ? $total_rating[$id] : 0;
            }


            if ($ids[1]) {
                $ids[2] = $this->merge_ids($ids[1], $ids[2]);
            }

            if ($ids[9]) {
                $ids[8] = $this->merge_ids($ids[8], $ids[9]);
            }

            foreach ($facet_titles as $key => $value) {

                $item_title = $value;
                $rating_title = isset($ids[$key]) ? $ids[$key]['rating'] : 0;
                $cnt = isset($ids[$key]) ? $ids[$key]['cnt'] : 0;
                $dates[$key] = array('title' => $item_title, 'rating' => $rating_title, 'count' => $cnt);
            }
        }

        $this->theme_bias_facet($dates);
    }

    private function merge_ids($first, $second) {
        $ret = array('cnt' => 0, 'rating' => 0);
        $cnt_1 = isset($first['cnt']) ? $first['cnt'] : 0;
        $cnt_2 = isset($second['cnt']) ? $second['cnt'] : 0;
        $ret['cnt'] = $cnt_1 + $cnt_2;

        $r_1 = isset($first['rating']) ? $first['rating'] : 0;
        $r_2 = isset($second['rating']) ? $second['rating'] : 0;
        if ($r_1 && $r_2) {
            $ret['rating'] = round(($r_1 + $r_2) / 2, 1);
        } else if ($r_1) {
            $ret['rating'] = $r_1;
        } else if ($r_2) {
            $ret['rating'] = $r_2;
        }
        return $ret;
    }

    public function theme_bias_facet($dates) {
        if (!$dates) {
            return false;
        }
        ?>
        <div class="bias_info rspv-table">
            <?php
            $rows = array(
            );
            foreach ($dates as $key => $value) {
                $rating = $value['rating'];
                $rating_text = $rating > 0 ? $rating : 'None';

                $rating_after = '';
                if ($rating > 0) {
                    $rating_after = '/5';
                }

                $rows['title'][] = '<span class="title">' . $value['title'] . '</span> <span class="cnt">(' . $value['count'] . ')</span>';
                $rows['rating'][] = '<span class="rating"><span class="rt_color-' . $rating . '">' . $rating_text . '</span>' . $rating_after . '</span>';
            }
            ?>
            <?php
            $ir = 1;
            foreach ($rows as $row) {
                $ic = 1;
                ?>
                <div class="rspv-row row-<?php print $ir ?>">
                    <?php foreach ($row as $clmn) { ?>
                        <div class="rspv-clm clm-<?php print $ic ?>"><?php print $clmn ?></div>
                        <?php
                        $ic++;
                    }
                    ?>
                </div>                    
                <?php
                $ir++;
            }
            ?>
        </div>
        <?php
    }

    public function theme_search_tags($item, $cats_arr, $link = '') {
        $tags = array();
        $facet_data = array(
            'bias' => array(
                'title' => 'Bias rating',
                'facet_titles' => array(
                    0 => 'Not rated',
                    1 => 'Extreme left',
                    2 => 'Far left',
                    3 => 'Left',
                    4 => 'Left-center',
                    5 => 'Least biased',
                    6 => 'Right-center',
                    7 => 'Right',
                    8 => 'Far right',
                    9 => 'Extreme right'
                )
            ),
            'biastag' => array(
                'title' => 'Bias tags',
                'facet_titles' => array(
                    1 => 'Conpiracy-pseudoscience',
                    2 => 'Pro-science',
                    3 => 'Satire',
                ),
            ),);

        foreach ($cats_arr as $tag => $tag_data) {
            if ($item->$tag >= 0) {
                $tag_tile = '';
                if ($tag_data['title']) {
                    $tag_tile = $facet_data[$tag]['title'];
                    $tag_tile .= ': ';
                }

                $title = $item->$tag;
                if (isset($facet_data[$tag]['facet_titles'][$item->$tag])) {
                    $title = $facet_data[$tag]['facet_titles'][$item->$tag];
                } else {
                    if ($tag_data['show_tags']) {
                        $title = null;
                    }
                }

                if (isset($title)) {
                    $theme_tag = '<a href="' . $link . '/' . $tag . '_' . $item->$tag . '" rel="category tag">#' . $title . '</a>';
                    $tags[] = $theme_tag;
                }
            }
        }
        return $tags;
    }

    public function theme_item_url($item) {
        $url = $item->link;
        if ($item->type == 0) {
            $url = $item->site . substr($item->link, 1);
        }
        $text_url = $url;
        $domain = $url;
        if (preg_match('#(http[s]*://)([^/]+)/#', $url, $match)) {
            $text_url = str_replace($match[2], '<b>' . $match[2] . '</b>', $text_url);
            $domain = $match[1] . $match[2];
        }
        $icon = 'https://www.google.com/s2/favicons?domain=' . $domain;
        $theme_url = '<img srcset="' . $icon . '" width="16" height="16"> <a target="_blank" href="' . $url . '">' . $text_url . '</a>';
        return $theme_url;
    }

    /*
     * User avatars
     */

    public function change_user_avatar($wp_id = 0, $user_rating = 0, $settings_page = 0) {
        $ss = $this->cm->get_settings();
        $score_avatar = $ss['score_avatar'];
        //$cav = $this->cm->get_cav();

        if ($user_rating >= $score_avatar) {
            // Enable to upload avatar
            // Check avatar type
            $author = $this->cm->get_author_by_wp_uid($wp_id);
            $with_avfile = '';
            if ($author->avatar_name) {
                $with_avfile = ' avfile';
            }
            if ($author->id) {
                ?>
                <div id="author_id" data-id="<?php print $author->id ?>"></div>
                <div class="av_upload<?php print $with_avfile ?>">                
                    <button class="btn btn-primary" id="upl_avatar" title="Upload avatar"><i class="icon-upload"></i></button>
                    <?php if ($settings_page) { ?>
                        <button id="trash_avatar" class="btn btn-secondary" title="Remove avatar"><i class="icon-trash"></i></button>
                    <?php } ?>
                    <input type="file" accept=".png, .jpg, .jpeg, .gif" id="avatar_file">
                </div>
                <?php
            }
        }
    }

    public function upload_new_user_avatar() {
        $ss = $this->cm->get_settings();
        $score_avatar = $ss['score_avatar'];
        //$cav = $this->cm->get_cav();

        if ($score_avatar == 0) {
            // Enable to upload avatar                
            ?>
            <br /> 
            <div id="author_id" data-id="0"></div>
            <div class="av_upload">                
                <button id="upl_avatar" title="Upload avatar" class="button"><i class="icon-user-circle-o"></i> Upload avatar</button>
                <input type="file" accept=".png, .jpg, .jpeg, .gif" id="avatar_file">
            </div>
            <?php
        }
    }

    public function select_user_avatar($wp_id = 0, $user_rating = 0) {
        // DEPRECATED UNUSED
        $ss = $this->cm->get_settings();
        $score_avatar = $ss['score_avatar'];
        $cav = $this->cm->get_cav();

        if ($user_rating >= $score_avatar) {
            // Enable to upload avatar
            // Check avatar type
            $author = $this->cm->get_author_by_wp_uid($wp_id, true);
            ?>
            <div id="author_id" data-id="<?php print $author->id ?>"></div>
            <fieldset id="select_av_type">
                <legend>Avatar type:</legend>
                <?php
                foreach ($this->cm->author_av_types as $key => $value) {
                    $checked = $key == $author->avatar_type ? 'checked' : '';
                    ?>
                    <div>
                        <input type="radio" id="<?php print $key ?>" name="avtype" value="<?php print $key ?>" <?php print $checked ?>>
                        <label for="<?php print $key ?>"><?php print $value ?></label>
                    </div>
                    <?php
                }
                ?>              
            </fieldset>
            <div class="av_actions">
                <?php
                foreach ($this->cm->author_av_types as $key => $value) {
                    $checked = $key == $author->avatar_type ? ' active' : '';
                    ?>
                    <div id="av_action_<?php print $key ?>" class="av_action<?php print $checked ?>" >
                        <?php
                        if ($key == 0) {
                            $this->user_random_avatar();
                        } else {
                            $this->user_upload_avatar();
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>                
            </div>
            <?php
        } else {
            $this->user_random_avatar();
        }
    }

    private function user_upload_avatar() {
        // DEPRECATED UNUSED
        ?>

        <div>    
            <button id="upl_avatar" class="btn-small">Upload avatar</button><br />
            <input type="file" id="avatar_file">            
        </div>
        <?php
    }

    private function user_random_avatar() {
        ?>
        <button id="change_avatar" class="btn-small" title="Get another random avatar">Change avatar</button><span data-value="change_user_avatar" class="nte_info"></span>
        <?php
    }

    /*
     * External fucntions 
     */

    public function cache_img($url) {
        $cache_site = 'https://img.rightwingtomatoes.com/';
        if (!strstr($url, $cache_site)) {
            $url = $cache_site . $url;
        }
        return $url;
    }

    public function rating_images($type, $rating, $subrating = 0) {
        $ca = $this->get_ca();
        return $ca->rating_images($type, $rating, $subrating);
    }

    public function get_user_reactions($cid, $post_type = 0, $allow_cmt = true) {
        if ($this->enable_reactions) {
            $ce = $this->cm->get_ce();
            $reaction_data = $ce->get_user_reactions($cid, $post_type, $allow_cmt);
        } else {
            $reaction_data = '<div class="review_comment_data" data-ptype="' . $post_type . '"></div>';
        }
        return $reaction_data;
    }

    public function init_thumb_service() {
        if (!$this->thumb_class) {

            if (!class_exists('GETTSUMB')) {
                include (ABSPATH . 'wp-content/themes/custom_twentysixteen/template/include/create_tsumb.php');
            }
            $this->thumb_class = new GETTSUMB();
        }
    }

    public function get_local_thumb($w = 0, $h = 0, $path = '', $name = '') {
        // DEPRECATED
        $this->init_thumb_service();
        $image = CreateTsumbs::getThumbLocal_custom($w, $h, $path, $name);
        return $image;
    }

    public function screenshot($url, $resolution = array(800, 460)) {

        return '/wp-content/uploads/2021/12/RWT_rightwingtomatoes_filter_the_woke.gif';

        //        $and = '?';
        //        if (strstr($url, '?')) {
        //            $and = '&';
        //        }
        //        $url = $url . $and . 'to_image';
        //        $post = md5($url);
        //        if (file_exists(ABSPATH . 'wp-content/uploads/screencap/' . $post . '.png')) {
        //            return '/wp-content/uploads/screencap/' . $post . '.png';
        //        }
        //
        //        $request = 'xvfb-run --server-args="-screen 0, ' . $resolution[0] . 'x' . $resolution[1] . 'x16" cutycapt --url=' . $url . ' --out=wp-content/uploads/screencap/' . $post . '.png';
        //        //echo $request;
        //        system($request);
        //        return '/wp-content/uploads/screencap/' . $post . '.png';
    }

    public function get_movie_tags_facet($mid = 0, $limit = 1000, $debug = false) {

        $movie = $this->cs->get_movie_by_id($mid);
        if ($debug) {
            print_r($movie);
        }

        $result = array();

        $mkw = $movie->mkw;

        if (!$mkw) {
            return $result;
        }

        $mkw_arr = explode(',', $mkw);

        $filter = 'mkw';

        $facets = array($filter);
        $filters = [];

        $last_limit = $this->cs->facet_limit;
        $last_max_limit = $this->cs->facet_max_limit;
        $this->cs->facet_limit = 10000;
        $this->cs->facet_max_limit = 10000;

        $this->cs->filter_custom_and[$filter] = "ANY(mkw) IN(" . implode(',', $mkw_arr) . ")";
        $result = $this->cs->front_search_movies_multi($this->keywords, $facets, 0, array(), $filters, $facets, true, true, false);
        $this->cs->facet_limit = $last_limit;
        $this->cs->facet_max_limit = $last_max_limit;

        $data = array();
        if (isset($result['facets'][$filter]['data'])) {
            $titles = $this->cs->get_keywords_titles($mkw_arr);

            if (sizeof($result['facets'][$filter]['data'])) {

                $i = 0;
                foreach ($result['facets'][$filter]['data'] as $item) {

                    if (in_array($item->id, $mkw_arr)) {
                        $item->title = isset($titles[$item->id]) ? $titles[$item->id] : $item->id;
                        $data[] = $item;
                        $i += 1;
                    }

                    if ($i >= $limit) {
                        break;
                    }
                }
            }

            // Get names
        }



        return $data;
    }

    public function update_author_name($wp_id = 0, $name = '') {
        if ($wp_id) {
            $author = $this->cm->get_author_by_wp_uid($wp_id, true);
            $author->name = $name;
            $this->cm->update_author($author);
        }
    }

    /*
     * Wp user tags
     */

    public function get_user_tags($wp_uid) {
        $author = $this->cm->get_author_by_wp_uid($wp_uid);

        // Tags
        $catdata = '';
        $tags = $this->cm->get_author_tags($author->id);
        if (sizeof($tags)) {
            foreach ($tags as $tag) {
                $catdata .= $this->get_tag_link($tag->slug, $tag->name);
            }
        }
        return $catdata;
    }

    public function update_author_tags($wp_uid, $tags) {
        $author = $this->cm->get_author_by_wp_uid($wp_uid);

        $old_tags = $this->cm->get_author_tags($author->id);

        foreach ($old_tags as $old_tag) {

            if (!in_array($old_tag->id, $tags)) {
                $this->cm->remove_author_tag($author->id, $old_tag->id);
            }
        }
        foreach ($tags as $tag_id) {
            $this->cm->add_author_tag($author->id, $tag_id);
        }
    }

    /*
     * User functions
     */

    public function get_uid() {
        $user = $this->cm->get_current_user();
        return $user->ID;
    }

    public function get_user_search_filter($request_uri, $search_url = '') {
        // UNUSED
        $ret = array();
        if ($search_url != '/search') {
            $uf = $this->cm->get_uf();
            $ret = $uf->get_user_filter($request_uri, $search_url);
        }
        return $ret;
    }

    public function edit_author_tags($wp_uid) {
        ?>
        <input type="hidden" name="post_category[]" value="0">
        <div class="form-check">
            <?php
            $author = $this->cm->get_author_by_wp_uid($wp_uid);
            $tags = $this->cm->get_tags();
            $author_tags = $this->cm->get_author_tags($author->id, -1, false);
            $tag_arr = array();
            if (sizeof($author_tags)) {
                foreach ($author_tags as $tag) {
                    $tag_arr[] = $tag->id;
                }
            }

            if (sizeof($tags)) {
                foreach ($tags as $tag) {
                    $checked = '';
                    if (in_array($tag->id, $tag_arr)) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <div id="category-<?php print $tag->id ?>">
                        <input value="<?php print $tag->id ?>" class="form-check-input" <?php print $checked ?> type="checkbox" name="post_category[]" id="in-category-<?php print $tag->id ?>">
                        <label class="form-check-label" for="in-category-<?php print $tag->id ?>">
                            <?php print $tag->name ?>
                        </label>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <?php
    }

    /* Theme filters */

    public function theme_filter_holder($post) {
        if (is_array($post)) {
            $post = (object) $post;
        }
        /*
         * id, aid, wp_uid, fid, publish, date, last_upd, frating, title, content, img, ftab, link
         */
        $content = $post->content;
        if (strstr($content, '[su_')) {
            // Remove su spoilers
            $regv = '#\[su_([^\]]+)\].+\[/su_[\w\d]+\]#Us';
            if (preg_match_all($regv, $content, $mach)) {
                // var_dump($mach);              
                foreach ($mach[0] as $i => $val) {
                    $rtitle = '';
                    $reg2 = '#title="([^\"]+)#';
                    if (preg_match($reg2, $mach[1][$i], $m2)) {
                        $rtitle = $m2[1];
                    }

                    $content = str_replace($val, $rtitle, $content);
                }
            }
            // Remove all custom tags
            $regv = '#\[su_[^\]]+\]#Us';
            $content = preg_replace($regv, '', $content);
        }
        $content = $this->format_content($content, 400);
        $content = str_replace('<p>', '', $content);
        $content = str_replace('</p>', '<br />', $content);
        $ptime = $post->date;
        $critic_addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

        // User filter
        $uf = $this->cm->get_uf();
        $fdata = $uf->get_filters_by_url($post->link, true);

        $publish = $post->publish;

        // User profile link
        $uc = $this->cm->get_uc();
        $wp_user = $uc->getUserById($post->wp_uid);

        // Link to full post
        $link = $uf->get_filter_link($post->id, $wp_user->url);

        // Author name
        $author = $this->cm->get_author($post->aid);
        $author_title = $author->name;

        // img
        $img = $post->img;
        $img_path = '';
        if ($img) {
            $img_path = $uf->get_img_path($img);
        }

        ob_start();
        ?>
        <div id="filter-<?php print $post->id ?>" class="card sitem card-filter" data-id="<?php print $post->id ?>">           
            <div class="card-body">
                <div class="card-top mb-4">
                    <?php if ($img) { ?>
                        <div class="card-image">                                                    
                            <a href="<?php print $link ?>">
                                <img loading="lazy" class="fimg" src="<?php print $img_path; ?>">                        
                            </a>
                        </div>    
                    <?php } ?>                    
                    <div class="card-info">                  
                        <div class="mt-3">
                            <?php $this->theme_card_author($author_title, $post->aid); ?>
                        </div>
                        <div class="d-flex align-items-center">
                            <small class="text-body-secondary"><?php print $critic_addtime ?></small>                            
                        </div>
                    </div> 
                </div>  
                <div>   
                    <a href="<?php print $link ?>">
                        <h5 class="card-title mb-3 mt-0 break-line" title="<?php print $post->title ?>"><?php print $post->title ?></h5>                    
                        <div class="card-text text-body text-limited two-clm mb-3"><?php print $content ?></div>
                        <div class="card-fdata text-limited two-clm mb-3"><?php print $fdata['tags'] ?></div>  
                    </a>
                </div>
                <div class="card-action">
                    <div class="card-ratings">               
                        <?php if ($publish == 0) { ?>
                            <span class="r-item">
                                <i title="Private" class="icon-eye-off"></i> 
                            </span>
                        <?php } ?>
                        <span class="r-item c-link js-click">
                            <i class="icon-comment-empty"></i> <span class="c-count"></span>
                        </span>
                        <span class="r-item e-link js-click">
                            <i class="icon-thumbs-up"></i> <span class="e-count"></span>
                        </span>              
                    </div>
                    <a class="btn btn-outline-secondary text-nowrap" href="<?php print $link ?>">Show <i class="icon-right-open"></i></a>
                </div>
            </div>

        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function theme_user_filter($post) {
        /* [id] => 20
          [publish] => 1
          [aid] => 1230
          [wp_uid] => 81
          [fid] => 20
          [date] => 1703919218
          [last_upd] => 1703953656
          [rating] => 1
          [title] => Best Rated & ≥82% White Cast (Made Between '85 - 2000)
          [content] => The Stars "has photo" is for search result accuracy because missing photos means a high error rate for verdicts. 100% of the Stars must be White and at least 82% of the entire cast must be White too.
          [img] => 1230-1703919218.jpg */
        // img
        if (is_array($post)) {
            $post = (object) $post;
        }
        /*
         * id, aid, wp_uid, fid, publish, date, last_upd, frating, title, content, img, ftab, link
         */
        $content = $post->content;
        if (strstr($content, '[su_')) {
            // Remove su spoilers
            $regv = '#\[su_([^\]]+)\].+\[/su_[\w\d]+\]#Us';
            if (preg_match_all($regv, $content, $mach)) {
                // var_dump($mach);              
                foreach ($mach[0] as $i => $val) {
                    $rtitle = '';
                    $reg2 = '#title="([^\"]+)#';
                    if (preg_match($reg2, $mach[1][$i], $m2)) {
                        $rtitle = $m2[1];
                    }

                    $content = str_replace($val, $rtitle, $content);
                }
            }
            // Remove all custom tags
            $regv = '#\[su_[^\]]+\]#Us';
            $content = preg_replace($regv, '', $content);
        }
        $content = $this->format_content($content, 400);
        $content = str_replace('<p>', '', $content);
        $content = str_replace('</p>', '<br />', $content);
        $ptime = $post->date;
        $critic_addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

        // User filter
        $uf = $this->cm->get_uf();

        // Author name
        $author = $this->cm->get_author($post->aid);
        $author_title = $author->name;

        // img
        $img = $post->img;
        $img_path = '';
        if ($img) {
            $img_path = $uf->get_img_path($img);
        }

        ob_start();
        ?>

        <div class="clearfix mb-4">     
            <h1 class="mb-4"><?php print $post->title ?></h1>
            <?php if ($img) { ?>
                <div class="col-md-6 float-md-end mb-4 ms-md-3 text-center text-md-end">
                    <img loading="lazy" src="<?php print $img_path; ?>">
                </div>    
            <?php } ?>            
            <div>
                <div class="mb-3">                  
                    <div class="sitem">
                        <?php $this->theme_card_author($author_title, $post->aid, false); ?>
                    </div>                 
                    <small class="text-body-secondary"><?php print $critic_addtime ?></small>                                                
                </div> 
                <div>                             
                    <div class="card-text mb-3"><?php print $content ?></div>                     
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function theme_filter_item($item) {
        if ($this->cache_results) {
            $item_theme = $this->cache_theme_filter_item_get($item);
        } else {
            $item_theme = $this->theme_filter_holder($item);
        }
        return $item_theme;
    }

    public function cache_theme_filter_item_get($item) {
        $arg = (array) $item;
        $aid = $item->aid;

        $author = $this->cm->get_author($aid, true);
        $filename = "f-{$item->id}-{$item->last_upd}-{$author->last_upd}";
        $str = ThemeCache::cache('theme_filter_holder', false, $filename, 'filters', $this, $arg);
        return $str;
    }

    public function theme_filter_item_get($item) {
        // UNUSED
        if (is_array($item)) {
            $item = (object) $item;
        }

        $title = $item->title;

        if (!$title) {
            $title = ' ';
        }
        $content = $item->content;
        $aid = $item->aid;
        $author = $this->cm->get_author($aid);

        // Author name
        $author_title = $author->name;
        $author_title = $this->pccf_filter($author_title);
        $wp_uid = $item->wp_uid;

        // WP avatar
        $cav = $this->cm->get_cav();
        $wp_avatar = $cav->get_author_avatar($author, 64);

        $author_admin_img = '';

        if (!$wp_avatar) {
            // Author image

            $author_options = unserialize($author->options);
            $author_img = $author_options['image'];

            if ($author_img) {
                try {
                    $image = $this->get_local_thumb(100, 100, $author_img);
                    $author_admin_img = '<div class="a_img_container" style="background: url(' . $image . '); background-size: cover;"></div>';
                } catch (Exception $exc) {
                    
                }
            }
        }

        $umeta = '';

        // User profile link
        $uc = $this->cm->get_uc();
        $wp_user = $uc->getUserById($wp_uid);

        $author_link = $uc->get_user_profile_link($wp_user->url);
        $ucarma_class = ($wp_user->carma < 0) ? " minus" : " plus";
        $umeta = '<div class="umeta' . $ucarma_class . '">
                    <span class="urating" ><i class="icon-star"></i>' . (int) $wp_user->rating . '</span>                   
                </div>';

        $author_title_link = '<a href="' . $author_link . '">' . $author_title . '</a>';

        // Tags
        $catdata = '';
        $max_tags = 3;
        $tags = $this->cm->get_author_tags($author->id);
        if (sizeof($tags)) {
            $tags_count = 1;
            foreach ($tags as $tag) {
                $catdata .= $this->get_tag_link($tag->slug, $tag->name);
                $tags_count += 1;
                if ($tags_count > 3) {
                    break;
                }
            }
        }

        if ($catdata) {
            $catdata = '<div class="a_cat">' . $catdata . '</div>';
        }

        // Time
        $ptime = $item->date;
        $critic_addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

        // User filter
        $uf = $this->cm->get_uf();
        $fdata = $uf->get_filters_by_url($item->link, true);

        $publish = $item->publish;

        $pub_icon = '';
        if ($publish == 0) {
            $pub_icon = '<i class="icon-eye-off"></i> Private';
        }

        // Link to full post
        $link = $uf->get_filter_link($item->id, $wp_user->url);

        // img
        $img = $item->img;
        $img_str = '';
        if ($img) {
            $img_path = $uf->get_img_path($img);
            $img_str = '<a href="' . $link . '"><img src="' . $img_path . '" /></a>';
        }

        // Country
        $country_img = '';

        $short_codes_exist_class = '';
        $wp_core = '';

        $content = $this->pccf_filter($content);
        if ($content) {
            $content = '<div class="sfilter-content">' . $content . '</div>';
        }
        $title = $this->pccf_filter($title);

        $filter_content = '';
        if ($content || $title) {


            $filter_content = '
                    <div class="sfilter-item">' . $img_str
                    . '<h3 class="sfilter-title"><a href="' . $link . '">' . $title . '</a></h3>' . $content
                    . '<div>' . $fdata['tags'] . "</div></div>";
        }

        $stars_data = 0;
        $actorsdata = '';

        if ($wp_avatar) {
            $actorsdata = $wp_avatar;
        } else if ($author_admin_img) {
            $actorsdata = $author_admin_img;
        }

        if (!$actorsdata) {
            $actorsdata = '<span></span>';
        }

        $actorsdata_link = '<a href="' . $author_link . '">' . $actorsdata . '</a>';

        $review_bottom = '<div class="review_bottom"><div class="r_type">' . $pub_icon . '</div><div class="r_right"><div class="r_date">' . $critic_addtime . '</div>' . $country_img . '</div></div>';

        $reaction_data = $this->get_user_reactions($item->id, 1, false);

        $actorsresult = '<div class="a_msg">
    <div class="a_msg_i">
        ' . $filter_content . $review_bottom . '<div class="ugol"><div></div></div>
    </div>
        <div class="em_hold"></div>
        <div class="amsg_aut">
            ' . $actorsdata_link . '
            <div class="review_autor_name">' . $author_title_link . $umeta . $catdata . '</div>
            ' . $reaction_data . '
        </div>
</div>';

        return $actorsresult;
    }

    /* Theme watchlist */

    public function theme_watchlist_item($item) {
        if ($this->cache_results) {
            $item_theme = $this->cache_theme_watchlist_item_get($item);
        } else {
            $item_theme = $this->theme_watchlist_item_get($item);
        }
        return $item_theme;
    }

    public function cache_theme_watchlist_item_get($item) {
        $arg = (array) $item;
        $aid = $item->aid;
        $author = $this->cm->get_author($aid, true);
        $filename = "wl-{$item->id}-{$item->last_upd}-{$author->last_upd}";
        $str = ThemeCache::cache('theme_watchlist_item_get', false, $filename, 'watchlists', $this, $arg);
        return $str;
    }

    public function theme_watchlist_item_get($post) {
        if (is_array($post)) {
            $post = (object) $post;
        }

        /*
         *  [id] => 199
          [aid] => 1374
          [wp_uid] => 42
          [top_mid] => 0
          [publish] => 0
          [date] => 1742046907
          [last_upd] => 1742229383
          [frating] => 0
          [title] => yrdtest
          [content] => dsf
          [type] => 0
          [items] => 4
          [w] => 1
          [upub] => 1
         */


        $content = $post->content;
        if (strstr($content, '[su_')) {
            // Remove su spoilers
            $regv = '#\[su_([^\]]+)\].+\[/su_[\w\d]+\]#Us';
            if (preg_match_all($regv, $content, $mach)) {
                // var_dump($mach);              
                foreach ($mach[0] as $i => $val) {
                    $rtitle = '';
                    $reg2 = '#title="([^\"]+)#';
                    if (preg_match($reg2, $mach[1][$i], $m2)) {
                        $rtitle = $m2[1];
                    }

                    $content = str_replace($val, $rtitle, $content);
                }
            }
            // Remove all custom tags
            $regv = '#\[su_[^\]]+\]#Us';
            $content = preg_replace($regv, '', $content);
        }
        $content = $this->format_content($content, 400);
        $content = str_replace('<p>', '', $content);
        $content = str_replace('</p>', '<br />', $content);
        $ptime = $post->date;
        $critic_addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

        // Author name
        $author = $this->cm->get_author($post->aid);
        $author_title = $author->name;

        // User profile link
        $uc = $this->cm->get_uc();
        $wp_user = $uc->getUserById($post->wp_uid);

        // User watchlist
        $wl = $this->cm->get_wl();
        $publish = $post->publish;

        // Link to full post                
        $link = $wl->get_list_link($post->id, $wp_user->url);

        // img
        $img_str = $wl->get_list_collage($post->id);

        ob_start();
        ?>
        <div id="wlist-<?php print $post->id ?>" class="card sitem card-wlist" data-id="<?php print $post->id ?>">           
            <div class="card-body">
                <div class="wlist-image mb-3">                                                    
                    <a href="<?php print $link ?>">
                        <?php print $img_str; ?>                      
                    </a>
                </div>   
                <div class="card-top">
                    <div class="card-info">                  
                        <div class="mt-3">
                            <?php $this->theme_card_author($author_title, $post->aid); ?>
                        </div>
                        <div class="d-flex align-items-center">
                            <small class="text-body-secondary"><?php print $critic_addtime ?></small>                            
                        </div>
                    </div> 
                </div> 
                <div>         
                    <a href="<?php print $link ?>">
                        <h5 class="card-title mb-3 mt-0 break-line" title="<?php print $post->title ?>"><?php print $post->title ?></h5>                    
                        <div class="card-text text-body text-limited two-clm mb-3"><?php print $content ?></div>                      
                    </a>
                </div>
                <div class="card-action">
                    <div class="card-ratings">               
                        <?php if ($publish == 0) { ?>
                            <span class="r-item">
                                <i title="Private" class="icon-eye-off"></i> 
                            </span>
                        <?php } ?>
                        <span class="r-item c-link js-click">
                            <i class="icon-comment-empty"></i> <span class="c-count"></span>
                        </span>
                        <span class="r-item e-link js-click">
                            <i class="icon-thumbs-up"></i> <span class="e-count"></span>
                        </span>              
                    </div>
                    <a class="btn btn-outline-secondary text-nowrap" href="<?php print $link ?>">Show <i class="icon-right-open"></i></a>
                </div>
            </div>

        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function theme_user_watchlist($post) {
        /* [id] => 20
          [publish] => 1
          [aid] => 1230
          [wp_uid] => 81
          [fid] => 20
          [date] => 1703919218
          [last_upd] => 1703953656
          [rating] => 1
          [title] => Best Rated & ≥82% White Cast (Made Between '85 - 2000)
          [content] => The Stars "has photo" is for search result accuracy because missing photos means a high error rate for verdicts. 100% of the Stars must be White and at least 82% of the entire cast must be White too.
          [img] => 1230-1703919218.jpg */
        // img
        if (is_array($post)) {
            $post = (object) $post;
        }
        /*
         * id, aid, wp_uid, fid, publish, date, last_upd, frating, title, content, img, ftab, link
         */
        $content = $post->content;
        if (strstr($content, '[su_')) {
            // Remove su spoilers
            $regv = '#\[su_([^\]]+)\].+\[/su_[\w\d]+\]#Us';
            if (preg_match_all($regv, $content, $mach)) {
                // var_dump($mach);              
                foreach ($mach[0] as $i => $val) {
                    $rtitle = '';
                    $reg2 = '#title="([^\"]+)#';
                    if (preg_match($reg2, $mach[1][$i], $m2)) {
                        $rtitle = $m2[1];
                    }

                    $content = str_replace($val, $rtitle, $content);
                }
            }
            // Remove all custom tags
            $regv = '#\[su_[^\]]+\]#Us';
            $content = preg_replace($regv, '', $content);
        }
        $content = $this->format_content($content, 400);
        $content = str_replace('<p>', '', $content);
        $content = str_replace('</p>', '<br />', $content);
        $ptime = $post->date;
        $critic_addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

        // Author name
        $author = $this->cm->get_author($post->aid);
        $author_title = $author->name;

        ob_start();
        ?>

        <div class="clearfix mb-4">     
            <h1 class="mb-4"><?php print $post->title ?></h1>                  
            <div>
                <div class="mb-3">                  
                    <div class="sitem">
                        <?php $this->theme_card_author($author_title, $post->aid, false); ?>
                    </div>                 
                    <small class="text-body-secondary"><?php print $critic_addtime ?></small>                                                
                </div> 
                <div>                             
                    <div class="card-text mb-3"><?php print $content ?></div>                     
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /*
     * Bootstrap carousel
     */

    public function audience_carousel_ajax($movie_id = 0, $vote_type = 'a') {
        ?>            
        <div class="bs-carousel-load loadblock" data-func="audience_carousel" data-id="<?php print $movie_id ?>" data-keys="<?php print $vote_type ?>"></div>
        <?php
    }

    public function custom_carousel_ajax($id = 0) {
        ?>            
        <div class="bs-carousel-load loadblock" data-func="custom_carousel" data-id="<?php print $id ?>"></div>
        <?php
    }

    public function get_actors_carousel_ajax($movie_id = 0, $type = '') {
        ob_start();
        ?>            
        <div class="bs-carousel-load loadblock" data-func="actor_carousel" data-id="<?php print $movie_id ?>" data-keys="<?php print $type ?>"></div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function get_custom_carousel_data($id = 0, $page = 0, $per_page = 1, $limit = 1) {
        // Load custom block
        $scoll_id = 'custom_carousel_id_' . $id;
        $cdata = $this->cm->get_meta_compilation_link($id);
        if (!$cdata->url) {
            return;
        }
        return $this->search_carousel($cdata->url, $scoll_id, $page, $per_page, $limit, false, true);
    }

    public function get_custom_carousel($id = 0, $page = 0, $per_page = 3, $limit = 12, $only_data = false) {
        $scoll_id = 'custom_carousel_id_' . $id;
        $cdata = $this->cm->get_meta_compilation_link($id);
        if (!$cdata->url) {
            return;
        }
        return $this->search_carousel($cdata->url, $scoll_id, $page, $per_page, $limit, false, $only_data);
    }

    public function search_carousel($url = '', $scoll_id = '', $page = 0, $per_page = 3, $limit = 12, $html_req = true, $only_data = false) {

        $array_data = array();
        $tab_key = '';

        try {

            $last_req = $_SERVER['REQUEST_URI'];
            $_SERVER['REQUEST_URI'] = $url;
            $local_front = new CriticFront();
            $local_front->init_search_filters();
            $tab_key = $local_front->get_tab_key();
            $uid = $local_front->get_uid();
            $local_front->per_page = $per_page;
            //$uid = 0, $ids = array(), $show_facets = true, $show_count = true, $only_curr_tab = false, $limit = -1, $page = -1, $show_main = true, $show_chart = true, $fields = array()
            $array_data = $local_front->find_results($uid, array(), false, true, true, $limit, $page);
            $_SERVER['REQUEST_URI'] = $last_req;
        } catch (Exception $exc) {
            
        }
        $posts_arr = array();
        if ($array_data && $array_data[$tab_key]) {
            $posts_arr = $array_data[$tab_key];
        }
        $movie_id = 0;
        return $this->review_carousel($movie_id, $scoll_id, $tab_key, $posts_arr, $html_req, $only_data, $url);
    }

    public function audience_carousel($movie_id = 0, $vote_type = 'a', $html_req = true) {
        $start = 0;
        $limit = 12;
        $scoll_id = 'audience_scroll_' . $vote_type;
        $posts_arr = $this->get_audience_post_arr($movie_id, $vote_type, $limit, $start);

        // Audience link
        $ext_link = '/search/tab_critics/author_audience';
        if ($vote_type == 'p') {
            $ext_link = '/search/tab_critics/auvote_pay/author_audience';
        } else if ($vote_type == 'n') {
            $ext_link = '/search/tab_critics/auvote_skip/author_audience';
        }

        if ($movie_id) {
            $ext_link .= '/movie_' . $movie_id;
        }

        return $this->review_carousel($movie_id, $scoll_id, 'critics', $posts_arr, $html_req, false, $ext_link);
    }

    public function pro_carousel($movie_id = 0, $html_req = true) {
        $start = 0;
        $limit = 12;
        $scoll_id = 'pro_scroll';
        $posts_arr = $this->get_pro_post_arr($movie_id, $limit, $start);

        // Pro link        
        $ext_link = '/search/tab_critics/author_critic';

        if ($movie_id) {
            $ext_link .= '/movie_' . $movie_id;
        }

        print $this->review_carousel($movie_id, $scoll_id, 'critics', $posts_arr, $html_req, false, $ext_link);
    }

    public function actors_carousel($movie_id = '', $type = '') {
        $result = [];
        $actor_type = [];

        if ($type == 'stars') {
            $actor_type[] = 'star';
        } else if ($type == 'main') {
            $actor_type[] = 'main';
        } else if ($type == 'extra') {
            $actor_type[] = 'extra';
        } else if ($type == 'directors') {
            $actor_type[] = 'director';
            $actor_type[] = 'writer';
            $actor_type[] = 'cast_director';
            $actor_type[] = 'producer';
        }
        !class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';

        $content_array = MOVIE_DATA::get_actors_template($movie_id, $actor_type);

        foreach ($content_array as $i => $data) {
            $result[] = $data['content_data'];
        }

        if (!$result) {
            return '';
        }

        $data = array();
        $scoll_id = 'actor_carousel_' . $type;
        $posts_arr = array(
            'list' => $result,
            'count' => count($result)
        );
        return $this->review_carousel($movie_id, $scoll_id, 'actors', $posts_arr, false);
    }

    public function review_carousel($movie_id, $scoll_id = '', $type = 'critics', $posts_arr = array(), $html_req = true, $only_data = false, $search_link = '') {


        $total_count = $posts_arr['count'] ? $posts_arr['count'] : 0;
        $count = $total_count;

        if ($count >= 1000) {
            $count = 999;
        }

        if ($count == 0) {
            return '';
        }

        $width = 350;

        $posts = $posts_arr['list'] ? $posts_arr['list'] : [];
        $data = array();
        $show_movie = true;
        if ($movie_id) {
            $show_movie = false;
        }

        if ($posts) {

            if ($type == 'movies' || $type == 'games') {
                $data = $this->render_movies_list($posts, false);
                $width = 250;
            } else if ($type == 'critics') {
                foreach ($posts as $post) {
                    $data[] = $this->theme_review_holder($post, $show_movie);
                }
            } else if ($type == 'filters') {
                
            } else if ($type == 'watchlists') {
                
            } else if ($type == 'comments') {
                
            } else if ($type == 'actors') {
                $width = 280;
                $data = $posts;
            }
        }

        if ($only_data) {
            // Return data ony for ajax
            return $data;
        }

        ob_start();

        if ($html_req) {
            if ($data) {
                $data = '"' . addslashes(json_encode($data)) . '"';
            } else {
                $data = 'null';
            }
            $scrpts = array();
            $scrpts[] = '<script  type="text/javascript" >';
            $scrpts[] = 'var ' . $scoll_id . '_data = ' . $data . '; ';
            $scrpts[] = '</script>';
            print (implode("\n", $scrpts));
        }
        ?>
        <div id="carousel_<?php print $scoll_id ?>" class="bs-carousel mb-4" data-key="<?php print $scoll_id ?>" data-total="<?php print $count ?>" data-width="<?php print $width ?>"" data-movie="<?php print $movie_id ?>"></div>
        <div class="mb-4 text-body-secondary text-center">Total found: <?php print $total_count ?><?php if ($search_link) {
            ?>. <a href="<?php print $search_link ?>">Show in Search <small><i class="icon-right-open"></i></small></a><?php } ?>
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();

        if ($html_req) {
            // Return html carousel content
            $ret = $content;
        } else {
            // Return carousel and data
            $ret_ajax = array(
                'content' => $content,
                'data' => $data,
            );
            $ret = json_encode($ret_ajax);
        }
        return $ret;
    }

    public function get_audience_carousel_data($page = 0, $per_page = 1, $limit = 0, $movie_id = 0, $vote_type = 'a') {
        $start = $page * $per_page;
        $posts_arr = $this->get_audience_post_arr($movie_id, $vote_type, $limit, $start);
        $posts = $posts_arr['list'] ? $posts_arr['list'] : [];
        $data = array();
        $show_movie = true;
        if ($movie_id) {
            $show_movie = false;
        }
        if ($posts) {
            foreach ($posts as $post) {
                $data[] = $this->theme_review_holder($post, $show_movie);
            }
        }
        return $data;
    }

    public function get_pro_carousel_data($page = 0, $per_page = 1, $limit = 0, $movie_id = 0) {
        $start = $page * $per_page;
        $posts_arr = $this->get_pro_post_arr($movie_id, $limit, $start);
        $posts = $posts_arr['list'] ? $posts_arr['list'] : [];
        $data = array();
        $show_movie = true;
        if ($movie_id) {
            $show_movie = false;
        }
        if ($posts) {
            foreach ($posts as $post) {
                $data[] = $this->theme_review_holder($post, $show_movie);
            }
        }
        return $data;
    }

    private function get_audience_post_arr($movie_id = 0, $vote_type_str = 'a', $limit = 999, $start = 0) {
        $a_type = 2;
        $ss = $this->cm->get_settings();
        $unique = $ss['audience_unique'];

        $vote_type = 0;
        if ($vote_type_str == 'p') {
            $vote_type = 1;
        } else if ($vote_type_str == 'n') {
            $vote_type = 2;
        }

        if ($vote_type == 1 || $vote_type == 2) {
            // $vote_type = 1; Positive
            // $vote_type = 2; Negative
            $unique = $ss['audience_top_unique'];
        }

        // TODO Unique logic
        //$unique = 0;
        // Vote to rating  

        $tags = array();
        $meta_type = array();
        $min_rating = 0;
        $min_au = 0;
        $max_au = 0;
        $vote = 0;

        $posts_arr = $this->cs->get_last_critics($a_type, $limit, $movie_id, $start, $tags, $meta_type, $min_rating, $vote, $min_au, $max_au, $vote_type, $unique);
        return $posts_arr;
    }

    private function get_pro_post_arr($movie_id = 0, $limit = 999, $start = 0) {
        $a_type = 1;
        // Get settings
        $ss = $this->cm->get_settings();
        $min_rating = $ss['posts_rating'];
        $meta_type = array();
        if ($ss['posts_type_1']) {
            $meta_type[] = 1;
        }
        if ($ss['posts_type_2']) {
            $meta_type[] = 2;
        }
        if ($ss['posts_type_3']) {
            $meta_type[] = 3;
        }

        $unique = 0;
        $tags = array();
        $min_au = 0;
        $max_au = 0;
        $vote = 0;
        $vote_type = 0;

        $posts_arr = $this->cs->get_last_critics($a_type, $limit, $movie_id, $start, $tags, $meta_type, $min_rating, $vote, $min_au, $max_au, $vote_type, $unique);
        return $posts_arr;
    }

    public function theme_review_holder($post = array(), $show_movie = true) {

        /*
          [id] => 293064
          [date] => 1719392163
          [date_add] => 1719392193
          [top_movie] => 140384
          [mtitle] => The Old Way
          [myear] => 2023
          [aid] => 2238
          [author_name] => dfsafddsf
          [aurating] => 2.6
          [title] => dfdsf
          [content] => sdfdsfsdf
          [auvote] => 3
         */

        $keywords = $this->get_search_keywords();
        if ($keywords) {
            $title = $post->t;
            $content = $post->c;
        } else {
            $title = $post->title;

            $content = $post->content;
            if (strstr($content, '[su_')) {
                // Remove su spoilers
                $regv = '#\[su_([^\]]+)\].+\[/su_[\w\d]+\]#Us';
                if (preg_match_all($regv, $content, $mach)) {
                    // var_dump($mach);              
                    foreach ($mach[0] as $i => $val) {
                        $rtitle = '';
                        $reg2 = '#title="([^\"]+)#';
                        if (preg_match($reg2, $mach[1][$i], $m2)) {
                            $rtitle = $m2[1];
                        }

                        $content = str_replace($val, $rtitle, $content);
                    }
                }
                // Remove all custom tags
                $regv = '#\[su_[^\]]+\]#Us';
                $content = preg_replace($regv, '', $content);
            }
            $content = $this->format_content($content, 400);
            $content = str_replace('<p>', '', $content);
            $content = str_replace('</p>', '<br />', $content);
        }
        $ptime = $post->date;
        $critic_addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

        // Get link
        $critic = new stdClass();
        $critic->author_type = $post->author_type;
        $critic->author_name = $post->author_name;
        $critic->title = $post->title;
        $critic->id = $post->id;

        $slug = $this->get_critic_slug($critic);
        $link = '/critics/' . $slug . '/';
        $meta_block = '';

        $show_robot_icon = false;

        if ($show_robot_icon && $post->author_type != 2) {
            if ($post->top_movie > 0) {
                $meta_block = '<span class="r-item loadblock" data-func="post_meta_block" data-id="' . $post->id . '" data-keys="' . $post->top_movie . '"></span>';
                //$info_link = $this->get_info_link($post->id, $post->top_movie, $post->pmstate);
            }
        }

        // Find video link
        /* view_type 
          0 => 'Default',
          1 => 'Youtube',
          2 => 'Odysee',
          3 => 'Bitchute'
         */
        $video_link = '';
        if ($post->author_type == 1 && $post->viewtype != 0) {
            // Only pro
            $video_link = $this->find_video_link($post->link, $post->id, true);
        }

        ob_start();
        ?>
        <div id="review-<?php print $post->id ?>" class="card sitem card-review" data-id="<?php print $post->id ?>" data-atype="<?php print $post->author_type; ?>">           
            <div class="card-body">
                <div class="card-top mb-4">
                    <?php if ($show_movie && $post->top_movie && $post->mtitle) { ?>
                        <?php
                        $thumbs = array([220, 330], [440, 660]);
                        $array_tsumb = array();
                        foreach ($thumbs as $thumb) {
                            $array_tsumb[] = $this->get_thumb_path_full($thumb[0], $thumb[1], $post->top_movie, $post->madd);
                        }
                        $movie_link = $this->get_simple_movie_link($post->mpname, $post->type);
                        ?>                    
                        <div class="card-image">                                                    
                            <a href="<?php print $movie_link ?>">
                                <img loading="lazy" src="<?php echo $array_tsumb[0]; ?>"
                                     <?php if ($array_tsumb[1]) { ?> srcset="<?php echo $array_tsumb[0]; ?> 1x, <?php echo $array_tsumb[1]; ?> 2x"<?php } ?> >                        
                            </a>
                        </div>    
                    <?php } ?>                    
                    <div class="card-info">
                        <?php if ($show_movie) { ?>
                            <p class="card-movie break-line mb-3">
                                <?php if ($post->top_movie && $post->mtitle) { ?>
                                    <b><a href="<?php print $movie_link ?>" title="<?php print $post->mtitle ?> (<?php print $post->myear ?>)"><?php print $post->mtitle ?> (<?php print $post->myear ?>)</a></b>
                                <?php } ?>
                            </p>
                        <?php } else { ?>
                            <div class="mb-3"></div>
                        <?php } ?>                    
                        <?php $this->theme_card_author($post->author_name, $post->aid, true); ?>
                        <div class="d-flex align-items-center">    
                            <span class="flag"></span>                        
                            <small class="text-body-secondary"><?php print $critic_addtime ?></small>                            
                        </div>
                    </div> 
                </div>  
                <div>
                    <?php if ($video_link) { ?>
                        <?php
                        /*
                         * TODO bichude load block, click js
                         */
                        ?>                                      
                        <a class="card-vi mb-3 icntn r-link js-click" href="<?php print $link ?>">
                            <?php $this->theme_review_img($video_link, $post->id) ?>
                            <div class="video"><i class="icon-play"></i></div>
                            <h5 class="card-tvi break-line" title="<?php print strip_tags($post->title) ?>"><?php print $title ?></h5>    
                        </a>

                    <?php } else { ?>    
                        <a class="r-link js-click" href="<?php print $link ?>">
                            <h5 class="card-title mb-3 mt-0 break-line" title="<?php print strip_tags($post->title) ?>"><?php print $title ?></h5>                    
                            <div class="card-text text-body text-limited mb-3"><?php print $content ?></div>
                        </a>  
                    <?php } ?>
                </div>
                <div class="card-action">
                    <div class="card-ratings">
                        <?php
                        if ($post->author_type == 2) {
                            // Audience only
                            ?>
                            <span class="r-item" title="Audience rating: <?php print $post->aurating ?>/5">
                                <i class="icon-star"></i> <span><?php print $post->aurating ?></span>
                            </span>
                            <?php
                        } else if ($post->author_type == 1) {
                            ?>
                            <span class="rating-pro"></span>
                            <?php
                        }
                        ?>                    
                        <span class="r-item c-link js-click">
                            <i class="icon-comment-empty"></i> <span class="c-count"></span>
                        </span>
                        <span class="r-item e-link js-click">
                            <i class="icon-thumbs-up"></i> <span class="e-count"></span>
                        </span>
                        <?php print $meta_block; ?>
                    </div>
                    <a class="btn btn-outline-secondary r-link js-click text-nowrap" href="<?php print $link ?>">More <i class="icon-right-open"></i></a>
                </div>
            </div>

        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function theme_card_author($author_name = '', $aid = 0, $only_get = false, $show_tags = true) {
        $author_icon = '<i class="icon-user-circle-o"></i>';

        // Author data
        $cache_actor = $this->cache_author_data($aid, $only_get);
        if ($cache_actor) {
            $author_icon = $cache_actor['avatar'];
            $author_name = $cache_actor['name'];
            if ($cache_actor['link']) {
                $author_name = '<a href="' . $cache_actor['link'] . '">' . $author_name . '</a>';
            }
        }
        ?>
        <p class="card-author d-flex align-items-center<?php
        if (!$cache_actor) {
            print " loadblock";
        }
        ?>" data-func="theme_card_author" data-id="<?php print $aid ?>">
            <span class="card-icon"><?php print $author_icon ?></span>
            <span class="card-author-info d-flex flex-column">
                <span class="card-author-name break-line"><?php print $author_name ?></span>
                <?php if ($show_tags && $cache_actor['tags']) { ?>            
                    <small class="card-author-tags break-line"><?php print $cache_actor['tags'] ?></small>
                <?php } ?>
            </span>

        </p>
        <?php
    }

    public function theme_post_meta_block($id = 0, $top_movie = 0) {
        $meta_state = $this->cm->get_critic_meta_state($id, $top_movie);
        $info_link = $this->get_info_link($id, $top_movie, $meta_state->state);
        return $info_link;
    }

    public function cache_author_data($aid, $get_only = false) {
        if ($this->cache_results) {
            $filename = "profile-{$aid}";
            $str = ThemeCache::cache('get_author_data_serialize', false, $filename, 'user', $this, $aid, $get_only);
            if ($str) {
                return unserialize($str);
            }
            return '';
        } else {
            return $this->get_author_data($aid);
        }
    }

    public function get_author_data_serialize($aid) {
        return serialize($this->get_author_data($aid));
    }

    public function get_author_data($aid) {
        $thumb_size = 64;
        $author = $this->cm->get_author($aid);
        $author_link = '';

        // Author name        
        $author_name = $this->pccf_filter($author->name);

        // WP avatar
        $cav = $this->cm->get_cav();
        $author_options = unserialize($author->options);
        $avatar = '';
        if ($author->type == 2) {
            // Audience
            $wp_avatar = $cav->get_author_avatar($author, $thumb_size);
            $avatar = $wp_avatar;
            if (!$wp_avatar) {
                // Author image
                $author_img = $author_options['image'];
                if ($author_img) {
                    try {
                        $image = $this->get_local_thumb($thumb_size, $thumb_size, $author_img);
                        $avatar = '<span class="a_img_container" style="background: url(' . $image . '); background-size: cover;"></span>';
                    } catch (Exception $exc) {
                        
                    }
                }
            }
        } else {
            // pro avatar
            $author_options = unserialize($author->options);
            $author_img = $cav->get_pro_thumb($thumb_size, $thumb_size, $author->avatar_name);
            if ($author_img) {
                $avatar = '<span class="a_img_container" style="background: url(' . $author_img . '); background-size: cover;"></span>';
            }
            $author_link = '/search/tab_critics/from_' . $aid;
        }

        if (!$avatar) {
            // Empty image
            $avatar = '<span class="a_img_def"></span>';
        }


        // Tags
        $catdata = '';
        $max_tags = 3;
        $tags = $this->cm->get_author_tags($author->id);
        if (sizeof($tags)) {
            $tags_count = 1;
            foreach ($tags as $tag) {
                $catdata .= $this->get_tag_link($tag->slug, $tag->name);
                $tags_count += 1;
                if ($tags_count > $max_tags) {
                    break;
                }
            }
        }
        // User profile link        

        $wp_uid = $author->wp_uid;
        if ($wp_uid) {
            $uc = $this->cm->get_uc();
            $wp_user = $uc->getUserById($wp_uid);
            if ($wp_user->url) {
                $author_link = $uc->get_user_profile_link($wp_user->url);
            }
        }
        $ret = array(
            'wp_uid' => $wp_uid,
            'avatar' => $avatar,
            'link' => $author_link,
            'name' => $author_name,
            'tags' => $catdata,
        );
        return $ret;
    }

    public function ajax_review_ratings($ids_str, $ftype = 0) {
        /*
         * ftype
         *  0 - critic
         *  1 - filters
         *  2 - lists
         */
        if (!$ids_str) {
            return array();
        }

        $ids = array();
        try {
            foreach ($ids_str as $id_str) {
                $ids_arr = explode('-', $id_str);
                $id = $ids_arr[0];
                $type = $ids_arr[1];
                $ids[0][] = $id;
                $ids[$type][] = $id;
            }
        } catch (Exception $exc) {
            
        }

        if (!$ids) {
            return array();
        }

        // Comments
        $comments = $this->cm->get_comments();
        $comments_numbers = $comments->get_comments_number_all($ids[0], $ftype);

        // Emotions
        $ce = $this->cm->get_ce();
        $emotions = $ce->get_emotions_counts_all($ids[0], $ftype);

        $ret = array(
            'comments' => $comments_numbers,
            'emotions' => $emotions,
        );

        if ($ftype == 0) {
            // reviews only
            $ratings = array();
            if (isset($ids[2])) {
                // Audience flags
                $ratings = $this->cm->get_posts_rating($ids[2]);
                $flags = array();
                if ($ratings) {
                    foreach ($ratings as $key => $value) {
                        $ip = isset($value['ip']) ? $value['ip'] : '';
                        if ($ip) {
                            $country_img = $this->theme_country_flag_by($ip);
                            if ($country_img) {
                                $flags[$key] = $country_img;
                            }
                        }
                    }
                }
            }

            $rating_pro = array();
            if (isset($ids[1])) {
                // Pro rating
                $posts = $this->cm->get_posts_by_ids($ids[1]);
                if ($posts) {
                    foreach ($posts as $post) {
                        $custom_rating = $this->get_custom_critic_rating($post, false);
                        if ($custom_rating) {
                            $rating_pro[$post->id] = $custom_rating;
                        }
                    }
                }
            }
        }

        $ret['flags'] = $flags;
        $ret['rating_pro'] = $rating_pro;

        return $ret;
    }

    private function theme_country_flag_by($ip) {
        // Country
        $country_img = '';

        $country_data = $this->cm->get_geo_flag_by_ip($ip);
        if ($country_data['path']) {
            $country_name = $country_data['name'];

            $country_img = '<div class="nte cflag" title="' . $country_name . '">
          <div class="nbtn"><img src="' . $country_data['path'] . '" /></div>
          <div class="nte_show">
          <div class="nte_in">
          <div class="nte_cnt">
          This review was posted from ' . $country_name . ' or from a VPN in ' . $country_name . '.
          </div>
          </div>
          </div>
          </div>';
        }
        return $country_img;
    }

    public function show_carousel_list($array_list = array(), $movie_id = 0) {
        foreach ($array_list as $value) {
            //'title' => 'Latest Audience Reviews:', 'id' => 'audience_scroll', 'class' => 'audience_review widthed ',
            //'tabs' => array('p' => 'Positive', 'n' => 'Negative', 'a' => 'Latest')
            extract($value);
            ?>
            <section class="<?php print $class ?> mb-5"> 
                <h2 class="text-center mb-4"><?php print $title ?></h2>
                <?php if ($tabs) { ?>
                    <ul class="nav nav-pills justify-content-center mb-4">
                        <?php
                        // Tabs logic
                        $i = 0;
                        foreach ($tabs as $k => $v) {
                            $id_key = $id . '_' . $k;
                            $active = '';
                            if ($i == 0) {
                                $active = ' active';
                            }
                            ?>
                            <li class="nav-item tab-<?php print $id_key; ?>">
                                <a href="#tab-<?php print $id_key; ?>" class="nav-link<?php print $active; ?>" data-bs-toggle="tab" data-id="tab-<?php print $id_key; ?>"><?php print $v; ?></a>
                            </li>
                            <?php
                            $i++;
                        }
                        ?>
                    </ul>
                    <div class="tab-content">
                        <?php
                        $i = 0;
                        foreach ($tabs as $k => $v) {
                            $id_key = $id . '_' . $k;
                            $active = false;
                            if ($i == 0) {
                                $active = true;
                            }
                            gmi('before ' . $id_key);
                            ?>
                            <div class="tab-pane fade<?php
                            if ($active) {
                                print ' show active';
                            }
                            ?>" id="tab-<?php print $id_key; ?>">
                                     <?php
                                     if ($id == 'audience_scroll') {
                                         if ($active) {
                                             print $this->audience_carousel($movie_id, $k);
                                         } else {
                                             $this->audience_carousel_ajax($movie_id, $k);
                                         }
                                     }
                                     ?>
                            </div>
                            <?php
                            gmi('after ' . $id_key);
                            $i++;
                        }
                        ?>
                    </div>
                    <?php
                } else if ($dropdown) {
                    /*
                      [id] => 99
                      [title] => Homogenous Cast, Minimal LGBT/Woke Keywords
                      [type] => 5
                      [url] => /search/sort_random-desc/show_actorsdata_sphoto_wokedata_lgbt_woke/type_movies/cast_main/sphoto_exist/minus-simmain_32-100/minus-simall_29-100/minus-woke_3-20/minus-lgbt_2-20
                      [weight] =>
                      [select_type] => 0
                      [parents] => woke_movies
                      [description] =>
                      [enable] => 1
                      [last_update] => 1724967403
                      )
                     */

                    $rand_key = random_int(0, count($dropdown) - 1);
                    $first_item = $dropdown[$rand_key];
                    ob_start();
                    ?>
                    <!-- Главный заголовок и dropdown -->
                    <div class="dropdown-block mb-3">
                        <div class="dropdown d-flex justify-content-center align-items-center mb-4">
                            <h3 class="dropdown-title mb-0 me-2"><?php print $first_item['title'] ?></h3>         
                            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php
                                foreach ($dropdown as $key => $item) {
                                    $compilation_id = "clps-{$item['id']}";
                                    ?>
                                    <li><a class="dropdown-item multi<?php
                                        if ($key == $rand_key) {
                                            print ' active';
                                        }
                                        ?>" href="#" data-title="<?php print $item['title'] ?>" data-target="#<?php print $compilation_id ?>"><?php print $item['title'] ?></a>
                                    </li>                               
                                <?php } ?>                        
                            </ul>
                        </div>
                        <div class="dropdown-text">
                            <?php
                            foreach ($dropdown as $key => $item) {
                                $compilation_id = "clps-{$item['id']}";
                                ?>
                                <div class="collapse<?php
                                if ($key == $rand_key) {
                                    print ' show';
                                }
                                ?>" id="<?php print $compilation_id ?>">
                                         <?php
                                         $this->custom_carousel_ajax($item['id']);
                                         ?>
                                </div>                        
                            <?php } ?>                        
                        </div>
                    </div>
                    <?php
                } else if ($random) {
                    if (strstr($id, 'compilation_')) {
                        $rand_keys = array_keys($random);
                        shuffle($rand_keys);
                        $rand_key = array_pop($rand_keys);

                        $rand_id = "compilation_scroll_id_" . $rand_key;
                        ?>
                        <div class="random-holder">
                            <div class="random-header d-flex justify-content-center align-items-center mb-4">
                                <h3 class="mb-0 me-2"><?php print $random[$rand_key]['title'] ?></h3>                            
                                <div class="refresh_random"><div class="rr_image" data-id="<?php print $id ?>"></div></div>
                            </div>
                            <div class="desc"></div> 
                            <script>var data_<?php print $id ?> = <?php print json_encode($random) ?></script>
                            <div class="random-content">
                                <?php
                                gmi('before ' . $rand_id);
                                $this->custom_carousel_ajax($rand_id);
                                gmi('after ' . $rand_id);
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div id="<?php print $id ?>">
                        <?php
                        gmi('before ' . $id);
                        if ($id == 'review_scroll') {
                            print $this->pro_carousel($movie_id);
                        } else if (strstr($id, 'compilation_')) {
                            $this->custom_carousel_ajax($id);
                        }
                        gmi('after ' . $id);
                        ?>
                    </div>
                <?php } ?>
            </section>
            <?php
            $tabs = '';
            $dropdown = '';
            $random = '';
        }
    }

    public function get_reveiw_img($id) {
        $post = $this->cm->get_post_cache($id);
        // Only pro
        $video_link = $this->find_video_link($post->link, $post->id, false);
        $this->theme_review_img($video_link);
    }

    public function theme_review_img($video_link, $post_id = 0) {

        if ($video_link['img']) {
            $video_img = $video_link['img'];
        }

        if ($video_img) {
            //$video_img = 'https://img.filmdemographics.com/' . $video_img;
            ?>
            <img src="/wp-content/themes/custom_twentysixteen/images/placeholder.png" class="poster" loading="lazy" srcset="<?php print $video_img ?>">
            <?php
        } else {
            if ($post_id) {
                ?>
                <img src="/wp-content/themes/custom_twentysixteen/images/placeholder.png" class="poster loadblock" data-func="review_img_block" data-id="<?php print $post_id ?>">
                <?php
            }
        }
    }
}
