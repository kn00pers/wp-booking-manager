<?php


defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'CBM_UNINSTALL_DATA' ) || ! CBM_UNINSTALL_DATA ) {
	return;
}

global $wpdb;

$tables = [
	$wpdb->prefix . 'cbm_bookings',
	$wpdb->prefix . 'cbm_unavailability',
	$wpdb->prefix . 'cbm_booking_logs',
	$wpdb->prefix . 'cbm_resources',
	$wpdb->prefix . 'cbm_schema_version',
];

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

delete_option( 'cbm_settings' );
delete_option( 'cbm_secret' );

global $wp_roles;
if ( ! empty( $wp_roles->roles ) ) {
	foreach ( array_keys( $wp_roles->roles ) as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			$role->remove_cap( 'manage_cbm_bookings' );
		}
	}
}
