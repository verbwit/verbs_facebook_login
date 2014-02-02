<?php // create custom plugin settings menu
add_action('admin_menu', 'verb_fb_login_create_menu');

function verb_fb_login_create_menu() {

	//create new top-level menu
	add_menu_page('Verb\'s Facebook Login', 'Facebook Login Admin', 'administrator', __FILE__, 'vfbl_settings_page',plugins_url('../images/fb_icon.png', __FILE__));

	//call register settings function
	add_action( 'admin_init', 'register_mysettings' );
}


function register_mysettings() {
	//register our settings
	register_setting( 'vfbl-settings-group', 'vfbl_app_id' );
	register_setting( 'vfbl-settings-group', 'vfbl_app_secret' );
	register_setting( 'vfbl-settings-group', 'vfbl_site_url' );
	
	//register_setting( 'vfbl-settings-group', 'some_other_option' );
	//register_setting( 'vfbl-settings-group', 'option_etc' );
}

function vfbl_settings_page() {
?>
<div class="wrap">
<h2>Verb's Facebook Login Admin</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'vfbl-settings-group' ); ?>
    <?php do_settings_sections( 'vfbl-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Facebook App ID</th>
           <td class="phase_text"><input placeholder="Facebook App ID" type="text" name="vfbl_app_id" value="<?php echo get_option('vfbl_app_id'); ?>" /></td>
         </tr>
        
         <tr>
         <th scope="row">Facebook App Secret</th>
        	<td class="phase_text"><input type="text" name="vfbl_app_secret" value="<?php echo get_option('vfbl_app_secret'); ?>" /></td>
         </tr>
        
        <tr>
         <th scope="row">Site URL</th>
        	<td class="phase_text"><input type="text" placeholder="<?php echo get_bloginfo('url'); ?>" name="vfbl_site_url" value="<?php echo get_option('vfbl_site_url'); ?>" /></td>
         </tr>
        
         
      <?php /*?>  <tr valign="top">
        <th scope="row">Some Other Option</th>
        <td><input type="text" name="some_other_option" value="<?php echo get_option('some_other_option'); ?>" /></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Options, Etc.</th>
        <td><input type="text" name="option_etc" value="<?php echo get_option('option_etc'); ?>" /></td>
        </tr><?php */?>
    </table>
    
    <?php submit_button(); ?>

</form>
</div>
<?php }