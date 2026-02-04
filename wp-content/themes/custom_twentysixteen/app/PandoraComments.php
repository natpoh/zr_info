<?php

class PandoraComments {
    # Управление комментариями

    var $comments_per_page = 20;
    var $is_recent = false;
    var $is_search = false;
    var $show_avatar = true;
    var $post_ids = array();
    var $search_query = '';
    var $search_sortby = '';
    var $cauthor_id = 0;
    var $ajax_load = true;

    function init() {

        add_action('wp_set_comment_status', array($this, 'upd_comment_childs'));
        add_action('preprocess_comment', array($this, 'ctg_auto_check_comment'), 1, 1);
        add_action('comment_post', array($this, 'comment_post_bot_filter'), 9, 2);

        /* Votes info */
        add_action("wp_ajax_vote_info_users", array($this, "vote_info_users"));
        add_action('wp_ajax_nopriv_vote_info_users', array($this, "vote_info_users"));

        /* Ajax comments */
        add_action("wp_ajax_load_comments", array($this, "load_comments"));
        add_action('wp_ajax_nopriv_load_comments', array($this, "load_comments"));
    }

    function vote_info_users() {
        $cid = 0;
        $vote = true;

        if (strstr($_REQUEST['id'], "comment-")) {
            $cid = (int) str_replace('comment-', '', $_REQUEST['id']);
        }

        if (!is_integer($cid) || $cid == 0) {
            exit("Error comment id");
        }

        global $wpdb;
        $result = $wpdb->get_results(sprintf("SELECT id, user_id, guest_id, vote FROM wp_gdsr_votes_log WHERE vote_type = 'cmmthumb' AND id = %d", $cid));

        if (sizeof($result) == 0) {
            $vote = false;
        }

        $plusUsersNum = 0;
        $plusGuestNum = 0;

        $minusUsersNum = 0;
        $minusGuestNum = 0;

        $vote_data = array();
        foreach ($result as $item) {
            if ($item->user_id > 0) {
                //get user
                $user = getUserById($item->user_id);
                $user->avatar = get_avatar($item->user_id, '40');

                if (!$user->rating)
                    $user->rating = 0;

                if (!$user->carma)
                    $user->carma = 0;

                $carmakey = $user->rating * 100000 + $item->user_id;

                $user->carma_icon = CarmaIcon::carma($user->carma);
                $user->rating_icon = CarmaIcon::rating($user->carma);

                $user->url = '/author/' . $user->url;
                $user->vote = $item->vote;
                unset($user->id);

                if (isset($vote_data['user'][$carmakey])) {
                    if ($item->vote == '1') {
                        $plusGuestNum++;
                    } else {
                        $minusGuestNum++;
                    }
                    continue;
                }

                $vote_data['user'][$carmakey] = (array) $user;

                if ($item->vote == '1') {
                    $plusUsersNum++;
                } else {
                    $minusUsersNum++;
                }
            } else {
                $vote_data['guest'][$item->vote] += 1;

                if ($item->vote == '1') {
                    $plusGuestNum++;
                } else {
                    $minusGuestNum++;
                }
            }
        }
        $totalVotes = sizeof($result);
        $guestCountPlusText = $this->getGuestInfo($plusGuestNum);
        $plusSep = ($plusUsersNum > 0 && $plusGuestNum > 0) ? ", " : '';
        $plusText = $this->getUserInfo($plusUsersNum) . $plusSep . $guestCountPlusText;


        $guestCountMinusText = $this->getGuestInfo($minusGuestNum);
        $minusSep = ($minusUsersNum > 0 && $minusGuestNum > 0) ? ", " : '';
        $minusText = $this->getUserInfo($minusUsersNum) . $minusSep . $guestCountMinusText;

        $vote_data['data'] = array(
            'plusText' => $plusText,
            'plusGuestText' => $guestCountPlusText,
            'plusCount' => $plusGuestNum,
            'minusText' => $minusText,
            'minusGuestText' => $guestCountMinusText,
            'minusCount' => $minusGuestNum,
            'totalVotes' => "$totalVotes голос" . TextOp::okonchanie($totalVotes, '', 'а', 'ов'),
        );

        if (isset($vote_data['user'])) {
            ksort($vote_data['user']);
        }

        if ($vote === false) {
            $result['type'] = "error";
            $result['vote_data'] = 'error';
        } else {
            $result['type'] = "success";
            $result['vote_data'] = $vote_data;
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $result = json_encode($result);
            echo $result;
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

        die();
    }

    function getUserInfo($user = 0) {
        $ret = '';
        if ($user > 0) {
            $ret .= $user . ' участник' . TextOp::okonchanie($user, '', 'а', 'ов');
        }

        return $ret;
    }

    function getGuestInfo($guest = 0) {
        $ret = '';

        if ($guest > 0) {

            $ret .= $guest . ' гост' . TextOp::okonchanie($guest, 'ь', 'я', 'ей');
        }

        return $ret;
    }

    function getComments($post_id=0) {
        ob_start();
        $comments = $this->get_post_comments_data($post_id);
        ?>
        <?php if ($comments): ?>
            <?php
            $number = get_comments_number();
            $comm_text = 'Нет комментариев';
            if ($number == 1) {
                $comm_text = 'Один комментарий';
            } else if ($number > 1) {
                $comm_text = $number . ' Комментари' . TextOp::okonchanie($number, 'й', 'я', 'ев');
            }
            ?>
            <h5 class="com-title"><b><?php print $comm_text ?></b>
                &raquo; <a href="#respond">Оставить комментарий</a>
            </h5>
            <?php
            // Навигация 
            if (get_comment_pages_count() > 1 && get_option('page_comments')) : // Are there comments to navigate through? 
                ?>
                <div class="navigation clearfix">
                    <div class="nav-previous"><?php previous_comments_link('<span class="meta-nav">&larr; Старые комментарии</span>') ?></div>
                    <div class="nav-next"><?php next_comments_link('Новые комментарии <span class="meta-nav">&rarr;</span>') ?></div>
                </div> <!-- .navigation -->
            <?php endif; ?>

            <ul class="commentlist">
                <?php wp_list_comments(array(), $comments); ?>
            </ul>

            <?php
            // Навигация низ 
            if (get_comment_pages_count() > 1 && get_option('page_comments')) : // Are there comments to navigate through? 
                ?>
                <div class="navigation clearfix">
                    <div class="nav-previous"><?php previous_comments_link('<span class="meta-nav">&larr; Старые комментарии</span>') ?></div>
                    <div class="nav-next"><?php next_comments_link('Новые комментарии <span class="meta-nav">&rarr;</span>') ?></div>
                </div> <!-- .navigation -->
            <?php endif; ?>
        <?php endif; ?>
        <?php
        $comments_template_data = ob_get_contents();
        ob_end_clean();

        return $comments_template_data;
    }

    function commentForm($args = array(), $post_id = null) {
        if (null === $post_id)
            return;

        //Пользователь
        $user = wp_get_current_user();
        $user_identity = $user->exists() ? $user->display_name : '';

        //Автор записи
        $author_class = '';
        global $cfornt;
        if (!$cfornt){
            return;
        }
        if ($post = $cfornt->cm->get_post($post_id)) {
            $post_author = $post->post_author;
            if ($user->ID && $post_author == $user->ID) {
                $author_class = ' psta';
            }
        }

        $commenter = wp_get_current_commenter();

        $agreement = '<br />Оставляя комментарий Вы <b>соглашаетесь</b> с <a href="/o-sajte/agreement/" target="_blanc">правилами сайта</a>.';

        $req = get_option('require_name_email');
        $aria_req = ( $req ? " aria-required='true'" : '' );
        $fields = array(
            'author' => '<p class="comment-form-author">' . '<input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30"' . $aria_req . ' />' .
            '<label for="author"><b>' . __('Имя') . '</b></label> ' . ( $req ? '<span class="required">(Обязательно)</span>' : '' ) . '</p>',
            'email' => '<p class="comment-form-email"><input id="email" name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" size="30"' . $aria_req . ' />' .
            '<label for="email"><b>Почта</b> (не публикуется)</label> ' . ( $req ? '<span class="required">(Обязательно)</span>' : '' ) . '</p>'
        );

        $required_text = "<p>Будте вежливы. Не ругайтесь. Оффтоп тоже не приветствуем. Спам убивается моментально. $agreement</p>";
        $defaults = array(
            'fields' => apply_filters('comment_form_default_fields', $fields),
            'comment_field' => '<p class="comment-form-comment"><label for="comment"><b>' . _x('Комментарий', 'noun') . '</b></label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>',
            'must_log_in' => '<p class="must-log-in">' .
            sprintf(__('Вы должны <a href="%s">авторизоваться</a> чтобы оставлять комментарии.'), wp_login_url(apply_filters('the_permalink', get_permalink($post_id)))) . '</p>',
            'log_in' =>
            sprintf(__('<a href="%s">авторизоваться</a>'), wp_login_url(apply_filters('the_permalink', get_permalink($post_id)))),
            'logged_in_as' => '<p class="logged-in-as">' .
            sprintf(__('Вы вошли как <a href="%1$s">%2$s</a>. <a href="%3$s" title="Выйти из данного аккаунта">Выйти?</a>'), admin_url('profile.php'), $user_identity, wp_logout_url(apply_filters('the_permalink', get_permalink($post_id)))) . '</p>',
            'comment_notes_before' => '<p class="comment-notes">' . $required_text . '</p>',
            'comment_notes_after' => '<p class="form-allowed-tags">' .
            sprintf(__('Вы можете использовать эти HTML теги и атрибуты: %s'), ' <code>' . allowed_tags() . '</code>') . '</p>',
            'id_form' => 'commentform',
            'id_submit' => 'submit',
            'title_reply' => __('Оставить комментарий'),
            'title_reply_to' => __('Оставить комментарий на %s'),
            'cancel_reply_link' => __('Отмена ответа'),
            'label_submit' => __('Ок, отправить'),
        );

        $args = wp_parse_args($args, apply_filters('comment_form_defaults', $defaults));
        ?>
        <?php if (comments_open()) : ?>
            <?php
            $is_block = false;
            $is_comment_block = false;
            // Проверка блокировки пользователя
            if (class_exists('UsersBan')) {
                $usersBan = new UsersBan();
                if ($user->ID > 0) {
                    $isBan = $usersBan->isBan($user->ID);
                    if ($isBan) {
                        $banInfo = $usersBan->banInfo($user->ID);
                        $ubt = $banInfo->ubt;
                        if ($ubt == 1) {
                            $is_block = true;
                            // Бан комментариев
                        } else if ($ubt == 2) {
                            $current_time = strtotime(gmdate('Y-m-d H:i:s', ( time() + ( get_option('gmt_offset') * HOUR_IN_SECONDS ))));
                            $unblock_date = $banInfo->date_added + ($banInfo->time) * 86400;
                            if ($current_time < $unblock_date) {
                                $is_comment_block = true;
                                $ban_date = CtgHumanDate::humanDate($current_time, $unblock_date);
                                $autorlink = '/author/' . $user->user_nicename;
                                $comment_block_text = "Комментирование заблокированно до $ban_date в связи с нарушением правил сайта. <a href=\"$autorlink/baninfo\">Подробности нарушения</a>.";
                            } else {
                                $usersBan->unBanUser($user->ID);
                            }
                        }
                    }
                }
            }
            //robot no comments
            if (user_can($user, 'robot')) {
                $is_comment_block = true;
                $comment_block_text = "Робот не может оставлять комментарии.";
            }

            $author_role = '';
            if (user_can($user, 'author')) {
                $author_role = ' author';
            }

            $editor_class = '';
            if (current_user_can("editor") || current_user_can("administrator")) {
                $editor_class = " editor";
            }
            $usebl_class = '';
            if (class_exists('UserBl')) {
                # Черные списки
                $usebl_class = ' userbl';
            }
            ?>
            <?php do_action('comment_form_before'); ?>
            <div id="respond" class="rspnd<?php print $author_class . $editor_class . $author_role . $usebl_class ?>">
                <h3 id="reply-title"><?php comment_form_title($args['title_reply'], $args['title_reply_to']); ?>
                    <span class="cancel"><?php cancel_comment_reply_link($args['cancel_reply_link']); ?></span></h3>
                <?php if (get_option('comment_registration') && !is_user_logged_in()) : ?>
                    <?php echo $args['must_log_in']; ?> <?php do_action('comment_form_must_log_in_after'); ?>
                <?php elseif ($user && $is_block): ?>  
                    <div class="msg error">Вы не можете оставлять комментарии так как ваш аккаунт заблокирован.</div>
                <?php elseif ($user && $is_comment_block): ?>  
                    <div class="msg warning"><?php print $comment_block_text; ?></div>
                <?php else : ?>                
                    <form action="<?php echo site_url('/wp-comments-post.php'); ?>"
                          method="post" id="<?php echo esc_attr($args['id_form']); ?>"><?php do_action('comment_form_top'); ?>
                              <?php if (is_user_logged_in()) : ?> 
                                  <?php echo apply_filters('comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity); ?>
                                  <?php do_action('comment_form_logged_in_after', $commenter, $user_identity); ?>
                              <?php else : ?> 
                            <p>Вы вошли как Гость. Вы можете <?php echo $args['log_in']; ?></p>
                            <?php echo $args['comment_notes_before']; ?> <?php
                            do_action('comment_form_before_fields');
                            foreach ((array) $args['fields'] as $name => $field) {
                                echo apply_filters("comment_form_field_{$name}", $field) . "\n";
                            }
                            do_action('comment_form_after_fields');
                            ?> 
                        <?php endif; ?> 
                        <?php echo apply_filters('comment_form_field_comment', $args['comment_field']); ?>
                        <?php //echo $args['comment_notes_after'];    ?>
                        <input id="sjs" name="sjs" type="hidden" value="no" />
                        <div class="form-submit">
                            <button name="submit" class="btn" type="submit" id="<?php echo esc_attr($args['id_submit']); ?>" />Ок, отправить</button> 
                            <?php comment_id_fields(); ?>
                        </div>
                        <?php do_action('comment_form', $post_id); ?></form>
                <?php endif; ?></div>            
            <!-- #respond -->
            <?php do_action('comment_form_after'); ?>

        <?php else : ?>
            <?php do_action('comment_form_comments_closed'); ?>
            <div id="commentform">
                <div class="hide">
                    <input id="sjs" name="sjs" type="hidden" value="no" />
                </div>
            </div>
        <?php endif; ?>
        <?php
    }

    function upd_comment_childs($cid) {
        #Меняем статус ответов на комментарий

        $comment = get_comment($cid);
        $comment_status = $comment->comment_approved;

        if ($comment_status == 'spam' || $comment_status == 'trash') {

            $post = get_post($comment->comment_post_ID);
            global $wpdb;
            $childs = array();
            $sql = sprintf("SELECT comment_ID, comment_parent FROM " . $wpdb->comments . " WHERE comment_post_ID=%d AND  comment_approved IN(0,1)", $post->ID);
            $result = $wpdb->get_results($sql);
            if (count($result)) {
                $commentChilds = new CommentChilds($result);
                $commentChilds->tree($cid);
                if (count($commentChilds->childs)) {
                    $childs = $commentChilds->childs;
                }
            }


            if (count($childs)) {
                remove_action('wp_set_comment_status', 'upd_comment_childs');
                foreach ($childs as $id) {
                    wp_set_comment_status($id, $comment_status);
                }
            }
        }
    }

    function ctg_auto_check_comment($commentdata) {
        $sjs = $_POST['sjs'];
        if ($sjs == "no") {
            wp_die(__('Ошибка: для отправки комментария включите Javasrcipt'));
        }

        if ($commentdata['comment_parent'] && $commentdata['user_ID'] && function_exists('userbl_init')) {
            $userbl = new UserBl();
            $user = $commentdata['user_ID'];

            $parrent = get_comment($commentdata['comment_parent']);

            $ban_type = $userbl->inBl($user, $parrent->user_id);
            if ($ban_type) {
                wp_die(__('Ошибка: вы не можете отвечать пользователю, который добавил вас в чёрный список.'));
            } else {
                $ban_type = $userbl->inBl($parrent->user_id, $user);
                if ($ban_type) {
                    wp_die(__('Ошибка: вы не можете отвечать пользователю, которого добавили в чёрный список.'));
                }
            }
        }
        if (function_exists('updGuestMeta'))
            updGuestMeta($commentdata['comment_author_email'], (int) $sjs);

        return $commentdata;
    }

    function comment_post_bot_filter($comment_ID, $comment_approved) {
        /*
         * Защита от спама в комментариях
         * 1. Проверка url адреса для нового пользователя.
         *  Если рейтинг пользователя меньше 10 и он оставил урл адрес,
         *  комментарий отправляется на модерацию.
         * 
         * 2. Проверка повтора. 
         *  Если рейтинг пользователя меньше 10 загружаем предыдущие два его комментария
         *  сравниваем содрежание текущего комментария с ними. Если оно совпадает, 
         *  все три комментария отправляем на модерацию и ставим пользователя на модерацию.
         *  Создаём уведомление для администратора.
         * 
         * 3. Проверка вставки английских букв в комментарий: пример деbилы.
         *  Проверяем слова регулярками, если в слове есть русские и английские буквы
         *  не разделённые пробелами, отправляем комментарий на модерацию.
         * 
         * TODO Комментарии к модерации. Добавить функционал отображения информации, почему
         * тот или иной комментарий попал на модерацию. Можно создавать уведомления.
         * 
         * 
         *     [comment_ID] => 399421
          [comment_post_ID] => 313213
          [comment_author] => test
          [comment_author_email] => testtt@example.com
          [comment_author_url] =>
          [comment_author_IP] => 172.17.0.1
          [comment_date] => 2019-05-25 21:18:29
          [comment_date_gmt] => 2019-05-25 18:18:29
          [comment_content] => 555
          [comment_karma] => 0
          [comment_approved] => 1
          [comment_agent] => Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:67.0) Gecko/20100101 Firefox/67.0
          [comment_type] =>
          [comment_parent] => 0
          [user_id] => 47384
         * 
         */
        if ($comment_approved != 1) {
            return;
        }

        $approved = 1;

        $comment = get_comment($comment_ID);

        $smallrating = false;

        if (function_exists('getUserById') && $comment->user_id) {
            $user = getUserById($comment->user_id);

            if ($user && $user->rating < 10) {
                $smallrating = true;
            }
        }

        if ($smallrating) {
            //Поиск доменов
            if (preg_match('/[a-zA-Z_0-9]+\.[a-zA-Z_0-9]+/', $comment->comment_content)) {
                $approved = 0;
            }
            //Последние три повтора
            if ($approved) {
                global $wpdb;
                $sql = sprintf("SELECT comment_ID, comment_content FROM {$wpdb->comments} WHERE user_id=%d ORDER BY comment_ID DESC limit 3", $comment->user_id);
                $comments = $wpdb->get_results($sql);
                if ($this->detect_povtor_comments($comments)) {
                    //пользователя и комментарии на модерацию
                    $approved = 0;
                    //Пользователя на модерацию
                    if (class_exists('UsersBan')) {
                        $usersBan = new UsersBan();
                        $usersBan->banUser($comment->user_id, 0, '', false);
                        //Уведомление для администратора
                        global $flagReport;
                        if ($flagReport) {
                            $flagReport->user_comment_moderate($user, $comment, $comments);
                        }
                    }
                    //Предыдущие комментарии на модерацию
                    foreach ($comments as $item) {
                        if ($item->comment_ID != $comment->comment_ID) {
                            $c = get_comment($item->comment_ID);
                            $c->comment_approved = 0;
                            wp_update_comment((array) $c);
                        }
                    }
                }
            }
        }

        if ($approved != 1) {
            $comment->comment_approved = $approved;
            wp_update_comment((array) $comment);
        }
    }

    function detect_povtor_comments($comments) {
        $povtor = false;
        if (count($comments) == 3) {
            $con_1 = $comments[0]->comment_content;
            $con_2 = $comments[1]->comment_content;
            $con_3 = $comments[2]->comment_content;
            if ($this->compareResultsComm($con_1, $con_2) > 90 && $this->compareResultsComm($con_1, $con_3) > 90) {
                $povtor = true;
            }
        }
        return $povtor;
    }

    function compareResultsComm($first, $second) {
        $search_first = $this->getUniqueWordsComm(strip_tags($first));
        $search_second = $this->getUniqueWordsComm(strip_tags($second));
        $count = sizeof($search_first);
        $find = 0;
        if ($count && sizeof($search_second)) {
            foreach ($search_second as $word) {
                if (in_array($word, $search_first)) {
                    $find++;
                } else {
                    // echo " <b>$word</b>, ";
                }
            }
        }
        $precent = ($find > 0) ? 100 * $find / $count : 0;
        return $precent;
    }

    function getUniqueWordsComm($words) {
        if (preg_match_all("#([\p{L}0-9]+)#uis", $words, $matchesarray)) {
            $wordsArr = array_unique($matchesarray[0]);
            return $wordsArr;
        }
    }

    function load_comments() {
        # Загрузка комментариев аяксом
        $page = (int) $_GET['page'];
        $cauthor = (int) $_GET['cauthor'];
        $data = array();
        if ($_GET['search_comments']) {
            # Search
            $this->is_search = true;
            # Поиск
            global $wpdb;
            $search_query = "&s=" . $wpdb->escape($_GET['s']);
            $page = (int) $_GET['page'];
            $postPerPageQuery = "&posts_per_page=10";
            query_posts("paged=$page" . $postPerPageQuery . $search_query);

            global $defaultObjectSphinxSearch;
            $front = $defaultObjectSphinxSearch->frontend;
            if (!$front->used_match_any) {
                $data = $this->get_search_comments_data();
            }
        } else {
            # Recent
            $lastcmt = (int) $_GET['lastcmt'];
            $this->is_recent = true;
            if ($cauthor > 0) {
                $this->cauthor_id = $cauthor;
            }

            $data = $this->get_recent_comments_data($page);
        }
        if (sizeof($data)) {
            $this->get_comment_items($data, $lastcmt);
        }
        exit;
    }

    function get_comment_items($data, $lastcmt = 0) {
        $commentsTree = new CommentsTree();
        $commentRender = $commentsTree->commentRender;
        //User black list
        global $user_ID;
        if ($user_ID && $user_ID > 0) {
            if (function_exists('userbl_init')) {
                $user_bl = new UserBl();
                // я забанил
                $bl = $user_bl->getBlackList($user_ID);
                // меня забанили
                $tbl = $user_bl->getBlForTarget($user_ID);
            }
        }
        foreach ($data as $item) {

            //Автор комментария
            $com_a = '';
            if ($commentsTree->curr_uid && $commentsTree->curr_uid == $item->user_id) {
                $com_a = $commentsTree->comm_author_class;
            }

            //Скрытый комментарий
            $hideComments = new HideComments();
            $is_hide = $hideComments->isHide($item->comment_ID);
            $hide_class = $is_hide ? ' is-hide' : '';

            if (!$hide_class) {
                if (isset($bl[$item->user_id])) {
                    $hide_class = ' is-hide inbl';
                }
            }

            $ourbl_class = '';
            if (isset($tbl[$item->user_id])) {
                $ourbl_class = ' ourbl';
            }

            if ($lastcmt && $lastcmt <= $item->comment_ID) {
                continue;
            }
            ?>                    
            <li id="comment-<?php print $item->comment_ID ?>" class="cmt lvl-1<?php print $com_a . $hide_class . $ourbl_class ?>" data-uid="<?php print $item->user_id ?>">
                <?php print $commentRender->get_comment($item, 1, $this->is_recent, $this->is_search, $this->show_avatar) ?>
            </li>
            <?php
        }
    }

    function recentComments($page = 1) {
        $data = $this->get_recent_comments_data($page);
        $this->is_recent = true;
        $this->renderComments($data);
    }

    function userComments($page = 1, $uid = 0) {
        $this->commentQueryAction();
        $this->cauthor_id = $uid;
        $this->is_recent = true;
        $data = $this->get_recent_comments_data($page);
        $this->renderComments($data);
    }

    function renderComments($data) {
        if (sizeof($data)) {
            ?>
            <div id="comments">                                    
                <div id="commentform">
                    <div class="hide">
                        <input id="sjs" name="sjs" type="hidden" value="no" />
                        <?php
                        $editor_class = '';
                        if (current_user_can("editor") || current_user_can("administrator")) {
                            $editor_class = " editor";
                        }
                        $usebl_class = '';
                        if (class_exists('UserBl')) {
                            # Черные списки
                            $usebl_class = ' userbl';
                        }
                        ?>
                        <div id="respond" class="rspnd<?php print $editor_class . $usebl_class ?>"></div>
                    </div>
                </div>
                <ul class="commentlist<?php if ($this->ajax_load) print ' ajax-comments' ?>">
                    <?php $this->get_comment_items($data); ?>
                </ul>
            </div>
            <?php
        }
    }

    function commentsWidget($uid) {
        # Виджет комментариев на странице пользователя
        $this->comments_per_page = 5;
        $this->is_recent = true;
        $this->show_avatar = false;
        $this->cauthor_id = $uid;
        $this->ajax_load = false;

        $data = $this->get_recent_comments_data(1);
        $content = '';

        if (sizeof($data) > 0) {
            ob_start();
            $this->renderComments($data);
            $content = ob_get_contents();
            ob_end_clean();
        }
        return $content;
    }

    function get_recent_comments_data($page = 1) {
        if ($page < 1) {
            $page = 1;
        }

        global $wpdb;

        // Control protected comments.
        $sql_protected = " AND post_password = ''";


        // First comment.
        $start = $this->comments_per_page * ($page - 1);

        // Need to pick one more so that we can know if there's more comments exist.
        $size = $this->comments_per_page + 1;

        $user_sql = '';
        if ($this->cauthor_id > 0) {
            //User comments
            $user_sql = sprintf("AND user_id = %d ", $this->cauthor_id);
        }

        // Select comments on database.
        $comments_query = "SELECT 
	comment_date, comment_author, comment_author_email, comment_author_url, comment_ID, comment_post_ID, user_id, 
        comment_content, comment_type, comment_author_IP, comment_agent, comment_parent, comment_date_gmt 
        FROM $wpdb->comments, "
                . "$wpdb->posts "
                . "WHERE comment_approved = '1' "
                . "AND comment_post_ID = ID "
                . $user_sql
                . "AND post_status = 'publish'" . $sql_protected . " "
                . "ORDER BY comment_ID DESC "
                . "LIMIT " . $start . "," . $size;
        $comments = $wpdb->get_results($comments_query);

        return $comments;
    }

    function get_post_comments_data($post_id=0, $page = 1) {
        if ($page < 1) {
            $page = 1;
        }

        global $wpdb;

        // First comment.
        $start = $this->comments_per_page * ($page - 1);

        // Need to pick one more so that we can know if there's more comments exist.
        $size = $this->comments_per_page + 1;

        $user_sql = '';
        if ($this->cauthor_id > 0) {
            //User comments
            $user_sql = sprintf("AND user_id = %d ", $this->cauthor_id);
        }

        // Select comments on database.
        $comments_query = "SELECT 
	comment_date, comment_author, comment_author_email, comment_author_url, comment_ID, comment_post_ID, user_id, 
        comment_content, comment_type, comment_author_IP, comment_agent, comment_parent, comment_date_gmt 
        FROM $wpdb->comments "             
                . "WHERE comment_approved = '1' "
                . "AND comment_post_ID = $post_id "
                . $user_sql     
                . "ORDER BY comment_ID DESC "
                . "LIMIT " . $start . "," . $size;
        $comments = $wpdb->get_results($comments_query);

        return $comments;
    }

    function searchComments() {
        //post data
        //$found = array();
        $this->commentQueryAction();
        $this->is_search = true;

        $comments = $this->get_search_comments_data();
        $this->renderComments($comments);
    }

    function get_search_comments_data() {
        $ids = array();
        $post_ids = array();
        $comments = array();
        while (have_posts()) :
            the_post();
            global $post;
            $post_ids[$post->comment_id] = $post->ID;
            //$found[$post->comment_id] = $post;
            $ids[] = $post->comment_id;
        endwhile;
        if (sizeof($ids)) {
            $this->post_ids = $post_ids;
            // Select comments on database.
            global $wpdb;
            $comments_query = "SELECT * FROM $wpdb->comments WHERE comment_ID IN(" . implode(',', $ids) . ")";
            $comments = $wpdb->get_results($comments_query);
            $unsort_commetns = array();
            if (sizeof($comments)) {
                foreach ($comments as $comment) {
                    $unsort_commetns[$comment->comment_ID] = $comment;
                }
                $comments = array();
                foreach ($ids as $id) {
                    $comments[] = $unsort_commetns[$id];
                }
            }
        }
        return $comments;
    }

    function get_author_url_by_email($id_or_email, $author) {
        $url = $this->get_url_by_author($id_or_email);
        if ($url) {
            return '<a href="' . $url . '" title="Перейти к профилю ' . $author . '">' . $author . '</a>';
        } else {
            return $author;
        }
    }

    function get_url_by_author($id_or_email) {

        $user = get_user_by_email($id_or_email);
        if ($user) {
            return '/author/' . $user->user_nicename;
        } else {
            return '';
        }
    }

    function commentQueryAction() {
        # Добавляем информацию о посте вниз страницы.
        add_action('wp_footer', array($this, 'wp_post_head'));
    }

    function wp_post_head() {
        $page = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $this->currentPage = $page;
        ?>
        <script type="text/javascript">
            var search_sortby = "<?php print $this->search_sortby ?>";
            var search_query = "<?php print $this->search_query ?>";
            var cauthor_id = <?php print $this->cauthor_id ?>;
            var post_page = <?php print $page ?>;
        </script>
        <?php
    }

    function getCommentsTopCache() {
        global $themeCache;
        $tkey = 'getCommentsTop_' . $themeCache->key_hour();
        $cid = (int) $themeCache->cache('getCommentsTop', false, $tkey, 'def', $this);
        if ($cid && $cid > 0) {
            $comment = get_comment($cid);
            if ($comment && $comment->comment_approved == 1) {
                $this->renderCommentTop($comment);
                global $themepath;
                wp_enqueue_style('single', $themepath . '/css/single.css', array(), '1.02');
                wp_enqueue_script('single', $themepath . '/js/single.js', array(), '1.01', true);
            }
        }
    }

    function getCommentsTop() {
        /* Находим самый популярный комментарий за определённое время и отображаем его
         */
        $time = 12; //время в часах
        $minplus = 60; //Минимальный процент голосов ЗА
        $min_rating = 2; //Количество проголосовавших участников ЗА
        $limit = 5;


        $datet = date("Y-m-d H:i:s", time() - 3600 * $time);

        global $wpdb, $table_prefix;
        $sql = sprintf("SELECT comments.*, gdsr.*, posts.post_title FROM $wpdb->comments comments "
                . "INNER JOIN " . $table_prefix . "gdsr_data_comment gdsr ON gdsr.comment_id = comments.comment_ID  "
                . "INNER JOIN $wpdb->posts posts ON posts.ID = comments.comment_post_ID  "
                . "WHERE gdsr.user_recc_plus > %d "
                . "AND comments.comment_date > '%s' "
                . "AND comments.comment_approved = 1 "
                . "ORDER BY gdsr.user_recc_plus DESC "
                . "LIMIT %d", $min_rating, $datet, $limit);

        $top_comm = $wpdb->get_results($sql);
        $comments = array();

        $total = array();
        if (sizeof($top_comm) > 0) {
            foreach ($top_comm as $comment) {
                $comments[$comment->comment_ID] = $comment;
                /*
                  [user_recc_plus] => 5
                  [user_recc_minus] => 4
                  [visitor_recc_plus] => 3
                  [visitor_recc_minus] => 3
                 */
                $itog = $comment->user_recc_plus + $comment->visitor_recc_plus - $comment->user_recc_minus - $comment->visitor_recc_minus;
                $votes = $comment->user_recc_plus + $comment->visitor_recc_plus + $comment->user_recc_minus + $comment->visitor_recc_minus;
                $plusk = round(100 * $itog / $votes, 0);
                $total[$comment->comment_ID] = array('itog' => $itog, 'votes' => $votes, 'plusk' => $plusk);
            }
        }

        $total_count = 0;
        $cid = '';
        if (sizeof($total)) {
            foreach ($total as $key => $vaule) {
                if ($vaule['plusk'] >= $minplus) {
                    if (!$cid) {
                        $cid = $key;
                        $total_count = $vaule['votes'];
                    } else {
                        if ($vaule['votes'] > $total_count) {
                            $cid = $key;
                            $total_count = $vaule['votes'];
                        }
                    }
                }
            }
        }

        return $cid;
    }

    function renderCommentTop($comment) {
        $data = array($comment);
        $this->is_recent = true;
        $this->ajax_load = false;
        $this->renderComments($data);
    }

    /**
     * List comments.
     *
     * Used in the comments.php template to list comments for a particular post.
     *
     * @since 2.7.0
     *
     * @see WP_Query->comments
     *
     * @param string|array $args {
     *     Optional. Formatting options.
     *
     *     @type string $walker            The Walker class used to list comments. Default null.
     *     @type int    $max_depth         The maximum comments depth. Default empty.
     *     @type string $style             The style of list ordering. Default 'ul'. Accepts 'ul', 'ol'.
     *     @type string $callback          Callback function to use. Default null.
     *     @type string $end-callback      Callback function to use at the end. Default null.
     *     @type string $type              Type of comments to list.
     *                                     Default 'all'. Accepts 'all', 'comment', 'pingback', 'trackback', 'pings'.
     *     @type int    $page              Page ID to list comments for. Default empty.
     *     @type int    $per_page          Number of comments to list per page. Default empty.
     *     @type int    $avatar_size       Height and width dimensions of the avatar size. Default 32.
     *     @type string $reverse_top_level Ordering of the listed comments. Default null. Accepts 'desc', 'asc'.
     *     @type bool   $reverse_children  Whether to reverse child comments in the list. Default null.
     *     @type string $format            How to format the comments list.
     *                                     Default 'html5' if the theme supports it. Accepts 'html5', 'xhtml'.
     *     @type bool   $short_ping        Whether to output short pings. Default false.
     *     @type bool   $echo              Whether to echo the output or return it. Default true.
     * }
     * @param array $comments Optional. Array of comment objects.
     */
    function wp_list_comments($args = array(), $comments = null) {
        global $wp_query, $comment_alt, $comment_depth, $comment_thread_alt, $overridden_cpage, $in_comment_loop;

        $in_comment_loop = true;

        $comment_alt = $comment_thread_alt = 0;
        $comment_depth = 1;

        $defaults = array(
            'walker' => null,
            'max_depth' => '',
            'style' => 'ul',
            'callback' => null,
            'end-callback' => null,
            'type' => 'all',
            'page' => '',
            'per_page' => '',
            'avatar_size' => 32,
            'reverse_top_level' => null,
            'reverse_children' => '',
            'format' => current_theme_supports('html5', 'comment-list') ? 'html5' : 'xhtml',
            'short_ping' => false,
            'echo' => true,
        );

        $r = wp_parse_args($args, $defaults);

        // Figure out what comments we'll be looping through ($_comments)
        if (null !== $comments) {
            $comments = (array) $comments;
            if (empty($comments))
                return;
            if ('all' != $r['type']) {
                $comments_by_type = separate_comments($comments);
                if (empty($comments_by_type[$r['type']]))
                    return;
                $_comments = $comments_by_type[$r['type']];
            } else {
                $_comments = $comments;
            }
        } else {
            if (empty($wp_query->comments))
                return;
            if ('all' != $r['type']) {
                if (empty($wp_query->comments_by_type))
                    $wp_query->comments_by_type = separate_comments($wp_query->comments);
                if (empty($wp_query->comments_by_type[$r['type']]))
                    return;
                $_comments = $wp_query->comments_by_type[$r['type']];
            } else {
                $_comments = $wp_query->comments;
            }
        }

        if ('' === $r['per_page'] && get_option('page_comments'))
            $r['per_page'] = get_query_var('comments_per_page');

        if (empty($r['per_page'])) {
            $r['per_page'] = 0;
            $r['page'] = 0;
        }

        if ('' === $r['max_depth']) {
            if (get_option('thread_comments'))
                $r['max_depth'] = get_option('thread_comments_depth');
            else
                $r['max_depth'] = -1;
        }

        if ('' === $r['page']) {
            if (empty($overridden_cpage)) {
                $r['page'] = get_query_var('cpage');
            } else {
                $threaded = ( -1 != $r['max_depth'] );
                $r['page'] = ( 'newest' == get_option('default_comments_page') ) ? get_comment_pages_count($_comments, $r['per_page'], $threaded) : 1;
                set_query_var('cpage', $r['page']);
            }
        }
        // Validation check
        $r['page'] = intval($r['page']);
        if (0 == $r['page'] && 0 != $r['per_page'])
            $r['page'] = 1;

        if (null === $r['reverse_top_level'])
            $r['reverse_top_level'] = ( 'desc' == get_option('comment_order') );

        extract($r, EXTR_SKIP);

        if (empty($walker))
            $walker = new Walker_Comment;

        if (class_exists('CommentsTree')) {
            if (sizeof($_comments)) {
                $commentsTree = new CommentsTree($_comments);
                $output = $commentsTree->paged_walk($page, $per_page);
            }
            $wp_query->max_num_comment_pages = $commentsTree->max_pages;
        } else {
            $output = $walker->paged_walk($_comments, $max_depth, $page, $per_page, $r);
            $wp_query->max_num_comment_pages = $walker->max_pages;
        }

        $in_comment_loop = false;

        if ($r['echo'])
            echo $output;
        else
            return $output;
    }

}

class CommentChilds {
    # Считаем количество ответов на комментарий

    public $comments = array();
    public $comm_parr = array();
    public $parrents = array();
    public $childs = array();
    public $counter = 0;

    public function __construct($comments) {
        foreach ($comments as $comment) {
            $this->parrents[$comment->comment_parent][] = $comment->comment_ID;
            $this->comm_parr[$comment->comment_ID] = $comment->comment_parent;
            $this->comments[$comment->comment_ID] = $comment;
        }
    }

    function tree($parrent) {
        if (isset($this->parrents[$parrent])) {
            $childs = $this->parrents[$parrent];
            foreach ($childs as $cid) {
                $this->counter += 1;
                $comment = $this->comments[$cid];
                if ($comment->comment_parent == $parrent) {
                    $this->childs[] = (int) $cid;
                    $this->tree($comment->comment_ID);
                }
            }
        }
    }

}

class CommentsTree {

    var $comments = array();
    var $comm_parr = array();
    var $parrents = array();
    var $counter = 0;
    var $view_depth = 5;
    var $commentRender;
    var $max_pages = 1;
    var $start = 0;
    var $end = 0;
    var $post_author = 0;
    var $post_author_class = ' pau';
    var $curr_uid = 0;
    var $comm_author_class = ' cau';

    public function __construct($comments = array()) {
        if (sizeof($comments)) {
            foreach ($comments as $comment) {
                $this->parrents[$comment->comment_parent][] = $comment->comment_ID;
                $this->comm_parr[$comment->comment_ID] = $comment->comment_parent;
                $this->comments[$comment->comment_ID] = $comment;

                # Автор материала
                if (!$this->post_author) {
                    if ($post = get_post($comment->comment_post_ID)) {
                        $this->post_author = $post->post_author;
                    }
                }
            }
        }

        $this->commentRender = new CommentRender();

        $user = wp_get_current_user();
        if ($user->ID) {
            $this->curr_uid = $user->ID;
        }
    }

    function paged_walk($page_num, $per_page) {
        $total_top = count($this->comm_parr);
        if ($page_num < 1 || $per_page < 0) {
            // No paging
            $paging = false;
            $this->max_pages = 1;
            $this->start = 0;
            $this->end = $total_top;
        } else {
            $paging = true;
            $this->start = ( (int) $page_num - 1 ) * (int) $per_page;
            $this->end = $this->start + $per_page;
            $this->max_pages = ceil($total_top / $per_page);
        }

        return $this->tree(0, 1);
    }

    function tree($parrent, $level = 2) {
        $ret = '';
        $count = -1;
        if (isset($this->parrents[$parrent])) {
            if ($parrent != 0) {
                $ret .= "<ul class=\"cld\">\n";
            }
            $childs = $this->parrents[$parrent];

            //User black list
            global $user_ID;
            if ($user_ID && $user_ID > 0) {
                if (function_exists('userbl_init')) {
                    $user_bl = new UserBl();
                    $bl = $user_bl->getBlackList($user_ID);
                    $tbl = $user_bl->getBlForTarget($user_ID);
                }
            }

            foreach ($childs as $cid) {

                // Только для главного цикла. Разметка на страницы
                if ($parrent == 0) {
                    $count++;

                    if ($count < $this->start)
                        continue;

                    if ($count >= $this->end)
                        break;
                }

                $this->counter += 1;

                $comment = $this->comments[$cid];
                if ($comment->comment_parent == $parrent) {
                    $mx = ($level == 5) ? " mx" : "";
                    //Автор материала
                    $post_a = '';
                    if ($this->post_author == $comment->user_id) {
                        $post_a = $this->post_author_class;
                    }

                    //Автор комментария
                    $com_a = '';
                    if ($this->curr_uid && $this->curr_uid == $comment->user_id) {
                        $com_a = $this->comm_author_class;
                    }

                    //Скрытый комментарий
                    $hideComments = new HideComments();
                    $is_hide = $hideComments->isHide($comment->comment_ID);
                    $hide_class = $is_hide ? ' is-hide' : '';

                    if (!$hide_class) {
                        if (isset($bl[$comment->user_id])) {
                            $hide_class = ' is-hide inbl';
                        }
                    }

                    $ourbl_class = '';
                    if (isset($tbl[$comment->user_id])) {
                        $ourbl_class = ' ourbl';
                    }

                    $ret .= '<li id="comment-' . $comment->comment_ID . '" class="cmt lvl-' . $level . $post_a . $com_a . $hide_class . $ourbl_class . $mx . '" data-uid="' . $comment->user_id . '">';
                    $ret .= $this->commentRender->get_comment($comment, $level);
                    $ret .= "</li>\n";
                    if ($level < $this->view_depth) {
                        $ret .= $this->tree($comment->comment_ID, $level + 1);
                    } else {
                        $ret .= $this->afer_tree($comment->comment_ID, $level + 1);
                    }
                }
            }
            if ($parrent != 0) {
                $ret .= "</ul>\n";
            }
        }
        return $ret;
    }

    function afer_tree($parrent = 0, $level = 0) {
        $childs = $this->parrents[$parrent];
        $ret = '';

        //User black list
        global $user_ID;
        if ($user_ID && $user_ID > 0) {
            if (function_exists('userbl_init')) {
                $user_bl = new UserBl();
                $bl = $user_bl->getBlackList($user_ID);
                $tbl = $user_bl->getBlForTarget($user_ID);
            }
        }

        if (sizeof($childs)) {
            foreach ($childs as $cid) {
                $this->counter += 1;

                $comment = $this->comments[$cid];

                if ($comment->comment_parent == $parrent) {

                    //Автор материала
                    $post_a = '';
                    if ($this->post_author == $comment->user_id) {
                        $post_a = $this->post_author_class;
                    }

                    //Автор комментария
                    $com_a = '';
                    if ($this->curr_uid && $this->curr_uid == $comment->user_id) {
                        $com_a = $this->comm_author_class;
                    }

                    //Скрытый комментарий
                    $hideComments = new HideComments();
                    $is_hide = $hideComments->isHide($comment->comment_ID);
                    $hide_class = $is_hide ? ' is-hide' : '';

                    if (!$hide_class) {
                        if (isset($bl[$comment->user_id])) {
                            $hide_class = ' is-hide inbl';
                        }
                    }

                    $ourbl_class = '';
                    if (isset($tbl[$comment->user_id])) {
                        $ourbl_class = ' ourbl';
                    }

                    $ret .= '<li id="comment-' . $comment->comment_ID . '" class="cmt lvl-' . $level . $post_a . $com_a . $hide_class . $ourbl_class . ' mx" data-uid="' . $comment->user_id . '">';
                    $ret .= $this->commentRender->get_comment($comment, $level);
                    $ret .= "</li>\n";
                    $ret .= $this->afer_tree($comment->comment_ID, $level + 1);
                }
            }
        }
        return $ret;
    }

}

class CommentRender {
    /*
     * Тело комментария
     * 
     *     [0] => stdClass Object
      (
      [comment_ID] => 324449
      [comment_post_ID] => 42185
      [comment_author] => Союз Таможенный
      [comment_author_email] => 35081@pandoraopen.ru
      [comment_author_url] => http://vk.com/id436853148
      [comment_author_IP] => 217.118.93.140
      [comment_date] => 2018-02-28 21:26:29
      [comment_date_gmt] => 2018-02-28 18:26:29
      [comment_content] => Уважаемый Брахман! Искал, но не нашел возможность автоматического отслеживания "Ответов" на свои комментарии. Есть ли такой сервис на вашем сайте?
      [comment_karma] => 0
      [comment_approved] => 1
      [comment_agent] => Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.137 YaBrowser/17.4.1.1026 Yowser/2.5 Safari/537.36
      [comment_type] =>
      [comment_parent] => 0
      [user_id] => 35081
      )
     */

    public function get_comment($comment, $level = 1, $is_recent = false, $is_search = false, $show_avatar = true) {

        # Комментарий
        $cid = (int) $comment->comment_ID;
        # Пользователь
        $uid = (int) $comment->user_id;
        $user = get_userdata($uid);

        # Ссылка на профиль пользвоателя
        $author_link = '/author/' . $user->user_nicename;

        if ($show_avatar) {
            # Карма
            $carma = getCarma($uid);

            # Аватар
            $avSize = $level == 1 ? 80 : 40;
            $avatar = get_avatar($uid, $avSize);
            if (preg_match('|<img[^>]+src="([^"]+)"|', $avatar, $match)) {
                //Создаём ленивые картинки
                $avatar = '<img src="" data-src="' . $match[1] . '" class="avatar lazyload" width="' . $avSize . '" height="' . $avSize . '">';
            }
        }
        # Флажок страны
        $country_code = '';
        $country_exist = false;
        $comment_ip = $comment->comment_author_IP;
        if (function_exists('getGeoData')) {
            $data = getGeoData($comment_ip);
            $country_code = $data['country_code'];
            $country = getRuCountryByCode($country_code);
            $country_path = '/wp-content/lib/geoip/flags-iso/24/' . strtolower($country_code) . '.png';
            if (file_exists(ABSPATH . $country_path)) {
                $country_exist = true;
            }
        }

        # Время
        # TODO отвязаться от global и сделать время (минут назад, только что)        
        # Рейтинг
        $comment_data = wp_gdget_comment($cid);
        if (count($comment_data) == 0) {
            GDSRDatabase::add_empty_comment($cid, $comment->comment_post_ID);
            $comment_data = wp_gdget_comment($cid);
        }

        /* if (current_user_can("administrator")) {
          //p_r($comment_data);
          } */

        $votes = $comment_data->user_recc_plus + $comment_data->user_recc_minus + $comment_data->visitor_recc_plus + $comment_data->visitor_recc_minus;
        $score = $comment_data->user_recc_plus - $comment_data->user_recc_minus + $comment_data->visitor_recc_plus - $comment_data->visitor_recc_minus;
        $votes_plus = $comment_data->user_recc_plus + $comment_data->visitor_recc_plus;
        $votes_minus = $comment_data->user_recc_minus + $comment_data->visitor_recc_minus;

        $score_class = "sz";

        if ($score > 0) {
            $score_class = "sp";
        } else if ($score < 0) {
            $score_class = "sm";
        }

        if ($votes > 0) {
            $score_class = "ne " . $score_class;
        }
        $respond = '#respond-' . $cid;
        $answer_link = $respond;
        $author_comment_link = '#comment-' . $cid;
        $answers = '';
        if ($comment->comment_parent) {
            $parent = get_comment($comment->comment_parent);
            $parent_link = '#comment-' . $comment->comment_parent;
        }

        $comment_text = apply_filters('comment_text', $comment->comment_content, $comment);

        if ($is_recent || $is_search) {
            $post_title = get_the_title($comment->comment_post_ID);
            global $commentLinks;
            if ($commentLinks) {
                $comment_link = $commentLinks->link($comment->comment_ID, false);
            } else {
                $comment_link = get_permalink($comment->comment_post_ID);
            }

            $parent_link = $comment_link . $parent_link;
            $answer_link = $comment_link . $respond;
            $author_comment_link = $comment_link . $author_comment_link;

            # Ответы на комментарий
            global $wpdb;
            $sql = sprintf("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_parent = %d", $comment->comment_ID);
            $answers = $wpdb->get_var($sql);
            if ($answers) {
                $answer_text = '&nbsp;ответ' . TextOp::okonchanie($answers);
            }
            if ($is_search) {
                # Выделение поисковых запросов
                global $defaultObjectSphinxSearch;
                if ($defaultObjectSphinxSearch->frontend) {
                    $post_id = $this->post_ids[$comment->comment_ID];
                    $post_title_arr = $defaultObjectSphinxSearch->frontend->get_excerpt(array($post_id => $post_title), true);
                    $post_title = $post_title_arr[$post_id];
                    $comment_text_arr = $defaultObjectSphinxSearch->frontend->get_excerpt(array($post_id => $comment_text));
                    $comment_text = '<p>' . $comment_text_arr[$post_id] . '</p>';
                }
            }
        }

        if (class_exists('CtgHumanDate')) {
            $time = strtotime(gmdate('Y-m-d H:i:s', ( time() + ( get_option('gmt_offset') * HOUR_IN_SECONDS ))));
            $comment_time = CtgHumanDate::humanDate($time, strtotime($comment->comment_date));
        } else {
            $comment_time = $this->get_comment_time($comment);
        }


        ob_start();

        if ($show_avatar) {
            ?>
            <div class="av">
                <a href="<?php print $author_link ?>"><?php print $avatar ?></a>
                <div class="umeta<?php echo $ucarma = ($carma[1] < 0) ? " minus" : " plus"; ?>">
                    <span class="urating" title ="Известность"><?php print CarmaIcon::rating($carma[1]) ?><?php print $carma[0] ?></span>
                    <span class="ucarma" title="Карма" ><?php print CarmaIcon::carma($carma[1]) ?><?php print $carma[1] ?></span>
                </div>
            </div>
        <?php } ?>
        <div class="ch">
            <div class="tp">                                                 
                <?php
                // Флаг
                if ($country_exist):
                    ?><span class="cntr"><img src="" data-src="<?php print $country_path ?>" class="lazyload" title="<?php echo $country ?>" /></span><?php
                endif
                // Автор
                ?> 
                <span class="usrs"><a class="athr" href="<?php print $author_link ?>"><?php print $comment->comment_author ?></a> <?php
                    // Родитель
                    if ($comment->comment_parent) {
                        ?>
                        <?php if ($is_recent || $is_search) { ?>
                            &rarr; <a class="prnt ext" href="<?php print $parent_link ?>"><?php print $parent->comment_author ?> <i class="icon icon-link-ext"></i></a>
                        <?php } else { ?>
                            &rarr; <a class="prnt" href="<?php print $parent_link ?>"><?php print $parent->comment_author ?></a>
                        <?php } ?>
                    <?php } ?>
                </span> <?php
                // Дата
                ?>
                <a class="dt" href="<?php print $author_comment_link ?>"><?php print $comment_time ?></a>
                <?php if ($is_recent || $is_search) { ?>
                    <span class="pl">к посту <a href="<?php print $author_comment_link ?>"><?php print $post_title ?></a></span> 
                <?php } ?>
                <?php
                // Меню управления комментарием                    
                ?>
                <div class="dropdown">
                    <button class="dropdown-toggle" data-toggle="dropdown"><b class="caret"></b></button>                    
                    <ul class="dropdown-menu"></ul>
                </div>


            </div>  
            <?php if ($comment->comment_approved == '0') : ?> <em class="noapprvd">Спасибо! Ваш
                    комментарий отправлен на модерацию.</em> <?php endif; ?> 
            <div class="t">
                <?php print $comment_text; ?>
            </div>             
            <div class="bnm">                
                <div class="vtb">
                    <div class="vt">
                        <div>
                            <a class="up" href="#" title="Поддержать"><b class="upi icon-thumbs-up"></b><span class="cnt"><i><?php if ($votes_plus) print $votes_plus ?></i></span></a>  
                            <a class="dw" href="#" title="Не согласиться"><b class="dwi icon-thumbs-down"></b><span class="cnt"><i><?php if ($votes_minus) print $votes_minus ?></i></span></a>                         
                        </div>
                        <div class="vc"></div>
                    </div>
                    <div class="ttl <?php print $score_class ?>"><?php
                        if ($votes > 0) {
                            /* if ($score > 0) {
                              print "+";
                              } */
                            print $score;
                        }
                        ?>
                    </div>
                </div>
                <div id="respond-<?php print $cid ?>" class="rsd">                    
                    <?php if ($is_recent || $is_search) { ?>
                        <?php if ($answers) { ?>
                            <?php print $answers ?> <a class="ext" href="<?php print $answer_link ?>"><?php print $answer_text; ?> <i class="icon icon-link-ext"></i></a>
                        <?php } else { ?>
                            <a class="ext" href="<?php print $answer_link ?>">Ответить <i class="icon icon-link-ext"></i></a>
                            <?php } ?>
                        <?php } else { ?>                    
                        <a href="<?php print $answer_link ?>">Ответить</a>
                    <?php } ?>
                </div>
            </div>
        </div> 
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    function get_comment_time($_comment) {
        global $comment;
        $comment = $_comment;

        ob_start();

        comment_date('j F Y');
        print " в ";
        comment_time();

        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

}

class CarmaIcon {
    /*
     * Иконки кармы
     * 
     * 0 :)  icon-emo-happy
     * 10 :D icon-emo-grin
     * 100 ;) icon-emo-squint
     * 1000 ;D icon-emo-laugh
     * 10000 0;) icon-emo-saint
     * Минус
     * 10 :( icon-emo-unhappy
     * 100 ;( icon-emo-angry
     * 1000 ;:) icon-emo-devil
     * 
     */

    static function carma($carma) {

        if ($carma >= 100000) {
            $icon = 'icon-emo-saint';
        } else if ($carma >= 10000) {
            $icon = 'icon-emo-sunglasses';
        } else if ($carma >= 1000) {
            $icon = 'icon-emo-laugh';
        } else if ($carma >= 100) {
            $icon = 'icon-emo-squint';
        } else if ($carma >= 10) {
            $icon = 'icon-emo-squint';
        } else if ($carma >= 0) {
            $icon = 'icon-emo-happy';
        } else if ($carma > -10) {
            $icon = 'icon-emo-unhappy';
        } else if ($carma > -100) {
            $icon = 'icon-emo-angry';
        } else {
            $icon = 'icon-emo-devil';
        }
        $ret = '<i class="' . $icon . '"></i>';
        return $ret;
    }

    static function rating($carma) {
        $icon = 'icon-star';
        if ($carma <= 10) {
            $icon = 'icon-star-empty';
        }
        $ret = '<i class="' . $icon . '"></i>';
        return $ret;
    }

}
