<?php

class MoviesLinksSettings extends ItemAdmin {

    public $mla;
    public $ml;
    private $mp;
    private $settings_tabs = array(
        'parser' => 'Parser',
        'tor' => 'Tor',
    );
    public $tor_agent = array(
        0 => 'List agents',
        1 => 'Generate',
    );
    
    public function __construct($mla = '') {
        $this->mla = $mla;
        $this->ml = $mla->ml;
        $this->mp = $this->ml->get_mp();
        $this->get_perpage();
    }

    public function init() {
        $curr_tab = $this->get_tab();

        $url = $this->mla->admin_page . $this->mla->settings_url;

        if (!$curr_tab) {
            $curr_tab = 'parser';
        }

        $tabs_arr = $this->settings_tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        if ($_GET['export_services']) {
            $tp = $this->ml->get_tp();
            $services = $tp->get_services(array(),1,0, $orderby = 'type',  'ASC');
            $result = array();
            if ($services){
                foreach ($services as $item) {
                    $cols = array();
                    $cols[]=$item->type;
                    $cols[]=$item->url;
                    $cols[]=$item->name;
                    $row = implode('|', $cols);
                    $result[]=$row;
                }
            }
            print '<h2>Export services</h2>';
            print '<textarea style="width:90%; height:500px">' . implode("\n", $result) . '</textarea>';

            exit;
        }

        if ($curr_tab == 'parser') {

            if (isset($_POST['ml-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->ml->update_settings($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $ss = $this->ml->get_settings();

            include(MOVIES_LINKS_PLUGIN_DIR . 'includes/settings_parser.php');
        } else if ($curr_tab == 'tor') {

            if (isset($_POST['ml-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->ml->update_settings($_POST);
                    if ($_POST['import_services_list']){
                        $tp = $this->ml->get_tp();
                        $tp->import_services($_POST['import_services_list']);
                    }
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $ss = $this->ml->get_settings();

            include(MOVIES_LINKS_PLUGIN_DIR . 'includes/settings_tor.php');
        }
    }

}
