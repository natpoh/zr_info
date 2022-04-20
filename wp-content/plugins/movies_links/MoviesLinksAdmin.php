<?php

/*
 * Admin interface for Movies links
 */

class MoviesLinksAdmin {

    //Movies links
    public $ml;
    private $access_level = 4;
    //Slug    
    public $parrent_slug = 'moveis_links';
    public $admin_page = '/wp-admin/admin.php?page=';
    public $movies_url = '';
    public $parser_url = '';
    public $settings_url = '';
    public $per_pages = array(30, 100, 500, 1000);

    public function __construct($ml = '') {
        $this->ml = $ml ? $ml : new MoviesLinks();
        $this->parser_url = $this->movies_url . '_movies';
        $this->parser_url = $this->parrent_slug . '_parser';
        $this->settings_url = $this->parrent_slug . '_settings';
        add_action('admin_menu', array($this, 'add_option_page'));
    }

    public function add_option_page() {
        add_menu_page(__('Movies Links'), __('Movies Links'), $this->access_level, $this->parrent_slug, array($this, 'overview'));
        add_submenu_page($this->parrent_slug, __('Movies Links overview'), __('Overview'), $this->access_level, $this->parrent_slug, array($this, 'overview'));
        //add_submenu_page($this->parrent_slug, __('Movies'), __('Movies'), $this->access_level, $this->movies_url, array($this, 'movies'));
        add_submenu_page($this->parrent_slug, __('Parser'), __('Parser'), $this->access_level, $this->parser_url, array($this, 'parser'));
        add_submenu_page($this->parrent_slug, __('Settings'), __('Settings'), $this->access_level, $this->settings_url, array($this, 'settings'));
    }

    public function overview() {  
        if (!class_exists('MoviesParserAdmin')) {
            require_once( MOVIES_LINKS_PLUGIN_DIR . '/admin/MoviesParserAdmin.php' );
        }
        $mpa = new MoviesParserAdmin($this);
        $mpa->overview();
    }

    public function movies() {
        
    }

    public function parser() {
        if (!class_exists('MoviesParserAdmin')) {
            require_once( MOVIES_LINKS_PLUGIN_DIR . '/admin/MoviesParserAdmin.php' );
        }
        $mpa = new MoviesParserAdmin($this);
        $mpa->init();
    }

    public function settings() {
        if (!class_exists('MoviesLinksSettings')) {
            require_once( MOVIES_LINKS_PLUGIN_DIR . '/admin/MoviesLinksSettings.php' );
        }
        $mls = new MoviesLinksSettings($this);
        $mls->init();
    }

    public function theme_parser_url_link($id, $name) {
        $link = $id;
        if ($id > 0) {
            $url = $this->admin_page . $this->parser_url . '&uid=' . $id;
            $link = '<a href="' . $url . '">' . $name . '</a>';
        }
        return $link;
    }

    public function theme_parser_campaign($id, $name) {
        $link = $id;
        if ($id > 0) {
            $url = $this->admin_page . $this->parser_url . '&cid=' . $id;
            $link = '<a href="' . $url . '">' . $name . '</a>';
        }
        return $link;
    }

}
