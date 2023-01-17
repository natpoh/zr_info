<?php
if (!isset($_GET['debug']))header('Content-Type: image/jpeg');

error_reporting('E_ERROR');
set_time_limit(0);
//ini_set('display_errors','On');

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';

//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
!class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';




$watermark_src = $_SERVER['DOCUMENT_ROOT'].'/analysis/images/mask.png';
$noimage_src = $_SERVER['DOCUMENT_ROOT'].'/analysis/images/noimage.png';



$id =$_GET['id'];

//echo $filename;

if (strstr($id,'_v'))
{
    $id = substr($id,0,strpos($id,'_v'));
}

if (strstr($id,'m_'))
{

    //Curl
    !class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

    function get_tmb_img_from_db($id){
        $image='';

        $sql ="SELECT `data` FROM `cache_tmdb_sinc` WHERE `rwt_id`={$id} and `type`=2 and `status`=1 limit 1";

        $rows = Pdo_an::db_fetch_row($sql);
        if ($rows)
        {
        $image_data = $rows->data;
        if ($image_data)
        {
            $image = 'https://image.tmdb.org/t/p/w1280'.$image_data;
        }
            return $image;
        }
    }

    function get_title_imdb($id){

        $sql ="select * from `data_movie_imdb` where  id = ".intval($id)." limit 1";
        $rows = Pdo_an::db_results_array($sql);
            return $rows[0];

    }
    function get_img_from_db($id){

    $sql ="select `data` from `data_movie_imdb` where  id = ".intval($id)." limit 1";
    $rows = Pdo_an::db_fetch_row($sql);
    if ($rows) {
        $image_data = $rows->data;
        if ($image_data) {
            $image_data = json_decode($image_data, 1);
            $image = $image_data['image'];
        }
        return $image;
    }
    }


    $id = substr($id,2);
    if (isset($_GET['debug']))echo 'id='.$id.'<br>';
    ////get poster
    $link =  get_tmb_img_from_db($id);
    if (isset($_GET['debug']))echo 'tmdb '.$link;

    if (!$link)
    {
        $link = get_img_from_db($id);
    }

    if (isset($_GET['debug']))echo 'imdb '.$link;

    if ($link)
    {
        $result = GETCURL::getCurlCookie($link);


        if ($result=='Not Found')
        {
         ///try get imdb images
            if (isset($_GET['debug']))echo 'result '.$result;
            $result='';
        }
    }

    if (!$result)
    {

        function split_string($string)
        {
            $maxlen= 0;
            $array_result = [];
            $len_widh=8;

            if (strstr($string,' '))
            {
                $array = explode(' ',$string);


            }
            else
            {
                $array[0]=   $string;
            }
            $len=0;
            $res_word='';
            foreach ($array as $words)
            {

                if ($len+strlen($words) > $len_widh) {

                    $array_result[]= trim($res_word);
                    $res_word='';
                    $len=0;

                }
                    $len+= strlen($words);
                    $res_word.=' '.$words;



                if ($len>$maxlen)
                {
                    $maxlen=$len;
                }

            }
            $array_result[]= trim($res_word);


            return ['max'=>$maxlen,'array'=>$array_result];


        }

        //add empty image
       $obj =  get_title_imdb($id);
        if ($obj){
            $title = $obj['title'];
            $year = $obj['year'];
        }
        if ($title) {


            $y  =100;
            $i_width  = 440;
            $i_height = 660;

            $string = $title;
            $pointsize = 34;
            $pointsize_year = 25;
            $fontfile = $_SERVER['DOCUMENT_ROOT'].'/analysis/HOLLYWOODSTARFIRE.ttf';
            $im = imagecreate($i_width, $i_height);
            $black = imagecolorallocate ($im, 0, 0, 0);
            $white = imagecolorallocate ($im, 255, 255, 255);

            $array_title = split_string($title);

            $y = 330-count($array_title['array'])*60;

            foreach ($array_title['array'] as $words)
            {
                $y+=60;

                $string_size = ImageFtBbox($pointsize, 0, $fontfile, $words, array("linespacing" => 1));
                $s_width  = $string_size[4];
                $s_height = $string_size[5];

                ImageFtText($im, $pointsize, 0, $i_width/2 - $s_width/2 - 1,  $y , $white, $fontfile, $words, array("linespacing" => 1));


            }
            if ($y<600 && $year)
            {
                $string_size = ImageFtBbox($pointsize_year, 0, $fontfile, $year, array("linespacing" => 1));
                $s_width  = $string_size[4];
                $s_height = $string_size[5];
                ImageFtText($im, $pointsize_year, 0, $i_width/2 - $s_width/2 - 1, 600, $white, $fontfile, $year, array("linespacing" => 1));

            }




            imagejpeg ($im);
            ImageDestroy ($im);
        return;
        }
        else
        {

            $file = ABSPATH.'/wp-content/themes/custom_twentysixteen/images/empty.jpg';
            $result = file_get_contents($file);
        }



    }

    if ($result)
    {
        if (!isset($_GET['debug']))echo $result;
    }


    return;
}



$array_compare_cache = array('Sadly, not'  => 'N/A','1' => 'N/A', '2' => 'N/A', 'NJW' => 'N/A','W' => 'White', 'B' => 'Black', 'EA' => 'Asian', 'H' => 'Latino', 'JW' => 'Jewish', 'I' => 'Indian', 'M' => 'Arab', 'MIX' => 'Mixed / Other', 'IND' => 'Indigenous');

$array_convert = array('2' => 'Male', '1' => 'Female', '0' => 'NA');

$array_type=array(
    "crowd"=>'CROWDSOURCE:' ,
    "jew"=>'JEWORNOTJEW:' ,
    "ethnic"=>'ETHNICELEBS:',
"kairos"=>'KAIROS FACIAL RECOGNITION:'  ,
"bettaface"=>'BETAFACE FACIAL RECOGNITION:'

,"familysearch"=>'FAMILYSEARCH:'
,"forebears"=>'FOREBEARS:',
"surname"=>'SURNAME ANALYSIS:'
 );

$id =intval($id);


$vd_data = unserialize(unserialize(OptionData::get_options('','critic_matic_settings')));
$verdict_method=0; if ($vd_data["an_verdict_type"]=='w'){$verdict_method=1;}

    $sql = "SELECT * FROM `data_actors_meta` where actor_id =" . $id . " ";
    $row = Pdo_an::db_results_array($sql);

    foreach ($row as $r) {

        if ($r['gender'])$gender = $array_convert[$r['gender']];


        if ($r['jew']) $result['jew'] = $r['jew'];
        if ($r['bettaface'] && $r['bettaface']!=2 && $r['bettaface']!=1) $result['bettaface'] = $r['bettaface'];
        if ($r['surname']) $result['surname'] = $r['surname'];
        if ($r['ethnic']) $result['ethnic'] = $r['ethnic'];
        if ($r['familysearch']) $result['familysearch'] = $r['familysearch'];
        if ($r['forebears']) $result['forebears'] = $r['forebears'];
        if ($r['kairos']) $result['kairos'] = $r['kairos'];
        if ($r['crowdsource']) $result['crowd'] = $r['crowdsource'];


        if ($verdict_method==1)
        {
            $verdict = $r['verdict_weight'];
        }
        if ($verdict_method==0 || !$verdict)
        {

            $verdict = $r['verdict'];
        }


    }
    if ($verdict)
    {
        $verdict = $array_compare_cache[$verdict];
    }

$verdict =strtoupper($verdict);
if (!$verdict)
{
    $verdict = 'N/A';
}



if ($gender) {
    if ($gender == 'Male') {
        $img_gender = $_SERVER['DOCUMENT_ROOT'] . '/analysis/images/mask-m.png';
    } else if ($gender =='Female') {
        $img_gender = $_SERVER['DOCUMENT_ROOT'] . '/analysis/images/mask-f.png';
    }
}


$count=0;
$dw=644;
$dh=640;

$im = ImageCreate ($dw, $dh)
or die ("error create image");

$maxwidth=200;

$couleur_fond = ImageColorAllocate ($im, 0, 0, 0);
$color = ImageColorAllocate ($im, 255, 255, 255);
$color_result = ImageColorAllocate ($im, 255, 255, 0);

ImageColorTransparent ($im, $couleur_fond);

$font = $_SERVER['DOCUMENT_ROOT'].'/analysis/8-bit pusab.ttf';

arsort($array_race);

$i=0;

$step=20;
$dist=30;

$start=160;
$x=100;

$fontsize = 10;


foreach ($array_type as $type=>$title)
{
    $data = $result[$type];

    if ($array_compare_cache[$data])
    {
        $data = $array_compare_cache[$data];
    }
    if (!$data)
    {
        $data = 'N/A';
    }
    $data =strtoupper($data);


    imagettftext($im, $fontsize, 0, $x, $start, $color, $font,$title );
    imagettftext($im, $fontsize, 0, $x, $start + $step, $color, $font, $data);
    $start += $dist + $step;

}


imagettftext($im, $fontsize, 0, 100, 564, $color, $font, 'VERDICT:');
imagettftext($im, 13, 0, 100, 593, $color_result, $font, $verdict);

///VERDICT:  WHITE, BRITISH

imagesavealpha($im, true);

$number = str_pad($id, 7, '0', STR_PAD_LEFT);


$imgsource =$_SERVER['DOCUMENT_ROOT'].'/analysis/img_final_tmdb/'.$number.'.jpg';

if (!file_exists($imgsource)) {
    $imgsource =$_SERVER['DOCUMENT_ROOT'].'/analysis/img_final/'.$number.'.jpg';
}
if (!file_exists($imgsource)) {
    $imgsource =$_SERVER['DOCUMENT_ROOT'].'/analysis/img_final_crowd/'.$number.'.jpg';
}



if (file_exists($imgsource)) {
   // echo "The file $imgsource exists";
    $image = imagecreatefromjpeg($imgsource);
    $sizeWM = getimagesize($imgsource);
    $heightWM = $sizeWM[1];
    $widthWM = $sizeWM[0];
    $heightWM=$widthWM;
    //   $heightWM=$heightWM*($dw/$widthWM);
    //   $heightWM=$heightWM/$dh;
    $imagebig = imagecreatetruecolor($dw, $dh);
    imagecopyresampled ($imagebig, $image, 0, 0, 0, 0, $dw, $dh, $widthWM, $heightWM);
} else {
    // echo "The file $imgsource does not exist";
    $imagebig = imagecreatefrompng($noimage_src);
}




$watermark = imagecreatefrompng($watermark_src);
imagesavealpha($watermark, true);




imagecopy(
    $imagebig, $watermark, 0, 0, 0, 0,
    $dw, $dh
);
imagecopy(
    $imagebig, $im, 0, 0, 0, 0,
    $dw, $dh
);

if ($img_gender)
{
///echo $img_gender;
    $img_gender_true = imagecreatefrompng($img_gender);
    imagesavealpha($img_gender_true, true);

    imagecopy(
        $imagebig, $img_gender_true, 0, 0, 0, 0,
        $dw, $dh
    );


}



imagejpeg($imagebig, $filename_ex);



imagejpeg($imagebig);

imagedestroy($image);
imagedestroy($im);
imagedestroy($watermark);

