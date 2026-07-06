<?php
namespace CBM\Service;

defined( 'ABSPATH' ) || exit;

use CBM\Repository\BookingRepository;
use CBM\Repository\LogRepository;
use CBM\Repository\UnavailabilityRepository;
use CBM\Validator\BookingValidator;
use WP_Error;

final class BookingService {

	public function __construct(
		private readonly BookingRepository $booking_repo,
		private readonly AvailabilityService $availability,
		private readonly MailService $mail,
		private readonly LogRepository $log_repo,
		private readonly BookingValidator $validator
	) {}


	public function create_booking( array $raw ): array {
		global $wpdb;

		$data = $this->validator->validate( $raw );
		if ( is_wp_error( $data ) ) {
			return [
				'success'    => false,
				'booking_id' => 0,
				'message'    => implode( ' ', $data->get_error_messages() ),
				'errors'     => $data,
			];
		}

		$wpdb->query( 'START TRANSACTION' );

		try {
			if ( $this->availability->check_conflict(
				$data['resource_id'],
				$data['booking_date'],
				$data['time_from'],
				$data['time_to']
			) ) {
				$wpdb->query( 'ROLLBACK' );
				return [
					'success'    => false,
					'booking_id' => 0,
					'message'    => __( 'The selected time slot is no longer available. Please choose another one.', 'crane-booking-manager' ),
					'errors'     => null,
				];
			}

			$booking_id = $this->booking_repo->create( $data );
			if ( ! $booking_id ) {
				$wpdb->query( 'ROLLBACK' );
				return [
					'success'    => false,
					'booking_id' => 0,
					'message'    => __( 'An error occurred while saving. Please try again.', 'crane-booking-manager' ),
					'errors'     => null,
				];
			}

			$wpdb->query( 'COMMIT' );

		} catch ( \Throwable $e ) {
			$wpdb->query( 'ROLLBACK' );
			error_log( '[CBM] BookingService::create_booking exception: ' . $e->getMessage() );
			return [
				'success'    => false,
				'booking_id' => 0,
				'message'    => __( 'Server error. Please try again.', 'crane-booking-manager' ),
				'errors'     => null,
			];
		}

		$this->log_repo->log_status_change( $booking_id, null, 'pending', null, __( 'Booking submitted', 'crane-booking-manager' ) );

		$booking = $this->booking_repo->find( $booking_id );
		if ( $booking ) {
			do_action( 'cbm_booking_created', $booking );
			$this->mail->send_new_booking_admin( $booking );
			$this->mail->send_new_booking_customer( $booking );
		}

		return [
			'success'    => true,
			'booking_id' => $booking_id,
			'message'    => __( 'Your request has been received. We will contact you by e-mail or phone after confirmation. The e-mail may end up in your SPAM folder.', 'crane-booking-manager' ),
			'errors'     => null,
		];
	}


	public function approve( int $id, int $admin_id ): array {
		$booking = $this->booking_repo->find( $id );
		if ( ! $booking ) {
			return [ 'success' => false, 'message' => __( 'Booking does not exist.', 'crane-booking-manager' ) ];
		}

		if ( $this->availability->check_conflict(
			(int) $booking->resource_id,
			$booking->booking_date,
			$booking->time_from,
			$booking->time_to,
			$id
		) ) {
			return [ 'success' => false, 'message' => __( 'The time slot conflicts with another approved booking.', 'crane-booking-manager' ) ];
		}

		$this->booking_repo->update_status( $id, 'approved', $admin_id );
		$this->log_repo->log_status_change( $id, $booking->status, 'approved', $admin_id );

		$updated = $this->booking_repo->find( $id );
		if ( $updated ) {
			do_action( 'cbm_booking_approved', $updated );
			$this->mail->send_approved_customer( $updated );
		}

		return [ 'success' => true, 'message' => __( 'Booking has been approved.', 'crane-booking-manager' ) ];
	}


	public function reject( int $id, int $admin_id, string $note = '' ): array {
		$booking = $this->booking_repo->find( $id );
		if ( ! $booking ) {
			return [ 'success' => false, 'message' => __( 'Booking does not exist.', 'crane-booking-manager' ) ];
		}

		$this->booking_repo->update_status( $id, 'rejected', $admin_id );
		$this->log_repo->log_status_change( $id, $booking->status, 'rejected', $admin_id, $note ?: null );

		$updated = $this->booking_repo->find( $id );
		if ( $updated ) {
			do_action( 'cbm_booking_rejected', $updated );
			$this->mail->send_rejected_customer( $updated );
		}

		return [ 'success' => true, 'message' => __( 'Booking has been rejected.', 'crane-booking-manager' ) ];
	}


	public function cancel_by_customer( int $id ): array {
		$booking = $this->booking_repo->find( $id );
		if ( ! $booking ) {
			return [ 'success' => false, 'message' => __( 'Booking does not exist.', 'crane-booking-manager' ) ];
		}

		if ( in_array( $booking->status, [ 'cancelled', 'rejected' ], true ) ) {
			return [ 'success' => false, 'message' => __( 'This booking can no longer be cancelled.', 'crane-booking-manager' ) ];
		}

		$this->booking_repo->update_status( $id, 'cancelled', 0 );
		$this->log_repo->log_status_change( $id, $booking->status, 'cancelled', null, __( 'Cancelled by customer', 'crane-booking-manager' ) );

		$updated = $this->booking_repo->find( $id );
		if ( $updated ) {
			do_action( 'cbm_booking_cancelled', $updated );
			$this->mail->send_cancelled_customer( $updated );
			$this->mail->send_cancelled_admin( $updated );
		}

		return [ 'success' => true, 'message' => __( 'Your booking has been cancelled. Thank you for letting us know.', 'crane-booking-manager' ) ];
	}


	public function soft_delete( int $id, int $admin_id ): array {
		$booking = $this->booking_repo->find( $id );
		if ( ! $booking ) {
			return [ 'success' => false, 'message' => __( 'Booking does not exist.', 'crane-booking-manager' ) ];
		}

		$this->booking_repo->soft_delete( $id, $admin_id );
		$this->log_repo->log_status_change( $id, $booking->status, 'deleted', $admin_id );
		do_action( 'cbm_booking_deleted', $id );

		return [ 'success' => true, 'message' => __( 'Booking has been deleted (can be restored).', 'crane-booking-manager' ) ];
	}


	public function restore( int $id, int $admin_id ): array {
		$booking = $this->booking_repo->find( $id, true );
		if ( ! $booking || $booking->deleted_at === null ) {
			return [ 'success' => false, 'message' => __( 'Booking does not exist or is not deleted.', 'crane-booking-manager' ) ];
		}

		$this->booking_repo->restore( $id );
		$this->log_repo->log_status_change( $id, 'deleted', $booking->status, $admin_id, __( 'Restored', 'crane-booking-manager' ) );

		return [ 'success' => true, 'message' => __( 'Booking has been restored.', 'crane-booking-manager' ) ];
	}


	public function update_booking( int $id, array $raw, int $admin_id ): array {
		$booking = $this->booking_repo->find( $id );
		if ( ! $booking ) {
			return [ 'success' => false, 'message' => __( 'Booking does not exist.', 'crane-booking-manager' ) ];
		}

		$data = $this->validator->validate( $raw );
		if ( is_wp_error( $data ) ) {
			return [ 'success' => false, 'message' => implode( ' ', $data->get_error_messages() ) ];
		}

		if ( $this->availability->check_conflict(
			$data['resource_id'],
			$data['booking_date'],
			$data['time_from'],
			$data['time_to'],
			$id
		) ) {
			return [ 'success' => false, 'message' => __( 'The selected time slot conflicts with another booking.', 'crane-booking-manager' ) ];
		}

		$this->booking_repo->update( $id, $data, $admin_id );
		$this->log_repo->log_status_change( $id, $booking->status, $data['status'], $admin_id, __( 'Edited by admin', 'crane-booking-manager' ) );

		$updated = $this->booking_repo->find( $id );
		if ( $updated ) {
			do_action( 'cbm_booking_updated', $updated );
			$this->mail->send_updated_customer( $updated );
		}

		return [ 'success' => true, 'message' => __( 'Booking has been updated.', 'crane-booking-manager' ) ];
	}

	public static function make(): self {
		$booking_repo = new BookingRepository();
		$unavail_repo = new UnavailabilityRepository();
		$log_repo     = new LogRepository();
		$availability = new AvailabilityService( $booking_repo, $unavail_repo );
		$mail         = new MailService();
		$validator    = new BookingValidator();
		return new self( $booking_repo, $availability, $mail, $log_repo, $validator );
	}
}
