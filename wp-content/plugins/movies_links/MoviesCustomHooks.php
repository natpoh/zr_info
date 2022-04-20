<?php

/*
 * Custom hook functions from Movies Links
 */

class MoviesCustomHooks {

    private $ml = '';

    public function __construct($ml) {
        $this->ml = $ml ? $ml : new MoviesLinks();
    }

    public function add_post($campaign = array(), $post = array()) {

        $options = unserialize($post->options);

        // Tomatoes logic
        $this->update_rotten_tomatoes($post, $options);
    }

    private function update_rotten_tomatoes($post, $options) {        
        
        $score_opt = array(
            'tomatometerScore' => 'rotten_tomatoes',
            'audienceScore' => 'rotten_tomatoes_audience'
        );

        $to_update = array();
        foreach ($score_opt as $post_key => $db_key) {
            if (isset($options[$post_key])) {
                $field_value = base64_decode($options[$post_key]);
                $to_update[$db_key] = (int) $field_value;
            }
        }
        if ($to_update) {
            $ma = $this->ml->get_ma();
            $ma->update_movie_rating($post->top_movie, $to_update);           
        }
    }

}
