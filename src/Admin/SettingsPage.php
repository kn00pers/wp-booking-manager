<?php
namespace CBM\Admin;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	private const OPTION_KEY = 'cbm_settings';

	public function register(): void {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_settings(): void {
		register_setting( 'cbm_settings_group', self::OPTION_KEY, [
			'sanitize_callback' => [ $this, 'sanitize' ],
		] );
	}


	public static function get( string $key, mixed $default = null ): mixed {
		$settings = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	public static function get_all(): array {
		$saved    = get_option( self::OPTION_KEY, [] );
		$defaults = self::defaults();
		return is_array( $saved ) ? array_replace_recursive( $defaults, $saved ) : $defaults;
	}

	public static function label_singular(): string {
		return (string) self::get( 'resource_label_singular', '' ) ?: __( 'Resource', 'crane-booking-manager' );
	}

	public static function label_plural(): string {
		return (string) self::get( 'resource_label_plural', '' ) ?: __( 'Resources', 'crane-booking-manager' );
	}

	public static function defaults(): array {
		return [
			'resource_label_singular' => __( 'Resource', 'crane-booking-manager' ),
			'resource_label_plural'   => __( 'Resources', 'crane-booking-manager' ),
			'working_hours'       => [
				0 => [ 'enabled' => false, 'time_from' => '08:00', 'time_to' => '17:00' ],
				1 => [ 'enabled' => true,  'time_from' => '07:00', 'time_to' => '17:00' ],
				2 => [ 'enabled' => true,  'time_from' => '07:00', 'time_to' => '17:00' ],
				3 => [ 'enabled' => true,  'time_from' => '07:00', 'time_to' => '17:00' ],
				4 => [ 'enabled' => true,  'time_from' => '07:00', 'time_to' => '17:00' ],
				5 => [ 'enabled' => true,  'time_from' => '07:00', 'time_to' => '17:00' ],
				6 => [ 'enabled' => false, 'time_from' => '08:00', 'time_to' => '14:00' ],
			],
			'slot_duration'       => 60,
			'min_advance_hours'   => 24,
			'pending_blocks_slot' => false,
			'reminder_enabled'    => true,
			'admin_email'         => get_option( 'admin_email', '' ),
			'mail_subject_admin_new'           => __( 'New booking request - {customer_name}', 'crane-booking-manager' ),
			'mail_template_admin_new'          => __( "A new booking request has been received.\n\nRequest ID: {booking_id}\nCustomer: {customer_name}\nPhone: {customer_phone}\nE-mail: {customer_email}\nCompany: {customer_company}\nLocation: {location}\nDate: {date}\nTime: {time_from} - {time_to}\nNotes: {notes}\n\nManage bookings in the WordPress admin panel.", 'crane-booking-manager' ),
			'mail_subject_customer_pending'    => __( 'Booking request #{booking_id} received - {site_name}', 'crane-booking-manager' ),
			'mail_template_customer_pending'   => __( "Thank you, {customer_name}!\n\nYour booking request ({resource_name}, ID: {booking_id}) has been received and is awaiting confirmation.\n\nDate: {date}\nTime: {time_from} - {time_to}\nLocation: {location}\n\nWe will notify you about the decision by e-mail.\n\nIf you wish to cancel the booking, click the link below:\n{cancel_url}\n\n{site_name}", 'crane-booking-manager' ),
			'mail_subject_customer_approved'   => __( 'Booking #{booking_id} approved - {site_name}', 'crane-booking-manager' ),
			'mail_template_customer_approved'  => __( "Dear {customer_name},\n\nYour booking ({resource_name}, ID: {booking_id}) has been approved.\n\nDate: {date}\nTime: {time_from} - {time_to}\nLocation: {location}\n\nSee you soon!\n\nIf you wish to cancel the booking, click the link below:\n{cancel_url}\n\n{site_name}", 'crane-booking-manager' ),
			'mail_subject_customer_rejected'   => __( 'Booking #{booking_id} rejected - {site_name}', 'crane-booking-manager' ),
			'mail_template_customer_rejected'  => __( "Dear {customer_name},\n\nUnfortunately your booking ({resource_name}, ID: {booking_id}) on {date} ({time_from} - {time_to}) cannot be fulfilled.\n\nIf you have any questions, please contact us.\n\n{site_name}", 'crane-booking-manager' ),
			'mail_subject_customer_updated'    => __( 'Booking #{booking_id} updated - {site_name}', 'crane-booking-manager' ),
			'mail_template_customer_updated'   => __( "Dear {customer_name},\n\nYour booking ({resource_name}, ID: {booking_id}) has been updated.\n\nCurrent booking details:\nDate: {date}\nTime: {time_from} - {time_to}\nLocation: {location}\nStatus: {status}\n\nIf you wish to cancel the booking, click the link below:\n{cancel_url}\n\nBest regards,\n{site_name}", 'crane-booking-manager' ),
			'mail_subject_customer_cancelled'  => __( 'Booking #{booking_id} cancelled - {site_name}', 'crane-booking-manager' ),
			'mail_template_customer_cancelled' => __( "Dear {customer_name},\n\nYour booking ({resource_name}, ID: {booking_id}) on {date} ({time_from} - {time_to}) has been cancelled.\n\nThank you for letting us know. Feel free to make a new booking anytime.\n\n{site_name}", 'crane-booking-manager' ),
			'mail_subject_admin_cancelled'     => __( 'Customer cancelled booking #{booking_id}', 'crane-booking-manager' ),
			'mail_template_admin_cancelled'    => __( "The customer has cancelled a booking.\n\nID: {booking_id}\nCustomer: {customer_name}\nPhone: {customer_phone}\nE-mail: {customer_email}\nDate: {date}\nTime: {time_from} - {time_to}\nLocation: {location}\n\nThe slot is available again.", 'crane-booking-manager' ),
			'mail_subject_customer_reminder'   => __( 'Reminder: booking #{booking_id} tomorrow - {site_name}', 'crane-booking-manager' ),
			'mail_template_customer_reminder'  => __( "Dear {customer_name},\n\nThis is a reminder of your booking ({resource_name}, ID: {booking_id}) tomorrow, {date}.\n\nTime: {time_from} - {time_to}\nLocation: {location}\n\nIf you wish to cancel the booking, click the link below:\n{cancel_url}\n\n{site_name}", 'crane-booking-manager' ),
			'turnstile_site_key'               => '',
			'turnstile_secret_key'             => '',
		];
	}


	public function sanitize( mixed $raw ): array {
		if ( ! is_array( $raw ) ) {
			return self::defaults();
		}

		$defaults = self::defaults();
		$out      = $defaults;
		if ( isset( $raw['working_hours'] ) && is_array( $raw['working_hours'] ) ) {
			for ( $d = 0; $d <= 6; $d++ ) {
				$out['working_hours'][ $d ]['enabled']   = ! empty( $raw['working_hours'][ $d ]['enabled'] );
				$out['working_hours'][ $d ]['time_from'] = sanitize_text_field( $raw['working_hours'][ $d ]['time_from'] ?? '07:00' );
				$out['working_hours'][ $d ]['time_to']   = sanitize_text_field( $raw['working_hours'][ $d ]['time_to']   ?? '17:00' );
			}
		}

		$out['slot_duration']       = in_array( (int) ( $raw['slot_duration'] ?? 60 ), [ 30, 60 ], true )
			? (int) $raw['slot_duration']
			: 60;
		$out['min_advance_hours']   = absint( $raw['min_advance_hours'] ?? 24 );
		$out['pending_blocks_slot'] = ! empty( $raw['pending_blocks_slot'] );
		$out['reminder_enabled']    = ! empty( $raw['reminder_enabled'] );
		$out['admin_email']         = sanitize_email( $raw['admin_email'] ?? '' );

		$out['resource_label_singular'] = sanitize_text_field( $raw['resource_label_singular'] ?? '' ) ?: $defaults['resource_label_singular'];
		$out['resource_label_plural']   = sanitize_text_field( $raw['resource_label_plural'] ?? '' )   ?: $defaults['resource_label_plural'];

		foreach ( [
			'mail_subject_admin_new', 'mail_template_admin_new',
			'mail_subject_customer_pending', 'mail_template_customer_pending',
			'mail_subject_customer_approved', 'mail_template_customer_approved',
			'mail_subject_customer_rejected', 'mail_template_customer_rejected',
			'mail_subject_customer_updated', 'mail_template_customer_updated',
			'mail_subject_customer_cancelled', 'mail_template_customer_cancelled',
			'mail_subject_admin_cancelled', 'mail_template_admin_cancelled',
			'mail_subject_customer_reminder', 'mail_template_customer_reminder',
		] as $key ) {
			$out[ $key ] = sanitize_textarea_field( $raw[ $key ] ?? $defaults[ $key ] );
		}

		$out['turnstile_site_key']   = sanitize_text_field( $raw['turnstile_site_key'] ?? '' );
		$out['turnstile_secret_key'] = sanitize_text_field( $raw['turnstile_secret_key'] ?? '' );

		return $out;
	}

	public function render(): void {
		require CBM_PLUGIN_DIR . 'views/admin/settings.php';
	}
}
