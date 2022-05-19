<?php

/*
 * Critic Matic Transcriptions
 */

class CriticMaticTrans extends AbstractDB {

    private $cm;
    private $cp;
    private $db;

    public function __construct($cm = '') {
        $this->cm = $cm;
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            //CM
            'posts' => $table_prefix . 'critic_matic_posts',
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

            // 1. Get no ts posts
            $no_ts = $this->get_no_ts_posts($count);
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

    private function get_no_ts_posts($count = 10) {
        $sql = sprintf("SELECT p.id, p.link FROM {$this->db['posts']} p LEFT JOIN {$this->db['transcriptions']} t ON p.id = t.pid"
                . " WHERE p.view_type=1 AND t.pid IS NULL ORDER BY id ASC limit %d", (int) $count);
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

}
