<?php

class PandoraStyles {

    function remove_styles() {
        wp_deregister_style('autoptimize-toolbar');
        wp_deregister_style('gdsr_style_main');
        wp_deregister_style('gdsr_style_xtra');
    }

    function minify_styles() {
        global $wp_styles;
        if (sizeof($wp_styles->queue)) {

            $content = '';
            $ver = '';
            foreach ($wp_styles->queue as $style) {
                if (isset($wp_styles->registered[$style])) {
                    $reg = $wp_styles->registered[$style];

                    if ($reg->ver && strstr($reg->ver, 'ext-')) {
                        continue;
                    }
                    $filename = $reg->src;
                    $file_ver = $reg->ver ? $reg->ver : '-';

                    $local = preg_replace('|^/|', '', $filename);
                    if ($local != $filename) {
                        # Обрабатываем только локальные файлы
                        if (file_exists($local)) {
                            if (!strstr($filename, '.min.')) {
                                # Файл не минифицирован. Ищем мини весрию
                                $minname = $this->get_minify_css($style, $local, $file_ver);
                                if ($minname) {
                                    $local = $minname;
                                    # Если найдена мини версия, меняем версию файла.
                                    $file_ver .= 'm';
                                }
                            }
                            $content .= file_get_contents($local);
                        }

                        wp_deregister_style($style);
                        $ver .= $file_ver;
                    }
                }
            }
            if ($content) {
                $ver_hash = md5($ver);
                //print $ver_hash;
                $cachename = 'all.m';
                $path = 'wp-content/uploads/csstmp';


                //Проверяем наличие файла 
                $file_name = $path . '/' . $cachename . '.' . $ver_hash . '.css';

                if (!file_exists($file_name)) {
                    // Если кеша нету, создаём
                    // Пишем файл
                    $fp = fopen($file_name, "w");
                    fwrite($fp, $content);
                    fclose($fp);
                    chmod($file_name, 0777);
                }
            }
            wp_enqueue_style('all.m', '/' . $file_name);
        }
    }

    function get_minify_css($cssname, $filename, $file_ver) {
        # Минификация css
        $local_name = $cssname;
        if ($file_ver != "-") {
            $local_name .= "-" . $file_ver;
        }

        $loc_name = 'orig.m.' . $local_name . '.css';
        $min_name = 'min.m.' . $local_name . '.css';

        $path = 'wp-content/uploads/cssmin';
        $min_path = $path . '/' . $min_name;
        if (file_exists($min_path)) {
            # Файл уже есть, возвращаем путь
            return $min_path;
        }
        $loc_path = $path . '/' . $loc_name;

        # Копируем файл в рабочую директорию    
        if (!file_exists($loc_path)) {
            # Если нет локального файла, копируем и ожидаем компиляции
            copy($filename, $loc_path);
        }

        # Уменьшаем css
        $content = file_get_contents($loc_path);
        # TODO замена коротких путей длинными
        if (preg_match_all('/url\(([^\)]+)\)/s', $content, $match)) {
            foreach ($match[1] as $found) {
                $item = $found;
                # Пропуск внешних адресов
                if (strstr($item, '://')) {
                    continue;
                }
                $item = str_replace('"', '', $item);
                $item = str_replace("'", '', $item);

                # Пропуск полных адресов
                if (preg_match('/^\//', $item)) {
                    continue;
                }
                $arr_path = explode('/', $filename);

                # Удаляем последний элемент массива со стилем
                unset($arr_path[sizeof($arr_path) - 1]);

                $item = str_replace('../', '', $item, $count);

                while ($count > 0) {
                    unset($arr_path[sizeof($arr_path) - 1]);
                    $count -= 1;
                }

                $path = implode('/', $arr_path);
                $full_path = "/" . $path . "/" . $item;

                $content = str_replace($found, $full_path, $content);
            }
        }

        $min = $this->minify_css($content);
        $fp = fopen($min_path, "w");
        fwrite($fp, $min);
        fclose($fp);
        chmod($min_path, 0777);

        return $min_path;
    }

    function minify_css($str) {
        # remove comments first (simplifies the other regex)
        $re1 = <<<'EOS'
(?sx)
  # quotes
  (
    "(?:[^"\\]++|\\.)*+"
  | '(?:[^'\\]++|\\.)*+'
  )
|
  # comments
  /\* (?> .*? \*/ )
EOS;

        $re2 = <<<'EOS'
(?six)
  # quotes
  (
    "(?:[^"\\]++|\\.)*+"
  | '(?:[^'\\]++|\\.)*+'
  )
|
  # ; before } (and the spaces after it while we're here)
  \s*+ ; \s*+ ( } ) \s*+
|
  # all spaces around meta chars/operators
  \s*+ ( [*$~^|]?+= | [{};,>~+] | !important\b ) \s*+
|
  # spaces right of ( [ :
  ( [[(:] ) \s++
|
  # spaces left of ) ]
  \s++ ( [])] )
|
  # spaces left (and right) of :
  \s++ ( : ) \s*+
  # but not in selectors: not followed by a {
  (?!
    (?>
      [^{}"']++
    | "(?:[^"\\]++|\\.)*+"
    | '(?:[^'\\]++|\\.)*+' 
    )*+
    {
  )
|
  # spaces at beginning/end of string
  ^ \s++ | \s++ \z
|
  # double spaces to single
  (\s)\s+
EOS;

        $str = preg_replace("%$re1%", '$1', $str);
        return preg_replace("%$re2%", '$1$2$3$4$5$6$7', $str);
    }

}

