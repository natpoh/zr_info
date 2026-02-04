<?php

class PandoraScripts {

    var $third_scripts = array();

    function remove_scripts() {
        # Удаление не нужных скриптов
        wp_deregister_script('autoptimize-toolbar');
        wp_deregister_script('gdsr_script');
        wp_deregister_script('jquery');
    }

    function join_scripts() {

        global $wp_scripts;
        if (sizeof($wp_scripts->queue)) {

            /* [identic] => _WP_Dependency Object
              (
              [handle] => identic
              [src] => /wp-content/plugins/identic/js/identic.min.js
              [deps] => Array
              (
              [0] => jquery
              )

              [ver] => 1.1
              [args] =>
              [extra] => Array
              (
              [group] => 1
              )

              ) */
            $content = '';
            $ver = '';
            foreach ($wp_scripts->queue as $script) {
                if (isset($wp_scripts->registered[$script])) {
                    $reg = $wp_scripts->registered[$script];
                    if ($reg->handle == 'jquery') {
                        continue;
                    }

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
                                $minname = $this->get_minify_js($script, $local, $file_ver);
                                if ($minname) {
                                    $local = $minname;
                                    # Если найдена мини версия, меняем версию файла.
                                    $file_ver .= 'm';
                                }
                            }
                            $content .= file_get_contents($local);
                        }
                        wp_deregister_script($script);
                        $ver .= $file_ver;
                    }
                }
            }
            if ($content) {
                $ver_hash = md5($ver);
                //print $ver_hash;
                $cachename = 'all.m';
                $path = 'wp-content/uploads/jstmp';

                //Проверяем наличие файла 
                $file_name = $path . '/' . $cachename . '.' . $ver_hash . '.js';

                if (!file_exists($file_name)) {
                    // Если кеша нету, создаём
                    //Пишим файл
                    $fp = fopen($file_name, "w");
                    fwrite($fp, $content);
                    fclose($fp);
                    chmod($file_name, 0777);
                }
                wp_enqueue_script('all.m', '/' . $file_name, array(), false, true);
            }
        }
    }

    function add_third_scripts() {
        # Обработка скриптов, загружаемых другими скриптами
        /* TODO
         * Поиск мини версий скрипта
         * Добавление в переменную имён скрипта с учётом версии
         */

        global $third_scripts;
        if (sizeof($third_scripts) > 0) {
            $this->third_scripts = array();

            foreach ($third_scripts as $name => $script) {

                $filename = $script['path'];
                $file_ver = $script['ver'];

                $local = preg_replace('|^/|', '', $filename);
                if ($local != $filename) {
                    # Обрабатываем только локальные файлы
                    if (file_exists($local)) {
                        if (!strstr($local, '.min.')) {
                            # Файл не минифицирован. Ищем мини весрию
                            $minname = $this->get_minify_js($name, $local, $file_ver);
                            if ($minname) {
                                $local = $minname;
                            }
                        }
                    }
                }
                $this->third_scripts[$name] = $local;
            }
            add_action('wp_footer', array($this, 'wp_post_head'));
        }
    }

    function get_minify_js($script_name, $filename, $file_ver) {
        # Минификация с помощью внешнего докер-сервиса
        $local_name = $script_name;
        if ($file_ver != "-") {
            $local_name .= "-" . $file_ver;
        }

        $loc_name = 'orig.m.' . $local_name . '.js';
        $min_name = 'min.m.' . $local_name . '.js';

        $path = 'wp-content/uploads/jsmin';
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
        //$work_dir = "/var/www/pandoraopen.ru/wp-content/uploads/jsmin";
        //$command = "docker run -i --rm -v {$work_dir}:/opt/scripts jborza/closure-compiler --js /opt/scripts/{$loc_name} --js_output_file /opt/scripts/{$min_name}";
        return $loc_path;
    }

    function wp_post_head() {
        if (sizeof($this->third_scripts) > 0) {
            ?>
            <script type="text/javascript">
                var third_scripts = {<?php
            foreach ($this->third_scripts as $key => $value) {
                print $key . ':"/' . $value . '",';
            }
            ?>};
            </script>
            <?php
        }
    }

}
