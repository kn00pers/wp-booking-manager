<?php
namespace CBM\Repository;

defined( 'ABSPATH' ) || exit;

final class ResourceRepository {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'cbm_resources';
	}

	public function find( int $id ): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$this->table}` WHERE id = %d", $id )
		) ?: null;
	}

	
	public function list( bool $active_only = false ): array {
		global $wpdb;
		$sql = "SELECT * FROM `{$this->table}`";
		if ( $active_only ) {
			$sql .= ' WHERE is_active = 1';
		}
		$sql .= ' ORDER BY name ASC';
		return $wpdb->get_results( $sql ) ?: [];
	}

	public function create( array $data ): int|false {
		global $wpdb;
		$inserted = $wpdb->insert(
			$this->table,
			[
				'name'        => sanitize_text_field( $data['name'] ),
				'description' => sanitize_textarea_field( $data['description'] ?? '' ),
				'is_active'   => (int) ( $data['is_active'] ?? 1 ),
			],
			[ '%s', '%s', '%d' ]
		);
		return $inserted ? $wpdb->insert_id : false;
	}

	public function update( int $id, array $data ): bool {
		global $wpdb;
		$fields  = [];
		$formats = [];

		if ( isset( $data['name'] ) ) {
			$fields['name'] = sanitize_text_field( $data['name'] );
			$formats[]      = '%s';
		}
		if ( isset( $data['description'] ) ) {
			$fields['description'] = sanitize_textarea_field( $data['description'] );
			$formats[]             = '%s';
		}
		if ( isset( $data['is_active'] ) ) {
			$fields['is_active'] = (int) $data['is_active'];
			$formats[]           = '%d';
		}

		if ( empty( $fields ) ) {
			return false;
		}

		return (bool) $wpdb->update( $this->table, $fields, [ 'id' => $id ], $formats, [ '%d' ] );
	}
}
