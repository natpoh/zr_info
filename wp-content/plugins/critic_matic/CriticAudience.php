<?php

/**
 * Review logic. Bazed in: WP Customer Reviews
 *
 * @author brahman
 */
/*
 * TODO
 * +Validate req fields in client
 * Validate already vote in get form
 * +Validata req fields in server
 * +Save data
 * + Add tyni editor
 * +Filter allow tags
 * +Test secret field staff post
 * +Test audience post
 * 
 * Get all votes for movie by staff or audience
 */
class CriticAudience extends AbstractDb {

    public $prefix = 'wpcr3';
    private $cm = '';
    private $p = '';
    public $vote_data = array(
        'vote' => array(
            'title' => 'Boycott Suggestion',
            'options' => array(
                1 => array('title' => 'Pay To Watch', 'img' => "slider_green_pay_drk.png", 'verdict' => 'pay_to_watch'),
                2 => array('title' => 'Skip It', 'img' => 'slider_red_skip_drk.png', 'verdict' => 'skip_it'),
                3 => array('title' => 'Watch If Free', 'img' => 'slider_orange_free.png', 'verdict' => 'watch_if_free')
            )
        ),
        'rating' => array(
            'img' => '01_star',
            'class' => 'WORTHWHILE',
            'title' => 'Overall Rating'),
        'hollywood' => array(
            'img' => '02_poop',
            'class' => 'hollywood',
            'title' => 'Overall Hollywood BS'),
        'patriotism' => array(
            'img' => '03_PTRT',
            'class' => 'PATRIOTISM',
            'title' => 'Neo-Marxism'),
        'misandry' => array(
            'img' => '04_CNT',
            'class' => 'MISANDRY',
            'title' => 'Feminism'),
        'affirmative' => array(
            'img' => '05_profit_muhammad',
            'class' => 'AFFIRMATIVE',
            'title' => 'Affirmative Action'),
        'lgbtq' => array(
            'img' => '06_queer',
            'class' => 'LGBTQ',
            'title' => 'Gay S**t'),
        'god' => array(
            'img' => '07_cliche_not_brave',
            'class' => 'GOD',
            'title' => 'Fedora Tipping')
    );
    public $rating_form = array(
        'r' => 'rating',
        'h' => 'hollywood',
        'p' => 'patriotism',
        'm' => 'misandry',
        'a' => 'affirmative',
        'l' => 'lgbtq',
        'g' => 'god',
        'v' => 'vote',
    );
    public $queue_status = array(
        0 => 'Waiting',
        1 => 'Done',
    );
    public $sort_pages = array('id', 'date', 'critic_name');
    public $audience_post_edit = array(
        0 => 'Never',
        10 => '10 Min.',
        30 => '30 Min.',
        60 => '1 Hour',
    );

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            //CM
            'posts' => $table_prefix . 'critic_matic_posts',
            'meta' => $table_prefix . 'critic_matic_posts_meta',
            'rating' => $table_prefix . 'critic_matic_rating',
            'authors' => $table_prefix . 'critic_matic_authors',
            'authors_meta' => $table_prefix . 'critic_matic_authors_meta',
            'movies_meta' => $table_prefix . 'critic_movies_meta',
            'ip' => $table_prefix . 'critic_matic_ip',
            //CA
            'author_key' => $table_prefix . 'meta_critic_author_key',
            'audience' => $table_prefix . 'critic_matic_audience',
            'audience_rev' => $table_prefix . 'critic_matic_audience_rev',
        );
    }

    function make_p_obj() {
        $this->p = new stdClass();

        foreach ($_GET as $c => $val) {
            if (is_array($val)) {
                $this->p->$c = $val;
            } else {
                $this->p->$c = trim(stripslashes($val));
            }
        }

        foreach ($_POST as $c => $val) {
            if (is_array($val)) {
                $this->p->$c = $val;
            } else {
                $this->p->$c = trim(stripslashes($val));
            }
        }
    }

    public function add_actions() {
        wp_enqueue_script('audience_reviews', CRITIC_MATIC_PLUGIN_URL . 'js/reviews.js', false, CRITIC_MATIC_VERSION);
        wp_enqueue_style('audience_star', CRITIC_MATIC_PLUGIN_URL . 'css/star.css', false, CRITIC_MATIC_VERSION);
        wp_enqueue_style('audience_reviews', CRITIC_MATIC_PLUGIN_URL . 'css/reviews.css', false, CRITIC_MATIC_VERSION);
    }

    public function ajax() {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-type: application/json');
        header('Access-Control-Allow-Origin: *');

        // make P variables object
        $this->make_p_obj();

        /*
          [fname] => ssdaf
          [ftitle] => dsfasdf
          [frating_rating] => 3
          [review_form_rating_field_vote] => 3
          [frating_patriotism] => 3
          [frating_misandry] => 3
          [frating_affirmative] => 3
          [frating_lgbtq] => 3
          [frating_god] => 3
          [ftext] => ssss
          [postid] => 39111
          [checkid] => 39111
          [ajaxAct] => form
          )
         */
        $ip = $this->cm->get_remote_ip();


        $rtn = new stdClass();
        $rtn->err = array();
        $rtn->success = false;
        $rtn->needlogin = false;

        $posted = new stdClass();
        foreach ($this->p as $k => $v) {
            $k = str_replace($this->prefix . '_', '', $k);
            if ($k != 'ftext') {
                $posted->$k = trim(strip_tags($v));
            } else {
                $posted->$k = trim($v);
            }
        }


        if ($posted->ajaxAct === 'form') {
            if ($posted->checkid != $posted->postid) {
                $rtn->err[] = 'You have failed the spambot check. Code 1';
            }


            // Anon review            
            $anon_review = $posted->fname ? false : true;

            $uid = 0;
            if (!$anon_review) {
                // Check login
                $user = wp_get_current_user();
                $uid = $user->exists() ? $user->ID : 0;
            }

            // Unique id post ip and user agent hash
            $unic_id = $this->unic_id();

            $is_edit = false;
            if ($posted->unic_id) {
                $last_unic_id = $posted->unic_id;
                if ($last_unic_id != $unic_id) {
                    $rtn->err[] = 'Edit eror. Code 2';
                }
                $is_edit = true;
            }


            //print_r($user);
            /*
             * [data] => stdClass Object
              (
              [ID] => 42
              [user_login] => sergemel
              [user_pass] => $P$BUHDkAfHMZqfRP5Qg.UPjEfvC8G7eS1
              [user_nicename] => sergemel
              [user_email] => stfuhollywood.com@gmail.com
              [user_url] =>
              [user_registered] => 2018-12-13 20:10:05
              [user_activation_key] =>
              [user_status] => 0
              [display_name] => sergemel sergemel
              )

             */


            if ($uid) {
                $posted->fname = $user->data->display_name;
                $posted->femail = $user->data->user_email;
            }



            if (!$posted->ftext) {
                $rtn->err[] = 'Review Text is required.';
            } else {
                $min_len = 50;
                $clear_text = $author_name = trim(preg_replace("/[^A-Za-z0-9 ]/", '', strip_tags($posted->ftext)));
                if (strlen($clear_text) < $min_len) {
                    $rtn->err[] = 'Review Text is too small.';
                }
            }



            $author_name = '';
            if ($posted->fname) {
                $author_name = trim(preg_replace("/[^A-Za-z0-9 ]/", '', $posted->fname));
                if (strlen($author_name) > 50) {
                    $author_name = substr($author_name, 0, 50);
                }
            }

            if (!$author_name && !$is_edit) {
                if ($anon_review) {
                    $author_name = 'Anon';
                } else {
                    $rtn->err[] = 'Critic Name is required.';
                }
            }

            $pass = '';
            if (!$anon_review) {

                if (!$posted->femail && !$is_edit) {
                    $rtn->err[] = 'Password is required.';
                }

                if ($posted->femail) {
                    $pass = trim($posted->femail);
                }
                // Allow login user
                if (!$uid && $author_name && $pass) {

                    if (class_exists('GuestLogin')) {
                        $gl = new GuestLogin();

                        $login_exist = $gl->loginExist($author_name);
                        if ($login_exist) {
                            // Check pass
                            if ($gl->getCheckUserPass($pass, $login_exist->user_pass, $login_exist->ID)) {
                                // Password correct
                                $uid = $login_exist->ID;
                            } else {
                                // Password incorrect
                                $rtn->err[] = 'Password incorrect. This name "' . $author_name . '" already used. Enter another name or type correct password.';
                            }
                        } else {
                            // Create new user
                            $uid = $gl->create_new_user($author_name, $pass);
                        }

                        if ($uid) {
                            // Login         
                            // Set user data to comment cookeis
                            $comment = new stdClass();
                            $comment->comment_author = $author_name;
                            $comment->comment_author_pass = base64_encode($pass);
                            $this->wp_set_comment_cookies($comment, $user);
                            $gl->wp_login_user($uid);
                            $rtn->needlogin = true;
                        }
                    }
                }
            }


            //Comment to disable spambot check
            if (count($rtn->err)) {
                // die here if we failed any spambot checks
                die(json_encode($rtn));
            }

            // insert a new staff post
            $date = $this->cm->curr_time();

            $content = html_entity_decode($posted->ftext);
            $title = $posted->ftitle;
            $top_movie = $posted->postid;
            if (!$title) {
                $title = $this->cm->crop_text(strip_tags($content), 30);
            }

            // Calculate rating

            $total_rating = 0;
            $total_rating_count = 0;

            $posted->frating_hollywood = 0;

            if (isset($posted->frating_patriotism)) {
                $total_rating += $posted->frating_patriotism;
                $total_rating_count++;
            }
            if (isset($posted->frating_misandry)) {
                $total_rating += $posted->frating_misandry;
                $total_rating_count++;
            }
            if (isset($posted->frating_affirmative)) {
                $total_rating += $posted->frating_affirmative;
                $total_rating_count++;
            }
            if (isset($posted->frating_lgbtq)) {
                $total_rating += $posted->frating_lgbtq;
                $total_rating_count++;
            }
            if (isset($posted->frating_god)) {
                $total_rating += $posted->frating_god;
                $total_rating_count++;
            }
            if ($total_rating && $total_rating_count) {
                $posted->frating_hollywood = $total_rating / $total_rating_count;
                $posted->frating_hollywood = ceil(($posted->frating_hollywood) / 0.5) * 0.5;
            }

            $add_arr = array(
                'date' => $date,
                'status' => 0,
                'top_movie' => (int) $top_movie,
                'rating' => (int) $posted->frating_rating,
                'hollywood' => (int) $posted->frating_hollywood,
                'patriotism' => (int) $posted->frating_patriotism,
                'misandry' => (int) $posted->frating_misandry,
                'affirmative' => (int) $posted->frating_affirmative,
                'lgbtq' => (int) $posted->frating_lgbtq,
                'god' => (int) $posted->frating_god,
                'vote' => (int) $posted->review_form_rating_field_vote,
                'ip' => $ip,
                'critic_name' => $author_name,
                'wp_uid' => (int) $uid,
                'unic_id' => $unic_id,
                'title' => $title,
                'content' => $content,
            );

            if ($is_edit) {
                $this->update_audience($add_arr);
            } else {
                // Add to temp db
                $this->add_audience($add_arr);
                // Update data
                $this->run_cron_hook();
            }
        } else if ($posted->ajaxAct === 'editor') {
            ob_start();
            $quicktags_settings = array('buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,close');
            wp_editor('', 'id_wpcr3_ftext', array('textarea_name' => 'wpcr3_ftext', 'media_buttons' => false, 'tinymce' => true, 'quicktags' => $quicktags_settings));
            $review_field = ob_get_clean();
            $rtn->editor = $review_field;
        }

        $rtn->success = true;
        die(json_encode($rtn));
    }

    public function add_audience($arr = array()) {

        $fields = array('date', 'status', 'top_movie', 'rating', 'hollywood', 'patriotism', 'misandry', 'affirmative', 'lgbtq', 'god', 'vote', 'ip', 'critic_name', 'unic_id', 'title', 'content', 'wp_uid');

        $data = array();
        foreach ($fields as $key) {
            if (isset($arr[$key])) {
                $data[$key] = $arr[$key];
            }
        }

        $id = $this->db_insert($data, $this->db['audience']);
        return $id;
    }

    public function run_cron_hook() {
        // Run cron webhook
        $ss = $this->cm->get_settings();
        if ($ss['audience_cron_path']) {
            $cp = $this->cm->get_cp();
            $cp->send_curl_no_responce($ss['audience_cron_path']);
        }
    }

    public function update_audience($arr) {
        // Already vote
        $a_voted = $this->already_voted($arr['top_movie']);
        if ($a_voted['ret'] == 2) {
            // Is edit
            $au_data = (array) $a_voted['au_data'];

            $data_upd = array(
                'date' => $this->curr_time(),
                'rating' => $this->get_rating_by_audata('rating', $arr, $au_data),
                'hollywood' => $this->get_rating_by_audata('hollywood', $arr, $au_data),
                'patriotism' => $this->get_rating_by_audata('patriotism', $arr, $au_data),
                'misandry' => $this->get_rating_by_audata('misandry', $arr, $au_data),
                'affirmative' => $this->get_rating_by_audata('affirmative', $arr, $au_data),
                'lgbtq' => $this->get_rating_by_audata('lgbtq', $arr, $au_data),
                'god' => $this->get_rating_by_audata('god', $arr, $au_data),
                'vote' => $this->get_rating_by_audata('vote', $arr, $au_data),
                'title' => $arr['title'],
                'content' => $arr['content']
            );

            // Get post audience
            if ($au_data['pid']) {
                // Need add revision? Add.
                $this->add_revision($au_data, $data_upd, $au_data['pid']);
                // Update post
                $data = array(
                    'title' => $arr['title'],
                    'content' => $arr['content'],
                );
                $this->cm->update_post_fields($au_data['pid'], $data);

                // Update rating
                $rating = array(
                    'r' => $this->get_rating_by_audata('rating', $arr, $au_data),
                    'h' => $this->get_rating_by_audata('hollywood', $arr, $au_data),
                    'p' => $this->get_rating_by_audata('patriotism', $arr, $au_data),
                    'm' => $this->get_rating_by_audata('misandry', $arr, $au_data),
                    'a' => $this->get_rating_by_audata('affirmative', $arr, $au_data),
                    'l' => $this->get_rating_by_audata('lgbtq', $arr, $au_data),
                    'g' => $this->get_rating_by_audata('god', $arr, $au_data),
                    'v' => $this->get_rating_by_audata('vote', $arr, $au_data),
                );
                $this->cm->update_post_rating($au_data['pid'], $rating);
            } else if ($au_data['id']) {
                // If not post get temp
                // Update temp
                $this->db_update($data_upd, $this->db['audience'], $au_data['id']);

                $this->run_cron_hook();
            }
        }
    }

    private function add_revision($au_data, $data_upd, $cid) {
        $add = false;
        $data_to_add = array();
        foreach ($data_upd as $key => $value) {
            if ($au_data[$key]) {
                $data_to_add[$key] = $au_data[$key];
            }
            if ($key == 'date') {
                continue;
            }
            if ($value != $au_data[$key]) {
                $add = true;
            }
        }

        if ($add) {
            $data_to_add['cid'] = $cid;
            $this->db_insert($data_to_add, $this->db['audience_rev']);
        }
    }

    public function get_rating_by_audata($name, $arr, $au_arr) {
        $arr_val = $arr[$name] ? $arr[$name] : $au_arr[$name];
        return $arr_val;
    }

    public function run_cron($count = 100, $debug = false, $force = false) {
        $cron_option = 'audience_cron_last_run';
        $last_run = $this->get_option($cron_option, 0);
        $currtime = $this->curr_time();
        $max_wait = $last_run + 5 * 60; // 5 min

        if ($currtime > $max_wait || $force) {
            // Set curr time to option
            $this->update_option($cron_option, $currtime);

            // Add queue posts to critics
            // 1. Get posts
            $status = 0;
            $queue = $this->get_queue($status, 0, $count);
            if ($debug) {
                print_r($queue);
            }
            if ($queue) {
                foreach ($queue as $item) {
                    // 2. Pubish posts
                    $this->publish_audience($item, $debug);
                }
            }
            // Remove last run time
            $this->update_option($cron_option, 0);
        } else {
            if ($debug) {
                print "Cron already run: $currtime < $max_wait";
            }
        }
    }

    public function publish_audience($item, $debug = false) {
        /*
          [0] => stdClass Object
          (
          [id] => 2
          [date] => 1651751484
          [status] => 0
          [top_movie] => 66498
          [rating] => 3
          [hollywood] => 1
          [patriotism] => 0
          [misandry] => 4
          [affirmative] => 0
          [lgbtq] => 3
          [god] => 0
          [vote] => 3
          [ip] => 127.0.0.1
          [critic_name] => Как и исторические предшественники, украинские нацисты собираются вести войну на уничтожение. Но как только такие деятели попадают в пл
          [unic_id] => a8d66a5289df9c243d24a6ec6a3aded0
          [title] => sdf
          [content] => Как и исторические предшественники, украинские нацисты собираются вести войну на уничтожение. Но как только такие деятели попадают в плен, они сразу начинают рассказывать о том, что они повара, водители, воевать не хотели, хотели просто подзаработать денег и т.д.
          )
         */
        // return;
        $author_name = $item->critic_name;
        $ip = $item->ip;
        $unic_id = $item->unic_id;
        $content = $item->content;
        $date = $item->date;
        $title = $item->title;
        $top_movie = $item->top_movie;

        $wp_uid = $item->wp_uid;

        // Default status publish
        $ss = $this->cm->get_settings();
        $status = $ss['audience_post_status'];

        // Check ip
        $ip_item = $this->cm->get_ip($ip);
        if ($ip_item) {
            $ip_type = $ip_item->type;
            if ($ip_type == 3) {
                //Black list -> Trash
                $status = 2;
            } else if ($ip_type == 2) {
                //Gray list -> Draft
                $status = 0;
            } else if ($ip_type == 1) {
                //White list -> Publish
                $status = 1;
            }
        }

        // Staff        
        $aid = 0;
        $author_type = 0;
        $is_staff = false;
        $new_author = false;
        if ($author_name) {
            // Staff content
            /* $aid = $this->cm->get_author_id_by_secret_key($author_name, $author_type);
              if ($aid) {
              $status = 1;
              $is_staff = true;
              } else { */
            // Audience
            $aid = $this->get_author_audience($author_name, $unic_id, $wp_uid);

            if (!$aid) {
                // Get remote aid for a new author
                $new_author = true;
                $author_status = 1;
                $author_type = 2;
                $options = array('audience' => $unic_id);
                $aid = $this->cm->create_author_by_name($author_name, $author_type, $author_status, $options, $wp_uid);
            }
            /* } */
        }

        if ($aid) {
            // Loacal, not sync
            $this->add_author_key($aid);
        }

        $allowed_tags = array(
            'br' => array(),
            'i' => array(),
            'em' => array(),
            'strong' => array(),
            'b' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'p' => array(),
            'blockquote' => array(),
            'del' => array(),
            'div' => array(),
        );

        if (!$is_staff) {
            $content = $this->wp_kses($content, $allowed_tags);
        }

        // Type - manual
        $type = 2;
        $link = '';

        $pid = $this->cm->add_post($date, $type, $link, $title, $content, $top_movie, $status, 0, 0, true);

        if ($pid) {
            // Add post author
            $this->cm->add_post_author($pid, $aid);
            // Add meta
            // Proper review
            $movie_cat = 1;
            // Approve
            $state = 1;
            // Add post movie meta
            $update_top_movie = false;
            $this->cm->add_post_meta($top_movie, $movie_cat, $state, $pid, 0, $update_top_movie);
        }

        if ($pid) {
            // Add rating
            $options = $this->cm->get_rating_array($item);

            $this->cm->add_rating($pid, $options);

            // Change queue status
            $this->update_queue_status($item->id, 1);

            // Update post rating
            $this->cm->hook_update_post($pid);

            // Reset cron
            $this->cm->critic_delta_cron();
        }
    }

    public function rating_images($type, $rating, $subrating = 0) {

        if ($subrating == 0) {
            $rating = (int) round($rating, 0);
        }
        if ($rating > 5)
            $rating = 5;


        $image_path = '';
        $count = '';
        $title = '';
        $bg = 20;

        if ($type == 'vote') {
            if (isset($this->vote_data['vote']['options'][$rating]['img'])) {
                $desc = $this->vote_data['vote']['options'][$rating]['title'];
                $verdict = $this->vote_data['vote']['options'][$rating]['verdict'];


                $title = $this->vote_data[$type]['title'];


                $image_path = '<div class="rating_inner_row"><span class="rating_title">' . $title . '</span><span title="' . $desc . '" class="rating_result ' . $verdict . '"><span class="verdict_text">' . $desc . '</span></span></div>';
            }
        } else {
            if (isset($this->vote_data[$type]['img'])) {

                $count = $rating * 20;
                $title = $this->vote_data[$type]['title'];
                if ($rating) {
                    $bg = 100 / $rating;
                }
                $desc = $rating . '/5';
                $verdict = $type;

                $image_path = '<div class="rating_inner_row"><span class="rating_title">' . $title . '</span><span class="rating_result ' . $verdict . '"><span style="width: ' . $count . '%;   background-size: ' . $bg . '%;" class="rating_result_total" title="' . $desc . '"></span></span></div>';
            }
        }




        return $image_path;
    }

    function audience_form_code($post_id) {
        ?>
        <div id="audience_form" class="not_load wpcr3_respond_1" data-value="<?php print $post_id ?>" data-postid="<?php print $post_id ?>"></div>       
        <?php
    }

    /*
     * return: 
     * 0 - no vote
     * 1 - voted
     * 2 - can edit
     */

    public function already_voted($post_id) {
        $unic_id = $this->unic_id();
        $voted = false;
        $queue_id = 0;
        $cid = 0;

        // Queue user
        $user = wp_get_current_user();
        $wp_uid = $user->exists() ? $user->ID : 0;

        // Queue vote
        if ($wp_uid) {
            $queue_id = $this->get_author_post_queue_by_wpuid($wp_uid, $post_id);
        } else {
            $queue_id = $this->get_author_post_queue($unic_id, $post_id);
        }

        if ($queue_id) {
            $voted = true;
            $post_queue = $this->get_post_queue($queue_id);
            // Post exist?
            if ($post_queue->status == 1) {
                $author_name = $post_queue->critic_name;

                // Author is voted?
                $cid = $this->get_author_post_id_movie($author_name, $post_id);
            }
        }

        $ret = 0;
        $au_data = new stdClass();
        $au_data->status = 0;
        if ($voted) {
            $ret = 1;

            // User allow edit post
            $ss = $this->cm->get_settings();
            if ($ss['audience_post_edit']) {
                $time_to_edit = $ss['audience_post_edit'];
                $date_add = 0;

                if ($cid) {
                    $post = $this->cm->get_post($cid);

                    if ($post) {
                        $date_add = $post->date_add;
                        $au_data = $this->get_audata_post($post);
                        $au_data->unic_id = $unic_id;
                        if ($post->status == 1) {
                            $au_data->status = 1;
                        }
                    }
                } else {
                    $date_add = $post_queue->date;
                    $au_data = $post_queue;
                }
                if ($date_add) {
                    $time = $this->curr_time();
                    if ($time < ($date_add + ($time_to_edit * 60))) {
                        // Edit mode
                        $ret = 2;
                        // Get search post date
                        if ($au_data->status == 1 && $cid) {
                            $cs = $this->cm->get_cs();
                            $search_add = $cs->get_critic_last_upd($cid);
                            if (!$search_add || $search_add != $date_add) {
                                $au_data->status = 0;
                            }
                        }
                    }
                }
            }
        }

        return array('ret' => $ret, 'au_data' => $au_data);
    }

    private function get_audata_post($post) {

        $au_data = new stdClass();
        $au_data->pid = $post->id;
        $au_data->status = $post->status;
        $au_data->title = $post->title;
        $au_data->content = $post->content;

        // [r] => 2 [h] => 1 [p] => 1 [m] => 1 [a] => 1 [l] => 1 [g] => 1 [v] => 2 [ip] => 127.0.0.1 
        $rating = $this->cm->get_post_rating($post->id);
        $au_data->rating = $rating['r'];
        $au_data->hollywood = $rating['h'];
        $au_data->patriotism = $rating['p'];
        $au_data->misandry = $rating['m'];
        $au_data->affirmative = $rating['a'];
        $au_data->lgbtq = $rating['l'];
        $au_data->god = $rating['g'];
        $au_data->vote = $rating['v'];

        return $au_data;
    }

    public function get_queue($status = -1, $page = 1, $per_page = 20, $orderby = '', $order = 'ASC') {
        $page -= 1;
        $start = $page * $this->perpage;


        // Custom status
        $status_query = "";
        if ($status != -1) {
            $status_query = " AND status = " . (int) $status;
        }


        //Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY id DESC";
        }

        $limit = '';
        if ($per_page > 0) {
            $limit = " LIMIT $start, " . $per_page;
        }


        $sql = "SELECT * FROM {$this->db['audience']} WHERE id>0 " . $status_query . $and_orderby . $limit;

        $result = $this->db_results($sql);

        return $result;
    }

    public function update_queue_status($id = 0, $status = 1) {
        $sql = sprintf("UPDATE {$this->db['audience']} SET status='%d' WHERE id=%d", $status, $id);
        $this->db_query($sql);
    }

    public function get_queue_status($status) {
        return isset($this->queue_status[$status]) ? $this->queue_status[$status] : 'None';
    }

    public function get_queue_states() {

        $count = $this->get_queue_count();
        $states = array();
        foreach ($this->queue_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_queue_count($key));
        }
        return $states;
    }

    public function get_queue_count($status = -1) {

        $status_query = '';
        if ($status != -1) {
            $status_query = " AND status = " . (int) $status;
        }

        $query = "SELECT COUNT(id) FROM {$this->db['audience']} WHERE id>0 " . $status_query;

        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_author_post_id_movie($author_name, $fid) {
        $query = sprintf("SELECT am.cid FROM {$this->db['authors']} a "
                . "INNER JOIN {$this->db['authors_meta']} am ON am.aid=a.id "
                . "INNER JOIN {$this->db['meta']} m ON m.cid=am.cid "
                . "WHERE m.fid=%d AND a.name = '%s'", (int) $fid, $this->escape($author_name));
        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_post_queue($id) {
        $query = sprintf("SELECT * FROM {$this->db['audience']} WHERE id=%d", (int) $id);
        $result = $this->db_fetch_row($query);
        return $result;
    }

    public function get_author_post_queue($unic_id, $fid) {
        $query = sprintf("SELECT id FROM {$this->db['audience']} WHERE top_movie=%d AND unic_id = '%s'", (int) $fid, $this->escape($unic_id));
        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_author_post_queue_by_wpuid($wp_uid, $fid) {
        $query = sprintf("SELECT id FROM {$this->db['audience']} WHERE top_movie=%d AND wp_uid = '%d'", (int) $fid, $wp_uid);
        $result = $this->db_get_var($query);
        return $result;
    }

    public function add_author_key($aid) {
        $unic_id = $this->unic_id();
        $aid_db = $this->get_author_by_key($unic_id);
        if ($aid != $aid_db) {
            //new key
            $data = array(
                'aid' => $aid,
                'name' => $unic_id
            );

            $id = $this->cm->sync_insert_data($data, $this->db['author_key'], $this->cm->sync_client, $this->cm->sync_data);
        }
    }

    public function get_author_by_key($unic_id) {
        $sql = sprintf("SELECT aid FROM {$this->db['author_key']} WHERE name = '%s'", $this->escape($unic_id));
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function unic_id() {
        $ip = $this->cm->get_remote_ip();
        $unic_id = md5($_SERVER["HTTP_USER_AGENT"] . $ip);
        return $unic_id;
    }

    public function already_voted_msg() {
        ?>
        <div class="succes_send">You have already left your review for this movie.</div>
        <?php
    }

    public function audience_form($post_id, $au_data = array()) {

        if ($post_id) {
            header('Access-Control-Allow-Origin: *');
        }


        $submit_text = "Submit your review:";
        if ($au_data) {
            $submit_text = "Edit your review:";
        }

        $ma = $this->cm->get_ma();
        $movie = $ma->get_post($post_id);
        $post_link = $ma->get_post_link($movie);

        // Audience desc
        $cfront = new CriticFront($this->cm);
        $ss = $this->cm->get_settings();
        $audience_desc = $ss['audience_desc'];

        $user_identity = '';
        $commenter = array();

        $comment_author = '';
        $comment_author_pass = '';
        // Check user login
        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();

            $user_identity = $user->exists() ? $user->display_name : '';
            // print_r($user_identity);
            $commenter = $this->wp_get_current_commenter();
            if (isset($commenter['comment_author'])) {
                $comment_author = $commenter['comment_author'];
            }
            if (isset($commenter['comment_author_pass'])) {
                $comment_author_pass = base64_decode($commenter['comment_author_pass']);
            }
        }

        $anon = ' anon';
        $checked = ' checked="checked"';
        $required = 0;
        if ($user_identity || $commenter['comment_author']) {
            $anon = '';
            $checked = '';
            $required = 1;
        }
        ?>
        <div class="wpcr3_respond_2">
            <div class="wpcr3_div_2">
                <table class="wpcr3_table_2<?php print $anon ?>" id="audience_respond">
                    <tbody>
                        <tr>
                            <td colspan="2">
                                <h3 class="column_header"><?php print $submit_text ?></h3>
                            </td>
                        </tr>                        
                        <?php
                        if ($au_data) {
                            if ($au_data->status != 1) {
                                ?>
                            <p class="redtext">Your review is awaiting an anti-troll check.</p>
                        <?php } ?>
                        <input id="unic_id" type="hidden" name="unic_id" value="<?php print $au_data->unic_id ?>" />
                    <?php } ?>
                    <tr class="msg-holder"><td colspan="2"><div class="msg-data"></div></td></tr>
                    <?php
                    /*
                     * Array ( [comment_author] => [comment_author_email] => [comment_author_url] => )
                     * print_r($commenter);
                     */

                    if ($user_identity) {
                        $user_profile = get_author_posts_url($user->ID, $user->user_nicename);
                        ?><tr><td colspan="2"><?php
                                        $logged_in_as = '<p class="logged-in-as">' .
                                                sprintf(__('You are logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Sign out of this account">Sign out?</a>'), $user_profile, $user_identity, wp_logout_url($post_link)) . '</p>';
                                        print apply_filters('comment_form_logged_in', $logged_in_as, $commenter, $user_identity);
                                        do_action('comment_form_logged_in_after', $commenter, $user_identity);
                                        ?></td></tr><?php
                    } else {
                        /* if (!$au_data) {
                          ?>
                          <tr><td colspan="2">
                          <div class="big_checkbox">
                          <input type="checkbox"<?php print $checked ?> name="wpcr3_fanon_review" value="on" class="review_type" id="anon_review">
                          <label for="anon_review">Send review anonymously</label>
                          </div>
                          </td></tr>
                          <?php
                          } */
                    }
                    //do_action('comment_form', $post_id); 

                    $vote_fields = array(
                        'name' => array('title' => 'Critic Name', 'required' => 0, 'class' => ''),
                        'email' => array('title' => 'Password', 'required' => $required, 'class' => ' noanon'),
                        'title' => array('title' => 'Review Title', 'required' => 0, 'class' => ''),
                    );

                    foreach ($vote_fields as $key => $value):
                        if ($au_data) {
                            if ($key == 'name' || $key == 'email') {
                                continue;
                            }
                        }
                        if ($user_identity) {
                            // No name data for users
                            if ($key == 'name' || $key == 'email') {
                                continue;
                            }
                        }
                        $title = $value['title'];
                        $required = '';
                        if ($value['required']) {
                            $required = ' wpcr3_required';
                        }

                        $desc = '';
                        $vote_data = isset($audience_desc[$key]) ? $audience_desc[$key] : '';
                        if ($vote_data) {
                            $desc = $cfront->get_nte('i', '<div class="nte_cnt_toltip">' . stripslashes($vote_data) . '</div>');
                        }
                        ?>
                        <tr class="wpcr3_review_form_text_field<?php print $value['class'] ?>">
                            <td>
                                <label for="wpcr3_f<?php print $key ?>" class="comment-field"><span class="rtitle"><?php print $title ?></span>: <?php print $desc ?></label>
                            </td>
                            <td>
                                <input maxlength="150" class="text-input<?php print $required ?>" type="text" id="wpcr3_f<?php print $key ?>" name="wpcr3_f<?php print $key ?>" value="<?php
                                if ($au_data) {
                                    if ($key == 'title') {
                                        print htmlspecialchars($au_data->title);
                                    }
                                }
                                if ($key == 'name' && $comment_author) {
                                    print htmlspecialchars($comment_author);
                                } else if ($key == 'email' && $comment_author_pass) {
                                    print htmlspecialchars($comment_author_pass);
                                }
                                ?>"<?php
                                       if ($key == 'name') {
                                           print ' placeholder="Anon"';
                                       }
                                       ?> />
                            </td>
                        </tr>
                    <?php endforeach; ?> 
                    <?php
                    $rating_order = array('rating', 'vote', 'patriotism', 'misandry', 'affirmative', 'lgbtq', 'god');

                    foreach ($rating_order as $key) {

                        $vote_data = isset($audience_desc[$key]) ? $audience_desc[$key] : '';
                        $desc = $cfront->get_nte('i', '<div class="nte_cnt_toltip">' . stripslashes($vote_data) . '</div>');

                        if ($key == 'vote') {
                            $rating_val = 3;
                            if ($au_data) {
                                if ($au_data->$key) {
                                    $rating_val = $au_data->$key;
                                }
                            }
                            $this->audience_revew_form_boycott($desc, $rating_val);
                        } else {
                            $rating_val = 0;
                            if ($au_data) {
                                if ($au_data->$key) {
                                    $rating_val = $au_data->$key;
                                }
                            }
                            $this->audience_revew_form_item($key, $desc, $rating_val);
                        }
                    }
                    ?>                         
                    <tr id="review-text" class="wpcr3_review_form_review_field_textarea">
                        <td colspan="2">
                            <label for="id_wpcr3_ftext" class="comment-field"><span class="rtitle">Review text</span>: </label>
                            <div id="wp-id_wpcr3_ftext-wrap" class="wp-core-ui wp-editor-wrap tmce-active">                                    
                                <div id="wp-id_wpcr3_ftext-editor-tools" class="wp-editor-tools hide-if-no-js">
                                    <div class="wp-editor-tabs">
                                        <button type="button" id="id_wpcr3_ftext-tmce" class="wp-switch-editor switch-tmce" data-wp-editor-id="id_wpcr3_ftext">Visual</button>
                                        <button type="button" id="id_wpcr3_ftext-html" class="wp-switch-editor switch-html" data-wp-editor-id="id_wpcr3_ftext">Text</button>
                                    </div>
                                </div>
                                <div id="wp-id_wpcr3_ftext-editor-container" class="wp-editor-container">
                                    <div id="qt_id_wpcr3_ftext_toolbar" class="quicktags-toolbar"></div>
                                    <textarea class="wp-editor-area wpcr3_required" rows="20" autocomplete="off" cols="40" name="wpcr3_ftext" id="id_wpcr3_ftext"><?php
                                        if ($au_data) {
                                            print htmlspecialchars($au_data->content);
                                        }
                                        ?></textarea>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="wpcr3_check_confirm">
                            <div class="wpcr3_clear"></div>
                            <input type="hidden" name="wpcr3_postid" value="<?php print $post_id ?>" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div class="wpcr3_button_1 wpcr3_submit_btn" href="#">Submit</div>                     
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>  
        <div class="wpcr3_dotline"></div>
        <?php
    }

    public function audience_revew_form_boycott($desc, $rating_val = 3) {
        $vote_order = array(2, 3, 1);
        $title = $this->vote_data['vote']['title'];
        $vote_data = $this->vote_data['vote']['options'];
        ?>
        <tr>
            <td id="suggestion" class="wpcr3_review_form_rating_field">
                <label><?php print $title . ': ' . $desc ?></label>
            </td>
            <td >
                <div class="sug_buttons_wrapper">
                    <select class="wpcr3_vote" name="wpcr3_review_form_rating_field_vote">
                        <?php
                        foreach ($vote_order as $value) {
                            if (isset($vote_data[$value])) {
                                $selected = '';
                                if ($rating_val == $value) {
                                    $selected = ' selected';
                                }
                                ?>
                                <option class="s<?php print $value ?><?php print $selected ?>" value="<?php print $value ?>"><?php print $vote_data[$value]['title'] ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                </div>
            </td>
        </tr>
        <?php
    }

    public function audience_revew_form_item($key, $desc, $value = 0) {
        $vote_data = $this->vote_data[$key];

        // width: 60%; background-size: 33.3333%;
        $span_style = 'width: 0;';
        if ($value) {
            $width = $value * 20;
            $sizes = array(0, 100, 50, 33.3333, 25, 20);
            $span_style = "width: " . $width . "%; background-size: " . $sizes[$value] . "%;";
        }
        ?>
        <tr class="wpcr3_review_form_rating_field">
            <td>
                <label for="id_wpcr3_frating" class="comment-field"><span class="rtitle"><?php print $vote_data['title'] . '</span>: ' . $desc ?> </label>
            </td>
            <td class="<?php print $vote_data['class'] ?> rating_input">

                <div class="rating_container"><span class="rating_result <?php echo $key ?>">
                        <span style="<?php print $span_style ?>" class="rating_result_total" ></span>
                    </span><span class="rating_number rating_num<?php echo $key ?>"><span class="rating_number_rate number_rate_<?php print $value ?>"><?php print $value ?></span></span>
                    <input style="display:none;" type="hidden" class="wpcr3_frating" id="id_wpcr3_f<?php print $key ?>" name="wpcr3_frating_<?php print $key ?>" />
                </div></td>
        </tr>
        <?php
    }

    public function edit_post_rating($rating_full) {
        ?>
        <table class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>
                    <th><?php print __('Name') ?></th>                
                    <th colspan="6"><?php print __('Value') ?></th>    
                </tr>
            </thead>
            <tbody><?php
                foreach ($rating_full as $key => $value) {
                    if (!isset($this->rating_form[$key])) {
                        continue;
                    }
                    $name = $this->rating_form[$key];

                    if (!isset($this->vote_data[$name])) {
                        continue;
                    }
                    $title = $this->vote_data[$name]['title'];
                    $keys = array(0 => 'None', 1 => '1 star', 2 => '2 stars', 3 => '3 stars', 4 => '4 stars', 5 => '5 stars');
                    $colspan = 1;
                    if ($name == 'vote') {
                        $colspan = 2;
                        $keys = array();
                        foreach ($this->vote_data[$name]['options'] as $k => $v) {
                            $keys[$k] = $v['title'];
                        }
                    }
                    ?>
                    <tr>
                        <td><?php print $title ?></td>
                        <?php foreach ($keys as $item => $title) { ?>
                            <td colspan="<?php print $colspan ?>">
                                <?php
                                $selected = ($value == $item) ? 'checked' : '';
                                ?>
                                <input name="<?php print 'rating_' . $key ?>" type="radio" value="<?php print $item ?>" <?php print $selected ?> > <?php print $title ?>                               
                            </td>
                        <?php } ?>                  
                    </tr>
                    <?php
                }
                ?>
            </tbody>       
        </table>       
        <br />
        <?php
    }

    public function get_author_audience($author_name, $unic_id, $wp_uid = 0) {
        $author_type = 2;

        if ($wp_uid) {

            $author = $this->cm->get_author_by_wp_uid($wp_uid, true);
            if ($author) {
                $aid = $author->id;
            } else {
                // Check old authors
                $authors = $this->cm->get_author_by_name($author_name, true, $author_type, true, 0);
                if (sizeof($authors)) {
                    foreach ($authors as $author) {
                        $options = unserialize($author->options);
                        if (isset($options['audience']) && $options['audience'] == $unic_id) {
                            $this->cm->update_author_wp_uid($author->id, $wp_uid);
                            $aid = $author->id;
                            break;
                        }
                    }
                }
            }
        } else {

            $authors = $this->cm->get_author_by_name($author_name, true, $author_type, true);
            $aid = 0;
            if (sizeof($authors)) {
                foreach ($authors as $author) {
                    $options = unserialize($author->options);
                    if (isset($options['audience']) && $options['audience'] == $unic_id) {
                        $aid = $author->id;
                        break;
                    }
                }
            }
        }
        return $aid;
    }

    private function wp_kses($string, $allowed_html, $allowed_protocols = array()) {

        $string = $this->wp_kses_no_null($string, array('slash_zero' => 'keep'));
        $string = $this->wp_kses_normalize_entities($string);

        return $this->wp_kses_split($string, $allowed_html, $allowed_protocols);
    }

    function wp_kses_no_null($string, $options = null) {
        if (!isset($options['slash_zero'])) {
            $options = array('slash_zero' => 'remove');
        }

        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $string);
        if ('remove' === $options['slash_zero']) {
            $string = preg_replace('/\\\\+0+/', '', $string);
        }

        return $string;
    }

    function wp_kses_normalize_entities($string, $context = 'html') {
        // Disarm all entities by converting & to &amp;
        $string = str_replace('&', '&amp;', $string);

        // Change back the allowed entities in our list of allowed entities.
        if ('xml' === $context) {
            $string = preg_replace_callback('/&amp;([A-Za-z]{2,8}[0-9]{0,2});/', 'wp_kses_xml_named_entities', $string);
        } else {
            $string = preg_replace_callback('/&amp;([A-Za-z]{2,8}[0-9]{0,2});/', 'wp_kses_named_entities', $string);
        }
        $string = preg_replace_callback('/&amp;#(0*[0-9]{1,7});/', 'wp_kses_normalize_entities2', $string);
        $string = preg_replace_callback('/&amp;#[Xx](0*[0-9A-Fa-f]{1,6});/', 'wp_kses_normalize_entities3', $string);

        return $string;
    }

    function wp_kses_split($string, $allowed_html, $allowed_protocols) {
        global $pass_allowed_html, $pass_allowed_protocols;

        $pass_allowed_html = $allowed_html;
        $pass_allowed_protocols = $allowed_protocols;

        return preg_replace_callback('%(<!--.*?(-->|$))|(<[^>]*(>|$)|>)%', '_wp_kses_split_callback', $string);
    }

    function wp_get_current_commenter() {
        // Cookies should already be sanitized.

        $comment_author = '';
        if (isset($_COOKIE['comment_author_' . COOKIEHASH])) {
            $comment_author = $_COOKIE['comment_author_' . COOKIEHASH];
        }

        $comment_author_pass = '';
        if (isset($_COOKIE['comment_author_pass_' . COOKIEHASH])) {
            $comment_author_pass = $_COOKIE['comment_author_pass_' . COOKIEHASH];
        }
        return compact('comment_author', 'comment_author_pass');
    }

    function wp_set_comment_cookies($comment, $user) {
        // If the user already exists, or the user opted out of cookies, don't set cookies.
        if ($user->exists()) {
            return;
        }
        /**
         * Filters the lifetime of the comment cookie in seconds.
         *
         * @since 2.8.0
         *
         * @param int $seconds Comment cookie lifetime. Default 30000000.
         */
        $comment_cookie_lifetime = time() + apply_filters('comment_cookie_lifetime', 30000000);

        $secure = ( 'https' === parse_url(home_url(), PHP_URL_SCHEME) );

        setcookie('comment_author_' . COOKIEHASH, $comment->comment_author, $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure);
        setcookie('comment_author_pass_' . COOKIEHASH, $comment->comment_author_pass, $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure);
    }

}
