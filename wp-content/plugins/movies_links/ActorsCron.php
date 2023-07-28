<?php

/*
 * Add normalize actors to db
 */

class ActorsCron extends MoviesAbstractDBAn {

    private $db;

    public function __construct() {
        $this->db = array(
            'actors_normalize' => 'data_actors_normalize',
            'actors_imdb' => 'data_actors_imdb',
        );
    }

    public function run_cron($count = 100, $debug = false) {
        // Get actors data from imdb and normalize it
        $actors = $this->get_last_actors($count);

        $result = array();
        /*
         *     [571] => stdClass Object
          (
          [id] => 13352056
          [name] => Andrew L. Barnett
          )
          [610] => stdClass Object
          (
          [id] => 13349807
          [name] => Gregory P.M. Trigo
          )
         *     [629] => stdClass Object
          (
          [id] => 13349449
          [name] => John Papenfuss Jr.
          )
         * 
          [647] => stdClass Object
          (
          [id] => 13349415
          [name] => Samson J. Greenman Jr.
          )
         * Jérémie Arné
         * Kôtarô Wada
         */

        if ($actors) {
            foreach ($actors as $actor) {
                // Source name 
                // 1 - default
                // 2 - birth
                if ($debug){
                    print_r($actor);
                }
                $source_name = 1;
                $raw_name = $actor->name;
                if ($actor->birth_name && ($actor->name != $actor->birth_name)) {
                    $source_name = 2;
                    $raw_name = $actor->birth_name;
                }
                $name = $raw_name;
                $id = $actor->id;

                // 1. A.B.
                $ab_reg = '/(^| )[a-zA-Z]{1}\./';
                while (preg_match($ab_reg, $name, $match)) {
                    $name = preg_replace($ab_reg, ' ', $name);
                }

                // 2. Remove jr.
                $ab_reg = '/(^| )jr(|\.)($| )/i';
                while (preg_match($ab_reg, $name, $match)) {
                    $name = preg_replace($ab_reg, ' ', $name);
                }

                // 3. Remove jr.
                $ab_reg = '/(^| )mr(|\.)($| )/i';
                while (preg_match($ab_reg, $name, $match)) {
                    $name = preg_replace($ab_reg, ' ', $name);
                }

                // 4. Remove spaces

                $name = trim(preg_replace('/  /', ' ', $name));

                
                if ($debug) {
                    $result[$id] = array('raw' => $raw_name, 'filter' => $name);
                }
                $result[$id]['source_name'] = $source_name;
                // Get last name
                if (strstr($name, ' ')) {
                    $name_arr = explode(' ', $name);
                    $result[$id]['first'] = array_shift($name_arr);
                    $result[$id]['last'] = implode(' ', $name_arr);
                } else {
                    $result[$id]['first'] = $name;
                }
            }
        }
        if ($debug) {
            print_r($result);
        }
        if ($result) {
            // Add name to db
            /*
             *     [13507701] => Array
              (
              [raw] => Brittney L. Noria
              [filter] => Brittney Noria
              [first] => Brittney
              [last] => Noria
              )

             */
            foreach ($result as $aid => $item) {

                $data = array(
                    'aid' => $aid,
                    'firstname' => isset($item['first']) ? $item['first'] : '',
                    'lastname' => isset($item['last']) ? $item['last'] : '',
                    'source_name' => $item['source_name'],
                );
                
                $this->update_actor($aid, $data);

                if ($debug) {
                    print_r(array($aid, $data));
                }

                // $insert_id = $this->getInsertId('id', $this->db['actors_normalize']);
            }
        }
    }

    private function get_last_actors($count) {
        $sql = sprintf("SELECT a.id, a.name, a.birth_name "
                . "FROM {$this->db['actors_imdb']} a "
                . "LEFT JOIN {$this->db['actors_normalize']} an ON an.aid = a.id "
                . "WHERE (an.id is NULL OR an.last_upd=0) AND name!='' ORDER BY a.id DESC limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }
    
    private function update_actor($aid = 0, $data = array()) {
        // Get rating      

        $sql = sprintf("SELECT * FROM {$this->db['actors_normalize']} WHERE aid = %d", (int) $aid);
        $exist = $this->db_fetch_row($sql);
        $data['last_upd'] = $this->curr_time();
        if ($exist) {
            // Update       
            $this->db_update($data, $this->db['actors_normalize'], $exist->id);
        } else {
            // Add             
            $this->db_insert($data, $this->db['actors_normalize']);
        }
    }

}
