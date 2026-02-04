<?php

if ( !defined('ABSPATH') )
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

include  ABSPATH.'an_config.php';

if (!function_exists('pdoconnect_db')) {

function pdoconnect_db()
{

    global $pdo;

    try {

        $pdo = new PDO("mysql:host=".DB_HOST_AN.";dbname=".DB_NAME_AN, DB_USER_AN, DB_PASSWORD_AN );

    }
    catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }

    $pdo->exec("SET NAMES '" .DB_CHARSET_AN . "' ");

}
}

if (!function_exists('gmi')) {

    function gmi($name) {
        global $timestart;
        if (!$timestart) {
            ctg_timer_start();
        }
        
        global $gmi;
        if (!$gmi) {
            $gmi = array();
        }              

        $mem = function_exists('memory_get_usage') ? round(memory_get_usage() / 1024 / 1024, 2) : 0;
        
        if (function_exists('get_num_queries')){
            $gmi[$name] = sprintf('%d queries. %s seconds.', get_num_queries(), ctg_timer_stop(0, 3)) . ' | ' . $mem . ' Mb';
        } else {
            $gmi[$name] = sprintf(' %s seconds.', ctg_timer_stop(0, 3)) . ' | ' . $mem . ' Mb';
        }
        
        
    }

    function ctg_timer_start() {
        global $timestart;
        $timestart = microtime(true);
        return true;
    }

    function ctg_timer_stop($display = 0, $precision = 3) {
        global $timestart, $timeend, $laststop;
        if (!$laststop) {
            $laststop = $timestart;
        }
        $timeend = microtime(true);
        $timetotal = $timeend - $timestart;

        if ($laststop > $timetotal) {
            $laststop = 0;
        }

        $functime = $timetotal - $laststop;
        $laststop = $timetotal;

        $t = number_format($timetotal, $precision);
        $f = number_format($functime, $precision);
        if ($t > 1) {
            if ($t > 5) {
                $t = "<b>$t</b>";
            }
            $t = "<i>$t</i>";
        }



        if ($f > 1) {
            $f = "<b>$f</b>";
        }

        $r = $t . ' [' . $f . ']';
        if ($display)
            echo $r;
        return $r;
    }

}