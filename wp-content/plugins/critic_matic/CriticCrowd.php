<?php

/*
 * TODO
 * get data from crowd db
 * validate data
 * public critic or moderate
 */

/**
 * Description of CriticCrowd
 *
 * @author brahman
 */
class CriticCrowd extends AbstractDB {

    private $cm;
    private $db;
    public $critic_status = array(
        0 => 'New',
        1 => 'Waiting',
        2 => 'Done'
    );

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            'posts' => $table_prefix . 'critic_matic_posts',
            'meta' => $table_prefix . 'critic_matic_posts_meta',
            'critic_crowd' => 'data_critic_crowd',
            'movie_imdb' => 'data_movie_imdb',
            'transcriptions' => $table_prefix . 'critic_transcritpions',
        );
    }

    public function run_cron($count = 100, $debug = false) {
        // 1. Get new crowd and create posts
        $this->get_new_crowd($count, $debug);

        // 2. Calculate rating
        $this->calculate_posts($count, $debug);
    }

    private function get_new_crowd($count = 100, $debug = false) {
        $sql = sprintf("SELECT * FROM {$this->db['critic_crowd']} WHERE status=0 AND critic_status=0 ORDER BY id ASC LIMIT %d", $count);
        $results = $this->db_results($sql);
        if ($debug) {
            print_r($results);
        }

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
                            $msg = "Info. Critic already exist. Update critic\n";
                            $data = $this->update_post($post_exist, $item, $debug);
                            $this->update_crowd($id, $data);
                        } else {
                            $msg = "Error $id. Wrong post exist\n";
                        }
                    } else {
                        $msg = "Error $id. Link post not exist\n";
                    }
                } else {
                    if (!$post_exist) {
                        // Add a new critic
                        $msg = "Info $id. Add a new critic\n";
                        $data = $this->add_post($item, $debug);
                        $this->update_crowd($id, $data);
                    } else {
                        $msg = "Error $id. Wrong critic id\n";
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

        if ($results) {
            foreach ($results as $item) {
                $msg = '';
                $id = $item->id;
                $cid = $item->review_id;
                // Post in index?
                $in_index = $cs->critic_in_index($cid);
                $msg = "Post $cid index:" . ($in_index ? "true" : "false") . "\n";
                if ($debug) {
                    print $msg;
                }
                if (!$in_index) {
                    continue;
                }

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
                        $msg = "Info $cid. No ts status\n";
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
                $movie = $this->get_movie_by_id($movie_id, true);

                $bulk = true;
                $ids = array($cid);
                $cs->update_movie($movie, $debug, $bulk, $ids);

                // Update crowd                
                $critic_status = 2;
                $data = array('critic_status' => $critic_status);

                //Get meta
                $movie_exist = $this->cm->get_movies_data($cid, $movie_id);
                if ($debug) {
                    print_r($movie_exist);
                }
                if ($movie_exist) {
                    $movie_exist_meta = $movie_exist[0];
                    $data['weight'] = $movie_exist_meta->rating;
                    // Update post status
                    $data['status'] = 1;
                    // Update meta state to Approved
                    $meta_data = array('state' => 1);
                    $this->sync_update_data($meta_data, $movie_exist_meta->id, $this->db['meta'], $this->cm->sync_data, 5);
                    $msg = "Info: Add meta $cid\n";
                } else {
                    // Can not find meta
                    $data['status'] = 2;
                    $msg = "Error: Can not find meta $cid\n";
                }
                if ($debug) {
                    print $msg;
                }
                $this->update_crowd($item->id, $data);
            }
        }
    }

    public function update_crowd($id = 0, $data) {
        $this->sync_update_data($data, $id, $this->db['critic_crowd'], $this->cm->sync_data, 10);
    }

    private function add_post($crowd_item, $debug = false) {
        // TODO Validate bad words
        $data = array();
        $curr_time = $this->curr_time();
        $date = $curr_time;
        $ret = 0;
        $msg = '';
        $link = $crowd_item->link;
        $id = $crowd_item->id;
        $content = '';
        $title = $crowd_item->title;
        $author_name = $crowd_item->critic_name;
        $author_id = $crowd_item->critic_id;
        $view_type = 0;

        if (!$author_name) {
            $msg = "Error $id. The author name is empty\n";
            if ($debug) {
                print $msg;
            }
            $data['status'] = 2;
            return $data;
        }

        // Is youtube
        $youtube = false;
        if (strstr($link, 'https://www.youtube.com/watch?v=')) {
            $youtube = true;
            $view_type = 1;
        }

        $cp = $this->cm->get_cp();

        if ($youtube) {
            // Get youtube data
            $result = $cp->yt_video_data($link);
            if ($result && $result->description) {
                $date = strtotime($result->publishedAt);
                $content = str_replace("\n", '<br />', $result->description);
            }
        } else {
            ///get main data
            $result = $cp->clear_read($link);
            if ($result) {
                $content = $result['content'];
            }
            if (!$content) {
                $msg = "Error $id. Can not get the data from URL\n";
                if ($debug) {
                    print $msg;
                }
                $data['status'] = 2;
                return $data;
            }
        }

        $link_hash = $this->link_hash($link);

        // Type manual
        $type = 2;
        // Status publish
        $post_status = 1;


        $content = $this->cm->clear_utf8($content);
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

        if ($debug){
            print_r($post_data);
        }
        
        $post_id = $this->sync_insert_data($post_data, $this->db['posts'], $this->sync_client, $this->sync_data);


        if ($post_id > 0) {
            $ret = $post_id;
            $msg .= "Info $id: Add post $post_id\n";
            if ($debug) {
                print_r($post_data);
            }

            //Add author meta
            if (!$author_id) {
                // Status: 0 => 'Draft'
                $author_status = 0;
                // Type: 1 => 'Critic'
                $author_type = 1;
                $author_id = $this->cm->get_or_create_author_by_name($author_name, $author_type, $author_status);
            }
            $this->cm->add_post_author($post_id, $author_id);

            $msg .= "Info $id: Add post author $author_id\n";

            // Success
            $data['critic_status'] = 1;
            $data['review_id'] = $post_id;
            $data['critic_id'] = $author_id;
        } else {
            // Error
            $data['status'] = 2;
            $msg .= "Error $id: Can not add the post\n";
        }
        if ($debug && $msg) {
            print $msg;
        }

        return $data;
    }

    private function update_post($post_exist, $crowd_item, $debug = false) {
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
            if ($debug) {
                $msg = "Error $id. The post already exist\n";
            }
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
                $msg = "Info $id. Publish post $cid\n";
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
        if ($debug) {
            if ($msg) {
                print $msg . "\n";
            }
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

}
