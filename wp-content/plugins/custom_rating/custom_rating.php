<?php
/**
 * Plugin Name: Custom rating
 * Version: 1.0.0
 * Description: Custom movie rating.
 */
register_activation_hook(__FILE__, 'custom_rating_activate');

function custom_rating_activate()
{
    global $table_prefix;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE  IF NOT EXISTS `" . $table_prefix . "post_rating` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `movie_id` int(20) DEFAULT NULL,
  `type` int(2) DEFAULT NULL,
  `vote` int(2) DEFAULT NULL,
  `rating` float DEFAULT NULL,
  `affirmative` float DEFAULT NULL,
  `god` float DEFAULT NULL,
  `hollywood` float DEFAULT NULL,
  `lgbtq` float DEFAULT NULL,
  `misandry` float DEFAULT NULL,
  `patriotism` float DEFAULT NULL,
     PRIMARY KEY (`id`),
   KEY `movie_id` (`movie_id`),
   KEY `type` (`type`),
   KEY `vote` (`vote`),
   KEY `rating` (`rating`),
   KEY `affirmative` (`affirmative`),
   KEY `god` (`god`),
   KEY `hollywood` (`hollywood`),
   KEY `lgbtq` (`lgbtq`),
   KEY `misandry` (`misandry`),
   KEY `patriotism` (`patriotism`)
)  DEFAULT COLLATE utf8_general_ci";

    dbDelta($sql);


    $sql = "CREATE TABLE  IF NOT EXISTS `" . $table_prefix . "post_gender_rating` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `movie_id` int(20) DEFAULT NULL,
  `imdb_id` int(20) DEFAULT NULL,
  `diversity` float DEFAULT NULL,
  `diversity_data` text,
  `male` float DEFAULT NULL,
  `female` float DEFAULT NULL,
     PRIMARY KEY (`id`),
   KEY `movie_id` (`movie_id`),
   KEY `imdb_id` (`imdb_id`),
   KEY `diversity` (`diversity`),
   KEY `male` (`male`),
   KEY `female` (`female`)
) ENGINE=InnoDB DEFAULT COLLATE utf8_general_ci;";

    dbDelta($sql);

    $sql = "CREATE TABLE  IF NOT EXISTS `" . $table_prefix . "post_pg_rating` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `movie_id` int(20) DEFAULT NULL,
  `imdb_id` int(20) DEFAULT NULL,
  `pgrating` float DEFAULT NULL,
  `pg_data` text CHARACTER SET utf8 COLLATE utf8_general_ci,
    PRIMARY KEY (`id`),
   KEY `movie_id` (`movie_id`),
   KEY `imdb_id` (`imdb_id`),
   KEY `pgrating` (`pgrating`)
) ENGINE=InnoDB DEFAULT COLLATE utf8_general_ci;";

    dbDelta($sql);

}


class CustomRating
{


    private $access_level = 4;
    private $parrent_slug = 'custom_rating';
    public $user_can;

    public function __construct()
    {
        global $table_prefix;
        add_action('admin_menu', array($this, 'rating_menu_pages'));

    }

    public function rating_menu_pages()
    {
        add_menu_page(__('Custom Rating'), __('Custom Rating'), $this->access_level, $this->parrent_slug, array($this, 'overview'));
        add_submenu_page($this->parrent_slug, __('Custom Rating overview'), __('Overview'), $this->access_level, $this->parrent_slug, array($this, 'overview'));
        add_submenu_page($this->parrent_slug, __('Custom Rating'), __('Movie Rating'), $this->access_level, $this->parrent_slug . '_movies_rating', array($this, 'movies_rating'));
        add_submenu_page($this->parrent_slug, __('PG Rating'), __('PG Rating'), $this->access_level, $this->parrent_slug . '_pgrating', array($this, 'pgrating'));

    }

    public function user_can() {
        global $user_ID;
        if (user_can($user_ID, 'editor') || user_can($user_ID, 'administrator')) {
            return true;
        }
        return false;
    }

    public function movies_rating()
    {
        echo '<h1>Movies Rating</h1>';

        $array = get_option('movies_raiting_weight');
        if (!$array)
        {
            $array=array(  'total_rwt_staff' => 5,  'total_rwt_audience' => 5, 'total_imdb' => 5, 'total_tomatoes' => 5, 'total_tmdb' => 5);

            update_option('movies_raiting_weight', $array);
        }
        else
        {
            $array = $array['rwt'];
        }
        if (!$array['total_tomatoes_audience'] ){$array['total_tomatoes_audience'] =1;}

        echo self::rating_to_table('rwt', $array);

        echo '<p style="margin: 20px;"><input type="submit" name="submit" id="submit" class="button button-primary rwt_rating_save" value="Save Changes">
<span style=" padding-left: 10px; padding-right: 10px;  font-style: italic;" class="rating_save_result"></span>
<input type="button" class="button button-primary rwt_rating_update" value="Update all RWT rating"></p>';
        echo '<h4>Rating table</h4>';

        echo '<table id="jqGrid"></table><div id="jqGridPager"></div>';


        self::rwt_rating_script();


    }

    public function overview()
    {

        /////info

        $postcount_movies = self::get_data_count_an('data_movie_imdb', " where `type` ='Movie'");
        $postcount_tv = self::get_data_count_an('data_movie_imdb', " where `type` ='TVSeries'");
        $total_mt = $postcount_movies + $postcount_tv;

        ///total audience rating
        $total_audience = self::get_data_count_an('cache_rwt_rating');
        $total_audience_filled = self::get_data_count_an('cache_rwt_rating', ' where vote > 0');
        $total_audience_percent = round(($total_audience_filled / $total_audience) * 100, 2);

        ///total staff rating
        $total_staff = self::get_data_count_an('cache_rwt_rating_staff');
        $total_staff_filled = self::get_data_count_an('cache_rwt_rating_staff', ' where vote > 0');
        $total_staff_percent = round(($total_staff_filled / $total_staff) * 100, 2);

        ///gender rating
        $total_gender = self::get_data_count_an('cache_rating');
        $total_gender_percent = round($total_gender / $total_mt * 100, 2);

        ///pg rating
        $total_post_pg_rating = self::get_data_count_an('data_pg_rating');
        $total_post_pg_rating_filled = self::get_data_count_an('data_pg_rating'," where `rwt_pg_result` > 0 ");
        $total_pg_percent = round($total_post_pg_rating_filled / $total_post_pg_rating * 100, 2);

        ///tmdb sync
        $total_tmdb_sync = self::get_data_count_an('cache_tmdb_sinc'," where `type` = 1 ");
        $total_tmdb_sync_filled = self::get_data_count_an('cache_tmdb_sinc'," where `type` = 1 and `status` = 1 ");
        $total_tmdb_sync_percent = round($total_tmdb_sync_filled / $total_tmdb_sync * 100, 2);


        echo '<h1>Rating overview</h1>
<table class="wp-list-table widefat fixed striped posts"><thead><tr><th>Type</th><th>Total Post</th><th>Total filled</th><th>Percent</th><th>Action</th></tr></thead>
<tbody>
<tr><td>Total Movies</td><td>' . $postcount_movies . '</td><td></td><td></td><td></td></tr>
<tr><td>Total TV</td><td>' . $postcount_tv . '</td><td></td><td></td<td></td></tr>
<tr><td>Total Movies & TV</td><td>' . $total_mt . '</td><td></td><td>100</td><td></td></tr>
<tr><td>Total Audience cahce</td><td>' . $total_audience . '</td><td>' . $total_audience_filled . '</td><td>' . $total_audience_percent . '</td><td></td></tr>
<tr><td>Total Staff cahce</td><td>' . $total_staff . '</td><td>' . $total_staff_filled . '</td><td>' . $total_staff_percent . '</td><td><a target="_blank" href="/analysis/include/scrap_imdb.php?update_all_audience_and_staff">Rebuild All Audience and Staff Cache</a></td></tr>
<tr><td>Total gender cahce</td><td>' . $total_gender . '</td><td>' . $total_gender . '</td><td>' . $total_gender_percent . '</td><td><a target="_blank" href="/analysis/include/scrap_imdb.php?update_all_gender_cache">Rebuild All Gender Cache</a></td></tr>
<tr><td>Total PG data</td><td>' . $total_post_pg_rating . '</td><td>' . $total_post_pg_rating_filled . '</td><td>' . $total_pg_percent . '</td><td><a target="_blank" href="/analysis/include/scrap_imdb.php?update_all_pg_rating">Rebuild All PG Rating</a></td></tr>

<tr><td>Total TMDB sync cache</td><td>' . $total_tmdb_sync . '</td><td>' . $total_tmdb_sync_filled . '</td><td>' . $total_tmdb_sync_percent . '</td><td><a target="_blank" href="/analysis/include/scrap_imdb.php?check_tmdb_data&update_all=1">Rebuild All TMDB sync cache</a></td></tr>
</tbody></table>';


    }

    public function pgrating()
    {


        if (isset($_POST)) {
            if ($_POST['action'] == 'update_array') {
                $data = $_POST['val'];
                $data = stripcslashes($data);

                $data = json_decode($data, 1);
                //var_dump($data);
                update_option('custom_rating_data', $data);
                echo 'ok';

                return;
            }
            if ($_POST['action'] == 'update_array_rating') {
                $data = $_POST['val'];
                $data = stripcslashes($data);

                $data = json_decode($data, 1);
                //var_dump($data);
                update_option('movies_raiting_weight', $data);
                echo 'ok';

                return;
            }
        }


        $total_imdb = self::get_data_imdb('data_pg_rating', 'imdb_rating');
        $total_cms = self::get_data_imdb('data_pg_rating', 'cms_rating');
        $total_dove = self::get_data_imdb('data_pg_rating', 'dove_rating');

        $total_audience_filled = self::get_data_count('post_rating', '1', ' and vote > 0');
        $total_staff_filled = self::get_data_count('post_rating', '2', ' and vote > 0');
        $total_post_pg_rating = self::get_data_count('post_pg_rating');


        echo '<h1>PG Rating</h1>
<table class="wp-list-table widefat fixed striped posts"><thead><tr><th>Total IMDB</th><th>Total Commonsensemedia</th><th>Total Dove</th><th>Total RWT audience</th><th>Total RWT staff</th><th>Total RWT PG</th></tr></thead>
<tbody>
<tr><td>' . $total_imdb . '</td><td>' . $total_cms . '</td><td>' . $total_dove . '</td><td>' . $total_audience_filled . '</td><td>' . $total_staff_filled . '</td><td>' . $total_post_pg_rating . '</td></tr>

</tbody></table>';

        echo '<h3>PG rating calculate</h3>';

        $rating_data = self::get_data();


        echo '<h4>Convert IMD Rating on a scale</h4>';
        echo self::rating_to_table('convert', $rating_data['convert']);
        echo '<h4>Relative Weight Rating data. The more weight of one data regarding the other, the more it will affect the rating</h4>';

        echo '<h4>RWT Crowdsource</h4>';
        echo self::rating_to_table('rwt', $rating_data['rwt']);
        echo '<h4>IMDB</h4>';
        echo self::rating_to_table('Imdb', $rating_data['Imdb']);
        echo '<h4>Commonsensemedia</h4>';
        echo self::rating_to_table('Commonsensemedia', $rating_data['Commonsensemedia']);
        echo '<h4>Dove</h4>';
        echo self::rating_to_table('Dove', $rating_data['Dove']);
        // var_dump($rating_data);


        echo '<h4>Total Audience and Staff weight</h4>';
        echo self::rating_to_table('Audience_Staff', $rating_data['Audience_Staff']);

        echo '<h4>Total Imdb and RWT weight</h4>';
        echo self::rating_to_table('Imdb_Rwt', $rating_data['Imdb_Rwt']);


        echo '<h4>Total Rating weight</h4>';
        echo self::rating_to_table('Positive', $rating_data['Positive']);

        echo '<h4>PG Rating limit</h4>';
        echo self::rating_to_table('PG_limit', $rating_data['PG_limit']);

        echo '<h4>Keywords limit</h4>';
        echo self::rating_to_table('words_limit', $rating_data['words_limit']);
        echo '<h4>LGBT Warning</h4>';
        echo self::rating_to_table('lgbt_warning', $rating_data['lgbt_warning'],"fullwidth ");

        echo '<h4>Woke conclusions</h4>';
        echo self::rating_to_table('woke', $rating_data['woke'],"fullwidth ");


        echo '<p style="margin: 20px;"><input type="submit" name="submit" id="submit" class="button button-primary rating_save" value="Save Changes"><span style=" padding-left: 10px; padding-right: 10px;  font-style: italic;" class="rating_save_result"></span>
<input type="button" class="button button-primary rating_update" value="Update all PG rating"></p>';
        echo '<h4>Rating table</h4>';

        echo '<table id="jqGrid"></table><div id="jqGridPager"></div>';


        self::rating_script();

    }



    public function get_data()
    {
        $rating = get_option('custom_rating_data');
        if (!$rating || !$rating['PG_limit'])
        {
            $rating = [];
            ///default data
            $rating['convert'] = array("None" => 0, "Mild" => 1.5, "Moderate" => 3, "Severe" => 5);
            $rating['Imdb'] = array("nudity" => 1, "violence" => 0.4, "profanity" => 0.7, "alcohol" => 0.4, "frightening" => 1);
            $rating['Commonsensemedia'] = array("educational" => 0.1, "message" => 0.1, "role_model" => 0.1, "sex" => 1, "violence" => 0.4, "language" => 1, "drugs" => 1, "consumerism" => 0.5);
            $rating['Dove'] = array("Faith" => 0.9, "Integrity" => 0.9, "Sex" => 1, "Language" => 0.7, "Violence" => 0.4, "Drugs" => 1, "Nudity" => 0.7, "Other" => 0.3);
            $rating['Audience_Staff'] = array('audience_rating' => 1, 'staff_rating' => 3);
            $rating['Imdb_Rwt']=array( 'total_imdb_rating' => 5, 'total_positive_rwt' => 1);
            $rating['Positive'] = array('imdb_weight' => 5, 'cms_weight' => 1, 'dove_weight' => 3,'rwt_weight' => 1);
            $rating['PG_limit'] = array('G,0+,TV-Y,TV-G,U,ATP' => '3-5', 'PG,6+,TV-Y7,TV-PG,A,T' => '2-4.5', 'PG-13,12+,TV-14,12,12A' => '1-3.5','R,16+,TV-MA,15,16'=>'0-2.4','NC-17,18+,18,R18'=>'0-2');
            $rating['words_limit'] = array('2-5' => '', '1-4' => '', '0-3' => 'gay relationship,male nudity', '0-2'=>'homosexuality,anal sex,gay sex');
            $rating['lgbt_warning'] = array('text' => 'gay relationship,homosexuality,gay sex,lesbian,gay subtext,gay man,transgender,lgbt,bisexual,gay dog','max_rating'=>'0-3.5');
            $rating['woke'] = array('text' => 'feminism,female protoganist,misogyny,female buisnes owner,male secretary,f rated,interracial','max_rating'=>'0-4');


            update_option('custom_rating_data', $rating);
        }

        if (!$rating['woke']) {
            $rating['woke'] = array('text' => 'feminism,female protoganist,misogyny,female buisnes owner,male secretary,f rated,interracial','max_rating'=>'0-4');
        }
        if (!$rating['rwt']) {
            $rating['rwt'] = array("message" => 1,  "nudity" => 1, "violence" => 1, "language" => 1, "drugs" => 1, "other" => 1);
        }
        if (!$rating['Positive']['rwt_weight'])
        {
            $rating['Positive']['rwt_weight']=1;
        }

        return $rating;
    }

    public function rating_to_table($rating_name, $rating_data,$fulwidth='')
    {
        foreach ($rating_data as $index => $val) {
            $result_head .= '<th>' . $index . '</th>';
            $style='';
            if ($fulwidth ){$style=' style="width:100%"';}
            $result_body .= '<td><input '.$style.' class="' .$index . '" value="' . $val . '"></td>';
        }

        $rating_result = '<table class="wp-list-table widefat fixed striped posts rating_table_inputs" id="' . $rating_name . '"><thead><tr>' . $result_head . '</tr></thead><tbody><tr>' . $result_body . '</tr></tbody></table>';

        return $rating_result;
    }

    public function get_data_imdb($db = '', $table = '')
    {
        ///get total rating date

//DB config
        !defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
        !class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

        $sql = "SELECT COUNT(*) AS count FROM " . $db . " WHERE " . $table . " IS NOT NULL";
        $result = Pdo_an::db_fetch_row($sql);
        return $result->count;

    }

    public function get_data_count_an($db = '', $where = '', $filled = '')
    {

        $query = "SELECT COUNT(*) AS count FROM "  . $db . " " . $where . $filled;
        $result = Pdo_an::db_fetch_row($query);
        return $result->count;
    }

    public function get_data_count($db = '', $post_type = '', $filled = '')
    {
        global $table_prefix;
        global $wpdb;
        if ($db == 'posts') {

            $where = " WHERE post_type = '" . $post_type . "'";

        }
        if ($db == 'post_rating') {

            $where = " WHERE type = '" . $post_type . "'";

        }

        $query = "SELECT COUNT(*) AS count FROM " . $table_prefix . $db . " " . $where . $filled;
        $result = $wpdb->get_row($query);
        return $result->count;
    }

    public function rwt_rating_script()
    {

        ?>
        <script type="text/ecmascript" src="<?php echo home_url(); ?>/analysis/jqgrid/js/i18n/grid.locale-en.js"></script>
        <!-- This is the Javascript file of jqGrid -->
        <script type="text/ecmascript" src="<?php echo home_url(); ?>/analysis/jqgrid/js/jquery.jqGrid.min.js"></script>
        <script>
            jQuery.jgrid.defaults.responsive = true;
            jQuery.jgrid.defaults.styleUI = 'Bootstrap';
        </script>
        <script src="https://code.highcharts.com/stock/highstock.js"></script>

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" media="screen" href="<?php echo home_url(); ?>/analysis/jqgrid/css/ui.jqgrid-bootstrap4.css" />
        <link rel="stylesheet" href="<?php echo home_url(); ?>/wp-content/themes/custom_twentysixteen/css/movie_single.css?<?php echo LASTVERSION; ?>">
        <link rel="stylesheet" href="<?php echo home_url(); ?>/wp-content/themes/custom_twentysixteen/css/colums_template.css?<?php echo LASTVERSION; ?>">
        <script type="text/javascript">

            jQuery(document).ready(function () {



                jQuery('.rwt_rating_update').click(function () {

                    window.open(window.location.protocol+"/analysis/include/scrap_imdb.php?update_all_rwt_rating=1");

                });
                jQuery('.rwt_rating_save').click(function () {

                    var rating = new Object();

                        var id = jQuery('.rating_table_inputs').attr('id');

                        if (!rating[id]) {
                            rating[id] = new Object();
                        }


                        jQuery('.rating_table_inputs').find('input').each(function () {
                            var index = jQuery(this).attr('class');
                            var val = jQuery(this).val();
                            if (!val) {
                                val = 0
                            }
                            rating[id][index] = val;

                        });

                    if (rating) {
                        var rating_string = JSON.stringify(rating);
                    }

                    jQuery('.rating_save_result').html('updating...');


                    jQuery.ajax({
                        type: 'post',
                        url: '?page=custom_rating_pgrating',
                        data: ({'action': 'update_array_rating', 'val': rating_string}),

                        success: function (html) {

                            jQuery('.rating_save_result').html('data saved');

                        }
                    });


                });


                //////rating table


                function getSubgrid(subgrid_id, row_id){

                    ////check select grig


                    var movie  = jQuery("#jqGrid").jqGrid('getCell',row_id,'movie_id');


                    jQuery.ajax({
                        type: "POST",
                        url: window.location.protocol+"/analysis/get_data.php",

                        data: ({
                            oper: 'movie_data',
                            refresh_rwt_rating:1,
                            rwt_id: movie,
                        }),
                        success: function (html) {
                            jQuery('#'+subgrid_id).html(html);
                        }
                    });

                }

////rwt_audience 	rwt_staff 	imdb 	rotten_tomatoes 	tmdb 	total_rating 	last_update

                jQuery("#jqGrid").jqGrid({
                    url: window.location.protocol+'/analysis/jqgrid/get.php?data=movie_rating',
                    mtype: "POST",
                    datatype: "json",
                    page: 1,
                    colModel: [
                        {   label : 'id',
                            name: 'id',
                            key: true,
                            width: 10,
                            editable:true,
                            hidden:true

                        },      {   label : 'movie_id',
                            name: 'movie_id',
                            key: true,
                            width: 10,
                            editable:true

                        },
                        {   label : 'title',
                            name: 'title',
                            key: true,
                            width: 40,
                            editable:true

                        },      {   label : 'rwt_audience',
                            name: 'rwt_audience',
                            key: true,
                            width: 10,
                            editable:true

                        },      {   label : 'rwt_staff',
                            name: 'rwt_staff',
                            key: true,
                            width: 10,
                            editable:true

                        },      {   label : 'imdb',
                            name: 'imdb',
                            key: true,
                            width: 10,
                            editable:true

                        },
                        {   label : 'rotten_tomatoes',
                            name: 'rotten_tomatoes',
                            key: true,
                            width: 10,
                            editable:true

                        },
                        {   label : 'rotten_tomatoes_audience',
                            name: 'rotten_tomatoes_audience',
                            key: true,
                            width: 10,
                            editable:true

                        },

                        {   label : 'tmdb',
                            name: 'tmdb',
                            key: true,
                            width: 10,
                            editable:true,
                            hidden:true

                        },
                        {   label : 'total_rating',
                            name: 'total_rating',
                            key: true,
                            width: 10,
                            editable:true

                        },
                        {   label : 'last_update',
                            name: 'last_update',
                            key: true,
                            width: 15,
                            editable:true

                        },                ],
                    // editurl:  window.location.protocol+'/analysis/jqgrid/get.php?data=pg_rating',
                    loadonce: false,
                    viewrecords: true,
                    width: (window.innerWidth-200),
                    height: (window.innerHeight-200),
                    rowNum: 100,
                    pager: "#jqGridPager",
                    subGrid: true,
                    subGridRowExpanded: function(subgrid_id, row_id) {
                        getSubgrid(subgrid_id, row_id);
                    },
                    afterInsertRow: function(row_id, row_data){
                        ///  console.log(row_id,row_data);
                    },
                });
                // activate the toolbar searching
                jQuery('#jqGrid').jqGrid('filterToolbar', {stringResult: true, searchOnEnter: false, defaultSearch: 'cn', ignoreCase: true});
                jQuery('#jqGrid').jqGrid('navGrid',"#jqGridPager", {
                    search: true, // show search button on the toolbar
                    add: false,
                    edit: false,
                    del: false,
                    refresh: true
                },{
                    onclickSubmit: function() {
                        setTimeout(function () {
                            $('#edithdjqGrid .ui-jqdialog-titlebar-close').click();
                        },500);
                    },
                });




            });
        </script>
        <style type="text/css">
            .movie_container {
                font-size: 2rem;
            }
            .subgrid-data   td {
                white-space: pre-wrap;
                word-wrap: anywhere;
            }

        </style>
        <?php

    }
    public function rating_script()
    {

        ?>
        <script type="text/ecmascript" src="<?php echo home_url(); ?>/analysis/jqgrid/js/i18n/grid.locale-en.js"></script>
        <!-- This is the Javascript file of jqGrid -->
        <script type="text/ecmascript" src="<?php echo home_url(); ?>/analysis/jqgrid/js/jquery.jqGrid.min.js"></script>
        <script>
            jQuery.jgrid.defaults.responsive = true;
            jQuery.jgrid.defaults.styleUI = 'Bootstrap';
        </script>
        <script src="https://code.highcharts.com/stock/highstock.js"></script>

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" media="screen" href="<?php echo home_url(); ?>/analysis/jqgrid/css/ui.jqgrid-bootstrap4.css" />
        <link rel="stylesheet" href="<?php echo home_url(); ?>/wp-content/themes/custom_twentysixteen/css/movie_single.css?<?php echo LASTVERSION; ?>">
        <link rel="stylesheet" href="<?php echo home_url(); ?>/wp-content/themes/custom_twentysixteen/css/colums_template.css?<?php echo LASTVERSION; ?>">
        <script type="text/javascript">

            jQuery(document).ready(function () {



                jQuery('.rating_update').click(function () {

                    window.open(window.location.protocol+"/analysis/include/scrap_imdb.php?update_all_pg_rating");

                });
                jQuery('.rating_save').click(function () {

                    var rating = new Object();
                    jQuery('.rating_table_inputs').each(function () {
                        var id = jQuery(this).attr('id');

                        if (!rating[id]) {
                            rating[id] = new Object();
                        }

                        jQuery(this).find('input').each(function () {
                            var index = jQuery(this).attr('class');
                            var val = jQuery(this).val();
                            if (!val) {
                                val = 0
                            }
                            rating[id][index] = val;

                        });


                    });
                    if (rating) {
                        var rating_string = JSON.stringify(rating);
                    }

                    jQuery('.rating_save_result').html('updating...');
                    //console.log(rating);
                    jQuery.ajax({
                        type: 'post',
                        url: '?page=custom_rating_pgrating',
                        data: ({'action': 'update_array', 'val': rating_string}),

                        success: function (html) {

                            jQuery('.rating_save_result').html('data saved');

                        }
                    });


                });


                //////rating table


                function getSubgrid(subgrid_id, row_id){

                    ////check select grig


                   var movie  = jQuery("#jqGrid").jqGrid('getCell',row_id,'movie_id');


                    jQuery.ajax({
                            type: "POST",
                            url: window.location.protocol+"/analysis/get_data.php",

                            data: ({
                                oper: 'movie_data',
                                refresh_rating:1,
                                id: movie
                            }),
                            success: function (html) {
                                jQuery('#'+subgrid_id).html(html);
                            }
                        });

                }



                jQuery("#jqGrid").jqGrid({
                    url: window.location.protocol+'/analysis/jqgrid/get.php?data=pg_rating',
                    mtype: "POST",
                    datatype: "json",
                    page: 1,
                    colModel: [
                        {   label : 'id',
                            name: 'id',
                            key: true,
                            width: 10,
                            editable:true,
                            hidden:true

                        },      {   label : 'movie_id',
                            name: 'movie_id',
                            key: true,
                            width: 15,
                            editable:true

                        },      {   label : 'rwt_id',
                            name: 'rwt_id',
                            key: true,
                            width: 15,
                            editable:true

                        },      {   label : 'movie_title',
                            name: 'movie_title',
                            key: true,
                            width: 40,
                            editable:true

                        },      {   label : 'pg',
                            name: 'pg',
                            key: true,
                            width: 10,
                            editable:true

                        },      {   label : 'certification',
                            name: 'certification',
                            key: true,
                            width: 10,
                            editable:true

                        },      {   label : 'certification_countries',
                            name: 'certification_countries',
                            key: true,
                            width: 10,
                            editable:true

                        },      {   label : 'imdb_date',
                            name: 'imdb_date',
                            key: true,
                            width: 10,
                            editable:true,
                            hidden:true

                        },      {   label : 'imdb_rating',
                            name: 'imdb_rating',
                            key: true,
                            width: 30,
                            editable:true

                        },      {   label : 'imdb_rating_desc',
                            name: 'imdb_rating_desc',
                            key: true,
                            width: 10,
                            editable:true,
                            hidden:true

                        },      {   label : 'imdb_result',
                            name: 'imdb_result',
                            key: true,
                            width: 10,
                            editable:true

                        },      {   label : 'cms_date',
                            name: 'cms_date',
                            key: true,
                            width: 10,
                            editable:true,
                            hidden:true

                        },      {   label : 'cms_link',
                            name: 'cms_link',
                            key: true,
                            width: 10,
                            editable:true,
                            hidden:true

                        },      {   label : 'cms_rating',
                            name: 'cms_rating',
                            key: true,
                            width: 30,
                            editable:true

                        },      {   label : 'cms_rating_desk',
                            name: 'cms_rating_desk',
                            key: true,
                            width: 10,
                            editable:true,
                            hidden:true

                        },      {   label : 'cms_result',
                            name: 'cms_result',
                            key: true,
                            width: 10,
                            editable:true

                        },      {   label : 'dove_date',
                            name: 'dove_date',
                            key: true,
                            width: 10,
                            editable:true,
                            hidden:true

                        },      {   label : 'dove_link',
                            name: 'dove_link',
                            key: true,
                            width: 10,
                            editable:true,
                            hidden:true

                        },      {   label : 'dove_rating',
                            name: 'dove_rating',
                            key: true,
                            width: 30,
                            editable:true

                        },      {   label : 'dove_rating_desc',
                            name: 'dove_rating_desc',
                            key: true,
                            width: 10,
                            editable:true,
                            hidden:true

                        },      {   label : 'dove_result',
                            name: 'dove_result',
                            key: true,
                            width: 10,
                            editable:true

                        },      {   label : 'rwt_audience',
                            name: 'rwt_audience',
                            key: true,
                            width: 10,
                            editable:true

                        },      {   label : 'rwt_staff',
                            name: 'rwt_staff',
                            key: true,
                            width: 10,
                            editable:true

                        },
                        {   label : 'lgbt_warning',
                            name: 'lgbt_warning',
                            key: true,
                            width: 10,
                            editable:true

                        },
                        {   label : 'woke',
                            name: 'woke',
                            key: true,
                            width: 10,
                            editable:true

                        },
                        {   label : 'rwt_pg_result',
                            name: 'rwt_pg_result',
                            key: true,
                            width: 15,
                            editable:true

                        },                ],
                   // editurl:  window.location.protocol+'/analysis/jqgrid/get.php?data=pg_rating',
                    loadonce: false,
                    viewrecords: true,
                    width: (window.innerWidth-200),
                    height: (window.innerHeight-200),
                    rowNum: 100,
                    pager: "#jqGridPager",
                    subGrid: true,
                    subGridRowExpanded: function(subgrid_id, row_id) {
                        getSubgrid(subgrid_id, row_id);
                    },
                    afterInsertRow: function(row_id, row_data){
                      ///  console.log(row_id,row_data);
                    },
                });
                // activate the toolbar searching
                jQuery('#jqGrid').jqGrid('filterToolbar', {stringResult: true, searchOnEnter: false, defaultSearch: 'cn', ignoreCase: true});
                jQuery('#jqGrid').jqGrid('navGrid',"#jqGridPager", {
                    search: true, // show search button on the toolbar
                    add: false,
                    edit: false,
                    del: false,
                    refresh: true
                },{
                    onclickSubmit: function() {
                        setTimeout(function () {
                            $('#edithdjqGrid .ui-jqdialog-titlebar-close').click();
                        },500);
                    },
                });




            });
        </script>
<style type="text/css">
    .movie_container {
        font-size: 2rem;
    }
    .subgrid-data   td {
        white-space: pre-wrap;
        word-wrap: anywhere;
    }
</style>
        <?php

    }

}

$rating = new CustomRating;