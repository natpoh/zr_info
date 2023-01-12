<?php

class TorAdmin extends ItemAdmin {

    public $mla;
    public $ml;
    public $tp;
    public $tor_tabs = array(
        'home' => 'Services list',
        'agents' => 'Agents',
        'ips' => 'IPs',
        'log' => 'Log',
        'add' => 'Add a new service',
    );
    public $service_tabs = array(
        'home' => 'Veiw',
        'log' => 'Log',
        'edit' => 'Edit',
    );
    public $bulk_actions = array(
        'tor_active' => 'Activate service',
        'tor_inactive' => 'Stop service',
        'tor_getip' => 'Get IP',
        'tor_reboot' => 'Reboot service',
        'tor_trash' => 'Trash service',
    );
    public $agent_ip = array(
        1 => 'Attached',
        0 => 'Empty',
    );
    public $service_status = array(
        1 => 'Active',
        0 => 'Inactive',
        3 => 'Reboot',
        4 => 'Error',
        2 => 'Trash'
    );
    public $service_type = array(
        0 => 'Tor',
        1 => 'Proxy',
    );

    /* Log */
    public $log_type = array(
        0 => 'Info',
        1 => 'Warning',
        2 => 'Error',
    );
    public $log_status = array(
        0 => 'Other',
        1 => 'Parsing',
        2 => 'Update IP',
        3 => 'Reboot',
    );

    public function __construct($mla = '') {
        $this->mla = $mla;
        $this->ml = $mla->ml;
        $this->tp = $this->ml->get_tp();
    }

    public function init() {
        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();
        $cid = isset($_GET['cid']) ? (int) $_GET['cid'] : '';

        // Bulk actions
        $this->bulk_submit();

        //Sort
        $orderby = $this->get_orderby($this->tp->sort_pages);
        $order = $this->get_order();

        $url = $this->mla->admin_page . $this->mla->tor_url;

        /*
         * Campaign page
         */
        if ($cid) {
            $service = $this->tp->get_service($cid);


            //Tabs
            $append = '&cid=' . $cid;
            $tabs_arr = $this->service_tabs;

            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

            if (!$curr_tab) {
                $curr_tab = 'home';
            }

            if ($curr_tab == 'home') {

                include(MOVIES_LINKS_PLUGIN_DIR . 'includes/view_tor.php');
            } else if ($curr_tab == 'edit') {
                if (isset($_POST['name'])) {
                    $valid = $this->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->campaign_edit_submit($_POST);
                        $service = $this->tp->get_service($cid);
                        $result = __('The service updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                include(MOVIES_LINKS_PLUGIN_DIR . 'includes/edit_tor.php');
            } else if ($curr_tab == 'log') {
                // Log
                $append_tab = '&tab=' . $curr_tab;
                $page_url = $url . $append_tab . $append;

                $query_adb = new QueryADB();
                $query_adb->add_query('driver', $cid);
                $query = $query_adb->get_query();

                $filters = array(
                    'status' => $this->log_status,
                    'type' => $this->log_type
                );

                $filters_tabs = $this->get_filters_tabs($this->tp, 'get_logs_count', $filters, $page_url, $query_adb);

                $page_url = $filters_tabs['p'];
                $count = $filters_tabs['c'];

                $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
                $logs = $this->tp->get_logs($query, $page, $per_page, $orderby, $order);

                include(MOVIES_LINKS_PLUGIN_DIR . 'includes/list_tor_logs.php');
            }

            return;
        }

        $tabs_arr = $this->tor_tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        if (!$curr_tab) {
            // List of services
            $page_url = $url;
            //Pager

            $filters = array(
                'status' => $this->service_status,
                'type' => $this->service_type
            );

            $filters_tabs = $this->get_filters_tabs($this->tp, 'get_services_count', $filters, $page_url);
            $query_adb = $filters_tabs['query_adb'];
            $query = $query_adb->get_query();
            $page_url = $filters_tabs['p'];
            $count = $filters_tabs['c'];

            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $services = $this->tp->get_services($query, $page, $per_page, $orderby, $order);

            include(MOVIES_LINKS_PLUGIN_DIR . 'includes/list_tor_services.php');
        } else if ($curr_tab == 'agents') {

            $append = '&tab=' . $curr_tab;
            $page_url = $url . $append;

            if (isset($_POST['add_agents'])) {
                $result = $this->add_agents($_POST['add_agents']);
                print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
            }
            $filters = array(
                'ip' => $this->agent_ip
            );
            $filters_tabs = $this->get_filters_tabs($this->tp, 'get_agents_count', $filters, $page_url);
            $query_adb = $filters_tabs['query_adb'];
            $query = $query_adb->get_query();
            $page_url = $filters_tabs['p'];
            $count = $filters_tabs['c'];

            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $agents = $this->tp->get_agents($query, $page, $per_page, $orderby, $order);

            include(MOVIES_LINKS_PLUGIN_DIR . 'includes/list_tor_agents.php');
        } else if ($curr_tab == 'ips') {

            $append = '&tab=' . $curr_tab;
            $page_url = $url . $append;

            $filters = array(
                'agent' => $this->agent_ip
            );
            $filters_tabs = $this->get_filters_tabs($this->tp, 'get_ips_count', $filters, $page_url);
            $query_adb = $filters_tabs['query_adb'];
            $query = $query_adb->get_query();
            $page_url = $filters_tabs['p'];
            $count = $filters_tabs['c'];

            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $ips = $this->tp->get_ips($query, $page, $per_page, $orderby, $order);

            include(MOVIES_LINKS_PLUGIN_DIR . 'includes/list_tor_ips.php');
        } else if ($curr_tab == 'log') {
            // Log
            $append = '&tab=' . $curr_tab;
            $page_url = $url . $append;

            $filters = array(
                'status' => $this->log_status,
                'type' => $this->log_type
            );
            $filters_tabs = $this->get_filters_tabs($this->tp, 'get_logs_count', $filters, $page_url);
            $query_adb = $filters_tabs['query_adb'];
            $query = $query_adb->get_query();
            $page_url = $filters_tabs['p'];
            $count = $filters_tabs['c'];

            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $logs = $this->tp->get_logs($query, $page, $per_page, $orderby, $order);

            include(MOVIES_LINKS_PLUGIN_DIR . 'includes/list_tor_logs.php');
        } else if ($curr_tab == 'add') {
            // Add
            if (isset($_POST['name'])) {
                $valid = $this->campaign_edit_validate($_POST);
                if ($valid === true) {
                    $result_id = $this->campaign_edit_submit($_POST);
                    $result = __('The service added. Go to the service: ') . '<a href="' . $url . '&cid=' . $result_id . '">' . $result_id . '</a>';
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }

            include(MOVIES_LINKS_PLUGIN_DIR . 'includes/add_tor.php');
        }
    }

    public function bulk_submit() {
        /*
          'tor_active' => 'Activate service',
          'tor_inactive' => 'Stop service',
          'tor_getip' => 'Get IP',
          'tor_reboot' => 'Reboot service',
          'tor_trash' => 'Trash service',
         */
        if (isset($_POST['bulkaction'])) {

            //[bulkaction] => draft [bulk-35660] => on [bulk-35659] => on
            $b = $_POST['bulkaction'];
            $ids = array();

            foreach ($_POST as $key => $value) {
                if (strstr($key, 'bulk-')) {
                    $ids[] = (int) str_replace('bulk-', '', $key);
                }
            }

            if ($b && sizeof($ids)) {
            
                $active_arr = array('tor_active', 'tor_inactive', 'tor_trash');
                if (in_array($b, $active_arr)) {
                    // Change service status

                    $act_names = array(
                        'tor_active' => 1,
                        'tor_inactive' => 0,
                        'tor_trash' => 2
                    );

                    $status = $act_names[$b];

                    $data = array(
                        'status' => $status,
                    );
                    foreach ($ids as $id) {
                        $this->tp->update_service_field($data, $id);
                    }
                    print "<div class=\"updated\"><p><strong>Updated</strong></p></div>";
                } else if ($b == 'tor_getip') {
                    foreach ($ids as $id) {
                        $this->tp->update_service_ip($id, 'Manual', 4);                       
                    }
                } else if ($b == 'tor_reboot') {
                    foreach ($ids as $id) {
                        $this->tp->reboot_service($id, 'Manual', true, false);
                    }
                }
            }
        }
    }

    public function service_actions() {
        $tabs = $this->service_tabs;
        foreach ($tabs as $key => $value) {
            $parser_actions[$key] = array('title' => $value);
        }
        return $parser_actions;
    }

    public function campaign_edit_validate($form_state) {

        if (isset($form_state['add_tor']) || isset($form_state['edit_tor'])) {
            // Add
            if ($form_state['name'] == '') {
                return __('Enter the name');
            }

            if ($form_state['url'] == '') {
                return __('Enter the url');
            }

            if (strstr($form_state['url'], 'http')) {
                return __('Enter vaild url');
            }
        }

        $nonce = wp_verify_nonce($_POST['ml-nonce'], 'ml-nonce');
        if (!$nonce) {
            return __('Error validate nonce');
        }

        return true;
    }

    public function campaign_edit_submit($form_state) {

        $result = 0;
        $id = 0;

        $status = isset($form_state['status']) ? $form_state['status'] : 0;
        $name = $form_state['name'];
        $type = $form_state['type'];
        $url = $form_state['url'];

        $id = $form_state['id'] ? $form_state['id'] : 0;
        if ($id) {
            //EDIT
            $this->tp->update_service($status, $name, $url, $type, $id);
            $result = $id;
        } else {
            //ADD
            $result = $this->tp->add_service($status, $name, $url, $type);
        }
        return $result;
    }

    private function add_agents($add) {
        if (strstr($add, "\n")) {
            $list = explode("\n", $add);
        } else {
            $list = array($add);
        }

        $message = 'No new agents';

        $total_add = 0;

        foreach ($list as $name) {
            $name = trim($name);
            if ($name) {
                if ($this->tp->add_agent_id($name)) {
                    $total_add += 1;
                }
            }
        }
        if ($total_add > 0) {
            $message = 'Agents added from list: ' . $total_add;
            //$this->tp->log_info($message);
        }
        return $message;
    }

}
