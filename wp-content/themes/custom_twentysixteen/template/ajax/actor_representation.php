<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


function get_actors_full_representation()
{

    !class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';

    $ethnic =$_POST['ethnic'];

    if (isset($_POST['id'])) {

        $movie_id = $_POST['id'];
        $movie_id = intval($movie_id);

    }

    $ethnic_array = json_decode($ethnic,1);

    $actor_type = $ethnic_array['actor_type'];
    $ethnycity =  $ethnic_array['ethnycity'];


    if ((in_array('directors', $actor_type)) || !$actor_type) {

        if (!$actor_type)
        {
            $actor_type[] = 'star';
            $actor_type[] = 'main';
            $actor_type[] = 'extra';
        }

        $actor_type[] = 'director';
        $actor_type[] = 'writer';
        $actor_type[] = 'cast_director';
        $actor_type[] = 'producer';
        unset($actor_type[array_search('directors',$actor_type)]);


    }

    $actors_array=  MOVIE_DATA::get_actors_from_movie($movie_id,'',$actor_type);

    $array_movie_result = MOVIE_DATA::get_movie_data_from_db($movie_id, '', 0, $actor_type , $actors_array, "default", $ethnycity, 1 );

    echo $array_movie_result['current'];

   /// single_movie($ethnic_array,1);


return;

}


function get_actors_representation()
{

if (isset($_GET['id'])) {

            $movie_id = $_GET['id'];
            $movie_id = intval($movie_id);


            ////get search menu
?>

<div class="r_header">
    <div class="r_row">
<details class="dark actor_details">
    <summary>Setup</summary>
    <div ><?php  include ABSPATH.'analysis/include/template_control.php';  echo $data_Set; ?></div>
</details>
    </div>
            <div class="r_row">

                <?php
                $a = array('star'=>'Star','main'=>'Supporting','extra'=>'Other','directors'=>'Production');
                foreach ($a as $i=>$v)
                {
                    $ck='';
                    if ($i =='star' || $i =='main')
                    {
                        $ck='checked';
                    }

                    $c.='<div class="r_row_item big_checkbox"><input type="checkbox" '.$ck.' class="actor_type" id="'.$i.'" ><label for="'.$i.'">'.$v.'</label></div>';

                }
                echo $c;
                ?>

            </div>
</div>
<div class="r_content">

</div>
<?php

            include ABSPATH.'analysis/include/template_control.php';







        }


}

if (isset($_POST['ethnic'])) {

    get_actors_full_representation();
    return;

}
   get_actors_representation();




