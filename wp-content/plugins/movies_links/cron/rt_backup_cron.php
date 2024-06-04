<?php

/*
 * /wp-content/plugins/movies_links/cron/rt_backup_cron.php?p=8ggD_23_2D0DSF-F&debug=1&force=1
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
nocache_headers();

if (!function_exists('movies_links_init')) {
    return;
}

$p = '8ggD_23_2D0DSF-F';

if ($_GET['p'] != $p) {
    return;
}

$count = 100;
if ($_GET['c']) {
    $count = (int) $_GET['c'];
}

$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$force = false;
if ($_GET['force']) {
    $force = true;
}

if (!class_exists('MoviesLinks')) {

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
}

$ml = new MoviesLinks();

$cron_name = 'rt_backup_cron';
if ($ml->cron_already_run($cron_name, 10, $debug)) {
    if (!$force) {
        exit();
    }
}

if (!class_exists('MoviesAbstractDBAn')) {
    require_once( MOVIES_LINKS_PLUGIN_DIR . '/db/MoviesAbstractDBAn.php' );
}

class MoviesLinksEratingBackup extends MoviesAbstractDBAn {
    private $ml;

    public function __construct($ml) {
        $this->ml=$ml;
        $this->db = array(
            'erating' => 'data_movie_erating',
            'erating_backup' => 'data_movie_erating_backup'
        );
    }

    public function run_cron($count = 100, $debug = false, $force = false) {
        $option_name = 'cron_rt_erating_backup';
        $last_id = $this->get_option($option_name, 0);
        if ($force) {
            $last_id = 0;
        }

        if ($debug) {
            print_r(array('last_update', $last_id));
        }

        // 1. Get backup data
        $sql = sprintf("SELECT id, movie_id, rt_rating, rt_aurating, rt_gap FROM {$this->db['erating_backup']} WHERE id>=%d AND (rt_rating >0 OR rt_aurating>0) ORDER BY id ASC limit %d", $last_id, $count);

        if ($debug) {
            print_r($sql);
        }
        $results = $this->db_results($sql);
        if ($debug) {
            print_r($results);
        }

        if ($results) {
            $last = end($results);
            if ($debug) {
                print 'last_update: ' . $last->id . "\n";
            }
            if ($last) {
                $this->update_option($option_name, $last->id);
            }
            
            $ma = $this->ml->get_ma();

            foreach ($results as $item) {
                // 1. Get new data
                $sql = sprintf("SELECT id, movie_id, rt_rating, rt_aurating, rt_gap FROM {$this->db['erating']} WHERE movie_id=%d", $item->movie_id);
                if ($debug) {
                    print_r($sql);
                }
                $result = $this->db_fetch_row($sql);
                if ($debug) {
                    print_r($result);
                }

                $update = false;
                $data = array(
                    'rt_rating' => $result->rt_rating,
                    'rt_aurating' => $result->rt_aurating,
                );

                if ($result->rt_rating == 0 && $item->rt_rating > 0) {
                    $update = true;
                    $data['rt_rating'] = $item->rt_rating;
                }
                if ($result->rt_aurating == 0 && $item->rt_aurating > 0) {
                    $update = true;
                    $data['rt_aurating'] = $item->rt_aurating;
                }

                if ($update) {
                    $data['rt_gap'] = $data['rt_aurating'] - $data['rt_rating'];
                    if ($debug) {
                        print_r($data);
                    }
                   
                    $ma->update_erating($item->movie_id, $data);
                }
            }
        }
    }
}

print $cron_name;

$ml->register_cron($cron_name);

$mb = new MoviesLinksEratingBackup($ml);

$mb->run_cron($count, $debug, $force);

$ml->unregister_cron($cron_name);
