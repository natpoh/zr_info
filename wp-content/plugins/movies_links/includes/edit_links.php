<h2><a href="<?php print $url ?>"><?php print __('Movies Links. Link to movies') ?></a>. <?php print __('Edit') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;


if ($cid) {
    $options = $this->mp->get_options($campaign);
    $o = $options['links'];
    ?>
    <form accept-charset="UTF-8" method="post" id="campaign">

        <div class="cm-edit inline-edit-row">
            <fieldset>

                <input type="hidden" name="edit_parsing_data" value="1">
                <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Update') ?></span>
                    <select name="interval" class="interval">
                        <?php
                        $inetrval = $o['interval'];
                        foreach ($this->update_interval as $key => $name) {
                            $selected = ($key == $inetrval) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select> 
                    <span class="inline-edit"><?php print __('The link parser update interval') ?></span>                    
                </label>

                <label class="inline-edit-interval"> 
                    <span class="title"><?php print __('URLs count') ?></span>         
                    <select name="num" class="interval">
                        <?php
                        foreach ($this->parse_number as $key => $name) {
                            $selected = ($key == $o['num']) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                     
                    <span class="inline-edit"><?php print __('Number of posts for cron parsing') ?></span> 
                </label>

                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($o['status'] == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Parser is active') ?></span>
                </label>

                <label>
                    <span class="title"><?php print __('Min match') ?></span>
                    <span class="input-text-wrap"><input type="match" name="match" class="match" value="<?php print $o['match'] ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Min rating') ?></span>
                    <span class="input-text-wrap"><input type="rating" name="rating" class="rating" value="<?php print $o['rating'] ?>"></span>
                </label>

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Link to') ?></span>
                    <select name="type" class="interval">
                        <?php
                        foreach ($this->rwt_movie_type as $key => $value) {
                            $selected = ($key == $o['type']) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $value ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select> 
                    <span class="inline-edit"><?php print __('Choose where to look for a match: in Movies or TVSeries.') ?></span>                    
                </label>

                <h2>Links rules</h2>
                <?php
                $rules = $o['rules'];
                $data_fields = $this->mp->get_parser_fields($options);
                $this->show_links_rules($rules, $data_fields);
                ?> 
                <label class="inline-edit-status">                
                    <input type="checkbox" name="preview" value="1" checked="checked">
                    <span class="checkbox-title"><?php print __('Preview') ?></span>
                </label>
                <label class="inline-edit-interval">                    
                    <select name="pr_num" class="interval">
                        <?php
                        foreach ($this->parse_number as $key => $name) {
                            $selected = ($key == $o['pr_num']) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                     
                    <span class="inline-edit"><?php print __('Number of previews') ?></span> 
                </label>

                <br />
                <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  
            </fieldset>
        </div>
    </form>

    <?php
    if (isset($_POST['preview'])) {
        $this->preview_links_search($preivew_data);
    }
    ?>

<?php } ?>