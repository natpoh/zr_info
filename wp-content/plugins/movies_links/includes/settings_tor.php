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

            <div class="label">
                <?php print __('Tor Log limit') ?>
            </div>
            <input type="text" name="tor_log" class="title" value="<?php print $ss['tor_log'] ?>" style="width:90%">
            <br /><br />
            
            <label class="inline-edit-interval">
                <span class="title"><?php print __('Tor agent') ?></span>
                <select name="tor_agent" class="interval">
                    <?php
                    $current = $ss['tor_agent'];
                    foreach ($this->tor_agent as $key => $name) {
                        $selected = ($key == $current) ? 'selected' : '';
                        ?>
                        <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                        <?php
                    }
                    ?>                          
                </select>                                        
            </label>

            <p><b>Export</b> services to <a target="_blank" href="<?php print $url ?>&export_services=1">list</a>.</p>
            <p><b>Import</b> URLs from list. Example: TYPE|IP|NAME</p>

            <fieldset>              
                <textarea name="import_services_list" style="width:100%" rows="3"></textarea>           
            </fieldset>

            <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

        </fieldset>
    </div>
</form>