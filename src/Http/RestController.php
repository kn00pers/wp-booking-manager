<?php
namespace CBM\Http;

defined( 'ABSPATH' ) || exit;

use CBM\Repository\BookingRepository;
use CBM\Repository\UnavailabilityRepository;
use CBM\Service\AvailabilityService;
use CBM\Service\BookingService;
use CBM\Service\RateLimiter;
use CBM\Helpers\DateTimeHelper;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

final class RestController {

	private const NAMESPACE = 'cbm/v1';
	private RateLimiter $rate_limiter;

	public function __construct() {
		$this->rate_limiter = new RateLimiter( 5, 600 );
	}

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/availability', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_availability' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'resource_id' => [
					'required'          => false,
					'default'           => 1,
					'sanitize_callback' => 'absint',
					'validate_callback' => static fn( $v ) => is_numeric( $v ) && (int) $v > 0,
				],
				'date' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => static fn( $v ) => DateTimeHelper::validate_date( $v ),
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/blocked-dates', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_blocked_dates' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'resource_id' => [
					'required'          => false,
					'default'           => 1,
					'sanitize_callback' => 'absint',
				],
				'month' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => static fn( $v ) => (bool) preg_match( '/^\d{4}-\d{2}$/', $v ),
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/month-status', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_month_status' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'resource_id' => [
					'required'          => false,
					'default'           => 1,
					'sanitize_callback' => 'absint',
				],
				'month' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => static fn( $v ) => (bool) preg_match( '/^\d{4}-\d{2}$/', $v ),
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/bookings', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'create_booking' ],
			'permission_callback' => [ $this, 'verify_nonce' ],
		] );
	}

	public function verify_nonce( WP_REST_Request $request ): bool|WP_Error {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Invalid security token.', 'crane-booking-manager' ), [ 'status' => 403 ] );
		}
		return true;
	}

	public function get_availability( WP_REST_Request $request ): WP_REST_Response {
		$resource_id = (int) $request->get_param( 'resource_id' );
		$date        = $request->get_param( 'date' );

		$booking_repo = new BookingRepository();
		$unavail_repo = new UnavailabilityRepository();
		$service      = new AvailabilityService( $booking_repo, $unavail_repo );

		$result = $service->get_available_slots( $resource_id, $date );

		return new WP_REST_Response( $result, 200 );
	}

	public function get_blocked_dates( WP_REST_Request $request ): WP_REST_Response {
		$resource_id = (int) $request->get_param( 'resource_id' );
		$month       = $request->get_param( 'month' );

		$booking_repo = new BookingRepository();
		$unavail_repo = new UnavailabilityRepository();
		$service      = new AvailabilityService( $booking_repo, $unavail_repo );

		$blocked = $service->get_blocked_dates( $resource_id, $month );

		return new WP_REST_Response( [ 'blocked_dates' => $blocked ], 200 );
	}

	public function get_month_status( WP_REST_Request $request ): WP_REST_Response {
		$resource_id = (int) $request->get_param( 'resource_id' );
		$month       = $request->get_param( 'month' );

		$booking_repo = new BookingRepository();
		$unavail_repo = new UnavailabilityRepository();
		$service      = new AvailabilityService( $booking_repo, $unavail_repo );

		$status = $service->get_month_status( $resource_id, $month );

		return new WP_REST_Response( [ 'days' => $status ], 200 );
	}

	public function create_booking( WP_REST_Request $request ): WP_REST_Response {
		$ip = $this->get_client_ip();
		if ( ! $this->rate_limiter->check( $ip ) ) {
			return new WP_REST_Response(
				[ 'success' => false, 'message' => __( 'Too many requests. Please try again in a few minutes.', 'crane-booking-manager' ) ],
				429
			);
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_body_params();
		}

		$submit_token = sanitize_text_field( $params['submit_token'] ?? '' );
		if ( $submit_token ) {
			$token_key = 'cbm_submit_' . md5( $submit_token );
			if ( get_transient( $token_key ) ) {
				return new WP_REST_Response(
					[ 'success' => false, 'message' => __( 'The form has already been submitted.', 'crane-booking-manager' ) ],
					409
				);
			}
			set_transient( $token_key, 1, 300 );
		}

		$turnstile_secret = \CBM\Admin\SettingsPage::get( 'turnstile_secret_key' );
		if ( ! empty( $turnstile_secret ) ) {
			$turnstile_response = sanitize_text_field( $params['cf_turnstile_response'] ?? '' );
			if ( empty( $turnstile_response ) ) {
				return new WP_REST_Response(
					[ 'success' => false, 'message' => __( 'Anti-spam verification is required (check the box).', 'crane-booking-manager' ) ],
					403
				);
			}

			$verify = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
				'body' => [
					'secret'   => $turnstile_secret,
					'response' => $turnstile_response,
					'remoteip' => $ip,
				],
			] );

			if ( is_wp_error( $verify ) ) {
				return new WP_REST_Response(
					[ 'success' => false, 'message' => __( 'Anti-spam verification connection error.', 'crane-booking-manager' ) ],
					500
				);
			}

			$verify_body = json_decode( wp_remote_retrieve_body( $verify ), true );
			if ( empty( $verify_body['success'] ) ) {
				return new WP_REST_Response(
					[ 'success' => false, 'message' => __( 'Anti-spam verification failed.', 'crane-booking-manager' ) ],
					403
				);
			}
		}

		$service = BookingService::make();
		$result  = $service->create_booking( $params );

		$status_code = $result['success'] ? 201 : 422;
		unset( $result['errors'] );

		return new WP_REST_Response( $result, $status_code );
	}

	private function get_client_ip(): string {
		return sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
	}
}
