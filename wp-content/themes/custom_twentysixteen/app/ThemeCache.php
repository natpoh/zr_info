<?php

class ThemeCache {
    # Кеш функций

    var $cache_path = WP_CONTENT_DIR . '/uploads/cache/';
    var $path = '';

    function ThemeCache() {
        $this->path = array(
            'def' => array(
                'folder' => 'def',
                'cache' => array(
                    'getQuoteXML' => 1440,
                )
            ),
            'blocks' => array(
                'folder' => 'blocks',
                'cache' => array(
                    'arhive' => 1440,
                    'topcmt' => 180,
                )
            ),
            'posts' => array(
                'folder' => 'posts',
                'cache' => array(
                    'p-' => 43200, //post 1 mounth
                )
            ),
            'teasers' => array(
                'folder' => 'teasers',
                'cache' => array(
                    't-' => 129600, //teaser 3 mounth        
                )
            ),
            'long' => array(
                'folder' => 'long',
                'cache' => array(
                    'rel-' => 10080, //related 1 week
                )
            ),
            'user' => array(
                'folder' => 'user',
                'cache' => array(
                    'carmaTrend' => 43200,
                    'getCarmaPowerCache' => 1440,
                    'postCacheWidget' => 1440,
                    'activityCache' => 1440,
                )
            ),
        );
    }

    function cache($name = null, $echo = false, $filename = null, $path_tag = null, $class = null, $arg = null) {
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

        $path_name = $this->path['def']['folder'];
        if ($path_tag) {
            if (isset($this->path[$path_tag])) {
                $path_name = $this->path[$path_tag]['folder'];
            }
        }

        $path = $this->cache_path . $path_name;

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
        $fs = new FileService();
        $fs->check_and_create_dir($path);
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

    function clearCacheAll($path_tag = '') {
        global $memcacheD;
        $memcache = false;
        if (class_exists('Memcached')) {
            if (!$memcacheD) {
                $memcacheD = new Memcached();
                $memcacheD->addServer('127.0.0.1', 11211);
                $memcache = true;
            }
        }

        $cacheFolder = $this->path['def']['folder'];
        if ($path_tag && isset($this->path[$path_tag])) {
            $cacheFolder = $this->path[$path_tag]['folder'];
        }

        //Открываем директорию кеша
        $dir = WP_CONTENT_DIR . "/uploads/cache/" . $cacheFolder;
        if ($d = @opendir($dir)) {
            while (($file = readdir($d)) !== false) {
                if ($file == '.' || $file == '..')
                    continue;

                $delcache = '';
                if ($memcache) {
                    $cachename = str_replace('.html', '', $file);
                    if ($memcacheD->delete($cachename)) {
                        $delcache = ' MemcacheD clear';
                    }
                }
                echo "$file - removed. $delcache<br />";

                unlink($dir . '/' . $file); //если больше требуемого времени удаляем				
            }
            closedir($d);
        }
    }

    function clearCache($path_tag = '', $echo = true, $wait_def = 86400) {
        $output = '';
        global $memcacheD;
        $memcache = false;
        if (class_exists('Memcached')) {
            if (!$memcacheD) {
                $memcacheD = new Memcached();
                $memcacheD->addServer('127.0.0.1', 11211);
                $memcache = true;
            }
        }
        $cacheFolder = $this->path['def']['folder'];
        if ($path_tag && isset($this->path[$path_tag])) {
            $cacheFolder = $this->path[$path_tag]['folder'];
        }
        
        $customCache = array();
        if (isset($this->path[$path_tag]['cache'])){
            $customCache = $this->path[$path_tag]['cache'];
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
                    if ($memcache) {
                        $cachename = str_replace('.html', '', $file);
                        if ($memcacheD->delete($cachename)) {
                            $delcache = ' MemcacheD clear.';
                        }
                    }

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

    function get_path($path_name) {
        $path = $this->path['def'];
        if (isset($this->path[$path_name])) {
            $path = $this->path[$path_name];
        }
        return $this->cache_path . $path;
    }

    function teaser_cache_name($pid, $lastmod) {
        # Имя кеша для тизеров
        $path = $this->get_path('teasers');
        $tkey = $this->get_teaser_key($pid, $lastmod);
        $file_name = $path . '/' . $tkey . '.html';
        return $file_name;
    }

    function get_teaser_key($pid, $lastmod) {
        # Получаем ключ кешированной записи        
        $tkey = "t-" . $pid . "-" . $lastmod;
        return $tkey;
    }

    function get_post_key($pid, $lastmod) {
        # Получаем ключ кешированной записи        
        $tkey = "p-" . $pid . "-" . $lastmod;
        return $tkey;
    }

    function key_mounth() {
        $date = $this->get_date("Ym");
        return $date;
    }

    function key_day() {
        $date = $this->get_date("Ymd");
        return $date;
    }

    function key_hour() {
        $date = $this->get_date("YmdH");
        return $date;
    }

    function get_date($string) {
        return gmdate($string, time() + ( get_option('gmt_offset') * HOUR_IN_SECONDS ));
    }

    function get_date_by_url() {
        $url = $_SERVER['REQUEST_URI'];
        //echo $url;
        // /2011/02/
        // /2011-02-28/mertvye-goroda-rossiimedvezhka/

        $date = '';
        if (preg_match('/\/([0-9]{4})[\/\-]{1}([0-9]{2}).*/', $url, $match)) {
            $date = $match[1] . $match[2];
        }

        if ($date == '') {
            $datec = gmdate('Ym', time() + ( get_option('gmt_offset') * HOUR_IN_SECONDS ));
            $date = $datec;
        }
        return $date;
    }

}
