<h2><a href="<?php print $url ?>"><?php print __('Movies Links') ?></a>. <?php print __('Settings parser') ?></h2>
<?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="tag">
    <div class="cm-edit">
        <fieldset>    
            <div class="label">
                <?php print __('Tor driver') ?>
            </div>
            <input type="text" name="tor_driver" class="title" value="<?php print $ss['tor_driver'] ?>" style="width:90%">
            <br /><br />
            <div class="label">
                <?php print __('Tor get ip driver') ?>
            </div>
            <input type="text" name="tor_get_ip_driver" class="title" value="<?php print $ss['tor_get_ip_driver'] ?>" style="width:90%">
            <br /><br />

            <div class="label">
                <?php print __('Tor IP hour limit') ?>
            </div>
            <input type="text" name="tor_ip_h" class="title" value="<?php print $ss['tor_ip_h'] ?>" style="width:90%">
            <br /><br />


            <div class="label">
                <?php print __('Tor IP day limit') ?>
            </div>
            <input type="text" name="tor_ip_d" class="title" value="<?php print $ss['tor_ip_d'] ?>" style="width:90%">
            <br /><br />


            <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

        </fieldset>
    </div>
</form>