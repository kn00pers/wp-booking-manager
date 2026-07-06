<?php
namespace CBM\Http;

defined( 'ABSPATH' ) || exit;

use CBM\Repository\BookingRepository;
use CBM\Repository\UnavailabilityRepository;
use CBM\Service\BookingService;
use CBM\Helpers\DateTimeHelper;

final class AjaxController {

	public function register_hooks(): void {
		$actions = [
			'cbm_approve_booking',
			'cbm_reject_booking',
			'cbm_delete_booking',
			'cbm_restore_booking',
			'cbm_export_csv',
			'cbm_save_unavailability',
			'cbm_delete_unavailability',
			'cbm_admin_save_booking',
		];

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_' . $action, [ $this, 'handle_' . $action ] );
		}
	}

	public function handle_cbm_approve_booking(): void {
		$this->verify( 'cbm_admin_action' );
		$id      = $this->get_id();
		$service = BookingService::make();
		$result  = $service->approve( $id, get_current_user_id() );
		$this->respond( $result );
	}

	public function handle_cbm_reject_booking(): void {
		$this->verify( 'cbm_admin_action' );
		$id   = $this->get_id();
		$note = sanitize_textarea_field( $_POST['note'] ?? '' );

		$service = BookingService::make();
		$result  = $service->reject( $id, get_current_user_id(), $note );
		$this->respond( $result );
	}

	public function handle_cbm_delete_booking(): void {
		$this->verify( 'cbm_admin_action' );
		$id      = $this->get_id();
		$service = BookingService::make();
		$result  = $service->soft_delete( $id, get_current_user_id() );
		$this->respond( $result );
	}

	public function handle_cbm_restore_booking(): void {
		$this->verify( 'cbm_admin_action' );
		$id      = $this->get_id();
		$service = BookingService::make();
		$result  = $service->restore( $id, get_current_user_id() );
		$this->respond( $result );
	}

	public function handle_cbm_admin_save_booking(): void {
		$this->verify( 'cbm_save_booking' );

		$raw = [
			'resource_id'      => absint( $_POST['resource_id']      ?? 1 ),
			'customer_name'    => sanitize_text_field( $_POST['customer_name']    ?? '' ),
			'customer_phone'   => sanitize_text_field( $_POST['customer_phone']   ?? '' ),
			'customer_email'   => sanitize_email( $_POST['customer_email']        ?? '' ),
			'customer_company' => sanitize_text_field( $_POST['customer_company'] ?? '' ),
			'location'         => sanitize_textarea_field( $_POST['location']     ?? '' ),
			'booking_date'     => sanitize_text_field( $_POST['booking_date']     ?? '' ),
			'time_from'        => sanitize_text_field( $_POST['time_from']        ?? '' ),
			'time_to'          => sanitize_text_field( $_POST['time_to']          ?? '' ),
			'notes'            => sanitize_textarea_field( $_POST['notes']        ?? '' ),
			'status'           => sanitize_text_field( $_POST['status']           ?? 'pending' ),
			'source'           => 'admin',
		];

		$id = absint( $_POST['booking_id'] ?? 0 );

		$service = BookingService::make();

		if ( $id > 0 ) {
			$result = $service->update_booking( $id, $raw, get_current_user_id() );
		} else {
			$result = $service->create_booking( $raw );
		}

		$this->respond( $result );
	}

	public function handle_cbm_save_unavailability(): void {
		$this->verify( 'cbm_admin_action' );

		$date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
		$date_to   = sanitize_text_field( $_POST['date_to']   ?? '' );
		$time_from = sanitize_text_field( $_POST['time_from'] ?? '' );
		$time_to   = sanitize_text_field( $_POST['time_to']   ?? '' );

		if ( ! DateTimeHelper::validate_date( $date_from ) || ! DateTimeHelper::validate_date( $date_to ) ) {
			$this->respond( [ 'success' => false, 'message' => __( 'Invalid date.', 'crane-booking-manager' ) ] );
		}

		if ( $date_from > $date_to ) {
			$this->respond( [ 'success' => false, 'message' => __( 'End date must be later than start date.', 'crane-booking-manager' ) ] );
		}

		$repo   = new UnavailabilityRepository();
		$result = $repo->create( [
			'resource_id' => absint( $_POST['resource_id'] ?? 1 ),
			'date_from'   => $date_from,
			'date_to'     => $date_to,
			'time_from'   => ( $time_from && DateTimeHelper::validate_time( $time_from ) ) ? $time_from : null,
			'time_to'     => ( $time_to   && DateTimeHelper::validate_time( $time_to )   ) ? $time_to   : null,
			'reason'      => sanitize_text_field( $_POST['reason'] ?? '' ),
		] );

		if ( $result ) {
			$this->respond( [ 'success' => true, 'message' => __( 'Block has been added.', 'crane-booking-manager' ), 'id' => $result ] );
		} else {
			$this->respond( [ 'success' => false, 'message' => __( 'Save error.', 'crane-booking-manager' ) ] );
		}
	}

	public function handle_cbm_delete_unavailability(): void {
		$this->verify( 'cbm_admin_action' );
		$id   = $this->get_id();
		$repo = new UnavailabilityRepository();
		if ( $repo->delete( $id ) ) {
			$this->respond( [ 'success' => true, 'message' => __( 'Block deleted.', 'crane-booking-manager' ) ] );
		} else {
			$this->respond( [ 'success' => false, 'message' => __( 'Block not found.', 'crane-booking-manager' ) ] );
		}
	}

	public function handle_cbm_export_csv(): void {
		$this->verify( 'cbm_export_csv' );

		$repo    = new BookingRepository();
		$filters = [
			'status'    => sanitize_text_field( $_POST['status']    ?? '' ),
			'date_from' => sanitize_text_field( $_POST['date_from'] ?? '' ),
			'date_to'   => sanitize_text_field( $_POST['date_to']   ?? '' ),
		];

		$data  = $repo->list( $filters, 9999, 1 );
		$items = $data['items'];

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=bookings-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );

		$out = fopen( 'php://output', 'w' );
		fputs( $out, "\xEF\xBB\xBF" );

		fputcsv( $out, [
			__( 'ID', 'crane-booking-manager' ),
			\CBM\Admin\SettingsPage::label_singular(),
			__( 'Customer', 'crane-booking-manager' ),
			__( 'Phone', 'crane-booking-manager' ),
			__( 'E-mail', 'crane-booking-manager' ),
			__( 'Company', 'crane-booking-manager' ),
			__( 'Location', 'crane-booking-manager' ),
			__( 'Date', 'crane-booking-manager' ),
			__( 'From', 'crane-booking-manager' ),
			__( 'To', 'crane-booking-manager' ),
			__( 'Status', 'crane-booking-manager' ),
			__( 'Source', 'crane-booking-manager' ),
			__( 'Created at', 'crane-booking-manager' ),
		], ';' );

		foreach ( $items as $row ) {
			fputcsv( $out, [
				$row->id,
				$row->resource_id,
				$row->customer_name,
				$row->customer_phone,
				$row->customer_email,
				$row->customer_company ?? '',
				$row->location,
				$row->booking_date,
				$row->time_from,
				$row->time_to,
				$row->status,
				$row->source,
				$row->created_at,
			], ';' );
		}

		fclose( $out );
		exit;
	}

	private function verify( string $action ): void {
		if ( ! current_user_can( 'manage_cbm_bookings' ) ) {
			wp_send_json( [ 'success' => false, 'message' => __( 'Insufficient permissions.', 'crane-booking-manager' ) ] );
		}
		check_ajax_referer( $action, 'nonce' );
	}

	private function get_id(): int {
		$id = absint( $_POST['id'] ?? 0 );
		if ( $id < 1 ) {
			$this->respond( [ 'success' => false, 'message' => __( 'Invalid identifier.', 'crane-booking-manager' ) ] );
		}
		return $id;
	}

	private function respond( array $data ): never {
		wp_send_json( $data );
	}
}
