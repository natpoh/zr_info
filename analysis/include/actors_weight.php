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


public static function check_actor_weight()
{
//    $cr  =self::check_cr();
//    $af = $cr->cm->get_af();
//    $priority = $af->race_weight_priority;
//    if ($ss['an_weightid'] > 0) {
//        $ma = $cr->cm->get_ma();
//        $rule = $ma->get_race_rule_by_id($ss['an_weightid']);
//        if ($rule) {
//            $priority = json_decode($rule->rule, true);
//        }
//    }
//
//    $af->show_table_weight_priority($priority);
//

}


public static function update_actor_weight($actor_id,$debug=0,$sinch = 1,$count = 100, $force=false,$onlydata=0)
{
    $cr  =self::check_cr();
    $result = $cr->get_actors_meta($count, $debug , $force,$actor_id,$sinch,$onlydata);
    return $result;
}






}