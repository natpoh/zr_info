<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Settings actors') ?></h2>
<?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="tag">
    <div class="cm-edit">
        <fieldset>    

            <h2><?php print __('Stars') ?></h2>
            <input type="hidden" name="actors_rating" value="1">
            <div class="label">
                <?php print __('Search string') ?>
            </div>
            <input type="text" name="actors_star_ss" class="title" value="<?php print $ss['actors_star_ss'] ?>" style="width:90%">
            <br /><br />
            <div class="label">
                <?php print __('Wait interval') ?>
            </div>
            <input type="text" name="actors_star_wait" class="title" value="<?php print $ss['actors_star_wait'] ?>" style="width:90%">
            <div class="desc">Interval in days after which the recalculation will begin</div>
            <br /><br />

            <?php
            $star_stat = $maw->get_statistics(0);
            if ($star_stat):
                ?>
                <h3><?php print __('Statistics') ?></h3>    
                <table class="wp-list-table widefat striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php print __('Name') ?></th>                
                            <th><?php print __('Value') ?></th>    
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($star_stat as $key => $value): ?>
                            <tr>
                                <td><?php print $key ?></td>
                                <td><?php print $value ?></td>
                            </tr>            
                        <?php endforeach; ?>    
                    </tbody>        
                </table>
                <br />
            <?php endif ?>    
            <label class="inline-edit-status"> 
                <input type="checkbox" name="stars_reset" value="1">
                <span class="checkbox-title">Reset current stars progress</span>
            </label>

            <h2><?php print __('Main') ?></h2>

            <div class="label">
                <?php print __('Search string') ?>
            </div>
            <input type="text" name="actors_main_ss" class="title" value="<?php print $ss['actors_main_ss'] ?>" style="width:90%">
            <br /><br />
            <div class="label">
                <?php print __('Wait interval') ?>
            </div>
            <input type="text" name="actors_main_wait" class="title" value="<?php print $ss['actors_main_wait'] ?>" style="width:90%">
            <div class="desc">Interval in days after which the recalculation will begin</div>
            <br /><br />
            
                        <?php
            $main_stat = $maw->get_statistics(1);
            if ($main_stat):
                ?>
                <h3><?php print __('Statistics') ?></h3>    
                <table class="wp-list-table widefat striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php print __('Name') ?></th>                
                            <th><?php print __('Value') ?></th>    
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($main_stat as $key => $value): ?>
                            <tr>
                                <td><?php print $key ?></td>
                                <td><?php print $value ?></td>
                            </tr>            
                        <?php endforeach; ?>    
                    </tbody>        
                </table>
                <br />
            <?php endif ?> 
            
            <label class="inline-edit-status"> 
                <input type="checkbox" name="main_reset" value="1">
                <span class="checkbox-title">Reset current main progress</span>
            </label>

            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br /><br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

        </fieldset>
    </div>
</form>