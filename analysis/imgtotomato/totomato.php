<?php





function create_face($file,$dir)
{
    $img_path = "test.png";
    $watermark = "mask.png";
    $tomato = $dir.$file;


    $img = imagecreatefrompng($img_path);
    $water_img = imagecreatefrompng($watermark);
    $tomato_img = imagecreatefrompng($tomato);


    imagecopy($img, $water_img, 0, 0, 0, 0, 1024, 1024);
    imagetruecolortopalette($img, false, 2);

    $rgb = imagecolorat($img, 10, 10);

// �������� ������ �������� RGB
    $colors = imagecolorsforindex($img, $rgb);
    imagealphablending($img, true);
    imagesavealpha($img, false);

    $white = imagecolorexact($img, $colors["red"], $colors ["green"], $colors["blue"]);
    imagecolortransparent($img, $white);

///to tomato
    imagealphablending($tomato_img, true);
    imagesavealpha($tomato_img, true);
    imagecopy($tomato_img, $img, 0, 0, 0, 0, 1024, 1024);
    imagesavealpha($img, true);

    imagepng($tomato_img, 'result/'.$file);




}


$dir = './tomato_source/';
$file_array =[];
if($handle = opendir($dir)){
    while(false !== ($file = readdir($handle))) {
        if($file != "." && $file != ".."){
            $file_array[]=$file;
        }
    }
}

foreach ($file_array as $file)
{
    create_face($file,$dir);


}
//$count = count($file_array);
//$num = random_int( 1,  $count);
//$tomato = $dir.$file_array[$num];

