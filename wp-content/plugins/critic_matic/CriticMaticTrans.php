<?php

/*
 * Critic Matic Transcriptions
 */

class CriticMaticTrans extends AbstractDB {

    private $cm;
    private $cp;
    private $db;
    public $sort_pages = array('add_time', 'id', 'status');

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

    public function find_transcriptions_youtube($count = 10, $debug = false, $force = false) {
        $cron_option = 'find_transcriptions_last_run';
        $last_run = get_option($cron_option, 0);
        $currtime = $this->curr_time();
        $max_wait = $last_run + 10 * 60; // 10 min

        if ($currtime > $max_wait || $force) {
            // Set curr time to option
            update_option($cron_option, $currtime);
            
            // Find transcripts from posts from three days ago. We need a lot of time to create transcripts for new YouTube videos.
            $min_date = $currtime-86400*3; // 3 days

            // 1. Get no ts posts
            $no_ts = $this->get_no_ts_posts($count, $min_date);
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

    private function get_no_ts_posts($count = 10, $min_date=0) {
        if ($min_date==0){
            $min_date = $this->curr_time();
        }
        $sql = sprintf("SELECT p.id, p.link FROM {$this->db['posts']} p LEFT JOIN {$this->db['transcriptions']} t ON p.id = t.pid"
                . " WHERE p.view_type=1 AND t.pid IS NULL AND p.date<%d AND p.type!=4 ORDER BY id ASC limit %d", (int) $count,  (int) $min_date);
        $results = $this->db_results($sql);
        return $results;
    }

    private function get_old_ts_error_posts($count = 10) {
        // TODO old error td
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

    /*
     * Admin menu
     */

    public function get_posts($status = 0, $page = 1, $per_page = 20, $aid = 0, $type = -1, $meta_type = -1, $author_type = -1, $view_type = -1, $orderby = '', $order = 'ASC') {
        $page -= 1;
        $start = $page * $this->perpage;

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE p.status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE p.status = " . (int) $status;
        }


        // Author filter
        $aid_and = '';
        if ($aid > 0) {
            $aid_and = sprintf(" AND am.aid = %d", $aid);
        }

        //Post type filter
        $type_and = '';
        if ($type != -1) {
            $type_and = sprintf(" AND p.type =%d", (int) $type);
        }

        // View type filter
        $view_type_and = '';
        if ($view_type != -1) {
            $view_type_and = sprintf(" AND p.view_type =%d", (int) $view_type);
        }

        // Author type
        $atype_inner = '';
        $atype_and = '';
        if ($author_type != -1) {
            $atype_inner = " INNER JOIN {$this->db['authors']} a ON a.id = am.aid";
            $atype_and = sprintf(" AND a.type = %d", $author_type);
        }

        // Meta type filter
        $meta_type_and = '';
        if ($meta_type != -1) {
            if ($meta_type == 1) {
                $meta_type_and = " AND p.top_movie != 0";
            } else {
                $meta_type_and = " AND p.top_movie = 0";
            }
        }


        //Sort
        $and_orderby = '';
        $and_order = '';
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


        $sql = "SELECT p.id, p.date, p.date_add, p.status, p.type, p.link_hash, p.link, p.title, p.content, p.top_movie, p.blur, am.aid "
                . "FROM {$this->db['posts']} p "
                . "INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id "
                . "LEFT JOIN {$this->db['transcriptions']} t ON t.pid = p.id "
                . $atype_inner . $status_query . $aid_and . $type_and . $view_type_and . $meta_type_and . $atype_and . $and_orderby . $limit;


        $result = $this->db_results($sql);

        return $result;
    }

    public function get_post_count($status = -1, $type = -1, $aid = 0, $meta_type = -1, $author_type = -1) {
        // Custom status
        $status_trash = 2;
        $status_query = " WHERE p.status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE p.status = " . (int) $status;
        }

        //Post type filter
        $type_and = '';
        if ($type != -1) {
            $type_and = sprintf(" AND p.type =%d", (int) $type);
        }

        // Author filter
        $aid_inner = '';
        $aid_and = '';
        if ($aid > 0) {
            $aid_inner = " INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id";
            $aid_and = sprintf(" AND am.aid = %d", $aid);
        }

        // Author type
        $atype_inner = '';
        $atype_and = '';
        if ($author_type != -1) {
            $atype_inner = " INNER JOIN {$this->db['authors_meta']} am2 ON am2.cid = p.id INNER JOIN {$this->db['authors']} a ON a.id = am2.aid";
            $atype_and = sprintf(" AND a.type = %d", $author_type);
        }

        // Meta type filter
        $meta_type_and = '';
        if ($meta_type != -1) {
            if ($meta_type == 1) {
                $meta_type_and = " AND p.top_movie != 0";
            } else {
                $meta_type_and = " AND p.top_movie = 0";
            }
        }

        $query = "SELECT COUNT(*) FROM {$this->db['posts']} p" . $aid_inner . " LEFT JOIN {$this->db['transcriptions']} t ON t.pid = p.id" . $atype_inner . $status_query . $aid_and . $type_and . $meta_type_and . $atype_and;

        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_post_states($type = -1, $aid = 0, $meta_type = -1, $author_type = -1) {
        $status = -1;
        $count = $this->get_post_count($status, $type, $aid, $meta_type, $author_type);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->cm->post_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_post_count($key, $type, $aid, $meta_type, $author_type));
        }
        return $states;
    }

}
