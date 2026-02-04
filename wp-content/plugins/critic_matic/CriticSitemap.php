<?php
define('EPV_SITEMAP', 'epv-sitemap');
define('EPV_SITEMAP_VERSION', '1.1');
define('EPV_SITEMAP_NAME', 'sitemap.xml');
define('EPV_SITEMAP_HOTMAP', 'hotmap');
define('EPV_SITEMAP_PATH', ABSPATH . EPV_SITEMAP_NAME);
define('EPV_SITEMAP_PATH_HTTP', ABSPATH . 'sitemap-http.xml');
define('EPV_SITEMAPS_DIR', 'wp-content/uploads/sitemaps/');
//Сколько месяцев учитывать в горячей карте и сколько вычесть из ежегодной.
define('EPV_SITEMAP_LAST_MOUNTS', 2);
define('EPV_SITEMAP_GZIP', true);
//По умолчанию делаем только HTTPS версию карты сайта, но можно делать и дубликат HTTP
define('EPV_SITEMAP_HTTP', false);

/*
 * Sitemap generator
 */

class CriticSitemap extends AbstractDB {

    public $current_map = 'epv_sitemap_current';
    public $map_data;
    public $site_url;
    public $db;
    public $cm;
    public $ma;
    public $types = array(
        'movies',
        'tv',
        'reviews'
    );

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            //CM
            'posts' => $table_prefix . 'critic_matic_posts',
            'meta' => $table_prefix . 'critic_matic_posts_meta',
            'authors' => $table_prefix . 'critic_matic_authors',
            'authors_meta' => $table_prefix . 'critic_matic_authors_meta',
            //Movie
            'movie_imdb' => 'data_movie_imdb',
            'actors_all' => 'data_actors_all',
            'movies_meta' => 'search_movies_meta',
            'options' => 'options',
            'data_genre' => 'data_movie_genre',
            'meta_genre' => 'meta_movie_genre',
            'data_country' => 'data_movie_country',
            'meta_country' => 'meta_movie_country',
            'data_provider' => 'data_movie_provider',
            'meta_actor' => 'meta_movie_actor',
            'meta_director' => 'meta_movie_director',
            'actors' => 'data_actors_all',
        );

        $this->map_data = $this->get_option($this->current_map, array());

        $site_url = $this->get_option('siteurl');
        //Force SSL
        $site_url = str_replace('http://', 'https://', $site_url);
        $this->site_url = $site_url;
    }

    public function get_ma() {
        // Get criti
        if (!$this->ma) {
            //init cma
            if (!class_exists('MoviesAn')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesAn.php' );
            }
            $this->ma = new MoviesAn($this->cm);
        }
        return $this->ma;
    }

    public function checkUpdateYear($type) {
        $map_list = $this->getNeedsMapList($type);

        if (!isset($this->map_data['maps'][$type]) || sizeof($map_list) > sizeof($this->map_data['maps'][$type])) {
            $this->updateMapDataByList($map_list, $type);
        }
    }

    public function deleteOptions() {
        delete_option($this->current_map);
    }

    public function getMapData() {
        return $this->map_data;
    }

    function updateMapDataByList($map_list, $type) {
        $update = false;
        foreach ($map_list as $year) {
            if (!isset($this->map_data['maps'][$type][$year])) {
                $this->map_data['maps'][$type][$year] = $this->getDefaultMap();
                $update = true;
            }
        }
        if ($update) {
            $this->update_option($this->current_map, $this->map_data);
        }
    }

    //Cron каждый час
    public function checkUpdateHotMap($type = 'movies', $debug = false, $force = false) {
        //Проверяем изменения на сайте, если есть
        $lastPostUpd = $this->get_last_update($type);
        if ($lastPostUpd && !$force) {
            $lastUpdTime = isset($this->map_data['maps'][$type][EPV_SITEMAP_HOTMAP]['time']) ? $this->map_data['maps'][$type][EPV_SITEMAP_HOTMAP]['time'] : 0;

            //Были обновления записей, обновляем карту
            if ($lastPostUpd > $lastUpdTime) {
                if ($debug) {
                    print "Last post update: $lastPostUpd > Map time: $lastUpdTime<br />";
                }
                $this->updateHotMap($type, $debug);
            } else {
                if ($debug) {
                    print "Last post update: $lastPostUpd < Map time: $lastUpdTime<br />";
                }
            }
        } else {
            //Обновляем карту
            if ($debug) {
                print "Last post update: 0<br />";
            }
            $this->updateHotMap($type, $debug);
        }
    }

    //Cron каждый месяц 2 числа
    public function upadteCurrentYear($type = 'movies', $debug = false) {
        $key = date('Y');
        $this->updateMap($key, $type, $debug);
    }

    public function updateHotMap($type = 'movies', $debug = false) {
        $key = EPV_SITEMAP_HOTMAP;
        $this->updateMap($key, $type, $debug);
    }

    function updateMap($key, $type = 'movies', $debug = false) {
        $map_info = $this->rebuildMap($key, $type);
        $map_data = array();
        $map_data[$type][$key] = $map_info;
        if ($debug) {
            print_r($map_info);
        }
        $this->update($map_data);
    }

    public function getSitemapLink() {
        return $this->site_url . '/' . EPV_SITEMAP_NAME;
    }

    public function getMapListStatus() {
        //Проверяем валидность файла
        $ret = array();
        if (file_exists(EPV_SITEMAP_PATH)) {
            $lines = file(EPV_SITEMAP_PATH);

            //Валидация
            if ($this->validateMapList($lines)) {
                return __('Main Sitemap is valid');
            }
        }
        return __('Main Sitemap is not valid');
    }

    public function getMapLinkByYear($year, $type = 'movies') {
        $host = $this->site_url . '/' . EPV_SITEMAPS_DIR . $type . '-' . $year . '.xml';

        if (EPV_SITEMAP_GZIP) {
            $host .= '.gz';
        }

        return $host;
    }

    function renderMapList() {
        if (sizeof($this->map_data['maps']) > 0) {
            ob_start();
            print '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                    . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            ?>
            <!-- sitemap-generator-version="<?php print EPV_SITEMAP . '-' . EPV_SITEMAP_VERSION ?>" -->
            <!-- generated-on="<?php print date('Y-m-d H:i:s+00:00', $this->map_data['time']); ?>" -->
            <?php
            $maps = $this->map_data['maps'];

            foreach ($maps as $type => $m) {
                $hotmap = $m[EPV_SITEMAP_HOTMAP];
                $this->renderMapItem(EPV_SITEMAP_HOTMAP, $hotmap, $type);
            }

            foreach ($maps as $type => $m) {
                krsort($m);
                foreach ($m as $year => $sitemap) {
                    if ($year == EPV_SITEMAP_HOTMAP) {
                        continue;
                    }
                    if ($sitemap['time'] == 0) {
                        continue;
                    }
                    $this->renderMapItem($year, $sitemap, $type);
                }
            }
            print '</sitemapindex>';
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
        return '';
    }

    function renderMapItem($year, $sitemap, $type = 'movies') {
        ?>

        <sitemap>
            <loc><?php print $this->getMapLinkByYear($year, $type); ?></loc>
            <lastmod><?php print date('Y-m-d\TH:i:s+00:00', $sitemap['time']); ?></lastmod>
        </sitemap>
        <?php
    }

    /*
     * Проверка наша ли это карта или сторонняя
     *
     * <!-- sitemap-generator-version="epv-sitemap-1" -->
     */

    function validateMapList($lines) {
        if (isset($lines) && isset($lines[2])) {
            if (strstr($lines[2], EPV_SITEMAP)) {
                return true;
            }
        }
        return false;
    }

    function options_submit($form_state) {

        $map_data = array();
        if (sizeof($form_state) > 0) {
            foreach ($form_state as $key => $value) {
                if ($key == 'send') {
                    continue;
                }
                if (strstr($key, '-')) {
                    $key_arr = explode('-', $key);

                    $type = $key_arr[0];
                    $year = $key_arr[1];

                    $map_info = $this->rebuildMap($year, $type);

                    if ($map_info) {
                        $map_data[$type][$year] = $map_info;
                    }
                }
            }
        }

        /* Компилируем карту - список
         * Сохраняем опции из $map_data
         */
        $this->update($map_data);
    }

    /* Создаём карту сайта за год
     * Удаляем старую карту
     * записываем новую
     * возвращаем параметры карты
     */

    function rebuildMap($year, $type) {

        $ret = array('count' => 0, 'time' => 0);
        //Получаем данные о записях в базе
        if (preg_match('/[0-9]{4}/', $year) || $year == 'hotmap') {

            //Текущий год
            $current_year = date('Y');
            $current_mount = date('m');
            $m = $current_mount - (EPV_SITEMAP_LAST_MOUNTS - 1);


            if ($year == 'hotmap') {
                //Горячая карта
                $date_first = $current_year . '-' . $m . '-01 00:00:00';
                $date_last = date('c');
            } else {
                //Годовая карта
                $date_first = $year . '-01-01 00:00:00';
                $date_last = ($year + 1) . '-01-01 00:00:00';

                if ($current_year == $year) {
                    //Текущая дата минус два месяца
                    //Если прошло больше 2х месяцев с начала года
                    if ($current_mount > EPV_SITEMAP_LAST_MOUNTS) {

                        $date_last = $year . '-' . $m . '-01 00:00:00';
                    } else {
                        $date_last = $date_first;
                    }
                }
            }
            $from = strtotime($date_first);
            $to = strtotime($date_last);

            if ($type == 'tv' || $type == 'movies') {
                // TV and Movies logic
                $post_type = 'Movie';
                $slug = 'movies';

                if ($type == 'tv') {
                    $post_type = 'TVSeries';
                    $slug = 'tvseries';
                }
                $results = $this->get_movies_by_date($post_type, $from, $to);
            } else {
                //Reviews
                $results = $this->get_critics_by_date($from, $to);
            }
            $links = array();

            if ($year == EPV_SITEMAP_HOTMAP && $type == 'movies') {
                // Add the main page
                $links[time()] = $this->site_url;
            }

            if (sizeof($results) > 0) {
                $ret['count'] = sizeof($results);

                if ($type == 'tv' || $type == 'movies') {
                    foreach ($results as $post) {
                        if ($post->post_name) {
                            $link = $this->site_url . '/' . $slug . '/' . $post->post_name . '/';
                            $links[$post->add_time] = $link;
                        }
                    }
                } else {
                    foreach ($results as $post) {
                        $link = $this->site_url . '/critics/' . $this->cm->get_critic_slug($post) . '/';
                        $links[$post->date_add] = $link;
                    }
                }
            }
            unset($results);

            //Сохраняем файл карты
            if (sizeof($links) > 0) {
                $map = $this->renderMap($links, $year);
                unset($links);
                $ret['time'] = time();
                // $ret['status'] = '<p>' . __('Rebuild compleate') . ': <a target="_blank" href="' . $map_link . '">' . $map_link . '</a></p>';
            }
        }
        //Проверяем папку с картами
        $this->check_and_create_dir(EPV_SITEMAPS_DIR);
        $this->saveMap($map, $year, $type);

        //Сохраняем копию карты http
        if (EPV_SITEMAP_HTTP) {
            $h_year = $year . '-http';
            $h_map = str_replace('https:', 'http:', $map);
            $this->saveMap($h_map, $h_year, $type);
        }

        return $ret;
    }

    function saveMap($map = '', $year = '', $type = 'movies') {

        if (!$map || !$year) {
            return;
        }


        $map_path = EPV_SITEMAPS_DIR . $type . '-' . $year . '.xml';

        if (EPV_SITEMAP_GZIP) {
            $map_path .= '.gz';
        }

        $map_dir = ABSPATH . $map_path;

        //Проверяем наличие старого файла
        if (file_exists($map_dir)) {
            //Удаляем его
            unlink($map_dir);
        }
        if ($map) {
            //Сохраняем новый файл
            $fp = fopen($map_dir, "w");

            if (EPV_SITEMAP_GZIP) {
                $map = gzencode($map, 9);
            }

            fwrite($fp, $map);
            fclose($fp);
            chmod($map_dir, 0777);
        }
    }

    function renderMap($links, $year) {
        if ($year == EPV_SITEMAP_HOTMAP) {
            $changefreq = 'daily';
            $priority = '1';
        } else {
            $changefreq = 'monthly';
            $priority = '0.2';
        }

        ob_start();
        //sitemap encoding
        print '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        //custom style
        /* print '<?xml-stylesheet type="text/xsl" href="/wp-content/plugins/epv-sitemap/sitemap.xsl"?>' . "\n"; */
        ?>
        <urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">	
            <?php
            if (sizeof($links) > 0):
                krsort($links);
                foreach ($links as $time => $loc):
                    ?>

                    <url>
                        <loc><?php print $loc ?></loc>
                        <lastmod><?php print date('Y-m-d\TH:i:s+00:00', $time) ?></lastmod>
                        <changefreq><?php print $changefreq ?></changefreq>
                        <priority><?php print $priority ?></priority>
                    </url> 
                    <?php
                endforeach;
            endif;
            ?>
        </urlset>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    function getDefaultMap() {
        return array(
            'count' => 0,
            'time' => 0
        );
    }

    //Обновляем информацию о карте сайта
    public function update($map_data) {

        /* Array ( [hotmap] => Array ( [tv] => Array ( [count] => 79 [time] => 1634647619 ) ) )
          ) */
        if ($map_data && sizeof($map_data) > 0) {
            foreach ($map_data as $type => $items) {
                foreach ($items as $key => $value) {
                    $this->map_data['maps'][$type][$key] = array(
                        'count' => $value['count'],
                        'time' => $value['time']
                    );
                }
                $this->map_data['time'] = time();
            }

            //Обновляем опции
            $this->update_option($this->current_map, $this->map_data);

            //Перекомпилируем файл ссылок на карты с новыми данными
            $this->compileMap();
        }
    }

    function compileMap() {
        if ($this->map_data) {
            $maplist = $this->renderMapList();
            //Обновляем файл карты сайта
            if ($maplist) {
                $path = EPV_SITEMAP_PATH;
                $this->saveMapList($maplist, $path);

                //Сохраняем копию карты http
                if (EPV_SITEMAP_HTTP) {
                    $path = EPV_SITEMAP_PATH_HTTP;
                    $maplist = str_replace('https:', 'http:', $maplist);
                    $this->saveMapList($maplist, $path);
                }
            }
        }
    }

    function saveMapList($maplist, $path) {
        if (file_exists($path)) {

            //Удаляем сореджимое файла
            $fp = fopen($path, "w");
            fclose($fp);

            //Сохраняем файл
            $fp = fopen($path, "w");
            fwrite($fp, $maplist);
            fclose($fp);
            //chmod($path, 0777);
        }
    }

    //Получаем список небоходимых карт из базы
    //Для этого получаем первую и последнею дату из wp-posts
    function getNeedsMapList($type) {

        //Текущий год
        $current_year = date('Y');

        //Дата первой записи        
        if ($type == 'movies' || $type == 'tv') {
            $type_key = 'Movie';
            if ($type == 'tv') {
                $type_key = 'TVSeries';
            }
            $first_year = $this->get_movie_first_year($type_key, $current_year);
        } else if ($type == 'reviews') {
            $first_year = $this->get_critic_first_year($current_year);
        }

        $ret = array();
        //Массив лет
        if ($current_year > $first_year) {
            while ($current_year > $first_year) {
                $ret[] = $first_year;
                $first_year++;
            }
        }
        $ret[] = $current_year;

        $ret[] = EPV_SITEMAP_HOTMAP;

        return $ret;
    }

    private function get_last_update($type) {
        // Получаем последнюю дату поста
        $time = 0;
        if ($type == 'movies' || $type == 'tv') {
            $post_type = 'Movie';
            if ($type == 'tv') {
                $post_type = 'TVSeries';
            }
            $sql = sprintf("SELECT add_time FROM {$this->db['movie_imdb']} WHERE type = '%s' ORDER BY add_time DESC LIMIT 1", $post_type);

            $time = $this->db_get_var($sql);
        } else {
            $sql = sprintf("SELECT p.date_add "
                    . "FROM {$this->db['posts']} p "
                    . "WHERE p.status != 2 AND p.top_movie>0 ORDER BY p.date_add DESC LIMIT 1");
            $time = $this->db_get_var($sql);
        }
        return $time;
    }

    private function get_movies_by_date($type = 'Movie', $from = 0, $to = 0) {
        $sql = sprintf("SELECT add_time, post_name  FROM {$this->db['movie_imdb']} WHERE type = '%s' AND add_time>=%d AND add_time<%d ORDER BY add_time DESC", $type, $from, $to);
        return $this->db_results($sql);
    }

    private function get_critics_by_date($from, $to) {
        $sql = sprintf("SELECT p.id, p.date_add, p.title, a.name AS author_name, a.type AS author_type, a.last_upd AS author_last_upd, a.date_add AS author_date_add "
                . "FROM {$this->db['posts']} p "
                . "INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id "
                . "INNER JOIN {$this->db['authors']} a ON a.id = am.aid "
                . "WHERE p.status != 2 AND p.top_movie>0 AND p.date_add>=%d AND p.date_add<%d ORDER BY p.date_add DESC", $from, $to);

        return $this->db_results($sql);
    }

    private function get_movie_first_year($type = 'Movie', $current_year = '') {
        $first_year = $current_year;

        $sql = sprintf("SELECT add_time FROM {$this->db['movie_imdb']} WHERE type = '%s' ORDER BY add_time ASC limit 1", $type);
        $from = $this->db_get_var($sql);
        if ($from) {
            $first_year = date('Y', $from);
        }
        return $first_year;
    }

    private function get_critic_first_year($current_year) {
        $first_year = $current_year;

        $sql = sprintf("SELECT date_add FROM {$this->db['posts']} WHERE status != 2 AND top_movie>0  ORDER BY date_add ASC limit 1");
        $from = $this->db_get_var($sql);
        if ($from) {
            $first_year = date('Y', $from);
        }
        return $first_year;
    }

    function check_and_create_dir($path) {

        //Pandora
        $arr = explode("/", $path);

        $path = '';
        if (ABSPATH) {
            $path = ABSPATH . '/';
        }
        foreach ($arr as $a) {
            if ($a) {
                $path = $path . $a . '/';
                $this->fileman($path);
            }
        }
        return null;
    }

    //Проверка наличия и создание директории
    // string $way - путь к дириктории
    function fileman($way) {
        $ret = true;
        if (!file_exists($way)) {
            if (!mkdir("$way", 0777)) {
                $ret = false;
                // throw new Exception('Can not create dir: ' . $way . ', check cmod');
            }
        }
        return $ret;
    }

}
