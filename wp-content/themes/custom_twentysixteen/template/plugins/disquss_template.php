<?php
error_reporting(E_ERROR );
global $wpdb;
if (!$wpdb)
{

include($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

global $post_id;
if (!$post_id)
{
    $post_id = $_GET['post_id'];
}
if ($post_id)
{

$post = get_post($post_id);
$l10n = apply_filters( 'dcl_script_file_name', Disqus_Public::embed_vars_for_post( $post ) );
foreach ( (array) $l10n as $key => $value ) {
    if ( !is_scalar($value) )
        continue;
    $l10n[$key] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8');
}
$script = "var embedVars = " . wp_json_encode( $l10n ) . ';';


?>
<div id="disqus_thread"></div>
<script type="text/javascript">
<?php echo $script;  ?>
</script>
<script type="text/javascript" src="<?php echo plugins_url(); ?>/disqus-conditional-load/assets/js/public/embed.min.js?ver=11.0.5"></script>
<?php
}



