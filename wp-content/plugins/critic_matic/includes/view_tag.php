<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('View tag') ?></h2>

<?php if ($tag) { ?>
    <h3><?php print __('Tag') ?>: [<?php print $tid ?>] <?php print $tag->name ?></h3>
    <?php
} else {
    ?>
    <h3><?php print __('Tag not found') ?>: [<?php print $tid ?>]</h3>
    <?php
    return;
}

print $tabs;

if ($tid) {
    $authors_count = $this->cm->get_authors_count(-1, $tag->id);
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
                <td><?php print $tag->name ?></td>
            </tr>  
            <tr>
                <td><?php print __('Slug') ?></td>
                <td><?php print $tag->slug ?></td>
            </tr>  
            <tr>
                <td><?php print __('Status') ?></td>
                <td><?php print $this->cm->get_tag_status($tag->status) ?></td>
            </tr>
            <tr>
                <td><?php print __('Authors') ?></td>
                <td><?php print $authors_count ?></td>
            </tr>
        </tbody>        
    </table>
    <?php if ($authors_count) { ?>
        <h2><?php print __('Authors list') ?></h2>
        <?php print $pager ?>
        <table id="authors" class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>
                    <th class="mob-hide"><?php print __('id') ?></th>
                    <th><?php print __('Author') ?></th>                 
                    <th><?php print __('From') ?></th> 
                    <th><?php print __('Status') ?></th>                     
                    <th><?php print __('Actions') ?></th> 
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($authors as $author) {
                    $author_name = $author->name;
                    $author_type = $this->cm->get_author_type($author->type);
                    $author_status = $this->cm->get_author_status($author->status);
           
                    //Author links
                    $author_actions = $this->cm->authors_actions(array('home'));                    
                    $author_url = $this->admin_page . $this->authors_url . '&aid=' . $author->id;                    
                    $action_links = $this->get_filters($author_actions, $author_url, $curr_tab = 'none', $front_slug = 'home', 'tab', 'inline');
                    
                    $author_link = $this->theme_author_link($author->id, $author_name);  
                    ?>
                    <tr>
                        <td class="mob-hide"><?php print $author->id ?></td>     
                        <td><?php print $author_link ?></a></td>
                        <td><?php print $author_type ?></td>
                        <td><?php print $author_status ?></td>                      
                        <td><?php print $action_links; ?>
                        </td>
                    </tr> 
                <?php } ?>
            </tbody>
        </table>    
        <?php print $pager ?>
    <?php } ?>
<?php } ?>