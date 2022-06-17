<?php
/**
 * Plugin Name: Crowdsource
 * Version: 1.0.0
 * Description: Crowdsource user data.
 */

class CrowdAdmin
{


    private $access_level = 4;
    private $parrent_slug = 'crowdsource';
    public $user_can;

    public function __construct()
    {
        global $table_prefix;
        add_action('admin_menu', array($this, 'crowd_menu_pages'));
        add_action('admin_bar_menu', array($this, 'admin_bar_render'), 99);

    }

    public function crowd_menu_pages()
    {

        add_menu_page(__('Crowdsource'), __('Crowdsource'), $this->access_level, $this->parrent_slug, array($this, 'overview'));
        add_submenu_page($this->parrent_slug, __('Overview'), __('Overview'), $this->access_level, $this->parrent_slug, array($this, 'overview'));
        add_submenu_page($this->parrent_slug, __('Actors Crowdsource'), __('Actors'), $this->access_level, $this->parrent_slug. '_actors_crowdsource', array($this, 'actors_crowdsource'));
        add_submenu_page($this->parrent_slug, __('PG Rating Crowdsource'), __('PG Rating'), $this->access_level, $this->parrent_slug . '_pgrating_crowd', array($this, 'pgrating_crowd'));
        add_submenu_page($this->parrent_slug, __('Review Crowdsource'), __('Review'), $this->access_level, $this->parrent_slug . '_review_crowd', array($this, 'review_crowd'));
        add_submenu_page($this->parrent_slug, __('Critic Review Crowdsource'), __('Critic Review'), $this->access_level, $this->parrent_slug . '_critic_review_crowd', array($this, 'critic_review_crowd'));
    }
    public function overview()
    {
        $actor_count = Crowdsource::get_new_draft('data_actors_crowd');
        $pg_count = Crowdsource::get_new_draft('data_movies_pg_crowd');
        $rw_count = Crowdsource::get_new_draft('data_review_crowd');
        $rwc_count = Crowdsource::get_new_draft('data_critic_crowd');


        echo '<h1>Crowdsource</h1>';
        if ($pg_count || $actor_count|| $rw_count) {

            if ($pg_count)
            {
                echo '<h2><a href="/wp-admin/admin.php?page='.$this->parrent_slug.'_pgrating_crowd&status=0">New PG crowdsource '.$pg_count.'</a></h2>';
            }
            if ($actor_count)
            {
                echo '<h2><a href="/wp-admin/admin.php?page='.$this->parrent_slug.'_actors_crowdsource&status=0">New Actor crowdsource '.$actor_count.'</a></h2>';
            }
            if ($rw_count)
            {
                echo '<h2><a href="/wp-admin/admin.php?page='.$this->parrent_slug.'_review_crowd&status=0">New Review crowdsource '.$rw_count.'</a></h2>';
            }
            if ($rwc_count)
            {
                echo '<h2><a href="/wp-admin/admin.php?page='.$this->parrent_slug.'_critic_review_crowd&status=0">New Critic Review crowdsource '.$rwc_count.'</a></h2>';
            }
        }
        else{
            echo '<h2>no new draft</h2>';
        }


    }

    public function user_can()
    {
        global $user_ID;
        if (user_can($user_ID, 'editor') || user_can($user_ID, 'administrator')) {
            return true;
        }
        return false;
    }

    public function admin_bar_render($wp_admin_bar)
    {

        if (function_exists('user_can')) {

            $this->user_can = $this->user_can();

        }

        if ($this->user_can) {

            !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

            $actor_count = Crowdsource::get_new_draft('data_actors_crowd');
            $pg_count = Crowdsource::get_new_draft('data_movies_pg_crowd');
            $rw_count = Crowdsource::get_new_draft('data_review_crowd');
            $rwc_count= Crowdsource::get_new_draft('data_critic_crowd');

            $total_crowd = $pg_count + $actor_count+$rw_count+$rwc_count;
            if ($pg_count || $actor_count|| $rw_count || $rwc_count) {
                $wp_admin_bar->add_menu(array(
                    'parent' => '',
                    'id' => 'flag-report-crowd',
                    'title' => '<span style="color: #ff5e28; font-weight: bold;">Crowd: ' . $total_crowd . '</span>',

                ));

                if ($rw_count) {
                    $wp_admin_bar->add_menu(array(
                        'parent' => 'flag-report-crowd',
                        'id' => 'flag-report-crowd-review',
                        'title' => '<span style="color: #ff5e28; font-weight: bold;">Review Crowdsource: ' . $rw_count . '</span>',
                        'href' => '/wp-admin/admin.php?page='.$this->parrent_slug.'_review_crowd&status=0',
                    ));
                }

                if ($pg_count) {
                    $wp_admin_bar->add_menu(array(
                        'parent' => 'flag-report-crowd',
                        'id' => 'flag-report-crowd-pg',
                        'title' => '<span style="color: #ff5e28; font-weight: bold;">PG Rating Crowdsource: ' . $pg_count . '</span>',
                        'href' => '/wp-admin/admin.php?page='.$this->parrent_slug.'_pgrating_crowd&status=0',
                    ));
                }
                if ($actor_count) {
                    $wp_admin_bar->add_menu(array(
                        'parent' => 'flag-report-crowd',
                        'id' => 'flag-report-crowd-actor',
                        'title' => '<span style="color: #ff5e28; font-weight: bold;">Actor Crowdsource: ' . $actor_count . '</span>',
                        'href' => '/wp-admin/admin.php?page='.$this->parrent_slug.'_actors_crowdsource&status=0',
                    ));
                }
                if ($rw_count) {
                    $wp_admin_bar->add_menu(array(
                        'parent' => 'flag-report-crowd',
                        'id' => 'flag-report-crowd-actor',
                        'title' => '<span style="color: #ff5e28; font-weight: bold;">Critic Review Crowdsource: ' . $rwc_count . '</span>',
                        'href' => '/wp-admin/admin.php?page='.$this->parrent_slug.'_critic_review_crowd&status=0',
                    ));
                }
            }

        }
    }



    public function critic_review_crowd()
    {
        !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

        if (Crowdsource::checkpost())
        {
            return;
        }

        echo '<h1>Critic Review Crowdsource</h1>';


/// 	id	rwt_id	movie_title	link	title	content	review_id	critic_name	critic_id
/// weight	bad_words	critic_status	user	ip	add_time	status
        $array_rows = array(
            'id'=>array('w'=>10),
            'rwt_id' =>array('w'=>10, 'type' => 'textarea','editfalse'=>1),
            'movie_title' =>array('w'=>20, 'type' => 'textarea','editfalse'=>1),
            'link' =>array('w'=>10, 'type' => 'textarea','editfalse'=>1),
            'title' =>array('w'=>20, 'type' => 'textarea','editfalse'=>1),
            'content' =>array('w'=>40, 'type' => 'textarea','hidden'=>'hidden','editfalse'=>1),
            'critic_name' =>array('w'=>10, 'type' => 'textarea','editfalse'=>1),
            'critic_id' =>array('w'=>10, 'type' => 'textarea','editfalse'=>1),
            'weight' =>array('w'=>10, 'type' => 'textarea','editfalse'=>1),
            'bad_words' =>array('w'=>10, 'type' => 'textarea','editfalse'=>1),
            'critic_status' =>array('w'=>10, 'type' => 'textarea','editfalse'=>1),
            'status'=>array('type'=>'select','options'=>'0:Waiting;1:Approved;2:Rejected')
        );


        Crowdsource::Show_admin_table('critic_crowd',$array_rows,1);

    }



    public function review_crowd()
    {
        !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

        if (Crowdsource::checkpost())
        {
            return;
        }

        echo '<h1>Review Crowdsource</h1>';

//id	review_id	user	broken_link	source_link	incorrect_item	movies	irrelevant	remove	blur	comment	add_time	status

        $array_rows = array(
            'id'=>array('w'=>10),
            'movies' =>array('w'=>20, 'type' => 'textarea'),
            'broken_link' => array('w'=>10, 'type' => 'select','options'=>'0:Off;1:On'),
            'incorrect_item' => array('w'=>10, 'type' => 'select','options'=>'0:Off;1:On'),
            'irrelevant' => array('w'=>10, 'type' => 'select','options'=>'0:Off;1:On'),
            'remove' => array('w'=>10, 'type' => 'select','options'=>'0:Off;1:On'),
            'blur' => array('w'=>10, 'type' => 'select','options'=>'0:Off;1:On'),
            'comment' =>array('w'=>20, 'type' => 'textarea'),
            'status'=>array('type'=>'select','options'=>'0:Waiting;1:Approved;2:Rejected')
        );


        Crowdsource::Show_admin_table('review_crowd',$array_rows,1);



    }

    public function pgrating_crowd(){

        !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

        if (Crowdsource::checkpost())
        {
            return;
        }

        echo '<h1>PG Rating Crowdsource</h1>';



        $array_rows = array(
            'id'=>array('w'=>5),
            'message' => array('w'=>5, 'type' => 'select','options'=>'0:0;1:1;2:2;3:3;4:4;5:5'),
            'message_comment' => array('w'=>10, 'type' => 'textarea'),
            'nudity' => array('w'=>5, 'type' => 'select','options'=>'0:0;1:1;2:2;3:3;4:4;5:5'),
            'nudity_comment' =>array('w'=>10, 'type' => 'textarea'),
            'violence' => array('w'=>5, 'type' => 'select','options'=>'0:0;1:1;2:2;3:3;4:4;5:5'),
            'violence_comment' =>array('w'=>10, 'type' => 'textarea'),
            'language' => array('w'=>5, 'type' => 'select','options'=>'0:0;1:1;2:2;3:3;4:4;5:5'),
            'language_comment' => array('w'=>10, 'type' => 'textarea'),
            'drugs' =>array('w'=>5, 'type' => 'select','options'=>'0:0;1:1;2:2;3:3;4:4;5:5'),
            'drugs_comment' =>array('w'=>10, 'type' => 'textarea'),
            'other' => array('w'=>5, 'type' => 'select','options'=>'0:0;1:1;2:2;3:3;4:4;5:5'),
            'other_comment' =>array('w'=>10, 'type' => 'textarea'),
            'status'=>array('type'=>'select','options'=>'0:Waiting;1:Approved;2:Rejected')
        );


        Crowdsource::Show_admin_table('movies_pg_crowd',$array_rows,1,'',1);

    }
    public function actors_crowdsource()
    {
        !class_exists('Crowdsource') ? include ABSPATH . "analysis/include/crowdsouce.php" : '';

        if (Crowdsource::checkpost())
        {
            return;
        }





        $sql = "SELECT val  FROM `options` where id = 4";
        $r = Pdo_an::db_fetch_row($sql);
        $array_result = Crowdsource::prepare_array($r->val,1);

        $array_rs='';
        foreach ($array_result as $i =>$v)
        {
            $array_rs.=';'.$i.':'.$v;
        }

        $array_rs = substr($array_rs,1);

        echo '<h1>Actors Crowdsource</h1>';



        $array_rows = array(
            'id'=>array('w'=>5),
            'comment'=>array('w'=>30),
            'status'=>array('type'=>'select','options'=>'0:Waiting;1:Approved;2:Rejected'),
            'gender'=>array('type'=>'select','options'=>'0:N/A;m:Male;f:Female'),
            'verdict'=>array('type'=>'select','options'=>$array_rs),
        );


        Crowdsource::Show_admin_table('actors_crowd',$array_rows,1);
    }
}
$crowd = new CrowdAdmin;