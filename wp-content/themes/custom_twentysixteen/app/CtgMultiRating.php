<?php

class CtgMultiRating {

    var $review;
    var $user_votes;
    var $visitor_votes;
    var $votes;
    var $user_rating = 0;
    var $visitor_rating = 0;
    var $rating = 0;

    /**
     * Class constructor.
     *
     * @param object $post_data multi rating results from the database
     * @param int $set_id multi rating set id
     */
    function CtgMultiRating($post_data) {
        $this->review = $post_data->average_review;
        $this->user_votes = $post_data->total_votes_users;
        $this->visitor_votes = $post_data->total_votes_visitors;
        $this->votes = $post_data->total_votes_users + $post_data->total_votes_visitors;
        $this->user_rating = $post_data->average_rating_users;
        $this->visitor_rating = $post_data->average_rating_visitors;
        $totals = $this->user_rating * $this->user_votes + $this->visitor_rating * $this->visitor_votes;
        if ($this->votes > 0)
            $this->rating = number_format($totals / $this->votes, 1);
    }

}