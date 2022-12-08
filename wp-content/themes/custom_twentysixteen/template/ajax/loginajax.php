<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


require_once(ABSPATH. 'wp-load.php' );
login_with_ajax(array('template' => 'default','vanilla'=>0));