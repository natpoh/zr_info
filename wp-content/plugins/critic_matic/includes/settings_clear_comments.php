<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Settings Clear comments') ?></h2>
<?php print $tabs; ?>

<?php
/*
  'all' => '',
  'first' => '',
  'replace' => '',
  'white' => '',
 */



if (isset($_POST['clear-comments-nonce'])) {
    $nonce = wp_verify_nonce($_POST['clear-comments-nonce'], 'clear-comments-options');
    $result = '';

    if ($nonce) {
        $cc->update_settings($_POST);
    }
    if ($result) {
        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
    }
}
$settings = $cc->get_settings(false);
?>

<form accept-charset="UTF-8" method="post" id="options">

    <h1><?php print __('Black list', 'clear-comments') ?></h1>

    <h3>All stars *****</h3>
    <div class="form-field">	
        <textarea name="all" id="all" cols="50" rows="10"><?php print htmlspecialchars($settings['all']) ?></textarea> 
    </div>
    <div class="form-field">
        <label for="all"><?php print __('Еnter keywords, one word per line', 'clear-comments') ?></label>
    </div>

    <h3>First and end s***s</h3>
    <div class="form-field">	
        <textarea name="first" id="first" cols="50" rows="10"><?php print htmlspecialchars($settings['first']) ?></textarea> 
    </div>    
    <div class="form-field">
        <label for="first"><?php print __('Еnter keywords, one word per line', 'clear-comments') ?></label>
    </div>

    <h3>Replace words</h3>
    <div class="form-field">	
        <textarea name="replace" id="replace" cols="50" rows="10"><?php print htmlspecialchars($settings['replace'])  ?></textarea> 
    </div>    
    <div class="form-field">
        <label for="replace"><?php print __('Example: "nigger,nygger,niger:jagger". Еnter keywords, one word per line', 'clear-comments') ?></label>
    </div>

    <h1><?php print __('White list', 'clear-comments') ?></h1>
    <div class="form-field">
        <textarea name="white" id="white" cols="50" rows="10"><?php print htmlspecialchars($settings['white']) ?></textarea>
    </div>	    
    <div class="form-field">
        <label for="white"><?php print __('Еnter exclusion keywords, one word per line', 'clear-comments') ?></label>
    </div>
    <br />
    <?php wp_nonce_field('clear-comments-options', 'clear-comments-nonce'); ?>

    <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save', 'clear-comments') ?>" class="button-primary">           

</form>