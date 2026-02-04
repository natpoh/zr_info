<?php

class PandoraBlocks {
    # Дополнительные блоки, загружаемые через аякс

    var $cache = '';
    var $blocks = array(
        'calendar.html' => 'calendar',
        'arhive.html' => 'arhive',
        'topcmt.html' => 'topcmt',
        'svoboda.html' => 'svoboda',
        'partner.html' => 'partner',
        'motiv.html' => 'motiv',
    );

    function PandoraBlocks() {
        global $themeCache;
        $this->cache = $themeCache;
    }

    function init() {
        add_action('init', array($this, 'wp_init'));
    }

    function wp_init() {
        if (isset($_GET['customblock'])) {

            $url_param = '';
            $time_param = '';
            if (isset($this->blocks[$_GET['customblock']])) {
                $customblock = $this->blocks[$_GET['customblock']];
            } else {
                //Проверка дополнительных параметров блока
                if (strstr($_GET['customblock'], '_')) {
                    $customblock_arr = explode('_', str_replace('.html', '', $_GET['customblock']));
                    if (isset($this->blocks[$customblock_arr[0] . '.html'])) {
                        $customblock = $this->blocks[$customblock_arr[0] . '.html'];
                        if (isset($customblock_arr[1])) {
                            $url_param = (int) $customblock_arr[1];
                        }
                        if (isset($customblock_arr[2])) {
                            $time_param = (int) $customblock_arr[2];
                        }
                    }
                }
            }

            if (!$customblock) {
                exit;
            }

            $filename = $customblock;
            if ($url_param==0) {
                $url_param='';
            }
            $filename .= '_' . $url_param;
            
            if ($time_param==0) {
                $time_param='';
            }
            $filename .= '_' . $time_param;
            

            $item = $this->cache->cache($customblock, true, $filename, 'blocks', $this, $url_param);
            print $item;
            exit;
        }
    }

    /* Основные фукнции */

    function calendar($param) {
        if ($param && preg_match('/([0-9]{4})([0-9]{2})/', $param, $match)) {
            global $monthnum, $year;
            $year = $match[1];
            $monthnum = $match[2];
        }
        ?>
        <div class="title-block">
            <h3 class="title">Календарь</h3>
        </div>
        <div class="block">
            <?php get_calendar(); ?>
        </div>
        <?php
    }

    function arhive() {
        ?>
        <div class="title-block">
            <h3 class="title">Архивы</h3>
        </div>
        <div class="block">
            <?php $this->getYearArchives(); ?>
        </div>  
        <?php
    }

    function get_most_viewed_data($days = 7) {
        global $wpdb;
        $category_id = false;
        $limit = 10;



        $category_and = '';
        $inner_join = '';
        if ($category_id) {
            if (is_array($category_id)) {
                $category_sql = "$wpdb->term_taxonomy.term_id IN (" . join(',', $category_id) . ')';
            } else {
                $category_sql = "$wpdb->term_taxonomy.term_id = $category_id";
            }

            $category_and = "AND $wpdb->term_taxonomy.taxonomy = 'category' AND $category_sql ";

            $inner_join = "INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) "
                    . "INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) ";
        }


        $datet = date("Y-m-d 00:00:00", time() - 86400 * $days);


        $most_viewed = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.*, (meta_value+0) AS views "
                . "FROM $wpdb->posts "
                . "LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID "
                . $inner_join
                . "WHERE post_date < '" . current_time('mysql') . "' "
                . "AND post_date > '" . $datet . "' "
                . "AND post_type = 'post' "
                . $category_and
                . "AND post_status = 'publish' "
                . "AND meta_key = 'views' "
                . "AND post_password = '' "
                . "ORDER  BY comment_count "
                . "DESC LIMIT $limit");
        return $most_viewed;
    }

    function topcmt() {
        # Обсуждаемые материалы

        $days = 7;
        while (true) {
            # Если не находим материалы за период 7 дней, ищем за месяц и т.д.
            $most_viewed = $this->get_most_viewed_data($days);
            if ($most_viewed) {
                break;
            } else {
                $days = $days * 4;
            }
        }

        if ($most_viewed) {
            /*
              [ID] => 357694
              [post_title] => Оппозиционер Бабарико снят с выборов президента Белоруссии
              [comment_count] => 354
              [views] => 3923
             */
            ?> 
            <div class="title-block">
                <h3 class="title">Активные дискуссии</h3>
            </div>
            <div class="items">
                <?php
                $img_service = new ImgService();
                $pandoraPost = new PandoraPost();
                global $post;
                foreach ($most_viewed as $post) {
                    $teaser = $pandoraPost->getCacheTeaser();
                    $img = '/wp-content/themes/pandoramob/img/logo-75.jpg';
                    if (preg_match('|<img[^>]+src="([^"]+)"|', $teaser, $match)) {
                        $img = $match[1];
                        $thumb = $img_service->getThumbLocal($img_service->small_w, $img_service->small_h, $img);
                        if ($thumb) {
                            $img = $thumb;
                        }
                    }

                    $post_link = '';
                    if (preg_match('|<h3 class="title"><a href="([^"]+)"|', $teaser, $match)) {
                        $post_link = $match[1];
                    }

//Вывод записи
                    ?>

                    <div class="item">
                        <a href="<?php print $post_link ?>" title="<?php print $post->post_title ?>" >
                            <div class="img">                            
                                <img data-src="<?php print $img ?>" class="lazyload" /> 
                            </div>
                            <h5><?php print $post->post_title ?></h5> 
                            <span class="p-comm"><i class="icon-comment"></i> <?php print $post->comment_count ?></span>  
                        </a>
                    </div>
                <?php }
                ?>
            </div>
            <?php
        }
    }

    function partner() {
        $category_id = HideCat::getCatPartner();
        $category_arr = HideCat::getCatPartners();
        $this->simple_cat($category_id, $category_arr);
    }

    function svoboda() {
        $category_id = HideCat::getCatSvoboda();
        $this->simple_cat($category_id);
    }

    function motiv() {
        $motiv_xml = $this->cache->cache('getMotivXML', false, null, 'blocks', $this);
        $xml = simplexml_load_string($motiv_xml);
        $channel = $xml->channel;
        $items = $channel->item;

        $rand = array();
        foreach ($items as $item) {
            $rand[] = $item;
        }
        shuffle($rand);

        $item = current($rand);

        /*
         * SimpleXMLElement Object
          (
          [title] => Изображение, понятное без слов
          [link] => https://motivatory.ru/poster/izobrazhenie-ponyatnoe-bez-slov-554
          [author] => Михаил----
          [guid] => https://motivatory.ru/img/poster/55dbb1b847.jpg
          [pubDate] => 04/28/2020 - 08:43
          [source] => SimpleXMLElement Object
          (
          [@attributes] => Array
          (
          [url] => https://motivatory.ru/reklama.xml
          )

          )

          )
         */
        ?>
        <a href="<?php print $item->link ?>" title="<?php print $item->title ?>"><img src="<?php print $item->guid ?>"></a>
        <?php
    }

    /* Вспомогательные функции */

    function getMotivXML() {
        $fs = new FileService();
        $code = $fs->getProxy('https://motivatory.ru/reklama.xml');
        return $code;
    }

    function simple_cat($cid = 0, $carr = array()) {
# Последние материалы из рубрики, диапазона рубрик

        $limit = 10;

        global $wpdb;

        $inner_join = '';
        if ($carr) {
            $category_sql = "$wpdb->term_taxonomy.term_id IN (" . join(',', $carr) . ')';
        } else {
            $category_sql = "$wpdb->term_taxonomy.term_id = $cid";
        }
        $category_and = "AND $wpdb->term_taxonomy.taxonomy = 'category' AND $category_sql ";
        $inner_join = "INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) "
                . "INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) ";


        $result = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.* "
                . "FROM $wpdb->posts "
                . $inner_join
                . "AND post_type = 'post' "
                . $category_and
                . "AND post_status = 'publish' "
                . "AND post_password = '' "
                . "ORDER BY post_date DESC "
                . "LIMIT $limit");


        if ($result) {

            $link = get_category_link($cid);
            $title = get_cat_name($cid);
            ?> 
            <div class="title-block">
                <h3 class="title"><?php print $title ?></h3>
            </div>
            <div class="items">
                <?php
                $pandoraPost = new PandoraPost();
                $pandoraPost->themeSimplePosts($result);
            }
            ?>
        </div>
        <div class="btn-bottom">
            <a class="btn" href="<?php print $link ?>">Все публикации</a>
        </div>

        <?php
    }

    function getYearArchives() {
        ?>
        <div id="wp-arhive">
            <?php
            $arh = wp_get_archives('show_post_count=1&echo=0');
//p_r($arh);
//<li><a href="https://dev.pandoraopen.ru/2020/10/">Октябрь 2020</a>&nbsp;(2)</li>
            if (preg_match_all('|<li><a href=\'[^/]+//[^/]+([^\']+)\'>([^<]+) ([0-9]{4})</a>([^<]+)</li>|', $arh, $match)) {

                $cYear = array();
                for ($i = 0; $i < count($match[0]); $i++) {
                    $cYear[$match[3][$i]][] = '<li><a href="' . $match[1][$i] . '">' . $match[2][$i] . '</a>' . $match[4][$i] . '</li>';
                }
                foreach ($cYear as $key => $value) {
                    ?>
                    <div class="title"><?php print $key ?></div>
                    <ul><?php print implode('', $value); ?></ul>
                    <?php
                }
            }
            ?> 
        </div>   
        <?php
    }

}
