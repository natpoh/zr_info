<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('Add a new tag') ?></h2>
    <?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="campaign">
    <div class="cm-edit inline-edit-row">
        <fieldset>
            <label>
                <span class="title"><?php print __('Name') ?></span>
                <span class="input-text-wrap"><input type="text" name="name" class="name" value=""></span>
            </label>

            <label>
                <span class="title"><?php print __('Slug') ?></span>
                <span class="input-text-wrap"><input type="text" name="slug" class="slug" value=""></span>
            </label>

            <label class="inline-edit-active">                
                <?php
                $checked = 'checked="checked"';
                ?>
                <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                <span class="checkbox-title"><?php print __('Publish') ?></span>
            </label>


            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
        </fieldset>
    </div>
</form>
