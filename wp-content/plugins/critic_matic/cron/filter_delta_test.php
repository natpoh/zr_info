<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
if (!class_exists('CriticMatic')) {
    return;
}
$cm = new CriticMatic();
$uf = $cm->get_uf();
print "filter delta\n ";
print $uf->filters_delta();