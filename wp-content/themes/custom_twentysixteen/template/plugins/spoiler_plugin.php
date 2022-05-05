<?php

function get_spoiler_data($content)
{
    $rand = mt_rand();
    return '<spoiler class="spoiler_default" id="spoiler-' . $rand . '" style="display: block;">' . $content . '</spoiler>';
}

function check_spoiler(  $content = null ) {


$pos = strpos($content,'[spoiler]');

if ($pos!=-1)
{
    $spoilerdata_before = substr($content,$pos+9);
    $content_before =substr($content,0,$pos);

    $pos2 = strpos($spoilerdata_before,'[/spoiler]');
    if ($pos2)
    {
        $spoilerdata = substr($spoilerdata_before,0,$pos2);
        $content_after =substr($spoilerdata_before,$pos2+10);
        $spoiler = get_spoiler_data($spoilerdata);

        $content=$content_before.$spoiler.$content_after;


        $content =   check_spoiler(  $content  );
    }


}

    return $content;


}