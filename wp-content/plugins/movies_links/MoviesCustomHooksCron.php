<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class MoviesCustomHooksCron {
    private $db;

    public function __construct() {
        $this->db = array(
            
        );
    }

    public function run_cron($count = 100, $cid=0, $debug = false) {
        // 1. Get campaing
        // 2. Get last run id
        // 3. Get posts
        // 4. Run hook for posts
    }
}