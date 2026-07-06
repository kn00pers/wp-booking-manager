<?php
namespace CBM\Validator;

defined( 'ABSPATH' ) || exit;

use CBM\Helpers\DateTimeHelper;
use WP_Error;

final class BookingValidator {

	public const ALLOWED_STATUSES = [ 'pending', 'approved', 'rejected', 'cancelled' ];

	public const MIN_DURATION_MINUTES = 180;


	public function validate( array $raw ): array|WP_Error {
		$errors = new WP_Error();
		$data   = [];
		$is_admin = ( $raw['source'] ?? '' ) === 'admin';
		$data['resource_id'] = absint( $raw['resource_id'] ?? 1 );
		if ( $data['resource_id'] < 1 ) {
			$data['resource_id'] = 1;
		}
		$data['customer_name'] = sanitize_text_field( $raw['customer_name'] ?? '' );
		if ( empty( $data['customer_name'] ) ) {
			if ( ! $is_admin ) {
				$errors->add( 'customer_name', __( 'Full name is required.', 'crane-booking-manager' ) );
			}
		} elseif ( mb_strlen( $data['customer_name'] ) > 200 ) {
			$errors->add( 'customer_name', __( 'Full name is too long.', 'crane-booking-manager' ) );
		}
		$data['customer_phone'] = sanitize_text_field( $raw['customer_phone'] ?? '' );
		if ( empty( $data['customer_phone'] ) ) {
			if ( ! $is_admin ) {
				$errors->add( 'customer_phone', __( 'Phone number is required.', 'crane-booking-manager' ) );
			}
		} elseif ( mb_strlen( $data['customer_phone'] ) > 50 ) {
			$errors->add( 'customer_phone', __( 'Phone number is too long.', 'crane-booking-manager' ) );
		}
		$data['customer_email'] = sanitize_email( $raw['customer_email'] ?? '' );
		if ( $is_admin && empty( $data['customer_email'] ) ) {
			$data['customer_email'] = '';
		} elseif ( empty( $data['customer_email'] ) || ! is_email( $data['customer_email'] ) ) {
			$errors->add( 'customer_email', __( 'Please provide a valid e-mail address.', 'crane-booking-manager' ) );
		}
		$data['customer_company'] = sanitize_text_field( $raw['customer_company'] ?? '' );
		if ( $data['customer_company'] === '' ) {
			$data['customer_company'] = null;
		}
		$data['location'] = sanitize_textarea_field( $raw['location'] ?? '' );
		if ( empty( $data['location'] ) && ! $is_admin ) {
			$errors->add( 'location', __( 'Location is required.', 'crane-booking-manager' ) );
		}
		$raw_date             = sanitize_text_field( $raw['booking_date'] ?? '' );
		$data['booking_date'] = $raw_date;
		if ( ! DateTimeHelper::validate_date( $raw_date ) ) {
			$errors->add( 'booking_date', __( 'Invalid date format.', 'crane-booking-manager' ) );
		}
		$raw_from = substr( sanitize_text_field( $raw['time_from'] ?? '' ), 0, 5 );
		$data['time_from'] = $raw_from;
		if ( ! DateTimeHelper::validate_time( $raw_from ) ) {
			$errors->add( 'time_from', __( 'Invalid start time format.', 'crane-booking-manager' ) );
		}
		$raw_to = substr( sanitize_text_field( $raw['time_to'] ?? '' ), 0, 5 );
		$data['time_to'] = $raw_to;
		if ( ! DateTimeHelper::validate_time( $raw_to ) ) {
			$errors->add( 'time_to', __( 'Invalid end time format.', 'crane-booking-manager' ) );
		}
		if ( ! $errors->get_error_code() ) {
			$duration = DateTimeHelper::time_to_minutes( $raw_to ) - DateTimeHelper::time_to_minutes( $raw_from );
			if ( $duration <= 0 ) {
				$errors->add( 'time_range', __( 'End time must be later than start time.', 'crane-booking-manager' ) );
			} elseif ( $duration < self::MIN_DURATION_MINUTES ) {
				$errors->add( 'time_range', __( 'Minimum booking duration is 3 hours.', 'crane-booking-manager' ) );
			}
		}
		$data['notes'] = sanitize_textarea_field( $raw['notes'] ?? '' );
		if ( $data['notes'] === '' ) {
			$data['notes'] = null;
		}
		$raw_source    = sanitize_text_field( $raw['source'] ?? 'frontend' );
		$data['source'] = in_array( $raw_source, [ 'frontend', 'admin' ], true )
			? $raw_source
			: 'frontend';
		$raw_status     = sanitize_text_field( $raw['status'] ?? 'pending' );
		$data['status'] = in_array( $raw_status, self::ALLOWED_STATUSES, true )
			? $raw_status
			: 'pending';

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return $data;
	}

	public function validate_id( mixed $raw ): int|WP_Error {
		$id = absint( $raw );
		if ( $id < 1 ) {
			return new WP_Error( 'invalid_id', __( 'Invalid identifier.', 'crane-booking-manager' ) );
		}
		return $id;
	}

	public function validate_status( mixed $raw ): string|WP_Error {
		$status = sanitize_text_field( (string) $raw );
		if ( ! in_array( $status, self::ALLOWED_STATUSES, true ) ) {
			return new WP_Error( 'invalid_status', __( 'Invalid status.', 'crane-booking-manager' ) );
		}
		return $status;
	}
}
