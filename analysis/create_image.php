<?php
error_reporting('E_ERROR');
set_time_limit(0);
ini_set('display_errors','On');

$watermark_src = $_SERVER['DOCUMENT_ROOT'].'/analysis/images/mask.png';
$noimage_src = $_SERVER['DOCUMENT_ROOT'].'/analysis/images/noimage.png';

$id =$_GET['id'];

//echo $filename;

global $array_exclude;

function wph_cut_by_words($maxlen, $text) {
    $len = (mb_strlen($text) > $maxlen)? mb_strripos(mb_substr($text, 0, $maxlen), ' ') : $maxlen;
    $cutStr = mb_substr($text, 0, $len);
    $temp = (mb_strlen($text) > $maxlen)? $cutStr : $cutStr;
    return $temp;
}

$array_convert_type = array('crowd' => 'crowd','ethnic' => 'ethnic', 'jew' => 'jew', 'face' => 'kairos', 'face2' => 'bettaface', 'surname' => 'surname');

$e = $_GET['e'];
if ($e)
{
    $e = json_decode(urldecode($e) );
}
if (!$e)
{
    $e = json_decode('{"1":{"crowd":1},"2":{"ethnic":1},"3":{"jew":1},"4":{"face":1},"5":{"face2":1},"6":{"surname":1} }');
}


$filename = $id.'_'.md5($_GET['e']);
$filename_ex  = $_SERVER['DOCUMENT_ROOT'].'/analysis/img_result/'.$filename.'.jpg';

if (!isset($_GET['nocache'])) {

    if (file_exists($filename_ex)) {
        header('Content-Type: image/jpeg');

        $cache = 86400 * 7;

    if (time() - filemtime($filename_ex) > $cache) {
        unlink($filename_ex);

    }
    else
    {
        echo file_get_contents($filename_ex);
        return;

    }
    }
}



foreach ($e as $order => $data) {
    foreach ($data as $typeb => $enable) {
        if ($enable) {
            $ethnic_sort[$array_convert_type[$typeb]] = [];
        }
    }
}


$array_compare_cache = array('Sadly, not'  => 'N/A','1' => 'N/A', '2' => 'N/A', 'NJW' => 'N/A','W' => 'White', 'B' => 'Black', 'EA' => 'Asian', 'H' => 'Latino', 'JW' => 'Jewish', 'I' => 'Indian', 'M' => 'Arab', 'MIX' => 'Mixed / Other', 'IND' => 'Indigenous');


$array_convert = array('2' => 'Male', '1' => 'Female', '0' => 'NA');

$array_type=array( "crowd"=>'CROWDSOURCE:' , "jew"=>'JEWORNOTJEW:' ,"ethnic"=>'ETHNICELEBS:',"surname"=>'SURNAME ANALYSIS:',"bettaface"=>'BETAFACE FACIAL RECOGNITION:',"kairos"=>'KAIROS FACIAL RECOGNITION:');

$id =intval($id);

include 'db_config.php';
global $pdo;
pdoconnect_db();



    $sql = "SELECT * FROM `data_actors_meta` where actor_id =" . $id . " ";
    $q = $pdo->prepare($sql);
    $q->execute();
    while ($r = $q->fetch()) {
        if ($r['gender'])$gender = $array_convert[$r['gender']];
        if ($r['jew']) $ethnic['jew'][$r['actor_id']] = $r['jew'];
        if ($r['bettaface'] && $r['bettaface']!=2 && $r['bettaface']!=1) $ethnic['bettaface'][$r['actor_id']] = $r['bettaface'];
        if ($r['surname']) $ethnic['surname'][$r['actor_id']] = $r['surname'];
        if ($r['ethnic']) $ethnic['ethnic'][$r['actor_id']] = $r['ethnic'];
        if ($r['kairos']) $ethnic['kairos'][$r['actor_id']] = $r['kairos'];
        if ($r['crowdsource']) $ethnic['crowd'][$r['actor_id']] = $r['crowdsource'];
    }
$verdict='';

foreach ($ethnic_sort as $key => $value) {
    $result[$key] = $ethnic[$key][$id];

    if ( $result[$key] && !$verdict)
    {
        if ($array_compare_cache[$result[$key]]!='N/A')
        {
            $verdict =$result[$key];
        }
    }
}


if ($array_compare_cache[$verdict] )
{
    if ($array_compare_cache[$verdict]!='N/A')
    {
        $verdict=$array_compare_cache[$verdict];
    }

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

$step=26;
$dist=40;

$start=160;
$x=100;

$fontsize = 12;


foreach ($result as $type=>$data)
{
    if ($array_compare_cache[$data])
    {
        $data = $array_compare_cache[$data];
    }
    if (!$data)
    {
        $data = 'N/A';
    }
    $data =strtoupper($data);

    if ($array_type[$type])
    {
        $type=  $array_type[$type];
    }


    imagettftext($im, $fontsize, 0, $x, $start, $color, $font,$type );
    imagettftext($im, $fontsize, 0, $x, $start + $step, $color, $font, $data);

    $start += $dist + $step;

}



imagettftext($im, $fontsize, 0, 100, 564, $color, $font, 'VERDICT:');
imagettftext($im, 13, 0, 100, 593, $color_result, $font, $verdict);

///VERDICT:  WHITE, BRITISH

imagesavealpha($im, true);

$number = str_pad($id, 7, '0', STR_PAD_LEFT);

$imgsource =$_SERVER['DOCUMENT_ROOT'].'/analysis/img_final/'.$number.'.jpg';
if (!file_exists($imgsource)) {
$imgsource =$_SERVER['DOCUMENT_ROOT'].'/analysis/img_final_tmdb/'.$number.'.jpg';

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


header('Content-Type: image/jpeg');
imagejpeg($imagebig);

imagedestroy($image);
imagedestroy($im);
imagedestroy($watermark);

