<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

!class_exists('RWTimages') ? include ABSPATH . "analysis/include/rwt_images.php" : '';

class CreateTsumbs{

    public static function get_thumb_path_full($w, $h, $id) {
        return '/wp-content/uploads/thumbs/poster/' . $w . 'x' . $h . '/' . $id . '.jpg';
    }

    public static function get_poster_tsumb_fast($id, $array_request = array([220, 330], [440, 660]))
    {
        return self::get_poster_tsumb($id, $array_request);
    }




    public static function get_poster_tsumb($id, $array_request = array([220, 330], [440, 660]),$image='',$name='')
    {
        $array_result = [];
        if (!$image && $id) {

            $time = RWTimages::get_last_time($id);

            ///$image = self::get_movie_image($id, 'file');

            foreach ($array_request as $val) {
                $array_result[] =  RWTimages::get_image_link('m_'.$id,$val[0].'x'.$val[1],'',$time);
                /// $array_result[] = self::getThumbLocal_custom($val[0], $val[1], $image, $id,$name);
            }
        }
        else if ($image)
        {
            foreach ($array_request as $val) {
                $array_result[] =  RWTimages::get_image_link('',$val[0].'x'.$val[1],'','',$image);

            }

        }


        if (!$array_result[0]) {
            foreach ($array_request as $i=>$v)
            {
                $array_result[]=WP_SITEURL . '/wp-content/themes/custom_twentysixteen/images/empty_image.svg';
            }
            }
        return $array_result;
    }

    public static function get_movie_image($id,$type = ''){

        $file_patch ="wp-content/uploads/thumbs/original_data/" . $id . ".jpg";
        $dir = ABSPATH .$file_patch ;

        if ($type=='file')
        {
            $homeLink =    ABSPATH;
        }
        else if ($type=='http')
        {
            $homeLink = WP_SITEURL . '/';
        }
        else{
            $homeLink='';
        }

        if (file_exists($dir)) {
            return $homeLink.$file_patch;
        }
        else
        {
          $image=  self::get_img_from_db($id);

          if ($image)
          {
           if (self::curl_save($id, $image))
           {
               return $homeLink.$file_patch;
           }
          }
        }
    }

    public static function curl_save($id, $url)
    {
        $dir = ABSPATH."wp-content/uploads/thumbs/original_data";
        if (!file_exists($dir))mkdir($dir, 0777);

        $dir2 = $dir."/" . $id . ".jpg";
        $result = GETCURL::getCurlCookie($url);
        if ($result)
        {
            file_put_contents($dir2, $result);
            return 1;
        }

    }
    public static function get_img_from_db($id){

        $sql ="select `data` from data_movie_imdb where  id = '".intval($id)."' limit 1";
        $rows = Pdo_an::db_fetch_row($sql);
        $image_data = $rows->data;
        if ($image_data)
        {
            $image_data= json_decode($image_data,1);
            $image = $image_data['image'];
        }
        return $image;
    }
    public static  function fileman($way)
    {
        if (!file_exists($way))
            if (!mkdir("$way", 0777)) {
                // p_r($way);
                //    throw new Exception('Can not create dir: ' . $way . ', check cmod');
            }
        return null;
    }
    public static  function check_and_create_dir_custom($path)
    {


        if ($path) {
            $arr = explode("/", $path);

            $path = '';
            if (ABSPATH) {
                $path = ABSPATH;
            }
            foreach ($arr as $a) {
                if ($a) {
                    $path = $path . $a . '/';
                    self::fileman($path);
                }
            }
            return null;
        }
    }
    public static  function getThumbLocal_custom($w = 0, $h = 0, $path = '',$id='', $tsumb_name='')
    {

        $small = false;
        $typeCut = 1;

        //chdir(ABSPATH);
        $homeLink = WP_SITEURL . '/';
        $thumbsDir = "/wp-content/uploads/thumbs/";
        if ($tsumb_name)
        {
            $thumbDir = $thumbsDir .$tsumb_name.'/'. $w . "x" . $h;
        }
        else{
            $thumbDir = $thumbsDir . $w . "x" . $h;
        }



        if (strstr($path, $homeLink . $thumbsDir)) {

            $pathArr = explode('/', $path);
            $name = $pathArr[sizeof($pathArr) - 1];
        }
        else {
            //Other image
            $type = '.jpg';
            if (preg_match('#(\.jpg|\.png|\.gif|\.jpeg)#Ui', $path, $match)) {
                //var_dump($match);
                if (sizeof($match) > 1) {
                    $type = $match[1];
                }
            }
            $name = hash('md5', $path . $w . $h) . $type;


        }
        if ($id)
        {
            $name = $id. $type;
        }


        $thumbName = $thumbDir . "/" . $name;
        $thumbNamePath = ABSPATH. $thumbName;

        if (!file_exists($thumbNamePath)) {
            self::check_and_create_dir_custom($thumbDir);
            $image_info = array();
            try {

                if ($w == 0 || $h == 0)
                    $typeCut = 0;
              //  echo $path.' => '.$thumbNamePath.'<br>';

              self::constrain_image($path, $w, $h, $image_info, $thumbNamePath, 85, $typeCut, $small);


            } catch (Exception $ex) {

                 var_dump($ex);

                return null;
            }
        }
        $thumbName = substr($thumbName,1);

        return $homeLink . $thumbName;
    }
    public static  function constrain_image($src_file, $max_w = 0, $max_h = 0, &$image_info = array(), $dst_file = null, $quality = 100, $type = 1, $small = false, $overwrite = true, $str = null)
    {
/// echo $src_file.' => '.$dst_file.'<br>';

        // check params
        $max_w = @(int)$max_w;
        $max_h = @(int)$max_h;
        if ((empty($src_file)) || ((null !== $dst_file) && empty($dst_file))
            || (($max_w <= 0) && ($max_h <= 0))
            || ($quality < 1) || ($quality > 100)
        )
            throw new Exception('Wrong incoming params specified.');


        // setup funcs for supported types
        $mime_types = array(
            'image/jpeg' => array('imageCreateFromJpeg', 'imageJpeg')
        , 'image/gif' => array('imageCreateFromGif', 'imageGif')
        , 'image/png' => array('imageCreateFromPng', 'imagePng')
        );

        // check if file names are appropriate
        $fileway = 0;
        if (substr($src_file, 0, 4) != 'http') {
            $src_file = realpath($src_file);
            if (empty($src_file) || !file_exists($src_file)) {
                throw new Exception("Source file '{$src_file}' does not exist.");
            }
        } else {

            // файл, который мы проверяем
            stream_context_set_default([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            // echo $src_file;
            //var_dump(get_headers($src_file));

            $Headers = get_headers($src_file);
            // проверяем ли ответ от сервера с кодом 200 - ОК
            if (preg_match("|200|", $Headers[0])) {
                $fileway = 1;
            } else {
                throw new Exception("Source file '{$src_file}' not responce: " . $Headers[0]);
            }

        }
        $dst_real_file = realpath($dst_file);
///echo $fileway.'<br>';
        if (null !== $dst_file)
            if ((!$overwrite) && (!empty($dst_real_file)) && file_exists($dst_real_file))
                throw new Exception("Overwriting option is disabled, but target file '{$dst_real_file}' exists.");
        if ($src_file === $dst_real_file)
            throw new Exception('Source path equals to destination path.');

        // try to obtain source image size and type


///var_dump(getimagesize($src_file));

        list($src_w, $src_h, $src_type) = array_values(getimagesize($src_file));
        $src_type = image_type_to_mime_type($src_type);


        if (empty($src_w) || empty($src_h) || empty($src_type)) {

            throw new Exception("src_w empty ");
            ///echo '$src_w empty';
            return;
        }
        //   throw new Exception('Failed to obtain source image properties.');

        // Если картика меньше рамок
        if (($src_w < $max_w) && ($src_h < $max_h) && ($small == true)) {


            $image_info = array($src_w, $src_h, $src_type);

            // return raw contents
            if (null === $dst_file) {
                if ($fileway != 1) $raw_data = GETCURL::getCurlCookie($src_file);
                else {
                    $raw_data = file_get_contents(fopen($src_file, "r"));
                    fclose($src_file);
                }
                if (empty($raw_data))
                    throw new Exception('Constraining is not required, but failed to get source raw data.');
                return $raw_data;
            }

            // just copy the file
            if (!copy($src_file, $dst_file))
                throw new Exception('Constraining is not required, but failed to copy source file to destination file.');
            return null;
        }


        try {
            copy($src_file, $dst_file);
        } catch (Exception $e) {

            /// echo $e;

        }


        // Проверяем поддерживается ли тип изображения
        list($create_callback, $write_callback) = $mime_types[$src_type];
        if (empty($mime_types[$src_type])
            || (!function_exists($create_callback))
            || (!function_exists($write_callback))
        )
            throw new Exception("Source image type '{$src_type}' is not supported.");
        if ($type == 0) {
            //Загоняем картинку в нужные рамки, с сохранением целостности
            //Вариант умещения в рамки $max_w $max_h
            if (($max_w > 0) && ($max_h > 0)) {
                // Расчитываем новый размер
                $dst_w = $max_w;
                $dst_h = $max_h;
                //
                if (($src_w - $max_w) > ($src_h - $max_h))
                    $dst_h = (int)(($max_w / $src_w) * $src_h);
                else
                    $dst_w = (int)(($max_h / $src_h) * $src_w);
            } //Вариант умещения в рамки $max_w
            else if (($max_w > 0) && ($max_h == 0)) {
                // вычисление пропорций
                $ratio = $src_w / $max_w;
                $dst_w = round($src_w / $ratio);
                $dst_h = round($src_h / $ratio);
            } else {
                // вычисление пропорций
                $ratio = $src_h / $max_h;
                $dst_w = round($src_w / $ratio);
                $dst_h = round($src_h / $ratio);
            }
            //Сохраняем новые размеры в массив
            $image_info = array($dst_w, $dst_h, $src_type);

        } else {
            //Загоняем в рамки и обрезаем края
            $dst_w = $max_w;
            $dst_h = $max_h;
            $image_info = array($dst_w, $dst_h, $src_type);
        }
        // Создаем картинку, и заодно определяем количество цветов в ней
        // Извлекаем контент
        $src_img = call_user_func($create_callback, $src_file);
        if (empty($src_img))
            throw new Exception("Failed to create source image with {$create_callback}().");

        // Записываем количество цветов
        $src_colors = imagecolorstotal($src_img);

        // Создаем подложку
        if ($src_colors > 0 && $src_colors <= 256)
            $dst_img = imagecreate($dst_w, $dst_h);
        else
            $dst_img = imagecreatetruecolor($dst_w, $dst_h);
        if (empty($dst_img))
            throw new Exception("Failed to create blank destination image.");

        // Реализуем поддержку прозрачности (если она была в картинке)

        $transparent_index = imagecolortransparent($src_img);
        if ($transparent_index >= 0) {
            $t_c = imagecolorsforindex($src_img, $transparent_index);
            $transparent_index = imagecolorallocate($dst_img, $t_c['red'], $t_c['green'], $t_c['blue']);
            if (false === $transparent_index)
                throw new Exception('Failed to allocate transparency index for image.');
            if (!imagefill($dst_img, 0, 0, $transparent_index))
                throw new Exception('Failed to fill image with transparency.');
            imagecolortransparent($dst_img, $transparent_index);
        }

        // или сохраняем альфа прозрачность для png
        if ('image/png' === $src_type) {
            if (!imagealphablending($dst_img, false))
                throw new Exception('Failed to set alpha blending for PNG image.');
            $transparency = imagecolorallocatealpha($dst_img, 0, 0, 0, 127);
            if (false === $transparency)
                throw new Exception('Failed to allocate alpha transparency for PNG image.');
            if (!imagefill($dst_img, 0, 0, $transparency))
                throw new Exception('Failed to fill PNG image with alpha transparency.');
            if (!imagesavealpha($dst_img, true))
                throw new Exception('Failed to save alpha transparency into PNG image.');
        }

        if ($type == 0) {
            // пережимаем изображение с новыми размерами
            if (!imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h))
                throw new Exception('Failed to resample image.');
        } else {
            $k = $dst_w / $dst_h;
            $k2 = $src_w / $src_h;
            // вырезаем серединку по x, если фото горизонтальное
            if ($k < $k2)
                imagecopyresampled($dst_img, $src_img, 0, 0,
                    round((max($src_w, $src_h * $k) - min($src_w, $src_h * $k)) / 2),
                    0, $dst_w, $dst_h, round($src_h * $k), $src_h);
            else if ($k > $k2)
                imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0,
                    $dst_w, $dst_h, $src_w, round($src_w / $k));
            else imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0,
                $dst_w, $dst_h, $src_w, $src_h);
        }

        // пересчтываем quality для png
        if ('image/png' === $src_type) {
            $quality = round(($quality / 100) * 10);
            if ($quality < 1)
                $quality = 1;
            elseif ($quality > 10)
                $quality = 10;
            $quality = 10 - $quality;
        }

        // пишим изображение в файл или в буфер
        if (null === $dst_file)
            ob_start();
        if (!call_user_func($write_callback, $dst_img, $dst_file, $quality)) {
            // Чистим буфер
            if (null === $dst_file)
                ob_end_clean();
            throw new Exception('Failed to write destination image.');
        }


        if (null === $dst_file)
            return ob_get_clean();

        return null;
    }





}