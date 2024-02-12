<?php

/**
 *
 * @author brahman
 */
class CriticCrowd extends AbstractDB {

    private $cm;
    private $db;
    public $critic_status = array(
        0 => 'New',
        1 => 'Processed',
        2 => 'Done',
        3 => 'Error'
    );
    public $status = array(
        0 => 'Waiting',
        1 => 'Approved',
        2 => 'Error',
        3 => 'Rejected',
    );
    private $log_type = array(
        0 => 'Info',
        1 => 'Warning',
        2 => 'Error',
    );
    private $log_status = array(
        0 => 'Cron',
        1 => 'Admin',
    );

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            'posts' => $table_prefix . 'critic_matic_posts',
            'meta' => $table_prefix . 'critic_matic_posts_meta',
            'critic_crowd' => 'data_critic_crowd',
            'log' => 'critic_crowd_log',
            'movie_imdb' => 'data_movie_imdb',
            'transcriptions' => $table_prefix . 'critic_transcritpions',
        );
    }

    public function run_cron($count = 100, $debug = false) {


        //Check option auto_publish_crowdsource 
        $auto_publish_crowdsource = $this->get_option('auto_publish_crowdsource');
        if ($debug) {
            print_r(array('auto_publish_crowdsource', $auto_publish_crowdsource ? 'on' : 'off'));
        }
        if ($auto_publish_crowdsource) {

            // Publish new crowd
            $this->publish_new_crowd($count, $debug);

            // Try to parsing error crowd
            $this->renew_error_crowd($count, $debug);
        }
    }

    private function publish_new_crowd($count = 100, $debug = false) {
        $sql = sprintf("SELECT * FROM {$this->db['critic_crowd']} WHERE critic_status=0 ORDER BY id ASC LIMIT %d", $count);
        $results = $this->db_results($sql);
        if ($debug) {
            print_r(array('publish', $results));
        }

        // Log status cron
        $log_status = 0;

        if ($results) {
            foreach ($results as $item) {
                $msg = '';
                $id = $item->id;
                $cid = $item->review_id;
                $link = $item->link;

                // Post exist?
                $link_hash = $this->link_hash($link);
                $post_exist = $this->cm->get_post_by_link_hash($link_hash);
                $movie_id = $item->rwt_id;

                $error = array();

                if ($cid > 0) {
                    // Post exist
                    if ($post_exist) {
                        if ($post_exist->id == $cid) {
                            // Update critic
                            $msg = "Critic already exist. Update critic";
                            $this->log_info($msg, $id, $log_status);
                            $data = $this->update_post($post_exist, $item, $log_status, $debug);
                        } else {
                            $msg = "Wrong post exist " . $post_exist->id . " != $cid";
                            $error[] = $msg;
                        }
                    } else {
                        $msg = "Link post not exist";
                        $error[] = $msg;
                    }
                } else {
                    if (!$post_exist) {
                        // Add a new critic
                        $msg = "Add a new critic";
                        $this->log_info($msg, $id, $log_status);
                        $data = $this->add_post($item, $log_status, $debug);
                        if ($data['review_id']) {
                            $cid = $data['review_id'];
                        } else {
                            $msg = 'No rewiew id';
                            $error[] = $msg;
                        }
                    } else {
                        $msg = "Wrong critic id";
                        $error[] = $msg;
                    }
                }

                if ($error) {
                    foreach ($error as $msg) {
                        if ($debug) {
                            print $msg . "\n";
                        }
                        $this->log_error($msg, $id, $log_status);
                    }

                    $data['critic_status'] = 3;
                    $data['status'] = 2;
                    $this->update_crowd($item->id, $data);
                } else {
                    // Update crowd
                    $data['critic_status'] = 2;
                    $data['status'] = 1;
                    $this->update_crowd($id, $data);

                    //Get meta
                    $movie_exist = $this->cm->get_movies_data($cid, $movie_id);
                    if (!$movie_exist) {
                        // Need add a new movie to post
                        // Type: 1 => 'Proper Review',
                        $type = 1;
                        // State: 1 => 'Approved',
                        $state = 1;
                        // Add meta
                        $this->cm->add_post_meta($movie_id, $type, $state, $cid);
                        $msg = "Add movie $movie_id to post $cid";
                        $this->log_info($msg, $id, $log_status);
                    }
                }
            }
        }
    }

    private function renew_error_crowd($count = 100, $debug = false) {
        $max_error_count = 10;
        $time = $this->curr_time();
        $wait_time = $time - 600;
        $sql = sprintf("SELECT * FROM {$this->db['critic_crowd']} WHERE status=2 AND review_id=0 AND last_update < %d ORDER BY id ASC LIMIT %d", $wait_time, $count);
        $results = $this->db_results($sql);
        if ($debug) {
            print_r(array('renew', $results));
        }
        $log_status = 0;
        if ($results) {
            foreach ($results as $item) {
                // Get last error logs count
                $sql = sprintf("SELECT COUNT(*) FROM {$this->db['log']} WHERE cid=%d AND type=2", $item->id);
                $result = $this->db_get_var($sql);
                $data = array();
                if ($result <= $max_error_count) {
                    // Update critic crowd                    
                    $data['critic_status'] = 0;
                    // New
                    $data['status'] = 0;
                    $msg = "Renew, max error count:" . $result;
                } else {
                    // Rejected
                    $data['status'] = 3;
                    $msg = "Rejected, max error count:" . $result;
                }

                $this->log_info($msg, $item->id, $log_status);
                $this->update_crowd($item->id, $data);
            }
        }
    }

    private function get_new_crowd($count = 100, $debug = false) {
        $sql = sprintf("SELECT * FROM {$this->db['critic_crowd']} WHERE status=0 AND critic_status=0 ORDER BY id ASC LIMIT %d", $count);
        $results = $this->db_results($sql);
        if ($debug) {
            print_r($results);
        }

        // Log status cron
        $log_status = 0;

        if ($results) {
            foreach ($results as $item) {
                $msg = '';
                $id = $item->id;
                $cid = $item->review_id;
                $link = $item->link;

                // Post exist?
                $link_hash = $this->link_hash($link);
                $post_exist = $this->cm->get_post_by_link_hash($link_hash);

                if ($cid > 0) {
                    if ($post_exist) {
                        if ($post_exist->id == $cid) {
                            // Update critic
                            $msg = "Critic already exist. Update critic";
                            $this->log_info($msg, $id, $log_status);

                            $data = $this->update_post($post_exist, $item, $log_status, $debug);
                            $this->update_crowd($id, $data);
                        } else {
                            $msg = "Wrong post exist " . $post_exist->id . " != $cid";
                            $this->log_error($msg, $id, $log_status);
                        }
                    } else {
                        $msg = "Link post not exist";
                        $this->log_error($msg, $id, $log_status);
                    }
                } else {
                    if (!$post_exist) {
                        // Add a new critic
                        $msg = "Add a new critic";
                        $this->log_info($msg, $id, $log_status);

                        $data = $this->add_post($item, $log_status, $debug);
                        $this->update_crowd($id, $data);
                    } else {
                        $msg = "Wrong critic id";
                        $this->log_error($msg, $id, $log_status);

                        $crowd_status = 2;
                        $data = array('status' => $crowd_status);
                        $this->update_crowd($item->id, $data);
                    }
                }
                if ($debug) {
                    if ($msg) {
                        print $msg . "\n";
                    }
                }
            }
        }
    }

    private function calculate_posts($count = 100, $debug = false) {
        $sql = sprintf("SELECT * FROM {$this->db['critic_crowd']} WHERE status=0 AND critic_status=1 ORDER BY id ASC LIMIT %d", $count);
        $results = $this->db_results($sql);
        if ($debug) {
            print_r($results);
        }
        $cs = $this->cm->get_cs();
        $log_status = 0;

        if ($results) {
            foreach ($results as $item) {
                $msg = '';
                $id = $item->id;
                $cid = $item->review_id;

                /*
                  // Post in index?
                  $in_index = $cs->critic_in_index($cid);
                  $msg = "Post $cid index:" . ($in_index ? "true" : "false") . "\n";
                  if ($debug) {
                  print $msg;
                  }
                  if (!$in_index) {
                  continue;
                  }
                 */

                $link = $item->link;
                // Is youtube
                $youtube = false;
                if (strstr($link, 'https://www.youtube.com/watch?v=')) {
                    $youtube = true;
                }

                if ($youtube) {
                    $ts_updated = false;
                    $ts = $this->get_ts_status($cid);
                    if ($debug) {
                        print_r($ts);
                    }
                    if ($ts) {
                        $ts_status = $ts->status;

                        $msg = "Info $cid. Ts status: $ts_status\n";
                        if ($ts_status == 2) {
                            $msg .= "Info $cid. Ts in post\n";
                            $ts_updated = true;
                        } else if ($ts_status == 0) {
                            $msg .= "Info $cid. No ts\n";
                            $ts_updated = true;
                        }
                    } else {
                        $msg = "Info $cid. Waiting ts status\n";
                    }
                    if ($debug) {
                        print $msg;
                    }
                    if (!$ts_updated) {
                        continue;
                    }
                }

                // Calculate rating
                $movie_id = $item->rwt_id;
                // $movie = $this->get_movie_by_id($movie_id, true);

                /*
                  $bulk = true;
                  $ids = array($cid);
                  $cs->update_movie($movie, $debug, $bulk, $ids);
                 */
                // Update crowd                

                $data = array();
                $data['critic_status'] = 2;
                $data['status'] = 2;

                //Get meta
                $movie_exist = $this->cm->get_movies_data($cid, $movie_id);

                if (!$movie_exist) {
                    // Need add a new movie to post
                    // Type: 1 => 'Proper Review',
                    $type = 1;
                    // State: 1 => 'Approved',
                    $state = 1;
                    // Add meta
                    $this->cm->add_post_meta($movie_id, $type, $state, $cid);
                    $msg = "Add movie $movie_id to post $cid";
                    $this->log_info($msg, $id, $log_status);
                }

                $this->update_crowd($item->id, $data);
            }
        }
    }

    private function get_approved_posts($count = 100, $debug = false) {
        $sql = sprintf("SELECT * FROM {$this->db['critic_crowd']} WHERE status=1 AND critic_status !=2 ORDER BY id ASC LIMIT %d", $count);
        $results = $this->db_results($sql);
        if ($debug) {
            print_r($results);
        }
        $log_status = 0;
        if ($results) {
            foreach ($results as $item) {
                $msg = '';
                $id = $item->id;
                $cid = $item->review_id;
                $link = $item->link;
                $movie_id = $item->rwt_id;

                // Post exist?
                $link_hash = $this->link_hash($link);
                $post_exist = $this->cm->get_post_by_link_hash($link_hash);

                if ($post_exist) {
                    // Update exist critic
                    $msg = "Critic already exist. Update critic";
                    $this->log_info($msg, $id, $log_status);

                    // Publish post
                    if ($post_exist->status != 1) {
                        $post_data = array(
                            'status' => 1,
                        );
                        $this->cm->update_post_fields($cid, $post_data);
                        $msg = "Publish post $cid";
                        $this->log_info($msg, $id, $log_status);
                    }

                    // 2. Get post movie meta
                    $movie_exist = $this->cm->get_movies_data($cid, $movie_id);

                    if (!$movie_exist) {
                        // Need add a new movie to post
                        // Type: 1 => 'Proper Review',
                        $type = 1;
                        // State: 1 => 'Approved',
                        $state = 1;
                        // Add meta
                        $this->cm->add_post_meta($movie_id, $type, $state, $cid);
                        $msg = "Add movie $movie_id to post $cid";
                        $this->log_info($msg, $id, $log_status);
                    }
                    $data = array();
                    // Success
                    $data['critic_status'] = 2;
                    $data['review_id'] = $post_exist->id;
                    $this->update_crowd($id, $data);
                } else {
                    // Add a new critic
                    $msg = "Add a new critic";
                    $this->log_info($msg, $id, $log_status);

                    $data = $this->add_post($item, $log_status, $debug);
                    if ($debug) {
                        print_r($data);
                    }
                    // Success
                    if ($data['review_id']) {
                        $post_id = $data['review_id'];
                        $data['critic_status'] = 2;
                        // Type: 1 => 'Proper Review',
                        $type = 1;
                        // State: 1 => 'Approved',
                        $state = 1;
                        // Add meta
                        $this->cm->add_post_meta($movie_id, $type, $state, $post_id);
                        $msg = "Add movie $movie_id to post $post_id";
                        $this->log_info($msg, $id, $log_status);
                    }

                    $this->update_crowd($id, $data);
                }
                if ($debug) {
                    if ($msg) {
                        print $msg . "\n";
                    }
                }
            }
        }
    }

    public function update_crowd($id = 0, $data) {
        $data['last_update'] = $this->curr_time();
        $this->sync_update_data($data, $id, $this->db['critic_crowd'], $this->cm->sync_data, 3);
    }

    private function add_post($crowd_item, $log_status = 0, $debug = false) {
        // TODO Validate bad words
        $data = array();
        $curr_time = $this->curr_time();
        $date = $curr_time;
        $channelId = '';
        $ret = 0;
        $msg = '';
        $link = $crowd_item->link;
        $id = $crowd_item->id;
        $content = $crowd_item->content;
        $title = $crowd_item->title;
        $author_name = $crowd_item->critic_name;
        $author_id = $crowd_item->critic_id;

        if (!$author_name) {
            $msg = "The author name is empty";
            $this->log_error($msg, $id, $log_status);
            if ($debug) {
                print $msg;
            }
            $data['status'] = 2;
            $data['critic_status'] = 3;
            return $data;
        }

        $view_type = $this->cm->get_post_view_type($link);

        $cp = $this->cm->get_cp();

        if ($view_type > 0) {
            // Is youtube
            if ($view_type == 1) {
                $cpyoutube = $cp->get_cpyoutube();
                // Get youtube data
                $result = $cpyoutube->yt_video_data($link);
                if ($result) {
                    $channelId = $result->channelId;
                    if ($result->description) {
                        // $date = strtotime($result->publishedAt);
                        $content = str_replace("\n", '<br />', $result->description);
                    }
                }
            } else {
                // Bichude, odysee
            }
        } else {
            ///get main data
            #$service_url = 'http://148.251.54.53:8110/?p=ds1bfgFe_23_KJDS-F&clear=1&wait=3&url=';
            #$full_url = $service_url .$link;
            #$content = file_get_contents($full_url);
            /*
              $result = $cp->clear_read($link);
              if ($result) {
              $content = $result['content'];
              } */
            if (!$content) {
                $msg = "Sorry, there was an error fetching data from the URL. Please try again or manually submit it.";
                $this->log_error($msg, $id, $log_status);
                if ($debug) {
                    print $msg;
                }
                $data['status'] = 2;
                $data['critic_status'] = 3;
                return $data;
            }
        }

        $link_hash = $this->link_hash($link);

        // Type manual
        $type = 2;
        // Status publish
        $post_status = 1;

        # $content = $this->cm->clear_utf8($content);
        $date_add = $curr_time;
        $post_data = array(
            'date' => $date,
            'date_add' => $date_add,
            'status' => $post_status,
            'type' => $type,
            'blur' => 0,
            'link_hash' => $link_hash,
            'link' => $link,
            'title' => $title,
            'content' => $content,
            'top_movie' => 0,
            'view_type' => $view_type
        );

        if ($debug) {
            print_r($post_data);
        }

        $post_id = $this->sync_insert_data($post_data, $this->db['posts'], $this->sync_client, $this->sync_data);

        if ($post_id > 0) {
            $ret = $post_id;
            $msg = "Add post $post_id";
            $this->log_info($msg, $id, $log_status);
            if ($debug) {
                print $msg;
            }

            if ($debug) {
                print_r($post_data);
            }

            // Add author meta
            if (!$author_id) {
                // Status: 0 => 'Draft'
                $author_status = 0;
                // Type: 1 => 'Critic'
                $author_type = 1;
                $author_id = $this->cm->get_or_create_author_by_name($author_name, $author_type, $author_status);
                if ($channelId) {
                    $author_ob = $this->cm->get_author($author_id);
                    //Options
                    $options = unserialize($author_ob->options);
                    if (!$options['image']) {
                        // Add author avatar
                        $cpyoutube = $cp->get_cpyoutube();
                        $channel_info = $cpyoutube->youtube_get_channel_info($channelId);
                        if ($channel_info->items[0]->snippet->thumbnails->medium->url) {
                            $avatar = $channel_info->items[0]->snippet->thumbnails->medium->url;
                            if ($avatar) {
                                $options['image'] = $avatar;
                                $author_ob->options = $options;
                                // Publish author
                                $author_ob->status = 1;
                                $this->cm->update_author($author_ob);
                            }
                        }
                    }
                }
            }
            $this->cm->add_post_author($post_id, $author_id);

            $msg = "Add post author $author_id";
            $this->log_info($msg, $id, $log_status);
            if ($debug) {
                print $msg;
            }

            // Success
            $data['critic_status'] = 1;
            $data['review_id'] = $post_id;
            $data['critic_id'] = $author_id;
        } else {
            // Error
            $data['status'] = 2;
            $data['critic_status'] = 3;
            $msg = "Can not add the post";
            $this->log_error($msg, $id, $log_status);
            if ($debug) {
                print $msg;
            }
        }

        return $data;
    }

    private function update_post($post_exist, $crowd_item, $log_status = 0, $debug = false) {
        $data = array();

        // 1. Get post status
        $post_publish = false;
        $cid = $post_exist->id;
        $movie_id = $crowd_item->rwt_id;
        $id = $crowd_item->id;

        if ($post_exist->status == 1) {
            $post_publish = true;
        }

        // 2. Get post movie meta
        $movie_exist = $this->cm->get_movies_data($cid, $movie_id);

        $msg = '';

        if ($post_publish && $movie_exist) {
            // Post pulbish already linked            
            $msg = "The post already exist and linked";
            if ($debug) {
                print $msg;
            }
            $this->log_info($msg, $id, $log_status);
            $data['status'] = 2;
        } else {
            // Crowd status wait
            $data['critic_status'] = 1;
            if (!$post_publish) {
                // Need publish post
                $post_data = array(
                    'status' => 1,
                );
                $this->cm->update_post_fields($cid, $post_data);
                $msg = "Publish post $cid";
                $this->log_info($msg, $id, $log_status);
                if ($debug) {
                    print $msg;
                }
            }
            /* if (!$movie_exist) {
              // Need add a new movie to post
              // Type: 1 => 'Proper Review',
              $type = 1;
              // State: 1 => 'Approved',
              $state = 1;
              // Add meta
              $this->cm->add_post_meta($movie_id, $type, $state, $cid);
              $msg .= "Info. Add movie $movie_id to post $cid\n";
              } */
        }
        return $data;
    }

    public function get_movie_by_id($id, $cache = false) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }
        $sql = sprintf("SELECT * FROM {$this->db['movie_imdb']} WHERE id = %d", $id);
        $movie = $this->db_fetch_row($sql);
        $dict[$id] = $movie;

        return $movie;
    }

    private function get_ts_status($pid = 0) {
        $sql = sprintf("SELECT id, status FROM {$this->db['transcriptions']} WHERE pid=%d limit 1", (int) $pid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    /*
     * Log
     * message - string
     * cid - campaign id
     * type:
      0 => 'Info',
      1 => 'Warning',
      2 => 'Error'

      status:
      0 => 'Ignore post',
      1 => 'Add post',
      2 => 'Error',
      3 => 'Auto stop',
      4 => 'Done',
      5 => 'Add URLs',
     */

    public function log($message, $cid = 0, $type = 0, $status = 0) {
        $time = $this->curr_time();
        $this->db_query(sprintf("INSERT INTO {$this->db['log']} (date, cid, type, status, message) VALUES (%d, %d, %d, %d, '%s')", $time, $cid, $type, $status, $this->escape($message)));
    }

    public function get_log($page = 1, $cid = 0, $status = -1, $type = -1, $perpage = 30) {
        $page -= 1;
        $start = $page * $perpage;

        $limit = '';
        if ($perpage > 0) {
            $limit = " LIMIT $start, " . $perpage;
        }
        $and_cid = '';
        if ($cid) {
            $and_cid = sprintf(" AND cid=%d", (int) $cid);
        }

        $and_status = '';
        if ($status != -1) {
            $and_status = sprintf(" AND status=%d", (int) $status);
        }

        $and_type = '';
        if ($type != -1) {
            $and_type = sprintf(" AND type=%d", (int) $type);
        }

        $order = " ORDER BY id DESC";
        $sql = sprintf("SELECT id, date, cid, type, status, message FROM {$this->db['log']} WHERE id>0" . $and_cid . $and_status . $and_type . $order . $limit);

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_log_count($cid = 0, $status = -1, $type = -1) {

        $and_cid = '';
        if ($cid) {
            $and_cid = sprintf(" AND cid=%d", (int) $cid);
        }

        $and_status = '';
        if ($status != -1) {
            $and_status = sprintf(" AND status=%d", (int) $status);
        }

        $and_type = '';
        if ($type != -1) {
            $and_type = sprintf(" AND type=%d", (int) $type);
        }

        $query = "SELECT COUNT(id) FROM {$this->db['log']} WHERE id>0" . $and_cid . $and_status . $and_type;

        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_last_log($cid = 0) {

        $and_cid = '';
        if ($cid > 0) {
            $and_cid = sprintf(' AND cid=%d', $cid);
        }

        $query = "SELECT type, status, message FROM {$this->db['log']} WHERE id>0" . $and_cid . " ORDER BY id DESC";
        $result = $this->db_fetch_row($query);
        $str = '';
        if ($result) {
            $str = $this->get_log_type($result->type) . ': ' . $this->get_log_status($result->status);
            if ($result->message) {
                $str = $str . ' | ' . $result->message;
            }
        }
        return $str;
    }

    /*
      0 => 'Other',
      1 => 'Find URLs',
      3 => 'Parsing',
     */

    public function log_info($message, $cid, $status) {
        $this->log($message, $cid, 0, $status);
    }

    public function log_warn($message, $cid, $status) {
        $this->log($message, $cid, 1, $status);
    }

    public function log_error($message, $cid, $status) {
        $this->log($message, $cid, 2, $status);
    }

    public function get_log_type($type) {
        return isset($this->log_type[$type]) ? $this->log_type[$type] : 'None';
    }

    public function get_log_status($type) {
        return isset($this->log_status[$type]) ? $this->log_status[$type] : 'None';
    }

    public function get_post_log_status($cid = 0) {

        $count = $this->get_log_count($cid);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->log_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_log_count($cid, $key));
        }
        return $states;
    }

    public function get_post_log_types($cid = 0, $status = -1) {

        $count = $this->get_log_count($cid, $status);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->log_type as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_log_count($cid, $status, $key));
        }
        return $states;
    }

    public function clear_all_logs() {
        $sql = "DELETE FROM {$this->db['log']} WHERE id>0";
        $this->db_query($sql);
    }
}
