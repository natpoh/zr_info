<?php

class ItemAdmin {

    public $per_pages = array(30, 100, 500, 1000, 5000);

    public function init() {
        
    }

    /*
     * Get sort orderby
     */

    public function get_orderby($allow_order = array()) {
        $orderby = sanitize_text_field(stripslashes($_GET['orderby']));
        if (!in_array($orderby, $allow_order)) {
            $orderby = '';
        }
        return $orderby;
    }

    /*
     * Get sort order
     */

    public function get_order() {
        $order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'desc' : 'asc';
        return $order;
    }

    /*
     * Get current tab
     */

    public function get_tab() {
        $tab = !empty($_GET['tab']) ? sanitize_text_field(stripslashes($_GET['tab'])) : '';
        return $tab;
    }

    /*
     * Get current page
     */

    public function get_page() {
        $page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
        return $page;
    }

    public function get_perpage() {
        $pp = isset($_GET['perpage']) ? (int) $_GET['perpage'] : $this->per_pages[0];
        return $pp;
    }

    /*
     * Page filters
     */

    public function get_filters_tabs($q_class, $fname = '', $filters = array(), $p = '', $query_adb = '') {
        if (!$query_adb) {
            $query_adb = new MoviesQueryADB();
        }
        $count = 0;
        $filters_tabs = array();

        if ($filters) {
            foreach ($filters as $key => $type_list) {
                $home_type = -1;
                $type = isset($_GET[$key]) ? (int) $_GET[$key] : $home_type;
                $filter_type_arr = $this->get_type_count($q_class,$fname,$query_adb->get_query(), $type_list, $key);
                $filters_type = $this->get_filters($filter_type_arr, $p, $type, '', $key);
                if ($type != $home_type) {
                    $p = $p . '&' . $key . '=' . $type;
                }
                $query_adb->add_query($key, $type);
                $filters_tabs['filters'][$key] = $filters_type;
            }

            $count = isset($filter_type_arr[$type]['count']) ? $filter_type_arr[$type]['count'] : 0;
        }

        $filters_tabs['query_adb'] = $query_adb;
        $filters_tabs['p'] = $p;
        $filters_tabs['c'] = $count;

        return $filters_tabs;
    }

    public function get_type_count($q_class, $fname = '', $q_req = array(), $types = array(), $custom_type = '') {
        $status = -1;
        $count = $q_class->$fname($q_req);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        $q_req_custom = $q_req;

        foreach ($types as $key => $value) {
            $title=$value;
            if (is_array($title)){
                $title=isset($value['title'])?$value['title']:$key;
            }
            $q_req_custom[$custom_type] = $key;
            $states[$key] = array(
                'title' => $title,
                'count' => $q_class->$fname($q_req_custom));
        }
        return $states;
    }

    public function get_filters($filter_arr = array(), $url = '', $curr_tab = -1, $front_slug = '', $name = 'status', $class = '', $show_name = true) {
        $ret = array();
        if (sizeof($filter_arr)) {
            foreach ($filter_arr as $slug => $value) {
                $title = $value['title'];
                $count = isset($value['count']) ? $value['count'] : false;
                $tab_active = false;

                if ($slug === $front_slug) {
                    $slug = '';
                    if ($curr_tab == '') {
                        $tab_active = true;
                    }
                } else {
                    if ($slug == $curr_tab) {
                        $tab_active = true;
                    }
                    $slug = '&' . $name . '=' . $slug;
                }
                $tab_class = '';
                if ($tab_active) {
                    $tab_class .= 'current';
                }

                $str = '<li><a href="' . $url . $slug . '" class="' . $tab_class . '">';
                $str .= $title;

                if ($count !== false) {
                    $str .= ' <span class="count">(' . $count . ')</span>';
                }
                $str .= '</a></li>';

                $ret[] = $str;
            }
        }
        if ($class) {
            $class = ' ' . $class;
        }
        $first = '';
        if ($show_name) {
            $first = '<li>' . ucfirst(str_replace('_', ' ', $name)) . ': </li>';
        }
        return '<ul class="cm-filters subsubsub' . $class . '">' . $first . implode(' | ', $ret) . '</ul>';
    }

    /*
     * Pager
     */

    public function themePager($page = 1, $url = '/', $count = 1, $per_page = 100, $orderby = '', $order = '', $pg = 'p', $active_class = 'disabled') {
        $ret = '';
        $pager = $this->getPager($page, $url, $count, $per_page, $orderby, $order);
        if ($pager) {
            $ret = '<div class="tablenav cmnav"><div class="tablenav-pages" style="float:none;"><div class="pagination-links">' . $pager . '</div></div></div>';
        }
        return $ret;
    }

    public function getPager($page = 1, $url = '/', $count = 1, $per_page = 100, $orderby = '', $order = '', $pg = 'p', $active_class = 'disabled') {
        $paged = $page;
        $max_page = 1; 
        if ($per_page > 0) {
            $max_page = ceil($count / $per_page);
        }
        $pages_to_show = 10;
        $pages_to_show_minus_1 = $pages_to_show - 1;
        $half_page_start = floor($pages_to_show_minus_1 / 2);
        $half_page_end = ceil($pages_to_show_minus_1 / 2);
        $start_page = $paged - $half_page_start;
        if ($start_page <= 0) {
            $start_page = 1;
        }
        $end_page = $paged + $half_page_end;
        if (($end_page - $start_page) != $pages_to_show_minus_1) {
            $end_page = $start_page + $pages_to_show_minus_1;
        }
        if ($end_page > $max_page) {
            $start_page = $max_page - $pages_to_show_minus_1;
            $end_page = $max_page;
        }
        if ($start_page <= 0) {
            $start_page = 1;
        }

        $ret = '';

        $first_page_text = '«';
        $last_page_text = '»';

        //Sort
        $orderby_link = '';
        $order_link = '';
        if ($orderby) {
            $orderby_link = '&orderby=' . $orderby;
            $order_link = '&order=' . $order;
        }

        $per_page_link = '&perpage=' . $per_page;

        if ($max_page > 1) {

            if ($start_page >= 2 && $pages_to_show < $max_page) {
                $ret .= '<a class="tab button" href="' . $url . '&' . $pg . '=1' . $orderby_link . $order_link . $per_page_link . '" title="' . $first_page_text . '"><span>' . $first_page_text . '</span></a>';
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
                $active = '';
                if ($i == $paged) {
                    $active = $active_class;
                }
                $page_text = $i;
                $ret .= '<a class="tab button ' . $active . '" href="' . $url . '&' . $pg . '=' . $i . $orderby_link . $order_link . $per_page_link . '" title="' . $page_text . '"><span>' . $page_text . '</span></a>';
            }
            //next_posts_link($pagenavi_options['next_text'], $max_page);
            if ($end_page < $max_page) {

                $ret .= '<a class="tab button" href="' . $url . '&' . $pg . '=' . $max_page . $orderby_link . $order_link . $per_page_link . '" title="' . $last_page_text . '"><span>' . $last_page_text . '</span></a>';
            }
        }

        //Per page
        if ($count > $this->per_pages[0]) {
            $ret .= ' Per page: ';
            foreach ($this->per_pages as $pp) {

                $pp_active = '';
                if ($pp == $per_page) {
                    $pp_active = $active_class;
                }

                $ret .= '<a class="tab button ' . $pp_active . '" href="' . $url . '&perpage=' . $pp . $orderby_link . $order_link . '"><span>' . $pp . '</span></a>';

                if ($count < $pp) {
                    break;
                }
            }
        }

        return $ret;
    }

    /*
     * Page tabs
     */

    public function get_tabs($url = '', $tabs = array(), $curr_tab = '', $append = '') {
        $ret = '';
        if (sizeof($tabs)) {
            $ret = '<h3 class="nav-tab-wrapper cm-nav">';
            foreach ($tabs as $slug => $title) {
                $tab_active = false;
                if ($slug == 'home') {
                    $slug = '';
                    if ($curr_tab == '') {
                        $tab_active = true;
                    }
                } else {
                    if ($slug == $curr_tab) {
                        $tab_active = true;
                    }
                    $slug = '&tab=' . $slug;
                }
                $tab_class = 'nav-tab';
                if ($tab_active) {
                    $tab_class .= ' nav-tab-active';
                }

                $ret .= '<a href="' . $url . $slug . $append . '" class="' . $tab_class . '">' . $title . '</a>';
            }
            $ret .= '</h3>';
        }
        return $ret;
    }

    public function sorted_head($slug = '', $title = '', $orderby = '', $order = 'asc', $page_url = '') {
        $ret = '';
        if ($slug) {
            $sortable = 'sortable';
            $orderby_link = '';
            $order_link = '';
            if ($slug == $orderby) {
                $sortable = 'sorted';
                $orderby_link = '&orderby=' . $slug;
                $next_order = $order == 'desc' ? 'asc' : 'desc';
                $order_link = '&order=' . $next_order;
            } else {
                $orderby_link = '&orderby=' . $slug;
                $order_link = '&order=' . $order;
            }
            ?>
            <th scope="col" id="<?php print $slug ?>" class="manage-column column-<?php print $slug ?> <?php print $sortable ?> <?php print $order ?>">
                <a href="<?php print $page_url . $orderby_link . $order_link ?>">
                    <span><?php print $title ?></span><span class="sorting-indicator"></span>
                </a>
            </th>
        <?php } else { ?>
            <th><?php print $title ?></th>
            <?php
        }
    }

    public function nonce_validate($form_state) {

        $nonce = wp_verify_nonce($form_state['ml-nonce'], 'ml-nonce');
        if (!$nonce) {
            return __('Error validate nonce');
        }

        return true;
    }

}
