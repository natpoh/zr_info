<h2><a href="<?php print $url ?>"><?php print __('Movies Links Parsers') ?></a>. <?php print __('Edit') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;


if ($cid) {
    $options = $this->mp->get_options($campaign);
    ?>
    <form accept-charset="UTF-8" method="post" id="campaign">

        <div class="cm-edit inline-edit-row">
            <fieldset>

                <input type="hidden" name="edit_campaing" value="1">
                <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">

                <label>
                    <span class="title"><?php print __('Title') ?></span>
                    <span class="input-text-wrap"><input type="text" name="title" class="title" value="<?php print $campaign->title ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Site') ?></span>
                    <span class="input-text-wrap"><input type="text" name="site" value="<?php print $campaign->site ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Type') ?></span>
                    <select id="add-campaing-type" name="type" class="type">
                        
                        <?php
                        
                        foreach ($this->parser_types as $key => $name) {
                             $selected = ($key == $campaign->type) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select> 
                    <span class="inline-edit"><?php print __('Type of the Campaign') ?></span>                    
                </label>
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

                <br />
                <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  
            </fieldset>
        </div>
    </form>
    <?php ?>
<?php } ?>