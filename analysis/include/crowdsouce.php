<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

if (!defined('CROWDSOURCEURL')) {

    global $site_url;
    //define('CROWDSOURCEURL', 'https://service.'.$_SERVER['HTTP_HOST'].'/crowdsource.php');
    define('CROWDSOURCEURL', $site_url.'service/crowdsource.php');
}



class Crowdsource
{

public static function get_search_block($content_input,$movie_block='',$class='')
{
    $content= '<div class="check_container_main">'.$movie_block.'</div>
' . $content_input . '<div class="crowd_items_search"><div class="advanced_search_menu crowd_items '.$class.'" style="display: none;">
                        <div class="advanced_search_first"></div>
                        <div class="advanced_search_data advanced_search_hidden"></div>
                    </div></div>';

    return $content;
}


    public static  function crop_text($text = '', $length = 10, $tchk = true) {
        if (strlen($text) > $length) {
            $pos = strpos($text, ' ', $length);
            if ($pos != null)
                $text = substr($text, 0, $pos);
            if ($tchk) {
                $text = $text . '...';
            }
        }
        return $text;
    }
    public static function checkpost()
    {
        if (isset($_POST['oper']))
        {
            if ($_POST['oper']=='update_crowd')
            {
                $request  = $_POST;

                if ($request['ids'] && $request['table'])
                {
                    $array = json_decode($request['ids'],1);

                    if ($request['fields'])
                    {
                        $fields = $request['fields'];
                    }


                    if ($request['action'] == 'wl' || $request['action'] == 'gl' || $request['action'] == 'bl' || $request['action'] == 'nl') {
                        // Move IP to list
                        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                            require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
                        }
                        $cfront = new CriticFront();
                        $cfront->cm->bulk_change_ip_list_type_crowd($array, $request['action'],$request['table']);

                    }
                    else
                    {

                        self::update_status($request['table'],$request['action'],$array,$fields );
                    }


                    return 1;
                }
            }
        }


    }
    public static function update_status($table,$request,$array,$fields='')
    {
        foreach ($array as $id)
        {
            if ($request =='trash')
            {
                $sql ="DELETE FROM `data_".$table."` WHERE `data_".$table."`.`id` = ".$id;

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'delete', "data_" . $table, array('id' => $id), 'crowsource',5);

            }
            else if (strstr ($request, 'critic_status_'))
            {
                $new_status =  str_replace('critic_status_', '', $request);
                $sql ="update data_".$table." set critic_status =".intval($new_status)." where id=".$id;

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', "data_" . $table, array('id' => $id), 'crowsource',5);

            }
            else if ($request=='change_column')
            {

                $set ='';
                foreach ($fields as $i=>$v)
                {
                    $set.=", `".$i."`  = '".$v."' ";


                }
                if ($set)
                {
                    $set = substr($set,2);
                    $sql ="update data_".$table." set ".$set." where id=".$id;
                    echo $sql;
                    !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                    Import::create_commit('', 'update', "data_" . $table, array('id' => $id), 'crowsource',12);

                }

            }
            else
            {
                $new_status = intval($request);
                // Change post status to trash
                if ($new_status==2){
                    
                    $sql = sprintf("SELECT review_id FROM data_".$table." WHERE id=%d", $id);
                    $review_id = Pdo_an::db_get_var($sql);
                    
                    if ($review_id){
                        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                            require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
                        }                        
                        $cm = new CriticMatic();
                        $cm->trash_post_by_id($review_id);
                    }
                } else if ($new_status==1){
                    $sql ="update data_".$table." set critic_status =0 where id=".$id;
                    Pdo_an::db_query($sql);
                }
                
                
                $sql ="update data_".$table." set status =".$new_status." where id=".$id;

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', "data_" . $table, array('id' => $id), 'crowsource',5);

                
            }

            Pdo_an::db_query($sql);
            if ($request==1)
            {
                ///rebuild cache
                self::rebuild_cache($id,$table);                
                
            }


        }

    }
    public static function get_movie_template_small($ma_id,$cfront=[])
    {

        $ma = $cfront->get_ma();
        $movie = $ma->get_post($ma_id);
        $movie_templ = $cfront->get_small_movie_templ($movie,1);
        return '<div class="custom_crowd_movie" data-value="'.$ma_id.'">'.$movie_templ.'</div>';
    }
    public static function get_movie_template($id,$ma_id,$cfront=[],$main='',$mstat='')
    {
        $ma = $cfront->get_ma();
        $movie = $ma->get_post($ma_id);
                    $movie_templ = $cfront->get_small_movie_templ($movie,1);

                    $movies_meta = $cfront->cm->get_movies_data($id,$ma_id);
                    $cur_rtype = $movies_meta[0]->type;
                    $cat_array =$cfront->cm->post_category;

        $cat_array[0]='Relevance';
        $cat_array[10]='Remove item';

        $array_replace = array(0=>'unset',1=>'proper',2=>'mention',3=>'related',10=>'remove');

        foreach ($cat_array as $i=>$v)
        {
            $selected='';
            if ($mstat)
            {
                if ($array_replace[$i]==$mstat)
                {
                    $selected=' selected ';
                    $cur_rtype = $i;
                }

            }
            else
            {
                if ($cur_rtype==$i)
                {
                    $selected=' selected ';
                }

            }

            $option.='<option '.$selected.' value="'.$array_replace[$i].'">'.$v.'</option>';

        }

        $select_type = '<select id="'.$array_replace[$cur_rtype].'" data-id="movie_link link_'.$ma_id.'" class="movie_link link_'.$ma_id.'">'.$option.'</select>';

        if ($main)
        {
            $main = ' main ';
        }

        $inner_content='<div id="'.$ma_id.'" class="check_inner_container '.$main.'">'.$movie_templ.'<div class="type">'.$select_type.'</div></div>';

    return $inner_content;

    }
    public static function  intconvert($data)
{
    $result=0;

    $array_int_convert = array('W'=>1,'EA'=>2,'H'=>3,'B'=>4,'I'=>5,'M'=>6,'MIX'=>7,'JW'=>8,'NJW'=>9,'IND'=>10);

    if ($array_int_convert[$data])
    {
        $result = $array_int_convert[$data];
    }

return $result;
}

    public static function rebuild_cache($id,$table)
    {
        if ($table=='movies_pg_crowd')
        {

            $sql = "select * from data_".$table." where id =".$id;
            $data =  Pdo_an::db_fetch_row($sql);
            if ($data->rwt_id)
            {
                $rwt_id = $data->rwt_id;
                $imdb_id =$data->movie_id;
            }


            !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
            PgRatingCalculate::CalculateRating($imdb_id,$rwt_id,0,1);
        }
        if ($table=='actors_crowd')
        {
            ///get_croudsurce data

            $sql = "select * from data_".$table." where id =".$id;
            $data =  Pdo_an::db_fetch_row($sql);
            if ($data->gender)
            {
                $gender = $data->gender;
            }
                $verdict = $data->verdict;
                $actor = $data->actor_id;

            if ($verdict)
            {
                $sql =" UPDATE `data_actors_meta` SET `crowdsource` = '".$verdict."' ,`n_crowdsource` = '".(self::intconvert($verdict))."' ,
                `last_update` = ".time()." WHERE `data_actors_meta`.`actor_id` =".$actor;
                Pdo_an::db_query($sql);


            }
            ///update gender
            if ($gender)
            {
                $array_gender = array('m'=>2,'f'=>1);

                $sql =" UPDATE `data_actors_meta` SET `gender` = '".$array_gender[$gender]."'  ,`last_update` = ".time()."  WHERE `data_actors_meta`.`actor_id` =".$actor;
                Pdo_an::db_query($sql);


            }
            if ($verdict || $gender)
            {
                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'data_actors_meta', array('actor_id' => $actor), 'actor_meta',9,['skip'=>['id']]);
            }

//            ///delete image cache
//
//                 $filename_ex  = ABSPATH.'analysis/img_result/'.$id.'_*.jpg';
//                 array_map("unlink", glob($filename_ex));

        }
        if ($table=='review_crowd'){

            $sql = "select * from data_".$table." where id =".$id;
            $data =  Pdo_an::db_fetch_row($sql);
            if ($data)
            {
                 if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                            require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
                        }
                $cm = new CriticMatic();
                $cm->submit_review_crowd($data);
            }
        }

    }

public static function get_user()
{

    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
    }

    $cfront = new CriticFront();


    $ca = $cfront->get_ca();
    $unic_id = $ca->unic_id();

    $aid_db = $ca->get_author_by_key($unic_id);


    $actor_pefix = 'Crowd_';
    if (!$aid_db)
    {

        ///get last crowd name

        $author_type = 3;
        $last_name = $cfront->cm->get_last_authors_name();
        if (!$last_name)
        {
            $last_num = 1;
        }
        else
        {
            $last_name_s =  $last_name[0]->name;
            $last_num = substr($last_name_s,6);

            if ($last_num)
            {
                $last_num++;
            }

        }

        $author_name  = $actor_pefix.$last_num;
        ///add actor to db

        $aid = $cfront->cm->get_or_create_author_by_name($author_name, $author_type);

       // echo ' add $aid='.$aid;
        if ($aid) {
            $ca->add_author_key($aid);

        }
    }
    else
    {
        $aid = $aid_db;
        $autors_array=   $cfront->cm->get_author($aid_db);
        $name = $autors_array->name;
    }

    return array('id'=>$aid,'name'=>$name);
}



public static function preparename($name)
{
    $name = ucfirst($name);

    return $name;
}
public static function setcol($name,$b,$desc,$style='',$title='',$star='')
{


    if ($title)
    {
        $name=  $title;
    }
    else
    {
        $name = self::preparename($name);
    }
    if ($name=='none')
    {
        $col_title='';
    }
    else
    {
     $col_title=   '<div class="col_title '.$star.'">'.$name.'</div>';
    }

    $col='<div class="row '.$style.'">'.$col_title.'<div class="col_input">'.$b.'<div class="col_desc">'.$desc.'</div></div></div>';

    return $col;
}

public static function get_new_draft($datatype)
{

        $sql="SELECT COUNT(*) as count FROM `{$datatype}` where status = 0";


        $r = Pdo_an::db_fetch_row($sql);
        return $r->count;

}

public static function front($datatype, $array_rows, $array_user = [], $id = '', $only_array = '', $inner_content = '')
{

    $array_value = [];

    if ($id && $array_user) {

        $user_id = $array_user['id'];

        if ($datatype == 'review_crowdsource') {
            $sql = "SELECT * FROM `data_review_crowd` where `user` = ? and `review_id` = ? limit 1";
        }
        if ($datatype == 'actor_crowdsource') {
            $sql = "SELECT * FROM `data_actors_crowd` where `user` = ? and `actor_id` = ? limit 1";
        } else if ($datatype == 'moviespgcrowd') {
            $sql = "SELECT * FROM `data_movies_pg_crowd` where `user` = ? and `rwt_id` = ? limit 1";
        }


        if ($sql) {
            $rw = Pdo_an::db_results_array($sql, array($user_id, $id));
            if ($rw[0]['id']) {
                $row = $rw[0];
                if ($row['status'] > 0 && !$array_user['admin']) {
                    ///return
                    $content = '<p class="user_message_info">You already left a comment.</p><div class="submit_data"><button class="button close" >Close</button></div>';

                    return $content;
                } else if ($row['status'] == 0 || $array_user['admin']) {

                    $array_value = $row;


                }

            }


        }
    }

    $content='<div class="form_msg"></div>';

    foreach ($array_rows as $name=>$value) {

            if ($value['type'])
            {

                $array_pt = array('p'=>'(Positive measure)', 'n'=>'(negative measure)');

              $pt = $value['pt'];
                $pt_cm='';
                $ptcls = '';
              if ($pt)
              {
                  $pt_cm = ' '.$array_pt[$pt];
                  $ptcls = ' ratng_'.$pt;
              }

                $title = $value['title'];
                $style = $value['style'];
                $class = $value['class'];
                $textval = $value['default_value'];
                if (!$textval)$textval='';

                $type = $value['type'];
                $desc = $value['desc'];
                $star = $value['star'];


                if ($type == 'checkbox') {


                    if ($array_value[$name]) {
                        $textval = 'checked="checked"';
                    }

                    $textarea = '<input type="checkbox" data-id="' . $name . '" id="' . $name . '" class="' . $name . $class . '" ' . $textval . ' />';

                    $content .= '<div class="row default_checkbox ' . $style . '">' . $textarea . '<label for="' . $name . '" class="col_desc">' . $desc . '</label></div>';

                }

                if ($type == 'big_checkbox') {


                    if ($array_value[$name]) {
                        $textval = 'checked="checked"';
                        $display = 'style="display:block;"';
                    }

                    $textarea = '<input type="checkbox" data-id="' . $name . '"  id="' . $name . '" class="' . $name . $class . '" ' . $textval . ' />';

                    $content .= '<div class="big_checkbox ' . $style . '">' . $textarea . '<label for="' . $name . '" class="col_desc">' . $desc . '</label><div class="check_container" '.$display.'>' . $inner_content . '</div></div>';

                }

                if ($type == 'radio') {
                    $option = '';
                    foreach ($value['options'] as $i => $v) {

                        $selected = '';
                        if ($array_value[$name] == $i) {
                            $selected = ' selected ';
                            if ($ptcls)
                            {
                                $select_id = ' id="'.$i.'" ';
                            }
                        }

                        $option.='<div class="radio_block"><input data-id="' . $name . '" type="radio" id="'.$i.'"
     name="'.$name.'" value="'.$i.'" class="radio '.$name.'">
    <label for="'.$i.'">'.$v.'</label></div>';

                    }
                    if ($pt) {
                        $name = $name . $pt_cm;
                        $style = $style.$ptcls;
                    }


                    $content.= self::setcol($name,$option,$desc,$style,$title,$star);

                }
              if ($type=='select')
              {
                  $option='';
                  $select_id='';
                  foreach ($value['options'] as $i=>$v)
                  {
                      $selected='';
                      if ($array_value[$name]==$i)
                      {
                          $selected=' selected ';
                          if ($ptcls)
                          {
                              $select_id = ' id="'.$i.'" ';
                          }
                      }
                      $option.='<option '.$selected.' value="'.$i.'">'.$v.'</option>';

                  }

                  $select = '<select data-id="' . $name . '" '.$select_id.' class="'.$name.'">'.$option.'</select>';
                  if ($pt) {
                      $name = $name . $pt_cm;
                      $style = $style.$ptcls;
                  }

                  $content.= self::setcol($name,$select,$desc,$style,$title,$star);
              }
                if ($type=='textarea')
                {

                    if ($array_value[$name])
                    {
                       $textval= $array_value[$name];
                    }
                    $textarea = '<textarea data-id="' . $name . '"  class="'.$name.'"  placeholder="'.$value['placeholer'].'">'.$textval.'</textarea>';
                    $content.= self::setcol($name,$textarea,$desc,$style,$title,$star);
                }
                if ($type=='input')
                {

                    if ($array_value[$name])
                    {
                        $textval= $array_value[$name];
                    }

                    $textarea = '<input data-id="' . $name . '" class="' . $name . $class . '" value="' . $textval . '" placeholder="' . $value['placeholer'] . '" >';
                    $content .= self::setcol($name, $textarea, $desc, $style, $title,$star);
                }
                if ($type=='disabled')
                {

                    if ($array_value[$name])
                    {
                        $textval= $array_value[$name];
                    }

                    $textarea = '<input data-id="' . $name . '" class="' . $name . $class . '" value="' . $textval . '" disabled="disabled" placeholder="' . $value['placeholer'] . '" >';
                    $content .= self::setcol($name, $textarea, $desc, $style, $title,$star);
                }
                if ($type=='hidden')
                {

                    if ($array_value[$name])
                    {
                        $textval= $array_value[$name];
                    }

                    $textarea = '<input data-id="' . $name . '" name="'.$name.'" class="' . $name . $class . '" value="' . $textval . '" type="hidden" >';
                    $style = 'hidden';
                    $content .= self::setcol('', $textarea, $desc, $style, '');
                }
                if ($type=='html')
                {

                    if ($array_value[$name])
                    {
                        $textval= $array_value[$name];
                    }

                    $textarea = '<div data-id="' . $name . '" class="' . $name . $class . ' input_content" >'.$textval.'</div>';
                    $content .= self::setcol($name, $textarea, $desc, $style, $title,$star);
                }
                if ($type=='wpform')
                {

                    if ($array_value[$name])
                    {
                        $textval= $array_value[$name];
                    }
                    
                    ob_start();?>
                            <div id="wp-id_crowd_text-wrap" class="wp-core-ui wp-editor-wrap tmce-active">                                    
                                <div id="wp-id_crowd_text-editor-tools" class="wp-editor-tools hide-if-no-js">
                                    <div class="wp-editor-tabs">
                                        <button type="button" id="id_crowd_text-tmce" class="wp-switch-editor switch-tmce" data-wp-editor-id="id_crowd_text">Visual</button>
                                        <button type="button" id="id_crowd_text-html" class="wp-switch-editor switch-html" data-wp-editor-id="id_crowd_text">Text</button>
                                    </div>
                                </div>
                                <div id="wp-id_crowd_text-editor-container" class="wp-editor-container">
                                    <div id="qt_id_crowd_text_toolbar" class="quicktags-toolbar"></div>
                                    <textarea data-id="<?php print $name ?>" class="wp-editor-area wpcr3_required <?php print $class ?>" rows="20" autocomplete="off" cols="40" name="<?php print $name?>" id="id_crowd_text"><?php
                                        print $textval;
                                        ?></textarea>
                                </div>
                            </div>
                    <?php
                    $textarea = ob_get_contents();
                    ob_end_clean();
                    
                    $content .= self::setcol($name, $textarea, $desc, $style, $title,$star);
                }
            }

    }

    if (!$only_array) {
        $content .= '<div class="submit_data"><button id="' . $datatype . '" class="button submit_user_data" >Submit</button><button class="button close" >Close</button></div>';


    }


    return $content;

}

    public static function remove_quotes($data_Res,$addsplash='')
    {
        $data_Res = trim($data_Res);
        $data_Res=str_replace("'",'',$data_Res);

        if ($addsplash) $data_Res=str_replace("/",'\/',$data_Res);

        $data_Res=str_replace(PHP_EOL,'',$data_Res);
       return $data_Res;
    }

public static function get_count_status($datatype,$custom_table='')
{

    $array = array(0,1,2);
      $array_status = [];

    foreach ($array as $status)
    {
        if ($custom_table)
        {
            $sql = "SELECT COUNT(*) c FROM `".$custom_table."` WHERE status =".$status  ;
        }
        else
        {
            $sql = "SELECT COUNT(*) c FROM `data_".$datatype."` WHERE status =".$status  ;
        }


        $rows = Pdo_an::db_fetch_row($sql);
        $count = $rows->c;
        $array_status['All']+=$count;
        $array_status[$status]=$count;

    }
    return $array_status;

}

public static function prepare_array($options,$addsplash='')
{
    $options = str_replace('\\','',$options);
    $option_array = explode(',',$options);
    $array_rs=[];

    foreach ($option_array as $val)
    {
        if ($val)
        {
            $option_array_inner = explode('=>',$val);
            $index = trim($option_array_inner[0]);
            $data = trim($option_array_inner[1]);
            $index  =self::remove_quotes($index);
            $data  =self::remove_quotes($data,$addsplash);
            $array_rs[$index]=$data;
        }
    }
    return $array_rs;
}




public static function Show_admin_table($datatype,$array_rows,$WP_include,$custom_table='',$refresh_rating='',$no_status='',$no_subgrid='',$edit =1,$select=1)
{






    if ($custom_table)
    {
        $sql = "SHOW COLUMNS FROM ".$custom_table;
        $doptable='&doptable='.$custom_table;

    }
    else
    {
        $sql = "SHOW COLUMNS FROM data_".$datatype;
        $doptable='';
    }

    $rows = Pdo_an::db_results_array($sql);
    foreach ($rows as $r)
    {
        $name = $r["Field"];

        $width=10;
        $edittipe='';
        $edittable = 'true';
        $hidden ='';

        if ($array_rows[$name])
        {
            if ($array_rows[$name]['w'])
            {
                $width=$array_rows[$name]['w'];
            }

            if ($array_rows[$name]['type'])
            {
               $type = $array_rows[$name]['type'];
               if ($type=='select')
               {
                   $edittipe = 'edittype:"select", formatter:"select",editoptions:{value:"'.$array_rows[$name]['options'].'"},stype:"select", searchoptions:{value:"All:All;'.$array_rows[$name]['options'].'"}';

               }
                else if ($type=='textarea')
                {
                    $trw=3;
                    if ($array_rows[$name]['textarea_rows'])
                    {
                        $trw= $array_rows[$name]['textarea_rows'];
                    }

                    $edittipe = 'edittype:"textarea",editoptions: {  rows: '.$trw.',   cols: 40,  wrap: "off" }';
                }
              }
            if ($array_rows[$name]['hidden'])
            {
              $hidden =  ' hidden:true, ';
            }
            if ($array_rows[$name]['editfalse'])
            {
                $edittable='false';
            }
        }



        if ($name=='id' || $name=='add_time' )
        {
            $edittable='false';
        }

        $colums.="      {   label : '".$name."',
                        name: '".$name."',
                        key: true,
                        width: ".$width.",
                        editable:".$edittable.",
                        ".$hidden."
                        ".$edittipe."

                    },";


    }


    $link =$_SERVER['REQUEST_URI'];// "/wp-admin/admin.php?page=crowdsource_".$datatype;
    //get counts
    $table_staus ='';
    $array_status = self::get_count_status($datatype,$custom_table);
    //array_unshift($array_status , 'all');
    $array_status_name = array('All'=>'All',0=>'Waiting to check',1=>'Approved',2=>'Rejected');

    $curstat='All';
    if (isset($_GET['status']))
    {
        $curstat = $_GET['status'];

    }


    foreach ($array_status as $status=>$scount)
    {
        $class='';
        if (strval($curstat)==strval($status ) )
        {
            $class='class="current"';
        }

        if (!$scount)$scount=0;
        $table_staus.='| <li><a id="'.$status.'" href="'.$link.'&amp;status='.$status.'" '.$class.'>'.$array_status_name[$status].' <span class="count">('.$scount.')</span></a></li>';

    }
    if ($table_staus)
    {
        $table_staus = substr($table_staus,2);
    }




    $home_url=  site_url().'/';
?>
    <script type="text/ecmascript" src="<?php echo $home_url ?>analysis/js/jquery.min.js"></script>
<script type="text/ecmascript" src="<?php echo $home_url ?>analysis/jqgrid/js/i18n/grid.locale-en.js"></script>
<!-- This is the Javascript file of jqGrid -->
<script type="text/ecmascript" src="<?php echo $home_url ?>analysis/jqgrid/js/jquery.jqGrid.min.js"></script>
<script>
    jQuery.jgrid.defaults.responsive = true;
    jQuery.jgrid.defaults.styleUI = 'Bootstrap';
</script>
<script src="https://code.highcharts.com/stock/highstock.js"></script>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $home_url ?>analysis/jqgrid/css/ui.jqgrid-bootstrap4.css" />
<link rel="stylesheet" href="<?php echo $home_url.'wp-content/themes/custom_twentysixteen/css/movie_single.css?'.LASTVERSION ?>">
<link rel="stylesheet" href="<?php echo $home_url.'wp-content/themes/custom_twentysixteen/css/colums_template.css?'.LASTVERSION ?>">
<script type="text/javascript">
var first_run = 0;


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
    function getSubgrid(subgrid_id, row_id){

        ////check select grig

        var grid = jQuery('.header_menu a.selected').html();

        var data_type ='<?php echo $datatype; ?>';


        var actor_id  = jQuery("#jqGrid").jqGrid('getCell',row_id,'actor_id');
            var movie  = jQuery("#jqGrid").jqGrid('getCell',row_id,'movie_id');
            var review_id  = jQuery("#jqGrid").jqGrid('getCell',row_id,'id');


        if (data_type =='movies_pg_crowd')
        {

            jQuery.ajax({
                type: "POST",
                url: "<?php echo $home_url ?>analysis/get_data.php",

                data: ({
                    oper: 'movie_data',
                    id: movie,
                    refresh_rating:	'<?php echo $refresh_rating ?>'
                }),
                success: function (html) {
                    jQuery('#'+subgrid_id).html(html);
                }
            });
        }



        else if (data_type =='woke')
        {

            jQuery.ajax({
                type: "POST",
                url: "<?php echo $home_url ?>analysis/get_data.php",

                data: ({

                    oper: 'movie_data',
                    rwt_id: jQuery("#jqGrid").jqGrid('getCell',row_id,'mid'),
                    woke:	1

                }),
                success: function (html) {
                    jQuery('#'+subgrid_id).html(html);
                }
            });
        }

        else if (data_type =='actors_crowd' || data_type =='actors_log') {
            /// console.log(actor_id);
            ///jQuery('#'+subgrid_id).html('<img src="create_image.php?id='+actor_id+'&nocache=1" />');
            jQuery.ajax({
                type: "POST",
                url: "<?php echo $home_url ?>analysis/get_data.php",
                data: ({
                    oper: 'get_actordata',
                    id: actor_id

                }),
                success: function (html) {
                    jQuery('#'+subgrid_id).html(html);
                }
            });
        }
        else if (data_type =='critic_crowd')
        {
            var re = /(\<[^\>]+\>.([0-9]+))/;
            review_id = review_id.replace(re, "$2");

            jQuery.ajax({
                type: "POST",
                url: "<?php echo CROWDSOURCEURL ?>",

                data: ({
                    oper: 'crowd_submit',
                    type:'critic_crowd_link',
                    'admin_view': review_id,
                }),
                success: function (html) {

                    var obj = JSON.parse(html);
                    var data = obj.critic_data;


                    jQuery('#'+subgrid_id).html(data);
                    //add link to sritic matic
                    var td = jQuery('td[title="'+review_id+'"][aria-describedby="jqGrid_id"]');
                   // var prnt = td.parents('tr.jqgrow');
                   // var id = prnt.find('td[aria-describedby="jqGrid_review_id"]').html();

                    jQuery('#'+subgrid_id+' .submit_data').remove();


                   // jQuery('h2.r_info').after('<a href="<?php echo $home_url ?>wp-admin/admin.php?page=critic_matic&pid='+id+'"> View Review in "Critic Matic"</a>');
                }
            });

        }

        else if (data_type =='review_crowd')
        {
            var re = /(\<[^\>]+\>.([0-9]+))/;
            review_id = review_id.replace(re, "$2");

            jQuery.ajax({
                type: "POST",
                url: "<?php echo CROWDSOURCEURL ?>",

                data: ({
                    oper: 'review_crowd',
                    'admin_view': review_id,
                }),
                success: function (html) {
                    jQuery('#'+subgrid_id).html(html);
                    //add link to sritic matic
                    var td = jQuery('td[title="'+review_id+'"][aria-describedby="jqGrid_id"]');
                    var prnt = td.parents('tr.jqgrow');
                    var id = prnt.find('td[aria-describedby="jqGrid_review_id"]').html();


                    jQuery('h2.r_info').after('<a href="<?php echo $home_url ?>wp-admin/admin.php?page=critic_matic&pid='+id+'"> View Review in "Critic Matic"</a>');
                }
            });

        }
    }
    //
    // function check_request(data)
    // {
    //     console.log(data);
    // }

    jQuery(document).ready(function () {
        jQuery("#jqGrid").jqGrid({
            url: '<?php echo $home_url ?>analysis/jqgrid/get.php?data=<?php echo $datatype.$doptable ?>',
            mtype: "POST",
            datatype: "json",
            page: 1,
            colModel: [
                <?php echo $colums; ?>
            ],

            <?php if ($WP_include) { ?>

            editurl: '<?php echo $home_url ?>analysis/jqgrid/get.php?data=<?php echo $datatype.$doptable; ?>',

            <?php } ?>
            sortorder : "desc",
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
            gridview : false,

            <?php if ($select==1)  {    ?>


            multiselect: true,


            <?php }   ?>



            beforeRequest:function(){


                if (typeof check_request=='function')
                {

                    check_request();
                }

                var status='All'
                if (first_run==0) {

                    <?php if (isset($_GET['status']))
                    {
                    echo "var status = '".$_GET['status']."';";

                    ?>

                    if (status!='All')
                    {
                        var data = jQuery("#jqGrid").jqGrid("getGridParam", "postData");
                        data._search = 'false';
                        data.search = 'false';
                        data.status = status;
                        jQuery("#jqGrid").jqGrid("setGridParam", {"postData": data});
                    }


                    <?php
                    } ?>
                    first_run=1;


                    //   jQuery("#jqGrid").trigger("reloadGrid");
                }
            },
            afterInsertRow : function( row_id, rowdata, rawdata) {

                var data_type = '<?php echo  $datatype; ?>';




                if (data_type =='critic_crowd')
                {

                    if (rowdata.review_id && rowdata.review_id!=0) {
                        $('#jqGrid').jqGrid('setCell', row_id, 'review_id', '<a target="_blank" href="/wp-admin/admin.php?page=critic_matic&pid='+rowdata.review_id+'">'+rowdata.review_id+'</a>', {'color': 'blue'});
                    }
                    if (rowdata.critic_id && rowdata.critic_id!=0) {
                        $('#jqGrid').jqGrid('setCell', row_id, 'critic_name', '<a target="_blank" href="/wp-admin/admin.php?page=critic_matic_authors&aid='+rowdata.critic_id+'">'+rowdata.critic_name+'</a>', {'color': 'blue'});
                    }


                }

                if (rowdata.link) {
                    $('#jqGrid').jqGrid('setCell', row_id, 'link', '<a target="_blank" href="'+rowdata.link+'">'+rowdata.link+'</a>', {'color': 'blue'});
                }
                if (rowdata.image) {
                    $('#jqGrid').jqGrid('setCell', row_id, 'image', '<a target="_blank" href="'+rowdata.image+'"><img style="height: 100px;" src="'+rowdata.image+'"></a>', {'color': 'blue'});
                }
            },


            <?php if(!$no_subgrid) { ?>
            subGrid: true,
            subGridRowExpanded: function(subgrid_id, row_id) {
                getSubgrid(subgrid_id, row_id);
            },
            <?php } ?>


        });
        // activate the toolbar searching

        jQuery('#jqGrid').jqGrid('filterToolbar', {stringResult: true, searchOnEnter: false, defaultSearch: 'cn', ignoreCase: true});

        jQuery('#jqGrid').jqGrid('navGrid',"#jqGridPager", {
                search: true, // show search button on the toolbar

                <?php if ($WP_include && $edit) { ?>
                add: true,
                edit: true,
                del: true,


                <?php }  else  { ?>
                add: false,
                edit: false,
                del: false,

                <?php }   ?>
                refresh: true
            },
            {
                beforeShowForm: function(formID) {


                    jQuery('.DataTD textarea, .DataTD input').each(function(){

                        let vl = jQuery(this).val();


                       // vl = vl.replace(/\\+/g, '\\');

                        vl = vl.replace(/"\\"/g, '"');
                        vl = vl.replace(/\\""/g, '"');

                        jQuery(this).val(vl);

                    });


                    let link =  jQuery('.DataTD input[id="link"]').val();

                    if (link)
                    {
                        var re = /(\<a[^\>]+\>([^\<]+)\<\/a\>)/;
                        var newstr = link.replace(re, "$2");
                        if (newstr)link = newstr;

                        jQuery('.DataTD input[id="link"]').val(link)
                    }



                    let image =  jQuery('.DataTD input[id="image"]').val();

                    if (image)
                    {
                        var re = /(\<a[^\>]+\>.+src\=\"([^\"]+).+)/;
                        newstr = image.replace(re, "$2");
                        if (newstr)image = newstr;

                        jQuery('.DataTD input[id="image"]').val(image)
                    }




                   /// console.log(link);
                },

                onclickSubmit: function () {
                    setTimeout(function () {
                      jQuery('#edithdjqGrid .ui-jqdialog-titlebar-close').click();
                    },500);
                    var id  = jQuery('tr.success td[aria-describedby="jqGrid_id"]').html();

                    return {parent:id};
                },
            },
            {

                onclickSubmit: function () {
                    var id  = jQuery('tr.success td[aria-describedby="jqGrid_id"]').html();

                    setTimeout(function () {
                        jQuery('#edithdjqGrid .ui-jqdialog-titlebar-close').click();
                    },500);
                    return {parent:id};
                },
            },
            {
                onclickSubmit: function () {

                    var id  = jQuery('tr.success td[aria-describedby="jqGrid_id"]').html();

                    setTimeout(function () {
                        jQuery('#edithdjqGrid .ui-jqdialog-titlebar-close').click();
                    },500);

                    return {parent:id};




                },
            }

        );

        jQuery(".cm-filters a").click( function(e) {
            e.preventDefault();

            jQuery(".cm-filters a").removeClass('current');
            var id =jQuery(this).attr('id');
            jQuery(this).addClass('current');
            jQuery('select[id="gs_status"]').val(id).change();


            // var data = jQuery("#jqGrid").jqGrid("getGridParam", "postData");
            // data._search = 'false';
            // data.search = 'false';
            // data.status = id;
            // jQuery("#jqGrid").jqGrid("setGridParam", { "postData": data });
            // jQuery("#jqGrid").trigger("reloadGrid");
            let href='<?php echo ($_SERVER['REQUEST_URI']) ?>&status='+id;
            history.pushState({path: href}, '', href);

            return false;
            });


        jQuery('.update_crowd').click(function(){
            var thiss = jQuery(this);
            var table =thiss.attr('data-value');
            var action =jQuery('.bulk-actions').val();



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
                        action:action

                    }),
                    success: function () {
                        thiss.attr('disabled',false);
                        jQuery('#refresh_jqGrid .glyphicon-refresh').click();

                    }
                });
            }

        });



        <?php if (isset($_GET['status']))
        {
        echo "jQuery('select[id=\"gs_status\"]').val('".$_GET['status']."').change();";

        } ?>


    });


</script>

    <?php if (!$no_status) { ?>
    <ul class="cm-filters subsubsub"><li>Status: </li><?php echo $table_staus; ?></ul>

<?php
}

    if (!$custom_table)
    {
    ?>


    <div class="bulk-actions-holder">
        <select autocomplete="off" name="bulkaction" class="bulk-actions">
            <option value="none">Bulk actions</option>
            <option value="1">Approved</option>
            <option value="0">Waiting to check</option>
            <option value="2">Rejected</option>
            <option value="critic_status_0">Waiting critic_status</option>
            <option value="trash">Delete</option>
                <option value="wl">IP to White list</option>
                <option value="gl">IP to Gray list</option>
                <option value="bl">IP to Black list</option>
                <option value="nl">Remove IP from list</option>

        </select>
        <input type="submit" id="edit-submit" data-value="<?php echo $datatype ?>" value="Submit" class="update_crowd button-primary">
    </div>

    <?php
    }

    ?>

<table id="jqGrid"></table>
<div id="jqGridPager"></div>
<style type="text/css">

  input.cbox[type="checkbox"] {
        height: 20px;
        width: 20px;
    }

  input.cbox[type="checkbox"]:checked::before  {
        width: 20px;
        height: 21px;
    }
  #jqGrid iframe{
      max-height: 100px;
  }

</style>
<?php
}

}