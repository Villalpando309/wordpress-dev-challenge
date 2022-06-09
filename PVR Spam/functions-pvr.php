<?php

function pvrspam_log_stats() {
	$pvrspam_stats = get_option('pvrspam_stats', array());
	if (array_key_exists('blocked_total', $pvrspam_stats)){
		$pvrspam_stats['blocked_total']++;
	} else {
		$pvrspam_stats['blocked_total'] = 1;
	}
	update_option('pvrspam_stats', $pvrspam_stats);
}