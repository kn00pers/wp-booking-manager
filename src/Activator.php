<?php
namespace CBM;

defined( 'ABSPATH' ) || exit;

final class Activator {

	public static function activate(): void {
		Database\Schema::create();
		Database\Migrator::run();
		self::seed_default_resource();
		self::register_capability();
		self::ensure_secret();
		flush_rewrite_rules();
	}

	private static function ensure_secret(): void {
		if ( ! get_option( 'cbm_secret' ) ) {
			update_option( 'cbm_secret', wp_generate_password( 64, true, true ), false );
		}
	}

	private static function seed_default_resource(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'cbm_resources';
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		if ( $count === 0 ) {
			$wpdb->insert(
				$table,
				[
					'name'      => __( 'Resource #1', 'crane-booking-manager' ),
					'is_active' => 1,
				],
				[ '%s', '%d' ]
			);
		}
	}

	private static function register_capability(): void {
		$role = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( 'manage_cbm_bookings' ) ) {
			$role->add_cap( 'manage_cbm_bookings' );
		}

		if ( ! get_role( 'cbm_operator' ) ) {
			add_role(
				'cbm_operator',
				__( 'Booking operator', 'crane-booking-manager' ),
				[
					'read'                 => true,
					'manage_cbm_bookings'  => true,
				]
			);
		}
	}
}
