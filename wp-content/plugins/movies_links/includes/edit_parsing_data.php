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
                <input type="hidden" name="edit_parsing_options" value="1">
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
                
                <label class="inline-edit-interval"> 
                    <span class="title"><?php print __('Version') ?></span>         
                    <select name="version" class="interval">
                        <?php
                        foreach ($this->version_number as $key => $name) {
                            $selected = ($key == $o['version']) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                     
                    <span class="inline-edit"><?php print __('The version of the current parsing rules. If you\'ve changed the rules and need to update posts, just change the version.') ?></span> 
                </label>

                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($o['status'] == 1 || $o['status'] == 3) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Parser is active') ?></span>
                </label>


                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($o['multi_parsing'] == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="multi_parsing" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Multi posts paring') ?></span>
                </label>
                <br />
                <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  
            </fieldset>
        </div>
    </form>
    <br />
    <hr />

    <?php if ($campaign->type == 2): ?>
        <form accept-charset="UTF-8" method="post" id="campaign">
            <div class="cm-edit inline-edit-row">
                <fieldset>
                    <input type="hidden" name="edit_parsing_row" value="1">
                    <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">
                    <h2>Select row rules</h2>
                    <label class="inline-edit-status">                
                        <?php
                        $checked = '';
                        if ($o['row_status'] == 1) {
                            $checked = 'checked="checked"';
                        }
                        ?>
                        <input type="checkbox" name="row_status" value="1" <?php print $checked ?> >
                        <span class="checkbox-title"><?php print __('Find rows is active') ?></span>
                    </label>
                    <br />
                    <?php
                    $parser_rules = $o['row_rules'];
                    $this->show_parser_rules($parser_rules, true, $campaign->type, array(), $this->mp->parser_row_rules_fields, $this->mp->parser_row_rules_type);
                    ?> 
                    <p><b>Export</b> Rules to <a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&export_row_rules=1">JSON array</a>.</p>
                    <p><b>Import</b> Rules from JSON array:</p>
                    <div class="inline-edit-row">
                        <fieldset>              
                            <textarea name="import_rules_json" style="width:100%" rows="3"></textarea>           
                        </fieldset>
                    </div>
                    <div class="desc">Warning: adding new rules will replace all previous rules.</div>
                    <br />
                    <label class="inline-edit-status">                
                        <input type="checkbox" name="preview_row" value="1" checked="checked">
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
        <br /><?php
        if (isset($_POST['preview_row'])) {

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
                                $ret = $value;

                                if (!is_array($value)) {
                                    $ret = array($value);
                                }
                                foreach ($ret as $data) {
                                    ?>
                                    <tr>                                    
                                        <td colspan="2"><textarea style="width:100%" rows="10"><?php print htmlspecialchars($data) ?></textarea></td>
                                    </tr> 
                                    <?php
                                }
                            }
                            ?>
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
        <hr />
    <?php endif; ?>

    <?php if ($o['multi_parsing'] == 1) { ?>
        <form accept-charset="UTF-8" method="post" id="campaign">
            <div class="cm-edit inline-edit-row">
                <fieldset>
                    <input type="hidden" name="multi_parsing_data" value="1">
                    <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">
                    <h2>Multi parsing rules</h2>
                    <label>  
                        <span class="title"><?php print __('Rule type') ?></span> 
                        <select name="multi_rule_type" class="interval">
                            <?php
                            foreach ($this->multi_rule_type as $key => $name) {
                                $selected = ($key == $o['multi_rule_type']) ? 'selected' : '';
                                ?>
                                <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                <?php
                            }
                            ?>                          
                        </select>   

                    </label>
                    <label>
                        <span class="title"><?php print __('Rule') ?></span>
                        <span class="input-text-wrap"><input type="text" name="multi_rule" placeholder="" value="<?php print htmlspecialchars(base64_decode($o['multi_rule'])) ?>"></span>
                    </label>

                    <label class="inline-edit-status">                
                        <input type="checkbox" name="preview_multi" value="1" checked="checked">
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
        if (isset($_POST['preview_multi'])) {
            if ($preivew_data) {
                ?>
                <h3>Parsing result:</h3>                
                <textarea style="width: 90%; height: 300px;">
                    <?php
                    p_r($preivew_data);
                    ?>
                </textarea>
                <?php
            }
        }
        ?>
    <?php } ?>

    <form accept-charset="UTF-8" method="post" id="campaign">
        <div class="cm-edit inline-edit-row">
            <fieldset>
                <input type="hidden" name="edit_parsing_data" value="1">
                <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">
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
        ?>
        <h3>Parsing result:</h3>        
        <?php
        if ($preivew_data == -1) {
            print '<p>No arhives found</p>';
        } else if ($preivew_data) {
            foreach ($preivew_data as $id => $items) {
                $rows = array($items);
                if ($o['multi_parsing'] == 1) {
                    $rows = $items;
                    ?>
                    <h3>Row data: <?php print $this->mla->theme_parser_url_link($id, $id); ?></h3>
                    <?php
                }

                foreach ($rows as $item) {
                    ?>
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
                                    <td><?php
                                        if (is_array($value)) {
                                            foreach ($value as $k => $v) {
                                                print "[$k] $v<br />";
                                            }
                                        } else {
                                            print $value;
                                        }
                                        ?></td>
                                </tr> 
                            <?php } ?>
                        </tbody>        
                    </table>
                    <br />
                <?php } ?>
                <?php
            }
        } else {
            ?>
            <h3>Parsing error</h3>
            <p>Check regexp rules.</p>
            <?php
        }
    }
    ?>



    <?php
    if ($campaign->type == 2):

        $ol = $options['links'];
        $campaigns = $this->mp->get_campaigns();
        ?>
        <form accept-charset="UTF-8" method="post" id="campaign">
            <div class="cm-edit inline-edit-row">
                <fieldset>
                    <input type="hidden" name="links_parsing_data" value="1">
                    <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">
                    <h2>Link results to the movie</h2>
                    <label>
                        <span class="title"><?php print __('Min match') ?></span>
                        <span class="input-text-wrap"><input type="text" name="match" class="match" value="<?php print $ol['match'] ?>"></span>
                    </label>

                    <label>
                        <span class="title"><?php print __('Min rating') ?></span>
                        <span class="input-text-wrap"><input type="text" name="rating" class="rating" value="<?php print $ol['rating'] ?>"></span>
                    </label>

                    <label class="inline-edit-interval">
                        <span class="title"><?php print __('Campaign') ?></span>
                        <select name="camp" class="interval">
                            <option value="0" >Select</option>
                            <?php
                            if ($campaigns) {
                                $con = $ol['camp'];
                                foreach ($campaigns as $item) {
                                    $key = $item->id;
                                    $name = "[$key] " . $item->title;
                                    $selected = ($key == $con) ? 'selected' : '';
                                    ?>
                                    <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                    <?php
                                }
                            }
                            ?>                            
                        </select> <br />
                        <span class="inline-edit"><?php print __('Campaign to which the found url addresses will be added.') ?></span>                    
                    </label>


                    <label>
                        <span class="title"><?php print __('Weight') ?></span>
                        <span class="input-text-wrap"><input type="text" name="weight" class="weight" value="<?php print $ol['weight'] ?>"></span>
                    </label>

                    <span class="inline-edit"><?php print __('Weight for new urls. If the URL is already in another campaign and the weight of this campaign is higher, the URL will be moved to this campaign.') ?></span> 
                    <br />
                    <br />

                    <?php
                    $link_rules = $ol['rules'];
                    $data_fields = $this->mp->get_parser_fields($options);
                    $this->show_links_rules($link_rules, $data_fields, $campaign->type);
                    ?>
                    <p><b>Export</b> Rules to <a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&export_links_rules=1">JSON array</a>.</p>
                    <p><b>Import</b> Rules from JSON array:</p>
                    <div class="inline-edit-row">
                        <fieldset>              
                            <textarea name="import_rules_json" style="width:100%" rows="3"></textarea>           
                        </fieldset>
                    </div>
                    <div class="desc">Warning: adding new rules will replace all previous rules.</div>
                    <br />
                    <label class="inline-edit-status">                
                        <input type="checkbox" name="preview_links" value="1" checked="checked">
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
        if (isset($_POST['preview_links'])) {
            if ($preivew_data) {
                $this->preview_links_urls($preivew_data);
            }
        }
        ?>

    <?php endif ?>

<?php } ?>