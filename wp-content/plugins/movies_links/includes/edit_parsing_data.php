<h2><a href="<?php print $url ?>"><?php print __('Movies Links Parsing data') ?></a>. <?php print __('Edit') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;


if ($cid) {
    $options = $this->mp->get_options($campaign);
    $o = $options['parsing'];
    ?>
    <form accept-charset="UTF-8" method="post" id="campaign">

        <div class="cm-edit inline-edit-row">
            <fieldset>

                <input type="hidden" name="edit_parsing_data" value="1">
                <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Update') ?></span>
                    <select name="interval" class="interval">
                        <?php
                        $inetrval = $o['interval'];
                        foreach ($this->update_interval as $key => $name) {
                            $selected = ($key == $inetrval) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select> 
                    <span class="inline-edit"><?php print __('The parser update interval') ?></span>                    
                </label>

                <label class="inline-edit-interval"> 
                    <span class="title"><?php print __('URLs count') ?></span>         
                    <select name="num" class="interval">
                        <?php
                        foreach ($this->parse_number as $key => $name) {
                            $selected = ($key == $o['num']) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                     
                    <span class="inline-edit"><?php print __('Number of URLs for cron parsing') ?></span> 
                </label>

                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($o['status'] == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Parser is active') ?></span>
                </label>


                <h2>Parser rules</h2>
                <?php
                $parser_rules = $o['rules'];
                $this->show_parser_rules($parser_rules, true, $campaign->type);
                ?> 
                <p><b>Export</b> Rules to <a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&export_rules=1">JSON array</a>.</p>
                <p><b>Import</b> Rules from JSON array:</p>
                <div class="inline-edit-row">
                    <fieldset>              
                        <textarea name="import_rules_json" style="width:100%" rows="3"></textarea>           
                    </fieldset>
                </div>
                <div class="desc">Warning: adding new rules will replace all previous rules.</div>
                <br />

                <label class="inline-edit-status">                
                    <input type="checkbox" name="preview" value="1" checked="checked">
                    <span class="checkbox-title"><?php print __('Preview') ?></span>
                </label>
                <label class="inline-edit-interval">                    
                    <select name="pr_num" class="interval">
                        <?php
                        foreach ($this->parse_number as $key => $name) {
                            $selected = ($key == $o['pr_num']) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                     
                    <span class="inline-edit"><?php print __('Number of previews') ?></span> 
                </label>

                <br />
                <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  
            </fieldset>
        </div>
    </form>

    <?php
    if (isset($_POST['preview'])) {

        if ($preivew_data == -1) {
            print '<p>No arhives found</p>';
        } else if ($preivew_data) {
            ?>
            <h3>Parsing result:</h3>
            <?php foreach ($preivew_data as $id => $item) { ?>
                <table class="wp-list-table widefat striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php print __('Name') ?></th>                
                            <th><?php print __('Value') ?></th>    
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Uid</td>
                            <td><?php print $this->mla->theme_parser_url_link($id, $id); ?></td>
                        </tr> 
                        <?php
                        foreach ($item as $name => $value) {
                            $show_name = isset($this->parser_rules_names[$name]) ? $this->parser_rules_names[$name] : $name;
                            ?>
                            <tr>
                                <td><?php print $show_name ?></td>
                                <td><?php print $value ?></td>
                            </tr> 
                        <?php } ?>
                    </tbody>        
                </table>
                <br />
            <?php } ?>
        <?php } else { ?>
            <h3>Parsing error</h3>
            <p>Check regexp rules.</p>
            <?php
        }
    }
    ?>

<?php } ?>