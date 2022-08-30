<?php

class ActorWeight
{

public static function check_cr()
{
    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    }
    global $cr;
    global $cm;

    if (!class_exists('CriticTransit')) {
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractFunctions.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBAn.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDB.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMatic.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticSearch.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'SearchFacets.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTransit.php' );

        $cm = new CriticMatic();
        $cr = new CriticTransit($cm);
    }
    return $cr;
}

public static function update_actor_weight($actor_id,$debug=0,$sinch = 1,$count = 100)
{
    $cr  =self::check_cr();
    $cr->get_actors_meta($count, $debug , 0,$actor_id,$sinch);
}






}