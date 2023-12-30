<?php

/*
 * Include plugins for ajax requests
 */

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

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