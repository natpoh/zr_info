<?php
error_reporting(E_ERROR );
include($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');

use MailPoet\Config\Env;
use MailPoet\Form\Widget;
use \MailPoet\Form\AssetsController;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;


$form_widget = new Widget();


echo $form_widget->widget([
    'form' => (int)$_GET['id'],
    'form_type' => 'shortcode',
]);


$wp = new WPFunctions;
$renderer = new \MailPoet\Config\Renderer(!WP_DEBUG, !WP_DEBUG);
$assets_controller =  new AssetsController($wp, $renderer, SettingsController::getInstance());

//var_dump($assets_controller);
$assets_controller->setupFrontEndDependencies();
$scripts = $assets_controller->printScripts();



$css = Env::$assets_url . '/dist/css/' . $renderer->getCssAsset('public.css');



echo $scripts;

echo "<link rel='stylesheet'  href='".$css."' type='text/css' media='all' />";


///wp-content/themes/custom_twentysixteen/template/mailpoet_form.php?id=1