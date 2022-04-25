<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('Settings') ?></h2>

<?php
print $tabs;

$settings = $this->cf->get_feed_settings();
$update_interval = $this->cf->update_interval;
/*
 * Array ( 
 * [critic_feeds_max_feed_error] => 10 
 * [update_interval] => 60 
 * [post_status] => 1 
 * [rss_date] => 1 
 * [rules] => )
 */
?>
<form accept-charset="UTF-8" method="post" id="campaign">
    <div class="cm-edit inline-edit-row">
        <fieldset>
            <h3><?php print __('Default settings for all campaigns') ?></h3>
            <label class="inline-edit-interval">                
                <select name="update_interval" class="interval">
                    <?php
                    foreach ($update_interval as $key => $name) {
                        $selected = ($key == $settings['update_interval']) ? 'selected' : '';
                        ?>
                        <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                        <?php
                    }
                    ?>                          
                </select> 
                <span class="inline-edit"><?php print __('Feeds update interval.') ?></span>                    
            </label>
            <br />
            <label class="inline-edit-interval">                       
                <?php
                $post_status = $settings['post_status'];
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
                <span class="inline-edit"><?php print __('Status for all new posts') ?></span> 
            </label>
            <br />            
            <label class="inline-edit-rss_date">                
                <?php
                $checked = '';
                $rss_date = $settings['rss_date'];
                if ($rss_date) {
                    $checked = 'checked="checked"';
                }
                ?>
                <input type="checkbox" name="rss_date" value="1" <?php print $checked ?> >
                <span class="checkbox-title"><?php print __('Get the post date from RSS') ?></span>
            </label>

             <label class="inline-edit-rules">                
                <?php
                $checked = '';
                $use_global_rules = $settings['use_global_rules'];
                if ($rss_date) {
                    $checked = 'checked="checked"';
                }
                ?>
                <input type="checkbox" name="use_global_rules" value="1" <?php print $checked ?> >
                <span class="checkbox-title"><?php print __('Use global rules') ?></span>
            </label>
            <?php
            $rules = $settings['rules'];

            $this->cf->show_rules($rules, true);
            ?> 
            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

        </fieldset>

    </div>
</form>
