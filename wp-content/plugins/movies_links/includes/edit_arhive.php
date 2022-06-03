<h2><a href="<?php print $url ?>"><?php print __('Movies Links Arhive') ?></a>. <?php print __('Edit') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;


if ($cid) {
    $options = $this->mp->get_options($campaign);
    $ao = $options['arhive'];
    ?>
    <form accept-charset="UTF-8" method="post" id="campaign">

        <div class="cm-edit inline-edit-row">
            <fieldset>

                <input type="hidden" name="edit_arhive" value="1">
                <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Update') ?></span>
                    <select name="interval" class="interval">
                        <?php
                        $inetrval = $ao['interval'];
                        foreach ($this->update_interval as $key => $name) {
                            $selected = ($key == $inetrval) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select> 
                    <span class="inline-edit"><?php print __('The arhive parser update interval') ?></span>                    
                </label>

                <label class="inline-edit-interval"> 
                    <span class="title"><?php print __('URLs count') ?></span>         
                    <?php
                    $parse_num = $ao['num'];
                    $previews_number = $this->parse_number;
                    ?>

                    <select name="num" class="interval">
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

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Parse with') ?></span>
                    <select name="webdrivers" class="interval">
                        <?php
                        $current = $ao['webdrivers'];
                        foreach ($this->parse_mode as $key => $name) {
                            $selected = ($key == $current) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                                        
                </label>
               
                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($ao['random'] == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="random" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Random URLs parsing') ?></span>
                </label>

                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($ao['status'] == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Arhive parser is active') ?></span>
                </label>
                <br />

                <h3>Garbage collector</h3>
                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($ao['del_pea'] == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="del_pea" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Delete archives with parsing errors') ?></span>
                </label>
                <br />
                <label class="inline-edit-interval">

                    <select name="del_pea_int" class="interval">
                        <?php
                        $inetrval = $ao['del_pea_int'];
                        foreach ($this->remove_interval as $key => $name) {
                            $selected = ($key == $inetrval) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select> 
                    <span class="inline-edit"><?php print __('Delete archives after timeout') ?></span>                    
                </label>
                <br />

                <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">                  
            </fieldset>
        </div>
    </form>
    <br />
    <h2>Preview URLs</h2>
    <form accept-charset="UTF-8" method="post" id="campaign">
        <div class="cm-edit inline-edit-row">
            <fieldset>
                <label>
                    <span class="title"><?php print __('URL') ?></span>
                    <?php $first_url = $this->mp->get_last_url($cid); ?>
                    <span class="input-text-wrap"><input type="text" name="url" value="<?php print $first_url ?>"></span>
                </label>
                <br />
                <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
                <input type="hidden" name="arhive_preview" value="1">
                <input type="submit" name="preview" id="edit-submit" value="<?php echo __('Preview') ?>" class="button-secondary">
            </fieldset>
        </div>
    </form>
    <?php if ($preivew_data) { ?>
        <h2>Headers</h2>
        <textarea style="width: 90%; height: 300px;"><?php print $preivew_data['headers'] ?></textarea>        
        <h2>Content</h2>
        <textarea style="width: 90%; height: 300px;"><?php print htmlspecialchars($preivew_data['content']) ?></textarea>      
    <?php } ?>
    <?php ?>
<?php } ?>