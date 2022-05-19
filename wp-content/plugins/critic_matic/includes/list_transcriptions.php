<h2><a href="<?php print $url ?>"><?php print __('Critic transcriptions') ?></a></h2>

<?php
if (isset($filters_tabs['filters'])) {
    print implode("\n", array_values($filters_tabs['filters']));
}


if (sizeof($posts) > 0) {
    ?>
    <?php print $pager ?>  

    <table class="wp-list-table widefat striped table-view-list">
        <thead>           
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                
            <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?>                                         
            <?php $this->sorted_head('title', 'Title / Link', $orderby, $order, $page_url) ?>              
        <th><?php print __('Content') ?></th>                 
        <th><?php print __('Ts status') ?></th>  
        <th><?php print __('Transcriptions') ?></th>  
        <th><?php print __('Author') ?></th> 
        <th><?php print __('Status') ?></th>
        <th><?php print __('Type') ?></th> 
    </thead>
    <tbody>
        <?php
        foreach ($posts as $item) {
            //Author
            $author = $this->cm->get_author($item->aid);
            $author_name = $author->name;
            $author_link = $this->theme_author_link($item->aid, $author_name);

            //Post links         
            $post_actions = $this->cm->post_actions();
            $post_url = $this->admin_page . $this->parrent_slug . '&pid=' . $item->id;
            $post_links = $this->get_filters($post_actions, $post_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab', '', false);
            ?>
            <tr>           
                <td><?php print $item->id ?></td>     
                <td><?php print $this->cm->curr_date($item->date) ?></td>                                           
                <td>
                    <?php print $item->title ?>
                    <?php
                    if ($item->link):
                        //validate hash
                        /* if (!$this->cm->validate_link_hash($item->link, $item->link_hash)) {
                          $link_hash = $this->cm->update_link_hash($item->id, $item->link);
                          print $link_hash?'<br />'.$link_hash:'';
                          } */
                        ?>
                        <br />
                        <a href="<?php print $item->link ?>" target="_blank" title="<?php print $item->link ?>"><?php print substr($item->link, 0, 70) ?></a>                    
                    <?php endif ?>
                    <?php print $post_links ?>
                </td>
                <td><?php print $this->cm->crop_text(strip_tags($item->content), 100) ?></td>
                <td><?php print $item->tstatus ?></td>
                <td><?php print $this->cm->crop_text(strip_tags($item->tcontent), 100) ?></td>
                <td><?php print $author_link ?></td>
                <td><?php print $this->cm->get_post_status($item->status) ?></td>
                <td><?php print $this->cm->get_post_type($item->type) ?></td>  
            </tr> 
        <?php } ?>
    </tbody>
    </table>  

    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The critic posts not found') ?></p>
    <?php
}
?>