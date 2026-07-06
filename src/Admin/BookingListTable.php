<?php
namespace CBM\Admin;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

use CBM\Helpers\Labels;
use CBM\Repository\BookingRepository;

final class BookingListTable extends \WP_List_Table {

	private BookingRepository $repo;

	public function __construct() {
		parent::__construct( [
			'singular' => 'booking',
			'plural'   => 'bookings',
			'ajax'     => false,
		] );
		$this->repo = new BookingRepository();
	}

	public function get_columns(): array {
		return [
			'cb'            => '<input type="checkbox">',
			'id'            => __( 'ID', 'crane-booking-manager' ),
			'customer_name' => __( 'Customer', 'crane-booking-manager' ),
			'booking_date'  => __( 'Date', 'crane-booking-manager' ),
			'time_range'    => __( 'Time', 'crane-booking-manager' ),
			'location'      => __( 'Location', 'crane-booking-manager' ),
			'status'        => __( 'Status', 'crane-booking-manager' ),
			'source'        => __( 'Source', 'crane-booking-manager' ),
			'actions'       => __( 'Actions', 'crane-booking-manager' ),
		];
	}

	protected function get_sortable_columns(): array {
		return [
			'id'            => [ 'id', true ],
			'booking_date'  => [ 'booking_date', false ],
			'customer_name' => [ 'customer_name', false ],
			'status'        => [ 'status', false ],
		];
	}

	protected function get_bulk_actions(): array {
		return [
			'approve' => __( 'Approve', 'crane-booking-manager' ),
			'reject'  => __( 'Reject', 'crane-booking-manager' ),
			'delete'  => __( 'Delete (soft)', 'crane-booking-manager' ),
		];
	}

	public function prepare_items(): void {
		$per_page = 20;
		$paged    = max( 1, absint( $_GET['paged'] ?? 1 ) );

		$filters = [
			'status'          => sanitize_text_field( $_GET['status']    ?? '' ),
			'date_from'       => sanitize_text_field( $_GET['date_from'] ?? '' ),
			'date_to'         => sanitize_text_field( $_GET['date_to']   ?? '' ),
			'search'          => sanitize_text_field( $_GET['s']         ?? '' ),
			'include_deleted' => ! empty( $_GET['show_deleted'] ),
			'orderby'         => sanitize_text_field( $_GET['orderby']   ?? 'booking_date' ),
			'order'           => sanitize_text_field( $_GET['order']     ?? 'DESC' ),
		];

		$allowed_orderby            = [ 'id', 'booking_date', 'customer_name', 'status', 'created_at' ];
		$filters['orderby']         = in_array( $filters['orderby'], $allowed_orderby, true )
			? $filters['orderby']
			: 'booking_date';
		$filters['order']           = strtoupper( $filters['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$result      = $this->repo->list( $filters, $per_page, $paged );
		$this->items = $result['items'];

		$this->set_pagination_args( [
			'total_items' => $result['total'],
			'per_page'    => $per_page,
			'total_pages' => ceil( $result['total'] / $per_page ),
		] );

		$this->_column_headers = [
			$this->get_columns(),
			[],
			$this->get_sortable_columns(),
		];
	}

	protected function column_default( $item, $column_name ): string {
		return match ( $column_name ) {
			'id'            => '#' . esc_html( $item->id ),
			'customer_name' => esc_html( $item->customer_name )
				. '<br><small>' . esc_html( $item->customer_email ) . '</small>'
				. '<br><small>' . esc_html( $item->customer_phone ) . '</small>',
			'booking_date'  => esc_html( $item->booking_date ),
			'time_range'    => esc_html( $item->time_from ) . ' - ' . esc_html( $item->time_to ),
			'location'      => esc_html( $item->location ),
			'status'        => $this->status_badge( $item->status ),
			'source'        => esc_html( $item->source === 'admin'
				? __( 'Admin panel', 'crane-booking-manager' )
				: __( 'Website form', 'crane-booking-manager' ) ),
			'actions'       => $this->row_actions_html( $item ),
			default         => '',
		};
	}

	protected function column_cb( $item ): string {
		return '<input type="checkbox" name="booking_ids[]" value="' . absint( $item->id ) . '">';
	}

	private function status_badge( string $status ): string {
		return '<span class="cbm-status cbm-status--' . esc_attr( $status ) . '">'
			. esc_html( Labels::status( $status ) ) . '</span>';
	}

	private function row_actions_html( object $item ): string {
		$id   = absint( $item->id );
		$edit = admin_url( 'admin.php?page=cbm-add-booking&booking_id=' . $id );
		$html = '';

		if ( $item->deleted_at ) {
			$html .= '<button type="button" class="button button-small cbm-restore" data-id="' . $id . '">' . esc_html__( 'Restore', 'crane-booking-manager' ) . '</button> ';
		} else {
			$html .= '<a href="' . esc_url( $edit ) . '" class="button button-small">' . esc_html__( 'Edit', 'crane-booking-manager' ) . '</a> ';
			if ( $item->status === 'pending' ) {
				$html .= '<button type="button" class="button button-small cbm-approve" data-id="' . $id . '">' . esc_html__( 'Approve', 'crane-booking-manager' ) . '</button> ';
				$html .= '<button type="button" class="button button-small cbm-reject"  data-id="' . $id . '">' . esc_html__( 'Reject', 'crane-booking-manager' ) . '</button> ';
			}
			$html .= '<button type="button" class="button button-small cbm-delete" data-id="' . $id . '">' . esc_html__( 'Delete', 'crane-booking-manager' ) . '</button>';
		}

		return $html;
	}
}
