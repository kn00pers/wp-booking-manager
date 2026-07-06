<?php
namespace CBM\Frontend;

defined( 'ABSPATH' ) || exit;

final class Shortcode {

	private bool $assets_enqueued = false;

	public function render( array $atts ): string {
		$atts = shortcode_atts(
			[ 'resource_id' => 1 ],
			$atts,
			'crane_booking_form'
		);

		$this->enqueue_assets();

		$renderer = new FormRenderer();
		return $renderer->render( absint( $atts['resource_id'] ) );
	}

	public function enqueue_assets(): void {
		if ( $this->assets_enqueued ) {
			return;
		}
		$this->assets_enqueued = true;

		wp_enqueue_style(
			'flatpickr',
			'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
			[],
			'4.6.13'
		);
		wp_enqueue_script(
			'flatpickr',
			'https://cdn.jsdelivr.net/npm/flatpickr',
			[],
			'4.6.13',
			true
		);

		wp_enqueue_style(
			'cbm-frontend',
			CBM_PLUGIN_URL . 'assets/css/frontend.css',
			[ 'flatpickr' ],
			CBM_VERSION
		);

		wp_enqueue_script(
			'cbm-frontend',
			CBM_PLUGIN_URL . 'assets/js/frontend.js',
			[ 'flatpickr' ],
			CBM_VERSION,
			true
		);
		global $wp_locale;

		wp_localize_script( 'cbm-frontend', 'cbmFrontend', [
			'endpoints' => [
				'availability' => esc_url_raw( rest_url( 'cbm/v1/availability' ) ),
				'monthStatus'  => esc_url_raw( rest_url( 'cbm/v1/month-status' ) ),
				'bookings'     => esc_url_raw( rest_url( 'cbm/v1/bookings' ) ),
			],
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'calendar' => [
				'firstDay'      => (int) get_option( 'start_of_week', 1 ),
				'weekdaysShort' => array_values( $wp_locale->weekday_abbrev ),
				'weekdaysLong'  => array_values( $wp_locale->weekday ),
				'monthsShort'   => array_values( $wp_locale->month_abbrev ),
				'monthsLong'    => array_values( $wp_locale->month ),
			],
			'i18n'  => [
				'sending'          => __( 'Sending...', 'crane-booking-manager' ),
				'errorGeneral'     => __( 'An error occurred. Please try again.', 'crane-booking-manager' ),
				'dateRequired'     => __( 'Select a date.', 'crane-booking-manager' ),
				'timeFromRequired' => __( 'Select a start time.', 'crane-booking-manager' ),
				'timeToRequired'   => __( 'Select an end time.', 'crane-booking-manager' ),
				'nameRequired'     => __( 'Full name is required.', 'crane-booking-manager' ),
				'phoneRequired'    => __( 'Phone number is required.', 'crane-booking-manager' ),
				'emailInvalid'     => __( 'Please provide a valid e-mail address.', 'crane-booking-manager' ),
				'locationRequired' => __( 'Location is required.', 'crane-booking-manager' ),
				'loading'          => __( 'Loading...', 'crane-booking-manager' ),
				'noSlots'          => __( 'No available time slots', 'crane-booking-manager' ),
				'loadError'        => __( 'Failed to load time slots', 'crane-booking-manager' ),
				'chooseFrom'       => __( '- Select start time -', 'crane-booking-manager' ),
				'chooseTo'         => __( '- Select end time -', 'crane-booking-manager' ),
				'noEndAvailable'   => __( 'No available end time (min. 3 h)', 'crane-booking-manager' ),
				'submit'           => __( 'Send request', 'crane-booking-manager' ),
				/* translators: %s: formatted date */
				'bookingOn'        => __( 'Booking for %s', 'crane-booking-manager' ),
			],
		] );
	}
}
