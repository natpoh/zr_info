<?php

/*
 * Critic Matic Transcriptions
 */

class CriticMaticTrans extends AbstractDB {

    private $cm;
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

    public function find_transcriptions_youtube($count = 10, $debug = false, $force = false) {
        // 1. Get no ts posts
        $no_ts = $this->get_no_ts_posts($count);
    }

    private function get_no_ts_posts($count = 10) {
        $sql = sprintf("SELECT p.id FROM {$this->db['posts']} p LEFT JOIN {$this->db['transcriptions']} t ON p.id = t.pid"
                . " WHERE p.view_type=1 AND t.pid IS NULL ORDER BY id ASC limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }
    
    private function get_old_ts_error_posts($count = 10) {
        $sql = sprintf("SELECT p.id FROM {$this->db['posts']} p LEFT JOIN {$this->db['transcriptions']} t ON p.id = t.pid"
                . " WHERE p.view_type=1 AND t.pid IS NULL ORDER BY id ASC limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }

}
