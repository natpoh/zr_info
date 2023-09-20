<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
?>

</div><!-- .site-content -->

<footer id="colophon" class="site-footer" role="contentinfo">
    <?php
    include(get_template_directory() . '/template/plugins/mailpoet_widgets.php');

    echo '<div id="rwt_footer" class="footer_main">';

    if (is_active_sidebar('sidebar-2')) {
        echo '<div class="footer_main_left">';
        dynamic_sidebar('sidebar-2');
        echo '</div>';
    }
    if (is_active_sidebar('sidebar-3')) {
        echo '<div class="footer_main_right">';
        dynamic_sidebar('sidebar-3');
        echo '</div>';
    }
    
    echo '</div>';    
    ?>
</footer><!-- .site-footer -->
<?php

//wp_footer();

print get_zr_footer();

//if (isset($_GET['check']))
//{
//    gmi('footer before print');
//    echo '<!--'.PHP_EOL;
//    global $gmi; if ($gmi) {
//    foreach ($gmi as $i=>$val)
//    {
//        echo $val.'   '.$i.PHP_EOL;
//    }
//}
//    echo '-->';
//
//}
//list_hooked_functions();
?>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-124487298-2"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag() {
        dataLayer.push(arguments);
    }
    gtag('js', new Date());

    gtag('config', 'UA-124487298-2');
</script>
</div><!-- .site -->
<?php
/*
gmi('footer before print');

global $gmi;
if ($gmi) {
    foreach ($gmi as $i => $val) {
        echo $val . '   ' . $i . PHP_EOL;
    }
}
*/
?>
</body>
</html>
