<?php

// Путь к корневой директории WordPress
if (!defined('ABSPATH'))
    define('ABSPATH', dirname(__FILE__, 2) . '/');

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
}

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractFunctions.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBFront.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBAn.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'ThemeCache.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMatic.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticSearch.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'SearchFacets.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticFront.php' );
!class_exists('CustomHooks') ? include ABSPATH . "wp-content/plugins/critic_matic/CustomHooks.php" : '';

gmi('before cm');
$cm = new CriticMatic();
$wpu=$cm->get_wpu();
$user_id = $wpu->get_current_user();
if ($user_id){
    print_r($wpu->user);
}
gmi('after cm');

global $gmi;
if ($gmi) {
    print '<pre>';
    foreach ($gmi as $i => $val) {
        echo $val . '   ' . $i . PHP_EOL;
    }
    print '</pre>';
}
