<?php
error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
class Gsearch{

    public  function get_title($id)
    {

        $sql = "SELECT * from data_movie_imdb where id =".intval($id);
        $r = Pdo_an::db_results_array($sql);
        $title = $r[0]['title'];
        $type = $r[0]['type'];
        return [$title,$type];

    }
    private function check_anime($mid)
    {
        $q ="SELECT id FROM `meta_movie_genre` WHERE `mid` = ".$mid." and `gid` = 29 limit 1";
        $r = Pdo_an::db_results_array($q);
        if ($r)return 'anime';

    }


    private function get_tabs($id,$type,$title)
    {
        $content_type_str = OptionData::get_options('','zr_content_type');
        if ($content_type_str)$content_type = json_decode($content_type_str,1);


        $title_coded = urlencode($title);

        $type =strtolower($type);

        $result = [];

        $genre = $this->check_anime($id);

        $q ="SELECT * FROM `options_search_tabs`";
        $r = Pdo_an::db_results_array($q);
        foreach ( $r as $row)
        {
            $m_type = $row['type'];
            $array_types = $content_type[$m_type]['type'];
            $array_genres = $content_type[$m_type]['genre'];

            if (($array_types && in_array($type,$array_types)) || ($genre && $array_genres && in_array($genre,$array_genres)))
            {

                if ($row['link'])
                {
                    $row['link'] = str_replace('$',$title_coded,$row['link']);
                }

                if ($row['dop_content'])
                {
                    $row['dop_content'] = str_replace('$',$title_coded,$row['dop_content']);
                }


                if ($row['content_type']==1)//iframe
                {
                    $row['script'] = str_replace('$',$title_coded,$row['script']);
                }


                if ($row['sub_from']==0)
                {
                    $result[$row['id']]['data'] =  $row;

                }
                else
                {
                    $result[$row['sub_from']]['sub'][$row['id']]['data'] =  $row;

                }


            }



        }

        return $result;
    }


    public  function get_data($id)
    {

       [$title,$type] = $this->get_title($id);

       $result['data'] = $this->get_tabs($id,$type,$title);
       $result['title']=$title;
       $result['type']=strtolower($type);

       return json_encode($result);

    }


}





if (isset($_GET['id'])) {
    $movie_id = intval($_GET['id']);
    $Gsearch = new Gsearch();
    $result = $Gsearch->get_data($movie_id);
    echo ($result);

}

