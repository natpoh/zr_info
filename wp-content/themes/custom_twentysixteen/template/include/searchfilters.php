<?php

// UNUSED
const GROUP_PREFIX = 'group_';
const CATEGORY_PREFIX = 'category_';


/*
 * Functions
 */

function is_group($val) {
    preg_match("#" . GROUP_PREFIX . "(.+)#", $val, $mach);
    return $mach;
}

function is_audience($val) {
    $mach = is_group($val);
    return $mach && $mach[1] == 'audience';
}

function is_staff($val) {
    $mach = is_group($val);
    return $mach && $mach[1] == 'staff';
}

function is_category_($val) {
    preg_match("#" . CATEGORY_PREFIX . "(.+)#", $val, $mach);
    return $mach;
}

function get_critic_val($status) {
    switch ($status) {
        case 'staff': return 0;
        case 'pro': return 1;
    }

    return $status;
}

function is_exists_audience($filters) {
    foreach ($filters as $filter) {
        if (is_audience($filter)) {
            return true;
        }
    }

    return false;
}

function is_exists_critics($filters) {
    foreach ($filters as $filter) {
        $mach = is_group($filter);

        if ($mach && ($mach[1] == 'pro' || $mach[1] == 'staff')) {
            return true;
        }
    }

    return false;
}

if (!function_exists('custom_wprss_pagination')) {

    function custom_wprss_pagination($all, $x, $prev, $curr_link) {
        $link = $_SERVER['PHP_SELF'];

        $reg = '#(\/page([\d]+)*(\/)*)$#';
        $link = preg_replace($link, $reg, '');

        $link = $link . 'page';

        if (!$curr_link)
            $curr_link = 1;

        $first = $curr_link - $prev;
        if ($first < 1)
            $first = 1;

        $last = $curr_link + $prev;
        if ($last > ceil($all / $x))
            $last = ceil($all / $x);

        $result = '<div style="text-align: center;margin-top: 20px;margin-bottom: 10px;" class="wprss_search_pagination pt-cv-wrapper"><ul class="pt-cv-pagination pagination">';

        if ($curr_link > 1) {
            $result .= "<li class=\"cv-pageitem-prev\"><a id='previous' title='Go to previous page' href=\"" . $link . ($curr_link - 1) . "\"><</a></li> ";
        }

        $y = 1;

        if ($first > 1)
            $result .= "<li class='cv-pageitem-number'><a id='$y' title='Go to page $y' href=\"$link$y\">1</a></li> ";

        $y = $first - 1;

        if ($first > 6) {
            $result .= "<li class=\"cv-pageitem-number\"><a>...</a></li> ";
        } else {
            for ($i = 2; $i < $first; $i++) {
                $result .= "<li class='cv-pageitem-number'><a id='$i' title='Go to page $i'  href=\"$link" . $i . "\">$i</a></li> ";
            }
        }

        for ($i = $first; $i < $last + 1; $i++) {
            if ($i == $curr_link) {
                $result .= "<li class=\"cv-pageitem-number active\"><a id='$i' title='Current page is $i' href=\"$link" . $i . "\">$i</a></li> ";
            } else {
                $result .= "<li class='cv-pageitem-number'><a id='$i' title='Go to page $i' href=\"$link" . $i . "\">$i</a></li> ";
            }
        }

        $y = $last + 1;

        if ($last < ceil($all / $x) && ceil($all / $x) - $last > 0) {
            $result .= "<li class=\"cv-pageitem-number\"><a>...</a></li> ";
        }

        $e = ceil($all / $x);

        if ($last < ceil($all / $x)) {
            $result .= "<li  title='Go to page $e' class='cv-pageitem-number'><a id='$e' href=\"$link" . $e . "\">$e</a></li>";
        }

        if ($curr_link < $last) {
            $result .= "<li class=\"cv-pageitem-next\"><a id='nextpage' title='Go to next page' href=\"" . $link . ($curr_link + 1) . "\">></a></li> ";
        }

        $result .= '</ul></div>';

        return ($result);
    }

}
if (!function_exists('getmetarequest')) {

    function getmetarequest($filters, $metakey, $termarray, $arrayrequest, $mt, $like, $stuff = '', $filter_type = '') {
        global $table_prefix;

        $where = '';
        $join = '';

        $where_group = '';
        $join_group = '';

        $where_category = '';
        $join_category = '';

        if (is_array($filters)) {
            $is_exist_group = false;
            $is_exist_category = false;

            if ($stuff != 'none') {
                foreach ($filters as $val) {
                    $is_group = boolval(is_group($val));
                    $is_category = boolval(is_category_($val));

                    if ($is_group) {
                        $is_exist_group = true;
                    } else if ($is_category) {
                        $is_exist_category = true;
                    } else {
                        /*
                          if ($metakey=="_wpmoly_movie_genres")
                          {
                          $val =$termarray[$val];
                          }
                         */


                        if ($stuff == 8) {
                            $where .= " OR " . $mt . ".provider  = '" . $val . "' ";
                        } else if ($like == '=') {
                            $where .= " OR " . $mt . ".meta_value = '" . $val . "' ";
                        } else if ($like == 'like') {
                            if ($stuff == 4) {
                                $where .= " OR " . $mt . "t.meta_value like '%" . $val . "%' ";
                            } else if ($stuff == 3) {
                                $where .= " OR " . $mt . "r.meta_value like '%" . $val . "%' ";
                            } else {
                                $where .= " OR " . $mt . ".meta_value like '%" . $val . "%' ";
                            }
                        } else if ($like == 'like%') {
                            $where .= " OR " . $mt . ".meta_value like '" . $val . "%' ";
                        } else if ($like == '>') {
                            $where .= " OR " . $mt . ".meta_value > '" . $val . "' ";
                        } else if ($like == '<') {
                            $where .= " OR " . $mt . ".meta_value < '" . $val . "' ";
                        } else if ($like == '>1') {
                            $where .= " OR ( " . $mt . "pgr.pgrating  >= '" . $val . "' AND  " . $mt . "pgr.pgrating  < '" . ($val + 1) . "')  ";
                        }
                    }
                }
            }

            if ($stuff == 2) {

                //pro

                $join = "INNER JOIN " . $table_prefix . "postmeta AS " . $mt . "c ON (" . $mt . "c.meta_value = " . $table_prefix . "posts.post_title and " . $mt . "c.meta_key = 'wprss_item_movie') INNER JOIN " . $table_prefix . "postmeta AS " . $mt . " ON (" . $mt . "c.post_id = " . $mt . ".post_id) ";

                //   $join = "INNER JOIN " . $table_prefix . "movie_rss_category  AS " . $mt . "cat ON (" . $mt . "cat.title = " . $table_prefix . "posts.post_title )";  ///and " . $mt . "cat.category = 'Proper Review')
                ///   $join.= " INNER JOIN " . $table_prefix . "cache_wprss_feed_item_fast AS " . $mt . "cache  ON (" . $mt . "cat.rss_id = " . $mt . "cache.post_id) ";
            } else if ($stuff == 3) {
                $join = "INNER JOIN " . $table_prefix . "postmeta AS " . $mt . "r ON (" . $mt . "c.post_id = " . $mt . "r.post_id) ";
            } else if ($stuff == 4) {
                $join = "INNER JOIN " . $table_prefix . "postmeta AS " . $mt . "t ON (" . $mt . "c.post_id = " . $mt . "t.post_id) ";
            } else if ($stuff == 5) {
                $join = "INNER JOIN " . $table_prefix . "postmeta AS " . $mt . "i ON (" . $mt . "c.post_id = " . $mt . "i.post_id) ";
            } else if ($stuff == 6) {
                $join = "INNER JOIN " . $table_prefix . "posts AS " . $mt . "f ON (" . $mt . ".post_id = " . $mt . "f.ID) ";
            } else if ($stuff == 7) {
                $join = "INNER JOIN " . $table_prefix . "cache_wprss_feed_item_fast  AS " . $mt . "cmr ON (" . $mt . "cmr.wprss_item_movie_id  = " . $table_prefix . "posts.ID ) ";
            } else if ($stuff == 8) {
                $join = "INNER JOIN " . $table_prefix . "cache_just_wach  AS " . $mt . " ON (" . $mt . ".rwt_id  = " . $table_prefix . "posts.ID ) ";
            } else if ($stuff == 9) {
                $join = "INNER JOIN " . $table_prefix . "post_pg_rating AS " . $mt . "pgr ON (" . $mt . "pgr.movie_id  = " . $table_prefix . "posts.ID ) ";
            } else if ($stuff == 1) {
                $join = "INNER JOIN " . $table_prefix . "postmeta AS " . $mt . "x ON (" . $table_prefix . "posts.ID = " . $mt . "x.meta_value and " . $mt . "x.meta_key = 'wpcr3_review_post') INNER JOIN " . $table_prefix . "postmeta AS " . $mt . " ON (" . $mt . "x.post_id = " . $mt . ".post_id) ";
            } else {
                $join = "INNER JOIN " . $table_prefix . "postmeta AS " . $mt . " ON (" . $table_prefix . "posts.ID = " . $mt . ".post_id) ";
            }

            if (!$is_exist_group && !$is_exist_category) {
                $where = substr($where, 3);

                if ($stuff == 'none') {
                    $where = " and (" . $mt . ".meta_key = '" . $metakey . "') ";
                } else if ($stuff == 4) {
                    $where = " and (" . $mt . "t.meta_key = '" . $metakey . "' and (" . $where . ") ) ";
                } else if ($stuff == 3) {
                    $where = " and (" . $mt . "r.meta_key = '" . $metakey . "' and (" . $where . ") ) ";
                } else if ($stuff == 5) {
                    $where = " and (" . $mt . "i.meta_key = '" . $metakey . "') ";
                } else if ($stuff == 6) {
                    $where = "  ";
                } else if ($stuff == 7) {
                    $where = "  ";
                } else if ($stuff == 8) {

                    $where = " and (" . $where . ") ";
                } else if ($stuff == 9) {

                    $where = " and (" . $where . ") ";
                } else {
                    $where = " and (" . $mt . ".meta_key = '" . $metakey . "' and (" . $where . ") ) ";
                }
            }

            // for filter by some groups
            if ($is_exist_group) {
                $mtgroup = GROUP_PREFIX . $mt;

                if ($stuff != 'none') {
                    foreach ($filters as $val) {
                        $mach = is_group($val);

                        if ($mach) {
                            if ($stuff == 2) {

                                if ($filter_type == 'critics') {
                                    if ($mach && $mach[1] != 'audience') {
                                        $where_group .= " OR " . $mtgroup . ".meta_value = '" . get_critic_val($mach[1]) . "'";
                                    }
                                }
                            }
                        }
                    }
                }

                if ($stuff == 2) {
                    if ($filter_type == 'critics') {
                        if (is_exists_critics($filters)) {
                            $join_group .= "INNER JOIN " . $table_prefix . "postmeta AS " . $mtgroup . " ON (" . $mt . ".meta_value = " . $mtgroup . ".post_id) ";
                        }

                        if (is_exists_audience($filters)) {
                            $join_group .= "INNER JOIN " . $table_prefix . "postmeta AS " . $mtgroup . "ca ON (" . $table_prefix . "posts.ID = " . $mtgroup . "ca.meta_value) ";
                        }
                    }
                }

                $where_group = substr($where_group, 3);

                if ($stuff == 2) {
                    if ($filter_type == 'critics') {
                        if (is_exists_critics($filters)) {
                            $where_group = " OR (" . $mt . ".meta_key = '" . $metakey . "' and 
                                                 " . $mtgroup . ".meta_key = 'wprss_feed_from' and (" . $where_group . ") )";
                        }

                        if (is_exists_audience($filters)) {
                            $where_group .= " OR " . $mtgroup . "ca.meta_key = 'wpcr3_review_post'";

                            if (is_exists_critics($filters)) {
                                $where_group = substr($where_group, 3);
                                $where_group = ' OR (' . $where_group . ')';
                            }
                        }
                    }
                }

                if (!$where) {
                    $where_group = preg_replace('/OR/', 'and', $where_group, 1);
                } else {
                    $where = preg_replace('/OR/', 'and (', $where, 1);
                    $where_group .= ' )';
                }
            }

            // for filter by some taxonomy
            if ($is_exist_category) {
                $mtcategory = CATEGORY_PREFIX . $mt;

                if ($stuff != 'none') {
                    foreach ($filters as $val) {
                        $mach = is_category_($val);

                        if ($mach) {
                            if ($stuff == 2) {

                                if ($filter_type == 'critics') {
                                    $where_category .= " OR " . $mtcategory . ".slug = '" . $mach[1] . "'";
                                }
                            }
                        }
                    }
                }

                if ($stuff == 2) {
                    if ($filter_type == 'critics') {
                        $join_category .= "INNER JOIN " . $table_prefix . "term_relationships AS tr ON (" . $mt . ".meta_value = tr.object_id) INNER JOIN " . $table_prefix . "term_taxonomy AS tt ON (tt.term_taxonomy_id = tr.term_taxonomy_id and tt.taxonomy = 'wprss_feed_category') INNER JOIN " . $table_prefix . "terms AS " . $mtcategory . " ON (tt.term_id = " . $mtcategory . ".term_id)";
                    }
                }

                $where_category = substr($where_category, 3);

                if ($stuff == 2) {
                    if ($filter_type == 'critics') {
                        $where_category = " OR (" . $mt . ".meta_key = '" . $metakey . "' and (" . $where_category . ") )";
                    }
                }

                if (!$where) {
                    $where_category = preg_replace('/OR/', 'and', $where_category, 1);
                } else {
                    $where = preg_replace('/OR/', 'and (', $where, 1);
                    $where_category .= ' )';
                }
            }
        }

        $arrayrequest['where'] .= $where . $where_group . $where_category;
        $arrayrequest['join'] .= $join . $join_group . $join_category;

        return $arrayrequest;
    }

}


/*
 * Logic
 */

$arrayrequest = array();
$cm_new_api = false;

if (isset($_POST['filters'])) {
    $filters = $_POST['filters'];
//var_dump($filters);
    if (is_string($filters)) {
        $filters = json_decode($filters);
    } else if (is_array($filters)) {
        $filters = (object) $filters;
    }

    /*
     * New api
     */
    global $cfront, $cm_new_api;

    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    }

    if (!class_exists('CriticFront')) {
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
    }


    $cfront = new CriticFront();

    $cm_new_api = true;



    /*
     * Critic posts
     */
    $sql = '';
    if ($cm_new_api) {

        global $s_posts_per_page, $s_page, $total_search_count;

        // Limit
        $s_posts_per_page = 20;

        // Start page
        if ($filters->page || (isset($_POST['page']))) {
            $page = $filters->page;
            if (!$page) {
                $page = $_POST['page'];
            }
            if (!$page) {
                $page = 1;
            }
            $page = intval($page);
            $start = $s_posts_per_page * $page - $s_posts_per_page;
        }
        $s_page = $page;
        if ($start < 0 || !$start)
            $start = 0;

        if ($filters->critics) {
            //Filter by movie
            $movie_id = 0;
            if (isset($filters->movie[0])) {
                $slug = $filters->movie[0];
                //$movie = $cfront->get_movie_by_slug($slug);
                if ($movie) {
                    $movie_id = $movie->ID;
                }
            }

            // Redirect for old api
            if (in_array('group_staff', $filters->critics) && in_array('group_pro', $filters->critics) && in_array('group_audience', $filters->critics)) {
                $url = 'https://' . $_SERVER['HTTP_HOST'] . '/critics/all/';
                wp_redirect($url, 301);
                exit();
            }

            foreach ($filters->critics as $v) {
                if (is_numeric($v)) {
                    // Single critic post. Return                           
                    return;
                }

                if ($v == 'all' || $v == 'group_staff' || $v == 'group_pro' || $v == 'group_audience') {
                    // All posts
                    $type = -1;
                    if ($v == 'group_staff') {
                        // Staff posts
                        $type = 0;
                    } else if ($v == 'group_pro') {
                        // Pro posts
                        $type = 1;
                    } else if ($v == 'group_audience') {
                        // Audience posts
                        $type = 2;
                    }

                    $result = $cfront->get_sql_last_posts($type, $start, $s_posts_per_page, $movie_id);

                    $sql = $result['sql'];
                    $total_search_count = $result['total_count'];
                    break;
                } else if (strstr($v, 'category_')) {
                    $cat_result = substr($v, 9);
                    $cat = $cfront->cm->get_tag_by_slug($cat_result);
                    if ($cat) {
                        $type = -1;
                        $result = $cfront->get_sql_last_posts($type, $start, $s_posts_per_page, $movie_id, $cat->id);
                        $sql = $result['sql'];
                        $total_search_count = $result['total_count'];
                        break;
                    }
                }
            }
        }

        if ($filters->keyword) {
            return;
        }

        if ($sql) {
            return;
        }
    }


    /*
     * Old api
     */

///var_dump($filters);

    if ($filters->streaming) {
        $arrayrequest = getmetarequest($filters->streaming, "provider", '', $arrayrequest, 'mts', '=', 8);
    }

    if ($filters->genre) {
        /*
          global $table_prefix;
          $sql = "SELECT ".$table_prefix."terms.term_id , name FROM ".$table_prefix."term_taxonomy, ".$table_prefix."terms
          WHERE ".$table_prefix."term_taxonomy.taxonomy = 'genre' and ".$table_prefix."term_taxonomy.term_id = ".$table_prefix."terms.term_id";


          if ($pdo) {
          $q = $pdo->prepare($sql);
          $q->execute();
          $q->setFetchMode(PDO::FETCH_ASSOC);

          while ($r = $q->fetch()) {
          $termarray[$r['term_id']] = $r['name'];
          }
          } else {
          global $wpdb;

          if ($wpdb) {
          $posts = $wpdb->get_results($sql);

          if (is_array($posts)) {
          foreach ($posts as $val) {
          $termarray[$val->term_id] = $val->name;
          }
          }
          }
          }
         */

        $arrayrequest = getmetarequest($filters->genre, "_wpmoly_movie_genres", '', $arrayrequest, 'mt1', 'like');
    }

    if ($filters->release_date) {
        $like = 'like';

        // for dates: 80s, 90s, 2000s, etc.
        if (substr($filters->release_date[0], -1) == 's') {
            $filters->release_date[0] = substr($filters->release_date[0], 0, 3);
            $like .= '%';
        }

        $arrayrequest = getmetarequest($filters->release_date, "_wpmoly_movie_release_date", '', $arrayrequest, 'mt2', $like);
    }

    if ($filters->cast) {
        $arrayrequest = getmetarequest($filters->cast, "_wpmoly_movie_cast", '', $arrayrequest, 'mt3', 'like');
    }

    if ($filters->director) {
        $arrayrequest = getmetarequest($filters->director, "_wpmoly_movie_director", '', $arrayrequest, 'mt4', '=');
    }

    if ($filters->movie_rating) {
        $arrayrequest = getmetarequest($filters->movie_rating, "_wpmoly_movie_rating", '', $arrayrequest, 'mt5', 'like%');
    }
    if ($filters->movie_pg_rating) {

        $arrayrequest = getmetarequest($filters->movie_pg_rating, "", '', $arrayrequest, '', '>1', 9);
    }

    $x = 6;

    ///wpcr3_review

    if ($filters->audience_rating) {
        foreach ($filters->audience_rating as $val) {
            $x++;

            $stuff = '';
            $type = substr($val, 0, strpos($val, '-'));
            $rating = end(explode('-', $val));
            $value = array();

            if ($type == 'rating_vote') {
                $key = '_wpmoly_movie_rating';

                switch ($rating) {
                    case 3:
                        $value[] = 5;
                        break;
                    case 2:
                        $value[] = 4;
                        $value[] = 3;
                        break;
                    case 1:
                        $value[] = 2;
                        $value[] = 1;
                        break;
                }
            } else {
                $stuff = 1;
                $key = 'wpcr3_review_' . $type;
                $value[] = $rating;
            }

            $arrayrequest = getmetarequest($value, $key, '', $arrayrequest, 'mt' . $x, 'like%', $stuff);

            // max and min rate (deprecated)
//            if (strstr($val, '-max')) {
//                $x++;
//                $key = substr($val, 0, strpos($val, '-'));
//                $key = 'wpcr3_review_' . $key;
//                $value = end(explode('-', $val));
//                $arrayrequest = getmetarequest($value, $key, '', $arrayrequest, 'mt' . $x, 'like%', 1);
//            } else if (strstr($val, '-min')) {
//                $x++;
//                $key = substr($val, 0, strpos($val, '-min'));
//                $key = 'wpcr3_review_' . $key;
//                $value = array(0, 1);
//                $arrayrequest = getmetarequest($value, $key, '', $arrayrequest, 'mt' . $x, 'like%', 1);
//            }
        }
    }

    if ($filters->staff_rating) {
        foreach ($filters->staff_rating as $val) {
            $x++;

            $stuff = '';
            $type = substr($val, 0, strpos($val, '-'));
            $rating = end(explode('-', $val));
            $value = array();

            if ($type == 'rating_vote') {
                $key = '_wpmoly_movie_rating';

                switch ($rating) {
                    case 3:
                        $value[] = 5;
                        break;
                    case 2:
                        $value[] = 4;
                        $value[] = 3;
                        break;
                    case 1:
                        $value[] = 2;
                        $value[] = 1;
                        break;
                }
            } else {
                $stuff = 1;
                $key = 'wpcr3_review_' . $type;
                $value[] = $rating;
            }

            $arrayrequest = getmetarequest($value, $key, '', $arrayrequest, 'mt' . $x, 'like%', $stuff);

            // max and min rate (deprecated)
//            if (strstr($val, '-max')) {
//                $x++;
//                $key = substr($val, 0, strpos($val, '-max'));
//                $key = 'wpcr3_review_' . $key;
//                $value = array(4, 5);
//                $arrayrequest = getmetarequest($value, $key, '', $arrayrequest, 'mt' . $x, 'like%', 1);
//            } else if (strstr($val, '-min')) {
//                $x++;
//                $key = substr($val, 0, strpos($val, '-min'));
//                $key = 'wpcr3_review_' . $key;
//                $value = array(0, 1);
//                $arrayrequest = getmetarequest($value, $key, '', $arrayrequest, 'mt' . $x, 'like%', 1);
//            }
        }
    }

    /*
      'movie_asc' => 'Movie Title (A-Z)',
      'movie_desc' => 'Movie Title (Z-A)',
      'movie_rating_desc' => 'Movie Rating (5-1)',
      'movie_rating_asc' => 'Movie Rating (1-5)',
      'movie_date_desc' => 'Release Date (Newest)',
      'movie_date_asc' => 'Release Date (Oldest)',
      'review_date_desc' => 'Review Date (Newest)',
      'review_date_asc' => 'Review Date (Oldest)',
     */

    global $table_prefix;

    $order_by = " " . $table_prefix . "posts.post_title ASC ";
    $group_by = " " . $table_prefix . "posts.ID";

    if ($filters->sort_by) {
        if ($filters->sort_by == 'movie_asc') {
            $order_by = " " . $table_prefix . "posts.post_title ASC ";
        } else if ($filters->sort_by == 'movie_desc') {
            $order_by = " " . $table_prefix . "posts.post_title DESC ";
        } else if ($filters->sort_by == 'movie_date_desc' || $filters->sort_by == 'movie_date_asc' || $filters->sort_by == 'movie_date_last') {
            if (!$filters->release_date) {
                $arrayrequest = getmetarequest(array(0 => 1), "_wpmoly_movie_release_date", '', $arrayrequest, 'mt2', 'like', 'none');
            }

            $order_by = " CASE WHEN mt2.meta_value REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' > 0 THEN STR_TO_DATE(mt2.meta_value, '%Y-%m') 
                               ELSE STR_TO_DATE(" . $table_prefix . "posts.post_date,'%Y-%m') END ";

            if ($filters->sort_by == 'movie_date_last') {

                /// $arrayrequest = getmetarequest($filters->release_date, "_wpmoly_movie_release_date", '', $arrayrequest, 'mtr', '>');
                $arrayrequest['where'] = ($arrayrequest['where']) . " and UNIX_TIMESTAMP(mt2.meta_value) <  '" . time() . "' ";
            }

            if ($filters->sort_by == 'movie_date_desc' || $filters->sort_by == 'movie_date_last') {
                $order_by .= 'DESC ';
            } else if ($filters->sort_by == 'movie_date_asc') {
                $order_by .= 'ASC ';
            }
        } else if ($filters->sort_by == 'movie_rating_desc' || $filters->sort_by == 'movie_rating_asc') {
            if (!$filters->movie_rating) {
                $join .= "INNER JOIN " . $table_prefix . "postmeta as mt5 ON (" . $table_prefix . "posts.ID = mt5.post_id and mt5.meta_key = '_wpmoly_movie_rating') ";
            }

            if ($filters->sort_by == 'movie_rating_desc') {
                $order_by = 'mt5.meta_value DESC ';
            } else if ($filters->sort_by == 'movie_rating_asc') {
                $order_by = 'mt5.meta_value ASC ';
            }

            $group_by = ($group_by ? $group_by . ', ' : '') . 'mt5.meta_value';
        }
    }

    if ($filters->critics) {

        global $s_posts_per_page;
        $s_posts_per_page = 20;


        foreach ($filters->critics as $i => $v) {
            /// echo $v.' ';

            if (is_numeric($v)) {
                $pro = 1;

                $pro_request .= "OR cachecmr.wprss_feed_id   ='" . $v . "' ";
            }

            if ($v == 'group_staff') {
                $stuff = 1;
            }

            if ($v == 'group_pro') {
                $group_pro = 1;
            }

            if (strstr($v, 'category_')) {
                $group_pro_cat = 1;

                $cat_result = substr($v, 9);

                ///  echo $cat_result.' ';
                ///  $groop_request.= "OR cachecmcat.type = '".$cat_result."' ";

                $groop_request .= "OR cachecmr.categories   LIKE '%|" . $cat_result . "|%' ";
            }

            if ($v == 'group_audience') {
                $group_audience = 1;
            }
        }

        //// echo '$pro='.$pro.'<br>';

        /*
          if (  $group_audience) {

          $arrayrequest = getmetarequest($filters->critics, 'wprss_feed_id', '', $arrayrequest, 'critics_', '=', 2, 'critics');

          }
         */


        if ($group_pro_cat) {

            $arrayrequest = getmetarequest($filters->critics, 'wprss_feed_id', '', $arrayrequest, 'cache', '=', 7, '');
            //  $arrayrequest = getmetarequest($filters->critics, '', '', $arrayrequest, 'cache', '=', 8, '');


            $groop_request = " and (" . substr($groop_request, 2) . " ) ";

            $arrayrequest['where'] .= $groop_request;
        }


        if ($group_pro || $pro || $stuff || $group_audience) {

            $arrayrequest = getmetarequest($filters->critics, 'wprss_feed_id', '', $arrayrequest, 'cache', '=', 7, '');

            if ($pro) {
                $pro_request = " and (" . substr($pro_request, 2) . " ) ";
                $arrayrequest['where'] .= $pro_request;
            }

            if ($pro || $group_pro) {
                $aw .= "  cachecmr.wprss_feed_from  ='1' ";
                ;


                if ($filters->review_category) {
                    $cat_array = array('Proper Review' => 1, 'Contains Mention' => 2);

                    var_dump($filters->review_category);

                    $cur_cat = $cat_array[$filters->review_category[0]];


                    $arrayrequest['where'] .= " and cachecmr.wprss_feed_category  ='" . $cur_cat . "' ";
                }
            }

            if ($stuff) {

                if ($aw) {
                    $aw .= " OR";
                }

                $aw .= " cachecmr.wprss_feed_from  ='0' ";
            }

            if ($group_audience) {

                if ($aw) {
                    $aw .= " OR";
                }

                $aw .= " cachecmr.wprss_feed_from  ='2' ";
            }


            $arrayrequest['where'] .= " and (" . $aw . ") ";
        }


        if ($filters->sort_by) {


            /*
              if ($group_audience) {


              $arrayrequest = getmetarequest(array(0 => 1), 'post_date', '', $arrayrequest, 'group_critics_ca', 'like', 6);


              if ($filters->sort_by == 'review_date_asc') {
              $order_by = '';

              $c_order = " group_critics_caf.post_date  ASC ";
              } else if ($filters->sort_by == 'review_date_desc') {
              $order_by = '';

              $c_order = " group_critics_caf.post_date DESC ";
              }

              }

             */
            /*
              if ( $stuff  )  {
              $arrayrequest = getmetarequest(array(0 => 1), 'wprss_item_imported_date', '', $arrayrequest, 'critics_', 'like', 5);

              if ($filters->sort_by == 'review_date_asc') {
              $order_by = '';

              $c_order = " STR_TO_DATE(critics_i.meta_value, '%Y-%m-%d')  ASC ";
              } else if ($filters->sort_by == 'review_date_desc') {
              $order_by = '';

              $c_order = " STR_TO_DATE(critics_i.meta_value, '%Y-%m-%d') DESC ";
              }
              }
             */

            if ($group_pro || $group_pro_cat || $pro || $stuff || $group_audience) {

                if ($filters->sort_by == 'review_date_asc') {
                    $order_by = '';

                    $c_order = " `cachecmr`.`wprss_item_imported_date`   ASC ";
                } else if ($filters->sort_by == 'review_date_desc') {
                    $order_by = '';

                    $c_order = "  `cachecmr`.`wprss_item_imported_date`  DESC ";
                }



                if ($filters->review_publish_date) {
                    // for dates: 80s, 90s, 2000s, etc.
                    if (substr($filters->review_publish_date[0], -1) == 's') {
                        $filters->review_publish_date[0] = substr($filters->review_publish_date[0], 0, 3);
                    }

                    $arrayrequest['where'] .= " and cachecmr.wprss_item_imported_date  LIKE '" . $filters->review_publish_date[0] . "%'";
                    ;
                }
            }
        }



        /*
          if ($stuff) {


          if ($filters->review_publish_date) {
          // for dates: 80s, 90s, 2000s, etc.
          if (substr($filters->review_publish_date[0], -1) == 's') {
          $filters->review_publish_date[0] = substr($filters->review_publish_date[0], 0, 3);
          }

          $arrayrequest = getmetarequest($filters->review_publish_date, 'wprss_item_imported_date', '', $arrayrequest, 'critics_', 'like', 3);
          }

          if ($filters->review_category) {
          $arrayrequest = getmetarequest($filters->review_category, 'wprss_feed_category', '', $arrayrequest, 'critics_', 'like', 4);
          }


          }

         */
    }
}


if ($arrayrequest) {
    $join = $arrayrequest['join'];
    $where = $arrayrequest['where'];
}

if ($filters->keyword) {
    $keyword = strtolower($filters->keyword);
    $keyword = str_replace(array(',', ' '), '%', $keyword);
    $keyword_search = '%' . $keyword . '%';
    $keyword_request = "and LOWER(post_title) like ? ";

    global $onlysql;
    if ($onlysql) {
        $keyword_request = "and LOWER(post_title) like '" . $keyword_search . "' ";
    }
}
$post_type = '';

if ($filters->movie_type) {

    foreach ($filters->movie_type as $mtp) {
        $post_type .= "OR " . $table_prefix . "posts.post_type = '" . $mtp . "' ";
    }
    $post_type = substr($post_type, 2);
    $post_type = " ( " . $post_type . " ) ";
} else {
    $post_type = "  (" . $table_prefix . "posts.post_type = 'movie' OR  " . $table_prefix . "posts.post_type = 'tvseries') ";
}

global $s_posts_per_page;

if (!$s_posts_per_page)
    $s_posts_per_page = 20;

if ($filters->page || (isset($_POST['page']))) {
    $page = $filters->page;

    if (!$page) {
        $page = $_POST['page'];
    }

    if (!$page) {
        $page = 1;
    }

    $page = intval($page);

    $start = $s_posts_per_page * $page - $s_posts_per_page;
}

global $s_page;

$s_page = $page;

if ($start < 0 || !$start)
    $start = 0;

global $table_prefix;


$sqlcount = "SELECT COUNT(*) FROM " . $table_prefix . "posts " . $join . " 
             WHERE " . $table_prefix . "posts.post_type = 'movie' and " . $table_prefix . "posts.post_status = 'publish' " . $keyword_request . " " . $where . " 
             GROUP BY " . $table_prefix . "posts.ID 
             ORDER BY " . $table_prefix . "posts.post_title ";



////////not groop
if ($group_pro_cat || $group_pro || $pro || $stuff || $group_audience) {

    $group_by = ($group_by ? $group_by . ', ' : '') . 'cachecmr.post_id';

    $dop_request = "";
} else {
    $dop_request = "SQL_CALC_FOUND_ROWS " . $table_prefix . "posts.";
}

$dop_groop = "GROUP BY " . $group_by;


$sql = "SELECT " . $dop_request . "* FROM " . $table_prefix . "posts " . $join . " 
        WHERE  " . $post_type . "  and " . $table_prefix . "posts.post_status = 'publish' " . $keyword_request . " " . $where . " 
        " . $dop_groop . "
        ORDER BY " . $c_order . $order_by . " 
        LIMIT " . $start . ", " . $s_posts_per_page;

//echo $sql;
