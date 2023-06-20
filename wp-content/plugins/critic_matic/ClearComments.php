<?php

/*
 * TODO
 * 1. Add admin pages
 * 2. Add hook to reviews
 * 
 */

/**
 * Description of ClearComments
 *
 * @author brahman
 */
class ClearComments extends AbstractDB {

    public $tabs = array(
        'home' => 'Overview',
        'settings' => 'Settings',
        'test' => 'Test',
        'revisions' => 'Revisions',
    );
    public $cc_type = array(
        0 => 'Review',
        1 => 'Comment',
    );
    
    public $cc_ftype = array(
        0 => 'Title',
        1 => 'Content',
    );
    private $db;
    private $spamlist_data = '';
    private $white_list_data = '';

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            //CM
            'posts' => $table_prefix . 'critic_matic_posts',
            // CA
            'audience_rev' => $table_prefix . 'critic_matic_audience_rev',
            'log' => 'log_clear_comments',
        );
    }

    public function test_submit($text) {
        $result = '';

        $this->update_option('clear_comm_test', base64_encode($text));

        return $result;
    }

    public function options_submit($keys, $white) {
        $result = '';

        $keywords = base64_encode($this->validate_keywords($keys));
        $this->update_option('clear_comm_keywords', $keywords);
        $result = __('Success', 'clear-comments');


        if (isset($white)) {
            $keywords_w = base64_encode($this->validate_keywords($white));
            $this->update_option('clear_comm_keywords_white', $keywords_w);
            $result = __('Success', 'clear-comments');
        }

        return $result;
    }

    public function validate_keywords($keywords) {
        $new_arr = array();

        // Only words and numbers separated by space or comma
        if (preg_match_all('/(?:[ ,]*)([\p{L}0-9\']+)(?:[ ,]*)/ui', $keywords, $match)) {
            foreach ($match[1] as $key) {
                $new_arr[$key] = $key;
            }
        }

        if (sizeof($new_arr) > 0) {
            ksort($new_arr);
            $keywords = implode("\n", $new_arr);
        }
        return $keywords;
    }

    public function decode_field($field = '', $def = '') {
        return base64_decode($this->get_option($field, $def));
    }

    public function validate_content($content) {

        $comment_bold = '';
        $content_ret = $content;
        $valid = true;

        $replace_simbol = '*';

        if (!$this->spamlist_data) {
            $this->spamlist_data = $this->decode_field('clear_comm_keywords');
        }

        if ($this->spamlist_data) {
            $spamlist = explode("\n", $this->spamlist_data);


            $keys_found = array();
            foreach ($spamlist as $keyword) {
                if (preg_match_all('|([\p{L}0-9\']*' . $keyword . '[\p{L}0-9\']*)|ui', $content, $match)) {
                    foreach ($match[1] as $value) {
                        $keys_found[$value] = $keyword;
                    }
                }
            }

            if (!$this->white_list_data) {
                $this->white_list_data = $this->decode_field('clear_comm_keywords_white');
            }

            if ($this->white_list_data && $keys_found) {
                $white_list = explode("\n", $this->white_list_data);

                foreach ($keys_found as $phrase => $keyword) {
                    foreach ($white_list as $white_key) {
                        if (preg_match('|' . $white_key . '|ui', $phrase)) {
                            unset($keys_found[$phrase]);
                        }
                    }
                }
            }

            if ($keys_found) {
                foreach ($keys_found as $phrase => $keyword) {
                    if ($keyword) {
                        if (preg_match_all('|[\p{L}0-9\']|ui', $phrase, $match)) {
                            $len = sizeof($match[0]);
                            $keyString = '';
                            for ($i = 0; $i < $len; $i++) {
                                $keyString .= $replace_simbol;
                            }
                            $content_ret = preg_replace('/([^\p{L}]+|^)' . $phrase . '([^\p{L}]+|$)/ui', "$1" . $keyString . "$2", $content_ret);
                            if ($valid) {
                                $valid = false;
                            }
                        }
                    }
                }


                $comment_bold = $this->comment_bold($content, $keys_found);
            }
        }



        $ret = array(
            'keywords' => $keys_found,
            'comment_bold' => $comment_bold,
            'content' => $content_ret,
            'valid' => $valid,
        );
        return $ret;
    }

    public function comment_bold($comment, $keywords) {
        $comment_bold = $comment;
        if (sizeof($keywords) > 0) {
            foreach ($keywords as $key => $value) {
                $comment_bold = preg_replace('/([^\p{L}]+|^)' . $key . '([^\p{L}]+|$)/ui', "$1<b>" . $key . "</b>$2", $comment_bold);
            }
        }
        return $comment_bold;
    }

    public function check_post($post) {
        $fields = array(
            0 => 'title',
            1 => 'content',
        );
        $date = $this->curr_time();

        $update_post = array();
        foreach ($fields as $key => $field) {
            $content = $post->$field;
            $clear_data = $this->validate_content($content);
            /*
              $ret = array(
              'keywords' => $keys_found,
              'comment_bold' => $comment_bold,
              'content' => $content_ret,
              'valid' => $valid,
              );
             */

            if ($clear_data['valid'] == false) {
                $content_ret = $clear_data['content'];
                $update_post[$field] = $content_ret;
                // Update field
                $data = array(
                    'ftype' => $key,
                    'cid' => $post->id,
                    'date' => $date,
                    'content' => $content,
                    'content_clear' => $content_ret,
                );

                $this->db_insert($data, $this->db['log']);
            }
        }
        // Update post
        if ($update_post) {
            $this->sync_update_data($update_post, $post->id, $this->db['posts'], $this->cm->sync_data);
        }
    }

    /*
     * Log
     */

    public function get_log($page = 1, $per_page = 20, $orderby = '', $order = 'ASC') {
        $page -= 1;
        $start = $page * $this->perpage;

        //Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->cm->sort_pages)) {
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


        $sql = "SELECT * FROM {$this->db['log']} WHERE id>0 " . $and_orderby . $limit;

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_log_count() {
        $sql = "SELECT COUNT(*) FROM {$this->db['log']}";
        return $this->db_get_var($sql);
    }

    /*
     * Revisions
     */

    public function get_revisions($page = 1, $per_page = 20, $orderby = '', $order = 'ASC') {
        $page -= 1;
        $start = $page * $this->perpage;

        //Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->cm->sort_pages)) {
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


        $sql = "SELECT * FROM {$this->db['audience_rev']} WHERE id>0 " . $and_orderby . $limit;

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_revisions_count() {
        $sql = "SELECT COUNT(*) FROM {$this->db['audience_rev']}";
        return $this->db_get_var($sql);
    }

}
