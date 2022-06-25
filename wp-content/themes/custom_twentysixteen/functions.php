<?php
/**
 * Twenty Sixteen functions and definitions
 *
 * Set up the theme and provides some helper functions, which are used in the
 * theme as custom template tags. Others are attached to action and filter
 * hooks in WordPress to change core functionality.
 *
 * When using a child theme you can override certain functions (those wrapped
 * in a function_exists() call) by defining them first in your child theme's
 * functions.php file. The child theme's functions.php file is included before
 * the parent theme's file, so the child theme functions would be used.
 *
 * @link https://codex.wordpress.org/Theme_Development
 * @link https://developer.wordpress.org/themes/advanced-topics/child-themes/
 *
 * Functions that are not pluggable (not wrapped in function_exists()) are
 * instead attached to a filter or action hook.
 *
 * For more information on hooks, actions, and filters,
 * {@link https://codex.wordpress.org/Plugin_API}
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
/**
 * Twenty Sixteen only works in WordPress 4.4 or later.
 */
if (version_compare($GLOBALS['wp_version'], '4.4-alpha', '<')) {
    require get_template_directory() . '/inc/back-compat.php';
}

require get_template_directory() . '/template/include/create_tsumb.php';

add_action('wp_print_styles', 'custom_styles', 100);

function custom_styles() {
    wp_deregister_style('twentysixteen-style');
    $version = '1.2.26';
    if (defined('LASTVERSION')) {
        $version = $version . LASTVERSION;
    }
    wp_enqueue_style('twentysixteen-style', get_template_directory_uri() . '/style.css', array(), $version);
}

if (!function_exists('twentysixteen_setup')) :

    /**
     * Sets up theme defaults and registers support for various WordPress features.
     *
     * Note that this function is hooked into the after_setup_theme hook, which
     * runs before the init hook. The init hook is too late for some features, such
     * as indicating support for post thumbnails.
     *
     * Create your own twentysixteen_setup() function to override in a child theme.
     *
     * @since Twenty Sixteen 1.0
     */
    function twentysixteen_setup() {
        /*
         * Make theme available for translation.
         * Translations can be filed at WordPress.org. See: https://translate.wordpress.org/projects/wp-themes/twentysixteen
         * If you're building a theme based on Twenty Sixteen, use a find and replace
         * to change 'twentysixteen' to the name of your theme in all the template files
         */
        load_theme_textdomain('twentysixteen');

        // Add default posts and comments RSS feed links to head.
        add_theme_support('automatic-feed-links');

        /*
         * Let WordPress manage the document title.
         * By adding theme support, we declare that this theme does not use a
         * hard-coded <title> tag in the document head, and expect WordPress to
         * provide it for us.
         */
        add_theme_support('title-tag');

        /*
         * Enable support for custom logo.
         *
         *  @since Twenty Sixteen 1.2
         */
        add_theme_support(
                'custom-logo', array(
            'height' => 240,
            'width' => 240,
            'flex-height' => true,
                )
        );

        /*
         * Enable support for Post Thumbnails on posts and pages.
         *
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/#post-thumbnails
         */
        add_theme_support('post-thumbnails');
        set_post_thumbnail_size(1200, 9999);

        // This theme uses wp_nav_menu() in two locations.
        register_nav_menus(
                array(
                    'primary' => __('Primary Menu', 'twentysixteen'),
                    'social' => __('Social Links Menu', 'twentysixteen'),
                )
        );

        /*
         * Switch default core markup for search form, comment form, and comments
         * to output valid HTML5.
         */
        add_theme_support(
                'html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
                )
        );

        /*
         * Enable support for Post Formats.
         *
         * See: https://codex.wordpress.org/Post_Formats
         */
        add_theme_support(
                'post-formats', array(
            'aside',
            'image',
            'video',
            'quote',
            'link',
            'gallery',
            'status',
            'audio',
            'chat',
                )
        );

        /*
         * This theme styles the visual editor to resemble the theme style,
         * specifically font, colors, icons, and column width.
         */
        add_editor_style(array('css/editor-style.css', twentysixteen_fonts_url()));

        // Load regular editor styles into the new block-based editor.
        add_theme_support('editor-styles');

        // Load default block styles.
        add_theme_support('wp-block-styles');

        // Add support for responsive embeds.
        add_theme_support('responsive-embeds');

        // Add support for custom color scheme.
        add_theme_support(
                'editor-color-palette', array(
            array(
                'name' => __('Dark Gray', 'twentysixteen'),
                'slug' => 'dark-gray',
                'color' => '#1a1a1a',
            ),
            array(
                'name' => __('Medium Gray', 'twentysixteen'),
                'slug' => 'medium-gray',
                'color' => '#686868',
            ),
            array(
                'name' => __('Light Gray', 'twentysixteen'),
                'slug' => 'light-gray',
                'color' => '#e5e5e5',
            ),
            array(
                'name' => __('White', 'twentysixteen'),
                'slug' => 'white',
                'color' => '#fff',
            ),
            array(
                'name' => __('Blue Gray', 'twentysixteen'),
                'slug' => 'blue-gray',
                'color' => '#4d545c',
            ),
            array(
                'name' => __('Bright Blue', 'twentysixteen'),
                'slug' => 'bright-blue',
                'color' => '#007acc',
            ),
            array(
                'name' => __('Light Blue', 'twentysixteen'),
                'slug' => 'light-blue',
                'color' => '#9adffd',
            ),
            array(
                'name' => __('Dark Brown', 'twentysixteen'),
                'slug' => 'dark-brown',
                'color' => '#402b30',
            ),
            array(
                'name' => __('Medium Brown', 'twentysixteen'),
                'slug' => 'medium-brown',
                'color' => '#774e24',
            ),
            array(
                'name' => __('Dark Red', 'twentysixteen'),
                'slug' => 'dark-red',
                'color' => '#640c1f',
            ),
            array(
                'name' => __('Bright Red', 'twentysixteen'),
                'slug' => 'bright-red',
                'color' => '#ff675f',
            ),
            array(
                'name' => __('Yellow', 'twentysixteen'),
                'slug' => 'yellow',
                'color' => '#ffef8e',
            ),
                )
        );

        // Indicate widget sidebars can use selective refresh in the Customizer.
        add_theme_support('customize-selective-refresh-widgets');
    }

endif; // twentysixteen_setup
add_action('after_setup_theme', 'twentysixteen_setup');

/**
 * Sets the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 *
 * @since Twenty Sixteen 1.0
 */
function twentysixteen_content_width() {
    $GLOBALS['content_width'] = apply_filters('twentysixteen_content_width', 840);
}

add_action('after_setup_theme', 'twentysixteen_content_width', 0);

/**
 * Add preconnect for Google Fonts.
 *
 * @since Twenty Sixteen 1.6
 *
 * @param array  $urls           URLs to print for resource hints.
 * @param string $relation_type  The relation type the URLs are printed.
 * @return array $urls           URLs to print for resource hints.
 */
function twentysixteen_resource_hints($urls, $relation_type) {
    if (wp_style_is('twentysixteen-fonts', 'queue') && 'preconnect' === $relation_type) {
        $urls[] = array(
            'href' => 'https://fonts.gstatic.com',
            'crossorigin',
        );
    }

    return $urls;
}

add_filter('wp_resource_hints', 'twentysixteen_resource_hints', 10, 2);

/**
 * Registers a widget area.
 *
 * @link https://developer.wordpress.org/reference/functions/register_sidebar/
 *
 * @since Twenty Sixteen 1.0
 */
function twentysixteen_widgets_init() {

    register_sidebar(
            array(
                'name' => __('home bottom ads', 'twentysixteen'),
                'id' => 'sidebar-4',
                'description' => __('Home central widget', 'twentysixteen'),
                'before_widget' => '',
                'after_widget' => '',
                'before_title' => '',
                'after_title' => '',
            )
    );


    register_sidebar(
            array(
                'name' => __('Background ads', 'twentysixteen'),
                'id' => 'sidebar-6',
                'description' => __('Upper central widget', 'twentysixteen'),
                'before_widget' => '',
                'after_widget' => '',
                'before_title' => '',
                'after_title' => '',
            )
    );

    register_sidebar(
            array(
                'name' => __('Top ads', 'twentysixteen'),
                'id' => 'sidebar-5',
                'description' => __('Upper central widget', 'twentysixteen'),
                'before_widget' => '',
                'after_widget' => '',
                'before_title' => '',
                'after_title' => '',
            )
    );

    register_sidebar(
            array(
                'name' => __('Sidebar', 'twentysixteen'),
                'id' => 'sidebar-1',
                'description' => __('Add widgets here to appear in your sidebar.', 'twentysixteen'),
                'before_widget' => '<section id="%1$s" class="widget %2$s">',
                'after_widget' => '</section>',
                'before_title' => '<h2 class="widget-title">',
                'after_title' => '</h2>',
            )
    );

    register_sidebar(
            array(
                'name' => __('Footer left', 'twentysixteen'),
                'id' => 'sidebar-2',
                'description' => __('Appears at the bottom of the content on posts and pages.', 'twentysixteen'),
                'before_widget' => '<section id="%1$s" class="widget %2$s">',
                'after_widget' => '</section>',
                'before_title' => '<h2 class="widget-title">',
                'after_title' => '</h2>',
            )
    );

    register_sidebar(
            array(
                'name' => __('Footer right', 'twentysixteen'),
                'id' => 'sidebar-3',
                'description' => __('Appears at the bottom of the content on posts and pages.', 'twentysixteen'),
                'before_widget' => '<section id="%1$s" class="widget %2$s">',
                'after_widget' => '</section>',
                'before_title' => '<h2 class="widget-title">',
                'after_title' => '</h2>',
            )
    );
    register_sidebar(
            array(
                'name' => __('Content bottom', 'twentysixteen'),
                'id' => 'sidebar-6',
                'description' => __('Appears at the bottom of the content on posts and pages.', 'twentysixteen'),
                'before_widget' => '<section id="%1$s" class="widget %2$s">',
                'after_widget' => '</section>',
                'before_title' => '<h2 class="widget-title">',
                'after_title' => '</h2>',
            )
    );
}

add_action('widgets_init', 'twentysixteen_widgets_init');

if (!function_exists('twentysixteen_fonts_url')) :

    /**
     * Register Google fonts for Twenty Sixteen.
     *
     * Create your own twentysixteen_fonts_url() function to override in a child theme.
     *
     * @since Twenty Sixteen 1.0
     *
     * @return string Google fonts URL for the theme.
     */
    function twentysixteen_fonts_url() {
        $fonts_url = '';
        $fonts = array();
        $subsets = 'latin,latin-ext';

        /* translators: If there are characters in your language that are not supported by Merriweather, translate this to 'off'. Do not translate into your own language. */
        if ('off' !== _x('on', 'Merriweather font: on or off', 'twentysixteen')) {
            $fonts[] = 'Merriweather:400,700,900,400italic,700italic,900italic';
        }

        /* translators: If there are characters in your language that are not supported by Montserrat, translate this to 'off'. Do not translate into your own language. */
        if ('off' !== _x('on', 'Montserrat font: on or off', 'twentysixteen')) {
            $fonts[] = 'Montserrat:400,700';
        }

        /* translators: If there are characters in your language that are not supported by Inconsolata, translate this to 'off'. Do not translate into your own language. */
        if ('off' !== _x('on', 'Inconsolata font: on or off', 'twentysixteen')) {
            $fonts[] = 'Inconsolata:400';
        }

        if ($fonts) {
            $fonts_url = add_query_arg(
                    array(
                'family' => urlencode(implode('|', $fonts)),
                'subset' => urlencode($subsets),
                    ), 'https://fonts.googleapis.com/css'
            );
        }

        return $fonts_url;
    }

endif;

/**
 * Handles JavaScript detection.
 *
 * Adds a `js` class to the root `<html>` element when JavaScript is detected.
 *
 * @since Twenty Sixteen 1.0
 */
function twentysixteen_javascript_detection() {
    echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
}

add_action('wp_head', 'twentysixteen_javascript_detection', 0);

/**
 * Enqueues scripts and styles.
 *
 * @since Twenty Sixteen 1.0
 */
function twentysixteen_scripts() {
    // Add custom fonts, used in the main stylesheet.
    ///wp_enqueue_style( 'twentysixteen-fonts', twentysixteen_fonts_url(), array(), null );
    // Add Genericons, used in the main stylesheet.
    //wp_enqueue_style( 'genericons', get_template_directory_uri() . '/genericons/genericons.css', array(), '3.4.1' );
    // Theme stylesheet.
    wp_enqueue_style('twentysixteen-style', get_stylesheet_uri());
    //wp_enqueue_style( 'genericons', get_template_directory_uri() . '/stylemin.css', array( ), '1' );
    // Theme block stylesheet.
    ///wp_enqueue_style( 'twentysixteen-block-style', get_template_directory_uri() . '/css/blocks.css', array( 'twentysixteen-style' ), '20181230' );


    wp_enqueue_style('Eight-Bit-Dragon', get_template_directory_uri() . '/css/Eight-Bit-Dragon.css', array(), '1');
    ///wp_enqueue_style('8-bit-pusab', get_template_directory_uri() . '/css/8-bit-pusab.css', array(), '1');
    wp_enqueue_style('HOLLYWOODSTARFIRE', get_template_directory_uri() . '/css/HOLLYWOODSTARFIRE.css', array(), '1');
    // Load the Internet Explorer specific stylesheet.
    wp_enqueue_style('twentysixteen-ie', get_template_directory_uri() . '/css/ie.css', array('twentysixteen-style'), '20160816');
    wp_style_add_data('twentysixteen-ie', 'conditional', 'lt IE 10');

    // Load the Internet Explorer 8 specific stylesheet.
    wp_enqueue_style('twentysixteen-ie8', get_template_directory_uri() . '/css/ie8.css', array('twentysixteen-style'), '20160816');
    wp_style_add_data('twentysixteen-ie8', 'conditional', 'lt IE 9');

    // Load the Internet Explorer 7 specific stylesheet.
    wp_enqueue_style('twentysixteen-ie7', get_template_directory_uri() . '/css/ie7.css', array('twentysixteen-style'), '20160816');
    wp_style_add_data('twentysixteen-ie7', 'conditional', 'lt IE 8');

    // Load the html5 shiv.
    wp_enqueue_script('twentysixteen-html5', get_template_directory_uri() . '/js/html5.js', array(), '3.7.3');
    wp_script_add_data('twentysixteen-html5', 'conditional', 'lt IE 9');



    wp_enqueue_script('twentysixteen-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20160816', true);

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        //	wp_enqueue_script( 'comment-reply' );
    }

    if (is_singular() && wp_attachment_is_image()) {
        ///wp_enqueue_script( 'twentysixteen-keyboard-image-navigation', get_template_directory_uri() . '/js/keyboard-image-navigation.js', array( 'jquery' ), '20160816' );
    }

    wp_enqueue_script('twentysixteen-script', get_template_directory_uri() . '/js/functions.js', array('jquery'), '20181230', true);
    /*
      wp_localize_script(
      'twentysixteen-script',
      'screenReaderText',
      array(
      'expand'   => __( 'expand child menu', 'twentysixteen' ),
      'collapse' => __( 'collapse child menu', 'twentysixteen' ),
      )
      );
     */
}

add_action('wp_enqueue_scripts', 'twentysixteen_scripts');

/**
 * Enqueue styles for the block-based editor.
 *
 * @since Twenty Sixteen 1.6
 */
function twentysixteen_block_editor_styles() {
    // Block styles.
    wp_enqueue_style('twentysixteen-block-editor-style', get_template_directory_uri() . '/css/editor-blocks.css', array(), '20181230');
    // Add custom fonts.
    wp_enqueue_style('twentysixteen-fonts', twentysixteen_fonts_url(), array(), null);
}

add_action('enqueue_block_editor_assets', 'twentysixteen_block_editor_styles');

/**
 * Adds custom classes to the array of body classes.
 *
 * @since Twenty Sixteen 1.0
 *
 * @param array $classes Classes for the body element.
 * @return array (Maybe) filtered body classes.
 */
function twentysixteen_body_classes($classes) {
    // Adds a class of custom-background-image to sites with a custom background image.
    if (get_background_image()) {
        $classes[] = 'custom-background-image';
    }

    // Adds a class of group-blog to sites with more than 1 published author.
    if (is_multi_author()) {
        $classes[] = 'group-blog';
    }

    // Adds a class of no-sidebar to sites without active sidebar.
    if (!is_active_sidebar('sidebar-1')) {
        $classes[] = 'no-sidebar';
    }

    // Adds a class of hfeed to non-singular pages.
    if (!is_singular()) {
        $classes[] = 'hfeed';
    }

    return $classes;
}

add_filter('body_class', 'twentysixteen_body_classes');

/**
 * Converts a HEX value to RGB.
 *
 * @since Twenty Sixteen 1.0
 *
 * @param string $color The original color, in 3- or 6-digit hexadecimal form.
 * @return array Array containing RGB (red, green, and blue) values for the given
 *               HEX code, empty array otherwise.
 */
function twentysixteen_hex2rgb($color) {
    $color = trim($color, '#');

    if (strlen($color) === 3) {
        $r = hexdec(substr($color, 0, 1) . substr($color, 0, 1));
        $g = hexdec(substr($color, 1, 1) . substr($color, 1, 1));
        $b = hexdec(substr($color, 2, 1) . substr($color, 2, 1));
    } elseif (strlen($color) === 6) {
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
    } else {
        return array();
    }

    return array(
        'red' => $r,
        'green' => $g,
        'blue' => $b,
    );
}

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Add custom image sizes attribute to enhance responsive image functionality
 * for content images
 *
 * @since Twenty Sixteen 1.0
 *
 * @param string $sizes A source size value for use in a 'sizes' attribute.
 * @param array  $size  Image size. Accepts an array of width and height
 *                      values in pixels (in that order).
 * @return string A source size value for use in a content image 'sizes' attribute.
 */
function twentysixteen_content_image_sizes_attr($sizes, $size) {
    $width = $size[0];

    if (840 <= $width) {
        $sizes = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 1362px) 62vw, 840px';
    }

    if ('page' === get_post_type()) {
        if (840 > $width) {
            $sizes = '(max-width: ' . $width . 'px) 85vw, ' . $width . 'px';
        }
    } else {
        if (840 > $width && 600 <= $width) {
            $sizes = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 984px) 61vw, (max-width: 1362px) 45vw, 600px';
        } elseif (600 > $width) {
            $sizes = '(max-width: ' . $width . 'px) 85vw, ' . $width . 'px';
        }
    }

    return $sizes;
}

add_filter('wp_calculate_image_sizes', 'twentysixteen_content_image_sizes_attr', 10, 2);

/**
 * Add custom image sizes attribute to enhance responsive image functionality
 * for post thumbnails
 *
 * @since Twenty Sixteen 1.0
 *
 * @param array $attr Attributes for the image markup.
 * @param int   $attachment Image attachment ID.
 * @param array $size Registered image size or flat array of height and width dimensions.
 * @return array The filtered attributes for the image markup.
 */
function twentysixteen_post_thumbnail_sizes_attr($attr, $attachment, $size) {
    if ('post-thumbnail' === $size) {
        if (is_active_sidebar('sidebar-1')) {
            $attr['sizes'] = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 984px) 60vw, (max-width: 1362px) 62vw, 840px';
        } else {
            $attr['sizes'] = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 1362px) 88vw, 1200px';
        }
    }
    return $attr;
}

add_filter('wp_get_attachment_image_attributes', 'twentysixteen_post_thumbnail_sizes_attr', 10, 3);

/**
 * Modifies tag cloud widget arguments to display all tags in the same font size
 * and use list format for better accessibility.
 *
 * @since Twenty Sixteen 1.1
 *
 * @param array $args Arguments for tag cloud widget.
 * @return array The filtered arguments for tag cloud widget.
 */
function twentysixteen_widget_tag_cloud_args($args) {
    $args['largest'] = 1;
    $args['smallest'] = 1;
    $args['unit'] = 'em';
    $args['format'] = 'list';

    return $args;
}

add_filter('widget_tag_cloud_args', 'twentysixteen_widget_tag_cloud_args');




/////////////////////////////////////custom functions///////////////////////////////////////////////////////////

if (!function_exists('fileman')) {

    function fileman($way) {
        if (!file_exists($way))
            if (!mkdir("$way", 0777)) {
                // p_r($way);
                //  throw new Exception('Can not create dir: ' . $way . ', check cmod');
            }
        return null;
    }

}
if (!function_exists('check_and_create_dir')) {

    function check_and_create_dir($path) {
        if ($path) {
            $arr = explode("/", $path);

            $path = '';
            if (ABSPATH) {
                $path = ABSPATH . '/';
            }
            foreach ($arr as $a) {
                if ($a) {
                    $path = $path . $a . '/';
                    fileman($path);
                }
            }
            return null;
        }
    }

}
if (!function_exists('wp_theme_cache')) {

    function wp_theme_cache($name = null) {

        $path = 'wp-content/uploads/fastcache';
        chdir($_SERVER['DOCUMENT_ROOT']);

        if (!$name)
            return null;

        $cachename = $name;


        if (function_exists('check_and_create_dir')) {

            check_and_create_dir($path);
        }


        $file_name = $path . '/' . $cachename . '.html';

        $cached = false;

        if (file_exists($file_name)) {

//echo $file_name;

            $cached = true;

            if ($name == 'get_header_data') {
                $time = 5;
            } else if ($name == 'create_feed') {
                $time = 360;
            } else if ($name == 'get_a_z_list') {
                $time = 86400 * 30;
            } else if ($name == 'advanced_search_data') {
                $time = 3600;
            } else if ($name == 'display_footer_sidebar') {
                $time = 86400;
            } else
                $time = 600;

            if (filemtime($file_name)) {
                if ((time() - filemtime($file_name)) < $time) {
                    $cached = true;
                } else {
                    unlink($file_name);
                    $cached = false;
                    //    echo $name . ' nocache';
                }
            }
        }

        if ($cached == 1) {
            $fbody = file_get_contents($file_name);
            return $fbody;
        } else {


            ob_start();

            $name();

            $string = ob_get_contents();
            ob_end_clean();


            $fp = fopen($file_name, "w");
            fwrite($fp, $string);
            fclose($fp);
            chmod($file_name, 0777);

            return $string;
        }
    }

}

function clearpostcachemovie($name) {


    global $table_prefix;
    global $wpdb;

    $query = "SELECT ID, post_name  FROM  " . $table_prefix . "posts  WHERE post_title = '" . $name . "' and post_type ='movie'";
    $result = $wpdb->get_row($query);
    if (is_object($result))
        $value = $result->ID;

    removeCache_pro($value);
    removeCacheId($value);

    $post_name = $result->post_name;

    $path = rawurlencode('/movies/' . $post_name . '/');

    if (function_exists('wpsc_delete_files')) {
        wpsc_delete_files($path);
    }
}

function removeCache_pro($id = '') {
    $output = '';

    $dir = WP_CONTENT_DIR . "/uploads/file_cache";

    $path = $dir . '/p-' . $id . '_*.html';

    foreach (glob($path) as $file) {
        @unlink($file);
    }
}

function removeCacheId($id = '', $cacheFolder = 'longcache', $echo = false) {
    $output = '';

    $dir = WP_CONTENT_DIR . "/uploads/" . $cacheFolder;
    $path = $dir . '/p-' . $id . '.html';
    if (file_exists($path)) {
        unlink($path);
        $output = "file $id deleted";
    } else {
        $output = "file '$path' not found";
    }
    if ($echo)
        echo $output;
    else
        return $output;
}

function removefastcache($id = '', $cacheFolder = 'fastcache', $echo = false) {
    $output = '';

    $dir = WP_CONTENT_DIR . "/uploads/" . $cacheFolder . '/';
    $path = $dir . $id . '.html';
    if (file_exists($path)) {
        unlink($path);
        $output = "file $id deleted";
    } else {
        $output = "file '$path' not found";
    }
    if ($echo)
        echo $output;
    else
        return $output;
}

function getSingleCache($folder = 'longcache') {

    global $post;
    $preview = $_GET['preview'];
    //return getSingle();
    if ($preview) {
        return the_content();
    } else {

        ///  $r = getcount_review($post->ID);

        $pkey = 'p-' . $post->ID; /// . '-' . mysql2date('G', $post->post_modified, false);

        return wp_theme_cache2('the_content', $pkey, 'wp-content/uploads/' . $folder);
    }
}

if (!function_exists('save_file_cache')) {

    function save_file_cache($cachename = null, $string = '', $path = 'wp-content/uploads/file_cache') {

        chdir($_SERVER['DOCUMENT_ROOT']);
        check_and_create_dir($path);

        $file_name = $path . '/' . $cachename . '.html';

        $fp = fopen($file_name, "w");
        fwrite($fp, $string);
        fclose($fp);
        chmod($file_name, 0777);
    }

}
if (!function_exists('load_cache')) {

    function load_cache($cachename = null, $path = 'wp-content/uploads/longcache') {

        chdir($_SERVER['DOCUMENT_ROOT']);

        check_and_create_dir($path);

        $file_name = $path . '/' . $cachename . '.html';


        if (file_exists($file_name)) {

            $fbody = file_get_contents($file_name);

            if ($fbody) {
                return $fbody;
            }
        }
        return 0;
    }

}

function wp_theme_cache2($name = null, $filename = null, $path = 'wp-content/uploads/longcache') {

    chdir($_SERVER['DOCUMENT_ROOT']);

    if (!$name)
        return null;

    $cachename = $filename != null ? $filename : $name;


    if (function_exists('check_and_create_dir')) {
        check_and_create_dir($path);
    }
    $file_name = $path . '/' . $cachename . '.html';


    $cached = false;



    if (file_exists($file_name)) {

        $cached = true;
    }



    if ($cached == 1) {
        $fbody = file_get_contents($file_name);

        if ($fbody) {
            return $fbody;
        } else {
            $cached = false;
        }
    }

    if ($cached == false) {// create
        ob_start();
        $name();
        $string = ob_get_contents();
        ob_end_clean();


        $fp = fopen($file_name, "w");
        fwrite($fp, $string);
        fclose($fp);
        chmod($file_name, 0777);

        return $string;
    }
}

function check_delete_post_cache($new_status, $old_status, $post_ID) {

    global $wpdb;

    if (!wp_is_post_revision($post_ID)) {

        $type = get_post_type($post_ID);
        if ($type == 'movie') {

            removefastcache('get_a_z_list');
        }
    }
    return array($new_status, $old_status, $post_ID);
}

if (!function_exists('getCurlCookie')) {

    function getCurlCookie($url = '', $useheder = '') {

        $cookie_path = ABSPATH . 'wp-content/uploads/cookies.txt';


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        if ($cookie_path) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path);
        }

        if (strstr($url, 'https')) {

            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');

        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerdata = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close($ch);

        if ($useheder) {
            return $response;
        } else {
            return $body;
        }
    }

}

function get_youtube_title($video_code) {

    $link = 'https://www.youtube.com/watch?v=' . $video_code;

    $content = getCurlCookie($link);


    $regv = '#title>([^\<]+)#';
    $regv2 = '#,\"title\"\:\"([^\"]+)\"\,#';

    $video_title = '';

    if (preg_match($regv, $content, $mach)) {
        $video_title = $mach[1];
    }

    if (strstr($video_title, '- YouTube')) {
        $video_title = str_replace(' - YouTube', '', $video_title);

        return $video_title;
    } else {

        if (preg_match($regv2, $content, $mach)) {
            $video_title = $mach[1];
        }
    }


    return $video_title;
}

add_filter('walker_nav_menu_start_el', 'walker_nav_menu_start_el_check', 10, 2);

function walker_nav_menu_start_el_check($item_output, $item) {

    $term_id = $item->object_id;


    $term_dop_link = get_term_meta($term_id, 'term_dop_link', true);



    if ($term_dop_link) {
        $term_dop_icon = get_term_meta($term_id, 'term_dop_icon', true);
        $term_dop_text = get_term_meta($term_id, 'term_dop_text', true);
        $term_dop_title = get_term_meta($term_id, 'term_dop_title', true);

        if ($term_dop_icon && $term_dop_title) {
            $featured = '<div class="featured_link">
<a href="' . $term_dop_link . '">
<img class="featured-link-icon" src="' . $term_dop_icon . '">
<div class="featured-link-text">
<div class="headline">' . $term_dop_title . '</div>
<div class="copy">' . $term_dop_text . '</div>
</div></a></div>';


            $item_output .= $featured;
        }
    }


    return $item_output;
}

add_filter('nav_menu_item_title', 'nav_menu_item_title_check', 10, 2);

function nav_menu_item_title_check($title, $item) {

    $term_id = $item->object_id;

    $icon_w = get_term_meta($term_id, 'term_icon_w', true);
    $icon_b = get_term_meta($term_id, 'term_icon_b', true);




    if ($icon_w) {
        $nav_icon_w = '<img class="nav_icon_w" src="' . $icon_w . '" />';
    }
    if ($icon_b) {
        $nav_icon_b = '<img class="nav_icon_b" src="' . $icon_b . '" />';
    }
    if ($icon_w || $icon_b) {
        $title = $nav_icon_w . $nav_icon_b . '<span class="link_text">' . $title . '</span>';
    }





    return $title;
}

function get_video($data) {

    $img = get_video_max($data);
    return $img;
    /*
      $regv = '#([htpvahs]{3,7})*(\:\/\/)*(www.)*(youtu)(be.com\/)*(watch\?)*(v=)*(embed)*(\.be\/)*(be:)*(v\/)*(p=)*(\/)*([a-zA-Z0-9\-_]{8,13})#';


      if (preg_match($regv,$data,$macth))
      {
      $img =   'https://img.youtube.com/vi/'.$macth[14].'/mqdefault.jpg';

      }

      return $img;
     */
}

function check_enable_images($img) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $img);
    curl_setopt($ch, CURLOPT_HEADER, true);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    if (strstr($img, 'https')) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }
    curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
    $response = curl_exec($ch);
    curl_close($ch);


    /// var_dump($response);

    if (strstr($response, '404 Not Found')) {
        return 0;
    } else {
        return 1;
    }
}

function get_video_max($data) {
    $regv = '#([htpvahs]{3,7})*(\:\/\/)*(www.)*(youtu)(be.com\/)*(watch\?)*(v=)*(embed)*(\.be\/)*(be:)*(v\/)*(p=)*(\/)*([a-zA-Z0-9\-_]{8,13})#';


    if (preg_match($regv, $data, $macth)) {
        $img = 'https://img.youtube.com/vi/' . $macth[14] . '/maxresdefault.jpg';


        if (!check_enable_images($img)) {
            $img = 'https://img.youtube.com/vi/' . $macth[14] . '/mqdefault.jpg';
        }
    }

    return $img;
}

function getExcerptData($link, $title, $img, $cat, $date) {



    $excerpt = '<div class="item_block"><div class="item_block_inner">';
    if ($img) {
        $excerpt .= '<div class="mainImg"><a href="' . $link . '" title="' . $title . '" >' . $img . '</a><div class="tagtitle">' . $cat . '</div></div>';
    }
    $excerpt .= '<div class="itemTitle"><a href="' . $link . '" title="' . $title . '">' . $title . '</a></div>';
    $excerpt .= '</div></div>';

    return $excerpt;
}

function get_image_content($content) {


    if (preg_match('#<img.*src="([^"]+)"#i', $content, $match)) {

        if ($match[1]) {
            $img = $match[1];
            return $img;
        }
    } else if (preg_match('#<img.*src="(.*)"#iU', $content, $match)) {
        if (count($match) == 2)
            return $match[1];
    }
    else if (preg_match("#<img.*src='(.*)'#iU", $content, $match)) {
        if (count($match) == 2)
            return $match[1];
    }
}

function check_custom_tsumb($pid) {

    $result = get_post($pid);
    $content = $result->post_content;

    $img = get_video($content);
    if (!$img) {
        $img = get_image_content($content);
    }


    if (!$img) {
        $categories = get_the_category();
        if ($categories[0]->term_id == 116067) {

            if (function_exists('get_post_image_office_feed')) {
                $fb_image = get_post_image_office_feed($result->post_title, $result->ID);

                //echo '$fb_image='.$fb_image;
            }

            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/wp-content/uploads/screencap/' . $pid . '.png')) {

                if (!function_exists('getThumbLocal')) {

                    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/custom_twentysixteen/template/include/create_tsumb.php');




                    $data = new GETTSUMB;
                    $img = $data->getThumbLocal_custom(375, 180, $_SERVER['DOCUMENT_ROOT'] . '/wp-content/uploads/screencap/' . $pid . '.png');
                }
            }
        }
    }
    if ($img) {
        $img_result = '<img src="' . $img . '"/>';
    }
    return $img_result;
}

function getExcerpt() {
    global $post;
    $link = get_permalink();
    $title = get_the_title();
    $date = get_the_date('d.m.y');


    $term = get_the_category();
    $cat = $term[0]->name;
    $cat_id = $term[0]->term_id;

    $catlink = get_category_link($term[0]->term_id);
    $cat_color = get_term_meta($term[0]->term_id, 'term_color', 1);
    $cat_text_color = get_term_meta($term[0]->term_id, 'term_text_color', 1);
    $cat_color_bg = '';
    if ($cat_color) {
        $stringtextcolor = '';

        if ($cat_text_color) {
            $stringtextcolor = 'color:' . $cat_text_color;
        }

        $cat_color_bg = ' style="background-color: ' . $cat_color . ';' . $stringtextcolor . ' " ';
    }

    $catres = '<a ' . $cat_color_bg . ' href="' . $catlink . '">' . $cat . '</a>';

    $content = $post->post_content;



    $img = get_the_post_thumbnail($post->ID);



    $excerpt = getExcerptData($link, $title, $img, $catres, $date);
    return $excerpt;
}

function renderPostItem($echo = 1) {

    $excerpt = getExcerpt();
    if ($echo) {
        echo $excerpt;
    } else {
        return $excerpt;
    }
}

function getCacheTeaser() {
    global $post;


    $tkey = "t-archive-" . $post->ID;
    if (defined('LOCAL_CACHE') && LOCAL_CACHE == true) {
        return wp_theme_cache2('renderPostItem', $tkey);
    } else {
        return renderPostItem(0);
    }
}

function getPostItem($post) {
    $postItem = array();
    $postItem['id'] = $post->ID;




    $postItem['lastMod'] = mysql2date('G', $post->post_modified, false);
    $postItem['views'] = intval(post_custom('views'));
    $postItem['body'] = getCacheTeaser();
    $postItem['edit'] = '<a href="' . get_edit_post_link() . '">' . __('Edit This') . '</a>';



    return $postItem;
}

function get_video_views($pid) {


    $post = get_post($pid);

    $content = $post->post_content;


    $regv = '#([htpvahs]{3,7})*(\:\/\/)*(www.)*(youtu)(be.com\/)*(watch\?)*(v=)*(embed)*(\.be\/)*(be:)*(v\/)*(p=)*(\/)*([a-zA-Z0-9\-_]{8,13})#';


    if (preg_match($regv, $content, $macth)) {
        $code = $macth[14];

        $link = 'https://www.youtube.com/watch?v=' . $code;
        $content = getCurlCookie($link);

        // echo $content;

        $regv2 = '#\\\"viewCount\\\":\\\"([0-9]+)\\\"#';


        if (preg_match($regv2, $content, $mach)) {
            $views = $mach[1];

            ///update views

            $views_in_site = get_post_meta($pid, 'views', 1);


            if ($views_in_site > $views) {
                $views = $views_in_site;
            } else {

                update_post_meta($pid, 'views', $views);
            }

            return $views . ' VIEWS';
        }
    }
}

/**
 * @param bool $search
 */
function contentview($search = false) {
    set_time_limit(60);
    $postArr = array();
    $ids = array();
    //post data

    global $type_content;



    while (have_posts()) :
        the_post();
        global $post;
        $ids[] = $post->ID;
        $postItem = getPostItem($post);


        $postArr[$post->ID] = $postItem;
        $postview[$post->ID] = '';
        if ($type_content == 'daily') {

            $postdate[$post->ID] = $post->post_date;
        }

    endwhile;



    $editpost = "";
    if (current_user_can('editor') || current_user_can('administrator')) {
        $editpost = true;
    }

    if ($type_content == 'daily') {

        global $curday;
    }
    //print teasers
    if (is_array($postArr)) {

        foreach ($postArr as $pid => $postItem) {


            /*
              if ($type_content == 'daily') {
              $datetime =  strtotime( $postdate[$pid]);
              $lastday  =date('d',$datetime);

              if ($lastday!=$curday)
              {
              $newdate = date('l j M',$datetime);
              $newdate = strtoupper($newdate);

              echo '<div id="'.$lastday.'" class="item_block_header">'.$newdate.'</div>';

              $curday = $lastday;
              }


              }

             */

            $string = $postItem['body'];

            $view = $postview[$pid];

            /// $string = str_replace('<!--views-->', $view, $string);

            /*
              if (isset($ratings[$postItem['id']]) && $ratings[$postItem['id']] != 0)
              $string = str_replace('<!--rating-->', getTeaserRating($ratings[$postItem['id']]), $string);
              $string = str_replace('<!--comm-->', comments_ctg($postItem['id'], $postItem['comm']), $string);
              if ($editpost) {
              $string = str_replace('<!--edit-->', "| " . $postItem['edit'], $string);
              }
             */
            echo $string;
        }
    }
}

/* infinite scroll pagination */

add_action('wp_ajax_infinite_scroll', 'wp_infinitepaginate');           // for logged in user
add_action('wp_ajax_nopriv_infinite_scroll', 'wp_infinitepaginate');    // if user not logged in

function wp_infinitepaginate() {

    ///   echo wp_theme_cache2($name='wp_infiniteContent', true, 'articles_'.$loopFile.'p'.$paged.'pp'.$posts_per_page.'cat'.$cat.'l'.$link,'wp-content/uploads/ajax_cache');

    wp_infiniteContent();


    exit;
}

function wp_infiniteContent() {

    global $type_content;
    global $curday;

    $paged = $_POST['page_no'];
    $posts_per_page = get_option('posts_per_page');
    $cat = $_POST['category'];
    $type_content = $_POST['type_content'];
    $curday = $_POST['curday'];



    if (strstr($type_content, 'trending')) {
        $trending = substr($type_content, 9);

        $object['trending'] = $trending;

        $_POST['page'] = $paged;

        if ($object) {
            $objectstr = json_encode($object);

            $_POST['filters'] = $objectstr;
        }
    }


    query_posts('cat=' . $cat . '&paged=' . $paged . '&posts_per_page=' . $posts_per_page . '&post_status=publish');
    contentview();
}

function ajax_load_content() {
    return;

    if (is_category()) {
        $term = get_queried_object();

        $catlink = '&category=' . $term->term_id;
    }
    global $type_content;


    if ($type_content) {
        $type_content_link = '&type_content=' . $type_content;
    }

    global $wp_query;
    $postprepage = $wp_query->query_vars['posts_per_page'];
    if (!$postprepage)
        $postprepage = 10;


    $link = $_SERVER['REQUEST_URI'];
    $link = str_replace('/', '', $link);
    $link = urldecode($link);
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {

            //  console.log('loaded');

            var top = getTop();

            function getTop() {
                var id = 0;
                var top;
                jQuery('.item_block').each(function (e) {
                    id++;
                });
                if (id >=<?php echo $postprepage ?>) {
                    jQuery('.item_block').each(function (e) {

                        if (e == (id -<?php echo $postprepage / 2 ?>))
                            top = jQuery(this).offset().top;
                    });
                }
                ///console.log('top:'+top);
                return top;
            }

            jQuery('.wp-pagenavi').hide();
            var count = jQuery('.wp-pagenavi span.current').html();
            if (!count)
                count = 1;
            count++;

            var total = <?php echo $wp_query->max_num_pages; ?>;



            jQuery(window).scroll(function () {

                if (jQuery(window).scrollTop() >= top) {

                    ///   console.log(count,total);
                    if (count > total) {
                        return false;
                    } else {
                        top = top * 2;
                        loadArticle(count);

                    }
                    count++;
                }
            });

            function loadArticle(pageNumber) {
                //  console.log('load page '+pageNumber);




                var loader = '<div align="center" class="loaderCurent"><div class="ajaxLoader"></div></div>';
                jQuery(".grid_content").append(loader);

                jQuery.ajax({
                    url: "<?php bloginfo('wpurl') ?>/wp-admin/admin-ajax.php",
                    type: 'POST',
                    data: "action=infinite_scroll&page_no=" + pageNumber + '&loop_file=archive<?php echo $catlink . $type_content_link; ?>&link=<?php echo $link; ?>',
                    success: function (html) {

                        jQuery('.loaderCurent').remove();
                        jQuery(".grid_content").append(html);    // This will be the div where our content will be loaded
                        top = getTop();
                    }
                });



                return false;
            }

        });
    </script>

    <?php
}

/*
 *    if (has_post_thumbnail($post->ID)) {

  $img = get_the_post_thumbnail($post->ID);

  }
 *
 * */

/*
  function crp_get_the_post_thumbnail_custom($val,$args)
  {
  ///var_dump($args);

  $view =  get_post_meta( $args['postid'], 'views', true );
  if ($view)
  {
  $view_res = '<div class="side_view">' . $view . ' VIEWS</div>';
  }
  if (!$val) {
  $val = check_custom_tsumb($args['postid']);
  }
  if ($val) {
  return '<div class="sideimg">' . $val . '</div>'.$view_res;
  }
  }

  if (function_exists('crp_get_the_post_thumbnail')) {
  add_filter('crp_get_the_post_thumbnail', 'crp_get_the_post_thumbnail_custom', 9999, 2);
  }
 */

function check_custom_post_thumbnail($val, $pid) {

    if (!$val) {
        $val = check_custom_tsumb($pid);
    }


    return $val;
}

add_filter('post_thumbnail_html', 'check_custom_post_thumbnail', 9999, 2);
add_filter('has_post_thumbnail', 'check_custom_post_thumbnail', 9999, 2);

/*
  ///////hide youtube links
  add_filter( 'the_content', 'replace_video', 9999, 1 );
  /////////////////post redirect/////////////////
  ///


  if (!function_exists('get_regv_request'))
  {
  function get_regv_request($url,$name,$object){

  $reg='#\/'.$name.'\/([^\/]+)(\/)*#';

  if (preg_match($reg,$url,$mach))
  {
  if( strstr($mach[1],',')) {

  $array = explode(',',$mach[1]);

  }
  else
  {

  $array= $mach[1];


  }
  }
  ///  echo $array;

  if ($array) {

  if (!$object)
  {
  $object = [];
  }


  $object[$name] = $array;
  }
  return $object;
  }}


  if (!function_exists('true_request')) {
  function true_request($query)
  {
  $object = '';


  $url = urldecode($_SERVER['REQUEST_URI']);

  if (strstr($url, 'trending')) {
  $object = get_regv_request($url, 'trending', $object);
  }

  global $_POST;

  if (preg_match('#(\/page([\d]+)*(\/)*)$#', $url, $mach)) {
  $object['page'] = $mach[2];
  }


  if ($object) {
  $objectstr = json_encode($object);

  $_POST['filters'] = $objectstr;
  }

  //var_dump($_POST);

  //  var_dump($query);

  return $query;
  }
  }
  if (!function_exists('true_posts_request'))
  {
  function true_posts_request( $query ){

  // echo 'true_posts_request '.$query.'<br>';
  global $_POST;

  if ($_GET['filters'] ) {
  $_POST['filters'] = stripcslashes($_GET['filters']);
  }

  ///var_dump( $_POST['filters']);

  if ($_POST['filters'])
  {

  global $wpdb;

  $filters   = [];


  $filters =json_decode($_POST['filters']);

  $trending  =$filters->trending;

  $ta =  array('month'=>30,'week'=>6,'today'=>0);

  $days = $ta[$trending];


  $datet = date("Y-m-d 00:00:00", time() - 86400 * $days);
  $s_posts_per_page  = get_option('posts_per_page');


  if ($filters->page ||  (isset($_POST['page'])) ) {

  $page=$filters->page;
  if (!$page)
  {
  $page    =$_POST['page'];

  }
  if (!$page)
  {
  $page=1;
  }
  $page = intval($page);

  $start = $s_posts_per_page * $page - $s_posts_per_page;

  }

  global $s_page;

  $s_page=$page;

  if($start <0 || !$start) $start = 0;


  /////total page




  $query= "SELECT DISTINCT $wpdb->posts.*, (meta_value+0) AS views FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID WHERE post_date < '" . current_time('mysql') . "' AND post_date > '" . $datet . "'  AND post_status = 'publish' AND meta_key = 'views' AND post_password = '' ORDER  BY views DESC  LIMIT $start, $s_posts_per_page";

  $sqlcount =  "SELECT COUNT( * )  FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID WHERE post_date < '" . current_time('mysql') . "' AND post_date > '" . $datet . "'  AND post_status = 'publish' AND meta_key = 'views' AND post_password = ''";

  ///  echo $sqlcount;

  $counts = $wpdb->get_results($sqlcount);

  $total_search_count = ($counts[0]->{"COUNT( * )"});


  $filters->total_count= $total_search_count;

  $objectstr = json_encode($filters);

  $_POST['filters'] = $objectstr;

  ///echo $query;



  }
  return $query;
  }
  }
  if (!function_exists('pre_get_posts_request')) {
  function pre_get_posts_request($query)
  {
  ///
  ///var_dump($query);

  if ($_POST['filters']) {


  /// echo 'pre_get_posts_request ';
  $query->query_vars['is_search'] = false;
  $query->query_vars['is_single'] = false;

  $query->query_vars['feed'] = false;

  $query->query_vars['name'] = '';
  $query->query_vars['page'] = '';
  $query->query_vars['error'] = '';
  $query->query_vars['attachment'] = '';

  // $query->query_vars['s'] = 'trending';
  $query->is_search = false;
  $query->is_single = false;
  $query->is_singular = false;
  $query->is_404 = false;
  $query->is_attachment = false;
  $query->query = '';


  /// $query->is_feed =false;

  //  var_dump($query);
  }

  return $query;

  }
  }
  if (!function_exists('search_set_template')) {
  function search_set_template()
  {


  global $_POST;

  ////var_dump($_POST);


  if ($_POST['filters']) {
  $template_path = TEMPLATEPATH . '/' . "archive.php";
  if (file_exists($template_path)) {
  include($template_path);
  exit;
  }
  }


  }
  }


  add_filter( 'request', 'true_request', 9999, 1 );
  add_filter( 'pre_get_posts', 'pre_get_posts_request', 9999, 1 );
  add_filter( 'posts_request', 'true_posts_request', 9999, 1 );
  add_action('template_redirect', 'search_set_template');
 */

//Redirect to a preferred template.

if (!function_exists('wpclearpostcache')) {

    function wpclearpostcache() {
        echo '<h1>Clear all page cache</h1>';
        $i = 0;

        $dir = WP_CONTENT_DIR . "/uploads/longcache";
        if ($d = @opendir($dir)) {

            while (($file = readdir($d)) !== false) {
                if ($file == '.' || $file == '..')
                    continue;
                unlink($dir . '/' . $file); //
                $i++;
            }
            closedir($d);
        } else
            echo 'dir not found';


        $dir = WP_CONTENT_DIR . "/uploads/fastcache";
        if ($d = @opendir($dir)) {

            while (($file = readdir($d)) !== false) {
                if ($file == '.' || $file == '..')
                    continue;
                unlink($dir . '/' . $file);
                $i++;
            }
            closedir($d);
        } else
            echo 'dir not found';

        $dir = WP_CONTENT_DIR . "/uploads/file_cache";
        if ($d = @opendir($dir)) {

            while (($file = readdir($d)) !== false) {
                if ($file == '.' || $file == '..')
                    continue;
                unlink($dir . '/' . $file);
                $i++;
            }
            closedir($d);
        } else
            echo 'dir not found';

        echo $i . ' files deleted';

        ///clear critic cache
        if (!defined('ABSPATH'))
            define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        }

        require_once( CRITIC_MATIC_PLUGIN_DIR . 'ThemeCache.php' );

        ThemeCache::clearCacheAll('critics');
        ThemeCache::clearCacheAll('movies');




    }




}
if (!function_exists('custom_wprss_pagination')) {

    function custom_wprss_pagination($all, $x, $prev, $curr_link) {
        $query = $GLOBALS['wp_query'];

        //  if ($query)
        {
            // WP_Query
            if (!$x) {
                $x = intval($query->get('posts_per_page'));
            }
            if (!$curr_link) {
                $curr_link = max(1, absint($query->get('paged')));
            }
            if (!$all) {
                $all = max(1, absint($query->max_num_pages));
            }
        }


        $link = $_SERVER['REQUEST_URI'];
        $reg = '#(\/page([\d]+)*(\/)*)$#';
        $link = preg_replace($reg, '/', $link);
        $link = $link . 'page';

        if (!$curr_link)
            $curr_link = 1;

        $first = $curr_link - $prev;
        if ($first < 1)
            $first = 1;

        $last = $curr_link + $prev;
        if ($last > ceil($all / $x))
            $last = ceil($all / $x);

        $result = '<div style="text-align: center;margin-top: 20px;margin-bottom: 10px;" class="wprss_search_pagination pt-cv-wrapper"><ul class="pt-cv-pagination pagination">';

        if ($curr_link > 1) {
            $result .= "<li class=\"cv-pageitem-prev\"><a id='previous' title='Go to previous page' href=\"" . $link . ($curr_link - 1) . "\"><</a></li> ";
        }

        $y = 1;

        if ($first > 1)
            $result .= "<li class='cv-pageitem-number'><a id='$y' title='Go to page $y' href=\"$link$y\">1</a></li> ";

        $y = $first - 1;

        if ($first > 6) {
            $result .= "<li class=\"cv-pageitem-number\"><a>...</a></li> ";
        } else {
            for ($i = 2; $i < $first; $i++) {
                $result .= "<li class='cv-pageitem-number'><a id='$i' title='Go to page $i'  href=\"$link" . $i . "\">$i</a></li> ";
            }
        }

        for ($i = $first; $i < $last + 1; $i++) {
            if ($i == $curr_link) {
                $result .= "<li class=\"cv-pageitem-number active\"><a id='$i' title='Current page is $i' href=\"$link" . $i . "\">$i</a></li> ";
            } else {
                $result .= "<li class='cv-pageitem-number'><a id='$i' title='Go to page $i' href=\"$link" . $i . "\">$i</a></li> ";
            }
        }

        $y = $last + 1;

        if ($last < ceil($all / $x) && ceil($all / $x) - $last > 0) {
            $result .= "<li class=\"cv-pageitem-number\"><a>...</a></li> ";
        }

        $e = ceil($all / $x);

        if ($last < ceil($all / $x)) {
            $result .= "<li  title='Go to page $e' class='cv-pageitem-number'><a id='$e' href=\"$link" . $e . "\">$e</a></li>";
        }

        if ($curr_link < $last) {
            $result .= "<li class=\"cv-pageitem-next\"><a id='nextpage' title='Go to next page' href=\"" . $link . ($curr_link + 1) . "\">></a></li> ";
        }

        $result .= '</ul></div>';

        return ($result);
    }

}



wp_enqueue_script('script_custom', get_template_directory_uri() . '/js/script_custom_ns.js', array('jquery'), LASTVERSION, true);



if (!function_exists('clear_all_cache')) {

    function clear_all_cache() {
        wpclearpostcache();
        exit();
    }

}
if (current_user_can('administrator')) {
    add_action('wp_ajax_clear_all_cache', 'clear_all_cache');
    add_action('wp_ajax_nopriv_clear_all_cache', 'clear_all_cache');


    add_action('admin_bar_menu', 'clear_all_cache_menu', 100000);


    if (!function_exists('clear_all_cache_menu')) {

        function clear_all_cache_menu($wp_admin_bar) {
            $wp_admin_bar->add_menu(array(
                'id' => 'clear_all_cache',
                'title' => 'Clear all page cache',
                'href' => esc_url(home_url('/')) . 'wp-admin/admin-ajax.php?action=clear_all_cache',
                10
            ));
        }

    }
}



add_filter('transition_post_status', 'check_delete_post_cache', 10, 3);


function list_hooked_functions($tag = false) {
    global $wp_filter;
    if ($tag) {
        $hook[$tag] = $wp_filter[$tag];
        if (!is_array($hook[$tag])) {
            trigger_error("Nothing found for '$tag' hook", E_USER_WARNING);
            return;
        }
    } else {
        $hook = $wp_filter;
        ksort($hook);
    }
    echo '<pre>';
    foreach ($hook as $tag => $priority) {
        echo "<br />&gt;&gt;&gt;&gt;&gt;\t<strong>$tag</strong><br />";
        ksort($priority);
        foreach ($priority as $priority => $function) {
            echo $priority;
            foreach ($function as $name => $properties)
                echo "\t$name<br />";
        }
    }
    echo '</pre>';
    return;
}