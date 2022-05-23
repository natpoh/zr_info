<?php

/*
 * Critic Matic Transcriptions
 */

class CriticMaticTrans extends AbstractDB {

    private $cm;
    private $cp;
    private $db;
    public $sort_pages = array('add_time', 'id', 'status');
    public $ts = array(0 => 'Empty', 1 => 'Parsed', 2 => 'In post', 10=>'Waiting');

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

    /*
     * Find transcription
     */

    public function find_transcriptions_youtube($count = 10, $debug = false, $force = false) {
        $cron_option = 'find_transcriptions_last_run';
        $last_run = get_option($cron_option, 0);
        $currtime = $this->curr_time();
        $max_wait = $last_run + 10 * 60; // 10 min

        if ($currtime > $max_wait || $force) {
            // Set curr time to option
            update_option($cron_option, $currtime);

            // Find transcripts from posts from three days ago. We need a lot of time to create transcripts for new YouTube videos.
            $min_date = $currtime - 86400 * 3; // 3 days
            // 1. Get no ts posts
            $no_ts = $this->get_no_ts_posts($count, $min_date);
            if ($debug) {
                print_r($no_ts);
            }
            if ($no_ts) {
                foreach ($no_ts as $item) {
                    $id = $item->id;
                    $link = $item->link;
                    $this->insert_transcription($link, $id, $debug);
                }
            }
            // Remove last run time
            update_option($cron_option, 0);
        }
    }

    private function insert_transcription($link = '', $id = 0, $debug = false) {
        $cp = $this->get_cp();
        // Service
        // http://172.17.0.1:8009/?p=43dfsfgFe_dJD4S-fdds&proxy=107.152.153.239:9942&url=
        $service = 'http://148.251.54.53:8009/?p=43dfsfgFe_dJD4S-fdds';
        $proxy = array(
            '138.128.19.29:9264',
            '104.227.96.75:9283',
            '104.227.102.148:9269',
            '138.128.19.44:9824',
            '138.128.19.194:9643',
            '107.152.153.8:9689',
            '104.227.96.45:9093',
            '104.227.96.118:9663',
            '107.152.153.152:9006',
            '104.227.102.214:9281',
            '107.152.153.239:9942',
        );

        $proxy_num = array_rand($proxy);

        $service = $service . "&proxy=" . $proxy[$proxy_num] . '&url=' . $link;

        $data = $cp->get_proxy($service, '', $headers);
        $code = $this->get_code($headers);
        if ($debug) {
            print_r(array($id, $link, $service, $code, $headers));
        }
        $content = '';
        $status = 0;

        if ($code == 200) {
            // Transcriptions valid
            $content = $data;
            $status = 1;
        }
        // Insert transcription
        $date_add = $this->curr_time();

        $data = array(
            'pid' => $id,
            'date_add' => $date_add,
            'status' => $status,
            'content' => $content,
        );

        if ($debug) {
            print_r($data);
        }

        $this->cm->sync_insert_data($data, $this->db['transcriptions'], $this->cm->sync_client, $this->cm->sync_data, 10);
    }

    private function get_ts($count = 10, $status = 1) {
        $sql = sprintf("SELECT id, pid, date_add, content, status, type FROM {$this->db['transcriptions']} WHERE status=%d ORDER BY id ASC limit %d", (int) $status, (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }

    private function get_no_ts_posts($count = 10, $min_date = 0) {
        if ($min_date == 0) {
            $min_date = $this->curr_time();
        }
        $sql = sprintf("SELECT p.id, p.link FROM {$this->db['posts']} p LEFT JOIN {$this->db['transcriptions']} t ON p.id = t.pid"
                . " WHERE p.view_type=1 AND t.pid IS NULL AND p.date<%d AND p.type!=4 ORDER BY id ASC limit %d", (int) $min_date, (int) $count);
        $results = $this->db_results($sql);

        return $results;
    }

    private function get_old_ts_error_posts($count = 10) {
        // TODO old error td
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
