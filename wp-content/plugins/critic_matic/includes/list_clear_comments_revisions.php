<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Audience revisions') ?></h2>

<?php
print $tabs;


if (sizeof($posts) > 0) {
    $cfront = new CriticFront($this->cm);
    ?>
    <?php print $pager ?>

    <table id="overview" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>               
                <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                
                <?php $this->sorted_head('cid', 'cid', $orderby, $order, $page_url) ?>  
                <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?>                                         
                <th><?php print __('Title / Countent') ?></th>                
                <th><?php print __('Rating') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($posts as $item) {
                // meta
                $rating_data = $this->cm->get_rating_array($item);
                //$email = isset($rating_data['em']) ? $rating_data['em'] : '';
              
                ?>
                <tr>                                   
                    <td class="mob-hide"><?php print $item->id ?></td>
                    <td class="mob-hide"><?php print $this->theme_post_link($item->cid,$item->cid) ?></td>
                    <td class="mob-hide"><?php print $this->cm->curr_date($item->date) ?></td>                                           
                    <td>
                        <b><?php print stripslashes($item->title) ?></b><br />        
                        <?php print $item->content ?>                       
                    </td>                    
                    <td><?php print $cfront->theme_rating($rating_data); ?></td>                                  
                </tr> 
            <?php } ?>
        </tbody>
    </table>    

    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('No revisions found') ?></p>
    <?php
}
?>