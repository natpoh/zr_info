<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('View author') ?></h2>


<?php if ($author) { ?>
    <h3><?php print __('Author') ?>: [<?php print $aid ?>] <?php print stripslashes($author->name) ?></h3>
    <?php
} else {
    ?>
    <h3><?php print __('Author not found') ?>: [<?php print $aid ?>]</h3>
    <?php
    return;
}


print $tabs;

if ($aid) {

    $author_type = $this->cm->get_author_type($author->type);

    //Tags
    $tags = $this->cm->get_author_tags($aid);
    $tag_arr = array();
    if (sizeof($tags)) {
        foreach ($tags as $tag) {
            $tag_arr[] = $tag->name;
        }
    }

    //Movies
    $movies = $this->cm->get_author_post_count($aid);

    //Campaigns
    $campaigns = $this->cf->get_feeds_count(-1, $aid);

    //Options
    $options = unserialize($author->options);


    //image            
    $image = '';
    if ($options['image']) {
        $image = '<img src="' . $options['image'] . '" width="150"  height="150">';
    }

    $wp_uid = $author->wp_uid;


    if (!$image && $author->type == 2) {
        $cav = $this->cm->get_cav();
        if ($wp_uid) {
            // User            
            $image = $cav->get_or_create_user_avatar($wp_uid, 0, 150);
        } else {
            $image = $cav->get_or_create_user_avatar(0, $author->id, 150);
        }
    }

    print $image;
    ?>
    <br />
    <table class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <th><?php print __('Name') ?></th>                
                <th><?php print __('Value') ?></th>    
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php print __('Name') ?></td>
                <td><?php print stripslashes($author->name) ?></td>
            </tr>
            <tr>
                <td><?php print __('From') ?></td>
                <td><?php print $author_type ?></td>
            </tr>
            <?php
            if (isset($options['secret'])) {
                $secret = $options['secret'];
                ?>
                <tr>
                    <td><?php print __('Secret key') ?></td>
                    <td><?php print $secret ?></td>
                </tr>  
            <?php } ?>
            <tr>
                <td><?php print __('Tags') ?></td>
                <td><?php print implode(', ', $tag_arr) ?></td>
            </tr>
            <tr>
                <td><?php print __('Status') ?></td>
                <td><?php print $author_status[$author->status] ?></td>
            </tr>
            <tr>
                <td><?php print __('Show type') ?></td>
                <td><?php print $this->cm->author_show_type[$author->show_type] ?></td>
            </tr>
            <tr>
                <td><?php print __('WP Account') ?></td>
                <td><?php print $author->wp_uid ?></td>
            </tr>
            <tr>
                <td><?php print __('Autoblur') ?></td>
                <td><?php print isset($options['autoblur']) && $options['autoblur'] == 1 ? 'True' : 'False'  ?></td>
            </tr>
            <tr>
                <td><?php print __('Posts') ?></td>
                <td><?php print $movies ?></td>
            </tr>
            <tr>
                <td><?php print __('Feeds') ?></td>
                <td><?php print $campaigns ?></td>
            </tr>            
        </tbody>        
    </table>
<?php } ?>