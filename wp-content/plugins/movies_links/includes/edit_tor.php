<h2><a href="<?php print $url ?>"><?php print __('Tor Parser') ?></a>. <?php print __('Edit') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Service') ?>: [<?php print $cid ?>] <?php print $service->name ?></h3>
    <?php
}

print $tabs;


if ($cid) {   
    ?>
    <form accept-charset="UTF-8" method="post" id="campaign">

        <div class="cm-edit inline-edit-row">
            <fieldset>

                <input type="hidden" name="edit_tor" value="1">
                <input type="hidden" name="id" class="id" value="<?php print $service->id ?>">

                <label>
                    <span class="title"><?php print __('Name') ?></span>
                    <span class="input-text-wrap"><input type="text" name="name" class="title" value="<?php print $service->name ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Url') ?></span>
                    <span class="input-text-wrap"><input type="text" name="url" value="<?php print $service->url ?>"></span>
                </label>
              
                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($service->status == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Service is active') ?></span>
                </label>

                <br />
                <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  
            </fieldset>
        </div>
    </form>
    <?php ?>
<?php } ?>