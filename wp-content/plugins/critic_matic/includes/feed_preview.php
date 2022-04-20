<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('Preview feeds') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;

$status = isset($options['post_status']) ? $options['post_status'] : $this->cf->def_options['options']['post_status'];

if ($cid) {
    if ($preview['items']) {
        foreach ($preview['items'] as $item) {
            ?>
            <h2><?php print $item['post']['t'] ?></h2>
            <table class="wp-list-table widefat striped table-view-list">
                <thead>
                    <tr>
                        <th><?php print __('Name') ?></th>
                        <th><?php print __('Value') ?></th>                                                          
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($item['post'] as $row_key => $row_val) {
                        if ($row_key == 'd') {
                            continue;
                        }
                        ?>
                        <tr>
                            <td><?php print $this->cf->post_fields[$row_key] ?></td>
                            <td><?php print_r($row_val) ?></td>                 
                        </tr> 
                    <?php } ?>
                    <tr>
                        <td><?php print __('Rules') ?></td>
                        <td><?php
                            $check = $item['check'];
                            if ($check) {
                                foreach ($check as $key => $value) {
                                    print 'Result: <b>' . $this->cf->rules_actions[$value] . '</b>. Rule id: ' . $key;
                                    break;
                                }
                            } else {
                                print "<b>No changes</b>. ";
                                print $this->cf->rules_actions[$status].".";
                                
                            }
                            ?></td>                 
                    </tr> 
                </tbody>
            </table>  
            <br />
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th><?php print __('Content html') ?></th>                
                        <th><?php print __('Content view') ?></th>    
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php print htmlspecialchars(stripslashes($item['post']['d'])) ?></td>
                        <td><?php print $item['post']['d'] ?></td>
                    </tr>           

                </tbody>        
            </table>
            <?php
        }
    }
}
?>