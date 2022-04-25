<h2><a href="<?php print $url ?>"><?php print __('Critic parsers') ?></a>. <?php print __('Preview parser') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;

if ($cid) {
    if (sizeof($preview)) {
        foreach ($preview as $id => $item) {
            ?>            
            <table class="wp-list-table widefat striped table-view-list">
                <thead>
                    <tr>
                        <th><?php print __('Name') ?></th>
                        <th><?php print __('Value') ?></th>                                                          
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($item['url'] as $row_key => $row_val) { ?>
                        <tr>
                            <td><?php print $row_key ?></td>
                            <td><?php print_r($row_val) ?></td>                 
                        </tr> 
                    <?php } ?>
                    <?php if ($options['use_rules']) { ?>
                        <tr>
                            <td><?php print __('Rules url') ?></td>
                            <td><?php
                                $check = $item['check_url'];
                                if ($check) {
                                    foreach ($check['data'] as $key => $value) {
                                        print 'Result: <b>' . $this->cp->rules_actions[$value] . '</b>. Rule id: ' . $key;
                                        break;
                                    }
                                } else {
                                    print "<b>No changes</b>. ";
                                    print $this->cp->rules_actions[$status] . ".";
                                }
                                ?>
                            </td>                 
                        </tr> 
                        <tr>
                            <td><?php print __('Rules content') ?></td>
                            <td><?php
                                $check = $item['check_content'];
                                if ($check) {
                                    foreach ($check['data'] as $key => $value) {
                                        print 'Result: <b>' . $this->cp->rules_actions[$value] . '</b>. Rule id: ' . $key;
                                        break;
                                    }
                                } else {
                                    print "<b>No changes</b>. ";
                                    print $this->cp->rules_actions[$status] . ".";
                                }
                                ?>
                            </td>                 
                        </tr> 
                    <?php } ?>
                    <tr>
                        <td><?php print __('Found title') ?></td>
                        <td><?php print $item['title'] ?></td>                 
                    </tr> 
                    <tr>
                        <td><?php print __('Found author') ?></td>
                        <td><?php print $item['author'] ?></td>                 
                    </tr> 
                    <tr>
                        <td><?php print __('Found date') ?></td>
                        <td><?php 
                        
                        $date_raw = $item['date_raw']?$item['date_raw']:'Not found';
                        print "Date raw: ".$date_raw.'<br />';
                        print "Date: ". gmdate('d.m.Y H:i:s', $item['date']); 
                        ?></td>                 
                    </tr> 
                </tbody>
            </table>  
            <br />
            <h2><?php print $item['title'] ?></h2>
            <?php if ($item['headers']) { ?>
                <table class="wp-list-table widefat fixed striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php print __('Content html') ?></th>                
                            <th><?php print __('Content view') ?></th>    
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <h2>Headers</h2>
                                <?php print str_replace("\n", '<br />', $item['headers']) ?>
                                <h2>Content filtered</h2>
                                <div class="content-preview">
                                    <?php print $item['content'] ? htmlspecialchars(stripslashes($item['content'])) : ""  ?>
                                </div>
                                <h2>Content raw</h2>
                                <div class="content-preview">
                                    <?php print $item['raw'] ? htmlspecialchars(stripslashes($item['raw'])) : ""  ?>                                
                                </div>
                            </td>
                            <td><?php print $item['content'] ?></td>
                        </tr>           

                    </tbody>        
                </table>
                <br />
            <?php } ?>
            <?php
        }
    }
}
?>