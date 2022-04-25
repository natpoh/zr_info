<?php

/*
  Plugin Name: Movies links
  Plugin URI: https://emelianovip.ru/
  Description: This plugin manages the movies
  Author: Brahman  <fb@emelianovip.ru>
  Author URI: https://emelianovip.ru
  Version: 0.0.2
  License: GPLv2
 */

/*
 * TODO
 * 1. Campaings admin page
 * 2. Campaign settings:
 * - add urls map
 * - parse url
 * - parse page and find attributes
 * - link pages to movies
 * 
 * 
 */

define('MOVIES_LINKS_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/movies_links/');

$version = '0.0.2';
if (defined('LASTVERSION')) {
    define('MOVIES_LINKS_VERSION', $version . LASTVERSION);
} else {
    define('MOVIES_LINKS_VERSION', $version);
}

function include_movies_links() {
    //DB config
    !defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
    //Abstract DB
    !class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

    if (!class_exists('MoviesLinks')) {
        require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
        require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );
        require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
    }
}

// WP Logic

if (!function_exists('add_action')) {
    return;
}

add_action('init', 'movies_links_init');

function movies_links_init() {
    if (is_admin()) {

        include_movies_links();

        require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinksAdmin.php' );
        require_once( MOVIES_LINKS_PLUGIN_DIR . '/admin/ItemAdmin.php' );

        $mla = new MoviesLinksAdmin();

        // Force activation
        if (isset($_GET['ml_activation']) && $_GET['ml_activation'] == 1) {
            movies_links_plugin_activation();
        }
    }
}

/**
 * Install table structure
 */
register_activation_hook(__FILE__, 'movies_links_plugin_activation');

function movies_links_plugin_activation() {

    //Critic parser
    $sql = "CREATE TABLE IF NOT EXISTS  `movies_links_campaign`(
				`id` int(11) unsigned NOT NULL auto_increment,                                				
                                `date` int(11) NOT NULL DEFAULT '0',
                                `status` int(11) NOT NULL DEFAULT '1',                                                   		
                                `title` varchar(255) NOT NULL default '',                                                                
                                `site` text default NULL,                                
                                `options` text default NULL,
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_ml::db_query($sql);

    $sql = "ALTER TABLE `movies_links_campaign` ADD `type` int(11) NOT NULL DEFAULT '0'";
    Pdo_ml::db_query($sql);

    movies_links_create_index(array('date', 'status', 'type'), 'movies_links_campaign');

    //Critic parser log
    $sql = "CREATE TABLE IF NOT EXISTS  `movies_links_log`(
				`id` int(11) unsigned NOT NULL auto_increment,				
                                `date` int(11) NOT NULL DEFAULT '0',
                                `cid` int(11) NOT NULL DEFAULT '0',
                                `uid` int(11) NOT NULL DEFAULT '0',
                                `type` int(11) NOT NULL DEFAULT '0',
                                `status` int(11) NOT NULL DEFAULT '0',
				`message` varchar(255) NOT NULL default '',				
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_ml::db_query($sql);

    /*
     * cid - campaign id
     * pid - post id
     * 
     */
    $sql = "CREATE TABLE IF NOT EXISTS  `movies_links_url`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `cid` int(11) NOT NULL DEFAULT '0',   
                                `pid` int(11) NOT NULL DEFAULT '0',
                                `status` int(11) NOT NULL DEFAULT '0',
                                `link_hash` varchar(255) NOT NULL default '',                                
                                `link` text default NULL,               
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_ml::db_query($sql);
    movies_links_create_index(array('cid', 'pid', 'status', 'link_hash'), 'movies_links_url');

    /*
     * uid - url id
     * arhive_hash - arhive hash filename
     */
    $sql = "CREATE TABLE IF NOT EXISTS  `movies_links_arhive`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `date` int(11) NOT NULL DEFAULT '0',                                    
                                `uid` int(11) NOT NULL DEFAULT '0',                                  
                                `arhive_hash` varchar(255) NOT NULL default '',                                                                
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_ml::db_query($sql);
    movies_links_create_index(array('date', 'uid', 'arhive_hash'), 'movies_links_arhive');


    /*
     * uid - url id
     * status:
     * 0 - error parse arhive
     * 1 - done
     * options: json array.
     */
    $sql = "CREATE TABLE IF NOT EXISTS  `movies_links_posts`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `date` int(11) NOT NULL DEFAULT '0',                                    
                                `last_upd` int(11) NOT NULL DEFAULT '0',     
                                `uid` int(11) NOT NULL DEFAULT '0',                                                                  
                                `top_movie` int(11) NOT NULL DEFAULT '0', 
                                `rating` int(11) NOT NULL DEFAULT '0', 
                                `status` int(11) NOT NULL DEFAULT '0',                                                                  
                                `title` varchar(255) NOT NULL default '',   
                                `rel` varchar(255) NOT NULL default '',   
                                `year` int(11) NOT NULL DEFAULT '0',                                                                  
                                `options` text default NULL,           		                                                                
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_ml::db_query($sql);
    movies_links_create_index(array('date', 'last_upd', 'uid', 'status', 'top_movie', 'rating', 'title', 'rel', 'year'), 'movies_links_posts');

    $sql = "ALTER TABLE `movies_links_posts` ADD `status_links` int(11) NOT NULL DEFAULT '0'";
    Pdo_ml::db_query($sql);
    movies_links_create_index(array('status_links'), 'movies_links_posts');


    /*
     * Actors names meta
     * UNUSED
     */


    $sql = "CREATE TABLE IF NOT EXISTS  `actors_meta`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `aid` int(11) NOT NULL DEFAULT '0',   
                                `pid` int(11) NOT NULL DEFAULT '0',                                   
                                `cid` int(11) NOT NULL DEFAULT '0',   
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";

    Pdo_ml::db_query($sql);
    movies_links_create_index(array('aid', 'pid', 'cid'), 'actors_meta');

    /*
     * Actors names
     */

    $sql = "CREATE TABLE IF NOT EXISTS  `data_actors_normalize`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `aid` int(11) NOT NULL DEFAULT '0',   
                                `firstname` varchar(255) NOT NULL default '',
                                `lastname` varchar(255) NOT NULL default '',                                
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    if (function_exists('critic_matic_create_index_an')) {
        critic_matic_create_index_an(array('aid', 'firstname', 'lastname'), 'data_actors_normalize');
    }

    /*
     * Actors lastnames unique
     */

    $sql = "CREATE TABLE IF NOT EXISTS  `data_lastnames`(
				`id` int(11) unsigned NOT NULL auto_increment,                                                               
                                `lastname` varchar(255) NOT NULL default '',         
                                `topcountry` int(11) NOT NULL DEFAULT '0',  
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    if (function_exists('critic_matic_create_index_an')) {
        critic_matic_create_index_an(array('lastname','topcountry'), 'data_lastnames');
    }

    /*
     * Familysearch
     */
    $sql = "CREATE TABLE IF NOT EXISTS  `data_familysearch_country`(
				`id` int(11) unsigned NOT NULL auto_increment,                                
                                `country` varchar(255) NOT NULL default '',                                
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    if (function_exists('critic_matic_create_index_an')) {
        critic_matic_create_index_an(array('country'), 'data_familysearch_country');
    }

    $sql = "CREATE TABLE IF NOT EXISTS  `meta_familysearch`(
				`id` int(11) unsigned NOT NULL auto_increment,                                
                                `nid` int(11) NOT NULL DEFAULT '0',   
                                `cid` int(11) NOT NULL DEFAULT '0',   
                                `ccount` int(11) NOT NULL DEFAULT '0',  
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    if (function_exists('critic_matic_create_index_an')) {
        critic_matic_create_index_an(array('nid', 'cid', 'ccount'), 'meta_familysearch');
    }
}

function movies_links_create_index($names = array(), $table_name = '') {

    if ($names && $table_name) {
        foreach ($names as $name) {
            $index_sql = "SELECT COUNT(*)        
    FROM `INFORMATION_SCHEMA`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = 'movies_links' 
    AND `TABLE_NAME` = '$table_name'
    AND `INDEX_NAME` = '$name'";
            $exists = Pdo_ml::db_get_var($index_sql);

            if (!$exists) {
                $sql = "ALTER TABLE `$table_name` ADD INDEX(`$name`)";
                Pdo_ml::db_query($sql);
            }
        }
    }
}
