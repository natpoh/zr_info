<?php

class CustomHooks {

    static $actions;

    private function init_actions() {
        self:: $actions = [];
        // Test functions from: /wp-content/plugins/critic_matic/cron/custom_hooks_test.php
        self::add_action('test_action', 'test_function');
        self::add_action('test_action', 'test_function_second');

        // Erating, run after update or add
        self::add_action('add_erating', 'add_erating');

        // Movieslinks custom hooks        
        self::add_action('ml_add_post', 'ml_custom_hooks');
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
        self::$actions[$tag][] = array('function' => 'CustomHooks::' . $function_to_add);
    }

    /*
     * Custom functions
     */

    private static function test_function($args = []) {
        if (isset($args['rating'])) {
            print $args['rating'];
        }
    }

    private static function test_function_second($args = []) {
        if (isset($args['rating'])) {
            print $args['rating'] * 10;
        }
    }

    private static function add_erating($args = []) {
        /*
         * args = ['mid'=>$mid,'data'=>$data]
         */
        if (isset($args['mid'])) {
            // print_r($args['data']);
        }
    }

    private static function ml_custom_hooks($args = []) {
        /*
         * args = ['campaign'=>$campaign,'post'=>$post]
         */
        if (isset($args['post']) && isset($args['campaign']) && isset($args['url'])) {
            $campaign = $args['campaign'];
            $post = $args['post'];
            $url = $args['url'];

            if ($campaign->id == 25) {
                // Ethnic
                if (!class_exists('CriticMatic')) {
                    return;
                }

                if ($post->top_movie > 0) {
                    $cm = new CriticMatic();
                    $ac = $cm->get_ac();
                    $ac->add_ethnic($post, $url);
                }
            }

            // $options = unserialize($post->options);
            // print_r(array('custom hooks', $post, $campaign));
            /*
             *     [0] => custom hooks
              [1] => stdClass Object
              (
              [id] => 120980
              [date] => 1642774810
              [last_upd] => 1643036884
              [uid] => 255688
              [top_movie] => 17136
              [rating] => 19
              [status] => 1
              [title] => The Emperors New Groove

              [rel] =>
              [year] => 0
              [options] => a:6
              [status_links] => 1
              [multi] => 0
              [version] => 0
              [pid] => 0
              )

              [2] => stdClass Object
              (
              [id] => 3
              [date] => 1641808083
              [status] => 0
              [title] => dove.org
              [site] => https://dove.org/
              [options] => a:8
              [type] => 0
              )
             */
            // TODO ml custom hooks logic
        }
    }

}
