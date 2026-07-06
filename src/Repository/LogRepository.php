<?php
namespace CBM\Repository;

defined( 'ABSPATH' ) || exit;

final class LogRepository {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'cbm_booking_logs';
	}

	public function log_status_change(
		int $booking_id,
		?string $old_status,
		string $new_status,
		?int $user_id = null,
		?string $note = null
	): void {
		global $wpdb;
		$wpdb->insert(
			$this->table,
			[
				'booking_id'         => $booking_id,
				'old_status'         => $old_status,
				'new_status'         => $new_status,
				'changed_by_user_id' => $user_id,
				'note'               => $note,
			],
			[ '%d', '%s', '%s', '%d', '%s' ]
		);
	}

	
	public function get_for_booking( int $booking_id ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$this->table}` WHERE booking_id = %d ORDER BY created_at ASC",
				$booking_id
			)
		) ?: [];
	}
}
