<?php

class ImgService {

    var $small_w = 150;
    var $small_h = 120;

    function getImg($content, $filter = 0) {
        // Находим в тексте картинку и возрващаем ее путь
        // string $bodymessage - контент.
        // int $filter - поиск картинки, удовлетоворяющей условиям
        //  0 - первая картинка
        //	1 - самая высокая картинка
        //
        //<iframe title="YouTube video player" class="youtube-player" type="text/html" width="640" height="510" src="http://www.youtube.com/embed/0ZwbwweF_WQ" frameborder="0"></iframe>
        switch ($filter):
            case 0:
                return $this->findImage($content);
                break;
            case 1:
                if (preg_match_all('#<img.*src="(.*)".*height="(.*)".*>#iU', $content, $match)) {
                    $md = 0;
                    for ($i = 0; $i < count($match[1]); $i++) {
                        $max = $match[2][$i];
                        if ($md < $max) {
                            $md = $max;
                            $mi = $i;
                        }
                    }
                    return $match[1][$mi];
                } else if (preg_match('#<img.*src="(.*)"#iU', $content, $match))
                    if (count($match) > 0)
                        return $match[1];
                break;
        /* case 2:
          break; */
        endswitch;
        return null;
    }

    # Управление картинками
    //Создать локальную миниатюру из локального или удалённого источника

    function findImage($content) {
        $ret = '';
        if (preg_match_all('#<img[^>]+src=(?:\'|")([^"\']+)(?:\'|")#i', $content, $match)) {
            if (count($match[1]) > 0) {
                foreach ($match[1] as $img) {
                    if (strstr($img, 'livejournal.net')) {
                        # Пропускаем аватарки ЖЖ
                        continue;
                    }
                    $ret = $img;
                    break;
                }
            }
        }
        //try to get video
        if (!$ret && (strstr($content, 'youtube') || strstr($content, 'youtu.be'))) {
            if (preg_match('#//www\.youtube\.com/embed/([a-zA-Z0-9\-_]+)#', $content, $match) ||
                    preg_match('#//www\.youtube\.com/(?:v/|watch\?v=|watch\?.*v=|embed/)([a-zA-Z0-9\-_]+)#', $content, $match) ||
                    preg_match('#//youtu\.be/([a-zA-Z0-9\-_]+)#', $content, $match)) {
                if (count($match) > 1) {
                    $ret = 'https://img.youtube.com/vi/' . $match[1] . '/hqdefault.jpg';
                }
            }
        }

        if (!$ret && strstr($content, 'rutube.ru')) {
            if (preg_match('#rutube.ru/video/([a-zA-Z0-9\-_]+)#', $content, $match) ||
                    preg_match('#rutube.ru/video/embed/([a-zA-Z0-9\-_]+)#', $content, $match)) {
                if (count($match) > 1) {
                    // Get Img from URL
                    // <meta property="og:image" content="https://pic.rutubelist.ru/video/8f/77/8f77cbef93b84a79c832603e3461f3a8.jpg" data-react-helmet="true" />
                    $dst_url = 'https://rutube.ru/video/' . $match[1] . '/';
                    $fs = new FileService();
                    $raw_data = $fs->getProxy($dst_url);
                    if (preg_match('/<meta property="og\:image" content="([^"]+)"/', $raw_data, $match2)) {
                        $ret = $match2[1];
                    }
                }
            }
        }

        return $ret;
    }

    function getThumbLocal($w = 0, $h = 0, $path = '', $small = false, $typeCut = 1) {

        //Путь миниатюр
        $thumbsDir = "wp-content/uploads/thumbs/";
        $thumbDir = $thumbsDir . $w . "x" . $h;
        $fs = new FileService();
        $fs->check_and_create_dir($thumbDir);

        //Внешняя картинка
        $extimg = false;

        //Преобразование миниатюрки
        $localthumbdir = 'pandoraopen.ru/' . $thumbsDir;

        //Img unique name
        $type = '.jpg';
        if (preg_match('#(\.jpg|\.png|\.gif|\.jpeg)#Ui', $path, $match)) {
            if (sizeof($match > 1)) {
                $type = $match[1];
            }
        }
        $name = hash('md5', $path) . $type;

        if (strstr($path, $localthumbdir) || preg_match('/^\/.*/', $path)) {
            //local img
        } else {
            $extimg = true;
        }
        $thumbName = $thumbDir . "/" . $name;
        $thumbNamePath = ABSPATH . $thumbName;

        if (!file_exists($thumbNamePath)) {

            $path_to_load = $path;

            if ($extimg) {
                //upload external img to cache dir
                $tempDir = "wp-content/uploads/temp";
                $fs->check_and_create_dir($tempDir);
                $thumbNameTemp = $tempDir . "/" . $name;
                $thumbNameTempPath = ABSPATH . $thumbNameTemp;
                if (!file_exists($thumbNameTempPath)) {
                    $raw_data = $fs->getProxy($path);
                    if ($raw_data) {
                        $fp = fopen($thumbNameTempPath, "w");
                        fwrite($fp, $raw_data);
                        fclose($fp);
                        $path_to_load = $thumbNameTempPath;
                    } else {
                        return '';
                    }
                }
            } else {
                if (!strstr($path, ABSPATH)) {
                    $path = preg_replace('/^\//', '', $path);
                    $path_to_load = ABSPATH . $path;
                }
            }

            $image_info = array();
            try {
                if ($w == 0 || $h == 0) {
                    $typeCut = 0;
                }
                $res = $this->constrain_image($path_to_load, $w, $h, $image_info, $thumbNamePath, 85, $typeCut, $small);
            } catch (Exception $ex) {
                //p_r($ex);
                return '';
            }
        }
        return "/" . $thumbName;
    }

    /**
     * Constrain an image proportionally and write it to destination file, if specified.
     *
     * Array($new_w, $new_h, $mime_type) of new constrained image will be written to $image_info
     *
     * When $dest_file is NULL, the raw contents will be returned.
     * Otherwise, contents will be written to specified file and NULL will be returned.
     *
     * Quality for image/png (0 to 9) will be rounded (and inverted) automatically from given values 1..100
     *
     * Alpha and non-alpha transparency for gif/png is preserved, if applicable.
     *
     * Gif animation is not supported (outputs gif87a)
     *
     * @param string $src_file - source file, must exist in filesystem
     * @param int $max_w - max width to constrain
     * @param int $max_h - max height to constrain
     * @param array &$image_info - new constrained width, height and mimetype will be put here
     * @param string|null $dst_file - destination file. Must NOT be equal to source
     * @param int $quality - from 1 to 100
     * @param bool $small - простое копирование картинки если она меньше рамок. (входит в ОДЗ :) )
     * @param int $type = 0 сохраняем размеры картинки, $type=1; обрезаем.
     * @paramstring|null $str - текстовая строка.
     * @exception on any processing errors
     * @return mixed
     * v 1.0
     */
    function constrain_image($src_file, $max_w = 0, $max_h = 0, &$image_info = array(), $dst_file = null, $quality = 100, $type = 1, $small = false, $overwrite = true, $str = null) {
        // check params
        $max_w = @(int) $max_w;
        $max_h = @(int) $max_h;
        if ((empty($src_file)) || ((null !== $dst_file) && empty($dst_file)) || (($max_w <= 0) && ($max_h <= 0)) || ($quality < 1) || ($quality > 100)
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
            $Headers = @get_headers($src_file);
            // проверяем ли ответ от сервера с кодом 200 - ОК
            if (preg_match("|200|", $Headers[0]))
                $fileway = 1;
            else {
                throw new Exception("Source file '{$src_file}' not responce: " . $Headers[0]);
            }
        }
        $dst_real_file = realpath($dst_file);

        if (null !== $dst_file)
            if ((!$overwrite) && (!empty($dst_real_file)) && file_exists($dst_real_file))
                throw new Exception("Overwriting option is disabled, but target file '{$dst_real_file}' exists.");
        if ($src_file === $dst_real_file)
            throw new Exception('Source path equals to destination path.');

        // try to obtain source image size and type
        @list($src_w, $src_h, $src_type) = array_values(getimagesize($src_file));
        $src_type = image_type_to_mime_type($src_type);
        if (empty($src_w) || empty($src_h) || empty($src_type))
            throw new Exception('Failed to obtain source image properties.');

        // Если картика меньше рамок
        if (($src_w < $max_w) && ($src_h < $max_h) && ($small == true)) {
            $image_info = array($src_w, $src_h, $src_type);

            // return raw contents
            if (null === $dst_file) {
                if ($fileway != 1)
                    $raw_data = file_get_contents($src_file);
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


        // Проверяем поддерживается ли тип изображения
        @list($create_callback, $write_callback) = $mime_types[$src_type];
        if (empty($mime_types[$src_type]) || (!function_exists($create_callback)) || (!function_exists($write_callback))
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
                    $dst_h = (int) (($max_w / $src_w) * $src_h);
                else
                    $dst_w = (int) (($max_h / $src_h) * $src_w);
            }
            //Вариант умещения в рамки $max_w	
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
        }
        else {
            $k = $dst_w / $dst_h;
            $k2 = $src_w / $src_h;
            // вырезаем серединку по x, если фото горизонтальное 
            if ($k < $k2)
                imagecopyresampled($dst_img, $src_img, 0, 0, round((max($src_w, $src_h * $k) - min($src_w, $src_h * $k)) / 2), 0, $dst_w, $dst_h, round($src_h * $k), $src_h);
            else if ($k > $k2)
                imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, round($src_w / $k));
            else
                imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        }
        //Добавление текста на изображение
        if (null !== $str)
            $this->textinimg($str, $dst_img, $dst_w, $dst_h);

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

    function textinimg($str, $dst_img, $dst_w, $dst_h) {
        //Добавление текста на изображение
        //$str - текстовая строка 
        //$dst_img - изображение
        //$dst_w - widght картинки
        //$dst_h - height картинки
        //
        // определяем координаты вывода текста 
        $size = 2; // размер шрифта 
        $x_text = $dst_w - imagefontwidth($size) * strlen($str) - 3;
        $y_text = $dst_h - imagefontheight($size) - 3;

        // определяем каким цветом на каком фоне выводить текст 
        $white = imagecolorallocate($dst_img, 255, 255, 255);
        $black = imagecolorallocate($dst_img, 0, 0, 0);
        $gray = imagecolorallocate($dst_img, 127, 127, 127);
        if (imagecolorat($dst_img, $x_text, $y_text) > $gray)
            $color = $black;
        if (imagecolorat($dst_img, $x_text, $y_text) < $gray)
            $color = $white;

        // выводим текст 
        imagestring($dst_img, $size, $x_text - 1, $y_text - 1, $str, $white - $color);
        imagestring($dst_img, $size, $x_text + 1, $y_text + 1, $str, $white - $color);
        imagestring($dst_img, $size, $x_text + 1, $y_text - 1, $str, $white - $color);
        imagestring($dst_img, $size, $x_text - 1, $y_text + 1, $str, $white - $color);

        imagestring($dst_img, $size, $x_text - 1, $y_text, $str, $white - $color);
        imagestring($dst_img, $size, $x_text + 1, $y_text, $str, $white - $color);
        imagestring($dst_img, $size, $x_text, $y_text - 1, $str, $white - $color);
        imagestring($dst_img, $size, $x_text, $y_text + 1, $str, $white - $color);

        imagestring($dst_img, $size, $x_text, $y_text, $str, $color);

        return $dst_img;
    }

    function get_bg_color($rgb, $invert = false) {
        $max_color = 120;
        $min_light_color = 220;
        if ($rgb[0] > $max_color | $rgb[1] > $max_color | $rgb[2] > $max_color) {
            $invert = true;
            //Засветляем не яркий фон
            $toup = 0;
            foreach ($rgb as $color) {
                if ($color < $min_light_color) {
                    if ($toup < $min_light_color - $color) {
                        $toup = $min_light_color - $color;
                    }
                }
            }
            $rgb = array($rgb[0] + $toup, $rgb[1] + $toup, $rgb[2] + $toup);
        } else {
            // Затемняем слишком светлый фон                                    
            $min_light_color = 80;
            $todown = 0;
            foreach ($rgb as $color) {
                if ($color > $min_light_color) {
                    if ($todown < $color - $min_light_color) {
                        $todown = $color - $min_light_color;
                    }
                }
            }
            $rgb = array($rgb[0] - $todown, $rgb[1] - $todown, $rgb[2] - $todown);
        }
        return array('rgb' => $rgb, 'invert' => $invert);
    }

    function radial_bg($rgb, $position = "100% 500% at 100% center") {
        $bg = 'style="background: rgb(' . $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2] . '); '
                . 'background: radial-gradient(' . $position . ', '
                . 'rgba(' . $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2] . ', 0) 55%, '
                . 'rgba(' . $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2] . ', 0.6) 70%, '
                . 'rgba(' . $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2] . ', 0.85) 80%, '
                . 'rgb(' . $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2] . ') 95%);"';
        return $bg;
    }

    function image_color($file) {
        list($width, $height) = getimagesize($file);
        $r = $width / $height;
        $w = 1;
        $h = 1;

        //Информация о картинке
        $image_info = getimagesize($file);
        $image_type = $image_info[2];
        if ($image_type == IMAGETYPE_JPEG) {
            $src = imagecreatefromjpeg($file);
        } elseif ($image_type == IMAGETYPE_GIF) {
            $src = imagecreatefromgif($file);
        } elseif ($image_type == IMAGETYPE_PNG) {
            $src = imagecreatefrompng($file);
        }

        $dst = imagecreatetruecolor(1, 1);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, 1, 1, $width, $height);


        $rgb = ImageColorAt($dst, 0, 0);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        $ret = array($r, $g, $b);
        return $ret;
    }

}
