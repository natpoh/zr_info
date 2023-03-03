<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}

$p = 'D_23_2D0FS0-vbb';

if ($_GET['p'] != $p) {
    return;
}


$sql = 'SELECT id FROM data_movie_imdb LIMIT 1000';
$results = Pdo_an::db_results($sql);

print_r(array('results',count($results)));
print_r(Pdo_an::last_error());

// Connect
Timer::timer_start();
foreach ($results as $item) {
    $id = $item->id;
    $sql = sprintf('SELECT title FROM data_movie_imdb WHERE id=%d', $id);
    $result = Pdo_an::db_fetch_row($sql, [], 'object', 0);
}
print_r(array('timer',Timer::timer_stop()));

// Disconnect
Timer::timer_start();
foreach ($results as $item) {
    $id = $item->id;
    $sql = sprintf('SELECT title FROM data_movie_imdb WHERE id=%d', $id);
    $result = Pdo_an::db_fetch_row($sql, [], 'object', 1);
}
print_r(array('timer',Timer::timer_stop()));


// Default
Timer::timer_start();

foreach ($results as $item) {
    $id = $item->id;
    $sql = sprintf('SELECT title FROM data_movie_imdb WHERE id=%d', $id);
    $result = Pdo_an::db_fetch_row($sql);
}

print_r(array('timer',Timer::timer_stop()));



class Timer {
    
    static $timestart = 0;

    static function timer_start() {        
        static::$timestart = microtime(1);
    }

    static function timer_stop($precision = 3) {
        $mtime = microtime(1);
        $timetotal = $mtime - static::$timestart;
        $r = number_format($timetotal, $precision);

        return $r;
    }

}
