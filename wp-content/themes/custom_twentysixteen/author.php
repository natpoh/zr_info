<?php get_header(); ?>
<?php
wp_enqueue_style('movie_single', get_template_directory_uri() . '/css/movie_single.css', array(), LASTVERSION);
wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);
wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);


$curauth = (isset($_GET['author_name'])) ? get_user_by('slug', $author_name) : get_userdata(intval($author));
if (!class_exists('UserCp')) {
    return;
}
$usercp = new UserCp($curauth);
$userCpData = $usercp->user_data;


$widgets = $usercp->widgets;


$widget_page = $usercp->is_widget_page();



if ($widget_page) {
    $can_show = $usercp->can_show_page($widget_page);
    if (!$can_show) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        get_template_part(404);
        exit();
    }
}
global $current_page;
$current_page = get_query_var('usercp');
?>
<div id="primary" class="content-full">
    <main id="main" class="site-main" role="main">
        <article class="author main<?php if ($widget_page) print ' user-page' ?>">
            <?php if (function_exists('breadcrumbs')) { ?>
                <div class="breadcrumbs"> 
                    <?php breadcrumbs(); ?>
                </div>
                <?php
            }


                $usercp->render_top_widgets();
                //Страница виджета





            ?>   
        </article>
        <?php

        global $cfront;
        $author = $cfront->cm->get_author_by_wp_uid($curauth->ID, true);

        $data = $usercp->get_menu();



        if ($current_page != 'settings') {
?>

        <nav>
            <?php echo $data ?>
        </nav>

        <?php

        }

        if (!$widget_page) {

         $usercp->render_widgets();
        }




        if ($widget_page) {
            $page = new UserCpPage($widget_page, $usercp->user_data);
            $page->show();
        }
        ?>
    </main><!-- .site-main -->
</div>

</div><!-- .content-area -->

<?php get_footer(); ?>
