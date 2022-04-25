<h2><a href="<?php print $url ?>"><?php print __('Movies Links') ?></a>. <?php print __('Custom results') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;
/*print $filters;
print $filters_arhive_type;
print $filters_parser_type;
print $filters_links_type;*/


if (sizeof($posts) > 0) {

    ?>
    <?php print $pager ?>  
    <form accept-charset="UTF-8" method="post" >
        <table id="parsers" class="wp-list-table widefat striped table-view-list">
            <thead>
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('lastname', 'Lastname', $orderby, $order, $page_url) ?>                                     
            <?php $this->sorted_head('topcountryname', 'Top country', $orderby, $order, $page_url) ?>                                    
            <th><?php print __('Countries') ?></th>             
            </thead>
            <tbody>
                <?php
                foreach ($posts as $item) {
                    ?>
                    <tr> 
                        <td><?php print $item->id ?></td>                             
                        <td><?php print $item->lastname ?></td>
                         <td><?php print $item->topcountryname ?></td>
                        <td>
                            <?php 
                            $countryes = $mlr->get_countries_by_lasnameid($item->id);
                            if($countryes){
                                $rows = array();
                                foreach ($countryes as $key => $value) {
                                    $rows[]=$key.': '.$value;
                                }
                                print implode('<br />', $rows);
                            }
                            ?>
                        </td>                       
                    </tr> 
                <?php } ?>
            </tbody>
        </table>  
    </form>
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The urls not found') ?></p>
    <?php
}
?>