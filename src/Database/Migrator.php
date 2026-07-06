<?php
namespace CBM\Database;

defined( 'ABSPATH' ) || exit;

final class Migrator {

	public static function run(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'cbm_schema_version';
		$current = $wpdb->get_var( "SELECT version FROM `{$table}` ORDER BY applied_at DESC LIMIT 1" );

		if ( $current === CBM_DB_VERSION ) {
			return;
		}

		self::apply_migrations( $current );

		$wpdb->replace(
			$table,
			[
				'version'    => CBM_DB_VERSION,
				'applied_at' => current_time( 'mysql' ),
			],
			[ '%s', '%s' ]
		);
	}

	private static function apply_migrations( ?string $from_version ): void {

	}
}
