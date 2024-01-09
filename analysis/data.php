<?php
error_reporting(E_ERROR);

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
include  ABSPATH.'an_config.php';

global $WP_include;

if (!$WP_include) {
    include ABSPATH . 'wp-load.php';
}

if (function_exists('current_user_can')) {
    $curent_user = current_user_can("administrator");
}

if ($curent_user) {


    $home_url = WP_SITEURL. '/';

//DB config
    !defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
    !class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';




    if (isset($_GET['onlytable']))
    {
        $currant_table =  $_GET['onlytable'];
        $table = preg_replace("/[^a-zA-Z0-9_]/", "",$currant_table);
        $datatype=$table;


        $request = $_GET;
        unset($request['onlytable']);



    echo '<h4>Table: '.$table.'</h4>';
    }
    else {

        $datatype = $_GET['table'];
        if (!$datatype)
            $datatype = 'movie_imdb';


        $links = '';


        $sql = "SELECT *
FROM information_schema.tables
WHERE table_type='BASE TABLE'
AND table_schema='" . DB_NAME_AN . "'";

        $rows = Pdo_an::db_results_array($sql);

        if ($WP_include) {
            $links = '';
        }
        foreach ($rows as $r) {
///var_dump($r);
            $link = $r["TABLE_NAME"];
            if (strstr($link, 'data_')) {

                $array_meta[substr($link, 5)] = $link;


                $link = substr($link, 5);
                $selected = '';
                if ($datatype == $link) {
                    $selected = ' selected ';
                }
                if ($WP_include) {
                    $links .= '<a class="' . $selected . '"  href="' . $home_url . 'wp-admin/admin.php?page=light_movies&table=' . $link . '">' . $link . '</a>';
                } else {
                    $links .= '<a class="' . $selected . '"  href="?table=' . $link . '">' . $link . '</a>';
                }
            }
        }
        if ($WP_include) {
            echo '<div class="header_menu" style="display: flex;margin: 10px;">' . $links . '</div>';
        } else {

            if ($links) {
                echo '<div class="header_menu" style="display: flex;margin: 10px;">' . $links . '<a href="index.php">Graph</a> </div>';
            }
        }

        $currant_table = $array_meta[$datatype];
    }

    $sql = "SHOW COLUMNS FROM " . $currant_table;

    $update_row='';
    $rows = Pdo_an::db_results_array($sql);


    $count_rows = count($rows);
    $counts_name =0;


    $requset_result =[];

    foreach ($rows as $r) {



        $name = $r["Field"];

        if ($request[$name])
        {
            $requset_result[$name] = $request[$name];
        }

        $counts_name+= mb_strlen($name, 'UTF-8');

        if (!$update_row)
        {
            if ($name == 'add_time'){$update_row = 'add_time';}
            else   if ($name == 'last_update'){$update_row = 'last_update';}
            else   if ($name == 'lastupdate'){$update_row = 'lastupdate';}
            else   if ($name == 'last_upd'){$update_row = 'last_upd';}

        }



        if ($name == 'id') {
            $colums .= "      {   label : '" . $name . "',
                        name: '" . $name . "',
                        key: true,
                        width: 10,
                        editable:true,
                        editoptions: { disabled:true  }

                    },";
        } else {
            $colums .= "      {   label : '" . $name . "',
                        name: '" . $name . "',
                        key: true,
                        width: 20,
                        editable:true,
                        edittype:'textarea',
                        editoptions: { cols: 45,rows: 1  }

                    },";
        }
    }

    if ($requset_result)
    {
        !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
        TMDB::var_dump_table($requset_result);
    }


    $r_string =  json_encode($requset_result);

    $min_width = $counts_name*10+$count_rows*10+80;
    if (!$min_width)
    {
        $min_width=800;
    }

    ?>
    <html>
        <head>
            <title>Actors data</title>
            <script type="text/ecmascript" src="<?php echo $home_url ?>analysis/js/jquery.min.js"></script>
            <!-- We support more than 40 localizations -->
            <script type="text/ecmascript" src="<?php echo $home_url ?>analysis/jqgrid/js/i18n/grid.locale-en.js"></script>
            <!-- This is the Javascript file of jqGrid -->
            <script type="text/ecmascript" src="<?php echo $home_url ?>analysis/jqgrid/js/jquery.jqGrid.min.js"></script>
            <script>
                jQuery.jgrid.defaults.responsive = true;
                jQuery.jgrid.defaults.styleUI = 'Bootstrap';
            </script>
            <script src="https://code.highcharts.com/stock/highstock.js"></script>
        <!--    <script src="https://code.highcharts.com/highcharts-more.js"></script>-->
        <!--    <script src="https://code.highcharts.com/modules/exporting.js"></script>-->
        <!--    <script src="https://code.highcharts.com/modules/export-data.js"></script>-->
        <!--    <script src="https://code.highcharts.com/modules/accessibility.js"></script>-->
        <!--    <script src="https://code.highcharts.com/modules/series-label.js"></script>-->

            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
            <link rel="stylesheet" type="text/css" media="screen" href="<?php echo $home_url ?>analysis/jqgrid/css/ui.jqgrid-bootstrap4.css" />
            <link rel="stylesheet" href="<?php echo $home_url . 'wp-content/themes/custom_twentysixteen/css/theme-style-colors.css?' . LASTVERSION ?>">
            <link rel="stylesheet" href="<?php echo $home_url . 'wp-content/themes/custom_twentysixteen/css/movie_single.css?' . LASTVERSION ?>">
            <link rel="stylesheet" href="<?php echo $home_url . 'wp-content/themes/custom_twentysixteen/css/colums_template.css?' . LASTVERSION ?>">
            <script src="<?php echo $home_url . 'wp-content/themes/custom_twentysixteen/js/section_home.js?' . LASTVERSION ?>"></script>

            <script type="text/javascript">
                function getSubgrid(subgrid_id, row_id){

                ////check select grig


                var grid = $('.header_menu a.selected').html();

                    <?php     if (isset($_GET['onlytable'])) {

                    ?>
                    var grid ='<?php echo $datatype; ?>';
                    <?php
                    }
                    ?>

               // console.log(grid);
                if (grid == 'actors_imdb' || grid == 'data_actors_imdb'  )
                {
                var actor_id = jQuery("#jqGrid").jqGrid('getCell', row_id, 'id');
                }
                else if (grid == 'movie_imdb' || grid == 'data_movie_imdb')
                {
                var movie = jQuery("#jqGrid").jqGrid('getCell', row_id, 'movie_id');
                }
                else
                {
                var actor_id = jQuery("#jqGrid").jqGrid('getCell', row_id, 'actor_id');
                if (!actor_id)
                {
                    actor_id = jQuery("#jqGrid").jqGrid('getCell', row_id, 'aid');
                }
                var movie = jQuery("#jqGrid").jqGrid('getCell', row_id, 'movie_id');

                var rwt_id='';

                if (jQuery("#jqGrid").jqGrid('getCell', row_id, 'mid'))
                {
                    rwt_id=jQuery("#jqGrid").jqGrid('getCell', row_id, 'mid');
                }


                }


                if (movie)
                {

                $.ajax({
                type: "POST",
                        url: "<?php echo $home_url ?>analysis/get_data.php",
                        data: ({
                        oper: 'movie_data',
                                id: movie,
                                rwt_id:rwt_id,
                                data:"{\"movie_type\":[],\"movie_genre\":[],\"animation\":\"0\",\"inflation\":\"0\",\"start\":\"1800\",\"end\":\"2100\",\"actor_type\":[\"star\",\"main\"],\"diversity_select\":\"default\",\"display_select\":\"date_range_international\",\"country_movie_select\":[],\"display_xa_axis\":\"Box+Office+Worldwide\",\"color\":\"default\",\"ethnycity\":{\"1\":{\"ethnic\":1},\"2\":{\"jew\":1},\"3\":{\"face\":1},\"4\":{\"face2\":1},\"5\":{\"surname\":1}}}"
                        }),
                        success: function (html) {
                        $('#' + subgrid_id).html(html);
                        }
                });
                }




                else if (actor_id) {
                /// console.log(actor_id);
                ///$('#'+subgrid_id).html('<img src="create_image.php?id='+actor_id+'&nocache=1" />');
                $.ajax({
                type: "POST",
                        url: "<?php echo $home_url ?>analysis/get_data.php",
                        data: ({
                        oper: 'get_actordata',
                                id: actor_id

                        }),
                        success: function (html) {
                        $('#' + subgrid_id).html(html);
                        }
                });
                }
                }

                function convertTimestamp(timestamp) {
                var d = new Date(timestamp * 1000), // Convert the passed timestamp to milliseconds
                        yyyy = d.getFullYear(),
                        mm = ('0' + (d.getMonth() + 1)).slice( - 2), // Months are zero based. Add leading 0.
                        dd = ('0' + d.getDate()).slice( - 2), // Add leading 0.
                        hh = d.getHours(),
                        h = hh,
                        min = ('0' + d.getMinutes()).slice( - 2), // Add leading 0.
                        ampm = 'AM',
                        time;
                // ie: 2014-03-24, 3:00 PM
                time = yyyy + '-' + mm + '-' + dd + ', ' + h + ':' + min;
                return time;
                }

                $(document).ready(function () {

                    var theight = window.innerHeight - 220;
                    if (theight<600)theight=600;


                    var t_width;
                    <?php if ($WP_include) { ?>
                    t_width= (window.innerWidth - 190);
                    <?php } else { ?>
                        t_width= (window.innerWidth - 1);
                    <?php } ?>
                    var min_width = <?php echo $min_width; ?>;

                    if (min_width>t_width)
                    {
                      t_width  = min_width;
                    }

                    jQuery("#jqGrid").jqGrid({
                url: '<?php echo $home_url ?>analysis/jqgrid/get.php?data=<?php echo $datatype; ?>',
                            mtype: "POST",
                            datatype: "json",
                            page: 1,
                        postData: {

                        "qustom_request":'<?php echo $r_string ?>'
                        },


                            colModel: [
    <?php echo $colums; ?>
                            ],
    <?php if ($WP_include) { ?>

                        editurl: '<?php echo $home_url ?>analysis/jqgrid/get.php?data=<?php echo $datatype; ?>',
    <?php } ?>

                        loadonce: false,
                                viewrecords: true,

                           width: t_width,
                           height: theight,
                                rowNum: 100,
                                pager: "#jqGridPager",
                                multiselect: true,

                                subGrid: true,
                                subGridRowExpanded: function(subgrid_id, row_id) {

                                getSubgrid(subgrid_id, row_id);
                                },
                    afterInsertRow : function( row_id, rowdata, rawdata) {



                    //     if (rowdata.add_time) {
                    //         let timeStampCon = convertTimestamp(rowdata.add_time);
                    //         jQuery('#jqGrid').jqGrid('setCell', row_id, 'add_time', timeStampCon);
                    //     }
                    //     if (rowdata.lastupdate) {
                    //         let timeStampCon = convertTimestamp(rowdata.lastupdate);
                    //         jQuery('#jqGrid').jqGrid('setCell', row_id, 'lastupdate', timeStampCon);
                    //     }
                    //     if (rowdata.last_update) {
                    //         let timeStampCon = convertTimestamp(rowdata.last_update);
                    //         jQuery('#jqGrid').jqGrid('setCell', row_id, 'last_update', timeStampCon);
                    //     }
                    //
                    //
                    },

                        });
                        // activate the toolbar searching
                        jQuery('#jqGrid').jqGrid('filterToolbar', {stringResult: true, searchOnEnter: false, defaultSearch: 'cn', ignoreCase: true});
                        jQuery('#jqGrid').jqGrid('navGrid', "#jqGridPager", {
                        search: true, // show search button on the toolbar

    <?php if ($WP_include) { ?>
                            add: true,
                                    edit: true,
                                    del: true,
    <?php } else { ?>

                            edit: false,
                                    del: false,
    <?php } ?>
                        refresh: true
                        },
                        {
                        onclickSubmit: function () {
                        setTimeout(function () {
                        $('#edithdjqGrid .ui-jqdialog-titlebar-close').click();
                        }, 500);
                        var id = $('.FormGrid input[name="id"]').val();
                        // console.log(id);

                        return {parent:id};
                        // setTimeout(function () {$('#edithdjqGrid .ui-jqdialog-titlebar-close').click();  }, 500);


                        },
                        },
                        {
                        onclickSubmit: function () {

                        var id = $('.FormGrid input[name="id"]').val();
                        // console.log(id);

                        return {parent:id};
                        // setTimeout(function () {$('#edithdjqGrid .ui-jqdialog-titlebar-close').click();  }, 500);


                        },
                        },
                        {
                        onclickSubmit: function () {

                        var id = $('tr.success td[aria-describedby="jqGrid_id"]').html();
                        //console.log('id', id);
                        return {parent:id};
                        // setTimeout(function () {$('#edithdjqGrid .ui-jqdialog-titlebar-close').click();  }, 500);


                        },
                        }

                        );
                        });

                function getSelectedRows() {
                    var grid = $("#jqGrid");
                    var rowKey = grid.getGridParam("selrow");

                    if (!rowKey)
                        alert("No rows are selected");
                    else {
                        var selectedIDs = grid.getGridParam("selarrrow");

                        var result = new Array();
                        for (var i = 0; i < selectedIDs.length; i++) {

                            let id  =jQuery('tr[id="'+selectedIDs[i]+'"] td[aria-describedby="jqGrid_id"]').html();

                            id = Number(id);
                            result.push(id);
                        }
                        return(result);
                    }
                }

                jQuery('body').on('change','select.bulk-actions', function (e) {
                    var valueSelected = this.value;
                    console.log(valueSelected);
                    if (valueSelected == 'change_column') {


                       let cntnt =  jQuery('.ui-jqgrid .ui-jqgrid-htable').html();

                        jQuery('.notice_edit').html('<table>'+cntnt+'</table>');

                    }
                });

                jQuery('body').on('click','.update_crowd', function (e) {

                    var thiss = jQuery(this);
                    var table =thiss.attr('data-value');
                    var action =jQuery('.bulk-actions').val();
                    let update_fields_str;
                    let update_fields = {};
                    if (action =='change_column')
                    {
                        jQuery('.notice_edit table input').each(function (){
                            let val = jQuery(this).val();
                            let id = jQuery(this).attr('id');
                            if (val && id !='cb_jqGrid') {

                                id =id.substr(3);

                                update_fields[id] = val;
                            }

                        });
                        jQuery('.notice_edit').html('');
                        jQuery('select.bulk-actions').val('none').change();
                    }

                    var data_crowd  = getSelectedRows();

                    thiss.attr('disabled','disabled');
                    if (data_crowd && action !='none')
                    {

                        var data_crowd_str =  JSON.stringify(data_crowd);
                        jQuery.ajax({
                            type: "POST",
                            url: window.location.href,
                            data: ({
                                oper: 'update_crowd',
                                ids: data_crowd_str,
                                table:table,
                                action:action,
                                fields:update_fields

                            }),
                            success: function () {
                                thiss.attr('disabled',false);
                                jQuery('#refresh_jqGrid .glyphicon-refresh').click();

                            }
                        });
                    }

                });

            </script>
        </head>
        <body>
        <?php
        !class_exists('Last_update') ? include ABSPATH . "analysis/include/last_update_graph.php" : '';
        $Last_update = new Last_update();

        if ($currant_table && $update_row)
        {
            $Last_update->show_graph($currant_table,$update_row,$request);
        }



        ?>

        <?php if ($WP_include) { ?>
        <div class="bulk-actions-holder">
            <select autocomplete="off" name="bulkaction" class="bulk-actions">
                <option value="none">Bulk actions</option>
                <option value="change_column">Change the column value</option>
                <option value="trash">Delete</option>
            </select>
            <span class="notice_edit">
            </span>
            <input type="submit" id="edit-submit" data-value="<?php echo $currant_table ?>" value="Submit" class="update_crowd button-primary">
        </div>
<?php } ?>


        <?php
        if (isset($_GET['onlytable'])) {echo '<a href="'.$_SERVER['REQUEST_URI'].'" target="_blank">Open in a new tab</a>';}
        ?>

            <table id="jqGrid"></table>
            <div id="jqGridPager"></div>
                <!--   <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>-->

        <!-- <script type="text/ecmascript" src="../../../js/bootstrap-datepicker.js"></script>
          <script type="text/ecmascript" src="../../../js/bootstrap3-typeahead.js"></script>
          <link rel="stylesheet" type="text/css" media="screen" href="../../../css/bootstrap-datepicker.css" />
            -->

            <style type="text/css">
                .notice_edit a,   .notice_edit #cb_jqGrid{
                    display: none;
                }

                .tablediv{
                    max-width: 1200px;
                }
                .notice_edit input{
                    margin-right: 10px;
                }
                .notice_edit table{ margin: 10px 0}


                input.cbox[type="checkbox"] {
                    height: 20px;
                    width: 20px;
                }

                input.cbox[type="checkbox"]:checked::before  {
                    width: 20px;
                    height: 21px;
                }
                .movie_description_container{
                    font-size: 16px;
                }
                .nte_show {
                    display: none;
                }
                #container_main_movie_graph{
                    display: none;
                }

    <?php if ($datatype == 'movie_ethnic'///  || $datatype=='population_country'
    ) {
        ?>
                    .ui-jqgrid .ui-jqgrid-btable tbody tr.jqgrow td {
                        overflow: visible;
                        white-space: normal!important;
                        height: auto!important;
                        word-wrap: break-word!important;
                    }
    <?php } ?>

                a.selected, a.selected:hover {
                    background-color: #3b3b3b;
                    color: #fff;
                    font-weight: bold;
                    text-decoration: none;
                }
                .header_menu a{
                    padding: 5px;
                    font-size: 12px;
                }
            </style>
        </body>

    </html>

<?php } ?>

