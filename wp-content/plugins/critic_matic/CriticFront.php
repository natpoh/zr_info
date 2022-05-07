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
    // Movies an
    private $ma = '';
    public $thumb_class;
    private $db = array();

    public function __construct($cm = '', $cs = '', $ce = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->cs = $cs ? $cs : new CriticSearch($this->cm);
        $this->ce = $ce ? $ce : new CriticEmotions($this->cm);
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            //CM
            'posts' => $table_prefix . 'critic_matic_posts',
            'meta' => $table_prefix . 'critic_matic_posts_meta',
            'tags' => $table_prefix . 'critic_matic_tags',
            'tag_meta' => $table_prefix . 'critic_matic_tag_meta',
            'authors' => $table_prefix . 'critic_matic_authors',
            'authors_meta' => $table_prefix . 'critic_matic_authors_meta',
            'movies_meta' => $table_prefix . 'critic_movies_meta',
            //CF
            'feed_meta' => $table_prefix . 'critic_feed_meta',
            // WP
            'wp_posts' => $table_prefix . 'posts',
            'wp_options' => $table_prefix . 'options',
            'wp_postmeta' => $table_prefix . 'postmeta',
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

    /*
     * Critic functions
     */

    public function theme_last_posts($a_type = -1, $limit = 10, $movie_id = 0, $start = 0, $tag_id = 0, $meta_type = array(), $min_rating = 0) {
        $posts = $this->get_last_posts($a_type, $limit, $movie_id, $start, $tag_id, $meta_type, $min_rating);
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
                    $item_theme = $this->cache_get_top_movie_critic($item->id, $item->date_add, $top_movie);
                } else {
                    $item_theme = $this->get_top_movie_critic($item->id, $item->date_add, $top_movie);
                }
                if ($item_theme) {
                    $items[] = $item_theme;
                }
            }
        }
        return $items;
    }

    public function get_last_posts($a_type = -1, $limit = 10, $movie_id = 0, $start = 0, $tag_id = 0, $meta_type = array(), $min_rating = 0) {
        $and_author = '';
        if ($a_type != -1) {
            $and_author = sprintf(' AND a.type = %d', $a_type);
        }

        $movie_inner = '';
        $movie_and = '';
        if ($movie_id > 0 || $meta_type || $min_rating) {
            $movie_inner = " INNER JOIN {$this->db['meta']} m ON m.cid = p.id";
        }
        if ($movie_id > 0) {
            $movie_and = sprintf(" AND m.fid=%d AND m.state!=0", (int) $movie_id);
        } else if ($meta_type || $min_rating) {
            $movie_and = sprintf(" AND m.fid=p.top_movie AND m.state!=0", (int) $movie_id);
        }

        $min_rating_and = '';
        if ($min_rating) {
            $min_rating_and = sprintf(' AND m.rating>=%d', $min_rating);
        }

        $meta_type_and = '';
        if ($meta_type) {
            $meta_type_and = ' AND m.type IN(' . implode(',', $meta_type) . ')';
        }

        // Odrer by rating desc
        $custom_order = '';
        if ($movie_id > 0) {
            $custom_order = ' m.rating DESC, ';
        }

        // Tag logic
        $tag_inner = '';
        $tag_and = '';
        if ($tag_id > 0) {
            $tag_inner = " INNER JOIN {$this->db['tag_meta']} t ON t.cid = a.id";
            $tag_and = sprintf(" AND t.tid=%d", (int) $tag_id);
        }

        $sql = sprintf("SELECT p.id, p.date_add, p.top_movie FROM {$this->db['posts']} p"
                . " INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id"
                . " INNER JOIN {$this->db['authors']} a ON a.id = am.aid" . $movie_inner . $tag_inner
                . " WHERE p.top_movie > 0 AND p.status=1" . $and_author . $movie_and . $tag_and . $min_rating_and . $meta_type_and . " ORDER BY" . $custom_order . " p.date DESC LIMIT %d, %d", (int) $start, (int) $limit);

        $results = $this->db_results($sql);

        return $results;
    }

    public function get_post_count($a_type, $movie_id = 0, $tag_id = 0) {
        $and_author = '';
        if ($a_type != -1) {
            $and_author = sprintf(' AND a.type = %d', $a_type);
        }

        $movie_inner = '';
        $movie_and = '';
        if ($movie_id > 0) {
            $movie_inner = " INNER JOIN {$this->db['meta']} m ON m.cid = p.id";
            $movie_and = sprintf(" AND m.fid=%d AND m.state!=0", (int) $movie_id);
        }

//Tag logic
        $tag_inner = '';
        $tag_and = '';
        if ($tag_id > 0) {
            $tag_inner = " INNER JOIN {$this->db['tag_meta']} t ON t.cid = a.id";
            $tag_and = sprintf(" AND t.tid=%d", (int) $tag_id);
        }

        $sql = "SELECT COUNT(p.id) FROM {$this->db['posts']} p"
                . " INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id"
                . " INNER JOIN {$this->db['authors']} a ON a.id = am.aid" . $movie_inner . $tag_inner
                . " WHERE p.top_movie > 0 AND p.status=1" . $and_author . $movie_and . $tag_and;

        $results = $this->db_get_var($sql);
        return $results;
    }

    public function admin_edit_link($id = 0, $type = 'critic') {
        $link = '';
        if ($this->cm->user_can) {
            if (!class_exists('CriticMaticAdmin')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMaticAdmin.php' );
            }
            $cma = new CriticMaticAdmin($this->cm, array(), array(), array());

            if ($type == 'critic') {
                $link = $cma->theme_post_link($id, 'Edit');
            }
        }
        return $link;
    }

    public function cache_get_top_movie_critic($critic_id, $date_add, $movie_id = 0) {
        $arg = array();
        $arg['critic_id'] = $critic_id;
        $arg['date_add'] = $date_add;
        $arg['movie_id'] = $movie_id;
        $filename = "c-$critic_id-$date_add-$movie_id";
        $str = ThemeCache::cache('get_top_movie_critic_string', false, $filename, 'critics', $this, $arg);
        return unserialize($str);
    }

    public function get_top_movie_critic_string($arg) {
        return serialize($this->get_top_movie_critic($arg['critic_id'], $arg['date_add'], $arg['movie_id']));
    }

    public function get_top_movie_critic($critic_id, $date_add, $movie_id = 0) {
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
          1 => 'Pro',
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
        if (!$top_movie) {
            return '';
        }
        // New logic 26.08.2021. Get movie from an db
        $ma = $this->get_ma();
        // $ma_id = $ma->get_post_id_by_rwt_id($top_movie);
        $ma_id = $top_movie;
        if (!$ma_id) {
            return '';
        }
        $movie = $ma->get_post($ma_id);
        // print_r($movie);

        $poster_link_90 = $this->get_thumb_path_full(90, 120, $ma_id);
        $poster_link_small = $this->get_thumb_path_full(220, 330, $ma_id);
        $poster_link_big = $this->get_thumb_path_full(440, 660, $ma_id);

        // Cast
        $cast_obj = json_decode($movie->actors);
        $cast = $this->get_cast_string($cast_obj, 50);

        // Title
        $title = $movie->title;

        // Post name
        $post_name = $this->get_or_create_ma_post_name($ma_id, $top_movie, $title, $movie->type);
        $slug = $ma->get_post_slug($movie->type);
        $url = '/' . $slug . '/' . $post_name;

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
            'link' => $url,
            'title' => $title,
            'genre' => $movie->genre,
            'release' => $movie->release,
            'cast' => $cast,
            'poster_link_small' => $poster_link_small,
            'poster_link_big' => $poster_link_big,
            'poster_link_90' => $poster_link_90,
            'content_pro' => $content,
            'date' => $critic->date,
            'm_id' => $ma_id,
            'type' => $slug,
        );
        return $ret;
    }

    public function cache_single_critic_content($critic_id, $movie_id = 0, $date_add = 0) {
        $arg = array();
        $arg['critic_id'] = $critic_id;
        $arg['date_add'] = $date_add;
        $arg['movie_id'] = $movie_id;
        $filename = "p-$critic_id-$date_add-$movie_id";
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
        $time_codes = '';
        if ($critic->type == 4) {
            if (strstr($content, '<div class="transcriptions">')) {
                if ($fullsize) {
                    $time_codes = $this->find_transcriptions($top_movie, $critic->id, $content);
                }
                $content = preg_replace('/<div class="transcriptions">.*<\/div>/Us', '', $content);
            }
        }

        // Get meta state
        $meta_state = $this->cm->get_critic_meta_state($critic->id, $top_movie);
        $critic->meta_state = isset($meta_state->state) ? $meta_state->state : 0;
        $critic->meta_type = isset($meta_state->type) ? $meta_state->type : 0;

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
        // Author image
        $author = $this->cm->get_author($critic->aid);
        $author_options = unserialize($author->options);
        $author_img = $author_options['image'];

        if ($author_img) {
            $image = $this->get_local_thumb(100, 100, $author_img);
            $actorsdata = '<div class="a_img_container" style="background: url(' . $image . '); background-size: cover;"></div>';
        } else {
            // Empty image
            $actorsdata = '<div class="a_img_def"></div>';
        }


        // Author name
        $actorstitle = $author->name;
        $actorstitle = $this->pccf_filter($actorstitle);

        // Tags
        $catdata = '';
        $tags = $this->cm->get_author_tags($author->id);
        if (sizeof($tags)) {
            foreach ($tags as $tag) {
                $catdata .= $this->get_tag_link($tag->slug, $tag->name);
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


        // Title
        $title_str = '';
        $title = strip_tags($title);
        $title = $this->pccf_filter($title);

        if ($title != $content && !$stuff) {
            $title_str = '<strong class="review-title">' . $title . '</strong>';
        }

        $info_link = $this->get_info_link($critic->id, $top_movie, $critic->meta_state);
        $review_bottom = '<div class="review_bottom"><div class="r_type">' . $this->cm->get_post_category_name($critic->meta_type) . '</div><div class="r_right"><div class="r_date">' . $critic_addtime . '</div>' . $info_link . '</div></div>';

        // Find video link
        $video_link = $this->find_video_link($permalink, $critic->content);

        if ($fullsize) {

            if ($stuff && $fullsize) {

                $original_link = '<a class="original_link" target="_blank" href="' . $permalink . '">Source Link >></a>';
            } else {
                $original_link = '<a class="original_link" target="_blank" href="' . $permalink . '">Full review >></a>';
            }


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
                $actorsresult = $video_embed . $title_str . $content . $review_bottom . $original_link .
                        '<div class="amsg_aut">' . $actorsdata . '<div class="review_autor_name">' . $actorstitle . '<div class="a_cat">' . $catdata . '</div></div></div>';
            } else {
                $actorsresult = '<div class="full_review_content_block' . $largest . '">' . $video_embed . $title_str . $content . '</div>' . $after_content . $review_bottom . $original_link . '
 <div class="amsg_aut">' . $actorsdata . '<div class="review_autor_name">' . $actorstitle . '<div class="a_cat">' . $catdata . '</div></div></div>';
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
        <div class="amsg_aut">' . $actorsdata . '<div class="review_autor_name">' . $actorstitle . '<div class="a_cat">' . $catdata . '</div>'
                    . '</div>' . $reaction_data . '</div></div>';
        }
        return $actorsresult;
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

            if ($results) {
                if (preg_match_all('/<b>([^<]+)<\/b>/', $results, $match)) {
                    $unique_words = array();
                    foreach ($match[1] as $value) {
                        $unique_words[$value] = $value;
                    }
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
                    }
                }
            }
        }

        ksort($timecodes);

        return $timecodes;
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
        if ($meta_state == 2) {
            $add_class = ' a_state';
            $title = 'This review was attached automatically by our robot';
        }

        $link = '<div data-value="' . $cid . '" data-movie="' . $mid . '" class="a_info' . $add_class . '" title="' . $title . '"></div>';
        return $link;
    }

    public function find_video_link($link, $content = '') {
        $ret = array();
        // https://www.bitchute.com/embed/kntoSwUiKY4T/
        if (preg_match('/bitchute\.com\/(?:embed|video)\/([a-zA-Z0-9\-_]+)/', $link, $match)) {
            if (count($match) > 1) {
                $embed = 'https://www.bitchute.com/embed/' . $match[1];
                $ret['video'] = $this->embed_video($embed);
                $ret['img'] = '';
                $ret['type'] = 'bitchute';
                /* if (preg_match('/https\:\/\/www\.bitchute\.com\/channel\/([^\/]+)\//', $content, $cm)) {
                  $ret['img'] = 'https://static-3.bitchute.com/live/cover_images/' . $cm[1] . '/' . $match[1] . '_320x180.jpg';
                  } */
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
        }

        return $ret;
    }

    public function embed_video($link) {
        $code = '<div class="embed-responsive embed-responsive-16by9">'
                . '<iframe class="embed-responsive-item" src="' . $link . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
                . '</div><br />';
        return $code;
    }

    public function get_audience_templ($critic = array(), $avatars = '', $fullsize = false) {
        if (!$avatars) {
            $avatars = $this->get_avatars();
        }

        $title = $critic->title;

        if (!$title) {
            $title = ' ';
        }
        $content = $critic->content;
        if (!$content) {
            $content = $title;
        }

        $author = $this->cm->get_author($critic->aid);

        // Author name
        $actorstitle = $author->name;

        $actorstitle = $this->pccf_filter($actorstitle);

        // Tags
        $catdata = '';
        $tags = $this->cm->get_author_tags($author->id);
        if (sizeof($tags)) {
            foreach ($tags as $tag) {
                $catdata .= $this->get_tag_link($tag->slug, $tag->name);
            }
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
        $country_img = '';

        $country_data = $this->cm->get_geo_flag_by_ip($ip);
        if ($country_data['path']) {
            $country_name = $country_data['name'];
            $country_img = '<div class="nte cflag" title="' . $country_name . '">
                                                    <div class="btn"><img src="' . $country_data['path'] . '" /></div> 
                                                    <div class="nte_show">
                                                        <div class="nte_in">
                                                            <div class="nte_cnt">
                                                                This review was posted from ' . $country_name . ' or from a VPN in ' . $country_name . '.                                                                
                                                            </div>
                                                        </div>
                                                    </div>
                             </div>';
        }


        if (!$fullsize) {
            $content = $this->format_content($content, 400);
        } else {
            // check links
            $content = $this->replacelink($content);
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

            if ($avatars == 'staff') {
                return '<div class="vote_main">' . $theme_rating . '</div>' . $content . '</div>';
            }

            if ($fullsize) {
                if ($title) {
                    $title = '<strong class="review-title">' . $title . '</strong>';
                }

                $content = '<div class="full_review_content_block">' . $title . '<div class="vote_main">' . $theme_rating . $content . '</div></div>';
            } else {
                $content = '<a class="icntn" href="' . $link . '">
    <div class="vote_main">' . $theme_rating . '<div class="vote_content"><strong>' . $title . '</strong><br>' . $content . "</div>
    </div>
</a>";
            }
        }

        $stars_data = 0;
        if ($rating) {
            $stars_data = $rating['r'];
        }
        $array_avatars = $avatars[$stars_data];

        if (is_array($array_avatars)) {
            $rand_keys = array_rand($array_avatars, 1);
            $avatar_user = $array_avatars[$rand_keys];
        }

        if ($avatar_user) {

            $actorsdata = '<div class="a_img_container_audience" style="background: url(https://' . $_SERVER['HTTP_HOST'] . '/wp-content/uploads/avatars/custom/' . $avatar_user . '); background-size: cover;"></div>';
        } else {
            $actorsdata = '<span></span>';
        }


        // get link
        // $link = $link . '?a=' . $c_pid;


        $review_bottom = '<div class="review_bottom"><div class="r_type"></div><div class="r_right"><div class="r_date">' . $critic_addtime . '</div>' . $country_img . '</div></div>';


        if ($fullsize) {

            $actorsresult = '
' . $content . $review_bottom . '<div class="amsg_aut">' . $actorsdata . '
        <div class="review_autor_name">' . $actorstitle . '<div class="a_cat">' . $catdata . '</div></div>
       
    </div>';
        } else {
            $reaction_data = $this->get_user_reactions($critic->id);

            $actorsresult = '<div class="a_msg">
    <div class="a_msg_i">
        ' . $content . $review_bottom . '<div class="ugol"><div></div></div>
    </div>
        
        <div class="amsg_aut">
            ' . $actorsdata . '
            <div class="review_autor_name">' . $actorstitle . '
                <div class="a_cat">' . $catdata . '</div>
            </div>
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

        $stars = round($stars, 0);
        $stars_data = $stars;

        $stars = $this->rating_images('rating', $stars);


        if ($hollywood) {
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
        $link = 'https://' . $_SERVER['HTTP_HOST'] . '/critics/' . $slug . '/';
        return $link;
    }

    public function get_wprss_feed_item_by_slug($slug) {
        $sql = sprintf("SELECT ID FROM {$this->db['wp_posts']} WHERE post_type='wprss_feed_item' AND post_name='%s'", $this->escape($slug));
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_avatars() {
        $avatars = [];
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/uploads/avatars/custom/';

        $files = scandir($dir);

        foreach ($files as $val) {
            if ($val != '.' && $val != '..') {
                $regv = '#(\d+)\-(\d+)-128\.[jpgn]+#';
                if (preg_match($regv, $val, $mach)) {
                    $avatars[$mach[2]][$mach[1]] = $val;
                }
            }
        }
        return $avatars;
    }

    /*
     * Movies
     */

    public function get_movie($id, $cache = true) {
        // Get from cache
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }

        $sql = sprintf("SELECT ID, post_name, post_title FROM {$this->db['wp_posts']} WHERE ID=%d", (int) $id);
        $result = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }

    public function get_movie_by_slug($slug) {
        $sql = sprintf("SELECT ID, post_title FROM {$this->db['wp_posts']} WHERE post_type='movie' AND post_name='%s'", $this->escape($slug));
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_tvseries_by_slug($slug) {
        $sql = sprintf("SELECT ID, post_title FROM {$this->db['wp_posts']} WHERE post_type='tvseries' AND post_name='%s'", $this->escape($slug));
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_wp_post_name($id) {
        $sql = sprintf("SELECT post_name FROM {$this->db['wp_posts']} WHERE ID=%d", (int) $id);
        $result = $this->db_get_var($sql);
        return $result;
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
        $post_name = $this->get_or_create_ma_post_name($id, $movie->rwt_id, $title, $movie->type);
        $ma = $this->get_ma();
        $slug = $ma->get_post_slug($movie->type);

        $url = '/' . $slug . '/' . $post_name;

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

    public function template_single_movie_small($item) {
        // DEPRECATED UNUSED

        $id = $item->id;
        $title = $item->movie;
        $type = $item->post_type;

        $slug = 'movies';
        if ($type == 'tvseries') {
            $slug = 'tvseries';
        }

        $url = '/' . $slug . '/' . $item->post_name;

        $date = $item->release_date;
        $cast = $item->cast;

        if ($cast) {
            $cast = $this->cm->crop_text($cast, 50);
        }

        if ($date) {
            $date = strtotime($date);
            $date = date('Y', $date);
            if (strstr($title, $date)) {
                $date = '';
            } else {
                $date = ' (' . $date . ')';
            }
        }

        $imgcache = $this->get_poster_tsumb($id, $array_request = array([90, 120]));

        if ($imgcache) {
            $imgsrc = $imgcache[0];
            $img = '<img src="' . $imgsrc . '">';
        }


        $content = '<div class="full_review_movie"><a href="' . $url . '/" class="movie_link" >' . $img . '<div class="movie_link_desc"><span  class="itm_hdr">' . $title . $date . '</span><span>' . $cast . '</span></div></a></div>';

        return $content;
    }

    public function get_movie_front_meta($pid) {
        $fields = array(
            'Release' => '_wpmoly_movie_release_date',
            'Cast' => '_wpmoly_movie_cast',
            'Genre' => '_wpmoly_movie_genres'
        );

        $movie_meta = $this->get_post_meta($pid);

        $ret = array();
        foreach ($fields as $name => $value) {
            if (isset($movie_meta[$value][0])) {
                $ret[$name] = $movie_meta[$value][0];
            } else {
                $ret[$name] = '';
            }
        }
        return $ret;
    }

    public function get_post_meta($pid = 0, $type = '', $single = false) {
        $type_query = '';
        if ($type) {
            $type_query = sprintf(" AND meta_key = '%s'", $type);
        }
        $sql = sprintf("SELECT meta_key, meta_value FROM {$this->db['wp_postmeta']} WHERE post_id = %d" . $type_query, (int) $pid);
        $meta = $this->db_results($sql);
        $ret = array();
        if (sizeof($meta)) {
            foreach ($meta as $item) {
                if ($single) {
                    $ret = $item->meta_value;
                    break;
                }
                $ret[$item->meta_key][] = $item->meta_value;
            }
        }

        return $ret;
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

            // Get in rwt base
            if ($rwt_id) {
                $post_name = $this->get_wp_post_name($rwt_id);
                if ($post_name) {
                    // append rwt post name
                    $ma->add_post_name($id, $post_name);
                }
            }
            // Create it
            if (!$post_name) {
                // Type: Movie, TVseries
                $post_name = $ma->create_post_name($id, $title, $type);
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


        $slug = 'movies';
        if (strtolower($type) == 'tvseries') {
            $slug = 'tvseries';
        }

        $array_type = array('tvseries' => 'TV series');
        $item_type = $array_type[$slug];
        if (!$item_type)
            $item_type = ucfirst($slug);

        $post_name = $item->post_name;
        if (!$post_name) {
            $post_name = $this->get_or_create_ma_post_name($id, $rwt_id, $title, $type);
        }

        // todo get post name
        $url = '/' . $slug . '/' . $post_name;

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
        $content = str_replace('<br>', '\n', $content);
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

        $regex_pattern = "/(youtube.com|youtu.be)\/(watch)?(\?v=)?(\S+)?/";
        $video_count = 0;
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

        $image = '';

        $crop_len = 200;
        if ($fullsize) {
            $crop_len = 800;
            // try to find img
            $regi = '/<img[^>]+src="([^"]+)"/Ui';

            if (preg_match($regi, $content, $mach)) {

                $image = $this->get_local_thumb(640, 0, $mach[1]);

                if ($image) {
                    $image = '<div style="text-align: center;margin: 10px 0;"><img src="' . $image . '"></div>';
                }
            }
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

        $content = $video . $content;
        return $content;
    }

    public function pccf_filter($text) {

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

    public function get_critic_url_by_old_slug($slug) {
        $pid = $this->get_wprss_feed_item_by_slug($slug);
        $cm_id = 0;

        if ($pid) {
            // Get post meta            
            $wprss_item_permalink = trim(get_post_meta($pid, 'wprss_item_permalink', true));
            // Post exist
            $link_hash = '';
            $link = $wprss_item_permalink;
            if ($link) {
                $link_hash = $this->cm->link_hash($link);
                // Check the post already in db
                $post_exist = $this->cm->get_post_by_link_hash($link_hash);
                if ($post_exist) {
                    $cm_id = $post_exist->id;
                }
            }
        }
        return $cm_id;
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

    public function get_thumb_path_full($w, $h, $id) {

        !class_exists('RWTimages') ? include ABSPATH . "analysis/include/rwt_images.php" : '';

        $time = RWTimages::get_last_time($id);
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

    public function get_scroll($type = '') {
        static $last_posts_id = '';
        static $last_movies_id = '';

        $content = '';

        if ($type == 'video_scroll' || $type == 'tv_scroll') {
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
            }
        }
        if ($type == 'review_scroll' || $type == 'stuff_scroll' || $type == 'audience_scroll') {
            if (!$last_posts_id) {
                $last_posts_id = $this->cm->get_posts_last_update();
            }

            if ($type == 'review_scroll') {
                if ($this->cache_results) {
                    $filename = "scroll-rev-$last_posts_id";
                    $content = ThemeCache::cache('get_review_scroll', false, $filename, 'def', $this);
                } else {
                    $content = $this->get_review_scroll();
                }
            } else if ($type == 'stuff_scroll') {
                if ($this->cache_results) {
                    $filename = "scroll-stf-$last_posts_id";
                    $content = ThemeCache::cache('get_stuff_scroll', false, $filename, 'def', $this);
                } else {
                    $content = $this->get_stuff_scroll();
                }
            } else if ($type == 'audience_scroll') {
                if ($this->cache_results) {
                    $filename = "scroll-aud-$last_posts_id";
                    $content = ThemeCache::cache('get_audience_scroll', false, $filename, 'def', $this);
                } else {
                    $content = $this->get_audience_scroll();
                }
            }
        }

        return $content;
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
        require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/ajax/tv_scroll.php');
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function get_review_scroll($movie_id = 0) {
        $content = $this->get_review_scroll_data($movie_id);
        return $content;
    }

    public function get_stuff_scroll($movie_id = 0) {
        $content = $this->get_stuff_scroll_data($movie_id);
        return $content;
    }

    public function get_audience_scroll($movie_id = 0) {
        $content = $this->get_audience_scroll_data($movie_id);
        return $content;
    }

    public function get_review_scroll_data($movie_id = 0) {
        $a_type = 1;
        $limit = 10;
        $start = 0;
        $tag_id = 0;
        global $site_url;
        if (!$site_url)
            $site_url = 'https://' . $_SERVER['HTTP_HOST'] . '/';

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

        $posts = $this->theme_last_posts($a_type, $limit, $movie_id, $start, $tag_id, $meta_type, $min_rating);
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
                // $link = '/critics/group_pro';
                // New api
                $link = '/search/tab_critics/author_pro';

                if ($movie_id) {
                    // Old api
                    /* $ma = $this->get_ma();
                      $ma_id = $movie_id; //$ma->get_post_id_by_rwt_id($movie_id);
                      $movie = $ma->get_post($ma_id);
                      if ($movie) {
                      $slug = $this->get_or_create_ma_post_name($ma_id, $movie->rwt_id, $movie->title, $movie->type);
                      $type_slug = $ma->get_post_slug($movie->type);
                      $link = $site_url . 'critics/group_pro/' . $type_slug . '/' . $slug;
                      } */
                    // New api
                    $link = '/search/tab_critics/author_pro/movie_' . $movie_id;
                }

                $title = 'Load more<br>Pro Reviews';
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
            $content['reaction'] = $this->ce->get_emotions_counts_all($pids);

            // Print json
            return json_encode($content);
        }
        return '';
    }

    public function get_stuff_scroll_data($movie_id = 0) {
        global $site_url;
        if (!$site_url) {
            $site_url = 'https://' . $_SERVER['HTTP_HOST'] . '/';
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
            $content['reaction'] = $this->ce->get_emotions_counts_all($pids);

            // Print json
            return json_encode($content);
        }
        return '';
    }

    public function get_audience_scroll_data($movie_id = 0) {
        global $site_url;
        if (!$site_url)
            $site_url = 'https://' . $_SERVER['HTTP_HOST'] . '/';

        /* Author type
          0 => 'Staff',
          1 => 'Pro',
          2 => 'Audience'
         */

        $a_type = 2;
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
                    // Old api
                    /*
                      $ma = $this->get_ma();
                      $ma_id = $movie_id; //$ma->get_post_id_by_rwt_id($movie_id);
                      $movie = $ma->get_post($ma_id);
                      if ($movie) {
                      $slug = $this->get_or_create_ma_post_name($ma_id, $movie->rwt_id, $movie->title, $movie->type);
                      $type_slug = $ma->get_post_slug($movie->type);
                      $link = $site_url . 'critics/group_audience/' . $type_slug . '/' . $slug;
                      } */
                    // New api
                    $link = '/search/tab_critics/author_audience/movie_' . $movie_id;
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
            $content['reaction'] = $this->ce->get_emotions_counts_all($pids);

            // Print json
            return json_encode($content);
        }
        return '';
    }

    /*
     * External fucntions 
     */

    public function rating_images($type, $rating, $subrating = 0) {
        $ca = $this->get_ca();
        return $ca->rating_images($type, $rating, $subrating);
    }

    public function get_user_reactions($cid) {
        if ($this->enable_reactions) {
            $reaction_data = $this->ce->get_user_reactions($cid);
        } else {
            $reaction_data = '<div class="review_comment_data"></div>';
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

    public function get_poster_tsumb($id, $array_request = array([220, 330], [440, 660]), $image = '', $name = '') {
        // DEPRECATED
        $this->init_thumb_service();

        //        if (!$image && $id) {
        //            $image = CreateTsumbs::get_movie_image($id, 'file');
        //        }

        $array_result = [];


        // Get local custom poster
        $poster = $this->get_post_meta($id, '_thumbnail_id', true);
        $poster_link = $this->get_post_meta($poster, '_wp_attached_file', true);

        if ($poster_link) {
            $abs_poster_link = ABSPATH . 'wp-content/uploads/' . $poster_link;
            foreach ($array_request as $val) {
                $array_result[] = $this->get_local_thumb($val[0], $val[1], $abs_poster_link);
            }
        }
        if ($array_result) {
            return $array_result;
        }

        // Get external poster
        $poster = $this->get_post_meta($id, '_wpmoly_movie_poster', true);
        if ($poster) {
            $ext_poster_link = 'https://www.themoviedb.org/t/p/w1280' . $poster;
            foreach ($array_request as $val) {
                $array_result[] = $this->get_local_thumb($val[0], $val[1], $ext_poster_link);
            }
        }
        if ($array_result) {
            return $array_result;
        }
        // Empty image
        $empty_img = '/wp-content/themes/custom_twentysixteen/images/empty_image.svg';
        foreach ($array_request as $value) {
            $array_result[] = $empty_img;
        }
        return $array_result;
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

}
