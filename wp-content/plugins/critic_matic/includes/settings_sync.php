<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Settings audience') ?></h2>
<?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="tag">
    <div class="cm-edit inline-edit-row">
        <fieldset>    
            <label class="inline-edit-author">
                <span class="title"><?php print __('Sync status') ?></span>
                <select name="sync_status" class="">
                    <?php

                    foreach ($this->cm->sync_status_types as $key => $status) {
                        $selected = ($key == $ss['sync_status']) ? 'selected' : '';
                        ?>
                        <option value="<?php print $key ?>" <?php print $selected ?> >
                            <?php print $status ?>
                        </option>                                
                        <?php
                    }
                    ?>                       
                </select>
            </label>
            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

        </fieldset>
    </div>
</form>