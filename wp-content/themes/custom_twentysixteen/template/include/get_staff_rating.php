<?php

class StaffRating
{

    public function get_staff_rating($content)
    {
        preg_match("/^(<img.*width=\"50%\">)<div>/", $content, $ratings);
        if (!isset($ratings) || empty($ratings)) {
            preg_match("/^(<img.*width=\"50%\">)<p>/", $content, $ratings);
        }
        if (!isset($ratings) || empty($ratings)) {
            preg_match("/^<p>(<img.*width=\"50%\">)/", $content, $ratings);
        }
        if (!isset($ratings) || empty($ratings)) {
            preg_match("/(<img.*width=\"50%\">)/", $content, $ratings);
        }

        if (isset($ratings) && !empty($ratings)) {
            $rating_str = $ratings[1];
        }


        if ($rating_str) {

            preg_match("/wp-content\/uploads\/2017\/01\/01_star_(\d)_and_(\d)half_out_of_5/", $rating_str, $worthwhile);
            preg_match("/wp-content\/uploads\/2017\/01\/02_poop_(\d)_and_(\d)half_out_of_5/", $rating_str, $hollywood);
            preg_match("/wp-content\/uploads\/2017\/02\/03_PTRT_(\d)_and_(\d)half_out_of/", $rating_str, $patriotism);
            preg_match("/wp-content\/uploads\/2017\/01\/04_CNT_(\d)_and_(\d)half_out_of_5/", $rating_str, $misandry);
            preg_match("/wp-content\/uploads\/2017\/01\/05_profit_muhammad_(\d)_and_(\d)half_out_of_5/", $rating_str, $affirmative);
            preg_match("/wp-content\/uploads\/2017\/01\/06_queer_(\d)_and_(\d)half_out_of_5/", $rating_str, $lgbtq);
            preg_match("/wp-content\/uploads\/2017\/01\/07_cliche_not_brave_(\d)_and_(\d)half_out_of_5/", $rating_str, $god);

            if (preg_match("/2017\/02\/slider_green_pay_drk.png/", $rating_str)) {
                $pay = 3;
            } else if (preg_match("/2017\/01\/slider_orange_free.png/", $rating_str)) {
                $pay = 2;
            } else if (preg_match("/2017\/02\/slider_red_skip_drk.png/", $rating_str)) {
                $pay = 1;
            }


            $rating_array = array('stars'=>$worthwhile[1].'.'.$worthwhile[2] ,'vote'=>$pay ,
                'hollywood'=>$hollywood[1].'.'.$hollywood[2],'misandry'=> $misandry[1].'.'.$misandry[2] ,
                'lgbtq'=> $lgbtq[1].'.'.$lgbtq[2],'patriotism'=> $patriotism[1].'.'.$patriotism[2] ,
                'affirmative'=>$affirmative[1].'.'.$affirmative[2] ,'god'=> $god[1].'.'.$god[2] );


            $vote = '';
            if ($pay) {

                $vote = self::rating_images('vote', $pay);
            } else {
                $vote = '';
            }


            if ($worthwhile[1]) {

                $stars = self::rating_images('rating', $worthwhile[1], $worthwhile[2]);
            } else {
                $stars = '';
            }

            if ($hollywood[1]) {
                $hollywood = self::rating_images('hollywood', $hollywood[1], $hollywood[2]);
            } else {
                $hollywood = '';
            }
            if ($affirmative[1]) {
                $affirmative = self::rating_images('affirmative', $affirmative[1], $affirmative[2]);
            } else {
                $affirmative = '';
            }

            if ($god[1]) {
                $god = self::rating_images('god', $god[1], $god[2]);
            } else {
                $god = '';
            }

            if ($lgbtq[1]) {
                $lgbtq = self::rating_images('lgbtq', $lgbtq[1], $lgbtq[2]);
            } else {
                $lgbtq = '';
            }

            if ($misandry[1]) {
                $misandry = self::rating_images('misandry', $misandry[1], $misandry[2]);
            } else {
                $misandry = '';
            }
            if ($patriotism[1]) {
                $patriotism = self::rating_images('patriotism', $patriotism[1], $patriotism[2]);
            } else {
                $patriotism = '';
            }


            $content_rating = '<div class="vote">' . $stars . $vote . $hollywood . $misandry . $lgbtq . $patriotism . $affirmative . $god . '</div>';


            return array('array'=>$rating_array,'data' =>$content_rating);
        }
    }

    public function rating_images($type, $rating, $subrating = 0)
    {

        if ($subrating == 0) {
            $rating = round($rating, 0);
        }

        $siteurl = WP_SITEURL;


        switch ($type) {

            case "rating":
                $image_path = $siteurl . "/wp-content/uploads/2017/01/01_star_" . $rating . "_and_" . $subrating . "half_out_of_5.png";
                break;

            case "hollywood":
                $image_path = $siteurl . "/wp-content/uploads/2017/01/02_poop_" . $rating . "_and_" . $subrating . "half_out_of_5.png";
                break;
            case "patriotism":
                $image_path = $siteurl . "/wp-content/uploads/2017/02/03_PTRT_" . $rating . "_and_" . $subrating . "half_out_of_5.png";
                break;
            case "misandry":
                $image_path = $siteurl . "/wp-content/uploads/2017/01/04_CNT_" . $rating . "_and_" . $subrating . "half_out_of_5.png";
                break;
            case "affirmative":
                $image_path = $siteurl . "/wp-content/uploads/2017/01/05_profit_muhammad_" . $rating . "_and_" . $subrating . "half_out_of_5.png";
                break;
            case "lgbtq":
                $image_path = $siteurl . "/wp-content/uploads/2017/01/06_queer_" . $rating . "_and_" . $subrating . "half_out_of_5.png";
                break;
            case "god":
                $image_path = $siteurl . "/wp-content/uploads/2017/01/07_cliche_not_brave_" . $rating . "_and_" . $subrating . "half_out_of_5.png";
                break;


            case "audience_vote":
                if ($rating == 1) {
                    $image_path = "https://zeitgeistreviews.com/wp-content/uploads/2017/02/slider_green_pay_drk.png";
                } else if ($rating == 3) {
                    $image_path = "https://zeitgeistreviews.com/wp-content/uploads/2017/01/slider_orange_free.png";
                } else if ($rating == 2) {
                    $image_path = "https://zeitgeistreviews.com/wp-content/uploads/2017/02/slider_red_skip_drk.png";
                }
                break;


            case "vote":
                if ($rating == 3) {
                    $image_path = "https://zeitgeistreviews.com/wp-content/uploads/2017/02/slider_green_pay_drk.png";
                } else if ($rating == 2) {
                    $image_path = "https://zeitgeistreviews.com/wp-content/uploads/2017/01/slider_orange_free.png";
                } else
                    $image_path = "https://zeitgeistreviews.com/wp-content/uploads/2017/02/slider_red_skip_drk.png";

                break;
        }


        return '<img class="rating_image" src="' . $image_path . '" /> ';


    }

}
