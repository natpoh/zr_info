<?php
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}

error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

!class_exists('Gsearch') ? include ABSPATH . "analysis/include/gsearch.php" : '';





if (isset($_GET['id'])) {
    $movie_id = intval($_GET['id']);
    $Gsearch = new Gsearch();
    $result = $Gsearch->get_data($movie_id);
    echo json_encode($result);

}

