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
        function cache_header_data() {
            ?>

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

            if (has_nav_menu('second')) :
                ?>
                <div id="site-header-menu" class="site-header-menu-right">
                    <?php if (has_nav_menu('second')) : ?>
                        <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e('Header Menu', 'twentysixteen'); ?>">
                            <?php
                            wp_nav_menu(
                                array(
                                    'theme_location' => 'second',
                                    'menu_class' => 'primary-menu',
                                )
                            );
                            ?>
                        </nav><!-- .main-navigation -->
                    <?php endif; ?>

                </div><!-- .site-header-menu -->
            <?php endif; ?>

            <?php // TODO Unotify  ?>
            <div class="user-bar">
                <?php if (is_user_logged_in()) {
                    // User button
                    // $uid = 0;
                    $wpUser = wp_get_current_user();
                    $uid = $wpUser->ID;
                    $title = "Sign in";
                    if ($uid):
                        $title = "My account";


                        ?>
                    <?php endif ?>
                <?php } ?>
                <div title="<?php print $title ?>" id="user-btn" class="btn btn-mob collapsed" data-toggle="collapse" data-target="#ucnt" aria-expanded="false" aria-controls="ucnt">
                    <?php
                    if ($uid):
                        ?>

                        <i class="icon-user icon-user-online"></i>

                    <?php
                    else:
                        ?>
                        <i class="icon-user icon-user-offline"></i>
                    <?php
                    endif;
                    ?>
                    <i class="icon-cancel close"></i>
                </div>

                <?php
                // User links


                $uname = 'Anon';
                if ($uid) {
                    $uname = $wpUser->display_name;
                }
                ?>

                <div id="ucnt" class="collapse" data-id="<?php print $uid?>">
                    <ul class="userlinks dropdown-menu">
                        <li class="uname">
                            <?php if ($uid) { ?>
                                <span class="open" style="margin-left: 10px;"><?php print get_avatar($uid, 40); ?></span>
                            <?php } ?>
                            <b><?php print $uname ?></b></li>
                        <li class="divider uname"></li>
                        <?php if ($uid) : ?>
                            <li class="sep"><a href="<?php echo get_author_posts_url($wpUser->ID, $wpUser->user_nicename) ?>" title="Public profile">Profile</a></li>
                            <?php if (user_can($wpUser, 'administrator')){ ?>
                                <li class="sep"><a href="/wp-admin/profile.php"  title="Account settings">Settings</a></li>
                            <?php } ?>
                            <li><a href="<?php
                                $logout = wp_logout_url('/');
                                //$logout = apply_filters( 'loginout', $logout );
                                $logout = str_replace('wp-login.php', 'login', $logout);
                                print $logout;
                                ?>" title="Log out">Log out</a></li>
                        <?php else: ?>
                            <li><a class="sep ajaxlogin" href="/login">Log in</a></li>
                            <?php /* ?><li><a href="/login?action=register">Register</a></li><?php */ ?>
                        <?php endif; ?>

                    </ul>
                </div>

            </div>

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



            </header><!-- .site-header -->
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
                    <div class="hide_left_sidebar"><svg class="hide_left_sidebar_arrow" version="1.1"  x="0px" y="0px" width="20px" height="20px" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve" fill="#5F6368"><path d="M8.59,16.59L13.17,12L8.59,7.41L10,6l6,6l-6,6L8.59,16.59z"></path><path fill="none" d="M0,0h24v24H0V0z"></path></svg></div>

                    <script type="text/javascript">
                        var site_theme = localStorage.getItem('site_theme');
                        if (site_theme == 'theme_dark')
                        {
                            document.querySelector('body').classList.add('theme_dark');
                        } else
                        {
                            document.querySelector('body').classList.add('theme_white');
                        }

                        var left_sidebar = localStorage.getItem('left_sidebar');

                        if (left_sidebar == 'hidden')
                        {
                            const siteHeaderMenu = document.querySelector('.site-header-menu');
                            siteHeaderMenu.classList.add('hidden_left');

                        }


                    </script>
                </div><!-- .site-header-menu -->
            <?php endif; ?>


            <?php
        }
        function custom_header(){
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
//                            if (function_exists('get_custom_logo')) {
//                                $logo = get_custom_logo();
//                            }

                            $logo ='<a class="zr_logo" href="'.WP_SITEURL.'/" ><div class="zr_logo_img"><span class="zr_logo_description">What does the world <i>really</i> think?</span></div><span class="zr_logo_site_name">Zeitgeist<span class="zr_logo_ws">Reviews</span></span></a>';
                            if ($logo) {
                                echo $logo;
                            } else {

                                if (is_front_page() && is_home()) :
                                    ?>
                                    <h1 class="site-title"><a href="<?php echo WP_SITEURL; ?>/" rel="home"><?php bloginfo('name'); ?></a></h1>
                                <?php else : ?>
                                    <p class="site-title"><a href="<?php echo WP_SITEURL; ?>/" rel="home"><?php bloginfo('name'); ?></a></p>
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




            <?php
            if (function_exists('wp_theme_cache') && !is_user_logged_in()) {
                echo wp_theme_cache('cache_header_data');
            } else {
                cache_header_data();

                if (function_exists('add_popup')) {
                    add_popup();
                }
            }
        }


            custom_header();

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