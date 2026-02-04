<?php

require_once('../wp-config.php');

global $gmi;
if ($gmi) {
    print '<pre>';
    foreach ($gmi as $i => $val) {
        echo $val . '   ' . $i . PHP_EOL;
    }
    print '</pre>';
}
list_hooked_functions();