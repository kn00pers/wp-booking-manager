<?php
namespace CBM\Helpers;

defined( 'ABSPATH' ) || exit;

final class Labels {

	public static function statuses(): array {
		return [
			'pending'   => __( 'Pending', 'crane-booking-manager' ),
			'approved'  => __( 'Approved', 'crane-booking-manager' ),
			'rejected'  => __( 'Rejected', 'crane-booking-manager' ),
			'cancelled' => __( 'Cancelled', 'crane-booking-manager' ),
		];
	}

	public static function status( string $status ): string {
		return self::statuses()[ $status ] ?? $status;
	}
}
