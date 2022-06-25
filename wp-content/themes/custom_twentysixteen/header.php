<?php
/**
 * The template for displaying the header
 *
 * Displays all of the head element and everything up until the "site-content" div.
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <?php if (is_singular() && pings_open(get_queried_object())) : ?>
            <link rel="pingback" href="<?php echo esc_url(get_bloginfo('pingback_url')); ?>">
        <?php endif; ?>
        <?php wp_head(); ?>
    </head>

    <body <?php body_class(); ?>>
        <?php
        wp_body_open();

        function get_header_data() {
            ?>

            <div class="header_nav">
                <header id="masthead" class="site-header" role="banner">
                    <div class="site-header-main">

                        <div class="open_menu" style="display: none"><span class="hdr_drp_dwn_menu">
                                <div class="bar"></div>
                                <div class="bar"></div>
                                <div class="bar"></div>
                                <div class="hdr_drp_dwn_menu_desc">Menu</div>
                                <div class="clear"></div>
                            </span>
                        </div>

                        <div class="site-branding">
                            <?php
                            if (function_exists('get_custom_logo')) {
                                $logo = get_custom_logo();
                            }
                            if ($logo) {
                                echo $logo;
                            } else {

                                if (is_front_page() && is_home()) :
                                    ?>
                                    <h1 class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a></h1>
                                <?php else : ?>
                                    <p class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a></p>
                                <?php
                                endif;
                                $description = get_bloginfo('description', 'display');
                                if ($description || is_customize_preview()) :
                                    ?>
                                    <p class="site-description"><?php echo $description; ?></p>
                                    <?php
                                endif;
                            }
                            ?>

                        </div><!-- .site-branding -->


                        <div class="search">
                            <?php
                            global $cfront, $cm_new_api;
                            if ($cm_new_api) {
                                $cfront->search_form();
                            } else {
                                get_search_form();
                            }
                            ?>
                        </div>


                        <?php
                        if (has_nav_menu('primary')) :
                            ?>


                            <div id="site-header-menu" class="site-header-menu"><span class="close_header_nav"></span>
                                <?php if (has_nav_menu('primary')) : ?>
                                    <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e('Primary Menu', 'twentysixteen'); ?>">
                                        <?php
                                        wp_nav_menu(
                                                array(
                                                    'theme_location' => 'primary',
                                                    'menu_class' => 'primary-menu',
                                                )
                                        );
                                        ?>
                                        <div class="site_theme_switch" title="Color theme">
                                            <div class="btn">
                                                <div class="box">
                                                    <div class="ball"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </nav><!-- .main-navigation -->
                                <?php endif; ?>

                            </div><!-- .site-header-menu -->
                        <?php endif; ?>



                    </div><!-- .site-header-main -->

                    <?php if (get_header_image()) : ?>
                        <?php
                        /**
                         * Filter the default twentysixteen custom header sizes attribute.
                         *
                         * @since Twenty Sixteen 1.0
                         *
                         * @param string $custom_header_sizes sizes attribute
                         * for Custom Header. Default '(max-width: 709px) 85vw,
                         * (max-width: 909px) 81vw, (max-width: 1362px) 88vw, 1200px'.
                         */
                        $custom_header_sizes = apply_filters('twentysixteen_custom_header_sizes', '(max-width: 709px) 85vw, (max-width: 909px) 81vw, (max-width: 1362px) 88vw, 1200px');
                        ?>


                    <?php endif; // End header image check.
                    ?>
                    <div class="scont site-header-menu"></div>


                </header><!-- .site-header -->
            </div>
<script type="text/javascript">
    var site_theme = localStorage.getItem('site_theme');
    if (site_theme =='theme_dark')
    {
        document.querySelector('body').classList.add('theme_dark');
    }
    else
    {
        document.querySelector('body').classList.add('theme_white');
    }

</script>
            <?php
        }

        if (function_exists('wp_theme_cache')) {
            echo wp_theme_cache('get_header_data');
        } else {
            get_header_data();

            if (function_exists('add_popup')) {
                add_popup();
            }
        }
        ?>





        <div id="page" class="site">

            <div id="content" class="site-content">

                <?php
                if (is_active_sidebar('sidebar-5')) {
                    echo '<div class="top_adheader">';
                    dynamic_sidebar('sidebar-5');
                    echo '</div>';
                }
                ?>