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

                $raw_name = $actor->name;
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
                $firstname = isset($item['first']) ? $item['first'] : '';
                $lastname = isset($item['last']) ? $item['last'] : '';
                $this->insert_actor($aid, $firstname, $lastname);
                if ($debug) {
                    print_r(array($aid, $firstname, $lastname));
                }
                
                $insert_id = $this->getInsertId('id', $this->db['actors_normalize']);
                
            }
        }
    }

    private function get_last_actors($count) {
        $sql = sprintf("SELECT a.id, a.name "
                . "FROM {$this->db['actors_imdb']} a "
                . "LEFT JOIN {$this->db['actors_normalize']} an ON an.aid = a.id "
                . "WHERE an.id is NULL ORDER BY a.id DESC limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }

    private function insert_actor($aid, $firstname, $lastname) {
        $sql = sprintf("INSERT INTO {$this->db['actors_normalize']} (aid,firstname,lastname) "
                . "VALUES (%d,'%s','%s')", (int) $aid, $this->escape($firstname), $this->escape($lastname));
        $this->db_query($sql);
    }

}
