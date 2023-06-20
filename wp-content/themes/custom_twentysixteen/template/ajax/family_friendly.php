<?php
error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
}
if ($id){




    $header = '<div class="column_header"><h2>Family friendly: <a href="#" data-value="'.$id.'" class="empty_ff_rating empty_ff_popup_rating">+add</a></h2></div>';


    ///get scroll



    !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
    ob_start();
    $array_family  = PgRatingCalculate::get_movie_desc($id);
    $inner_content=ob_get_contents();
    ob_clean();


    $title = PgRatingCalculate::get_data_in_movie('title', '', $id);
    $type = PgRatingCalculate::get_data_in_movie('type', '', $id);

    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
    $data =  OptionData::get_options('','pg_data');
    $data =  str_replace('\\','',$data);

    $data =str_replace('$',$title,$data);


    $result = ['rating'=>$array_family['rwt_pg_result'],'content'=>$inner_content,'other'=>$data,'type'=>$type];

    echo json_encode($result);

}