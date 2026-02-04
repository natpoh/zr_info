<h2><a href="<?php print $url ?>"><?php print __('Movies Links Parsers') ?></a>. <?php print __('Clone campaign') ?></h2>
<?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="campaign">
    <div class="cm-edit inline-edit-row">
        <fieldset>

            <input type="hidden" name="clone_campaign" value="<?php print $campaign->id ?>">            
            <label>
                <span class="title"><?php print __('Title') ?></span>
                <span class="input-text-wrap"><input type="text" name="title" class="title" value="<?php print $campaign->title ?> Clone"></span>
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
