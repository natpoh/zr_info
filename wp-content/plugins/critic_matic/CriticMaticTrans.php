<?php

/*
 * Critic Matic Transcriptions
 */

class CriticMaticTrans extends AbstractDB {

    private $cm;
    private $cp;
    private $db;
    public $sort_pages = array('add_time', 'id', 'status');
    public $ts = array(0 => 'Empty', 1 => 'Parsed', 2 => 'In post', 10 => 'Waiting');
    public $update_interval = array(1, 7, 30);
    public $err_interval = array(
        7 => 1,
        10 => 7,
        15 => 30,
        20 => 360);

    public function __construct($cm = '') {
        $this->cm = $cm;
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            // CM
            'posts' => $table_prefix . 'critic_matic_posts',
            'authors' => $table_prefix . 'critic_matic_authors',
            'authors_meta' => $table_prefix . 'critic_matic_authors_meta',
            // TS
            'transcriptions' => $table_prefix . 'critic_transcritpions',
        );
    }

    public function get_cp() {
        // Get criti
        if (!$this->cp) {
            //init cp
            if (!class_exists('CriticParser')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
            }
            $this->cp = new CriticParser($this->cm);
        }
        return $this->cp;
    }

    /*
     * Update transcription
     */

    public function update_posts_transcription($count = 10, $debug = false, $force = false) {
        // 1. Get posts with ts status 1
        $ts = $this->get_ts($count);
        if ($debug) {
            print_r($ts);
        }
        // 2. Update posts
        if ($ts) {
            foreach ($ts as $item) {
                $ts_raw = $item->content;
                $transcription = $this->youtube_content_filter($ts_raw);

                // Get post
                $post = $this->cm->get_post($item->pid);
                if ($debug) {
                    print_r($post);
                }
                $post_content = $post->content;
                // Ts not exist:
                if (!strstr($post_content, '<div class="transcriptions">')) {
                    $post_content = str_replace("\n", '<br />', $post_content);
                    $description = '<div class="description">' . $post_content . '</div>';

                    $full_content = $description . $transcription;

                    if ($debug) {
                        print_r($full_content);
                    }

                    // Update post
                    $this->cm->update_post_content($post->id, $full_content);
                }
                // 3. Update ts status
                $new_status = 2;
                $this->update_ts_status($item->id, $new_status);
            }
        }
    }

    private function youtube_content_filter($content) {
        $ret = '';
        if (preg_match_all('/([0-9]+\:[0-9]+\:[0-9\, ]+)-->[^\n]+\n([^\n]+)/s', $content, $match)) {
            for ($i = 0; $i < sizeof($match[1]); $i++) {
                $ret .= '<span data-time="' . $match[1][$i] . '">' . $match[2][$i] . '</span> ';
            }
            $ret = '<div class="transcriptions">' . $ret . '</div>';
        }
        return $ret;
    }

    public function update_youtube_urls($count = 10, $debug = false, $force = false) {
        // 1. Get posts        
        $sql = "SELECT id, link, link_hash, top_movie, status FROM {$this->db['posts']}"
                . " WHERE type=4"
                . " AND view_type=1"
                . " AND `link` LIKE '%//youtube.com%'"
                . " ORDER BY `id` DESC LIMIT " . (int) $count;
        $results = $this->db_results($sql);
        if ($results) {
            if ($debug) {
                print_r($results);
            }
            foreach ($results as $post) {
                $link = $post->link;
                $new_link = str_replace('//youtube.com', '//www.youtube.com', $link);
                $new_hash = $this->link_hash($new_link);
                // 2. Find exists hash
                $old_post = $this->cm->get_post_by_link_hash($new_hash);
                $post_status = $post->status;
                if ($old_post) {
                    if ($debug) {
                        print_r(array($old_post));
                    }
                    if ($post->top_movie > 0) {
                        // Need merge
                        if ($old_post->top_movie > 0) {
                            // Trash post
                            $post_status = 2;
                        } else {
                            // Trash old post
                            $this->cm->trash_post_by_id($old_post->id);
                        }
                    } else {
                        // Trash post
                        $post_status = 2;
                    }
                }
                // Update post
                $data = array(
                    'status' => $post_status,
                    'link_hash' => $new_hash,
                    'link' => $new_link,
                );
                if ($debug) {
                    print_r(array($post->id, $data));
                }
                $this->cm->update_post_fields($post->id, $data);
            }
        }
    }

    /*
     * Find transcription
     */

    public function find_transcriptions_youtube($count = 10, $debug = false, $force = false) {
        $cron_option = 'find_transcriptions_last_run';
        $last_run = $this->get_option($cron_option, 0);
        $curr_time = $this->curr_time();
        $max_wait = $last_run + 10 * 60; // 10 min

        if ($curr_time > $max_wait || $force) {
            // Set curr time to option
            $this->update_option($cron_option, $curr_time);

            // 1. Get no ts posts
            $no_ts = $this->get_no_ts_posts($count);
            
            if ($debug) {
                print_r(array('No ts', $no_ts));
            }

            if ($no_ts) {
                foreach ($no_ts as $item) {
                    $id = $item->id;
                    $link = $item->link;
                    $this->insert_transcription($link, $id, $debug);
                }
            }

            $total = count((array) $no_ts);

            // Update old transcriptions
            if ($total < $count) {
                $count = $count - $total;
                // 2. Get day interval
                // 3. Get week interval
                // 4. Get mounth intervel
                $total_found = array();
                foreach ($this->update_interval as $interval) {
                    $new_update = $curr_time - ($interval * 86400);
                    $old_ts = $this->get_old_ts_posts($interval, $new_update, $count, $debug);
                    if ($old_ts) {
                        foreach ($old_ts as $item) {
                            $total_found[$item->id] = $item;
                            if (sizeof($total_found) >= $count) {
                                break;
                            }
                        }
                    }
                    if (sizeof($total_found) >= $count) {
                        break;
                    }
                }

                if ($total_found) {
                    if ($debug) {
                        print_r(array('Old ts', $total_found));
                    }
                    /* TODO. Find and update ts
                     * update error count
                     * update interval
                     */
                    foreach ($total_found as $item) {
                        if ($debug){
                            p_r($item);
                        }
                        $id = $item->id;
                        $link = $item->link;
                        $tid = $item->tid;
                        $count_err = $item->count_err;
                        $this->update_transcription($link, $tid, $count_err, $debug);
                    }
                }
            }
            // Remove last run time
            $this->update_option($cron_option, 0);
        }
    }

    private function insert_transcription($link = '', $id = 0, $debug = false) {

        $content = '';
        $status = 0;

        $result = $this->parse_ts($link, $debug);

        if ($result['code'] == 200) {
            // Transcriptions valid
            $content = $result['data'];
            $status = 1;
        }
        // Insert transcription
        $date_add = $this->curr_time();

        $data = array(
            'pid' => $id,
            'date_add' => $date_add,
            'last_upd' =>  $date_add,
            'status' => $status,
            'content' => $content,
        );

        if ($debug) {
            print_r($data);
        }

        $this->cm->sync_insert_data($data, $this->db['transcriptions'], $this->cm->sync_client, $this->cm->sync_data, 10);
    }

    private function update_transcription($link, $tid, $count_err = 0, $debug = false) {
        $content = '';
        $status = 0;
        $interval = 1;
        $result = $this->parse_ts($link, $debug);

        if ($result['code'] == 200) {
            // Transcriptions valid
            $content = $result['data'];
            $status = 1;
        } else {
            $count_err += 1;
            // Change interval
            $interval = $this->calc_inerval($count_err);
        }
        // Insert transcription
        $last_upd = $this->curr_time();

        $data = array(
            'last_upd' =>  $last_upd,
            'status' =>  $status,
            'content' => $content,
            'count_err' =>  $count_err,
            'update_interval' => $interval,
        );
        
        if ($debug){
            p_r($data);
        }

        $this->cm->sync_update_data($data, $tid, $this->db['transcriptions'], $this->cm->sync_data, 10);
    }

    private function calc_inerval($count_err) {
        $nex_interval = 1;
        foreach ($this->err_interval as $cnt => $interval) {
            if ($count_err > $cnt) {
                $nex_interval = $interval;
            }
        }
        return $nex_interval;
    }

    private function parse_ts($link, $debug = false) {

        // Service
        // http://172.17.0.1:8009/?p=43dfsfgFe_dJD4S-fdds&proxy=107.152.153.239:9942&url=
        $service = 'http://148.251.54.53:8009/?p=43dfsfgFe_dJD4S-fdds';

        $proxy = $this->cm->get_parser_proxy(true);
        $proxy_text = '';
        if ($proxy) {
            $proxy_num = array_rand($proxy);
            $proxy_text = "&proxy=" . $proxy[$proxy_num];
        }
        $service = $service . $proxy_text . '&url=' . $link;
        $data = file_get_contents($service);
        $code = 0;
        if ($data && strstr($data, '00:00')) {
            $code = 200;
        }

        if ($debug) {
            print_r(array($link, $service, $code, strlen($data), $data));
        }

        return array('data' => $data, 'code' => $code);
    }

    private function get_ts($count = 10, $status = 1) {
        $sql = sprintf("SELECT id, pid, date_add, content, status, type FROM {$this->db['transcriptions']} WHERE status=%d ORDER BY id ASC limit %d", (int) $status, (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }

    private function get_no_ts_posts($count = 10) {

        $sql = sprintf("SELECT p.id, p.link FROM {$this->db['posts']} p LEFT JOIN {$this->db['transcriptions']} t ON p.id = t.pid"
                . " WHERE p.view_type=1 AND t.pid IS NULL ORDER BY p.id DESC limit %d", (int) $count);
        $results = $this->db_results($sql);

        return $results;
    }

    private function get_old_ts_posts($interval, $new_update, $count, $debug = false) {
        $sql = sprintf("SELECT t.id as tid, t.count_err, p.id, p.link FROM {$this->db['transcriptions']} t"
                . " INNER JOIN {$this->db['posts']} p ON p.id = t.pid"
                . " WHERE t.status=0 AND t.update_interval = %d AND t.last_upd <%d ORDER BY p.id DESC limit %d", (int) $interval, (int) $new_update, (int) $count);
        $results = $this->db_results($sql);
        if ($debug) {
            print $sql;
        }
        return $results;
    }

    private function update_ts_status($id = 0, $status = 1) {
        $data = array(
            'status' => $status,
        );
        $this->cm->sync_update_data($data, $id, $this->db['transcriptions'], $this->cm->sync_data);
    }

    public function get_code($headers) {
        $code = 0;
        if (preg_match('/HTTP\/1\.1[^\d]+403/', $headers)) {
            // Status - 403 error
            $code = 403;
        } else if (preg_match('/HTTP\/1\.1[^\d]+500/', $headers)) {
            // Status - 500 error
            $code = 500;
        } else if (preg_match('/HTTP\/1\.1[^\d]+404/', $headers)) {
            // Status - 500 error
            $code = 404;
        } else if (preg_match('/HTTP\/1\.1[^\d]+200/', $headers)) {
            // Status - 500 error
            $code = 200;
        }
        return $code;
    }

}
