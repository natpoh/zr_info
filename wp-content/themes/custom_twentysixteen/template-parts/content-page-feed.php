<?php
/**
 * Template part for displaying pages.
 *
 * @package Dyad
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
    </header><!-- .entry-header -->

    <?php twentysixteen_post_thumbnail(); ?>

    <div class="entry-content">
			<?php


global $feed_include;
$feed_include=1;


global $feed_preiew;
$feed_preiew=1;

            include ($_SERVER['DOCUMENT_ROOT'].'/wp-content/plugins/cirtics-review/search/createfeed.php');

            if (function_exists('create_feed'))
            {


            if ( defined( 'LOCAL_CACHE' ) && LOCAL_CACHE == true && $_GET['preview']!='true') {
           echo   wp_theme_cache('create_feed');
            }
           else
            {
                create_feed();

            }
            }

          ////  $rendered_styles = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/cirtics-review/search/feed-style.css');
?>

            <style type="text/css">
                @font-face {
                    font-family: "HOLLYWOODSTARFIRE";
                    src: url("<?php echo site_url() ?>/wp-content/plugins/cirtics-review/fonts/HS/HOLLYWOOD STARFIRE.eot");
                    src: url("<?php echo site_url()  ?>/wp-content/plugins/cirtics-review/fonts/HS/HOLLYWOOD STARFIRE.eot?#iefix") format("embedded-opentype"),
                    url("<?php echo site_url()  ?>/wp-content/plugins/cirtics-review/fonts/HS/HOLLYWOODSTARFIRE.woff") format('woff'),
                    url("<?php echo site_url()  ?>/wp-content/plugins/cirtics-review/fonts/HS/HOLLYWOODSTARFIRE.ttf") format('truetype');
                    font-weight: normal;
                    font-style: normal;
                }

                .site-content {

                    overflow: visible!important;

                }
                .fhd {
                    display: none;
                }

            </style>



            <?php


           $morecss =  '<link rel="stylesheet" href="'.site_url().'/wp-content/plugins/cirtics-review/search/feed-style.css" />';
        ///    echo $morecss;

           $custom_fonts_links = '<link href="https://fonts.googleapis.com/css?family=Barlow+Condensed|Nunito+Sans|Vesper+Libre&display=swap" rel="stylesheet">';
          echo $custom_fonts_links ;

/////. '<style type="text/css">' . $rendered_styles . '</style>'


            the_content(); ?>
			<?php
				wp_link_pages( array(
					'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'dyad' ),
					'after'  => '</div>',
				) );



            ////last post





				?>




	</div><!-- .entry-inner -->

    <?php
    edit_post_link(
        sprintf(
        /* translators: %s: Name of current post */
            __( 'Edit<span class="screen-reader-text"> "%s"</span>', 'twentysixteen' ),
            get_the_title()
        ),
        '<footer class="entry-footer"><span class="edit-link">',
        '</span></footer><!-- .entry-footer -->'
    );
    ?>

</article><!-- #post-<?php the_ID(); ?> -->


