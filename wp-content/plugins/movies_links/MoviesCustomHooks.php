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

    public function add_actors($campaign = array(), $post = array(), $valid_actors = array()) {
        $options = unserialize($post->options);

        // Familysearch logic
        $this->update_familysearch($campaign, $post, $options, $valid_actors);
    }

    private function update_familysearch($campaign, $post, $options, $valid_actors = array()) {

        $score_opt = array(
            'topcountry' => 'topcountry',
            'country' => 'country'
        );

        $to_update = array();
        foreach ($score_opt as $post_key => $db_key) {
            if (isset($options[$post_key])) {
                $field_value = base64_decode($options[$post_key]);
                $to_update[$db_key] = $field_value;
            }
        }
        $topcountry = '';
        $country = '';
        if ($to_update) {
            $country_meta = array();
            $topcountry = '';
            if ($to_update['topcountry']) {
                $topcountry = trim($to_update['topcountry']);
            }

            if ($to_update['country']) {
                $country = $to_update['country'];
                if (strstr($country, ';')) {
                    $c_arr = explode(';', $country);
                    foreach ($c_arr as $value) {
                        if (strstr($value, ':')) {
                            $val_arr = explode(':', $value);
                            $c = trim($val_arr[0]);
                            if (!$topcountry) {
                                $topcountry = $c;
                            }
                            $t = (int) str_replace(',', '', trim($val_arr[1]));
                            $country_meta[] = array('c' => $c, 't' => $t);
                        }
                    }
                }
            }
        }

        $lastname = trim($post->title);

        if ($lastname && $topcountry) {
            /*
             * $lastname: Markovic
             * $topcountry: Croatia
             * $country_meta: Array
              (
              [0] => Array
              (
              [c] => Croatia
              [t] => 128
              )

              [1] => Array
              (
              [c] => Austria
              [t] => 81
              )

              )
             */

            $fs = $this->ml->get_campaing_mlr($campaign);
            if ($fs) {

                $lastname_id = $fs->get_lastname_id($lastname);

                if (!$lastname_id) {
                    // Add name to db
                    $top_country_id = $fs->get_or_create_country($topcountry);
                    $last_name_id = $fs->create_lastname($lastname, $top_country_id);

                    // Add meta
                    if ($country_meta) {
                        foreach ($country_meta as $item) {
                            $c = $item['c'];
                            $t = $item['t'];
                            $c_id = $fs->get_or_create_country($c);
                            $fs->add_country_meta($last_name_id, $c_id, $t);
                        }
                    }
                } else {
                    // Name already exist, no actions
                }
            }
        }
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
