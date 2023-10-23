<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';



class Sheme
{

public static function table()
{
    !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

    $array_rows = array(
        'id'=>array('w'=>5),
    );

    Crowdsource::Show_admin_table('option_sheme',$array_rows,1,'option_sheme','',1,1,1,0);

}

public static function edit_data($id)
{

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $data = file_get_contents("php://input");

        $q ="UPDATE `option_sheme` SET `data` = ? WHERE `id`=".intval($id);
        Pdo_an::db_results_array($q,[$data]);

        !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
        Import::create_commit('', 'update', 'option_sheme', array('id' => intval($id)), 'option_sheme',6);

        header('Content-Type: application/json');
        echo json_encode(['message' => ' ok']);
    }

}

private static function get_from_db($id)
{
$q="SELECT * FROM `option_sheme` WHERE `id` = ".$id." limit 1";
$r = Pdo_an::db_results_array($q);
if ($r)
{
    return $r[0];
}


}

public static function run($id)
{

    $id=intval($id);

    $data = self::get_from_db($id);


    self::front($data);
    self::script($data);

}
public static function front($data)
{
    ///get arrays

    $name = $data['name'];
    $id = $data['id'];


    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
    $colors = OptionData::get_options('', 'sheme_colors');
    $options_color='';
    if ($colors) {
        $co = json_decode($colors);
        foreach ($co as $c => $v) {

            $root_css.= '--' . $c . ': ' . $v . ';' . PHP_EOL;
            $style.='option[value="'.$c.'"]{ background: '.$v.'!important;} '. PHP_EOL.'body .cube.color_'.$c.' .face{     background-color: '.$v.';}'.PHP_EOL;

        $options_color.='<option value="'.$c.'">'.$c.'</option>';
        }
    }

    $options_type='';
    $otype = OptionData::get_options('', 'sheme_types');
    if ($otype) {
        $otype = json_decode($otype,1);

        //{"Proccess":{"color":"green"},"Cron":{"color":"#B51E30"},"Function":{"color":"#369F1A"},"Database":{"color":"#1F407D"},"Hook":{"color":"#BC851F"}}

        foreach ($otype as $c => $v) {

            $options_type.='<option value="'.$c.'">'.$c.'</option>';

            $c_link = $c;
            if (strstr($c,' '))
            {
                $c_link = str_replace(' ','_',$c);
            }



            if ($v['color'])
            {
                $style.='body .cube.type_'.$c_link.' .face{   background-color: '.$v['color'].';}'.PHP_EOL;
            }

        }
    }




    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Sheme - <?php echo $name; ?></title>
    </head>
    <body class="hidden_left">


<div class="popup" id="popup">
    <div class="popup-header">
        <span class="close" id="closeBtn">&times;</span>
    </div>
    <div class="popup-content">

    </div>
</div>


<div class="left_menu">
<div class="menu_title"><?php echo $name; ?></div>
    <button class="add-button add_cube">Add Cube</button>
    <button class="add-button move_cube">Move Cube</button>
    <button class="add-button add-line-button">Add Line</button>
    <button class="add-button line-tw-button">Two ways</button>
    <div class="lines_container">
    <div class="menu_lines"></div>
    <div style="display: flex; gap:2px">

    <button class="add-button add-new-line-button">Add New Line</button>
    <button class="add-button delete-new-line-button">Delete</button>
    </div>
    </div>


    <div class="block_edit_menu">
        <p class="menu_title">Edit block</p>
        <div class="b_row">Id<span class="b_id"></span></div>
        <div class="b_row">Title <input class="b_title" ></div>
        <div class="b_row">Desc <textarea class="b_desc" ></textarea></div>
        <div class="b_row">Table <input class="b_table" ></div>
        <div class="b_row">Type <select class="b_type" ><?php echo $options_type; ?></select></div>
        <div class="b_row">Color <select class="b_color" ><?php echo $options_color; ?></select></div>


    </div>



    <button class="add-button save_button">Save data</button><span class="save_result"></span>


    <div class="hide_left_sidebar"><svg class="hide_left_sidebar_arrow" version="1.1" x="0px" y="0px" width="20px" height="20px" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve" fill="#5F6368"><path d="M8.59,16.59L13.17,12L8.59,7.41L10,6l6,6l-6,6L8.59,16.59z"></path><path fill="none" d="M0,0h24v24H0V0z"></path></svg></div>

</div>
<div class="main_scroll">
    <div class="main_win">


        <div class="iso">


<!--  <div id="chart-container" style="width: 100px; height: 100px;"></div>-->


            </div>


        </div>
</div>


    <?php

    if ($root_css)
        {
            echo '<style type="text/css">:root{'.$root_css.'}'.$style.'</style>';
        }

    }


    public static function script($data)
    {

        $object = $data['data'];
        $id = $data['id'];

        ?>


        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script type="text/javascript">

            var main_id =<?php echo $id ; ?>;

            <?php if ($object) {

                ?>
            var object_array = <?php echo $object ; ?>;

            <?php

            }
            else{
                ?>



            var object_array = {};
            object_array.cube = [];
            object_array.line_point = [];
            object_array.line= [];

            <?php
            }
            ?>

        </script>
        <script type="text/javascript" src="/service/js/sheme.js"></script>
        <link rel='stylesheet' href='/service/css/sheme.css' type='text/css' media='all' />
        </body>
        </html>
        <?php

    }



    }

    if (isset($_GET['edit_data']))
    {

        Sheme::edit_data($_GET['edit_data']);
    }
    else if (isset($_GET['edit_sheme']))
    {
        Sheme::run($_GET['edit_sheme']);

    }
    else
    {
        Sheme::table();

    }




    ?>


