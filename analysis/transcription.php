<?php
error_reporting(E_ERROR );

global $WP_include;

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');



//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

$home_url = WP_SITEURL.'/';

$datatype= $_GET['table'];
if (!$datatype)$datatype='youtube';


$links=  '';


$sql = "SELECT *
FROM information_schema.tables
WHERE table_type='BASE TABLE'
AND table_schema='transcriptions'";

$rows = Pdo_tc::db_results_array($sql);



foreach ($rows as $r)
{
///var_dump($r);
    $link = $r["TABLE_NAME"];

    $array_meta[$link]=$link;



        $selected='';
        if ($datatype==$link)
        {
            $selected = ' selected ';
        }

            $links.=  '<a class="'.$selected.'"  href="?table='.$link.'">'.$link.'</a>';


}
if ($links)
{
    echo '<div class="header_menu" style="display: flex;margin: 10px;">'.$links.'</div>';
}



    $sql = "SHOW COLUMNS FROM ".$array_meta[$datatype];




$rows = Pdo_tc::db_results_array($sql);

foreach ($rows as $r)
{

    $name = $r["Field"];

    $colums.="      {   label : '".$name."',
                        name: '".$name."',
                        key: true,
                        width: 10,
                        editable:true

                    },";
}







?>
<html>
<head>
    <title>transcription data</title>
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
    <link rel="stylesheet" href="<?php echo $home_url.'wp-content/themes/custom_twentysixteen/css/movie_single.css?'.LASTVERSION ?>">
    <link rel="stylesheet" href="<?php echo $home_url.'wp-content/themes/custom_twentysixteen/css/colums_template.css?'.LASTVERSION ?>">
    <script type="text/javascript">


        function convertTimestamp(timestamp) {
            var d = new Date(timestamp * 1000), // Convert the passed timestamp to milliseconds
                yyyy = d.getFullYear(),
                mm = ('0' + (d.getMonth() + 1)).slice(-2),  // Months are zero based. Add leading 0.
                dd = ('0' + d.getDate()).slice(-2),         // Add leading 0.
                hh = d.getHours(),
                h = hh,
                min = ('0' + d.getMinutes()).slice(-2),     // Add leading 0.
                ampm = 'AM',
                time;


            // ie: 2014-03-24, 3:00 PM
            time = yyyy + '-' + mm + '-' + dd + ', ' + h + ':' + min ;
            return time;
        }

        $(document).ready(function () {

            jQuery("#jqGrid").jqGrid({
                url: '<?php echo $home_url ?>analysis/jqgrid/get.php?data=<?php echo $datatype; ?>&doptable=<?php echo $datatype;?>&db=transcriptions',
                mtype: "POST",
                datatype: "json",
                page: 1,
                colModel: [
                    <?php echo $colums; ?>
                ],

                <?php if ($WP_include) { ?>

                 editurl: '<?php echo $home_url ?>analysis/jqgrid/get.php?data=<?php echo $datatype; ?>&doptable=<?php echo $datatype;?>&db=transcriptions',

                <?php } ?>

                loadonce: false,
                viewrecords: true,

                <?php if ($WP_include) { ?>
                width: (window.innerWidth-190),
                <?php } else { ?>
                width: (window.innerWidth-1),
                <?php }  ?>
                height: (window.innerHeight-220),
                rowNum: 100,
                pager: "#jqGridPager",
                afterInsertRow : function( row_id, rowdata, rawdata) {

                    if (rowdata.add_time) {
                        let timeStampCon = convertTimestamp(rowdata.add_time);
                        jQuery('#jqGrid').jqGrid('setCell', row_id, 'add_time', timeStampCon);
                    }
                    if (rowdata.lastupdate) {
                        let timeStampCon = convertTimestamp(rowdata.lastupdate);
                        jQuery('#jqGrid').jqGrid('setCell', row_id, 'lastupdate', timeStampCon);
                    }



                },
               // subGrid: true,
                // subGridRowExpanded: function(subgrid_id, row_id) {
                //     getSubgrid(subgrid_id, row_id);
                // },

            });
            // activate the toolbar searching
            jQuery('#jqGrid').jqGrid('filterToolbar', {stringResult: true, searchOnEnter: false, defaultSearch: 'cn', ignoreCase: true});
            jQuery('#jqGrid').jqGrid('navGrid',"#jqGridPager", {
                search: true, // show search button on the toolbar

                <?php if ($WP_include) { ?>
                add: true,
                edit: true,
                del: true,

                <?php }  else  { ?>

                edit: false,
                del: false,

                <?php }   ?>
                refresh: true
            },
            {
                onclickSubmit: function () {
                    setTimeout(function () {
                        $('#edithdjqGrid .ui-jqdialog-titlebar-close').click();
                    },500);
                    var id  = $('.FormGrid input[name="id"]').val();
                    // console.log(id);

                    return {parent:id};

                    // setTimeout(function () {$('#edithdjqGrid .ui-jqdialog-titlebar-close').click();  }, 500);


                },
            },
            {
                onclickSubmit: function () {

                    var id  = $('.FormGrid input[name="id"]').val();
                    // console.log(id);

                    return {parent:id};

                    // setTimeout(function () {$('#edithdjqGrid .ui-jqdialog-titlebar-close').click();  }, 500);


                },
            },
            {
                onclickSubmit: function () {

                    var id  = $('tr.success td[aria-describedby="jqGrid_id"]').html();
                    console.log('id',id);

                    return {parent:id};

                    // setTimeout(function () {$('#edithdjqGrid .ui-jqdialog-titlebar-close').click();  }, 500);


                },
            }

            );
        });
    </script>
</head>
<body>
<table id="jqGrid"></table>
<div id="jqGridPager"></div>
    <!--   <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>-->

    <!-- <script type="text/ecmascript" src="../../../js/bootstrap-datepicker.js"></script>
      <script type="text/ecmascript" src="../../../js/bootstrap3-typeahead.js"></script>
      <link rel="stylesheet" type="text/css" media="screen" href="../../../css/bootstrap-datepicker.css" />
-->
<style type="text/css">

    .movie_description_container{
        font-size: 16px;
    }


    .ui-jqgrid .ui-jqgrid-btable tbody tr.jqgrow td {
        overflow: hidden;
        white-space: nowrap    !important;
    }


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