<?php

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

class ThemeCache {
# Кеш функций

    public static $cache_path = WP_CONTENT_DIR . '/uploads/cache/';
    public static $path = array(
        'def' => array(
            'folder' => 'def',
            'cache' => array(
                 'scroll-' => 60, // 1 hour
            )
        ),
        'critics' => array(
            'folder' => 'critics',
            'cache' => array(
                'c-' => 129600, // 3 mounth        
            )
        ),
        'critic_posts' => array(
            'folder' => 'critic_posts',
            'cache' => array(
                'p-' => 129600, // 3 mounth        
            )
        )
    );

    public static function cache($name = null, $echo = false, $filename = null, $path_tag = null, $class = null, $arg = null) {
        /* Кеш функций классов
         * name - имя функции
         * echo - функция использует echo или return
         * filename - имя кеша
         * path - куда сохранять кеш
         * class - функция внутри класса?
         * arg - массив аргументов для функции         
         */

        if (!$name) {
            return;
        }

        $path_name = self::$path['def']['folder'];
        if ($path_tag) {
            if (isset(self::$path[$path_tag])) {
                $path_name = self::$path[$path_tag]['folder'];
            }
        }

        $path = self::$cache_path . $path_name;

        //Имя функции для кеша
        $fname = $name;
        if ($arg) {
            if (is_array($arg)) {
                $fname = implode('-', $arg);
            } else {
                $fname = $arg;
            }
            $fname = $name . '-' . $fname;
        }

        $cachename = $filename != null ? $filename : $fname;

        //Проверяем наличие кеша	
        //Создаем нужные папки        
        self::check_and_create_dir($path);
        //Проверяем наличие файла 
        $file_name = $path . '/' . $cachename . '.html';

        if (file_exists($file_name)) {
            $fbody = file_get_contents($file_name);
            return $fbody;
        } else {// Если кеша нету, создаём
            if (!$echo) {
                if ($class) {
                    if ($arg) {
                        $string = $class->$name($arg);
                    } else {
                        $string = $class->$name();
                    }
                } else {
                    if ($arg) {
                        $string = $name($arg);
                    } else {
                        $string = $name();
                    }
                }
            } else {
                ob_start(); // начало буферизации
                if ($class) {
                    if ($arg) {
                        $class->$name($arg);
                    } else {
                        $class->$name();
                    }
                } else {
                    if ($arg) {
                        $name($arg);
                    } else {
                        $name();
                    }
                }
                $string = ob_get_contents(); // буфер в переменную
                ob_end_clean();
            }

            //Пишем файл
            $fp = fopen($file_name, "w");
            fwrite($fp, $string);
            fclose($fp);
            chmod($file_name, 0777);

            return $string;
        }
    }

    static function clearCacheAll($path_tag = '') {

        $cacheFolder = self::$path['def']['folder'];
        if ($path_tag && isset(self::$path[$path_tag])) {
            $cacheFolder = self::$path[$path_tag]['folder'];
        }

        //Открываем директорию кеша
        $dir = WP_CONTENT_DIR . "/uploads/cache/" . $cacheFolder;
        if ($d = @opendir($dir)) {
            while (($file = readdir($d)) !== false) {
                if ($file == '.' || $file == '..')
                    continue;

                $delcache = '';

                echo "$file - removed. $delcache<br />";

                unlink($dir . '/' . $file); //если больше требуемого времени удаляем				
            }
            closedir($d);
        }
    }

    static function clearCache($path_tag = '', $echo = true, $wait_def = 86400) {
        $output = '';

        $cacheFolder = self::$path['def']['folder'];
        if ($path_tag && isset(self::$path[$path_tag])) {
            $cacheFolder = self::$path[$path_tag]['folder'];
        }

        $customCache = array();
        if (isset(self::$path[$path_tag]['cache'])) {
            $customCache = self::$path[$path_tag]['cache'];
        }

        //Открываем директорию кеша
        $dir = WP_CONTENT_DIR . "/uploads/cache/" . $cacheFolder;
        if ($d = @opendir($dir)) {

            while (($file = readdir($d)) !== false) {
                if ($file == '.' || $file == '..')
                    continue;

                $whait = $wait_def; //время в секундах, по умолчанию сутки
                //Проверяем наличие файла в массиве
                if (sizeof($customCache) > 0)
                    foreach ($customCache as $key => $value) {
                        if (strstr($file, $key)) {
                            $whait = $value * 60; //переводим в секунды					
                            break;
                        }
                    }

                $ftime = filemtime($dir . '/' . $file); // смотрим время создания

                $ctime = time() - $ftime;

                if ($ctime > $whait) {

                    $delcache = '';

                    $output .= "$ctime > $whait - removed. $delcache $file<br />";


                    unlink($dir . '/' . $file); //если больше требуемого времени удаляем				
                } else {
                    $output .= "$ctime < $whait - wait. $file<br />";
                }
            }
            closedir($d);
        } else
            $output = 'dir not found';
        if ($echo)
            echo $output;
        else
            return $output;
    }

    static function check_and_create_dir($dst_path) {
        $path = '';
        if (ABSPATH) {
            $path = ABSPATH;
        }
        $dst_path = str_replace($path, '', $dst_path);

        # Создать дирикторию
        $arr = explode("/", $dst_path);

        foreach ($arr as $a) {
            if ($a) {
                $path = $path . $a . '/';
                self::fileman($path);
            }
        }
        return null;
    }

    static function fileman($way) {
        //Проверка наличия и создание директории
        // string $way - путь к дириктории
        $ret = true;
        if (!file_exists($way)) {
            if (!mkdir("$way", 0777)) {
                $ret = false;
                throw new Exception('Can not create dir: ' . $way . ', check cmod');
            }
        }
        return $ret;
    }

    static function get_path($path_name) {
        $path = self::$path['def'];
        if (isset(self::$path[$path_name])) {
            $path = self::$path[$path_name];
        }
        return self::$cache_path . $path;
    }

    static function teaser_cache_name($pid, $lastmod) {
        # Имя кеша для тизеров
        $path = self::get_path('teasers');
        $tkey = self::get_teaser_key($pid, $lastmod);
        $file_name = $path . '/' . $tkey . '.html';
        return $file_name;
    }

    static function get_teaser_key($pid, $lastmod) {
        # Получаем ключ кешированной записи        
        $tkey = "t-" . $pid . "-" . $lastmod;
        return $tkey;
    }

    static function get_post_key($pid, $lastmod) {
        # Получаем ключ кешированной записи        
        $tkey = "p-" . $pid . "-" . $lastmod;
        return $tkey;
    }

    static function key_mounth() {
        $date = self::get_date("Ym");
        return $date;
    }

    static function key_day() {
        $date = self::get_date("Ymd");
        return $date;
    }

    static function key_hour() {
        $date = self::get_date("YmdH");
        return $date;
    }

    static function get_date($string) {
        return gmdate($string, time());
    }

    static function get_date_by_url() {
        $url = $_SERVER['REQUEST_URI'];
        //echo $url;
        // /2011/02/
        // /2011-02-28/mertvye-goroda-rossiimedvezhka/

        $date = '';
        if (preg_match('/\/([0-9]{4})[\/\-]{1}([0-9]{2}).*/', $url, $match)) {
            $date = $match[1] . $match[2];
        }

        if ($date == '') {
            $datec = gmdate('Ym', time());
            $date = $datec;
        }
        return $date;
    }

}
