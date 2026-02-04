<?php
class PandoraAdmin {

    function init() {
        # Валидация материалов        
        // Поиск повтора
        add_action('admin_notices', array($this, 'povtor_post_admin_notices'));
        add_filter('wp_insert_post_data', array($this, 'povotor_post_data_validator'), 99);
        // Пустой заголовок
        add_action('admin_notices', array($this, 'no_title_admin_notices'));
        add_filter('wp_insert_post_data', array($this, 'post_title_data_validator'), 99);
    }

    function povtor_post_admin_notices() {
        if (!isset($_GET['povtor_message']))
            return;

        $ps = new PandoraSearch();

        $povtor = $ps->find_current_post_povtor($text);
        if ($povtor) {
            $pre = '<h3>Запись <b>сохранена</b> в статусе "<b>На утверждении</b>".</h3>';
            $text = $pre . $text;
            echo '<div id="notice" class="error"><p>' . $text . '</p></div>';
        } else {
            echo '<div id="notice" class="updated"><p>' . $text . '</p></div>';
        }
    }

    function povotor_post_data_validator($data) {
        if ($data['post_type'] == 'post' && $data['post_status'] != 'pending') {
            if ($data['post_status'] == 'trash' || $data['post_status'] == 'private') {
                return $data;
            }

            global $post;
            $pid = isset($post->ID) ? $post->ID : '';
            $ps = new PandoraSearch();
            $povtor = $ps->find_post_povtor($data['post_content'], $info, $pid);
            if ($povtor) {
                if (isset($povtor->pid) && $povtor->pid == $pid) {
                    return $data;
                }

                $editortext = 'Редактор опубликовал повтор записи';
                $mod_text = '';
                if (!current_user_can("edit_others_pages")) {
                    $data['post_status'] = 'pending';                    
                    $editortext = 'Повтор записи';
                    $mod_text = ' добвален на модерацию';
                }
                add_filter('redirect_post_location', array($this, 'povtor_post_redirect_filter'), '99');

                $message_headers[] = 'From:' . 'olegbegg@gmail.com';
                $message_headers[] = 'Content-type: text/html; charset=utf-8';


                $title = $data['post_title'];
                $guid = $data['guid'];
                $site_url = get_site_url();
                if ($pid) {
                    $edit_url = $site_url . '/wp-admin/post.php?post=' . $pid . '&action=edit&povtor_message=1';
                    $edit = 'Изменить: <a href="' . $edit_url . '">' . $edit_url . '</a><br />';
                }
                $email = 'report@pandoraopen.ru';



                $subject = $editortext . ' "' . $title . '"' . $mod_text;

                $notify_message = $info . '<br /><br />Адрес статьи: <a href="' . $guid . '">' . $guid . '</a><br />' . $edit;

                //Отправка уведомления в базу
                global $flagReport;
                if ($flagReport) {
                    $flagReport->povtor_report($subject, $notify_message);
                }
                @wp_mail($email, $subject, $notify_message, $message_headers);
            }
        }
        return $data;
    }

    function no_title_admin_notices() {
        if (isset($_GET['no_title'])) {
            echo '<div id="notice" class="error"><p>Введите заголовок записи</p></div>';
        }
    }

    function post_title_data_validator($data) {

        if ($data['post_type'] == 'post' && $data['post_status'] != 'draft') {

            $no_title = false;

            if (!$data['post_title']) {
                $no_title = true;
            } else if (!preg_match('|[a-яА-Яa-zA-Z0-9]+|', $data['post_title'])) {
                $no_title = true;
            }

            if ($no_title) {
                $data['post_status'] = 'draft';
                add_filter('redirect_post_location', array($this, 'no_title_post_redirect_filter'), '99');
            }
        }
        return $data;
    }

    function no_title_post_redirect_filter($location) {
        remove_filter('redirect_post_location', __FILTER__, '99');
        return add_query_arg('no_title', 1, $location);
    }

    function povtor_post_redirect_filter($location) {
        remove_filter('redirect_post_location', __FILTER__, '99');
        return add_query_arg('povtor_message', 1, $location);
    }

}
