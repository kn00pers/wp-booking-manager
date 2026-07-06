<?php
namespace CBM\Repository;

defined( 'ABSPATH' ) || exit;

final class UnavailabilityRepository {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'cbm_unavailability';
	}

	public function find( int $id ): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$this->table}` WHERE id = %d", $id )
		) ?: null;
	}

	
	public function list(
		int $resource_id = 0,
		?string $date_from = null,
		?string $date_to = null
	): array {
		global $wpdb;
		$where  = [];
		$params = [];

		if ( $resource_id > 0 ) {
			$where[]  = 'resource_id = %d';
			$params[] = $resource_id;
		}
		if ( $date_from !== null ) {
			$where[]  = 'date_to >= %s';
			$params[] = $date_from;
		}
		if ( $date_to !== null ) {
			$where[]  = 'date_from <= %s';
			$params[] = $date_to;
		}
		$sql = "SELECT * FROM `{$this->table}`";
		if ( $where ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}
		$sql .= ' ORDER BY date_from ASC';

		if ( $params ) {
			return $wpdb->get_results(
				$wpdb->prepare( $sql, ...$params )
			) ?: [];
		}
		return $wpdb->get_results( $sql ) ?: [];
	}

	
	public function find_for_range( int $resource_id, string $date_from, string $date_to ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$this->table}`
				 WHERE resource_id = %d
				 AND date_from <= %s
				 AND date_to   >= %s",
				$resource_id,
				$date_to,
				$date_from
			)
		) ?: [];
	}

	public function create( array $data ): int|false {
		global $wpdb;
		$inserted = $wpdb->insert(
			$this->table,
			[
				'resource_id'        => absint( $data['resource_id'] ?? 1 ),
				'date_from'          => sanitize_text_field( $data['date_from'] ),
				'date_to'            => sanitize_text_field( $data['date_to'] ),
				'time_from'          => $data['time_from'] ?: null,
				'time_to'            => $data['time_to'] ?: null,
				'reason'             => sanitize_text_field( $data['reason'] ?? '' ) ?: null,
				'created_by_user_id' => get_current_user_id() ?: null,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%d' ]
		);
		return $inserted ? $wpdb->insert_id : false;
	}

	public function delete( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
	}
}
