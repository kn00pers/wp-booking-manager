<?php
namespace CBM;

defined( 'ABSPATH' ) || exit;

final class Deactivator {

	public static function deactivate(): void {
		remove_role( 'cbm_operator' );
		wp_clear_scheduled_hook( 'cbm_send_reminders' );
		flush_rewrite_rules();
	}
}
