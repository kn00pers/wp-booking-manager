<?php
namespace CBM\Database;

defined( 'ABSPATH' ) || exit;

final class Schema {

	public static function create(): void {
		global $wpdb;

		$collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( self::definitions( $collate ) as $sql ) {
			dbDelta( $sql );
		}
	}

	
	private static function definitions( string $collate ): array {
		global $wpdb;
		$p = $wpdb->prefix;

		return [
			"CREATE TABLE {$p}cbm_resources (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(200) NOT NULL,
  description text,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id)
) {$collate};",
			"CREATE TABLE {$p}cbm_bookings (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  resource_id bigint(20) unsigned NOT NULL DEFAULT 1,
  customer_name varchar(200) NOT NULL,
  customer_phone varchar(50) NOT NULL,
  customer_email varchar(200) NOT NULL,
  customer_company varchar(200) DEFAULT NULL,
  location text NOT NULL,
  booking_date date NOT NULL,
  time_from time NOT NULL,
  time_to time NOT NULL,
  notes text,
  status enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  source enum('frontend','admin') NOT NULL DEFAULT 'frontend',
  created_by_user_id bigint(20) unsigned DEFAULT NULL,
  updated_by_user_id bigint(20) unsigned DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY idx_date (booking_date),
  KEY idx_status (status),
  KEY idx_resource (resource_id),
  KEY idx_date_status (booking_date,status),
  KEY idx_deleted (deleted_at)
) {$collate};",
			"CREATE TABLE {$p}cbm_unavailability (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  resource_id bigint(20) unsigned NOT NULL DEFAULT 1,
  date_from date NOT NULL,
  date_to date NOT NULL,
  time_from time DEFAULT NULL,
  time_to time DEFAULT NULL,
  reason varchar(500) DEFAULT NULL,
  created_by_user_id bigint(20) unsigned DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_resource_date (resource_id,date_from,date_to)
) {$collate};",
			"CREATE TABLE {$p}cbm_booking_logs (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  booking_id bigint(20) unsigned NOT NULL,
  old_status varchar(50) DEFAULT NULL,
  new_status varchar(50) DEFAULT NULL,
  changed_by_user_id bigint(20) unsigned DEFAULT NULL,
  note text,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_booking (booking_id)
) {$collate};",
			"CREATE TABLE {$p}cbm_schema_version (
  version varchar(20) NOT NULL,
  applied_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (version)
) {$collate};",
		];
	}
}
