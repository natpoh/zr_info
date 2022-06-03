<?php

if ($_GET['p'] && $_GET['p'] == 'dfs_WFDS-32FhGSD6') {
    /*
     * Show request
     */

    print '<pre>';
    print_r($_SERVER);
    print_r($_REQUEST);
    print_r($_GET);
    print_r($_POST);
    print '</pre>';
}