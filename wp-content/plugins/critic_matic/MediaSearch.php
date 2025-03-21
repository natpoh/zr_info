<?php

/**
 * MediaSearch
 *
 * @author brahman
 */
class MediaSearch extends CriticSearch {

    public function __construct($cm = '') {
        $this->facet_data['ctags']['hide']=0;
        parent::__construct($cm);        
    }
    public function front_search_media_multi($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = false, $show_meta = true, $widlcard = false, $fields = array(), $show_main = true) {

        // Sort logic
        $order = $this->get_order_query_critics($sort);

        // Movie weight logic        

        if (isset($sort['sort']) && $sort['sort'] == 'mw') {
            $start = 0;
            $limit = 10000;
        }

        //Keywords logic
        $match = '';
        if ($keyword) {
            $keyword = str_replace("'", "\'", $keyword);
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,content,mtitle,myear) (%s)'", $search_keywords);
            $match = " AND MATCH(:match)";
        }

        $ret = array('list' => array(), 'count' => 0);
        $this->connect();
        $query_type = 'critics';

        if ($show_main) {

            // Filters logic
            $filters_and = $this->get_filters_query($filters, array(), $query_type);

            // Snipper logic
            if ($keyword) {
                $snippet = ', SNIPPET(title, QUERY()) t, SNIPPET(content, QUERY()) c, SNIPPET(mtitle, QUERY()) mt';
            }

            $custom_fields = '';
            if ($fields) {
                $custom_fields = ', ' . implode(', ', $fields) . ' ';
            }

            // Main sql
            $sql = sprintf("SELECT id, post_date as date, date_add, top_movie,state,mtitle,myear,mpname, type, aid, author_name, author_type, aurating, ip, title, content, aurating, auvote, link, viewtype, weight() w" . $snippet . $custom_fields . $order['select'] . $filters_and['select']
                    . " FROM critic WHERE id>0" . $filters_and['filter'] . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

            //Get result
            $ret = $this->movie_results($sql, $match, $search_query);
        }

        /*
          print_r($filters_and);
          print_r(array($match, $search_query));
          print_r($sql);
          print_r($ret);

          $meta = $this->sps->query("SHOW META")->fetchAll();
          print_r($meta);
          exit;
         */

        // Simple result
        if (!$show_meta) {
            return $ret['list'];
        }

        // Facets logic         
        $facets_arr = array();
        if ($facets) {
            $facets_arr = $this->critic_facets($filters, $match, $search_query, $query_type, $facets);
        }
        $ret['facets'] = $facets_arr;
        return $ret;
    }

    public function get_filters_query($filters = array(), $exlude = array(), $query_type = 'movies', $curr_filter = '', $aid = 0) {
        // Filters logic
        $filters_and = array();
        $select_and = array();

        if (!isset($filters['release'])) {
            $filters['release'] = $this->get_default_release();
        }

        if ($query_type == 'critics') {

            // Status
            if (!isset($filters['status'])) {
                $filters_and['status'] = $this->filter_multi_value('status', 1);
            }

            if ($this->in_exclude('state', $exlude)) {
                unset($filters['state']);
            }

            if ($this->in_exclude('status', $exlude)) {
                $filters_and['status'] = $this->filter_multi_value('status', array(0, 1));
            }

            if (isset($filters['state'])) {

                if (is_array($filters['state']) && sizeof($filters['state']) == 1) {
                    $filters['state'] = $filters['state'][0];
                }

                $filters_and['state'] = $this->filter_multi_value('state', $filters['state'], true);
            }
        }


        if (sizeof($filters)) {
            foreach ($filters as $key_data => $value) {

                $filter = $this->getSearchFilter($key_data);
                $key = $filter->filter;
                $minus = $filter->minus;
                $and = $filter->and;
                $or = $filter->or;

                // Get current facet                
                $curr_facet = isset($this->facets_data[$key]) ? $this->facets_data[$key] : array();
                if (isset($curr_facet['type']) && $curr_facet['type'] == 'tabs') {
                    continue;
                }

                // Get titles
                $this->get_filter_titles($key, $value);

                // Exclude filter
                if (!$and) {
                    if ($this->in_exclude($key, $exlude)) {
                        continue;
                    }
                }

                if ($query_type == 'critics') {

                    if ($key == 'author') {
                        // Author
                        $filters_and[$key] = $this->filter_multi_value('author_type', $value);
                    } else if ($key == 'from') {
                        // From author
                        $filters_and[$key] = $this->filter_multi_value('aid', $value, true);
                    } else if ($key == 'site') {
                        // From author
                        $filters_and[$key] = $this->filter_multi_value($key, $value, true);
                    } else if ($key == 'tags') {
                        // Tags                       
                        $filters_and[$key] = $this->filter_multi_value($key, $value, true);
                    } else if ($key == 'ctags') {
                        // Tags                   
                        unset($filters_and['state']);
                        $filters_and['status'] = $this->filter_multi_value('status', array(0, 1));
                        $filters_and[$key] = $this->filter_multi_value($key, $value, true);
                    } else if ($key == 'state') {
                        // Type
                        // $filters_and[]= $this->filter_multi_value('state', $value);
                    } else if ($key == 'movie') {
                        // Movie                 
                        $filters_and[$key] = $this->filter_multi_value('movies', $value, true);
                    }
                }

                // All
                if (isset($this->facet_data['findata']['childs'][$key])) {
                    // Finances                    
                    $data_arr = explode('-', $value);
                    $from = ((int) $data_arr[0]) * 1000;
                    $to = ((int) $data_arr[1]) * 1000;
                    $budget_min = $this->budget_min * 1000;
                    $budget_max = $this->budget_max * 1000;

                    if ($from == $to) {
                        if ($from == $budget_min) {
                            $filters_and[$key] = sprintf("{$key} > 0 AND {$key}<=%d", $from);
                        } else if ($from == $budget_max) {
                            $filters_and[$key] = sprintf("{$key}>=%d", $to);
                        } else {
                            $filters_and[$key] = sprintf("{$key}=%d", $from);
                        }
                    } else {
                        if ($from == $budget_min && $to == $budget_max) {
                            $filters_and[$key] = "{$key} > 0";
                        } else if ($from == $budget_min) {
                            $filters_and[$key] = sprintf("{$key} > 0 AND {$key} < %d", $to);
                        } else if ($to == $budget_max) {
                            $filters_and[$key] = sprintf("{$key} >= %d", $from);
                        } else {
                            $filters_and[$key] = sprintf("{$key} >=%d AND {$key} < %d", $from, $to);
                        }
                    }
                } else if (isset($curr_facet['facet']) && $curr_facet['facet'] == 'rating') {
                    if ($value == 'use' || $value == 'minus') {
                        $parent_key = $curr_facet['eid'];
                        if ($value == 'use') {
                            $filters_and[$key] = $parent_key . "=1";
                        } else {
                            $filters_and[$key] = $parent_key . "=0";
                        }
                    } else {
                        $dates = explode('-', $value);
                        $from = (int) $dates[0];
                        $to = (int) $dates[1];

                        if (isset($this->facet_data['wokedata']['childs'][$key])) {
                            // Woke ratings
                            if (!$minus) {
                                if ($from == $to) {
                                    $filters_and['if_woke'][] = "{$key} = {$from}";
                                } else {
                                    if ($from != 0) {
                                        $filters_and['if_woke'][] = "{$key} >= {$from} AND {$key} <= {$to}";
                                    } else {
                                        $filters_and['if_woke'][] = "{$key}<={$to}";
                                    }
                                }
                            } else {
                                /* if ($from == $to) {
                                  $filters_and['if_woke'][] = "{$key}!={$from}";
                                  } else {
                                  $filters_and['if_woke'][] = "{$key}<{$from} OR {$key}>{$to}";
                                  } */
                                if ($from == $to) {
                                    $key_filter = $key . '_filter';
                                    $select_and[$key] = "IF({$key}!={$from},1,0) AS {$key_filter} ";
                                    $filters_and[$key] = "{$key_filter}=1";
                                } else {
                                    $key_filter = $key . '_filter';
                                    $select_and[$key] = "IF({$key}<{$from} OR {$key}>{$to},1,0) AS {$key_filter} ";
                                    $filters_and[$key] = "{$key_filter}=1";
                                }
                            }
                        } else {
                            // Other ratings
                            if (!$minus) {
                                if ($from == $to) {
                                    $filters_and[] = sprintf($key . "=%d", $from);
                                } else {
                                    if ($from != 0) {
                                        $filters_and[$key] = "{$key} >= {$from} AND {$key} <= {$to}";
                                    } else {
                                        $key_filter = $key . '_filter';
                                        $select_and[$key] = "IF({$key}<={$to},1,0) AS {$key_filter} ";
                                        $filters_and[$key] = "{$key_filter}=1";
                                    }
                                }
                            } else {
                                if ($from == $to) {
                                    $key_filter = $key . '_filter';
                                    $select_and[$key] = "IF({$key}!={$from},1,0) AS {$key_filter} ";
                                    $filters_and[$key] = "{$key_filter}=1";
                                } else {
                                    $key_filter = $key . '_filter';
                                    $select_and[$key] = "IF({$key}<{$from} OR {$key}>{$to},1,0) AS {$key_filter} ";
                                    $filters_and[$key] = "{$key_filter}=1";
                                }
                            }
                            if ($key == 'rrtg') {
                                $filters_and[$key] .= " AND rrta>0 AND rrt>0";
                            }
                            if ($facet == 'rmg') {
                                $filters_and[$key] .= " AND rmu>0 AND rmc>0";
                            }
                        }
                    }
                } else if ($key == 'genre') {

                    // Genre
                    if ($and) {
                        if ($minus) {
                            $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus, true, false, true, true);
                        } else {
                            $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus, true, false, true, true);
                        }
                    } else {
                        $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                    }
                } else if ($key == 'platform') {
                    // Platform                        
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else if ($key == 'type') {
                    // Type
                    $filters_and[$key] = $this->filter_multi_value('type', $value);
                } else if ($key == 'id') {
                    // id
                    $filters_and[$key] = sprintf("id>%d", $value);
                } else if ($key == 'release') {
                    // Release
                    $dates = explode('-', $value);
                    $release_from = (int) $dates[0];
                    if ($release_from == 0) {
                        $release_from = 1;
                    }
                    $release_to = (int) $dates[1];
                    if ($release_from == $release_to) {
                        $filters_and[$key] = sprintf("year_int=%d", $release_from);
                    } else {
                        $filters_and[$key] = sprintf("year_int >=%d AND year_int < %d", $release_from, $release_to);
                    }
                } else if (isset($this->facet_data['wokedata']['childs'][$key])) {
                    if ($key == 'kmwoke') {

                        $value_loc = $value;
                        if (!is_array($value_loc)) {
                            $value_loc = array($value_loc);
                        }
                        $for_filter = array();
                        foreach ($value_loc as $kmkey) {
                            $kfilter = $this->search_filters[$key][$kmkey]['key'];

                            if ($this->in_exclude($kfilter, $exlude)) {
                                continue;
                            }

                            if ($minus) {
                                $for_filter[] = $kfilter . "=0";
                            } else {
                                $filters_and['if_woke'][] = $kfilter . "=1";
                            }
                        }
                        if ($minus) {
                            if ($for_filter) {
                                $filters_and[$key] = implode(' AND ', $for_filter);
                            }
                        }
                    } else {
                        if (!$minus) {
                            $filter_items = $value;
                            if (!is_array($value)) {
                                $filter_items = [$value];
                            }
                            foreach ($filter_items as $filter_item) {
                                $filters_and['if_woke'][] = $this->filter_multi_value($key, $filter_item, false);
                            }
                        } else {
                            $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                        }
                    }
                } else if ($key == 'provider') {
                    // Provider
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true);
                } else if ($key == 'price') {
                    // Provider price
                    $ma = $this->get_ma();
                    $pay_type = 1;
                    $list = $ma->get_providers_by_type($pay_type);
                    $filters_and[$key] = $this->filter_multi_value('provider', $list, true);
                } else if ($key == 'country') {
                    // Country
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else if ($key == 'lang') {
                    // lang
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else if (isset($this->facet_data['dirsdata']['childs'][$key])) {
                    if ($key == 'dirall' || $key == 'dir' || $key == 'dirwrite' || $key == 'dircast' || $key == 'dirprod') {
                        // Director  
                        $actor_filter = $this->facet_data['dirsdata']['childs'][$key]['filter'];
                        $filters_and[$key] = $this->filter_multi_value($actor_filter, $value, true, $minus);
                    } else {
                        // Race directors
                        // Gender dirs
                        $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                    }
                } else if ($key == 'indie') {
                    $value = is_array($value) ? $value : array($value);
                    $for_filter = array();
                    foreach ($value as $slug) {
                        if ($this->search_filters[$key][$slug]) {
                            $for_filter[] = $this->filter_multi_value($slug, 1, false, $minus, true, true, false);
                        }
                    }
                    if ($for_filter) {
                        $filters_and[$key] = implode(' AND ', $for_filter);
                    }
                } else if ($key == 'mkw') {
                    // Movie Keywords
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else if ($key == 'franchise') {
                    // Franchise
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else if ($key == 'distributor' || $key == 'production') {
                    // Distributor
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else {

                    $curr_parent = $this->get_last_parent($key);

                    $actors_facet = '';
                    if ($curr_parent == 'actorsdata') {
                        // Actors facet
                        $actors_facet = 'cast';
                    } else if ($curr_parent == 'dirsdata') {
                        // Directors facet
                        $actors_facet = 'director';
                    }
                    if ($actors_facet) {

                        $cparent = isset($curr_facet['parent']) ? $curr_facet['parent'] : '';

                        if ($cparent == 'race') {
                            // Race
                            $first_parent = $this->get_first_parent($key);
                            $race_facets = isset($this->actorscache[$actors_facet]['exist'][$first_parent]) ? $this->actorscache[$actors_facet]['exist'][$first_parent] : array();
                            //print $first_parent . "\n";
                            //print_r(array_keys($this->actorscache[$actors_facet]['exist']));
                            if ($race_facets) {
                                $for_filter = array();
                                foreach ($race_facets['all'] as $rkey => $rval) {
                                    $racekey = $rval['race'];
                                    // Exclude filter
                                    if (!$and) {
                                        if ($this->in_exclude($rkey, $exlude)) {
                                            continue;
                                        }
                                    }

                                    if (is_array($value)) {
                                        if (in_array($racekey, $value)) {
                                            $for_filter[] = $this->filter_multi_value($rkey, 1, false, $minus);
                                        }
                                    } else {
                                        if ($racekey == $value) {
                                            $for_filter[] = $this->filter_multi_value($rkey, 1, false, $minus);
                                        }
                                    }
                                }
                                if ($for_filter) {
                                    $filters_and[$key] = implode(' AND ', $for_filter);
                                }
                            }
                        } else if ($cparent == 'gender') {
                            // Gender                           
                            $first_parent = $this->get_first_parent($key);
                            $race_facets = isset($this->actorscache[$actors_facet]['exist'][$first_parent]) ? $this->actorscache[$actors_facet]['exist'][$first_parent] : array();
                            if ($race_facets) {
                                $for_filter = array();
                                foreach ($this->search_filters['gender'] as $gkey => $gitem) {
                                    foreach ($race_facets[$gkey] as $rkey => $rval) {
                                        $racekey = $rval['race'];
                                        if ($racekey == 'a') {

                                            if (!$and) {
                                                if ($this->in_exclude($rkey, $exlude)) {
                                                    continue;
                                                }
                                            }
                                            if (is_array($value)) {
                                                if (in_array($gkey, $value)) {
                                                    $for_filter[] = $this->filter_multi_value($rkey, 1, false, $minus);
                                                }
                                            } else {
                                                if ($gkey == $value) {
                                                    $for_filter[] = $this->filter_multi_value($rkey, 1, false, $minus);
                                                }
                                            }
                                        }
                                    }
                                }
                                if ($for_filter) {
                                    $filters_and[$key] = implode(' AND ', $for_filter);
                                }
                            }
                        } else {
                            if ($key == 'sphoto' || $key == 'mphoto' || $key == 'aphoto') {
                                // sphoto    
                                // $filters_and[$key]= $this->filter_multi_value($key, $value, true, $minus, false, true, false);
                                $filters_and[$key] = $this->filter_multi_value($key, $value, false, $minus);
                            } else {
                                // other
                                $custom_filter = isset($curr_facet['filter']) ? $curr_facet['filter'] : $key;
                                $filters_and[$key] = $this->filter_multi_value($custom_filter, $value, true, $minus);
                            }
                        }
                    }
                }
            }
        }


        if ($curr_filter && isset($this->filter_custom_and[$curr_filter])) {
            $filters_and[$curr_filter] = $this->filter_custom_and[$curr_filter];
        }

        // Woke logic

        if ($filters_and['if_woke']) {
            if ($this->in_exclude('if_woke', $exlude)) {
                unset($filters_and['if_woke']);
            } else {
                $select_and['if_woke'] = $this->woke_recursion($filters_and['if_woke']) . " AS if_woke";
                $filters_and['if_woke'] = "if_woke=1";
            }
        }


        $select_str = implode(', ', array_values($select_and));
        if ($select_str) {
            $select_str = ',' . $select_str;
        }



        $filters_str = implode(' AND ', array_values($filters_and));
        if ($filters_str) {
            $filters_str = ' AND ' . $filters_str;
        }
        return array(
            'filter' => $filters_str,
            'select' => $select_str,
        );
    }

}
