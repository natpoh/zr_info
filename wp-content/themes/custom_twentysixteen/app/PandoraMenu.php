<?php

class PandoraMenu {

    var $wpUser = 0;
    var $cache = '';
    var $partnerFull = false;
    var $partner_terms = array();

    function PandoraMenu() {
        $this->wpUser = wp_get_current_user();
        global $themeCache;
        $this->cache = $themeCache;

        $this->partner_terms = HideCat::getCatPartners();
        if (is_category()) {
            # Раздел сайта
            $term = get_queried_object();

            if (in_array($term->term_id, $this->partner_terms)) {
                $this->partnerFull = true;
            }
        }
        if (!$this->partnerFull) {
            global $postCategories;
            if ($postCategories) {
                # Страница сайта, принадлежащая категории
                foreach ($postCategories as $term) {
                    if (in_array($term->term_id, $this->partner_terms)) {
                        $this->partnerFull = true;
                        break;
                    }
                }
            }
        }
    }

    function navbar() {
        #Верхняя минюшка
        ?>
        <div id="navbar">  
            <a class="mlogo" href="/"><span>Ящик Пандоры</span></a>
            <span class="nav-btns">
                <span class="unotify">
                    <?php $this->userFlagReport() ?>
                    <?php $this->userNotify() ?>
                </span>
                <button id="search-btn" class="btn-mob collapsed" data-toggle="collapse" data-target="#scnt" aria-expanded="false" aria-controls="scnt">
                    <i class="icon-search open"></i>
                    <i class="icon-cancel close"></i>
                </button>
                <button id="menu-btn" class="btn-mob collapsed" data-toggle="collapse" data-target="#mcnt" aria-expanded="false" aria-controls="mcnt">
                    <i class="icon-menu open"></i>
                    <i class="icon-cancel close"></i>
                </button>
                <?php $this->userButton() ?>            
            </span>
        </div>  
        <?php
    }

    function userButton() {
        $uid = $this->wpUser->ID;
        $title = "Войти на сайт";
        if ($uid):
            $title = "Мой аккаунт";
            ?>
            <b class="uname"><?php echo $this->wpUser->display_name; ?></b>
        <?php endif ?>
        <button title="<?php print $title ?>" id="user-btn" class="btn-mob collapsed" data-toggle="collapse" data-target="#ucnt" aria-expanded="false" aria-controls="ucnt">
            <?php
            if ($uid):
                ?>
                <span class="open"><?php print get_avatar($uid, 40); ?></span>
                <?php
            else:
                ?>
                <i class="icon-user-o open"></i>
            <?php
            endif;
            ?>
            <i class="icon-cancel close"></i>
        </button>
        <?php
    }

    function userFlagReport() {
        # Иконка флага на материалы
        $uid = $this->wpUser->ID;
        if ($uid) {
            global $flagReport;
            if ($flagReport) {
                print $flagReport->new_reports_widget();
            }
        }
    }

    function userNotify() {
        if (class_exists('UserCpNotifiWidget')) {
            $userCpNotifiWidget = new UserCpNotifiWidget();
            print $userCpNotifiWidget->get_widget();
        }
    }

    function userLinks() {
        $uid = $this->wpUser->ID;
        $uname = 'Гость';
        if ($uid){
            $uname = $this->wpUser->display_name;
        }
        ?>
        <div id="ucnt" class="collapse">
            <ul class="userlinks dropdown-menu">
                <li class="uname"><b><?php print $uname ?></b></li>
                <li class="divider uname"></li>                
                <?php if ($uid) : ?>
                    <li class="sep"><a href="/wp-admin/post-new.php" <?php print $onclick ?>>Добавить материал</a></li>                 
                    <li class="sep"><a href="<?php echo get_author_posts_url($this->wpUser->ID, $this->wpUser->user_nicename) ?>" title="Публичный профиль">Профиль</a></li> 
                    <li class="sep"><a href="/wp-admin/profile.php"  title="Настройки аккаунта">Настройки</a></li> 
                    <li><a href="<?php echo wp_logout_url('/'); ?>" title="Выйти">Выйти</a></li>
                <?php else: ?>  
                    <li><a class="sep" href="/p-login.php">Войти</a></li>
                    <li><a href="/p-login.php?action=register">Регистрация</a></li>                        
                <?php endif; ?>                

            </ul>
        </div>
        <?php
    }

    function searchMenu() {
        # Меню поиска
        include (TEMPLATEPATH . '/searchform.php');
    }

    function navMenuCache() {
        # Кеширование меню навигации
        print $this->cache->cache($name = 'navMenu', $echo = true, $filename = null, $path = null, $class = $this, $arg = null);
    }

    function socialLinks() {
        # Ссылки на соц сети
        ?>
        <div class="socIcons">
            <a class="rss" href="feed/" rel="nofollow" title="Публикации сайта RSS"><i class="icon-rss"></i></a>
            <a class="mail" href="http://feedburner.google.com/fb/a/mailverify?uri=pandoraopen&amp;loc=ru_RU" rel="nofollow" title="Новости на E-mail"><i class="icon-mail"></i></a>
            <a class="vk" href="https://vk.com/pandoraopen" rel="nofollow" title="Группа Вконтакте"><i class="icon-vkontakte"></i></a>
            <a class="ok" href="https://ok.ru/group/52778940825772" rel="nofollow" title="Группа в Одноклассниках"><i class="icon-odnoklassniki"></i></a>            
            <a class="tlg" href="https://t.me/pandoraopen_ru" rel="nofollow" title="Группа в Telegram"><i class="icon-telegram"></i></a>  
        </div>	
        <?php
    }

    function navMenu() {
        # Навигация
        ?>
        <div id="main-menu" class="cat-menu">
            <div class="title-block">
                <h3 class="title">Навигация</h3>
            </div>
            <ul id="page-bar" class="block">
                <li><a href="<?php echo get_option('home'); ?>/">Главная</a></li>           
                <li><a href="<?php print get_category_link(HideCat::getCatChelovek()); ?>">Человек</a></li>
                <li><a href="<?php print get_category_link(HideCat::getCatPath()); ?>">Путь</a></li>
                <?php
                $guestid = 37755;
                $menu = wp_list_pages('echo=0&sort_column=menu_order&title_li=&exclude=22673,110002');
                if (preg_match('#<li.*page-item-' . $guestid . '.*</li>#Uis', $menu, $match)) {
                    $menu = str_replace($match[0], '', $menu);
                }
                //Добавляем активность в сообщество
                //<li class="page_item page-item-15905"><a href="/author/">Сообщество</a></li>
                //<li class="page_item page-item-239980 current_page_item"><a href="/activity/">Активность</a></li>
                $actid = 295590;
                $autid = 15905;
                if (preg_match('#<li.*page-item-' . $actid . '.*</li>#Uis', $menu, $match)) {
                    $child_act = $match[0];
                    $menu = str_replace($child_act, '', $menu);
                    //Беседка
                    $besedkaid = 327989;
                    $child_besedka = '';
                    if (preg_match('#<li.*page-item-' . $besedkaid . '.*</li>#Uis', $menu, $match)) {
                        $child_besedka = $match[0];
                        $menu = str_replace($child_besedka, '', $menu);
                    }

                    $childs = "<ul class='children'>" . $child_act . $child_besedka . "</ul>";
                    $menu = preg_replace('#(<li.*page-item-' . $autid . '.*)(</li>)#Uis', "$1" . $childs . "$2", $menu);
                }
                $menu = replace_pandora_url($menu);
                print $menu;
                ?>
            </ul>   
        </div>
        <?php
    }

    function siteInfo() {
        # Информация о сайте
        ?>
        <div id="site-info" class="sb-block">
            <div class="title-block">
                <h3 class="title">Информация о сайте</h3>        
            </div>
            <div class="block">
                <p><strong>Ящик Пандоры</strong> — информационный сайт, на котором освещаются вопросы: науки, истории, религии, образования, культуры и политики.</p> 
<p>Легенда гласит, что на сайте когда-то публиковались «тайные знания» – информация, которая долгое время была сокрыта, оставаясь лишь достоянием посвящённых. Ознакомившись с этой информацией, вы могли бы соприкоснуться с источником глубокой истины и взглянуть на мир другими глазами.<br />
Однако в настоящее время, общеизвестно, что это только миф. Тем не менее ходят слухи, что «тайные знания» в той или иной форме публикуются на сайте, в потоке обычных новостей.<br />
Вам предстоит открыть Ящик Пандоры и самостоятельно проверить, насколько легенда соответствует действительности.     
                </p>                
                <p>Сайт может содержать контент, не предназначенный для лиц младше 18-ти лет. Прежде чем приступать к просмотру сайта, ознакомьтесь с разделами:</p>
                <ul>
                    <li><a href="/o-sajte/agreement/">Пользовательское соглашение</a></li>
                    <li><a href="/o-sajte/rules/">Принципы сообщества</a></li>
                    <li><a href="/o-sajte/help/">Справочный раздел</a></li>
                </ul>
                <p>Со всеми вопросами и предложениями обращайтесь по почте <a href="mailto:info@pandoraopen.ru">info@pandoraopen.ru</a></p>
            </div>
        </div><?php
    }

    function catMenu() {
        # Меню категорий
        ?>
        <div id="cat-menu" class="cat-menu sb-block">
            <div class="title-block">
                <h3 class="title">Рубрики</h3>        
            </div>
            <ul class="block">
                <?php
                //print $this->getMainCat();
                print $this->cache->cache($name = 'getMainCat', $echo = false, $filename = null, $path = null, $class = $this, $arg = null);

                if ($this->partnerFull) {
                    //print $this->getPartnerCats();
                    print $this->cache->cache($name = 'getPartnerCats', $echo = false, $filename = null, $path = null, $class = $this, $arg = null);
                } else {
                    //print $this->getPartnerCat();
                    print $this->cache->cache($name = 'getPartnerCat', $echo = false, $filename = null, $path = null, $class = $this, $arg = null);
                }
                ?>
            </ul>
        </div><?php
    }

    function getMainCat() {
        $cat = wp_list_categories(array('echo' => 0, 'hierarchical' => 1, 'title_li' => '', 'use_desc_for_title' => 0, 'exclude' => HideCat::getCatPartner()));
        $cat = preg_replace('/title="[^"]+"/', '', $cat);
        return $cat;
    }

    function getPartnerCats() {
        $cat = wp_list_categories(array('echo' => 0, 'hierarchical' => 1, 'title_li' => '', 'use_desc_for_title' => 0, 'exclude' => HideCat::getCatParnterChilds()));
        $cat = preg_replace('/title="[^"]+"/', '', $cat);
        return $cat;
    }

    function getPartnerCat() {
        $cat = wp_list_categories(array('echo' => 0, 'hierarchical' => 1, 'title_li' => '', 'use_desc_for_title' => 0, 'exclude' => HideCat::getCatParnterChilds()));
        $cat = preg_replace('/title="[^"]+"/', '', $cat);
        $cat = preg_replace('|<ul.*<\/ul>|s', '', $cat);
        return $cat;
    }

}
