<?php

/**
 * Description of CriticCommentsAdmin
 *
 * @author brahman
 */
class CriticCommentsAdmin extends AbstractDB {

    private $cm;
    private $db;

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            // CM
            'posts' => $table_prefix . 'critic_matic_posts',
            'meta' => $table_prefix . 'critic_matic_posts_meta',
            'rating' => $table_prefix . 'critic_matic_rating',
            'authors' => $table_prefix . 'critic_matic_authors',
            'authors_meta' => $table_prefix . 'critic_matic_authors_meta',
            'movies_meta' => $table_prefix . 'critic_movies_meta',
            'ip' => $table_prefix . 'critic_matic_ip',
            'author_key' => $table_prefix . 'meta_critic_author_key',
            // Comments
            'comments' => 'data_comments',
            'comments_num' => 'meta_comments_num',
        );
    }
}
