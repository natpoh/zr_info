<?php

add_shortcode('mailpoet_form_custom', 'create_mailpoet_form_custom');



function create_mailpoet_form_custom($atts)
{

 $id = intval($atts[0]);

 return   '<div id="mailpoet_form" data-value="'. $id.'" class="not_load"></div>';




}