<h2><a href="<?php print $url ?>"><?php print __('Critic parser') ?></a>. <?php print __('Find URLs') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>

    <?php
}

print $tabs;


if ($cid) {
    $options = $this->cp->get_options($campaign);

    if ($campaign->type == 1) {
        $yt_urls = $options['yt_urls'];
        /*
          'yt_force_update' => 1,
          'yt_page' => '',
          'yt_parse_num' => 50,
          'yt_pr_num' => 50,
         * 
          'yt_urls' => array(
          'per_page' => 50,
          'cron_page' => 50,
          'last_update' => 0,
          'status' => 0,
          )
         */
        ?>


        <div style="overflow: hidden">
            <form accept-charset="UTF-8" method="post" id="campaign">

                <div class="cm-edit inline-edit-row">
                    <fieldset>                         
                        <h2><?php print __('YouTube cron settings') ?></h2>
                        <input type="hidden" name="yt_urls" value="1">
                        <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">
                        <label class="inline-edit-status">                
                            <?php
                            $checked = '';
                            if ($yt_urls['status'] == 1) {
                                $checked = 'checked="checked"';
                            }
                            ?>
                            <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                            <span class="checkbox-title"><?php print __('Find new posts by cron') ?></span>
                        </label>

                        <label class="inline-edit-interval">
                            <span class="title"><?php print __('Update') ?></span>
                            <select name="interval" class="interval">
                                <?php
                                $inetrval = $yt_urls['interval'];
                                foreach ($this->cp->parser_interval as $key => $name) {
                                    if ($key < 1440) {
                                        continue;
                                    }
                                    $selected = ($key == $inetrval) ? 'selected' : '';
                                    ?>
                                    <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                    <?php
                                }
                                ?>                          
                            </select> 
                            <span class="inline-edit"><?php print __('The parser update interval') ?></span>                    
                        </label>
                        <label class="inline-edit-interval">
                            <span class="title"><?php print __('Get URLs') ?></span>
                            <select name="cron_page" class="interval">
                                <?php
                                $inetrval = $yt_urls['cron_page'];
                                foreach ($this->cp->yt_per_page as $key => $name) {
                                    $selected = ($key == $inetrval) ? 'selected' : '';
                                    ?>
                                    <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                    <?php
                                }
                                ?>                          
                            </select> 
                            <span class="inline-edit"><?php print __('Get last URLs by cron') ?></span>                    
                        </label>

                        <label>
                            <span class="title"><?php print __('Last upd.') ?></span>
                            <span class="input-text-wrap"><input type="text" disabled="disabled" name="last_update" value="<?php print $yt_urls['last_update'] ?>"></span>
                        </label>

                        <label>
                            <span class="title"><?php print __('Last upd. all') ?></span>
                            <span class="input-text-wrap"><input type="text" disabled="disabled" name="last_update_all" value="<?php print $yt_urls['last_update_all'] ?>"></span>
                        </label>
                        
                        <h2><?php print __('Find URLs settings') ?></h2>

                        <label class="inline-edit-interval">                            
                            <span class="title"><?php print __('Per page') ?></span>
                            <select name="per_page" class="interval">
                                <?php
                                $inetrval = $yt_urls['per_page'];
                                foreach ($this->cp->yt_per_page as $key => $name) {
                                    $selected = ($key == $inetrval) ? 'selected' : '';
                                    ?>
                                    <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                    <?php
                                }
                                ?>                          
                            </select> 
                            <span class="inline-edit"><?php print __('Get URLs per page from YouTube API') ?></span>                    
                        </label>

                        <label>
                            <span class="title"><?php print __('Channel ID') ?></span>
                            <span class="input-text-wrap"><input type="text" name="yt_page" placeholder="Leave blank to search for Channel ID by Campaign URL address" class="title" value="<?php print htmlspecialchars(base64_decode($options['yt_page'])) ?>"></span>
                        </label>


                        <?php
                        $playlists = $this->cp->yt_playlists_select($options);
                        if ($playlists) {
                            ?><h3>Playlists</h3><?php
                            $playlists_checked = $options['yt_playlists'] ? $options['yt_playlists'] : array();

                            foreach ($playlists as $p_id => $p_title) {
                                $checked = '';
                                if (in_array($p_id, $playlists_checked)) {
                                    $checked = 'checked="checked"';
                                }
                                ?>
                                <label class="inline-edit-status">  
                                    <input type="checkbox" name="yt_playlists[]" value="<?php print $p_id ?>" <?php print $checked ?> >
                                    <span class="checkbox-title"><?php print $p_title ?></span>
                                </label>
                                <?php
                            }
                            ?>
                            <br />
                            <div class="desc">
                                <?php print __('Leave blank to parse all playlists.') ?>
                            </div>
                            <br />
                            <?php
                        }
                        ?>

                        <label class="inline-edit-status">                
                            <input type="checkbox" name="yt_preview" value="1" checked="checked">
                            <span class="checkbox-title"><?php print __('Preview') ?></span>
                        </label>
                        <br />

                        <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                        <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save settings') ?>" class="button-primary">  


                        <?php
                        $yt_posts = $this->cp->yt_total_posts($options);

                        if ($yt_posts != -1) {
                            ?>
                            <p>Total URLs found: <?php print $yt_posts ?></p>
                            <p><a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&find_urls_yt=1" class="button-secondary">Get YouTube URLs</a></p>     
                        <?php } ?>   

                    </fieldset>
                </div>
            </form>
        </div>
        <?php if ($yt_preivew) { ?>
            <br />
            <h2>Find YouTube URLs</h2>
            <div class="content-preview">
                <textarea style="width: 90%; height: 300px;"><?php
            if ($yt_preivew['urls']) {
                foreach ($yt_preivew['urls'] as $value) {
                    print $value . "\n";
                }
            }
            ?></textarea>                                
            </div>      
            <h2>Responce</h2>
            <?php if ($yt_preivew['total']) { ?>
                <p>Total found: <?php print $yt_preivew['total'] ?></p>
            <?php } ?>
            <textarea style="width: 90%; height: 300px;"><pre><?php print_r($yt_preivew['responce']) ?></pre></textarea>    
        <?php } ?>    
        <br />    
        <hr />
        <?php
    } else {
        $find_urls = $options['find_urls'];
        ?>
        <h2><?php print __('Search URL addresses on site pages') ?></h2>
        <div style="overflow: hidden">
            <form accept-charset="UTF-8" method="post" id="campaign">

                <div class="cm-edit inline-edit-row">
                    <fieldset>
                        <input type="hidden" name="find_urls" value="1">
                        <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">

                        <label>
                            <span class="title"><?php print __('First page') ?></span>
                            <span class="input-text-wrap"><input type="text" name="first" placeholder="Example: https://example.com/AutorName" class="title" value="<?php print htmlspecialchars(base64_decode($find_urls['first'])) ?>"></span>
                        </label>

                        <label>
                            <span class="title"><?php print __('Next page') ?></span>
                            <span class="input-text-wrap"><input type="text" name="page" placeholder="Example: https://example.com/AutorName/page/$1" value="<?php print htmlspecialchars(base64_decode($find_urls['page'])) ?>"></span>
                        </label>

                        <label>
                            <span class="title"><?php print __('Page from') ?></span>
                            <span class="input-text-wrap"><input type="text" name="from" placeholder="Default: 2" value="<?php print $find_urls['from'] ?>"></span>
                        </label>

                        <label>
                            <span class="title"><?php print __('Page to') ?></span>
                            <span class="input-text-wrap"><input type="text" name="to" placeholder="Max page" value="<?php print $find_urls['to'] ?>"></span>
                        </label>

                        <label>
                            <span class="title"><?php print __('Match reg') ?></span>
                            <span class="input-text-wrap"><input type="text" name="match" placeholder="<?php print htmlspecialchars('Example: /<a[^>]+href="([^"]+)"[^>]*>Read More<\/a>/'); ?>" value="<?php print htmlspecialchars(base64_decode($find_urls['match'])) ?>"></span>
                        </label>

                        <label class="inline-edit-interval">
                            <span class="title"><?php print __('Wait') ?></span>
                            <select name="wait" class="interval">
                                <?php
                                $interval = array(1, 2, 3);
                                foreach ($interval as $key) {
                                    $selected = ($key == $find_urls['wait']) ? 'selected' : '';
                                    ?>
                                    <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $key ?> sec.</option>                                
                                    <?php
                                }
                                ?>                          
                            </select> 
                            <span class="inline-edit"><?php print __('Waiting before loading the next page') ?></span>                    
                        </label>

                        <label class="inline-edit-status">                
                            <input type="checkbox" name="preview" value="1" checked="checked">
                            <span class="checkbox-title"><?php print __('Preview first page') ?></span>
                        </label>
                        <br />
                        <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                        <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save settings') ?>" class="button-primary">  
                        <a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&find_urls=1" class="button-secondary">Find URLs</a> 

                    </fieldset>
                </div>
            </form>
        </div>

        <?php if ($preivew_data) { ?>

            <br />
            <h2>Find URLs</h2>
            <div class="content-preview">
                <textarea style="width: 90%; height: 300px;"><?php
            if ($preivew_data['urls']) {
                foreach ($preivew_data['urls'] as $value) {
                    print $value . "\n";
                }
            }
            ?></textarea>                                
            </div>
            <h2>Headers</h2>
            <textarea style="width: 90%; height: 300px;"><?php print $preivew_data['headers'] ?></textarea>        
            <h2>Content</h2>
            <textarea style="width: 90%; height: 300px;"><?php print htmlspecialchars($preivew_data['content']) ?></textarea>      

        <?php } ?>
        <br />


        <hr />
        <?php
        $cron_urls = $options['cron_urls'];
        ?>
        <h2><?php print __('Regularly fetching URLs from a website page') ?></h2>
        <div style="overflow: hidden">
            <form accept-charset="UTF-8" method="post" id="campaign">

                <div class="cm-edit inline-edit-row">
                    <fieldset>                         
                        <input type="hidden" name="cron_urls" value="1">
                        <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">
                        <label class="inline-edit-status">                
                            <?php
                            $checked = '';
                            if ($cron_urls['status'] == 1) {
                                $checked = 'checked="checked"';
                            }
                            ?>
                            <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                            <span class="checkbox-title"><?php print __('Fetching is active') ?></span>
                        </label>
                        <label class="inline-edit-interval">
                            <span class="title"><?php print __('Update') ?></span>
                            <select name="interval" class="interval">
                                <?php
                                $inetrval = $cron_urls['interval'];
                                foreach ($this->cp->parser_interval as $key => $name) {
                                    $selected = ($key == $inetrval) ? 'selected' : '';
                                    ?>
                                    <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                    <?php
                                }
                                ?>                          
                            </select> 
                            <span class="inline-edit"><?php print __('The parser update interval') ?></span>                    
                        </label>

                        <label>
                            <span class="title"><?php print __('Page') ?></span>
                            <span class="input-text-wrap"><input type="text" name="page" placeholder="Example: https://example.com/AutorName" class="title" value="<?php print htmlspecialchars(base64_decode($cron_urls['page'])) ?>"></span>
                        </label>

                        <label>
                            <span class="title"><?php print __('Match reg') ?></span>
                            <span class="input-text-wrap"><input type="text" name="match" placeholder="<?php print htmlspecialchars('Example: /<a[^>]+href="([^"]+)"[^>]*>Read More<\/a>/'); ?>" value="<?php print htmlspecialchars(base64_decode($cron_urls['match'])) ?>"></span>
                        </label>

                        <label class="inline-edit-status">                
                            <input type="checkbox" name="cron_preview" value="1" checked="checked">
                            <span class="checkbox-title"><?php print __('Preview') ?></span>
                        </label>
                        <br />

                        <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                        <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save settings') ?>" class="button-primary">  

                    </fieldset>
                </div>
            </form>
        </div>

        <?php if ($cron_preivew_data) { ?>

            <br />
            <h2>Fetch URLs</h2>
            <div class="content-preview">
                <textarea style="width: 90%; height: 300px;"><?php
            if ($cron_preivew_data['urls']) {
                foreach ($cron_preivew_data['urls'] as $value) {
                    print $value . "\n";
                }
            }
            ?></textarea>                                
            </div>
            <h2>Headers</h2>
            <textarea style="width: 90%; height: 300px;"><?php print $cron_preivew_data['headers'] ?></textarea>        
            <h2>Content</h2>
            <textarea style="width: 90%; height: 300px;"><?php print htmlspecialchars($cron_preivew_data['content']) ?></textarea>      

        <?php } ?>
        <br />
        <hr />
        <h2>Add URLs list</h2>

        <form accept-charset="UTF-8" method="post" id="add_urls">
            <div class="cm-edit inline-edit-row">
                <fieldset>              
                    <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">
                    <textarea name="add_urls" style="width:100%" rows="10"></textarea>
                    <span class="inline-edit">Each address with a separate line.</span>
                    <br />    <br />
                    <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                    <input type="submit" name="options" id="edit-submit" value="<?php echo __('Submit URLs') ?>" class="button-secondary">  
                </fieldset>
            </div>
        </form>
        <br />
        <?php
    }
}?>