<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('Edit') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print stripslashes($campaign->title) ?></h3>
    <?php
}

print $tabs;

/*
 * date` int(11) NOT NULL DEFAULT '0',
  `last_update` int(11) NOT NULL DEFAULT '0',
  `update_interval` int(11) NOT NULL DEFAULT '60',
  `author` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `title` varchar(255) NOT NULL default '',
  `feed_hash` varchar(255) NOT NULL default '',
  `feed` text default NULL,
  `site` text default NULL,
  `last_hash` varchar(255) NOT NULL default '',
  `options` text default NULL,
 */
if ($cid) {
    $options = unserialize($campaign->options);
    ?>
    <form accept-charset="UTF-8" method="post" id="campaign">

        <div class="cm-edit inline-edit-row">
            <fieldset>

                <input type="hidden" name="edit_feed" value="1">
                <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">

                <label>
                    <span class="title"><?php print __('Title') ?></span>
                    <span class="input-text-wrap"><input type="text" name="title" class="title" value="<?php print stripslashes($campaign->title) ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Feed') ?></span>
                    <span class="input-text-wrap"><input type="text" name="feed" value="<?php print $campaign->feed ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Site') ?></span>
                    <span class="input-text-wrap"><input type="text" name="site" value="<?php print $campaign->site ?>"></span>
                </label>

                <label class="inline-edit-author">
                    <span class="title"><?php print __('Author') ?></span>
                    <select name="author" class="authors">
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
                    <span class="inline-edit"><?php print __('The feed update interval') ?></span>                    
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


                <label class="inline-edit-rss_date">                
                    <?php
                    $checked = '';
                    $rss_date = isset($options['rss_date']) ? $options['rss_date'] : $def_options['options']['rss_date'];
                    if ($rss_date) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="rss_date" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Get the post date from RSS') ?></span>
                </label>


                <label class="inline-edit-global_rules">                
                    <?php
                    $checked = '';
                    $use_global_rules = isset($options['use_global_rules']) ? $options['use_global_rules'] : $def_options['options']['use_global_rules'];
                    if ($use_global_rules) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="use_global_rules" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Use global rules') ?></span>
                </label>
                <?php
                $rules = isset($options['rules']) ? $options['rules'] : $def_options['options']['rules'];
                $this->cf->show_rules($rules, true);
                ?> 
                <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                <br />
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  
<br /><br />
            </fieldset>
        </div>
    </form>
    <?php
    $global_rules = isset($settings['rules']) ? $settings['rules'] : array();
    if ($global_rules) {
        ?>      
        <div class="cm-edit inline-edit-row">
            <?php
            $this->cf->show_rules($global_rules, false, array(), true);
            ?>
        </div>
        <?php
    }
    ?>
<?php } ?>