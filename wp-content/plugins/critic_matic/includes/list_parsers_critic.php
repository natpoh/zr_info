<h2><a href="<?php print $url ?>"><?php print __('Critic parsers') ?></a>. <?php print __('Campaigns') ?></h2>
<?php print $tabs; ?>
<?php print $filters ?>
<?php print $parser_status_filters ?>
<br />
<?php
if (sizeof($campaigns) > 0) {
    $url = $this->admin_page . $this->parser_url;
    ?>
    <?php print $pager ?>

    <table id="feeds" class="wp-list-table widefat striped table-view-list">
        <thead>
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
            <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?> 
            <?php $this->sorted_head('title', 'Title', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('author_name', 'Author', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('type', 'Type', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('status', 'State', $orderby, $order, $page_url) ?>            
        <th><?php print __('Find URLs') ?></th> 
        <?php $this->sorted_head('parser_status', 'Parser', $orderby, $order, $page_url) ?>

        <th><?php print __('URL') ?></th> 
        <th><?php print __('Urls count') ?></th> 
        <th><?php print __('Post count') ?></th>         
        <th><?php print __('Parsed') ?></th>  
        <th><?php print __('Last log') ?></th> 

    </thead>
    <tbody>
        <?php
        $def_options = $this->cp->def_options;
        foreach ($campaigns as $parser) {
            $options = unserialize($parser->options);

            //Author link
            $author = $this->cm->get_author($parser->author);
            $author_link = $this->theme_author_link($parser->author, $author->name);

            //Find urls
            $optkey = 'cron_urls';
            if ($parser->type == 1) {
                $optkey = 'yt_urls';
            }
            $parser_status_int = $parser->status ==1 ? 1 : 0;            
            
            $parser_parser_status_int = $parser->parser_status;
            
                
            if ($parser_parser_status_int==3){
                $parser_parser_status_int=2;
            } else if ($parser_parser_status_int==2){
                $parser_parser_status_int=3;
            }
            
            $find_state = $options[$optkey]['status'] ? 'Active' : 'Inactive';
            $find_state_int = $options[$optkey]['status'] ? 1 : 0;
            $find_interval = $options[$optkey]['interval'];
            $find_last_update = $options[$optkey]['last_update'];
            ?>
            <tr> 
                <td><?php print $parser->id ?></td>
                <td><?php print $this->cp->curr_date($parser->date) ?></td>
                <td>
                    <?php print $parser->title ?><br />                    
                    <?php
                    $parser_actions = $this->cp->parser_actions();
                    $parser_url = $url . '&cid=' . $parser->id;
                    $action_links = $this->get_filters($parser_actions, $parser_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab');
                    print $action_links;
                    ?>
                </td>
                <td><?php print $author_link ?></td>              
                <td><?php print $this->cp->parser_type[$parser->type] ?></td>
                <td class="nowrap"><i class="sticn st-<?php print $parser_status_int ?>"></i><?php print $this->cp->camp_state[$parser->status] ?></td>
                <td class="nowrap">
                    <i class="sticn st-<?php print $find_state_int ?>"></i><?php print $find_state ?>
                    <?php if ($find_interval) { ?>
                        <br /><?php print $this->cp->update_interval[$find_interval] ?> -  Update interval
                    <?php } ?>
                    <?php if ($find_last_update) { ?>
                        <br /><?php print $this->cp->curr_date($find_last_update) ?> - Last
                    <?php } ?>
                    <?php if ($options[$optkey]['status']) { ?>
                        <br /><?php print $this->cp->get_next_update($find_last_update, $find_interval) ?> - Next
                    <?php } ?>
                </td>
                <td class="nowrap">
                    <i class="sticn st-<?php print $parser_parser_status_int ?>"></i><?php print $this->cp->parser_state[$parser->parser_status] ?>
                    <br /><?php print $this->cp->update_interval[$parser->update_interval] ?> -  Update interval
                    <?php if ($parser->last_update) { ?>
                        <br /><?php print $this->cp->curr_date($parser->last_update) ?> - Last
                    <?php } ?>
                    <?php if ($parser->parser_status == 1) { ?>
                        <br /><?php print $this->cp->get_next_update($parser->last_update, $parser->update_interval) ?> - Next
                    <?php } ?>
                </td>
                <td><a href="<?php print $parser->site ?>"><?php print $parser->site ?></a></td>                                
                <td><?php print $this->cp->get_urls_count(-1, $parser->id) ?></td>
                <td><?php print $this->cp->get_urls_count(-1, $parser->id, 1) ?></td>
                <td><?php print $this->cp->get_urls_count(5, $parser->id, 1) ?></td>
                <td><?php print $this->cp->get_last_log(0, $parser->id) ?></td>


            </tr> 
        <?php } ?>
    </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The parsers not found') ?></p>
    <?php
}
?>