<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$rcount = array('e' => 'exist', 'p' => 'percent');
$rtab = array('a' => 'all', 'd' => 'directors', 'w' => 'writers', 'c' => 'castdirectors', 'p' => 'producers');
$rgender = array('a' => 'all', 'm' => 'male', 'f' => 'female');
$race = array(
    'a' => 'All',
    'w' => 'White',
    'ea' => 'Asian',
    'h' => 'Latino',
    'b' => 'Black',
    'i' => 'Indian',
    'm' => 'Arab',
    'mix' => 'Mixed / Other',
    'jw' => 'Jewish',
);
$and = '';
$sql = '';
foreach ($rcount as $ckey => $cvalue) {
    foreach ($rtab as $tckey => $tvalue) {
        foreach ($rgender as $gkey => $gvalue) {
            foreach ($race as $rkey => $rvalue) {
                if ($rkey != 'a' && $gkey != 'a') {
                    continue;
                }
                $key_str = "{$ckey}{$tckey}{$gkey}{$rkey}";
                $and .= "cmd.{$key_str} as d{$key_str},";
                $sql .= "sql_attr_uint       =  d{$key_str}\n";
            }
        }
    }
}
print $and . "\n";
print $sql;


$rtab = array('a' => 'all', 's' => 'star', 'm' => 'main');

$and = '';
$sql = '';
foreach ($rcount as $ckey => $cvalue) {
    foreach ($rtab as $tckey => $tvalue) {
        foreach ($rgender as $gkey => $gvalue) {
            foreach ($race as $rkey => $rvalue) {
                if ($rkey != 'a' && $gkey != 'a') {
                    continue;
                }
                $key_str = "{$ckey}{$tckey}{$gkey}{$rkey}";
                $and .= "cma.{$key_str},";
                $sql .= "sql_attr_uint       =  {$key_str}\n";
            }
        }
    }
}
print $and . "\n";
print $sql;
