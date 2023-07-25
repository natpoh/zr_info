<?php
/**
 * Plugin Name: Light movie library
 * Version: 1.0.0
 * Description: Light movie library.
 */

class LightMovies{
    private $access_level = 4;
    private $parrent_slug = 'light_movies';


    public function __construct()
    {
        global $table_prefix;
        add_action('admin_menu', array($this, 'light_movies_menu_pages'));
    }

    public function light_movies_menu_pages()
    {
        add_menu_page(__('Movies'), __('Movies'), $this->access_level, $this->parrent_slug, array($this, 'overview'));
        add_submenu_page($this->parrent_slug, __('Movies overview'), __('Overview'), $this->access_level, $this->parrent_slug, array($this, 'overview'));
        add_submenu_page($this->parrent_slug, __('Add Movies'), __('Add Movies'), $this->access_level, $this->parrent_slug . '_add_movies', array($this, 'add_movies'));
        add_submenu_page($this->parrent_slug, __('Actors info'), __('Actors info'), $this->access_level, $this->parrent_slug. '_actors_info', array($this, 'actors_info'));
        add_submenu_page($this->parrent_slug, __('Piracy links'), __('Custom links'), $this->access_level, $this->parrent_slug. '_piracy_links', array($this, 'piracy_links'));
        add_submenu_page($this->parrent_slug, __('Compilation Links'), __('Compilation Links'), $this->access_level, $this->parrent_slug. '_compilation_links', array($this, 'compilation_links'));
        add_submenu_page($this->parrent_slug, __('Custom fields'), __('Custom fields'), $this->access_level, $this->parrent_slug. '_custom_fields', array($this, 'custom_fields'));
        add_submenu_page($this->parrent_slug, __('Google search'), __('Google search'), $this->access_level, $this->parrent_slug. '_google_search', array($this, 'google_search'));
        add_submenu_page($this->parrent_slug, __('Ethnic data'), __('Ethnic data'), $this->access_level, $this->parrent_slug. '_custom_options', array($this, 'option'));


        add_submenu_page($this->parrent_slug, __('Logs'), __('Logs'), $this->access_level, $this->parrent_slug. '_movie_logs', array($this, 'movie_logs'));
         add_submenu_page($this->parrent_slug, __('Cron info'), __('Cron info'), $this->access_level, $this->parrent_slug. '_cron_info', array($this, 'cron_info'));

    }


public function get_option($id='',$type='')
{
            !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
            $data =  OptionData::get_options($id,$type);

                $data =  str_replace('\\','',$data);

            return $data;
}

public function set_option($id,$option,$type='')
{
            !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
            OptionData::set_option($id,$option,$type,1);

}

    public static function get_groop_logs()
    {
        $array_result=[];
        $sql="SELECT `name` FROM `movies_log` GROUP BY `name` ";
        $rows =Pdo_an::db_results_array($sql);
        foreach ($rows as $i =>$v)
        {
            $array_result[] =  $v["name"];
        }
        return $array_result;
    }

public function movie_logs()
{
///id 	movie_id 	imdb_id 	name 	comment 	status 	last_update

                    $groop = self::get_groop_logs();


       foreach ($groop as $i)
       {
         $option.= ';'.$i.':'.$i;
       }
        $option = substr($option,1);

            $array_rows = array(
            'id'=>array('w'=>10),
            'movie_id' =>array('w'=>10, 'type' => 'textarea'),
            'rwt_id' =>array('w'=>10, 'type' => 'textarea'),
            'name' =>array('type'=>'select','options'=>$option),
            'comment' =>array('w'=>30, 'type' => 'textarea'),
            'status' => array('type'=>'select','options'=>'0:Running;1:Complete;2:Error'),
            'last_update' => array('w'=>10, 'type' => 'textarea')
        );

        !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

        Crowdsource::Show_admin_table('movies_log',$array_rows,1,'movies_log',0,1,1,0,0);


?>
<style type="text/css">
tr.jqgrow>td
 {

  white-space: break-spaces!important;
}
</style>

<?php



}
   public function google_search()
    {


 echo '<h1>Google search</h1>';

        !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

    $typedata_string = $this->get_zr_type();
        $array_rows = array(
            'id'=>array('w'=>5),
             'content_type'=>array('type'=>'select','options'=>'0:google cse;1:iframe;2:news'),
             'type'=>array('type'=>'select','options'=>$typedata_string),
            'main_block'=>array('type'=>'select','options'=>'0:Internet Zeitgest;1:Critic Reviews;2:Global Consensus;3:Characters;4:Global Games;5:Similar show'),
            'dop_content' =>array('w'=>50, 'type' => 'textarea','textarea_rows' => 20),
        );

        Crowdsource::Show_admin_table('search_tabs',$array_rows,1,'options_search_tabs','',1,1,1,0);

?>
<style type="text/css">
.ui-jqgrid .ui-jqgrid-btable tbody tr.jqgrow td {
    overflow: hidden;
    white-space: nowrap;
    padding-right: 2px;
    max-height: 40px;
}
.EditTable td textarea {
    width: 100%;
}
</style>
<?php


    }
    public function custom_fields()
    {


 echo '<h1>Custom Option</h1>';

                 !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';


        $array_rows = array(
            'id'=>array('w'=>5),
            'val' =>array('w'=>50, 'type' => 'textarea','textarea_rows' => 20),
        );

        Crowdsource::Show_admin_table('options',$array_rows,1,'options','',1,1,1,0);

?>
<style type="text/css">
.ui-jqgrid .ui-jqgrid-btable tbody tr.jqgrow td {
    overflow: hidden;
    white-space: nowrap;
    padding-right: 2px;
    max-height: 40px;
}
.EditTable td textarea {
    width: 100%;
}
</style>
<?php


    }
    public function option()
    {





    if (isset($_POST['option_3']))
        {


$this->set_option(3,$_POST['option_3'],'Ethnic array');
$this->set_option(4,$_POST['option_4'],'Ethnic array fast');
$this->set_option(6,$_POST['option_6'],'Color array');


        }


    ?>
    <div class="content">
<h1>Ethnic data</h1>

<form action="admin.php?page=light_movies_custom_options" method="post">
    <div class="options_data">
<!--         <h2>Verdicts method</h2>-->
<!--        <select name="verdict_method">--><?php //echo $option; ?><!--</select>-->

        <h2>Ethnic array</h2>
        <textarea name="option_3" style="width: 600px; height: 500px"><?php echo $this->get_option(3); ?></textarea>
                <h2>Ethnic array fast</h2>
        <textarea name="option_4" style="width: 600px; height: 500px"><?php echo $this->get_option(4); ?></textarea>
        <h2>Color array</h2>
        <textarea name="option_6" style="width: 600px; height: 300px"><?php echo $this->get_option(6); ?></textarea>

    <div class="options_data"><button type="submit" class="button button-primary save_option">Save</button></div>
    </div>
</form>


<?php


    }
        public function get_data_count_an($db = '', $where = '', $filled = '')
    {

        $query = "SELECT COUNT(*) AS count FROM "  . $db . " " . $where . $filled;
        $result = Pdo_an::db_fetch_row($query);
        return $result->count;
    }


    public function get_filled($total_actors,$total_actors_meta,$db='',$custom='',$index='')
    {

        $wblock='';
        if ($db)
            {
             $array_update =   ACTIONLOG::get_last_data($db,$custom,$index);

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


public function graph()
{

    ?>
    <p>Period <select autocomplete="off" class="graph_period">
    <option value="1">1 hour</option>
    <option value="6">6 hour</option>
    <option selected="selected" value="24">24 hour</option>
    <option  value="168">7 days</option>
    <option  value="680">30 days</option>
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

    data.oper='get_log_data';

    ///get graph data
                jQuery.ajax({
                type: "POST",
                url: "<?php echo site_url() ?>/analysis/include/action_log.php",

                data: data,
                success: function (html) {
                   // console.log(html);
                    ///jQuery('.commit_graph').html(html);
                    create_Highcharts(html, 'commit_graph')
                }
            });



}

  jQuery(document).ready(function () {




jQuery('body').on('change','.graph_period, .graph_type, .graph_time',function ()
{
check_request();

});


  });

</script>


    <?php

}
    private function get_zr_type()
    {
                   $arrtypes =$this->get_option('','zr_content_type');
                 $typedata = [];

                 if ($arrtypes)
                     {
                         $arrtypes_ob =json_decode($arrtypes,1);
                         foreach ($arrtypes_ob as $t=>$v)
                             {
                               $typedata[]=$t.':'.  $v['title'];

                             }

                        $typedata_string = implode(';',$typedata) ;
                     }

                 return $typedata_string;
    }

    public function compilation_links()
    {

                echo '<h1>Compilation links</h1>';

                 !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

                $typedata_string = $this->get_zr_type();


        $array_rows = array(
            'id'=>array('w'=>5),

            'type'=>array('type'=>'select','options'=>$typedata_string),

            'last_update' =>array('w'=>10, 'type' => 'textarea','editfalse'=>1),
            'enable'=>array('type'=>'select','options'=>'1:Enable;0:Disable'),
        );

        Crowdsource::Show_admin_table('meta_compilation_links',$array_rows,1,'meta_compilation_links','',1,1,1,0);




    }
    public function piracy_links()
    {
        echo '<h1>Custom links</h1>';



        !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';
            $typedata_string = $this->get_zr_type();

        $array_rows = array(
            'id'=>array('w'=>5),

            'type'=>array('type'=>'select','options'=>$typedata_string),




            'category'=>array('type'=>'select','options'=>'0:\"FREE\";1:FREE;2:IRL;3:RENT;4:BUY'),
            'game_type'=>array('type'=>'select','options'=>'0:All;1:Nintendo Entertainment System (NES);2:Super Nintendo Entertainment System (SNES);3:Nintendo 64 (N64);4:GameCube;5:Wii;6:Wii U;7:Nintendo Switch;8:Game Boy;9:Game Boy Advance;10:Nintendo DS;11:Nintendo 3DS;10:PlayStation;11:PlayStation 2 (PS2);12:PlayStation 3 (PS3);13:PlayStation 4 (PS4);15:PlayStation 5 (PS5);16:Xbox;17:Xbox 360;18:Xbox One;19:Xbox Series X/S;20:PC (Windows, macOS, Linux);21:Mobile (iOS, Android);22:Arcade games'),
            'include_year'=>array('type'=>'select','options'=>'0:No;1:Yes'),
            'last_update' =>array('w'=>10, 'type' => 'textarea','editfalse'=>1),
            'enable'=>array('type'=>'select','options'=>'1:Enable;0:Disable'),
        );

        Crowdsource::Show_admin_table('meta_piracy_links',$array_rows,1,'meta_piracy_links','',1,1,1,0);


    }

    public function actors_info()
    {

       !class_exists('ACTIONLOG') ? include ABSPATH . "analysis/include/action_log.php" : '';

       echo '<h1>Actors info</h1>';

       $total_empty_actors = self::get_data_count_an('data_actors_imdb',"WHERE lastupdate = '0'");
       $total_actors = self::get_data_count_an('data_actors_imdb');
       $total_actors_filled = self::get_filled($total_actors,$total_actors-$total_empty_actors,'new_actors');


       $total_actors_null_names = self::get_data_count_an('data_actors_imdb',"WHERE `name`!= '' ");
       $total_actors_null_names_filled = self::get_filled($total_actors,$total_actors_null_names,'name');

       $total_data_actors_ethnicolr = self::get_data_count_an('data_actors_ethnicolr');
       $total_data_actors_ethnicolr_filled = self::get_filled($total_actors,$total_data_actors_ethnicolr,'data_actors_ethnicolr',1,'date_upd');

       $total_actors_meta_surname = self::get_data_count_an('data_actors_meta',"WHERE `n_surname` IS NOT NULL");
       $total_actors_meta_surname_filled = self::get_filled($total_actors,$total_actors_meta_surname,'');




       $total_actors_image = self::get_data_count_an('data_actors_imdb',"WHERE `image`= 'Y' ");
       $total_actors_image_filled = self::get_filled($total_actors,$total_actors_image,'image');



       $total_actors_tmdb = self::get_data_count_an('data_actors_tmdb');
       $total_actors_tmdb_f = self::get_data_count_an('data_actors_tmdb',"WHERE `actor_id` IS NOT NULL ");
       $total_actors_tmdb_filled = self::get_filled($total_actors_tmdb,$total_actors_tmdb_f,'tmdb_add_imdbid');





       $total_actors_tmdb_id = self::get_data_count_an('data_actors_meta',"WHERE `tmdb_id`> 0 ");
       $total_actors_tmdb_id_filled = self::get_filled($total_actors,$total_actors_tmdb_id,'tmdb_id');

       $total_actors_tmdb_image = self::get_data_count_an('data_actors_meta',"WHERE `tmdb_id`> 0 and tmdb_img =1");
       $total_actors_tmdb_image_filled = self::get_filled($total_actors,$total_actors_tmdb_image,'tmdb_image');


       $total_actors_meta_kairos_imdb= self::get_data_count_an('data_actors_race',"WHERE `kairos_verdict` IS NOT NULL");
       $total_actors_meta_kairos_imdb_filled = self::get_filled($total_actors_image,$total_actors_meta_kairos_imdb,'kairos_add');


       $total_actors_meta_kairos = self::get_data_count_an('data_actors_meta',"WHERE `n_kairos` IS NOT NULL");
       $total_actors_meta_kairos_filled = self::get_filled($total_actors,$total_actors_meta_kairos,'kairos');

       $total_actors_meta_bettaface = self::get_data_count_an('data_actors_meta',"WHERE `n_bettaface` IS NOT NULL and `bettaface`!=2");
       $total_actors_meta_bettaface_filled = self::get_filled($total_actors,$total_actors_meta_bettaface,'bettaface');


       $total_actors_meta = self::get_data_count_an('data_actors_meta');
       $total_actors_meta_filled = self::get_filled($total_actors,$total_actors_meta,'data_actors_meta');

       $total_actors_meta_gender = self::get_data_count_an('data_actors_meta',"WHERE `gender` IS NOT NULL");
       $total_actors_meta_gender_filled = self::get_filled($total_actors,$total_actors_meta_gender,'gender');

       $total_actors_meta_verdict = self::get_data_count_an('data_actors_meta',"WHERE `n_verdict` IS NOT NULL");
       $total_actors_meta_verdict_filled = self::get_filled($total_actors,$total_actors_meta_verdict,'verdict');



        echo '
<table class="wp-list-table widefat fixed striped posts"><thead><tr><th>Type</th><th>Count</th><th style="width: 30%">Percent</th><th>Not filled</th><th>Add 24 hours</th><th>Add This Week</th><th>Action</th></tr></thead>
<tbody>
<tr><td>Actors</td><td>' . $total_actors . '</td>'.$total_actors_filled.'<td></td></tr>
<tr><td>Empty Actors</td><td>' . $total_empty_actors . '</td>'.$total_actors_filled.'<td></td></tr>





<tr><td>Actor names</td><td>' . $total_actors_null_names . '</td>'.$total_actors_null_names_filled.'<td></td></tr>


<tr><td>Actor ethnicolr</td><td>' . $total_data_actors_ethnicolr . '</td>'.$total_data_actors_ethnicolr_filled.'<td></td></tr>
<tr><td>Actor surname</td><td>' . $total_actors_meta_surname . '</td>'.$total_actors_meta_surname_filled.'<td></td></tr>


<tr><td>Actor images</td><td>' . $total_actors_image . '</td>'.$total_actors_image_filled.'<td></td></tr>



<tr><td>Actor kairos imdb</td><td>' . $total_actors_meta_kairos_imdb . '</td>'.$total_actors_meta_kairos_imdb_filled.'<td></td></tr>
<tr><td>Actor kairos meta</td><td>' . $total_actors_meta_kairos . '</td>'.$total_actors_meta_kairos_filled.'<td></td></tr>
<tr><td>Actor bettaface</td><td>' . $total_actors_meta_bettaface . '</td>'.$total_actors_meta_bettaface_filled.'<td></td></tr>




<tr><td>Actor TMDB DB</td><td>' . $total_actors_tmdb . '</td>'.$total_actors_tmdb_filled.'<td></td></tr>

<tr><td>Actor TMDB_ID</td><td>' . $total_actors_tmdb_id . '</td>'.$total_actors_tmdb_id_filled.'<td></td></tr>




<tr><td>Actor TMDB image</td><td>' . $total_actors_tmdb_image . '</td>'.$total_actors_tmdb_image_filled.'<td></td></tr>

<tr><td>Actor_meta</td><td>' . $total_actors_meta . '</td>'.$total_actors_meta_filled.'<td></td></tr>
<tr><td>Actor gender</td><td>' . $total_actors_meta_gender . '</td>'.$total_actors_meta_gender_filled.'<td></td></tr>
<tr><td>Actor verdict</td><td>' . $total_actors_meta_verdict . '</td>'.$total_actors_meta_verdict_filled.'<td></td></tr>


</tbody></table>';

$groop = ACTIONLOG::$array;
       foreach ($groop as $i=>$v)
       {
           $option.= ';'.$v.':'.$i;

       }
        $option = substr($option,1);

        $array_rows = array(
            'id'=>array('w'=>10),
            'type' => array('type'=>'select','options'=>$option),
            'result' => array('type'=>'select','options'=>'0:error;1:Complete'),
            'table' => array('w'=>20, 'type' => 'textarea'),
            'actor_id' => array('w'=>15, 'type' => 'textarea'),
            'last_update' => array('w'=>10, 'type' => 'textarea')
        );

        !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

  $this->graph();

        Crowdsource::Show_admin_table('actors_log',$array_rows,1,'meta_actors_log','',1,0);



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

    public function get_user_data()
    {

        $sql = "SELECT * FROM `options` where id=16 ";
        $rows = Pdo_an::db_fetch_row($sql);
        $data  = $rows->val;
        if ($data)
        {
             $movie_list = json_decode($data,1);
         if ($movie_list)
             {
        !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';


        echo '<h2>Add Movies, TV, Games for user list</h2>';

        arsort($movie_list);
        $update_array='';
        $result_data='';
        foreach ($movie_list as $movie_id => $count)
            {

             if  ( self::check_in_db($movie_id))
                 {
                   $update_array=1;

                   if(isset($movie_list[$movie_id]))
                        {
	                    unset($movie_list[$movie_id]);
                        }

                 }
             else
             {
              $array_movie =  TMDB::get_content_imdb($movie_id);
             /// var_dump($array_movie);

                 $title = $array_movie['title'];
                 $image = $array_movie['image'];
                 $desc  = $array_movie["description"];
               $final_value = sprintf('%07d', $movie_id);
                $url = "https://www.imdb.com/title/tt" . $final_value . '/';

                $button = '<button  id="'.$movie_id.'" class="button button-primary add_movie_todb">Add to database</button>';
                $button_delete = '<button  id="'.$movie_id.'" class="button button-primary delete_movie_from_list">Remove from list</button>';
                $result_data.= '<tr class="click_open" id="'.$movie_id.'"><td>'.$count.'</td><td>'.$movie_id.'</td><td><img style="width: 100px" src="'.$image.'" /></td><td><a target="_blank" href="'.$url.'">'.$title.'</a></td><td>'.$desc.'</td><td>'.$button.'</td><td>'.$button_delete.'</td><td><a id="op" class="open_ethnic open_ul" href="#"></a></td></tr>';

             }

            }
        if ($update_array)
            {
                $movie_list_str = json_encode($movie_list);

                $this->set_option(16,$movie_list_str,'movie_list');

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                 Import::create_commit('', 'update', 'options', array('id' => 16), 'options',7);

            }

        if ($result_data)
            {
            echo '<table class="wp-list-table widefat fixed striped"><tr><th>Count user requests</th><th>IMDB ID</th><th>Image</th><th>Title</th><th>Description</th><th colspan="2">Action</th><th>Show in database</th></tr>'.$result_data.'</table>';
            }

        }
        }

        ?>

        <style type="text/css">

.note_show_content, #container_main_movie_graph{
display: none;

}

</style>

        <?php


    }



     public function cron_info()
    {
        echo '<h1>Cron info</h1>';


        include (ABSPATH.'service/cron.php');
       global $array_jobs;

        $cron = new Cronjob;
        $cron->run($array_jobs,1);



    }

    public function add_movies()
    {
        echo '<h1>Add Movies, TV, Games</h1>';

        $option_type='<option value="ft">Movie</option><option value="tv">TV</option><option value="vg">Game</option><option value="all">All</option>';
        if (isset($_POST['type']))
        {
            $type = $_POST['type'];
            $option_type = str_replace('value="'.$type.'"', 'value="'.$type.'" selected ',$option_type);
        }



        ?>
<p></p>
        <form action="admin.php?page=light_movies_add_movies" method="post">
        <input type="text" name="keyword" placeholder="Movie title"  value="<?php if (isset($_POST['keyword']))echo $_POST['keyword']; ?>"/><select name="type"><?php echo $option_type; ?></select><button type="submit" class="button button-primary">Search</button>
        </form>
<?php
        if (isset($_POST['keyword']))
        {
            $key = $_POST['keyword'];
            $type = $_POST['type'];
            $data = $this->get_data($key,$type);
            echo $data;


        }
        $this->get_user_data();



          $this->script_add_movies();

    }
    public function overview()
    {


    !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';


    if (Crowdsource::checkpost())
    {
        return;
    }

        global $WP_include;
        $WP_include=1;
        include ABSPATH.'analysis/data.php';

    }

    public function check_in_db($movie_id)
    {

        $sql = "select id from data_movie_imdb where movie_id = ".intval($movie_id)." limit 1";

        $result = Pdo_an::db_fetch_row($sql);

        if ($result)
        {
            return 1;
        }
        return 0;

    }

    public function get_data($key,$type)
    {
        !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
        $result_data_array =TMDB::get_data($key,$type) ;
        $result_data='';
       /// var_dump($result_data_array);
        if (is_array($result_data_array))
            {


              foreach ($result_data_array as $movie_id=>$data)
              {
                    $poster =  $data['titlePosterImageModel']['url'];
                    $poster = str_replace('_V1_.jpg','_V1_QL75_UY74_CR1,0,50,74_.jpg',$poster);
                  if ($movie_id)
                  {
                    $enable_on_db=  $this->check_in_db($movie_id);
                  }
                  if (!$enable_on_db)
                  {
                      $button = '<button  id="'.$movie_id.'" class="button button-primary add_movie_todb">Add to database</button>';
                  }
                  else
                  {
                      $button = 'Already in the database <button  id="'.$movie_id.'" class="button button-primary add_movie_todb">Update</button>';
                  }

                   $result_data.= '<tr class="click_open" id="'.$movie_id.'"><td>'.$movie_id.'</td><td><img src="'.$poster.'" /></td><td><a target="_blank" href="https://www.imdb.com/title/'. $data['id'].'">'.$data['titleNameText'].'</a> '.$data['titleReleaseText'].' '.$data['imageType'].'</td><td>'.$button.'</td><td><a id="op" class="open_ethnic open_ul" href="#"></a></td></tr>';

              }
              //print_r($mach);
          }
        return '<table class="wp-list-table widefat fixed striped"><tr><th>IMDB ID</th><th>Image</th><th>Title</th><th>Action</th><th>Show in database</th></tr>'.$result_data.'</table>';


    }

    public function script_add_movies()
    {
        //analysis/include/scrap_imdb.php?get_imdb_movie_id=

        ?>


        <script type="text/javascript">
            jQuery(document).ready(function () {




                    jQuery('.delete_movie_from_list').click(function () {
                    var button = jQuery(this);
                    button.attr('disabled',true);

                    var movie = button.attr('id');
                    jQuery.ajax({
                        type: 'post',
                        data:({'remove_movie':movie}),
                         url: window.location.protocol+"/wp-content/themes/custom_twentysixteen/template/ajax/search_ajax.php",
                        success: function (html) {
                            if (html==1)
                            {
                                button.after('Successfully deleted');
                            }
                            else
                            {
                                button.after(html);
                            }
                            button.remove();

                        }
                    });

                });
                jQuery('.add_movie_todb').click(function () {
                    var button = jQuery(this);
                    button.attr('disabled',true);

                    var movie = button.attr('id');
                    jQuery.ajax({
                        type: 'get',
                        url: window.location.protocol+"/analysis/include/scrap_imdb.php?get_imdb_movie_id="+movie,
                        success: function (html) {

                            if (html==1)
                            {
                                button.after('Successfully added');
                            }
                            else
                            {
                                button.after(html);
                            }
                            button.remove();

                        }
                    });

                });

       jQuery('.open_ethnic').click(function () {

        var prnt = jQuery(this).parents('tr.click_open');

        var op = jQuery(this).attr('id');

        if (op == 'cl') {
            if (prnt.next('tr.click_container').html()) {
                prnt.next('tr.click_container').remove();
            }
            jQuery(this).attr('id', 'op');


            jQuery('.click_open').removeClass('opened');
        }
 else {
            jQuery(this).attr('id', 'cl');

            var length_col = prnt.find('td').length;

///console.log(length_col);

            prnt.after('<tr class="click_container"><td colspan="' + length_col + '"><div class="cssload-circle">\n' +
                '\t\t<div class="cssload-up">\n' +
                '\t\t\t\t<div class="cssload-innera"></div>\n' +
                '\t\t</div>\n' +
                '\t\t<div class="cssload-down">\n' +
                '\t\t\t\t<div class="cssload-innerb"></div>\n' +
                '\t\t</div>\n' +
                '</div></td></tr>')
            jQuery('.click_open').removeClass('opened');

            prnt.addClass('opened');
            var cntnr_big = prnt.next('tr.click_container');
            cntnr_big.show();
            var cntnr = cntnr_big.find('td');


            var id = prnt.attr('id');

            if (id > 0) {
                jQuery.ajax({
                    type: "POST",
                    url: window.location.protocol+"/analysis/get_data.php",
                    data: ({
                        oper: 'movie_data',
                        id: id,
                    }),
                    success: function (html) {
                        cntnr.html(html);
                        cntnr_big.show();
                    }
                });
            } else {
                cntnr.html('no IMDb id');
            }
        }

    return false;
    });

            });
        </script>
        <script src="https://code.highcharts.com/stock/highstock.js"></script>
            <link rel="stylesheet" href="<?php echo site_url().'/wp-content/themes/custom_twentysixteen/css/movie_single.css?'.LASTVERSION ?>">
    <link rel="stylesheet" href="<?php echo site_url().'/wp-content/themes/custom_twentysixteen/css/colums_template.css?'.LASTVERSION ?>">
<style type="text/css">
a.open_ul {
    background: url("<?php echo site_url(); ?>/analysis/images/arrows2.png") no-repeat scroll left -40px top -40px transparent;
display: inline-block;
float: none;
height: 40px;
margin-right: 0;
margin-top: 0;
position: relative;
width: 40px;
}
a#cl.open_ul:hover {
    background: url("<?php echo site_url(); ?>/analysis/images/arrows2.png") no-repeat scroll left 0 top -40px transparent;
}
a#op.open_ul {
    background: url("<?php echo site_url(); ?>/analysis/images/arrows2.png") no-repeat scroll left -40px top 0 transparent;
}
a#op.open_ul:hover {
    background: url("<?php echo site_url(); ?>/analysis/images/arrows2.png") no-repeat scroll left 0 top 0 transparent;
}

.header_title h1, .column_header h2{
    color: #e6e6e6;
}
.movie_button_action .watch_buttom{
display: none;
}
    </style>
        <?php
    }


}

new LightMovies;