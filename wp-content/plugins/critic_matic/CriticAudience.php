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
 * Add tyni editor
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
    //Audience
    public $vote_fields = array(
        'name' => array('title' => 'Critic Name', 'required' => 1),
        'title' => array('title' => 'Review Title', 'required' => 0),
    );
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
            'title' => 'Worthwhile Content'),
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
            'title' => 'Misandry'),
        'affirmative' => array(
            'img' => '05_profit_muhammad',
            'class' => 'AFFIRMATIVE',
            'title' => 'Affirmative Action'),
        'lgbtq' => array(
            'img' => '06_queer',
            'class' => 'LGBTQ',
            'title' => 'LGBTQ rstuvwxyz'),
        'god' => array(
            'img' => '07_cliche_not_brave',
            'class' => 'GOD',
            'title' => 'Anti-God Themes')            
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

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix=DB_PREFIX_WP_AN;
        $this->db = array(
            'meta' => $table_prefix . 'critic_matic_posts_meta',
            'authors_meta' => $table_prefix . 'critic_matic_authors_meta',
            //CA
            'author_key' => $table_prefix . 'meta_critic_author_key',
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


        $rtn = new stdClass();
        $rtn->err = array();
        $rtn->success = false;

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

            if (!$posted->fname) {
                $rtn->err[] = 'Critic Name is required.';
            }


            if (!$posted->ftext) {
                $rtn->err[] = 'Review Text is required.';
            }



            // Check already review

            /* if ($reviewLog->post_count > 0) {
              $rtn->err[] = "You already left a review.";
              } */

            // Check block ip
            // $_SERVER['REMOTE_ADDR']
            /*
              if (count($block_ips)) {
              $rtn->err[] = "Sorry, You can't leave reviews";
              } */

            //Comment to disable spambot check
            if (count($rtn->err)) {
                // die here if we failed any spambot checks
                die(json_encode($rtn));
            }

            //TODO if aprove ip, no moderations. Post type = 1 - publish.
            // passed all spambot checks, continue


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

            /////check critics secret name
            $author_name = trim($posted->fname);
            $aid = 0;

            // Default status publish
            $ss = $this->cm->get_settings();
            $status = $ss['audience_post_status'];

            // Check ip
            $ip = trim($_SERVER['REMOTE_ADDR']);
            $user_agent = trim($_SERVER['HTTP_USER_AGENT']);

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
            $author_type = 0;
            $is_staff = false;
            if ($author_name) {
                // Staff content
                $aid = $this->cm->get_author_id_by_secret_key($author_name, $author_type);
                if ($aid) {
                    $status = 1;
                    $is_staff = true;
                } else {
                    // Audience
                    $aid = $this->get_or_create_audience_author_by_name($author_name, $ip, $user_agent);
                }
            }
            if ($aid) {
                $this->add_author_key($aid);
            }

            // insert a new staff post
            $date = $this->cm->curr_time();
            // Type - manual
            $type = 2;

            $link = '';
            $title = $posted->ftitle;
            if ($is_staff) {
                $content = html_entity_decode($posted->ftext);
            } else {
                $content = $this->wp_kses(html_entity_decode($posted->ftext), $allowed_tags);
            }
            //$content = strip_tags($posted->ftext);
            //$content = str_replace("\n", " <br />", $content);

            if (!$title) {
                $title = $this->cm->crop_text(strip_tags($content), 100);
            }

            $top_movie = $posted->postid;

            $pid = $this->cm->add_post($date, $type, $link, $title, $content, $top_movie, $status);
            if ($pid) {
                //Add post author
                $this->cm->add_post_author($pid, $aid);
                // Add meta
                // Proper review
                $movie_cat = 1;
                //Approve
                $state = 1;
                //Add post movie meta
                $this->cm->add_post_meta($top_movie, $movie_cat, $state, $pid);
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

            $rating_meta = array();


            if (isset($posted->frating_rating)) {
                $rating_meta['wpcr3_review_rating'] = $posted->frating_rating;
            }
            if (isset($posted->frating_hollywood)) {
                $rating_meta['wpcr3_review_rating_hollywood'] = $posted->frating_hollywood;
            }
            if (isset($posted->frating_patriotism)) {
                $rating_meta['wpcr3_review_rating_patriotism'] = $posted->frating_patriotism;
            }
            if (isset($posted->frating_misandry)) {
                $rating_meta['wpcr3_review_rating_misandry'] = $posted->frating_misandry;
            }
            if (isset($posted->frating_affirmative)) {
                $rating_meta['wpcr3_review_rating_affirmative'] = $posted->frating_affirmative;
            }
            if (isset($posted->frating_lgbtq)) {
                $rating_meta['wpcr3_review_rating_lgbtq'] = $posted->frating_lgbtq;
            }
            if (isset($posted->frating_god)) {
                $rating_meta['wpcr3_review_rating_god'] = $posted->frating_god;
            }

            if (isset($posted->review_form_rating_field_vote)) {
                $rating_meta['wpcr3_rating_vote'] = $posted->review_form_rating_field_vote;
            }

            $rating_meta['wpcr3_review_ip'] = array($_SERVER['REMOTE_ADDR']);


            if ($pid) {
                // Add rating
                $options = $this->cm->get_rating_from_postmeta($rating_meta);
                $this->cm->add_rating($pid, $options);

                // Update post rating
                $this->cm->hook_update_post($pid);
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

    public function already_voted($post_id) {
        $unic_id = $this->unic_id();
        //Author is voted?
        if ($this->get_author_post_count_movie($unic_id, $post_id)) {
            return true;
        }

        return false;
    }

    public function get_author_post_count_movie($unic_id, $fid) {
        $query = sprintf("SELECT COUNT(k.id) FROM {$this->db['author_key']} k "
                . "INNER JOIN {$this->db['authors_meta']} am ON am.aid=k.aid "
                . "INNER JOIN {$this->db['meta']} m ON m.cid=am.cid "
                . "WHERE m.fid=%d AND k.name = '%s'", (int) $fid, $this->escape($unic_id));
        $result = $this->db_get_var($query);
        return $result;
    }

    public function add_author_key($aid) {
        $unic_id = $this->unic_id();
        $aid_db = $this->get_author_by_key($unic_id);
        if ($aid != $aid_db) {
            //new key
            $sql = sprintf("INSERT INTO {$this->db['author_key']} (aid, name) VALUES (%d, '%s')", (int) $aid, $this->escape($unic_id));
            $this->db_query($sql);
        }
    }

    public function get_author_by_key($unic_id) {
        $sql = sprintf("SELECT aid FROM {$this->db['author_key']} WHERE name = '%s'", $this->escape($unic_id));
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function unic_id() {
        $unic_id = md5($_SERVER["HTTP_USER_AGENT"] . $_SERVER['REMOTE_ADDR']);
        return $unic_id;
    }

    public function already_voted_msg() {
        ?>
        <div class="succes_send">You have already left your review for this movie.</div>
        <?php
    }

    public function audience_form($post_id) {
        ?>
        <div class="wpcr3_respond_2">
            <div class="wpcr3_div_2">
                <table class="wpcr3_table_2">
                    <tbody>
                        <tr>
                            <td colspan="2">
                                <h3 class="column_header">Submit your review:</h3>
                            </td>
                        </tr>
                        <?php
                        foreach ($this->vote_fields as $key => $value):
                            $title = $value['title'];
                            $required = '';
                            if ($value['required']) {
                                $required = ' wpcr3_required';
                            }
                            ?>
                            <tr class="wpcr3_review_form_text_field">
                                <td>
                                    <label for="wpcr3_f<?php print $key ?>" class="comment-field"><?php print $title ?>: </label>
                                </td>
                                <td>
                                    <input maxlength="150" class="text-input<?php print $required ?>" type="text" id="wpcr3_f<?php print $key ?>" name="wpcr3_f<?php print $key ?>" value="" />
                                </td>
                            </tr>
                        <?php endforeach; ?> 
                        <?php
                        $rating_order = array('rating', 'vote', 'patriotism', 'misandry', 'affirmative', 'lgbtq', 'god');

                        $cfront = new CriticFront($this->cm);

                        $ss = $this->cm->get_settings();
                        $audience_desc = $ss['audience_desc'];

                        foreach ($rating_order as $key) {

                            $vote_data = isset($audience_desc[$key])?$audience_desc[$key]:'';
                            $desc = $cfront->get_nte('i','<div class="nte_cnt_toltip">'.stripslashes($vote_data).'</div>');

                            if ($key == 'vote') {
                                $this->audience_revew_form_boycott($desc);
                            } else {
                                $this->audience_revew_form_item($key,$desc);
                            }
                        }
                        ?>                         
                        <tr id="review-text" class="wpcr3_review_form_review_field_textarea">
                            <td colspan="2">
                                <label for="id_wpcr3_ftext" class="comment-field">Review text: </label>
                                <div id="wp-id_wpcr3_ftext-wrap" class="wp-core-ui wp-editor-wrap tmce-active">                                    
                                    <div id="wp-id_wpcr3_ftext-editor-tools" class="wp-editor-tools hide-if-no-js">
                                        <div class="wp-editor-tabs">
                                            <button type="button" id="id_wpcr3_ftext-tmce" class="wp-switch-editor switch-tmce" data-wp-editor-id="id_wpcr3_ftext">Visual</button>
                                            <button type="button" id="id_wpcr3_ftext-html" class="wp-switch-editor switch-html" data-wp-editor-id="id_wpcr3_ftext">Text</button>
                                        </div>
                                    </div>
                                    <div id="wp-id_wpcr3_ftext-editor-container" class="wp-editor-container">
                                        <div id="qt_id_wpcr3_ftext_toolbar" class="quicktags-toolbar"></div>
                                        <textarea class="wp-editor-area wpcr3_required" rows="20" autocomplete="off" cols="40" name="wpcr3_ftext" id="id_wpcr3_ftext"></textarea>
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

    public function audience_revew_form_boycott($desc) {
        $vote_order = array(2, 3, 1);
        $title = $this->vote_data['vote']['title'];
        $vote_data = $this->vote_data['vote']['options'];
        ?>
        <tr>
            <td id="suggestion" class="wpcr3_review_form_rating_field">
                <label><?php print $title.': '.$desc ?></label>
            </td>
            <td >
                <div class="sug_buttons_wrapper">
                    <select class="wpcr3_vote" name="wpcr3_review_form_rating_field_vote">
                        <?php
                        foreach ($vote_order as $value) {
                            if (isset($vote_data[$value])) {
                                ?>
                                <option class="s<?php print $value ?>" value="<?php print $value ?>"><?php print $vote_data[$value]['title'] ?></option>
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

    public function audience_revew_form_item($key,$desc) {
        $vote_data = $this->vote_data[$key];



        ?>
        <tr class="wpcr3_review_form_rating_field">
            <td>
                <label for="id_wpcr3_frating" class="comment-field"><?php print $vote_data['title'].': '.$desc ?> </label>
            </td>
            <td class="<?php print $vote_data['class'] ?> rating_input">

                <div class="rating_container"><span class="rating_result <?php echo $key ?>">
                    <span style="width: 0;" class="rating_result_total" ></span>
                </span><span class="rating_number rating_num<?php echo $key ?>"><span class="rating_number_rate">0</span>/5</span>
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

    public function get_or_create_audience_author_by_name($author_name, $ip, $user_agent) {
        $author_type = 2;
        $audience_key = md5($ip . $user_agent);
        $authors = $this->cm->get_author_by_name($author_name, true, $author_type, true);
        $aid = 0;
        if (sizeof($authors)) {
            foreach ($authors as $author) {
                $options = unserialize($author->options);
                if (isset($options['audience']) && $options['audience'] == $audience_key) {
                    $aid = $author->id;
                    break;
                }
            }
        }
        if ($aid) {
            return $aid;
        }

        $status = 1;
        $options = array('audience' => $audience_key);

        $aid = $this->cm->create_author_by_name($author_name, $author_type, $status, $options);

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

}
