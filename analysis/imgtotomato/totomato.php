<?php


$img_path = "test.png"; //передаем скрипту изображение, на которое нужно что-то наложить, в формате .jpg
$watermark = "mask.png"; //тут хранится путь к накладываемому изображению, в формате .png
$tomato ="tomato.png";

$dir = './tomato_source/';
$file_array =[];
if($handle = opendir($dir)){
    while(false !== ($file = readdir($handle))) {
        if($file != "." && $file != ".."){
            $file_array[]=$file;
        }
    }
}


$count = count($file_array);
$num = random_int( 1,  $count);
$tomato = $dir.$file_array[$num];


$img = imagecreatefrompng($img_path); //создаем исходное изображение
$water_img = imagecreatefrompng($watermark); //создаем водный знак
$tomato_img = imagecreatefrompng($tomato);


imagecopy($img, $water_img, 0, 0, 0, 0, 1024, 1024); //накладываем водный знак на изображение по заданным координатам.
imagetruecolortopalette($img, false, 2);

$rgb = imagecolorat($img, 10, 10);

// Получаем массив значений RGB
$colors = imagecolorsforindex($img, $rgb);
imagealphablending($img, true);
imagesavealpha($img, false);

$white = imagecolorexact($img, $colors["red"], $colors ["green"], $colors["blue"]);
imagecolortransparent($img, $white);

///to tomato
imagealphablending($tomato_img, true);
imagesavealpha($tomato_img, true);
imagecopy($tomato_img, $img, 0, 0, 0, 0, 1024, 1024); //накладываем водный знак на изображение по заданным координатам.
imagesavealpha($img, true);

imagepng($tomato_img, 'result.png');