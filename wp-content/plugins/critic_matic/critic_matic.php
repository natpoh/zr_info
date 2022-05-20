<?php

/*
  Plugin Name: Critic Matic
  Plugin URI: https://emelianovip.ru/
  Description: This plugin manages the posts of critics
  Author: Brahman  <fb@emelianovip.ru>
  Author URI: https://emelianovip.ru
  Version: 1.0.7
  License: GPLv2
 */

/*
 * TODO
 * Critic matic:
 * - critic list
 * - manage all submodules
 * - one time parsing old data to new scheme
 * 
 * Critic feeds
 * - manage rss feeds
 * 
 * Critic parser
 * - manage source sites and get its content 
 * 
 * Critic search
 * - creating a connecting between critics and films
 */

if (!function_exists('add_action')) {
    exit;
}

define('CRITIC_MATIC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRITIC_MATIC_PLUGIN_URL', plugin_dir_url(__FILE__));

$version = '1.0.75';
if (defined('LASTVERSION')) {
    define('CRITIC_MATIC_VERSION', $version . LASTVERSION);
} else {
    define('CRITIC_MATIC_VERSION', $version);
}

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractFunctions.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBWp.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDB.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBAn.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'ThemeCache.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMatic.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticSearch.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticEmotions.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'SearchFacets.php' );
require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticFront.php' );


add_action('init', 'critic_matic_init');

function critic_matic_init() {
    $cm = new CriticMatic();
    $cs = new CriticSearch($cm);

    if (is_admin()) {
        // Admin pages
        // Critic feeds
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticFeeds.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
        $cf = new CriticFeeds($cm);
        $cp = new CriticParser($cm);

        require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMaticAdmin.php' );
        $cma = new CriticMaticAdmin($cm, $cs, $cf, $cp);

        // One time import feeds and authors
        if (isset($_GET['cm_import_feeds']) && $_GET['cm_import_feeds'] == 1) {
            // Import feeds (One time task)
            // $cf->import_feeds();
        }
        if (isset($_GET['cm_add_counter']) && $_GET['cm_add_counter'] == 1) {
            $cm->add_sphinx_counter();
        }

        if (isset($_GET['cm_update_post_rating_options']) && $_GET['cm_update_post_rating_options'] == 1) {
            $cm->update_post_rating_options();
        }

        // Force activation
        if (isset($_GET['cm_activation']) && $_GET['cm_activation'] == 1) {
            critic_matic_plugin_activation();
        }
    } else {
        // Front pages
        global $cfront, $cm_new_api;
        $cfront = new CriticFront($cm, $cs);
        $cm_new_api = $cfront->new_api;
        $cfront->init_scripts();
    }
}

/**
 * Install table structure
 */
register_activation_hook(__FILE__, 'critic_matic_plugin_activation');

/*
 * Tables list:
 * 
 * WP DB:
 * 
 * critic_matic_wpposts_meta
 * 
 * Feed
 * critic_feed_campaign - feed campaign 
 * critic_feed_log - log for critic feeds 
 * 
 * Search
 * critic_search_log - log for critic search
 * 
 * Parser
 * critic_parser_campaign
 * critic_parser_log
 * critic_parser_url
 * 
 * 
 * AN DB:
 * 
 * Review
 * critic_matic_posts - review posts
 * critic_matic_meta - meta for connect posts and movies (unused)
 * critic_matic_posts_meta - meta for connect posts and movies AN db
 * critic_feed_meta - meta for connect campaign and review post
 * critic_matic_rating - rating data for audience and staff reviews
 * search_movies_meta - meta that include movies last search update (unused)
 * 
 * Auhors
 * critic_matic_tags - authors tags
 * critic_matic_tag_meta - meta for connect authors and tags
 * critic_matic_authors - review authors
 * critic_matic_authors_meta - meta for connect author and review
 *  
 * Search
 * sph_counter - counter for sphinx
 *  
 * Emotions
 * critic_emotions - emotion data
 * critic_emotions_authors - emotions auhtors
 *
 * Genre
 * data_movie_genre
 * meta_movie_genre
 * 
 * Provider
 * data_movie_provider
 * 
 * 
 * UNUSED
 * critic_matic_wpposts_meta - meta for connect wp_post and review post (unused)
 * critic_movies_meta - meta that include movies last search update (unused)
 * 
 */

function critic_matic_plugin_activation() {
    $table_prefix = DB_PREFIX_WP;
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    /*
     * WP db tables
     */
    // Transit

    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_matic_wpposts_meta`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `pid` int(11) NOT NULL DEFAULT '0', 
                                `cid` int(11) NOT NULL DEFAULT '0',        
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    dbDelta($sql);
    critic_matic_create_index(array('pid', 'cid'), $table_prefix . "critic_matic_wpposts_meta");

    /* Critic feeds table */

    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_feed_campaign`(
				`id` int(11) unsigned NOT NULL auto_increment,                                				
                                `date` int(11) NOT NULL DEFAULT '0',
                                `status` int(11) NOT NULL DEFAULT '1', 
                                `last_update` int(11) NOT NULL DEFAULT '0',
                                `update_interval` int(11) NOT NULL DEFAULT '60',
                                `author` int(11) NOT NULL DEFAULT '0',                                                                		
                                `title` varchar(255) NOT NULL default '',
                                `feed_hash` varchar(255) NOT NULL default '',           
                                `feed` text default NULL,
                                `site` text default NULL,
                                `last_hash` varchar(255) NOT NULL default '',           
                                `options` text default NULL,
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    dbDelta($sql);
    critic_matic_create_index(array('date', 'status', 'last_update', 'update_interval', 'author', 'title', 'feed_hash', 'last_hash'), $table_prefix . "critic_feed_campaign");

    //Logs
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_feed_log`(
				`id` int(11) unsigned NOT NULL auto_increment,				
                                `date` int(11) NOT NULL DEFAULT '0',
                                `cid` int(11) NOT NULL DEFAULT '0',
                                `type` int(11) NOT NULL DEFAULT '0',
                                `status` int(11) NOT NULL DEFAULT '0',
				`message` varchar(255) NOT NULL default '',				
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    dbDelta($sql);
    critic_matic_create_index(array('date', 'cid', 'type', 'status'), $table_prefix . "critic_feed_log");

    //Search meta logs
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_search_log`(
				`id` int(11) unsigned NOT NULL auto_increment,				
                                `date` int(11) NOT NULL DEFAULT '0',
                                `cid` int(11) NOT NULL DEFAULT '0',
                                `mid` int(11) NOT NULL DEFAULT '0',
                                `type` int(11) NOT NULL DEFAULT '0',
                                `status` int(11) NOT NULL DEFAULT '0',
				`message` varchar(255) NOT NULL default '',				
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    dbDelta($sql);
    critic_matic_create_index(array('date', 'cid', 'mid', 'type', 'status'), $table_prefix . "critic_search_log");

    //Critic parser
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_parser_campaign`(
				`id` int(11) unsigned NOT NULL auto_increment,                                				
                                `date` int(11) NOT NULL DEFAULT '0',
                                `status` int(11) NOT NULL DEFAULT '1', 
                                `last_update` int(11) NOT NULL DEFAULT '0',
                                `update_interval` int(11) NOT NULL DEFAULT '60',
                                `author` int(11) NOT NULL DEFAULT '0',                                                                		
                                `parser_status` int(11) NOT NULL DEFAULT '0',
                                `type` int(11) NOT NULL DEFAULT '0',
                                `title` varchar(255) NOT NULL default '',                                                                
                                `site` text default NULL,                                
                                `options` text default NULL,
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    dbDelta($sql);
    critic_matic_create_index(array('date', 'status', 'type', 'last_update', 'update_interval', 'author', 'parser_status'), $table_prefix . "critic_search_log");

    /*
     * Indexes: 
     * 
      ALTER TABLE `wp_bcw98b_critic_parser_campaign` ADD INDEX(`date`);
      ALTER TABLE `wp_bcw98b_critic_parser_campaign` ADD INDEX(`status`);
      ALTER TABLE `wp_bcw98b_critic_parser_campaign` ADD INDEX(`last_update`);
      ALTER TABLE `wp_bcw98b_critic_parser_campaign` ADD INDEX(`update_interval`);
      ALTER TABLE `wp_bcw98b_critic_parser_campaign` ADD INDEX(`author`);
     */

    //Critic parser log
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_parser_log`(
				`id` int(11) unsigned NOT NULL auto_increment,				
                                `date` int(11) NOT NULL DEFAULT '0',
                                `cid` int(11) NOT NULL DEFAULT '0',
                                `uid` int(11) NOT NULL DEFAULT '0',
                                `type` int(11) NOT NULL DEFAULT '0',
                                `status` int(11) NOT NULL DEFAULT '0',
				`message` varchar(255) NOT NULL default '',				
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    dbDelta($sql);
    critic_matic_create_index(array('date', 'cid', 'uid', 'type', 'status'), $table_prefix . "critic_parser_log");

    /*
     * Indexes: 
     * 
      ALTER TABLE `wp_bcw98b_critic_parser_url` ADD INDEX(`cid`);
      ALTER TABLE `wp_bcw98b_critic_parser_url` ADD INDEX(`pid`);
      ALTER TABLE `wp_bcw98b_critic_parser_url` ADD INDEX(`status`);
      ALTER TABLE `wp_bcw98b_critic_parser_url` ADD INDEX(`link_hash`);
     */

    /* Fid - film id. UNUSED
     * Meta
     * Type:
      0 => 'None',
      1 => 'Proper Review',
      2 => 'Contains Mention',
      3 => 'Related Article'
     * State:
      1 => 'Approved',
      2 => 'Auto',
      0 => 'Unapproved'
     * Cid - critic id
     * Rating - auto search rating
     */

    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_matic_meta`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `fid` int(11) NOT NULL DEFAULT '0', 
                                `type` int(11) NOT NULL DEFAULT '0', 
                                `state` int(11) NOT NULL DEFAULT '0', 
                                `cid` int(11) NOT NULL DEFAULT '0',     
                                `rating` int(11) NOT NULL DEFAULT '0', 
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    //dbDelta($sql);

    /*
     * AN db
     */

    /* Critics table 
     * date - critic public date
     * date_add - last update date
     */

    $table_prefix = DB_PREFIX_WP_AN;
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_matic_posts`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `date` int(11) NOT NULL DEFAULT '0',    
                                `date_add` int(11) NOT NULL DEFAULT '0',
                                `status` int(11) NOT NULL DEFAULT '1',    
                                `type` int(11) NOT NULL DEFAULT '0',                                    
                                `link_hash` varchar(255) NOT NULL default '',                                
                                `link` text default NULL,                                
                                `title` text default NULL,
                                `content` text default NULL,           		                                
                                `top_movie` int(11) NOT NULL DEFAULT '0', 
                                `blur` int(11) NOT NULL DEFAULT '0', 
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('date', 'date_add', 'status', 'type', 'link_hash', 'top_movie'), $table_prefix . "critic_matic_posts");


    // Add category
    $sql = "ALTER TABLE `" . $table_prefix . "critic_matic_posts` ADD `view_type` int(11) NOT NULL DEFAULT '0'";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('view_type'), $table_prefix . "critic_matic_posts");
    //
    // Add options
    //$sql = "ALTER TABLE `" . $table_prefix . "critic_parser_log` ADD `uid` int(11) NOT NULL DEFAULT '0'";
    //dbDelta($sql);


    /*
     * Transcriptions
     */
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_transcritpions`(
				`id` int(11) unsigned NOT NULL auto_increment,                                
                                `pid` int(11) NOT NULL DEFAULT '0',
                                `date_add` int(11) NOT NULL DEFAULT '0',                                
                                `content` longtext default NULL,               
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('pid', 'date_add'), $table_prefix . "critic_transcritpions");

    // Add status
    $sql = "ALTER TABLE `" . $table_prefix . "critic_transcritpions` ADD `status` int(11) NOT NULL DEFAULT '0'";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('status'), $table_prefix . "critic_transcritpions");
    
    // Add type
    $sql = "ALTER TABLE `" . $table_prefix . "critic_transcritpions` ADD `type` int(11) NOT NULL DEFAULT '0'";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('type'), $table_prefix . "critic_transcritpions");
    
    /*
     * cid - campaign id
     * pid - post id
     */
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_parser_url`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `cid` int(11) NOT NULL DEFAULT '0',   
                                `pid` int(11) NOT NULL DEFAULT '0',
                                `status` int(11) NOT NULL DEFAULT '0',
                                `link_hash` varchar(255) NOT NULL default '',                                
                                `link` text default NULL,               
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('cid', 'pid', 'status', 'link_hash'), $table_prefix . "critic_parser_url");

    //$sql = "ALTER TABLE `" . $table_prefix . "critic_matic_posts` ADD `blur` int(11) NOT NULL DEFAULT '0'";
    //Pdo_an::db_query($sql);


    /* Fid - film id for an db
     * Meta
     * Type:
      0 => 'None',
      1 => 'Proper Review',
      2 => 'Contains Mention',
      3 => 'Related Article'
     * State:
      1 => 'Approved',
      2 => 'Auto',
      0 => 'Unapproved'
     * Cid - critic id
     * Rating - auto search rating
     */

    // Add new meta for an movies 25.08.2021
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_matic_posts_meta`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `fid` int(11) NOT NULL DEFAULT '0', 
                                `type` int(11) NOT NULL DEFAULT '0', 
                                `state` int(11) NOT NULL DEFAULT '0', 
                                `cid` int(11) NOT NULL DEFAULT '0',     
                                `rating` int(11) NOT NULL DEFAULT '0', 
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('fid', 'type', 'state', 'cid', 'rating'), $table_prefix . "critic_matic_posts_meta");

    //Tags
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_matic_tags`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `status` int(11) NOT NULL DEFAULT '1', 
                                `name` varchar(255) NOT NULL default '',    
                                `slug` varchar(255) NOT NULL default '',  
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('status', 'name', 'slug'), $table_prefix . "critic_matic_tags");

    /*
     * Tid - tag id
     * Cid - critic author id
     */
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_matic_tag_meta`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `tid` int(11) NOT NULL DEFAULT '0', 
                                `cid` int(11) NOT NULL DEFAULT '0',        
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('tid', 'cid'), $table_prefix . "critic_matic_tag_meta");

    //Authors
    /*
      $author_type = array(
      0 => 'Staff',
      1 => 'Pro',
      2 => 'Audience'
      );
      $author_status = array(
      1 => 'Publish',
      0 => 'Draft',
      2 => 'Trash'
      );
     */
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_matic_authors`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `status` int(11) NOT NULL DEFAULT '1', 
                                `type` int(11) NOT NULL DEFAULT '0',  
                                `name` varchar(255) NOT NULL default '',                                
                                `options` text default NULL,
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('status', 'type', 'name'), $table_prefix . "critic_matic_authors");

    // Authors meta
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_matic_authors_meta`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `aid` int(11) NOT NULL DEFAULT '0', 
                                `cid` int(11) NOT NULL DEFAULT '0',        
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('aid', 'cid'), $table_prefix . "critic_matic_authors_meta");


    /*
     * Critics audience temp      
     */

    $table_prefix = DB_PREFIX_WP_AN;
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_matic_audience`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `date` int(11) NOT NULL DEFAULT '0',                                    
                                `status` int(11) NOT NULL DEFAULT '0',      		                                
                                `top_movie` int(11) NOT NULL DEFAULT '0',                                
                                `rating` int(11) NOT NULL DEFAULT '0', 
                                `hollywood` int(11) NOT NULL DEFAULT '0', 
                                `patriotism` int(11) NOT NULL DEFAULT '0', 
                                `misandry` int(11) NOT NULL DEFAULT '0', 
                                `affirmative` int(11) NOT NULL DEFAULT '0', 
                                `lgbtq` int(11) NOT NULL DEFAULT '0', 
                                `god` int(11) NOT NULL DEFAULT '0', 
                                `vote` int(11) NOT NULL DEFAULT '0', 
                                `ip` varchar(255) NOT NULL default '',
                                `critic_name` varchar(255) NOT NULL default '',
                                `unic_id` varchar(255) NOT NULL default '', 
                                `title` text default NULL,
                                `content` text default NULL,    
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('date', 'status', 'critic_name', 'unic_id'), $table_prefix . "critic_matic_audience");

    /*
      //Add columns UNUSED

      // Add date add
      $sql = "ALTER TABLE `" . $table_prefix . "critic_matic_posts` ADD `date_add` int(11) NOT NULL DEFAULT '0'";
      $wpdb->query($sql);

      // Add options
      $sql = "ALTER TABLE `" . $table_prefix . "critic_matic_authors` ADD `options` text default NULL";
      $wpdb->query($sql);

     */

    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_feed_meta`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `cid` int(11) NOT NULL DEFAULT '0', 
                                `pid` int(11) NOT NULL DEFAULT '0',                                        
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('cid', 'pid'), $table_prefix . "critic_feed_meta");

    // Movies meta. UNUSED. TODO delete
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_movies_meta`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `mid` int(11) NOT NULL DEFAULT '0', 
                                `date` int(11) NOT NULL DEFAULT '0',                                        
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    //dbDelta($sql);
    //Sphinx search. UNUSED
    $sql = "CREATE TABLE IF NOT EXISTS  `sph_counter`(
				`id` int(11) unsigned NOT NULL auto_increment,                                
                                `maxdocid` int(11) NOT NULL DEFAULT '0',  
                                `name` varchar(255) NOT NULL default '',	
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    //dbDelta($sql);
    // Critic rating
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_matic_rating`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `cid` int(11) NOT NULL DEFAULT '0',                                             
                                `options` text default NULL,
                                `rating` int(11) NOT NULL DEFAULT '0', 
                                `hollywood` int(11) NOT NULL DEFAULT '0', 
                                `patriotism` int(11) NOT NULL DEFAULT '0', 
                                `misandry` int(11) NOT NULL DEFAULT '0', 
                                `affirmative` int(11) NOT NULL DEFAULT '0', 
                                `lgbtq` int(11) NOT NULL DEFAULT '0', 
                                `god` int(11) NOT NULL DEFAULT '0', 
                                `vote` int(11) NOT NULL DEFAULT '0', 
                                `ip` varchar(255) NOT NULL default '', 
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('cid'), $table_prefix . "critic_matic_rating");

    /*
      $rating_arr = array(
      'rating',
      'hollywood',
      'patriotism',
      'misandry',
      'affirmative',
      'lgbtq',
      'god',
      'vote');

      foreach ($rating_arr as $item) {
      $sql = "ALTER TABLE `" . $table_prefix . "critic_matic_rating` ADD `" . $item . "` int(11) NOT NULL DEFAULT '0'";
      Pdo_an::db_query($sql);
      }
      critic_matic_create_index_an($rating_arr, $table_prefix . "critic_matic_rating");

      // IP
      $sql = "ALTER TABLE `" . $table_prefix . "critic_matic_rating` ADD `ip` varchar(255) NOT NULL default ''";
      Pdo_an::db_query($sql);
      critic_matic_create_index_an(array('ip'), $table_prefix . "critic_matic_rating");

      // Critic IP
      $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_matic_ip`(
      `id` int(11) unsigned NOT NULL auto_increment,
      `type` int(11) NOT NULL DEFAULT '0',
      `ip` varchar(255) NOT NULL default '',
      PRIMARY KEY  (`id`)
      ) DEFAULT COLLATE utf8_general_ci;";
      Pdo_an::db_query($sql);
      critic_matic_create_index_an(array('type', 'ip'), $table_prefix . "critic_matic_ip");
     */
    /*
     * Indexes: 
     * 
      ALTER TABLE `wp_bcw98b_critic_matic_ip` ADD INDEX(`type`);
      ALTER TABLE `wp_bcw98b_critic_matic_ip` ADD INDEX(`ip`);

     */


    // Critic emotions
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_emotions`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `date` int(11) NOT NULL DEFAULT '0',
                                `pid` int(11) NOT NULL DEFAULT '0',
                                `aid` int(11) NOT NULL DEFAULT '0',
                                `vote` int(11) NOT NULL DEFAULT '0',                                
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('date', 'pid', 'aid', 'vote'), $table_prefix . "critic_emotions");

    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "critic_emotions_authors`(
				`id` int(11) unsigned NOT NULL auto_increment, 
                                `name` varchar(255) NOT NULL default '',	
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('name'), $table_prefix . "critic_emotions_authors");

    // Add actor slug
    //$sql = "ALTER TABLE `data_actors_all` ADD `slug` varchar(255) NOT NULL default ''";
    //Pdo_an::db_query($sql);
    //
    // Critic audence
    $sql = "CREATE TABLE IF NOT EXISTS  `" . $table_prefix . "meta_critic_author_key`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `aid` int(11) NOT NULL DEFAULT '0',
                                `name` varchar(255) NOT NULL default '',                              
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('aid', 'name'), $table_prefix . "meta_critic_author_key");


    // Add rating to meta
    //$sql = "ALTER TABLE `" . $table_prefix . "critic_matic_meta` ADD `rating` int(11) NOT NULL DEFAULT '0'";
    //$wpdb->query($sql);
    // Add top movie id to critic post
    //$sql = "ALTER TABLE `" . $table_prefix . "critic_matic_posts` ADD `top_movie` int(11) NOT NULL DEFAULT '0'";
    //$wpdb->query($sql);
    //Sphinx search
    $sql = "CREATE TABLE IF NOT EXISTS  `sph_counter`(
				`id` int(11) unsigned NOT NULL auto_increment,                                
                                `maxdocid` int(11) NOT NULL DEFAULT '0',  
                                `name` varchar(255) NOT NULL default '',	
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);

    // Movies meta
    $sql = "CREATE TABLE IF NOT EXISTS  `search_movies_meta`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `mid` int(11) NOT NULL DEFAULT '0', 
                                `date` int(11) NOT NULL DEFAULT '0',                                        
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";

    Pdo_an::db_query($sql);

    // Add fields
    $names = array('movies_an', 'critic', 'actor', 'director');
    foreach ($names as $name) {
        $sql = sprintf("SELECT name FROM sph_counter WHERE name='%s'", $name);
        $n = Pdo_an::db_get_var($sql);

        if (!$n) {
            $sql = sprintf("INSERT INTO sph_counter (maxdocid, name) VALUES (%d, '%s')", 0, $name);
            Pdo_an::db_query($sql);
        }
    }

    // Genres 
    $sql = "CREATE TABLE IF NOT EXISTS  `data_movie_genre`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `status` int(11) NOT NULL DEFAULT '1',
                                `weight` int(11) NOT NULL DEFAULT '0',
                                `name` varchar(255) NOT NULL default '',     
                                `slug` varchar(255) NOT NULL default '',                                
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";

    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('status', 'weight', 'name', 'slug'), "data_movie_genre");

    // Genres meta
    $sql = "CREATE TABLE IF NOT EXISTS  `meta_movie_genre`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `mid` int(11) NOT NULL DEFAULT '0', 
                                `gid` int(11) NOT NULL DEFAULT '0',                                        
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";

    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('mid', 'gid'), "meta_movie_genre");

    // Provider
    $sql = "CREATE TABLE IF NOT EXISTS  `data_movie_provider`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `pid` int(11) NOT NULL DEFAULT '0', 
                                `status` int(11) NOT NULL DEFAULT '1',
                                `free` int(11) NOT NULL DEFAULT '0',
                                `weight` int(11) NOT NULL DEFAULT '0',
                                `name` varchar(255) NOT NULL default '',                                     
                                `slug` varchar(255) NOT NULL default '',
                                `image` varchar(255) NOT NULL default '',     
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";

    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('pid', 'status', 'free', 'weight', 'name', 'slug'), "data_movie_provider");

    /*
      // Add genre status
      $sql = "ALTER TABLE `data_movie_genre` ADD `status` int(11) NOT NULL DEFAULT '1'";
      Pdo_an::db_query($sql);

      // Add genre weight
      $sql = "ALTER TABLE `data_movie_genre` ADD `weight` int(11) NOT NULL DEFAULT '0'";
      Pdo_an::db_query($sql);

      // Add provider status
      $sql = "ALTER TABLE `data_movie_provider` ADD `status` int(11) NOT NULL DEFAULT '1'";
      Pdo_an::db_query($sql);

      // Add provider weight
      $sql = "ALTER TABLE `data_movie_provider` ADD `weight` int(11) NOT NULL DEFAULT '0'";
      Pdo_an::db_query($sql);

      // Add provider free status
      $sql = "ALTER TABLE `data_movie_provider` ADD `free` int(11) NOT NULL DEFAULT '0'";
      Pdo_an::db_query($sql);
     * 
     */
    // Actors meta
    // mid - movie id
    // aid - actor id
    // type: 
    //  0 - No info
    //  1 - Star
    //  2 - Main
    //  3 - Extra
    $sql = "CREATE TABLE IF NOT EXISTS  `meta_movie_actor`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `mid` int(11) NOT NULL DEFAULT '0', 
                                `aid` int(11) NOT NULL DEFAULT '0',                                        
                                `pos` int(11) NOT NULL DEFAULT '0', 
                                `type` int(11) NOT NULL DEFAULT '0', 
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";

    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('mid', 'aid', 'pos', 'type'), "meta_movie_actor");

    // Director meta
    // mid - movie id
    // did - director id    
    $sql = "CREATE TABLE IF NOT EXISTS  `meta_movie_director`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `mid` int(11) NOT NULL DEFAULT '0', 
                                `aid` int(11) NOT NULL DEFAULT '0',                                        
                                `pos` int(11) NOT NULL DEFAULT '0', 
                                `type` int(11) NOT NULL DEFAULT '0',                                                                         
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('mid', 'aid', 'pos', 'type'), "meta_movie_director");


    // Country 
    $sql = "CREATE TABLE IF NOT EXISTS  `data_movie_country`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `status` int(11) NOT NULL DEFAULT '1',
                                `weight` int(11) NOT NULL DEFAULT '0',
                                `name` varchar(255) NOT NULL default '',     
                                `slug` varchar(255) NOT NULL default '',                                
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";

    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('status', 'weight', 'name', 'slug'), "data_movie_country");

    // Country meta
    $sql = "CREATE TABLE IF NOT EXISTS  `meta_movie_country`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `mid` int(11) NOT NULL DEFAULT '0', 
                                `cid` int(11) NOT NULL DEFAULT '0',                                        
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";

    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('mid', 'cid'), "meta_movie_country");


    // Actor gender. Local data
    // gender: 1 - male, 2 -female.
    /*
      $sql = "CREATE TABLE IF NOT EXISTS  `actor_name_unique`(
      `id` int(11) unsigned NOT NULL auto_increment,
      `name` varchar(100) NOT NULL default '',
      `gender` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY  (`id`)
      ) DEFAULT COLLATE utf8_general_ci;";

      Pdo_an::db_query($sql);
     */
    /* Actor auto gender
     * aid - actor id
     * gender: 1 - male, 2 - female
     * k - gender procent 0-100         
     */
    $sql = "CREATE TABLE IF NOT EXISTS  `data_actor_gender_auto`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `actor_id` int(11) NOT NULL DEFAULT '0', 
                                `gender` int(11) NOT NULL DEFAULT '0',                                                                        
                                `k` int(11) NOT NULL DEFAULT '0', 
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";

    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('actor_id', 'gender', 'k'), "data_actor_gender_auto");

    /*
     * Indexes: 
     * 
      ALTER TABLE `meta_movie_country` ADD INDEX(`mid`);
      ALTER TABLE `meta_movie_country` ADD INDEX(`cid`);

      ALTER TABLE `data_movie_country` ADD INDEX(`status`);
      ALTER TABLE `data_movie_country` ADD INDEX(`weight`);
      ALTER TABLE `data_movie_country` ADD INDEX(`name`);
      ALTER TABLE `data_movie_country` ADD INDEX(`slug`);
     */

    /*
     * Cpi
     */

    $sql = "CREATE TABLE IF NOT EXISTS  `data_cpi`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `type` int(11) NOT NULL DEFAULT '0', 
                                `year` int(11) NOT NULL DEFAULT '0',                                                                        
                                `cpi` float(24) NOT NULL DEFAULT '0', 
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";

    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('type', 'year'), "data_cpi");

    /*
     * Ethnicolr
     */
    $sql = "CREATE TABLE IF NOT EXISTS  `data_actors_ethnicolr`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `aid` int(11) NOT NULL DEFAULT '0', 
                                `date_upd` int(11) NOT NULL DEFAULT '0',                                                                        
                                `firstname` varchar(255) NOT NULL default '',  
                                `lastname` varchar(255) NOT NULL default '',  
                                `verdict` varchar(10) NOT NULL default '',    
                                `wiki` text default NULL,   
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";

    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('aid', 'date_upd'), "data_actors_ethnicolr");

    /*
     * Movie slugs
     */
    $sql = "CREATE TABLE IF NOT EXISTS  `data_movie_title_slugs`(
				`id` int(11) unsigned NOT NULL auto_increment,
                                `mid` int(11) NOT NULL DEFAULT '0',                                 
                                `oldslug` varchar(255) NOT NULL default '',                                                                      
                                `newslug` varchar(255) NOT NULL default '',                                                                      
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8_general_ci;";
    Pdo_an::db_query($sql);
    critic_matic_create_index_an(array('mid', 'oldslug', 'newslug'), "data_movie_title_slugs");
}

function critic_matic_create_index($names = array(), $table_name = '') {

    if ($names && $table_name) {
        foreach ($names as $name) {
            $index_sql = "SELECT COUNT(*)        
    FROM `INFORMATION_SCHEMA`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = '" . DB_NAME_WP . "' 
    AND `TABLE_NAME` = '$table_name'
    AND `INDEX_NAME` = '$name'";
            $exists = Pdo_wp::db_get_var($index_sql);

            if (!$exists) {
                $sql = "ALTER TABLE `$table_name` ADD INDEX(`$name`)";
                Pdo_wp::db_query($sql);
            }
        }
    }
}

function critic_matic_create_index_an($names = array(), $table_name = '') {

    if ($names && $table_name) {
        foreach ($names as $name) {
            $index_sql = "SELECT COUNT(*)        
    FROM `INFORMATION_SCHEMA`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = '" . DB_NAME_AN . "' 
    AND `TABLE_NAME` = '$table_name'
    AND `INDEX_NAME` = '$name'";
            $exists = Pdo_an::db_get_var($index_sql);

            if (!$exists) {
                $sql = "ALTER TABLE `$table_name` ADD INDEX(`$name`)";
                Pdo_an::db_query($sql);
            }
        }
    }
}

/*
 * Remove meata dublicates
 * 
 * SELECT fid, cid, count(*) FROM `wp_bcw98b_critic_matic_posts_meta` GROUP by fid, cid having count(*) > 1;
 * 
 * 
DELETE m FROM `wp_bcw98b_critic_matic_posts_meta` m
INNER JOIN `wp_bcw98b_critic_matic_posts_meta` s
WHERE 
    m.id < s.id AND 
    m.fid = s.fid AND 
    m.cid = s.cid;
 * 
 * SELECT mid, count(*) FROM `data_movie_title_slugs` GROUP by mid having count(*) > 1;
 * DELETE m FROM `data_movie_title_slugs` m
INNER JOIN `data_movie_title_slugs` s
WHERE 
    m.id < s.id AND 
    m.mid = s.mid;
 * 
 * 
 * //Author meta
 * 
 *  SELECT cid, count(*) FROM `wp_bcw98b_critic_matic_authors_meta` GROUP by cid having count(*) > 1;
 * 
 * DELETE m FROM `wp_bcw98b_critic_matic_authors_meta` m
INNER JOIN `wp_bcw98b_critic_matic_authors_meta` s
WHERE 
    m.id > s.id AND 
    m.cid = s.cid;
 * 
 * 109838
 * 
 * 115820
 */