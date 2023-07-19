<h2><a href="<?php print $url ?>"><?php print __('Critic parsers') ?></a>. <?php print __('Edit') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print stripslashes($campaign->title) ?></h3>
    <?php
}

print $tabs;


if ($cid) {
    $options = $this->cp->get_options($campaign);
    $cprules = $this->cp->get_cprules();
    ?>
    <form accept-charset="UTF-8" method="post" id="campaign">

        <div class="cm-edit inline-edit-row">
            <fieldset>

                <input type="hidden" name="edit_parser" value="1">
                <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">

                <label>
                    <span class="title"><?php print __('Title') ?></span>
                    <span class="input-text-wrap"><input type="text" name="title" class="title" value="<?php print stripslashes($campaign->title) ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Site') ?></span>
                    <span class="input-text-wrap"><input type="text" name="site" value="<?php print $campaign->site ?>"></span>
                </label>

                <label class="inline-edit-author">
                    <span class="title"><?php print __('Author') ?></span>
                    <select name="author" class="authors">          
                        <option value="0">None</option>                                
                        <?php
                        if (sizeof($authors)) {
                            foreach ($authors as $author) {
                                $selected = ($author->id == $campaign->author) ? 'selected' : '';
                                ?>
                                <option value="<?php print $author->id ?>" <?php print $selected ?> ><?php print stripslashes($author->name) ?></option>                                
                                <?php
                            }
                        }
                        ?>                       
                    </select>
                </label>

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Type') ?></span>
                    <select name="type" class="type">
                        <?php
                        foreach ($this->cp->parser_type as $key => $name) {
                            $selected = ($key == $campaign->type) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select> 
                    <span class="inline-edit"><?php print __('Type of the Campaign') ?></span>                    
                </label>

                <label>
                    <span class="title"><?php print __('URLs weight') ?></span>
                    <span class="input-text-wrap"><input type="text" name="new_urls_weight" class="title" value="<?php print $options['new_urls_weight']; ?>"></span>
                </label>
                <div class="desc"><?php print __('If the weight is higher than other campaigns, when adding new urls added by campaigns before, they will be assigned to this campaign.') ?></div>
                <br />

                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($campaign->status == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Campaign is active') ?></span>
                </label>

                <h1>Page parser settings</h1>

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Update') ?></span>
                    <select name="interval" class="interval">
                        <?php
                        foreach ($update_interval as $key => $name) {
                            $selected = ($key == $campaign->update_interval) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select> 
                    <span class="inline-edit"><?php print __('The parser update interval') ?></span>                    
                </label>

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Post status') ?></span>               
                    <?php
                    $post_status = isset($options['post_status']) ? $options['post_status'] : $def_options['options']['post_status'];
                    $post_statuses = $this->cm->post_status;
                    ?>

                    <select name="post_status" class="interval">
                        <?php
                        foreach ($post_statuses as $key => $name) {
                            $selected = ($key == $post_status) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                     
                    <span class="inline-edit"><?php print __('Default status for all new posts') ?></span> 
                </label>


                <label class="inline-edit-interval">
                    <span class="title"><?php print __('URL status') ?></span>               
                    <?php
                    $url_status = $options['url_status'];
                    $url_statuses = $cprules->rules_actions;
                    ?>
                    <select name="url_status" class="interval">
                        <?php
                        foreach ($url_statuses as $key => $name) {
                            $selected = ($key == $url_status) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                     
                    <span class="inline-edit"><?php print __('Default URL status for rules filter') ?></span> 
                </label>


                <label class="inline-edit-interval"> 
                    <span class="title"><?php print __('URLs count') ?></span>         
                    <?php
                    /*
                      'yt_force_update' => 1,
                      'yt_page' => '',
                      'yt_parse_num' => 50,
                      'yt_pr_num' => 50,
                     */
                    $parse_num = $options['parse_num'];
                    $previews_number = $this->cp->previews_number;
                    if ($campaign->type == 1) {
                        $parse_num = $options['yt_parse_num'];
                        $previews_number = $this->cp->yt_per_page;
                    }
                    ?>
                    <select name="parse_num" class="interval">
                        <?php
                        foreach ($previews_number as $key => $name) {
                            $selected = ($key == $parse_num) ? 'selected' : '';
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
                    if ($campaign->parser_status == 1 || $campaign->parser_status == 3) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="parser_status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Parser is active') ?></span>
                </label>
                
                <br />
                <?php
                if ($campaign->type == 1) {
                    $yt_urls = $options['yt_urls'];
                    /*
                      'page' => '',
                      'per_page' => 50,
                      'last_update' => 0,
                      'force_update' => 1,
                     */
                    ?>                   
                    <h2>YouTube settings</h2>

                    <label>
                        <span class="title"><?php print __('Channel ID') ?></span>
                        <span class="input-text-wrap"><input type="text" name="yt_page" placeholder="Leave blank to search for Channel ID by Campaign URL address" class="title" value="<?php print htmlspecialchars(base64_decode($options['yt_page'])) ?>"></span>
                    </label>

                    <label class="inline-edit-status">                
                        <?php
                        $checked = '';
                        if ($options['yt_force_update'] == 1) {
                            $checked = 'checked="checked"';
                        }
                        ?>
                        <input type="checkbox" name="yt_force_update" value="1" <?php print $checked ?> >
                        <span class="checkbox-title"><?php print __('Force update Posts if they already exists in "Feeds"') ?></span>

                    </label>
                <?php } ?>

                <br />
                <h2>Rules filter</h2>

                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($options['use_rules'] == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="use_rules" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Use rules filter') ?></span>
                </label>
                <p><?php print __('Rules that determine whether to parse the address or not.') ?></p>
                <?php
                $rules = isset($options['rules']) ? $options['rules'] : $def_options['options']['rules'];
                $cprules->show_rules($rules, true, array(), $campaign->type);
                ?> 
                <br />
                <h2>Parser rules</h2>
                <?php if ($campaign->type == 1) { ?>
                    <label class="inline-edit-status">                
                        <?php
                        $checked = '';
                        if ($options['yt_pr_status'] == 1) {
                            $checked = 'checked="checked"';
                        }
                        ?>
                        <input type="checkbox" name="yt_pr_status" value="1" <?php print $checked ?> >
                        <span class="checkbox-title"><?php print __('Use parser rules') ?></span>
                    </label>
                <?php } ?>
                <?php
                $parser_rules = $options['parser_rules'];
                $cprules->show_parser_rules($parser_rules, true, $campaign->type);
                ?> 
                <p><b>Export</b> Rules to <a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&export_parser_rules=1">JSON array</a>.</p>
                <p><b>Import</b> Rules from JSON array:</p>
                <div class="inline-edit-row">
                    <fieldset>              
                        <textarea name="import_rules_json" style="width:100%" rows="3"></textarea>           
                    </fieldset>
                </div>
                <div class="desc">Warning: adding new rules will replace all previous rules.</div>
                <br />
                <label class="inline-edit-interval">                    
                    <?php
                    $pr_num = $options['pr_num'];
                    $previews_number = $this->cp->previews_number;
                    if ($campaign->type == 1) {
                        $pr_num = $options['yt_pr_num'];
                        $previews_number = $this->cp->yt_per_page;
                    }
                    ?>
                    <select name="pr_num" class="interval">
                        <?php
                        foreach ($previews_number as $key => $name) {
                            $selected = ($key == $pr_num) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                     
                    <span class="inline-edit"><?php print __('Number of previews') ?></span> 
                </label>


                <br />
                <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  
            </fieldset>
        </div>
    </form>
    <?php ?>
<?php } ?>