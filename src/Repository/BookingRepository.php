<?php
namespace CBM\Repository;

defined( 'ABSPATH' ) || exit;

final class BookingRepository {

	private string $table;

	private const ALLOWED_ORDER_BY = [ 'id', 'booking_date', 'status', 'created_at', 'customer_name' ];

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'cbm_bookings';
	}

	public function find( int $id, bool $include_deleted = false ): ?object {
		global $wpdb;
		$deleted_clause = $include_deleted ? '' : 'AND deleted_at IS NULL';
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$this->table}` WHERE id = %d {$deleted_clause}",
				$id
			)
		) ?: null;
	}

	
	public function list( array $filters = [], int $per_page = 20, int $paged = 1 ): array {
		global $wpdb;

		$where  = [];
		$params = [];

		$include_deleted = (bool) ( $filters['include_deleted'] ?? false );
		if ( ! $include_deleted ) {
			$where[] = 'deleted_at IS NULL';
		}

		if ( ! empty( $filters['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = sanitize_text_field( $filters['status'] );
		}

		if ( ! empty( $filters['resource_id'] ) ) {
			$where[]  = 'resource_id = %d';
			$params[] = absint( $filters['resource_id'] );
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where[]  = 'booking_date >= %s';
			$params[] = sanitize_text_field( $filters['date_from'] );
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where[]  = 'booking_date <= %s';
			$params[] = sanitize_text_field( $filters['date_to'] );
		}

		if ( ! empty( $filters['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $filters['search'] ) ) . '%';
			$where[]  = '(customer_name LIKE %s OR customer_email LIKE %s OR customer_phone LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$orderby = in_array( $filters['orderby'] ?? '', self::ALLOWED_ORDER_BY, true )
			? $filters['orderby']
			: 'booking_date';
		$order   = strtoupper( $filters['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

		$offset = ( max( 1, (int) $paged ) - 1 ) * $per_page;
		$total_sql = "SELECT COUNT(*) FROM `{$this->table}` {$where_sql}";
		$items_sql = "SELECT * FROM `{$this->table}` {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		if ( $params ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $total_sql, ...$params ) );
			$items = $wpdb->get_results(
				$wpdb->prepare( $items_sql, ...array_merge( $params, [ $per_page, $offset ] ) )
			) ?: [];
		} else {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $total_sql ) );
			$items = $wpdb->get_results(
				$wpdb->prepare( $items_sql, $per_page, $offset )
			) ?: [];
		}

		return [ 'items' => $items, 'total' => $total ];
	}

	
	public function find_conflicting(
		int $resource_id,
		string $date,
		string $time_from,
		string $time_to,
		array $statuses,
		?int $exclude_id = null
	): array {
		global $wpdb;

		if ( empty( $statuses ) ) {
			return [];
		}

		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		$exclude_sql  = $exclude_id !== null
			? $wpdb->prepare( ' AND id != %d', $exclude_id )
			: '';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM `{$this->table}`
				 WHERE resource_id  = %d
				 AND   booking_date = %s
				 AND   deleted_at   IS NULL
				 AND   status       IN ({$placeholders})
				 AND   time_from    < %s
				 AND   time_to      > %s
				 {$exclude_sql}",
				...array_merge( [ $resource_id, $date ], $statuses, [ $time_to, $time_from ] )
			)
		) ?: [];
	}

	public function count_by_status( string $status ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$this->table}` WHERE status = %s AND deleted_at IS NULL",
				$status
			)
		);
	}

	public function find_by_status_and_date( string $status, string $date ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$this->table}` WHERE status = %s AND booking_date = %s AND deleted_at IS NULL",
				$status,
				$date
			)
		) ?: [];
	}

	public function create( array $data ): int|false {
		global $wpdb;

		$inserted = $wpdb->insert(
			$this->table,
			[
				'resource_id'        => absint( $data['resource_id'] ),
				'customer_name'      => $data['customer_name'],
				'customer_phone'     => $data['customer_phone'],
				'customer_email'     => $data['customer_email'],
				'customer_company'   => $data['customer_company'] ?? null,
				'location'           => $data['location'],
				'booking_date'       => $data['booking_date'],
				'time_from'          => $data['time_from'],
				'time_to'            => $data['time_to'],
				'notes'              => $data['notes'] ?? null,
				'status'             => $data['status'] ?? 'pending',
				'source'             => $data['source'] ?? 'frontend',
				'created_by_user_id' => $data['created_by_user_id'] ?? null,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ]
		);

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	public function update( int $id, array $data, int $user_id = 0 ): bool {
		global $wpdb;

		$allowed = [
			'resource_id', 'customer_name', 'customer_phone', 'customer_email',
			'customer_company', 'location', 'booking_date', 'time_from', 'time_to',
			'notes', 'status',
		];

		$fields  = [];
		$formats = [];

		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}
			$fields[ $key ] = $data[ $key ];
			$formats[]      = match ( $key ) {
				'resource_id' => '%d',
				default       => '%s',
			};
		}

		if ( empty( $fields ) ) {
			return false;
		}

		$fields['updated_by_user_id'] = $user_id ?: null;
		$formats[]                    = '%d';

		return (bool) $wpdb->update( $this->table, $fields, [ 'id' => $id ], $formats, [ '%d' ] );
	}

	public function update_status( int $id, string $status, int $user_id = 0 ): bool {
		global $wpdb;
		return (bool) $wpdb->update(
			$this->table,
			[
				'status'             => $status,
				'updated_by_user_id' => $user_id ?: null,
			],
			[ 'id' => $id ],
			[ '%s', '%d' ],
			[ '%d' ]
		);
	}

	public function soft_delete( int $id, int $user_id = 0 ): bool {
		global $wpdb;
		return (bool) $wpdb->update(
			$this->table,
			[
				'deleted_at'         => current_time( 'mysql' ),
				'updated_by_user_id' => $user_id ?: null,
			],
			[ 'id' => $id ],
			[ '%s', '%d' ],
			[ '%d' ]
		);
	}

	public function restore( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->update(
			$this->table,
			[ 'deleted_at' => null ],
			[ 'id'         => $id ],
			[ '%s' ],
			[ '%d' ]
		);
	}

	public function hard_delete( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
	}
}
