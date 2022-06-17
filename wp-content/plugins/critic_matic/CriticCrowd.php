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

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            'posts' => $table_prefix . 'critic_matic_posts',
            'critic_crowd' => 'data_critic_crowd',
        );
    }

    public function run_cron($count = 100, $debug = false) {
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

    public function update_crowd($id = 0, $data) {
        $this->sync_update_data($data, $id, $this->db['critic_crowd'], $this->cm->sync_data, 10);
    }

    private function add_post($crowd_item, $debug = false) {
        // TODO Validate bad words
        $data = array();
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
        $date = $this->curr_time();

        $date_add = $date;
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
            $data['crowd_status'] = 1;
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

        // 2. Gem post movie meta
        $movies_meta = $this->cm->get_movies_data($cid);
        $movie_exist = false;
        if ($movies_meta) {
            foreach ($movies_meta as $meta) {
                if ($meta->fid == $movie_id) {
                    $movie_exist = true;
                    break;
                }
            }
        }
        $msg = '';

        if ($post_publish && $movie_exist) {
            // Post pulbish already linked            
            if ($debug) {
                $msg = "Error $id. The post already exist\n";
            }
            $data['status'] = 2;
        } else {
            // Crowd status wait
            $data['crowd_status'] = 1;
            if (!$post_publish) {
                // Need publish post
                $post_data = array(
                    'status' => 1,
                );
                $this->cm->update_post_fields($cid, $post_data);
                $msg = "Info $id. Publish post $cid\n";
            }
            /*if (!$movie_exist) {
                // Need add a new movie to post                
                // Type: 1 => 'Proper Review',
                $type = 1;
                // State: 1 => 'Approved',
                $state = 1;
                // Add meta
                $this->cm->add_post_meta($movie_id, $type, $state, $cid);
                $msg .= "Info. Add movie $movie_id to post $cid\n";
            }*/
        }
        if ($debug) {
            if ($msg) {
                print $msg . "\n";
            }
        }

        return $data;
    }

}
