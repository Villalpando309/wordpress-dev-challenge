<?php
/*
Plugin Name: PVR-spam
Plugin URI: http://prueba.villalpandonet.com/blogprueba/
Description: No spam in comments. No captcha.
Version: 3.5
Author: Pamela Villalpando
Author URI: http://prueba.villalpandonet.com/blogprueba/
*/

$pvrspam_send_spam_comment_to_admin = false; // if true, than rejected spam comments will be sent to admin email

$pvrspam_allow_trackbacks = false; // if true, than trackbacks will be allowed
// trackbacks almost not used by users, but mostly used by spammers; pingbacks are always enabled


define('PVRSPAM_VERSION', '3.5');

$antispam_settings = array(
	'send_spam_comment_to_admin' => $pvrspam_send_spam_comment_to_admin,
	'allow_trackbacks' => $pvrspam_allow_trackbacks,
	'version' => ANTISPAM_VERSION,
	'admin_email' => get_option('admin_email')
);

include('functions-pvr.php');
include('nfo-pvr.php');


function pvrspam_enqueue_script() {
	if (is_singular() && comments_open()) { // load script only for pages with comments form
		wp_enqueue_script('pvr-spam-script', plugins_url('/js/pvr-spam-3.5.js', __FILE__), array('jquery'), null, true);
	}
}
add_action('wp_enqueue_scripts', 'pvrspam_enqueue_script');


function pvrspam_form_part() {
	global $apvrspam_settings;
	$rn = "\r\n"; // .chr(13).chr(10)

	if ( ! is_user_logged_in()) { // add anti-spam fields only for not logged in users
		echo '		<p class="pvrspam-group pvrspam-group-q" style="clear: both;">
			<label>Current ye@r <span class="required">*</span></label>
			<input type="hidden" name="antspm-a" class="pvrspam-control pvrspam-control-a" value="'.date('Y').'" />
			<input type="text" name="antspm-q" class="pvrspam-control pvrspam-control-q" value="'.$pvrspam_settings['version'].'" autocomplete="off" />
		</p>'.$rn; // question (hidden with js)

		echo '		<p class="pvrspam-group pvrspam-group-e" style="display: none;">
			<label>Leave this field empty</label>
			<input type="text" name="antspm-e-email-url-website" class="pvrspam-control pvrspam-control-e" value="" autocomplete="off" />
		</p>'.$rn; // empty field (hidden with css); trap for spammers because many bots will try to put email or url here
	}
}
add_action('comment_form', 'antispam_form_part'); // add pvr-spam inputs to the comment form


function pvrspam_check_comment($commentdata) {
	global $pvrspam_settings;
	$rn = "\r\n"; // .chr(13).chr(10)

	extract($commentdata);

	$pvrspam_pre_error_message = '<p><strong><a href="javascript:window.history.back()">Go back</a></strong> and try again.</p>';
	$pvrspam_error_message = '';

	if ($pvrspam_settings['send_spam_comment_to_admin']) { // if sending email to admin is enabled
		$post = get_post($comment->comment_post_ID);
		$pvrspam_message_spam_info  = 'Spam for post: "'.$post->post_title.'"' . $rn;
		$pvrspam_message_spam_info .= get_permalink($comment->comment_post_ID) . $rn.$rn;

		$pvrspam_message_spam_info .= 'IP: ' . $_SERVER['REMOTE_ADDR'] . $rn;
		$pvrspam_message_spam_info .= 'User agent: ' . $_SERVER['HTTP_USER_AGENT'] . $rn;
		$pvrspam_message_spam_info .= 'Referer: ' . $_SERVER['HTTP_REFERER'] . $rn.$rn;

		$pvrspam_message_spam_info .= 'Comment data:'.$rn; // lets see what comment data spammers try to submit
		foreach ($commentdata as $key => $value) {
			$pvrspam_message_spam_info .= '$commentdata['.$key. '] = '.$value.$rn;
		}
		$pvrspam_message_spam_info .= $rn.$rn;

		$pvrspam_message_spam_info .= 'Post vars:'.$rn; // lets see what post vars spammers try to submit
		foreach ($_POST as $key => $value) {
			$pvrspam_message_spam_info .= '$_POST['.$key. '] = '.$value.$rn;
		}
		$pvrspam_message_spam_info .= $rn.$rn;

		$apvrspam_message_spam_info .= 'Cookie vars:'.$rn; // lets see what cookie vars spammers try to submit
		foreach ($_COOKIE as $key => $value) {
			$pvrspam_message_spam_info .= '$_COOKIE['.$key. '] = '.$value.$rn;
		}
		$pvrspam_message_spam_info .= $rn.$rn;

		$pvrspam_message_append = '-----------------------------'.$rn;
		$pvrspam_message_append .= 'This is spam comment rejected by PVR-spam plugin ' . $rn;
		$pvrspam_message_append .= 'You may edit "pvr-spam.php" file and disable this notification.' . $rn;
		$pvrspam_message_append .= 'You should find "$pvrspam_send_spam_comment_to_admin" and make it equal to "false".' . $rn;
	}

	if ( ! is_user_logged_in() && $comment_type != 'pingback' && $comment_type != 'trackback') { // logged in user is not a spammer
		$spam_flag = false;

		if (trim($_POST['antspm-q']) != date('Y')) { // year-answer is wrong - it is spam
			$spam_flag = true;
			if (empty($_POST['antspm-q'])) { // empty answer - it is spam
				$pvrspam_error_message .= 'Error: empty answer. ['.$_POST['antspm-q'].']<br> '.$rn;
			} else {
				$pvrspam_error_message .= 'Error: answer is wrong. ['.$_POST['antspm-q'].']<br> '.$rn;
			}
		}

		if ( ! empty($_POST['antspm-e-email-url-website'])) { // trap field is not empty - it is spam
			$spam_flag = true;
			$pvrspam_error_message .= 'Error: field should be empty. ['.$_POST['antspm-e-email-url-website'].']<br> '.$rn;
		}

		if ($spam_flag) { // it is spam
			$pvrspam_error_message .= '<strong>Comment was blocked because it is spam.</strong><br> ';
			if ($pvrspam_settings['send_spam_comment_to_admin']) {
				$pvrspam_subject = 'Spam comment on site ['.get_bloginfo('name').']'; // email subject
				$pvrspam_message = '';
				$pvrspam_message .= $pvrspam_error_message . $rn.$rn;
				$pvrspam_message .= $pvrspam_message_spam_info; // spam comment, post, cookie and other data
				$pvrspam_message .= $pvrspam_message_append;
				@wp_mail($pvrspam_settings['admin_email'], $pvrspam_subject, $pvrspam_message); // send spam comment to admin email
			}
			antispam_log_stats();
			wp_die( $pvrspam_pre_error_message . $pvrspam_error_message ); // die - do not send comment and show errors
		}
	}

	if ( ! $pvrspam_settings['allow_trackbacks']) { // if trackbacks are blocked (pingbacks are alowed)
		if ($comment_type == 'trackback') { // if trackbacks ( || $comment_type == 'pingback')
			$pvrspam_error_message .= 'Error: trackbacks are disabled.<br> ';
			if ($pvrspam_settings['send_spam_comment_to_admin']) { // if sending email to admin is enabled
				$pvrspam_subject = 'Spam trackback on site ['.get_bloginfo('name').']'; // email subject
				$pvrspam_message = '';
				$pvrspam_message .= $pvrspam_error_message . $rn.$rn;
				$pvrspam_message .= $pvrspam_message_spam_info; // spam comment, post, cookie and other data
				$pvrspam_message .= $antispam_message_append;
				@wp_mail($pvrspam_settings['admin_email'], $pvrspam_subject, $pvrspam_message); // send trackback comment to admin email
			}
			pvrspam_log_stats();
			wp_die($pvrspam_pre_error_message . $pvrspam_error_message); // die - do not send trackback
		}
	}

	return $commentdata; // if comment does not looks like spam
}

if ( ! is_admin()) {
	add_filter('preprocess_comment', 'pvrspam_check_comment', 1);
}


function pvrspam_plugin_meta($links, $file) { // add some links to plugin meta row
	if (strpos($file, 'anti-spam.php') !== false) {
		$links = array_merge($links, array('<a href="http://prueba.villalpandonet.com/blogprueba/" title="Plugin page">PVR-spam</a>'));
		$links = array_merge($links, array('<a href="http://prueba.villalpandonet.com/blogprueba/" title="Support the development">Donate</a>'));
		$links = array_merge($links, array('<a http://prueba.villalpandonet.com/blogprueba/" title="Upgrade to Pro">PVR-spam Pro</a>'));
	}
	return $links;
}
add_filter('plugin_row_meta', 'pvrspam_plugin_meta', 10, 2);