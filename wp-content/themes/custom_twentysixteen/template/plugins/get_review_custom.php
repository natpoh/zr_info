<?php
error_reporting(E_ERROR );
include($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');

///echo 'ok';



$postid=466345;
$post = get_post($postid);


$wpcr = new WPCustomerReviews3;
$wpcr->init();
$wpcr->include_goatee();
$reviews_content = $wpcr->show_reviews_form($postid,1,1);
echo $reviews_content;


//echo $wpcr::shortcode_show($opts);

//wp_register_style('vote-buttons-in-review-form', $wpcr->getpluginurl() . 'css/vote-buttons-in-review-form.css', array(), $this->plugin_version);
//wp_register_style('wp-customer-reviews-3-frontend', $wpcr->getpluginurl() . 'css/wp-customer-reviews-generated.css', array(), $this->plugin_version);
//wp_register_script('wp-customer-reviews-3-frontend', $wpcr->getpluginurl() . 'js/wp-customer-reviews.js', array('jquery'), $this->plugin_version);


//wp_enqueue_style('wp-customer-reviews-3-frontend');
//wp_enqueue_style('vote-buttons-in-review-form');
//wp_enqueue_script('wp-customer-reviews-3-frontend');


//$reviews_content = (new WPCustomerReviews3)->shortcode_show($opts);
//$reviews_content = $wpcr->output_reviews_show($opts);





?>


<link rel='stylesheet' type="text/css" href="<?php echo $wpcr->getpluginurl() . 'css/star.css'; ?>"/>
<link rel='stylesheet' type="text/css" href="<?php echo $wpcr->getpluginurl() . 'css/vote-buttons-in-review-form.css'; ?>"/>
<link rel='stylesheet' type="text/css" href="<?php echo $wpcr->getpluginurl() . 'css/wp-customer-reviews-generated.css'; ?>"/>
<link rel="stylesheet" id="editor-buttons-css" href="http://just.test1.ru/wp-includes/css/editor.min.css?ver=4.9.2" type="text/css" media="all">

<script type='text/javascript' src='http://just.test1.ru/wp-includes/js/jquery/jquery.js?ver=1.12.4'></script>
<script type='text/javascript' src='http://just.test1.ru/wp-includes/js/jquery/jquery-migrate.min.js?ver=1.4.1'></script>
<script type="text/javascript" src="<?php echo $wpcr->getpluginurl() . 'js/wp-customer-reviews.js'; ?>"></script>

