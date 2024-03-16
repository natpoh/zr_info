<?php
class Compilation_link
{


    public static function get_array()
    {

        $and='';
            if (function_exists('current_user_can'))
            {
                $curent_user =current_user_can("administrator") ;

                if (!$curent_user) {$and = " and `show`  = 0 ";}
            }


    $q =" SELECT * FROM `meta_home_bloks` WHERE `enable`=1 ".$and." ORDER BY `weight` desc ";

    $r = Pdo_an::db_results_array($q);
    return $r;

    }
public static function get_home_blocks()
{

    $array_convert_default = [1=>'Audience',2=>'Pro',3=>'Video',4=>'TV',5=>'Games'];

    ///1:Last Audience;2:Last Critics;3:Last Video;4:Last TV;5:Last Games'
    $array_list_default = array(

        'Audience' => array('title' => 'Latest Audience Reviews:', 'id' => 'audience_scroll', 'class' => 'audience_review widthed ','tabs' => array('p' => 'Positive', 'n' => 'Negative', 'a' => 'Latest')),
        'Pro' => array( 'title' => 'Latest Critic Reviews:<span data-value="critics_reviews_popup" class="critic_popup nte_info nte_right"></span>', 'id' => 'review_scroll', 'class' => 'pro_review widthed secton_gray'),
        'Video' => array('title' => 'New Movies:', 'id' => 'video_scroll', 'class' => ''),
        'TV' => array('title' => 'Popular Shows Streaming:', 'id' => 'tv_scroll', 'class' => ''),
        'Games' => array('title' => 'New Games:', 'id' => 'games_scroll', 'class' => ''),

    );
   $array_data = self::get_array();
if (!$array_data) {
    $array_data = array(
        array(
            "id" => "1",
            "name" => "a",
            "title" => "",
            "type" => "1",
            "select_type" => "0",
            "sub_id" => "0",
            "sub_parent" => "",
            "exclude_parents" => "",
            "weight" => "20",
            "show" => "0",
            "enable" => "1"
        ),
        array(
            "id" => "2",
            "name" => "c",
            "title" => "",
            "type" => "2",
            "select_type" => "0",
            "sub_id" => "0",
            "sub_parent" => "",
            "exclude_parents" => "",
            "weight" => "19",
            "show" => "0",
            "enable" => "1"
        ),
        array(
            "id" => "3",
            "name" => "m",
            "title" => "",
            "type" => "3",
            "select_type" => "0",
            "sub_id" => "0",
            "sub_parent" => "",
            "exclude_parents" => "",
            "weight" => "18",
            "show" => "0",
            "enable" => "1"
        ),
        array(
            "id" => "4",
            "name" => "t",
            "title" => "",
            "type" => "4",
            "select_type" => "0",
            "sub_id" => "0",
            "sub_parent" => "",
            "exclude_parents" => "",
            "weight" => "17",
            "show" => "0",
            "enable" => "1"
        ),
        array(
            "id" => "5",
            "name" => "g",
            "title" => "",
            "type" => "5",
            "select_type" => "0",
            "sub_id" => "0",
            "sub_parent" => "",
            "exclude_parents" => "",
            "weight" => "16",
            "show" => "0",
            "enable" => "1"
        )
    );
}
    foreach ($array_data as $data)
    {
        $type = $data['type'];
        $sid = $data['id'];
        $title =  $data['title'];
       if  ($type==0)
       {
           ///custom
           $array_list['compilation_'.$sid]=Compilation_link::last_compilation_scroll($data);
       }
       else
       {
           $array_list[$array_convert_default[$type]] =  $array_list_default[$array_convert_default[$type]];
           if ($title)
           {
               $array_list[$array_convert_default[$type]]['title'] = $title;
           }
       }


    }

    return $array_list;
}


public static function last_compilation_scroll($data ='')
{
    $custom_select_type = $data['select_type'];
    $parent  = $data['sub_parent'];
    $sub_id  = $data['sub_id'];
    $exclude_parents  = $data['exclude_parents'];
    $parent_title =  $data['title'];


    $and='';

    if ($exclude_parents)
    {
        if (strstr($exclude_parents,','))
        {
            $ex_parents = explode(',',$exclude_parents);
            foreach ($ex_parents as $v)
            {
                $and_parents = "OR `parents`!= '".trim($v)."' ";
            }

            if ($and_parents)
            {
                $and.=  "AND (".substr($and_parents,2).") ";
            }
        }
    }

    $li='';

    $title_desc='';

    if ($sub_id)
    {
        $and.= " and `id` = '".$sub_id."' ";
    }
else if ($parent)
{
    $and.= " and `parents` = '".$parent."' ";
}

    $q = "SELECT * FROM `meta_compilation_links` WHERE `enable` = 1 ".$and." ORDER BY RAND() LIMIT 1";

    $r = Pdo_an::db_results_array($q);

    if (!$parent)
    {
        $parent = $r[0]['parents'];
    }


    $select_type= $r[0]['select_type'];
    $main_title_desc=$r[0]['description'];
    $main_title=$r[0]['title'];
    $main_id=$r[0]['id'];






    if ($parent)
    {
        $and = " and `parents` = '".$parent."' ";
    }
    else
    {
        $and = " and (`parents` IS NULL or `parents` = '')";
    }

if ($custom_select_type)
{
    $select_type =$custom_select_type-1;
}


$q = "SELECT * FROM `meta_compilation_links` WHERE `enable` = 1 ".$and."  ORDER BY CAST(`weight` AS UNSIGNED) DESC ";

$ru = Pdo_an::db_results_array($q);
$row_data=[];
foreach ($ru as $row)
{
    $title = $row['title'];
    $id = $row['id'];
    $li.='<li data-value="'.$id.'" data-desc="'.$row['description'].'">'.$title.'</li>';
    $row_data[$id]=['title'=>$title,'desc'=>$row['description']];
}




//    $randomKey = array_rand($ru);
//    $randomValue = $ru[$randomKey];
//    $title = $randomValue['title'];
//    $id = $randomValue['id'];
if ($select_type==0)
{
$content ='<div class="dropdown">
  <span class="dropdown_button"></span>
  <ul class="dropdown-content" id="myList" >
'.$li.'
  </ul>
</div>';
}
else if ($select_type==1)
{
    $content ='<span class="refresh_random">
  <div class="rr_image"></div>
<span style="display: none" class="rr_content">'.json_encode($row_data).'</span></span>';
}
else
{
    $content='';
}



    if ($parent_title)
    {
        $main_title= '<span class="main_pre_header">'.$parent_title.'</span><span class="block_title block_title_child">'.$main_title.':</span>'.$content;
    }
    else
    {
        $main_title='<span class="block_title">'.$main_title.':</span>'.$content;
    }

    if ($main_title_desc)
    {
        $main_title_desc = '<div class="title_block_desc">'.$main_title_desc.'</div>';
    }
    else
    {
        $main_title_desc='<div class="title_block_desc hide"></div>';
    }

    return array('title' => $main_title, 'title_desc' => $main_title_desc, 'id' => 'compilation_scroll_id_'.$main_id, 'class' => 'rand_scroll_'.$main_id);
}
}
