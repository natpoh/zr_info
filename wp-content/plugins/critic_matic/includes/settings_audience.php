<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Settings audience') ?></h2>
<?php print $tabs; ?>
<?php 
$ca = $this->get_ca();
?>
<form accept-charset="UTF-8" method="post" id="tag">
    <div class="cm-edit inline-edit-row">
        <fieldset>    
            <label class="inline-edit-author">
                <span class="title"><?php print __('Post status') ?></span>
                <select name="audience_post_status" class="authors">
                    <?php
                    foreach ($this->cm->post_status as $key => $status) {
                        $selected = ($key == $ss['audience_post_status']) ? 'selected' : '';
                        ?>

                        <option value="<?php print $key ?>" <?php print $selected ?> >
                            <?php print $status ?>
                        </option>                                
                        <?php
                    }
                    ?>                       
                </select>
            </label>
            <div class="desc">Status for all new audience posts.</div>
            <input type="hidden" name="audience_descriptions" value="1">
            <br />
            <label class="inline-edit">
                <span class="title"><?php print __('Edit posts') ?></span>
                <select name="audience_post_edit" class="authors">
                    <?php
                    foreach ($ca->audience_post_edit as $key => $status) {
                        $selected = ($key == $ss['audience_post_edit']) ? 'selected' : '';
                        ?>

                        <option value="<?php print $key ?>" <?php print $selected ?> >
                            <?php print $status ?>
                        </option>                                
                        <?php
                    }
                    ?>                       
                </select>
            </label>
            <div class="desc">Users can edit their audience posts. Time after posting.</div>            
            
            <h3>Audience ratings description</h3>
            <?php foreach ($ss['audience_desc'] as $key => $value) { ?>
                <h4><?php print ucfirst($key) ?></h4>
                <textarea name="au_<?php print $key ?>" style="width: 90%;" rows="5"><?php print stripslashes($value) ?></textarea>
            <?php } ?>
            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>

            <br />
            <br />
            <div class="label">
                <?php print __('Audience cron path') ?>
            </div>
            <input type="text" name="audience_cron_path" class="title" value="<?php print $ss['audience_cron_path'] ?>" style="width:90%">
            <br />
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

        </fieldset>
    </div>
</form>