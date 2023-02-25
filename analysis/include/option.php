<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';



class OptionData {

    public static function set_option($id=null, $option,$type=null,$create_comit='')
    {
        if (($option || $option==0) && ($id || $type)) {

            if (self::get_options($id,$type,1))
            {
                if ($id)
                {
                    $sql ="UPDATE `options` SET `val`=?,`type`=? WHERE `id`=?";
                    Pdo_an::db_results_array($sql,array($option,$type,$id));
                }
                else if ($type)
                {
                    $sql ="UPDATE `options` SET `val`=? WHERE `type`=?";
                    Pdo_an::db_results_array($sql,array($option,$type));

                    $id =self::get_id($type);

                }
            }
            else
            {
                if (!$id)
                {
                    $id=null;
                }

                $sql = "INSERT INTO `options`(`id`, `val`, `type`) VALUES (?,?,?)";
                $id =Pdo_an::db_insert_sql($sql,array($id,$option,$type));



            }


            if ($create_comit)
            {
                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'options', array('id' => $id), 'options',3);
            }

        }

    }

    public static function get_id($type)
    {

        $sql = "SELECT id FROM `options` where `type` = '{$type}'";
        $rows = Pdo_an::db_fetch_row($sql);
        $data = $rows->id;
        return $data;
    }

    public static function get_options($id=null,$type=null,$cheach_enable=null)
    {
        if ($id) {

            $sql = "SELECT val FROM `options` where id = " . $id;

        }
        else if ($type ) {

        $sql = "SELECT val FROM `options` where `type` = '{$type}'";

        }

        $rows = Pdo_an::db_fetch_row($sql);
        if ($cheach_enable)
        {
            if ($rows)
            {
                return  1;
            }
        }
        $data = $rows->val;
        return $data;
    }

}