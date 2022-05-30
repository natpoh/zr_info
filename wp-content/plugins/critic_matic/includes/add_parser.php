<h2><a href="<?php print $url ?>"><?php print __('Critic parsers') ?></a>. <?php print __('Add a new campaign') ?></h2>
    <?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="campaign" class="cm-type--1">
    <div class="cm-edit inline-edit-row">
        <fieldset>
            <label>
                <span class="title"><?php print __('Type') ?></span>
                <select id="add-campaing-type" name="type" class="type">
                    <option value="-1"><?php print 'Select' ?></option>                                
                    <?php
                    foreach ($this->cp->parser_type as $key => $name) {
                        ?>
                        <option value="<?php print $key ?>"><?php print $name ?></option>                                
                        <?php
                    }
                    ?>                          
                </select> 
                <span class="inline-edit"><?php print __('Type of the Campaign') ?></span>                    
            </label>

            <div class="campaing-options">

                <div class="find-channel-id">
                    <p><b>Find the Channel ID</b></p>
                    <div id="error"></div>
                    <label>
                        <span class="title"><?php print __('Find') ?></span>
                        <span class="input-text-wrap"><input type="text" name="yt_find" placeholder="Type the Channel or Video URL" id="yt_find" value=""></span>
                    </label>
                    <div class="desc">
                        Example Channel: https://www.youtube.com/channel/UC337i8LcUSM4UMbLf820I8Q<br />
                        Example Video: https://www.youtube.com/watch?v=DapcHLXSLPo
                    </div>
                    <br />
                    <input type="submit" name="options" id="find-channel" value="<?php echo __('Find the Channel id') ?>" class="button-secondary"> 
                    <br /><br />
                </div>

                <div class="step-2">
                    <label class="channel-id">
                        <p>Total URLs found: <span id="total_found"></span></p>
                        <span class="title"><?php print __('Channel ID') ?></span>
                        <span class="input-text-wrap"><input type="text" name="yt_page" placeholder="Type the Channel URL or video URL" id="yt_page" value=""></span>
                    </label>

                    <label>
                        <span class="title"><?php print __('Title') ?></span>
                        <span class="input-text-wrap"><input type="text" name="title" id="title" value=""></span>
                    </label>

                    <label>
                        <span class="title"><?php print __('Site') ?></span>
                        <span class="input-text-wrap"><input id="site" type="text" name="site" value=""></span>
                    </label>

                    <label class="inline-edit-author">
                        <span class="title"><?php print __('Author') ?></span>
                        <select name="author" class="authors">
                            <?php
                            if (sizeof($authors)) {
                                foreach ($authors as $author) {
                                    ?>
                                    <option value="<?php print $author->id ?>"><?php print stripslashes($author->name) ?></option>                                
                                    <?php
                                }
                            }
                            ?>                       
                        </select>
                    </label>

                    <label class="inline-edit-interval">
                        <span class="title"><?php print __('Update') ?></span>
                        <select name="interval" class="interval">
                            <?php
                            foreach ($update_interval as $key => $name) {
                                $selected = ($key == $def_options['update_interval']) ? 'selected' : '';
                                ?>
                                <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                <?php
                            }
                            ?>                          
                        </select> 
                        <span class="inline-edit"><?php print __('The parser update interval') ?></span>                    
                    </label>
                    <br />

                    <label class="inline-edit-active">                               
                        <input type="checkbox" name="status" value="1">
                        <span class="checkbox-title"><?php print __('Campaign is active') ?></span>
                    </label>            

                    <label class="inline-edit-active">                               
                        <input type="checkbox" name="parser_status" value="1">
                        <span class="checkbox-title"><?php print __('Parser is active') ?></span>
                    </label>            
                    
                    <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                    <br />
                    <input type="submit" name="options" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary"> 
                </div>
            </div>
        </fieldset>

    </div>
</form>
