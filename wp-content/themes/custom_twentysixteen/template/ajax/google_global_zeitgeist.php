<?php

error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

!class_exists('Gsearch') ? include ABSPATH . "analysis/include/gsearch.php" : '';


if (isset($_GET['id'])) {
    $movie_id = intval($_GET['id']);
    $Gsearch = new Gsearch();
    $result = $Gsearch->get_data($movie_id, 2);
    echo json_encode($result);

}