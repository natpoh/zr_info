<h2><a href="<?php print $url ?>"><?php print __('Critic transcriptions') ?></a></h2>



<?php

/*
print $tabs;
print $filters_type;
print $filters_meta_type;
print $filters;
*/


if (sizeof($posts) > 0) {
    ?>
    <?php print $pager ?>  

        <table class="wp-list-table widefat striped table-view-list">
            <thead>           
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                
            <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?>                                         
            <?php $this->sorted_head('title', 'Title / Link', $orderby, $order, $page_url) ?>              
            <th><?php print __('Content') ?></th>                 
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
                    ?>
                    <tr>           
                        <td><?php print $item->id ?></td>     
                        <td><?php print $this->cm->curr_date($item->date) ?></td>                                           
                        <td>
                            <?php print $item->title ?>
                            <?php 
                            if ($item->link):
                                //validate hash
                                /*if (!$this->cm->validate_link_hash($item->link, $item->link_hash)) {
                                    $link_hash = $this->cm->update_link_hash($item->id, $item->link);
                                    print $link_hash?'<br />'.$link_hash:'';
                                }*/
                                ?>
                                <br />
                                <a href="<?php print $item->link ?>" target="_blank" title="<?php print $item->link ?>"><?php print substr($item->link, 0, 70) ?></a>                    
                            <?php endif ?>
                        </td>
                        <td><?php print $this->cm->crop_text(strip_tags($item->content), 100) ?></td>
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