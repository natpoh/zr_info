<?php

class PandoraMembers {

    var $sort = array('carma', 'rating', 'display_name', 'user_registered');
    var $order = array('asc', 'desc');
    var $sort_def = 'carma';
    var $order_def = 'desc';
    var $opt_def = array('search' => true, 'pagination' => false, 'sort' => true, 'alpha' => false, 'pagination2' => false, 'total' => false);

    function init_hooks() {
        add_action("wp_ajax_load_members", array($this, "load_members"));
        add_action('wp_ajax_nopriv_load_members', array($this, "load_members"));
    }

    function init() {
        add_action('wp_footer', array($this, 'wp_post_head'));
    }

    function load_members() {
        # Загрузка пользователей с помощью Ajax
        if (class_exists('tern_members')) {
            $members = new tern_members();
            $members->scope();
            $members->query($this->opt_def);
            $user_list = $members->get_user_list($members->r, $this->opt_def);
            print $user_list;
        }
        exit();
    }

    function members() {
        if (class_exists('tern_members')) {
            $members = new tern_members();
            $user_list = $members->members($this->opt_def, false);
            print $user_list;
        }
    }

    function wp_post_head() {
        ?>
        <script type="text/javascript">
            var members_page = <?php print $_GET['pg'] ? (int) $_GET['pg'] : 1  ?>;
            var members_sort = "<?php print in_array($_GET['sort'],$this->sort) ? $_GET['sort'] : $this->sort_def  ?>";
            var members_order = "<?php print in_array($_GET['order'],$this->order) ? $_GET['order'] : $this->order_def  ?>";
        </script>
        <?php
    }

}
