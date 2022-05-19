<?php
/**
 * Plugin Name: Export data
 * Version: 1.0.0
 * Description: Export data.
 */


//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

class Export_data
{
    private $access_level = 4;
    private $parrent_slug = 'export_data';


    public function __construct()
    {
        global $table_prefix;
        add_action('admin_menu', array($this, 'export_data_menu_pages'));
    }

    public function export_data_menu_pages()
    {
        add_menu_page(__('Export'), __('Export'), $this->access_level, $this->parrent_slug, array($this, 'overview'));
        add_submenu_page($this->parrent_slug, __('Export overview'), __('Overview'), $this->access_level, $this->parrent_slug, array($this, 'overview'));
        add_submenu_page($this->parrent_slug, __('Options'), __('Options'), $this->access_level, $this->parrent_slug. '_export_options', array($this, 'options'));



    }
    public function get_data_count_an($db = '', $where = '', $filled = '')
    {

        $query = "SELECT COUNT(*) AS count FROM "  . $db . " " . $where . $filled;
        $result = Pdo_an::db_fetch_row($query);
        return $result->count;
    }


    public function get_filled($total_actors,$total_actors_meta,$db='')
    {

        $wblock='';

        if ($db)
        {
            $array_update =   METALOG::get_last_data($db);

            $ublock='';

            if ($array_update[1])$width_w=$array_update[1]/$total_actors*100;

            if ($array_update[0])
            {

                $width=($array_update[0]/$total_actors*100)/$width_w*100;
                $ublock='<span class="update_d" style="width:'.$width.'% "></span>';

            }

            if ($array_update[1])
            {

                $wblock='<span class="update_w" style="width:'.$width_w.'% ">'.$ublock.'</span>';

            }
        }

        $not_filled = $total_actors-$total_actors_meta;
        $percent = round($total_actors_meta/$total_actors*100,2);
        $percent_block = '<div class="percent_container"><div class="percent_scroll" style="width: '.$percent.'%"><div class="percent_data">'.$percent.' %</div>'.$wblock.'</div></div>';
        return '<td>'.$percent_block.'</td><td>'.$not_filled.'</td><td>'.$array_update[0].'</td><td>'.$array_update[1].'</td>';
    }


    public function set_option($id,$option)
    {
        if ($option && $id)
        {

            $sql = "DELETE FROM `options` WHERE `options`.`id` = ".$id;
            Pdo_an::db_query($sql);
            $sql = "INSERT INTO `options`  VALUES ('".$id."',?)";
            Pdo_an::db_results_array($sql,array($option));
        }

    }

    public function check_new_tables()
    {
        /////add new tables
        $sql = "SELECT *
FROM information_schema.tables
WHERE table_type='BASE TABLE'
AND table_schema='imdbvisualization'";
        $rows = Pdo_an::db_results_array($sql);

        foreach ($rows as $r) {
            $link = $r["TABLE_NAME"];

            $sql = "SELECT id FROM `commit_tables_rules` WHERE `table_name` ='{$link}'";
            $r = Pdo_an::db_fetch_row($sql);
            if (!$r)
            {
                $sql="INSERT INTO `commit_tables_rules`(`id`, `table_name`, `export`, `import`, `del`, `last_update`) 
                    VALUES (NULL,'{$link}',0,0,0,".time().")";
                Pdo_an::db_query($sql);
            }


        }

    }

    public function options()
    {


        if (isset($_POST['submit_option']))
        {
           /// var_dump($_POST);
            unset($_POST['submit_option']);

            $data  =json_encode($_POST);

            $data =file_put_contents(ABSPATH.'wp-content/uploads/export',$data);

           // $this->set_option(18,$data);

        }

        $data =file_get_contents(ABSPATH.'wp-content/uploads/export');
        if ($data)
        {
            $options_data = json_decode($data,1);
        }



//        $sql = "SELECT `val` FROM `options` where id =18 ";
//        $rows = Pdo_an::db_fetch_row($sql);
//
//        $options_data  =$rows->val;
//        if ($options_data)
//        {
//            $options_data = json_decode($options_data,1);
//        }


        $request  = array('get'=>'Get Request','set'=>'Send Request','update'=>'Update data','add'=>'Add data','delete'=>'Delete data','site_id'=>'Site ID');
        $array_o = array(0=>'not set',1=>'enable',2=>'disable');


        $content='';

        foreach ($request as $i=>$n)
        {
            $option='';



                foreach ($array_o as $a=>$b)
                {
                    $selected='';
                    if (isset($options_data[$i.'_request']))
                    {


                    if ($options_data[$i.'_request']==$a)
                    {
                        $selected ='selected';
                    }
                    }

                    if ($i=='site_id')
                    {
                        $b=$a;
                    }
                    $option.= '<option '.$selected.' value="'.$a.'">'.$b.'</option>';
                }



            $content.='<tr><td>'.$n.'</td><td><select autocomplete="off" name="'.$i.'_request">'.$option.'</select></td></tr>';
        }
        $content.='<tr><td>Link to request</td><td><input autocomplete="off" name="link_request" value="'.$options_data['link_request'].'" /></td></tr>
                    <tr><td>Remote ip</td><td><input autocomplete="off" name="remote_ip" value="'.$options_data['remote_ip'].'" /></td></tr>';


        ?>
        <div class="content">
        <h1>Option</h1>

        <form action="admin.php?page=export_data_export_options" method="post">
            <div class="options_data">
                <table>
                <?php echo $content ?>
                </table>
                <div class="options_data"><button type="submit" name="submit_option" class="button button-primary save_option">Save</button></div>

            </div>
        </form>


        <?php

        $this->check_new_tables();



        !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

        if (Crowdsource::checkpost())
        {
            return;
        }





/// `commit_tables_rules`(`id`, `table_name`, `export`, `import`, `del`, `last_update`)
        $array_rows = array(
            'id'=>array('w'=>5),
            'table_name'=>array('w'=>30),
            'export'=>array('type'=>'select','options'=>'0:Not set;1:Enable;2:Only remote id;3:Disable'),
            'import'=>array('type'=>'select','options'=>'0:Not set;1:Enable;2:Only update;3:Disable'),
            'del'=>array('type'=>'select','options'=>'0:Not set;1:Enable;2:Disable'),
            'last_update'=>array('w'=>10)

        );


        Crowdsource::Show_admin_table('commit_tables_rules',$array_rows,1,'commit_tables_rules','',1,1);

    }


    public function overview()
    {

        !class_exists('METALOG') ? include ABSPATH . "analysis/include/meta_log.php" : '';

        echo '<h1>Export data</h1>';


        $groop = METALOG::get_groop();

 echo '<table class="wp-list-table widefat fixed striped posts"><thead><tr><th>Type</th><th>Count</th><th style="width: 30%">Percent</th><th>Not sync</th><th>Add 24 hours</th><th>Add This Week</th><th>Action</th></tr></thead>
<tbody>';
       foreach ($groop as $i)
       {
           $option.= ';'.$i.':'.$i;
           $total_add[$i]['add'] = self::get_data_count_an('commit',"WHERE `description`= '".$i."' ");
           $total_add[$i]['update'] = self::get_data_count_an('commit',"WHERE `description`= '".$i."' and `complete`= 1 ");
           $total_add[$i]['filled'] = self::get_filled($total_add[$i]['add'],$total_add[$i]['update'],$i);
           echo '<tr><td>'.$i.'</td><td>' . $total_add[$i]['add'] . '</td>'.$total_add[$i]['filled'].'<td></td></tr>';
       }
        $option = substr($option,1);





 echo '</tbody></table>';


 ///get status
       $array_status = array(0=>'0 Waiting',1=>'1 Sync',6=>'1 Remote sync', 2=>'2 Send request to get data',3=>'3 Send data',4=>'4 Get and save data',5=>'5 Complete',10=>'Error');

        echo '<br><br><table class="wp-list-table widefat fixed striped posts"><thead><tr><th>Status</th><th>Total</th><th>Add 10 minutes</th><th>Add 1 hour</th><th>Add 24 hours</th></tr></thead>
<tbody>';
       foreach ($array_status as $i=>$status)
       {
           $tabledata = self::get_data_count_an('commit',"WHERE `status`= '".$i."' ");
           $tabledata_10 = self::get_data_count_an('commit',"WHERE `status`= '".$i."' and last_update > '".(time()-300)."' ");
           $tabledata_h = self::get_data_count_an('commit',"WHERE `status`= '".$i."' and last_update > '".(time()-3600)."' ");
           $tabledata_24 = self::get_data_count_an('commit',"WHERE `status`= '".$i."' and last_update > '".(time()-86400)."' ");

           echo '<tr><td>' .$status . '</td><td>'.$tabledata.'</td><td>'.$tabledata_10.'</td><td>'.$tabledata_h.'</td><td>'.$tabledata_24.'</td></tr>';

       }
        echo '</tbody></table><br><br>';



        $array_rows = array(
            'id'=>array('w'=>10),
            'uniq_id' =>array('w'=>10, 'type' => 'textarea'),
            'description' => array('type'=>'select','options'=>$option),
            'text' => array('w'=>40, 'type' => 'textarea'),
            'update_data' => array('w'=>40, 'type' => 'textarea'),
            'status' => array('type'=>'select','options'=>'0:0 Waiting;1:1 Sync;6:1 Remote sync;2:2 Send request to get data;3:3 Send data;4:4 Get and save data;5:Complete;10:Error'),
            'complete' => array('type'=>'select','options'=>':no;1:Complete'),
            'site_id' => array('w'=>10,'type'=>'select','options'=>'1:1 hezner;2:2 rwt'),
            'last_update' => array('w'=>10, 'type' => 'textarea')
        );

        !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';
        $this->graph();
        Crowdsource::Show_admin_table('commit',$array_rows,1,'commit','',1,1);




echo '<style type="text/css">
.percent_container {
    width: 100%;
    background-color: #ddd;
    border: 1px solid #8e8e8e;
    height: 20px;
    overflow: hidden;
}
.percent_scroll {
    background-color: #4de95e;
    text-align: center;
    color: #000;
}
.percent_data{
display: inline-block;
}
.update_w
{
    width: 0.17235361791788%;
    display: inline-block;
    float: right;
    background-color: #ff8400;
    border: 1px solid #ff8400;
    height: 20px;
    position: relative;
    bottom: 0;
    box-sizing: border-box;
}
.update_d
{
    width: 0.17235361791788%;
    display: inline-block;
    float: right;
    background-color: #ff0000;
    border: 1px solid #ff0000;
    height: 20px;
    position: relative;
    bottom: 0;
    box-sizing: border-box;
}


</style>
';

    }

public function graph()
{

    ?>
    <p>Period <select autocomplete="off" class="graph_period">
    <option value="1">1 hour</option>
    <option value="6">6 hour</option>
    <option selected="selected" value="24">24 hour</option>
    <option  value="168">7 days</option>
    <option  value="720">30 days</option>
    </select> Datatype <select autocomplete="off" class="graph_type">
    <option value="count">count</option>
    <option value="time">time</option>
    </select></p>
    <div id="container_commit_graph" class="commit_graph"></div>
    <script type="text/javascript" src="https://code.highcharts.com/highcharts.js"></script>
    <script type="text/javascript">

function create_Highcharts(data, block)
{

  ///   console.log(data);

    if (typeof Highcharts !== 'undefined') {

        if (data)
        {
            data = JSON.parse(data);
            var data_series = data['series'];

          //  console.log(data_series);

           // var data_series_cast = data['cast'];
        }
        if (data)
        {
            Highcharts.chart('container_' + block, {
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'spline'
                },
                title: {
                    text:  data['title']
                },

                plotOptions: {
                    series: {
                        grouping: false,
                        borderWidth: 0
                    }
                },
                legend: {
                    enabled: true
                },

                xAxis: {
                     type: 'datetime',
                    },
                yAxis: [{
                        title: {
                            text: 'Total'
                        },
                        showFirstLabel: false
                    }],
                series:  data_series
            });
        }
    }
}

  function check_request()
{

   var data = jQuery("#jqGrid").jqGrid("getGridParam", "postData");
    data.period = jQuery('.graph_period').val();
    data.type = jQuery('.graph_type').val();


    ///get graph data
                jQuery.ajax({
                type: "POST",
                url: "<?php echo site_url() ?>/service/import.php",

                data: data,
                success: function (html) {
                   // console.log(html);
                    ///jQuery('.commit_graph').html(html);
                    create_Highcharts(html, 'commit_graph')
                }
            });



}

  jQuery(document).ready(function () {




jQuery('body').on('change','.graph_period, .graph_type',function ()
{
check_request();

});


  });

</script>


    <?php

}




}

new Export_data;