<?php

if (isset($_POST['ajaxAct'])) {
    // WP api
    require_once('../wp-config.php');
    // Post form data
    global $cfront;        
    $cav = $cfront->cm->get_cav();
    $cav->ajax_random_avatar();
} 