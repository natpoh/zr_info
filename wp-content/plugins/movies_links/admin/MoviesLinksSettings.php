<?php

class MoviesLinksSettings extends ItemAdmin {

    public $mla;
    public $ml;
    private $mp;
    private $settings_tabs = array(
        'parser' => 'Parser',
        'tor' => 'Tor',
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
