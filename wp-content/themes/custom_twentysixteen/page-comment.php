<?php
/**
Template Name: comments
 */


get_header();
?>

<div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
                <?php
                // Start the loop.
                while ( have_posts() ) :
                        the_post();

?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <header class="entry-header">
                                <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                        </header><!-- .entry-header -->
                        <div class="entry-content">

<?php


include (ABSPATH.'wp-content/themes/custom_twentysixteen/template/ajax/disqus_last_comments.php');

echo get_last_disqus_comment(20);

wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);




?>


                        </div><!-- .entry-content -->
                </article>

<script type="text/javascript">
    var loaded = 0;
function loadArticle(){
    loaded=1;
    var last_num  = jQuery('.next_cursor').html();
    if (last_num)
    {
        jQuery('.next_cursor').remove();

        var template_path = "/wp-content/themes/custom_twentysixteen/template/ajax/";
        url =window.location.protocol + template_path + "disqus_last_comments.php?count=20&cursor="+last_num;

    jQuery.ajax({
        type: "GET",
        url: url,
        success: function (data) {
            jQuery('.entry-content').append(data);
            loaded=0;
        }});
    }

}
    jQuery(window).scroll(function(){

        var document_heigh = jQuery('.entry-content').height();
        var wh  =jQuery(window).height();
       /// console.log(jQuery(window).scrollTop()+wh+200, document_heigh);

        if  (jQuery(window).scrollTop()+wh+200 >= document_heigh){
            if (!loaded)
            {
                loadArticle();
            }


        }
    })


</script>


                        <?php


                        // End of the loop.
                endwhile;
                ?>

        </main><!-- .site-main -->
</div><!-- .content-area -->
<?php get_footer(); ?>


