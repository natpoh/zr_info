<h2><a href="<?php print $url ?>"><?php print __('Movies Links Parser') ?></a>. <?php print __('Find URLs') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>

    <?php
}

print $tabs;


if ($cid) {
    $options = $this->mp->get_options($campaign);
    $find_urls = $options['find_urls'];
    ?>


    <form accept-charset="UTF-8" method="post" id="generate_urls">

        <div class="cm-edit inline-edit-row">
            <fieldset>              
                <?php
                $service_urls = $options['service_urls'];
                ?>      
                <input type="hidden" name="service_urls" value="1">
                <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">              

                <h3>Parser settings</h3>

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Parse with') ?></span>
                    <select name="webdrivers" class="interval">
                        <?php
                        $current = $service_urls['webdrivers'];
                        foreach ($this->parse_mode as $key => $name) {
                            $selected = ($key == $current) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                                        
                </label>

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Tor mode') ?></span>
                    <select name="tor_mode" class="interval">
                        <?php
                        $current = $service_urls['tor_mode'];
                        foreach ($this->tor_mode as $key => $name) {
                            $selected = ($key == $current) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                                        
                </label>


                <label class="inline-edit-interval"> 
                    <span class="title"><?php print __('Tor hour') ?></span>         
                    <?php
                    $parse_num = $service_urls['tor_h'];
                    $previews_number = $this->parse_number;
                    ?>

                    <select name="tor_h" class="tor_h">
                        <?php
                        foreach ($previews_number as $key => $name) {
                            $selected = ($key == $parse_num) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                     
                    <span class="inline-edit"><?php print __('Number of URLs parsing from one IP at one hour') ?></span> 
                </label>


                <label class="inline-edit-interval"> 
                    <span class="title"><?php print __('Tor day') ?></span>         
                    <?php
                    $parse_num = $service_urls['tor_d'];
                    $previews_number = $this->parse_number;
                    ?>

                    <select name="tor_d" class="tor_d">
                        <?php
                        foreach ($previews_number as $key => $name) {
                            $selected = ($key == $parse_num) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                     
                    <span class="inline-edit"><?php print __('Number of URLs parsing from one IP at one day') ?></span> 
                </label>

                <label>
                    <span class="title"><?php print __('Weight') ?></span>
                    <span class="input-text-wrap"><input type="text" name="weight" class="weight" value="<?php print $service_urls['weight'] ?>"></span>
                </label>


                <h3>Garbage collector</h3>
                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($service_urls['del_pea'] == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="del_pea" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Delete archives with errors') ?></span>
                </label>

                <label class="inline-edit-interval">

                    <select name="del_pea_cnt" class="interval">
                        <?php
                        $inetrval = $service_urls['del_pea_cnt'];
                        foreach ($this->parse_number as $key => $name) {
                            $selected = ($key == $inetrval) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select> 
                    <span class="inline-edit"><?php print __('URLs count') ?></span>                    
                </label>

                <br />
                <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save settings') ?>" class="button-primary">  
            </fieldset>
        </div>
    </form>

    <?php if ($campaign->type != 2): ?>

        <h2><?php print __('Search URL addresses on site pages') ?></h2>

        <form accept-charset="UTF-8" method="post" id="find_urls">     

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
                    <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
                    <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save settings') ?>" class="button-primary">  
                    <a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&find_urls=1" class="button-secondary">Find URLs</a> 

                </fieldset>
            </div>
        </form>
        <br />
        <?php if ($preivew_data) { ?>

            <h3>Find URLs</h3>
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
                                foreach ($this->parser_interval as $key => $name) {
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

                        <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
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
                    <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
                    <input type="submit" name="options" id="edit-submit" value="<?php echo __('Submit URLs') ?>" class="button-secondary">  
                </fieldset>
            </div>
        </form>
    <?php endif; ?>
    <br />
    <hr />
    <h2>Generate URLs from RWT database</h2>

    <form accept-charset="UTF-8" method="post" id="generate_urls">

        <div class="cm-edit inline-edit-row">
            <fieldset>              
                <?php
                $gen_urls = $options['gen_urls'];
                ?>
                <input type="hidden" name="generate_urls" value="1">
                <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">                 
                <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
                <p><b>Cron update settings</b></p>
                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($gen_urls['status'] == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Cron automatically generates new URLs is active') ?></span>
                </label>
                <label class="inline-edit-interval"> 
                    <span class="title"><?php print __('URLs count') ?></span>         
                    <?php
                    $parse_num = $gen_urls['num'];
                    $previews_number = $this->gen_urls_number;
                    ?>
                    <select name="num" class="interval">
                        <?php
                        foreach ($previews_number as $key => $name) {
                            $selected = ($key == $parse_num) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                     
                    <span class="inline-edit"><?php print __('Number of URLs for cron parsing') ?></span> 
                </label>
                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Update') ?></span>
                    <select name="interval" class="interval">
                        <?php
                        $inetrval = $gen_urls['interval'];
                        foreach ($this->parser_interval as $key => $name) {
                            $selected = ($key == $inetrval) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select> 
                    <span class="inline-edit"><?php print __('The cron update interval') ?></span>                    
                </label>
                <label>
                    <span class="title"><?php print __('Last Id') ?></span>
                    <span class="input-text-wrap"><input type="text" name="last_id" value="<?php print $gen_urls['last_id'] ?>" disabled="disabled"></span>
                </label>

                <label class="inline-edit-status">                                
                    <input type="checkbox" name="reset" value="1" >
                    <span class="checkbox-title"><?php print __('Reset last Id') ?></span>
                </label>
                <br />

                <h2>Generate URLs settings</h2>

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Data type') ?></span>
                    <select name="type" class="interval">
                        <?php
                        if ($campaign->type == 1) {
                            // Actors
                            foreach ($this->rwt_actor_type as $key => $value) {
                                $selected = ($key == $gen_urls['type']) ? 'selected' : '';
                                ?>
                                <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $value ?></option>                                
                                <?php
                            }
                        } else {
                            // Movies
                            foreach ($this->rwt_movie_type as $key => $value) {
                                $selected = ($key == $gen_urls['type']) ? 'selected' : '';
                                ?>
                                <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $value ?></option>                                
                                <?php
                            }
                        }
                        ?>                          
                    </select> 
                    <span class="inline-edit"><?php print __('Select the data source from which the addresses will be generated.') ?></span>                    
                </label>

                <label>
                    <span class="title"><?php print __('Page') ?></span>
                    <span class="input-text-wrap"><input type="text" name="page" placeholder="Example: https://example.com/api?query={title}" value="<?php print htmlspecialchars(base64_decode($gen_urls['page'])) ?>"></span>
                </label>
                <label>
                    <span class="title"><?php print __('Regexp') ?></span>
                    <span class="input-text-wrap"><input type="text" name="regexp" placeholder="<?php print htmlspecialchars('Example: /<a[^>]+href="([^"]+)"[^>]*>Read More<\/a>/; $1'); ?>" value="<?php print htmlspecialchars(base64_decode($gen_urls['regexp'])) ?>"></span>
                </label>

                <h3>Templates from the database</h3>
                <?php
                if ($campaign->type == 1) {
                    // Actors tpl
                    $tpl_data = $this->get_actors_templates();
                } else {
                    // Movies tpl
                    $tpl_data = $this->get_name_templates();
                }
                if ($tpl_data) {
                    ?>
                    <table class="wp-list-table widefat striped table-view-list">
                        <thead>
                            <tr>
                                <th><?php print __('Name') ?></th>                
                                <th><?php print __('Value example') ?></th>    
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tpl_data as $key => $value) { ?>
                                <tr>
                                    <td><?php print $key ?></td>
                                    <td><?php print $value ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>        
                    </table>
                    <?php
                }
                ?>

                <label class="inline-edit-status">                
                    <input type="checkbox" name="preview" value="1" checked="checked">
                    <span class="checkbox-title"><?php print __('Preview first page') ?></span>
                </label>

                <br />
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save settings') ?>" class="button-primary">  
                <a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&gen_urls=1" class="button-secondary">Generate URLs</a>   
            </fieldset>
        </div>
    </form>

    <?php if ($preview_gen_data) { ?>

        <h3>Preview Generate URLs</h3>
        <p>URL: <?php print $preview_gen_data['url'] ?></p>
        <h2>Headers</h2>
        <textarea style="width: 90%; height: 300px;"><?php print $preview_gen_data['headers'] ?></textarea>        
        <h2>Content</h2>
        <textarea style="width: 90%; height: 300px;"><?php print htmlspecialchars($preview_gen_data['content']) ?></textarea>      

    <?php } ?>



<?php } ?>