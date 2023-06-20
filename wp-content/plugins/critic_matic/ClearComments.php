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
    private $settings_def = array();

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

        $this->settings_def = array(
            'all' => '',
            'first' => '',
            'replace' => '',
            'white' => '',
        );
    }

    public function test_submit($text) {
        $result = '';

        $this->update_option('clear_comm_test', base64_encode($text));

        return $result;
    }

    public function validate_keywords($field, $keywords) {
        $new_arr = array();

        $field_types = array('all', 'first', 'white');
        if (in_array($field, $field_types)) {

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
        }
        return $keywords;
    }

    public function decode_field($field = '', $def = '') {
        return base64_decode($this->get_option($field, $def));
    }

    public function validate_content($content) {
        $settings = $this->get_settings();

        $comment_bold = '';
        $content_ret = $content;
        $valid = true;
        $error = array();



        $white_list_data = $settings['white'];

        $data_type = array('all', 'first', 'replace',);
        $keys_found = array();
        $replace_data = array();
        foreach ($data_type as $type) {
            // All
            $spamlist_data = $settings[$type];

            if ($spamlist_data) {
                $spamlist = explode("\n", $spamlist_data);

                if ($type == 'replace') {

                    $spamlist_big = array();
                    foreach ($spamlist as $line) {
                        try {
                            $line_arr = explode(':', $line);
                            $keys = explode(',', $line_arr[0]);
                            $to_replace = $line_arr[1];
                            
                            foreach ($keys as $rkey) {
                                $replace_data[$rkey] = $to_replace;
                                $spamlist_big[]=$rkey;
                            }                            
                            
                        } catch (Exception $exc) {
                            $error[] = array($exc, $line);
                        }
                    }
                   
                    $spamlist = $spamlist_big;
                }

                foreach ($spamlist as $keyword) {
                    if (preg_match_all('|([\p{L}0-9\']*' . $keyword . '[\p{L}0-9\']*)|ui', $content, $match)) {
                        foreach ($match[1] as $value) {
                            $keys_found[$value] = array(
                                'key' => $keyword,
                                'type' => $type,
                            );
                        }
                    }
                }
            }
        }



        // White list
        if ($white_list_data && $keys_found) {
            $white_list = explode("\n", $white_list_data);

            foreach ($keys_found as $phrase => $keyword_data) {
                foreach ($white_list as $white_key) {
                    if (preg_match('|' . $white_key . '|ui', $phrase)) {
                        unset($keys_found[$phrase]);
                    }
                }
            }
        }

        if ($keys_found) {
            foreach ($keys_found as $phrase => $keyword_data) {
                $keyword = $keyword_data['key'];
                $type = $keyword_data['type'];
                if ($keyword) {
                    if (preg_match_all('|[\p{L}0-9\']|ui', $phrase, $match)) {
                        $found = implode('', $match[0]);
                        $len = strlen($found);
                        $keyString = '';



                        if ($type == 'all') {
                            for ($i = 0; $i < $len; $i++) {
                                $keyString .= '*';
                            }
                        } else if ($type == 'first') {
                            foreach ($match[0] as $key => $word) {
                                if ($key == 0 || $key == sizeof($match[0]) - 1) {
                                    $keyString .= $word;
                                } else {
                                    $keyString .= '*';
                                }
                            }
                        } else if ($type == 'replace') {
                            $keyString = isset($replace_data[$phrase])?$replace_data[$phrase]:$phrase;                        
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

        


        $ret = array(
            'keywords' => array_keys($keys_found),
            'comment_bold' => $comment_bold,
            'content' => $content_ret,
            'valid' => $valid,
            'error' => $error,
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

    /*
     * Settings
     */

    public function get_settings($cache = true) {
        if ($cache && $this->settings) {
            return $this->settings;
        }
        // Get settings from options
        $settings = unserialize($this->get_option('clear_comments_settings', false));

        $valid_settings = array();
        if ($settings && sizeof($settings)) {
            foreach ($this->settings_def as $key => $value) {
                if (isset($settings[$key])) {
                    // Decode
                    $valid_settings[$key] = base64_decode($settings[$key]);
                } else {
                    //replace empty settings to default
                    $valid_settings[$key] = $value;
                }
            }
        } else {
            $valid_settings = $this->settings_def;
        }
        $this->settings = $valid_settings;
        return $valid_settings;
    }

    public function update_settings($form) {

        $settings_prev = unserialize($this->get_option('clear_comments_settings', false));

        $ss = $settings_prev;
        foreach ($form as $key => $value) {
            if (isset($this->settings_def[$key])) {
                $value = stripslashes($value);
                $value = $this->validate_keywords($key, $value);
                $ss[$key] = base64_encode($value);
            }
        }

        // Update options        
        $this->update_option('clear_comments_settings', serialize($ss));

        // Update settings
        $this->settings = $this->get_settings();
    }

}
