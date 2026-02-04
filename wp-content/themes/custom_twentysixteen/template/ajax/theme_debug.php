<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


if (!function_exists('get_option')) {
    function get_option_a($id)
    {
        global $table_prefix;

        $sql ="SELECT option_value FROM ".$table_prefix."options WHERE option_name = ? LIMIT 1";
        $r = Pdo_wp::db_results_array($sql,[$id]);

        return $r[0]['option_value'];
    }
}

if (isset($_POST['data']))
{

   $time =intval($_POST['data']);

  $last_time =  get_option_a('theme_colors_debug');

  if ($last_time!=$time){

      echo json_encode(['result'=>$last_time,'update'=>1,'t'=>$last_time,'lt'=>$time]);
  }
  else
  {
      echo json_encode(['result'=>'','update'=>0,'t'=>$last_time,'lt'=>$time]);
  }



}