<?php
header('Access-Control-Allow-Origin:*');

if (strstr( $_SERVER['DOCUMENT_ROOT'],'service'))
{
    $root = str_replace('/service','', $_SERVER['DOCUMENT_ROOT']);
}
else
{
    $root =$_SERVER['DOCUMENT_ROOT'];
}

if (!defined('ABSPATH'))
    define('ABSPATH',$root . '/');


// DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
// Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';


// Critic matic
if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
}

function critic_crowd_validation($link, $row = []) {
    !class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';



    $id = intval($_POST['id']);

    $error = array();

    if (!$link) {
        $error['link'] = 'No link';
    }

    $title_type = 'input';
    $critic_name_type = 'input';

    if ($link) {
        if ($row) {
            $id = $row->rwt_id;
            $content = $row->content;
            $title = $row->title;
            $author = $row->critic_name;
            $author_id = $row->critic_id;
            $cid = $row->review_id;
        } else {

            $cm = new CriticMatic();
            $cp = $cm->get_cp();

            ///validate link
            ///1 check link on critic bd

            $youtube = false;
            $reg_v = '#youtu(\.)*be(\.com)*\/(watch\?v\=)*([a-zA-Z0-9_-]+)#';
            if (preg_match($reg_v, $link, $match)) {
                $link = 'https://www.youtube.com/watch?v=' . $match[4];
                $youtube = true;
            }

            $link_hash = $cm->link_hash($link);
            $post_exist = $cm->get_post_by_link_hash($link_hash);
            $author_id = 0;
            $cid = 0;


            $array_result = array();
            if ($post_exist) {
                // 1. Get post status
                $post_publish = false;
                $cid = $post_exist->id;

                if ($post_exist->status == 1) {
                    $post_publish = true;
                }

                // 2. Get post movie meta
                $movie_exist = $cm->get_movies_data($cid, $id);
                

                if ($post_publish && $movie_exist) {
                    // Post pulbish already linked
                    $error['link'] = 'The post already exist';
                } else {
                    $author_obj = $cm->get_post_author($cid);

                    $title = $post_exist->title;
                    if ($title) {
                        $title_type = 'disabled';
                    }
                    $author = $author_obj->name;
                    if ($author) {
                        $critic_name_type = 'disabled';
                    }
                    $author_id = $author_obj->id;
                    $content = $post_exist->content;

                    if ($post_publish) {
                        // Need add a new movie to post
                    } else {
                        // Need publish post
                        if (!$movies_meta) {
                            // Need add a new movie to post
                        }
                    }
                }
            } else {

                //add data
                ///2 get content
                if ($youtube) {
                    $link = 'https://www.youtube.com/watch?v=' . $match[4];
                    ///get youtube data
                    $result = $cp->yt_video_data($link);
                    if ($result) {
                        $title = $result->title;
                        $author = $result->channelTitle;
                        $author_obj = $cm->get_author_by_name($author);
                        if ($author_obj) {
                            // author valid
                            $author_id = $author_obj->id;
                        }
                        $content = '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . $match[4] . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                    } else {
                        $error['link'] = 'Can not get the data from Youtube URL';
                    }
                } else {
                    ///get main data
                    $result = $cp->clear_read($link);
                    if ($result) {
                        $title = $result['title'];
                        $author = $result['author'];
                        if ($author) {
                            $author_obj = $cm->get_author_by_name($author);
                            if ($author_obj) {
                                // author valid
                                $author_id = $author_obj->id;
                            }
                        }
                        $content = $result['content'];
                        if (!$title && !$content) {
                            $error['link'] = 'Can not get the data from URL';
                        }
                    } else {
                        $error['link'] = 'Can not get the data from URL';
                    }
                }
            }
        }

        if ($content) {
            ////create data

            include(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/movie_single_template.php');

            $chead = template_single_movie_small($id);

            $array_user = Crowdsource::get_user();

            $array_rows = array(
                'link' => array('type' => 'disabled', 'title' => 'Link to the review source:', 'default_value' => $link),
                'title' => array('type' => $title_type, 'placeholer' => 'title', 'title' => 'Review Title:', 'default_value' => $title),
                'critic_name' => array('type' => $critic_name_type, 'placeholer' => 'Critic name', 'title' => 'Critic\'s Name:', 'default_value' => $author),
                'critic_id' => array('type' => 'hidden', 'default_value' => $author_id),
                'review_id' => array('type' => 'hidden', 'default_value' => $cid),
                'content' => array('type' => 'html', 'title' => 'Review Content:', 'default_value' => $content),
            );


            $content = Crowdsource::front('critic_crowd_result', $array_rows, $array_user, $id);
            $array_result['critic_data'] = $chead . $content;
        }
    }
    if ($error) {
        $array_result['error'] = $error;
    }


    echo json_encode($array_result);
}

if (isset($_POST['action']) && $_POST['action'] == 'author_autocomplite') {

    $keyword = isset($_POST['keyword']) ? strip_tags(stripslashes($_POST['keyword'])) : '';
    $ret = array('type' => 'no', 'data' => array());
    if ($keyword) {
        $limit = 6;
        // Only pro authors
        $author_type = 1;
        $cm = new CriticMatic();
        $results = $cm->find_authors($cm->escape($keyword), $limit, $author_type);

        if (sizeof($results)) {
            $ret['type'] = 'ok';
            foreach ($results as $item) {
                $type = $cm->get_author_type($item->type);
                $title = $item->name;
                $ret['data'][] = array('id' => $item->id, 'title' => $title);
            }
        }
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        print json_encode($ret);
    } else {
        header("Location: " . $_SERVER["HTTP_REFERER"]);
    }
    exit();
}

if (isset($_POST['oper'])) {

    $oper = $_POST['oper'];
    $id = intval($_POST['id']);

    if ($oper == 'add_custom')
    {
        $type = $_POST['type'];

        if ($type=="#add_movie")
        {
            //add movie


            echo 'ok';
            return;

        }
        else if ($type=="#add_review")
        {
            $content='';

            //add movie
            $array_rows =  array('movies' => array('class' => ' crowd_movie_autoinput', 'type' => 'input', 'placeholer' => 'type movies, tv, games', 'title' => 'Select Movie / TV / Game'));

            $content_input = Crowdsource::front('', $array_rows, [], [],1);

            $content = Crowdsource::get_search_block($content_input,'','crowd_select_movie');



            echo $content;

            $oper = 'add_critic';

        }
        else if ($type=="#add_audience_review")
        {
            $content='';

            //add movie
            $array_rows =  array('movies' => array('class' => ' crowd_movie_autoinput', 'type' => 'input', 'placeholer' => 'title', 'title' => 'Please select a movie, tv show, or game:'));

            $content_input = Crowdsource::front('', $array_rows, [], [],1);

            $content = Crowdsource::get_search_block($content_input,'','crowd_select_movie');



            echo $content;
            echo '<div id="audience_form" class="wpcr3_respond_1" data-value="0" data-postid="0">

<p class="w50"> </p>
</div>';
            return;

        }
    }



    if ($oper == 'crowd_submit') {




        $array_crowd = array('actor_crowdsource' => 'actors_crowd', 'moviespgcrowd' => 'movies_pg_crowd', 'review_crowdsource' => 'review_crowd', 'critic_crowd_link' => 'critic_crowd_link', 'critic_crowd_result' => 'critic_crowd');

        $type = $_POST['type'];
        $data = $_POST['data'];


        if ($array_crowd[$type] == 'critic_crowd_link') {
            $error = [];


            $data = json_decode($data, 1);

            $link = strip_tags($data['link']);
            $row = [];


            if (isset($_POST['admin_view'])) {
                $rid = intval($_POST['admin_view']);

                $sql = "select * from `data_critic_crowd` where id=" . $rid;
                $row = Pdo_an::db_fetch_row($sql);
                $link = $row->link;
            } else {

                if ($link) {
                    if (!filter_var($link, FILTER_VALIDATE_URL)) {
                        $data_obj['link'] = '';
                        $error['link'] = 'Link url is not valid';
                    }
                }

                ///check link

                $sql = "SELECT id FROM `data_critic_crowd` where `link` = ? limit 1";
                $count_link = Pdo_an::db_results_array($sql, [$link]);

                if ($count_link) {
                    $array_result = array('critic_data' => '<p class="user_message_info">This review has already been added.</p><div class="submit_data"><button class="button close" >Close</button></div>');
                    echo json_encode($array_result);


                    return;
                }

                if ($error) {
                    echo json_encode(array('error' => $error));
                    return;
                }
            }

            critic_crowd_validation($link, $row);
            return;
        }


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
            $link = strip_tags($data_obj['link']);
            $link = trim(strip_tags($link));
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
            $reqest_field_dop = '';

            if ($array_crowd[$type] == 'review_crowd') {
                $array_movies = [];


                foreach ($data_obj as $i => $v) {
                    if ($i == 'incorrect_item_inner') {

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
            else if ($array_crowd[$type] == 'actors_crowd') {
                ///add name from authors

                $sql = "SELECT `name` FROM `data_actors_imdb` where id = " . $id;
                $name_array = Pdo_an::db_fetch_row($sql);
                $actor_name = $name_array->name;

                $data_obj['actor_name'] = $actor_name;
                $data_obj['actor_id'] = $id;
                $reqest_field = 'actor_id';
            }
            else if ($array_crowd[$type] == 'movies_pg_crowd') {
                ///add name from authors

                $sql = "SELECT * FROM `data_movie_imdb` where id = " . $id;
                $name_array = Pdo_an::db_fetch_row($sql);
                $title = $name_array->title;
                $imdb_id = $name_array->movie_id;

                $data_obj['movie_title'] = $title;
                $data_obj['movie_id'] = $imdb_id;
                $data_obj['rwt_id'] = $id;
                $reqest_field = 'rwt_id';
            }
            else if ($array_crowd[$type] == 'critic_crowd') {
                ///add name from authors

                $sql = "SELECT * FROM `data_movie_imdb` where id = " . $id;
                $name_array = Pdo_an::db_fetch_row($sql);
                $title = $name_array->title;
                $imdb_id = $name_array->movie_id;

                $data_obj['movie_title'] = $title;
                $data_obj['rwt_id'] = $id;
                $reqest_field = '';
                $data_obj['review_id'] = intval($data_obj['review_id']);
                $data_obj['critic_id'] = intval($data_obj['critic_id']);

                $reqest_field = 'rwt_id';
                $reqest_field_dop = array('r' => 'and link = ? ', 'a' => strip_tags($data_obj['link']));
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
                require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
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

                if ($oper_insert_colums && $user_id && $reqest_field) {




                    if ($reqest_field_dop) {
                        $sql = "SELECT id FROM `data_" . $array_crowd[$type] . "` where `user` = ? and `" . $reqest_field . "` = ? " . $reqest_field_dop['r'] . " limit 1";
                        $rw = Pdo_an::db_results_array($sql, array($user_id, $id, $reqest_field_dop['a']));
                    } else {
                        $sql = "SELECT id FROM `data_" . $array_crowd[$type] . "` where `user` = ? and `" . $reqest_field . "` = ? limit 1";
                        $rw = Pdo_an::db_results_array($sql, array($user_id, $id));
                    }




                    if ($rw[0]['id']) {
                        $uddate_id = $rw[0]['id'];
                    }
                }

                if ($uddate_id) {
                    // echo 'update';

                    $oper_update = substr($oper_update, 1);

                    $inser_sql = "UPDATE `data_" . $array_crowd[$type] . "` SET " . $oper_update . "  WHERE id = " . $uddate_id;
                    Pdo_an::db_results_array($inser_sql, $data_array);
                } else {
                    $inser_sql = "INSERT INTO `data_" . $array_crowd[$type] . "`(`id` " . $oper_insert_colums . " ) VALUES (NULL " . $oper_insert_data . " )";
                    Pdo_an::db_results_array($inser_sql, $data_array);
                    $uddate_id = Pdo_an::last_id();
                }
                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', "data_" . $array_crowd[$type], array('id' => $uddate_id), 'crowsource', 5);



                if ($status == 1 && $uddate_id) {

                    ///rebuild cache
                    Crowdsource::rebuild_cache($uddate_id, $array_crowd[$type]);
                }



                if ($error) {
                    echo json_encode(array('error' => $error));
                }
            }
        }
    }
    else if ($oper == 'get_search_movie') {
        $m_id = intval($_POST['id']);
        $r_id = intval($_POST['r_id']);
        $only_movie = intval($_POST['only_movie']);

        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        }

        if (!class_exists('CriticFront')) {
            require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
        }

        $cfront = new CriticFront();

        if ($only_movie)
        {
            $movie_tmpl = Crowdsource::get_movie_template_small($m_id, $cfront);
        }
        else
        {
            $movie_tmpl = Crowdsource::get_movie_template($r_id, $m_id, $cfront);
        }

        echo $movie_tmpl;
    }
    else if ($oper == 'review_crowd') {
        $id = intval($_POST['id']);


        //////movie
        if (isset($_POST['movie']))
            ; {
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
            require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
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
                    $content = '<!--u ' . $user_id . ' s ' . $rw[0]['status'] . ' --><p class="user_message_info">You already left a comment.</p><div class="submit_data"><button class="button close" >Close</button></div>';

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
                'incorrect_item_inner' => array('class' => ' crowd_movie_autoinput', 'type' => 'input', 'placeholer' => 'type movies, tv, games', 'title' => 'Add other items to the review'),
            );

            $content_input = Crowdsource::front('', $array_rows, $array_user, $id, 1, $inner_content);
        }
        $inner_content =Crowdsource::get_search_block($content_input,$movie_tmpl,'');



        $array_rows = array(
            'incorrect_item' => array('type' => 'big_checkbox', 'desc' => 'INCORRECT OR INCOMPLETE ITEM(S) REVIEWED'),
        );
        $content .= '<div style="z-index: 2;position: relative;">' . Crowdsource::front('review_crowdsource', $array_rows, $array_user, $id, 1, $inner_content) . '</div>';



        $array_rows = array(
            'remove' => array('type' => 'checkbox', 'desc' => 'Remove the review from ZR?'),
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
    }
    else if ($oper == 'actor_crowd') {

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
    }
    else if ($oper == 'pg_rating') {
//`message`, `message_comment`, `nudity`, `nudity_comment`, `violence`, `violence_comment`, `language`, `language_comment`,
// `drugs`, `drugs_comment`, `other`, `other_comment`
        ///get movie header


        include(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/movie_single_template.php');


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
    else if ($oper == 'add_critic') {


        include(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/movie_single_template.php');


        $id = intval($_POST['id']);


        $chead = template_single_movie_small($id);

        $array_user = Crowdsource::get_user();

        $array_rows = array(
            'link' => array('type' => 'input', 'placeholer' => 'link', 'title' => 'Add a link to the review source:'),
        );

        $content = Crowdsource::front('critic_crowd_link', $array_rows, $array_user, $id);


        echo $chead . $content;
    }
}

