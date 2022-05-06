<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}

if (isset($_POST['oper'])) {

    $oper = $_POST['oper'];
    $id = intval($_POST['id']);





    if ($oper == 'crowd_submit') {

        $array_crowd = array('actor_crowdsource' => 'actors_crowd', 'moviespgcrowd' => 'movies_pg_crowd', 'review_crowdsource' => 'review_crowd');

        $type = $_POST['type'];
        $data = $_POST['data'];

        //////////get user

        $error = [];

        $array_user = Crowdsource::get_user();
        $user_id = $array_user['id'];

        if ($array_crowd[$type]) {

            $data_obj = json_decode($data, 1);

            $comment = $data_obj['comment'];
            if ($comment) {
                $comment = strip_tags($comment);
                $comment = Crowdsource::crop_text($comment, 1000);
                $data_obj['comment'] = $comment;
            }

            $image = $data_obj['image'];
            if ($image) {
                if (!filter_var($image, FILTER_VALIDATE_URL)) {
                    $data_obj['image'] = '';
                    $error['image'] = 'image url is not valid';
                }
            }
            $link = $data_obj['link'];
            if ($link) {
                if (!filter_var($link, FILTER_VALIDATE_URL)) {
                    $data_obj['link'] = '';
                    $error['link'] = 'link url is not valid';
                }
            }

            $oper_insert_colums = "";
            $oper_insert_data = "";
            $data_array = [];
            $oper_update = "";
            $reqest_field = '';


            if ($array_crowd[$type] == 'review_crowd') {
                $array_movies = [];


                foreach ($data_obj as $i => $v) {
                    if (strstr($i, 'crowd_movie_autoinput')) {

                        unset($data_obj[$i]);
                    }


                    if (strstr($i, 'movie_link')) {

                        $m_id = substr($i, 16);
                        $array_movies[$m_id] = $v;
                        unset($data_obj[$i]);
                    }
                }
                $data_obj['review_id'] = $id;
                $reqest_field = 'review_id';

                $data_obj['movies'] = json_encode($array_movies);
            }

            if ($array_crowd[$type] == 'actors_crowd') {
                ///add name from autors

                $sql = "SELECT `name` FROM `data_actors_imdb` where id = " . $id;
                $name_array = Pdo_an::db_fetch_row($sql);
                $actor_name = $name_array->name;

                $data_obj['actor_name'] = $actor_name;
                $data_obj['actor_id'] = $id;
                $reqest_field = 'actor_id';
            } else if ($array_crowd[$type] == 'movies_pg_crowd') {
                ///add name from autors

                $sql = "SELECT * FROM `data_movie_imdb` where id = " . $id;
                $name_array = Pdo_an::db_fetch_row($sql);
                $title = $name_array->title;
                $imdb_id = $name_array->movie_id;

                $data_obj['movie_title'] = $title;
                $data_obj['movie_id'] = $imdb_id;
                $data_obj['rwt_id'] = $id;
                $reqest_field = 'rwt_id';
            }
            
            $cm = new CriticMatic();
            $remote_ip = $cm->get_remote_ip();

            $data_obj['user'] = $user_id;
            $data_obj['status'] = 0;
            $data_obj['add_time'] = time();
            $data_obj['ip'] = $remote_ip;

            // Check ip


            if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
            }

            if (!class_exists('CriticFront')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
            }
            $cfront = new CriticFront();

            $ip = $remote_ip;
            $ip_item = $cfront->cm->get_ip($ip);

            $status = 0;
//        if ($ip_item) {
//
//            $ip_type = $ip_item->type;
//            if ($ip_type == 3) {
//                //Black list -> Trash
//                $status = 2;
//            } else if ($ip_type == 2) {
//                //Gray list -> Draft
//                $status = 0;
//            } else if ($ip_type == 1) {
//                //White list -> Publish
//                $status = 1;
//            }
//
//            $data_obj['status']=$status;
//        }




            if ($data_obj) {
                foreach ($data_obj as $row => $value) {
                    if ($row != 'button submit_user_data' && $row != 'button close') {
                        $oper_insert_colums .= ",`" . $row . "`";
                        $oper_insert_data .= ",?";
                        $data_array[] = $value;
                        $oper_update .= ",`" . $row . "`=?";
                    }
                }

                if ($oper_insert_colums && $user_id) {

                    $sql = "SELECT id FROM `data_" . $array_crowd[$type] . "` where `user` = ? and `" . $reqest_field . "` = ? limit 1";



                    $rw = Pdo_an::db_results_array($sql, array($user_id, $id));
                    if ($rw[0]['id']) {
                        $uddate_id = $rw[0]['id'];
                    }
                }

                if ($uddate_id) {
                    // echo 'update';

                    $oper_update = substr($oper_update, 1);

                    $inser_sql = "UPDATE `data_" . $array_crowd[$type] . "` SET " . $oper_update . "  WHERE id = " . $uddate_id;
                } else {
                    $inser_sql = "INSERT INTO `data_" . $array_crowd[$type] . "`(`id` " . $oper_insert_colums . " ) VALUES (NULL " . $oper_insert_data . " )";
                }


                Pdo_an::db_results_array($inser_sql, $data_array);

                if ($status == 1 && $uddate_id) {

                    ///rebuild cache
                    Crowdsource::rebuild_cache($uddate_id, $array_crowd[$type]);
                }



                if ($error) {
                    echo json_encode(array('error' => $error));
                }
            }
        }
    } else if ($oper == 'get_search_movie') {
        $m_id = intval($_POST['id']);
        $r_id = intval($_POST['r_id']);

        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        }

        if (!class_exists('CriticFront')) {
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
        }

        $cfront = new CriticFront();

        $movie_tmpl = Crowdsource::get_movie_template($r_id, $m_id, $cfront);

        echo $movie_tmpl;
    } else if ($oper == 'review_crowd') {
        $id = intval($_POST['id']);


        //////movie
        if (isset($_POST['movie']))
            ;
        {
            $movie_id = $_POST['movie'];
            $ma_id = intval($movie_id);
        }


        if (isset($_POST['admin_view'])) {
            $rid = intval($_POST['admin_view']);

            $sql = "select * from `data_review_crowd` where id=" . $rid;
            $row = Pdo_an::db_fetch_row($sql);
            $id = $row->review_id;
            $user_id = $row->user;
            $array_user['id'] = $user_id;
            $array_user['admin'] = 1;
            $only_edit = 1;
        } else {
            if (!$id || !$ma_id) {
                return;
            }

            $array_user = Crowdsource::get_user();

            $user_id = $array_user['id'];
        }


        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        }

        if (!class_exists('CriticFront')) {
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
        }

        $cfront = new CriticFront();

        $critic_id = $id;
        $title = '';
        $content = '';
        $link = '';

        $post = $cfront->cm->get_post_and_author($critic_id);
        if ($post) {
            $ma = $cfront->get_ma();
            if (!$ma_id) {

                $top_movie = $cfront->cm->get_top_movie($post->id);
                if ($top_movie) {

                    $ma_id = $top_movie;
                }
            }

            ////get movies
            $sql = "SELECT * FROM `data_review_crowd` where `user` = ? and `review_id` = ? limit 1";

            $movie_tmpl = '';

            $rw = Pdo_an::db_results_array($sql, array($user_id, $id));

            $i = 0;
            if ($rw[0]['id']) {

                if ($rw[0]['movies']) {
                    $amovies = json_decode($rw[0]['movies'], 1);

                    foreach ($amovies as $m_id => $mstat) {


                        $main = '';
                        if ($i == 0)
                            $main = 1;
                        $movie_tmpl .= Crowdsource::get_movie_template($id, $m_id, $cfront, $main, $mstat);
                        $i++;
                    }
                }



                if ($rw[0]['status'] > 0 && !$only_edit) {
                    ///return
                    $content = '<p class="user_message_info">You already left a comment.</p><div class="submit_data"><button class="button close" >Close</button></div>';

                    echo $content;
                    return;
                }
            }
            if (!$movie_tmpl) {
                $movie_tmpl .= Crowdsource::get_movie_template($id, $ma_id, $cfront, 1);
            }




            $meta_state = $cfront->cm->get_critic_meta_state($critic_id, $ma_id);

            if ($post->author_type == 0) {
                //Staff
                $content = $cfront->get_feed_templ($post, $ma_id, true, 2);
            } else if ($post->author_type == 1) {
                //Pro
                $content = $cfront->get_feed_templ($post, $ma_id, false, 2);
            } else if ($post->author_type == 2) {
                //Audience
                $content = $cfront->get_audience_templ($post, '', 2);
            }
        }

        ////get user




        echo '<h2 class="r_info">Review info</h2>';

        echo '<div class="blockquote" >' . $content . '</div>';
        if (isset($_POST['robot'])) {
            if ($_POST['robot'] == 1) {

                echo '<p class="robot_info">This review was attached automatically by our robot.</p>';
            }
        }

        echo '<h2>Did you find something wrong?</h2>';

        $array_rows = array(
            'source_link' => array('type' => 'input', 'placeholer' => 'source link', 'title' => 'Add a mirror link to the source if you have one.'),
        );
        $inner_content = Crowdsource::front('review_crowdsource', $array_rows, $array_user, $id, 1);


        $content = '';
        $array_rows = array(
            'broken_link' => array('type' => 'big_checkbox', 'desc' => 'BROKEN LINK'),
        );
        $content .= Crowdsource::front('review_crowdsource', $array_rows, $array_user, $id, 1, $inner_content);


        $content_input = '';

        if (!$only_edit) {
            $array_rows = array(
                'incorrect_item' => array('class' => ' crowd_movie_autoinput', 'type' => 'input', 'placeholer' => 'type movies, tv, games', 'title' => 'Add other items to the review'),
            );

            $content_input = Crowdsource::front('', $array_rows, $array_user, $id, 1, $inner_content);
        }




        $inner_content = '<div class="check_container_main">' . $movie_tmpl . '</div>
' . $content_input . '<div class="crowd_items_search"><div class="advanced_search_menu crowd_items" style="display: none;">
                        <div class="advanced_search_first"></div>
                        <div class="advanced_search_data advanced_search_hidden"></div>
                    </div></div>';



        $array_rows = array(
            'incorrect_item' => array('type' => 'big_checkbox', 'desc' => 'INCORRECT OR INCOMPLETE ITEM(S) REVIEWED'),
        );
        $content .= '<div style="z-index: 2;position: relative;">' . Crowdsource::front('review_crowdsource', $array_rows, $array_user, $id, 1, $inner_content) . '</div>';



        $array_rows = array(
            'remove' => array('type' => 'checkbox', 'desc' => 'Remove the review from RWT?'),
            'blur' => array('type' => 'checkbox', 'desc' => 'Blur the content?'),
        );
        $inner_content = Crowdsource::front('review_crowdsource', $array_rows, $array_user, $id, 1);


        $array_rows = array(
            'irrelevant' => array('type' => 'big_checkbox', 'desc' => 'OFFENSIVE OR IRRELEVANT CONTENT'),
        );
        $content .= Crowdsource::front('review_crowdsource', $array_rows, $array_user, $id, 1, $inner_content);


        $array_rows = array('comment' => array('type' => 'textarea'));
        $content .= Crowdsource::front('review_crowdsource', $array_rows, $array_user, $id, $only_edit);


        echo $content;
    } else if ($oper == 'actor_crowd') {

        ///get ethnic array
        $sql = "SELECT val  FROM `options` where id = 4";
        $r = Pdo_an::db_fetch_row($sql);

        $array_result = Crowdsource::prepare_array($r->val);

        asort($array_result);

        array_unshift($array_result, 'Not selected');


        //$array_result = array_reverse($array_result);


        $array_rows = array(
            'gender' => array('type' => 'select', 'options' => array('0' => 'Not selected', 'm' => 'Male', 'f' => 'Female')),
            'verdict' => array('type' => 'select', 'options' => $array_result),
            'comment' => array('type' => 'textarea'),
            'image' => array('type' => 'input', 'placeholer' => 'www.actor.jpg', 'desc' => 'If the cast member image is missing or poor quality, you can suggest another here.'),
            'link' => array('type' => 'input', 'placeholer' => 'www.wikipedia.org/actor_link', 'desc' => 'Cite your source(s) here if you have them.'),
        );

        $id = intval($_POST['id']);
        ////get user

        $array_user = Crowdsource::get_user();

        $content = Crowdsource::front('actor_crowdsource', $array_rows, $array_user, $id);

        echo $content;
    } else if ($oper == 'pg_rating') {
//`message`, `message_comment`, `nudity`, `nudity_comment`, `violence`, `violence_comment`, `language`, `language_comment`,
// `drugs`, `drugs_comment`, `other`, `other_comment`
        ///get movie header


        include($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/custom_twentysixteen/template/movie_single_template.php');


        $option = array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5);
        $id = intval($_POST['id']);


        $chead = template_single_movie_small($id);

        $array_user = Crowdsource::get_user();
        $array_rows = array(
            'message' => array('type' => 'select', 'pt' => 'p', 'style' => 'rating', 'options' => $option, 'desc' => 'What moral and life-affirming life lessons are shown in this work?'),
            'message_comment' => array('type' => 'textarea', 'style' => 'nohead', 'placeholer' => 'message comment'),
            'nudity' => array('type' => 'select', 'pt' => 'n', 'style' => 'rating', 'options' => $option, 'desc' => 'Sexual activity, nudity, promiscuity, etc. (Is it artistic and natural? Is it used to convey a message and depict reality? Or is it degenerate and used for cheap views?)'),
            'nudity_comment' => array('type' => 'textarea', 'style' => 'nohead', 'placeholer' => 'nudity comment'),
            'violence' => array('type' => 'select', 'pt' => 'n', 'style' => 'rating', 'options' => $option, 'desc' => 'Describe the violence without spoiling please. Is it violence with a purpose? Is it too extreme for preteens? Will it cause nightmares for elementary school kids?'),
            'violence_comment' => array('type' => 'textarea', 'style' => 'nohead', 'placeholer' => 'violence comment'),
            'language' => array('type' => 'select', 'pt' => 'n', 'style' => 'rating', 'options' => $option, 'desc' => 'Crude and obscene language throughout film. Profanity and any uses of extreme sexual language.'),
            'language_comment' => array('type' => 'textarea', 'style' => 'nohead', 'placeholer' => 'language comment'),
            'drugs' => array('type' => 'select', 'pt' => 'n', 'style' => 'rating', 'options' => $option, 'desc' => 'Are drugs/booze common, and are they shown positively or negatively? (E.g. A brief interrogation scene with cigarette smoke v.s. the protagonists glorifying cocaine use throughout.)'),
            'drugs_comment' => array('type' => 'textarea', 'style' => 'nohead', 'placeholer' => 'drugs comment'),
            'other' => array('type' => 'select', 'pt' => 'n', 'style' => 'rating', 'options' => $option, 'desc' => 'Miscellaneous negative influences and subversive propaganda elements. (Please elaborate.)'),
            'other_comment' => array('type' => 'textarea', 'style' => 'nohead', 'placeholer' => 'other comment'),
        );

        $content = Crowdsource::front('moviespgcrowd', $array_rows, $array_user, $id);
        echo $chead . $content;
    }
}

