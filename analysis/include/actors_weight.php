<?php

class ActorWeight
{
private static $cm;

public static function check_cr()
{
    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    }
    global $cr;
    global $cm;

    if (!class_exists('CriticTransit')) {
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractFunctions.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBAn.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDB.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMatic.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticSearch.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'SearchFacets.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTransit.php' );

        self::$cm = new CriticMatic();
        $cr = new CriticTransit(self::$cm);
    }
    return $cr;
}


public static function check_actor_weight()
{
//    $cr  =self::check_cr();
//    $af = $cr->cm->get_af();
//    $priority = $af->race_weight_priority;
//    if ($ss['an_weightid'] > 0) {
//        $ma = $cr->cm->get_ma();
//        $rule = $ma->get_race_rule_by_id($ss['an_weightid']);
//        if ($rule) {
//            $priority = json_decode($rule->rule, true);
//        }
//    }
//
//    $af->show_table_weight_priority($priority);
//

}
private static function get_ctable($result)
{
    $af = self::$cm->get_af();


    $arr_summ = [];
    $arr_top=[];




    $ptype = $af->race_type_calc[$result['type_calc']];

    $ctable =  '<p>Calculation type: <b>' . $ptype['title'] . '</b></p>';
    $filter_titles = array();
    $filter_races = array();
    foreach ($af->race_data_setup as $k => $v) {
        $filter_titles[$k] = $v['title'];
    }
    foreach ($af->race_small as $k => $v) {
        $filter_races[$v['key']] = $v['title'];
    }
    $cbody = '';
    $chead = '<th colspan="2">DataSet / Verdict</th>';
    $head_ex = false;

    foreach ($result['race_weight'] as $i => $v) {
        if ($i == 't') {
            continue;
        }
        $cbody .= '<tr id="' . $i . '">';

        $cbody .= '<td colspan="2">' . $filter_titles[$i]. '</td>';
        foreach ($v as $j => $val) {
            if (!$head_ex) {
                $chead .= '<th>' . $filter_races[$j] . '</th>';
            }

            if (!$arr_summ[$j])
            {
                $arr_summ[$j]=0;
            }

            if (!$arr_top[$j]) {
                $arr_top[$j] = 0;
            }

            if ($result['result'][$i]["key"]==$j && $result['result'][$i]["score"]>0)
            {
                $arr_summ[$j]+=$val;

                if ($val>$arr_top[$j])
                {
                    $arr_top[$j]=$val;
                }

                $cbody .= '<td class="col"><span class="big_data">' . $val . '</span></td>';
            }
            else
            {


                $cbody .= '<td class="col"><span class="small_gray">' . $val . '</span></td>';
            }

        }
        $head_ex = true;
        $cbody .= '</tr>';




    }


    $cbody .= '<tr><td colspan="2">Total:</td>';

    if ($result['type_calc']==0) //summ
    {
        foreach ($arr_summ as $j=>$val)
        {
            if ($val>0)
            {
                $cbody .= '<td class="col"><span class="big_data">' . $val . '</span></td>';
            }
            else
            {
                $cbody .= '<td class="col"><span class="small_gray">0</span></td>';
            }

        }
    }
    else if ($result['type_calc']==1) //top
    {

        foreach ($arr_top as $j=>$val)
        {
            if ($val>0)
            {
                $cbody .= '<td class="col"><span class="big_data">' . $val . '</span></td>';
            }
            else
            {
                $cbody .= '<td class="col"><span class="small_gray">0</span></td>';
            }

        }

    }
    $cbody .= '</tr>';


    $ctable.= '<table class="wp-list-table widefat striped table-view-list"><thead><tr>' . $chead . '</tr></thead><tbody>' . $cbody . '</tbody></table>';


    $verdict_num = $result['race_code_ret'];
    if ($verdict_num)
    {
        $verdict = $af->race_small[$verdict_num]["title"];
    }
    else
    {
        $verdict='N/A';
    }


    $ctable.= '<p>Verdict: '.$verdict.'</p>';

    return $ctable;
}

public static function update_actor_weight($actor_id,$debug=0,$sinch = 1,$count = 100, $force=false,$onlydata=0)
{
    $cr  =self::check_cr();
    $result = $cr->get_actors_meta($count, $debug , $force,$actor_id,$sinch,$onlydata);

if (!$onlydata)
{
    return;
}
   $table =  self::get_ctable($result);
return $table;



}






}