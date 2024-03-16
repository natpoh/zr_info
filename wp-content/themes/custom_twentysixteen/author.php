<?php get_header(); ?>
<?php

wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);
wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);


$curauth = (isset($_GET['author_name'])) ? get_user_by('slug', $author_name) : get_userdata(intval($author));
if (!class_exists('UserCp')) {
    return;
}
$usercp = new UserCp($curauth);
$userCpData = $usercp->user_data;

//Активные виджеты
$widgets = $usercp->widgets;

//Проверяем страницу, виджет ли это или главная
//Может быть только два типа страниц: главная страница профиля и страница виджета
$widget_page = $usercp->is_widget_page();


//Если страницы нет, отправляем пользователя на 404
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
?>
<div id="primary" class="content-full">
    <main id="main" class="site-main" role="main">
        <article class="main<?php if ($widget_page) print ' user-page' ?>">
            <?php if (function_exists('breadcrumbs')) { ?>
                <div class="breadcrumbs"> 
                    <?php breadcrumbs(); ?>
                </div>
                <?php
            }
            $usercp->render_top_widgets();
            //Страница виджета
            if (!$widget_page) {
                //Главная страница                            
                $usercp->render_widgets();
            }
            ?>   
        </article>
        <?php
        if ($widget_page) {
            $page = new UserCpPage($widget_page, $usercp->user_data);
            $page->show();
        }
        ?>
    </main><!-- .site-main -->
</div>

</div><!-- .content-area -->

<?php get_footer(); ?>
