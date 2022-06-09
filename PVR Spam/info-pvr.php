<?php

function pvrspam_admin_notice() {
	global $pagenow;
	if ($pagenow == 'edit-comments.php'):
		$user_id = get_current_user_id();
		$pvrspam_info_visibility = get_user_meta($user_id, 'pvrspam_info_visibility', true);
		if ($pvrspam_info_visibility == 1 OR $antispam_info_visibility == ''):
			$pvrspam_stats = get_option('pvrspam_stats', array());
			$blocked_total = $pvrspam_stats['blocked_total'];
			?>
			<div class="update-nag pvrspam-panel-info">
				<p style="margin: 0;">
					<?php echo $blocked_total; ?> spam comments were blocked by <a href="http://prueba.villalpandonet.com/">PVR-spam</a> plugin so far.
					
				</p>
			</div>
			<?php
		endif; // end of if($pvrspam_info_visibility)
	endif; // end of if($pagenow == 'edit-comments.php')
}
add_action('admin_notices', 'pvrspam_admin_notice');


function pvrspam_display_screen_option() {
	global $pagenow;
	if ($pagenow == 'edit-comments.php'):
		$user_id = get_current_user_id();
		$pvrspam_info_visibility = get_user_meta($user_id, 'pvrspam_info_visibility', true);

		if ($pvrspam_info_visibility == 1 OR $pvrspam_info_visibility == '') {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		}

		?>
		<script>
			jQuery(function($){
				$('.pvrspam_screen_options_group').insertAfter('#screen-options-wrap #adv-settings');
			});
		</script>
		<form method="post" class="pvrspam_screen_options_group" style="padding: 20px 0 5px 0;">
			<input type="hidden" name="pvrspam_option_submit" value="1" />
			<label>
				<input name="pvrspam_info_visibility" type="checkbox" value="1" <?php echo $checked; ?> />
				PVR-spam info
			</label>
			<input type="submit" class="button" value="<?php _e('Apply'); ?>" />
		</form>
		<?php
	endif; // end of if($pagenow == 'edit-comments.php')
}


function pvrspam_register_screen_option() {
	add_filter('screen_layout_columns', 'pvrspam_display_screen_option');
}
add_action('admin_head', 'pvrspam_register_screen_option');


function pvrspam_update_screen_option() {
	if (isset($_POST['pvrspam_option_submit']) AND $_POST['pvrspam_option_submit'] == 1) {
		$user_id = get_current_user_id();
		if (isset($_POST['pvrspam_info_visibility']) AND $_POST['pvrspam_info_visibility'] == 1) {
			update_user_meta($user_id, 'pvrspam_info_visibility', 1);
		} else {
			update_user_meta($user_id, 'pvrspam_info_visibility', 0);
		}
	}
}
add_action('admin_init', 'pvrspam_update_screen_option');