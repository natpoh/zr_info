<?php

if (isset($_POST['wpcr3_ajaxAct'])) {
    // WP api
    require_once('../wp-config.php');
    // Post form data
    global $cfront;
    if ($cfront) {
        $ca = $cfront->get_ca();
        $ca->ajax();
    }
} 