<h2><a href="<?php print $url ?>"><?php print __('Tor Parser') ?></a>. <?php print __('Add a new Tor service') ?></h2>
<?php
print $tabs;
?>

<form accept-charset="UTF-8" method="post" id="campaign">
    <div class="cm-edit inline-edit-row">
        <fieldset>

            <input type="hidden" name="add_tor" value="1">
            <label>
                <span class="title"><?php print __('Name') ?></span>
                <span class="input-text-wrap"><input type="text" name="name" class="name" value="" placeholder="tor218"></span>
            </label>

            <label>
                <span class="title"><?php print __('Url') ?></span>
                <span class="input-text-wrap"><input type="text" name="url" value="" placeholder="172.17.0.1:8218"></span>
            </label>

            <label>
                <span class="title"><?php print __('Type') ?></span>
                <select id="type" name="type" class="type">                    
                    <?php
                    foreach ($this->service_type as $key => $name) {
                        ?>
                        <option value="<?php print $key ?>"><?php print $name ?></option>                                
                        <?php
                    }
                    ?>                          
                </select> 
                <span class="inline-edit"><?php print __('Type of the service') ?></span>                    
            </label>

            <label class="inline-edit-active">                               
                <input type="checkbox" name="status" value="1">
                <span class="checkbox-title"><?php print __('Active') ?></span>
            </label>   

            <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  

        </fieldset>

    </div>
</form>
