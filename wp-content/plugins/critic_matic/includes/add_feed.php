<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('Add a new campaign') ?></h2>
<?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="campaign">
    <div class="cm-edit inline-edit-row">
        <fieldset>
            <label>
                <span class="title"><?php print __('Title') ?></span>
                <span class="input-text-wrap"><input type="text" name="title" class="title" value=""></span>
            </label>

            <label>
                <span class="title"><?php print __('Feed') ?></span>
                <span class="input-text-wrap"><input type="text" name="feed" value=""></span>
            </label>

            <label>
                <span class="title"><?php print __('Site') ?></span>
                <span class="input-text-wrap"><input type="text" name="site" value=""></span>
            </label>

            <label class="inline-edit-author">
                <span class="title"><?php print __('Author') ?></span>
                <select name="author" class="authors">
                    <?php
                    if (sizeof($authors)) {
                        foreach ($authors as $author) {
                            ?>
                            <option value="<?php print $author->id ?>"><?php print $author->name ?></option>                                
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
                        $selected = ($key == $def_options['update_interval']) ? 'selected' : '';
                        ?>
                        <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                        <?php
                    }
                    ?>                          
                </select> 
                <span class="inline-edit"><?php print __('The feed update interval') ?></span>                    
            </label>
            <br />

            <label class="inline-edit-active">                
                <?php
                $checked = 'checked="checked"';
                ?>
                <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                <span class="checkbox-title"><?php print __('Active') ?></span>
            </label>


            <label class="inline-edit-rss_date">                
                <?php
                $checked = '';
                $rss_date = $def_options['options']['rss_date'];
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
                $use_global_rules = $def_options['options']['use_global_rules'];
                if ($use_global_rules) {
                    $checked = 'checked="checked"';
                }
                ?>
                <input type="checkbox" name="use_global_rules" value="1" <?php print $checked ?> >
                <span class="checkbox-title"><?php print __('Use global rules') ?></span>
            </label>

            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  

        </fieldset>

    </div>
</form>
