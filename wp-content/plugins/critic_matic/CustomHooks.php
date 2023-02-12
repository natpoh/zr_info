<?php

class CustomHooks {

    static $actions;

    private function init_actions() {
        self:: $actions = [];
        // Udate erating
        self::add_action('erating', 'add_erating');
        
    }        

    public static function do_action($tag, $args = '') {
        self::init_actions();        
        
        if (self::$actions[$tag]) {
            foreach (self::$actions[$tag] as $item) {
                call_user_func_array($item['function'], [$args]);
            }
        }
    }

    private static function add_action($tag, $function_to_add) {
        self::$actions[$tag][] = array('function' => 'CustomHooks::'.$function_to_add);
    }

    /*
     * Custom functions
     */

    private static function add_erating($args) {
        if (isset($args['rating'])){
            print $args['rating'];
        }
    }

}
