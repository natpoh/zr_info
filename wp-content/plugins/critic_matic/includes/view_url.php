<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('View url') ?></h2>


<?php
if (!$url_data) {
    return;
}
?>


<h3><?php print __('URL') ?>: [<?php print $uid ?>] <?php print $url_data->link ?></h3>

<?php
if ($uid) {
    $campaign = $this->cp->get_campaign($url_data->cid);
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
                <td><?php print __('Campaign') ?></td>
                <td><?php if ($campaign) { ?>
                        <a href="/wp-admin/admin.php?page=critic_matic_parser&cid=<?php print $campaign->id ?>"><?php print $campaign->title ?></a>                        
                    <?php }
                    ?>
                </td>
            </tr>
            <tr>
                <td><?php print __('Link') ?></td>
                <td><a target="_blank" href="<?php print $url_data->link ?>"><?php print $url_data->link ?></a></td>
            </tr>
            <tr>
                <td><?php print __('Link hash') ?></td>
                <td><?php print $url_data->link_hash ?></td>
            </tr> 
            <tr>
                <td><?php print __('Status') ?></td>
                <td><?php print $this->cp->get_url_status($url_data->status) ?></td>
            </tr> 
            <tr>
                <td><?php print __('Post id') ?></td>
                <td><?php print $url_data->pid; ?></td>
            </tr>            
            <tr>
                <td><?php print __('Last log') ?></td>
                <td><?php print $this->cp->get_last_log($uid); ?></td>
            </tr>
        </tbody>        
    </table>


    <?php
    if ($url_data->pid) {
        $post = $this->cm->get_post($url_data->pid);
    } else {
        // Get post by linkhash
        $link_hash = $url_data->link_hash;
        $post = $this->cm->get_post_by_link_hash($link_hash);
    }

    if ($post):

        $post_url = $this->admin_page . $this->parrent_slug . '&pid=' . $post->id;

        //Author
        $author = $this->cm->get_author($post->aid);
        $author_name = $author->name;
        //Author link
        $author_link = $this->theme_author_link($post->aid, $author_name);

        ?>

            
        <br />
        <h3>Post data</h3>
        <table class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>
                    <th><?php print __('Name') ?></th>                
                    <th><?php print __('Value') ?></th>    
                </tr>
            </thead>
            <tbody>

                <tr>
                    <td><?php print __('Title') ?></td>
                    <td><a target="_blank" href="<?php print $post_url ?>" ><?php print $post->title ?></a></td>
                </tr>
                <tr>
                    <td><?php print __('Author') ?></td>
                    <td><?php print $author_link ?></td>
                </tr>                

            </tbody>        
        </table>
    <?php endif ?>

<?php } ?>