<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Clear comments') ?></h2>

<?php print $tabs; ?>

<?php
if (sizeof($posts) > 0) {
    ?>

    <?php print $pager ?>
    <table id="countries" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                
                <?php $this->sorted_head('type', 'Type', $orderby, $order, $page_url) ?>                                         
                <?php $this->sorted_head('ftype', 'Field', $orderby, $order, $page_url) ?> 
                <?php $this->sorted_head('cid', 'Cid', $orderby, $order, $page_url) ?> 
                <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?> 
                <th><?php print __('Content') ?></th>
                <th><?php print __('Content clear') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($posts as $post) {
                ?>
                <tr>
                    <td><?php print $post->id ?></td>
                    <td><?php print $cc->cc_type[$post->type] ?></td>
                    <td><?php print $cc->cc_ftype[$post->ftype] ?></td>
                    <td class="mob-hide"><?php print $this->theme_post_link($post->cid,$post->cid) ?></td>
                    <td class="mob-hide"><?php print $this->cm->curr_date($post->date) ?></td>  
                    <td><?php print $post->content ?></td>
                    <td><?php print $post->content_clear ?></td>                                    
                </tr> 
            <?php } ?>
        </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The comments not found') ?></p>
    <?php
}
?>