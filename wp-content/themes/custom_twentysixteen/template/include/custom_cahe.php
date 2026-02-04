<?php

$local_cahe=true;


if ( $local_cahe == true ) {
    if ( !defined('ABSPATH') )
        define('ABSPATH',$_SERVER['DOCUMENT_ROOT'] );


    if (!function_exists('fileman')) {

        function fileman($way)
        {
            /// echo $way;
            if (!file_exists($way))
                if (!mkdir("$way", 0777)) {
                    // p_r($way);
                    //  throw new Exception('Can not create dir: ' . $way . ', check cmod');
                }
            return null;
        }
    }
    if (!function_exists('check_and_create_dir')) {
        function check_and_create_dir($path)
        {
            if ($path) {
                $arr = explode("/", $path);

                $path = '';
                if (ABSPATH) {
                    $path = ABSPATH . '/';
                }
                foreach ($arr as $a) {
                    if ($a) {
                        $path = $path . $a . '/';
                        fileman($path);
                    }
                }
                return null;
            }
        }
    }

    function clear_wp_custom_cache($cachename = null, $folder = 'fastcache')
    {

        $path = 'wp-content/uploads/' . $folder;
        $file_name = ABSPATH .''.$path . '/' . $cachename . '.html';

        if (file_exists($file_name)) {
            unlink($file_name);
        }


    }

    function wp_custom_cache($name = null, $folder = 'fastcache', $time = 3600)
    {

        $path = 'wp-content/uploads/' . $folder;
        ///chdir($_SERVER['DOCUMENT_ROOT']);

        if (!$name)
            return null;

        $cachename = $name;


        if (function_exists('check_and_create_dir')) {

            check_and_create_dir($path);

        }


        $file_name = ABSPATH .''.$path . '/' . $cachename . '.html';

        $cached = false;

        if (file_exists($file_name)) {

            $cached = true;

            /*
                    if ( $name=='display_footer') {
                        $time = 3600;
                    }

                    else
                  */


            if (filemtime($file_name)) {
                if ((time() - filemtime($file_name)) < $time) {
                    $cached = true;
                } else {
                    unlink($file_name);
                    $cached = false;
                    //    echo $name . ' nocache';
                }
            }

        }

        if ($cached == 1) {
            $fbody = file_get_contents($file_name);
            return $fbody;

        } else {
            ob_start();


            $regv='#p-([0-9]+)_([a-z_]+)_([0-9]+)(_.+)*#';

            if (preg_match($regv,$name,$mach))
            {
                $_GET['id']=$mach[1];
                $_GET['page']=$mach[3];

                if ($mach[4])
                {
                    $_GET['data']=substr($mach[4],1);
                }

                $name=$mach[2];
            }


                $regv='#([a-z_]+)__([a-z_]+)__([0-9]+)#';
                if (preg_match($regv,$name,$mach))
                {

                    $name=$mach[1];
                    $a=$mach[2];
                    $b =$mach[3];

                    $result = $name($a,$b);
                }
                else
                {
                    $result = $name();
                }



            $string = ob_get_contents();
            ob_end_clean();

            if ($result && !$string) {
                $string = $result;
            }


            $fp = fopen($file_name, "w");
            fwrite($fp, $string);
            fclose($fp);
            chmod($file_name, 0777);

            return $string;
        }
    }

    if (!function_exists('load_cache_custom'))
    {
        function load_cache_custom($cachename = null, $path = 'wp-content/uploads/longcache',$time=0)
        {

            chdir($_SERVER['DOCUMENT_ROOT']);

            check_and_create_dir($path);

            $file_name = $path . '/' . $cachename . '.html';


            if (file_exists($file_name)) {

                    if (filemtime($file_name)) {
                        if ((time() - filemtime($file_name)) < $time) {

                            $fbody = file_get_contents($file_name);

                        } else {
                            unlink($file_name);
                              }
                    }


                if ($fbody) {
                    return $fbody;
                }

            }
            return 0;
        }
    }


}