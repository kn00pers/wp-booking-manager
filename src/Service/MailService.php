<?php
namespace CBM\Service;

defined( 'ABSPATH' ) || exit;

use CBM\Admin\SettingsPage;
use CBM\Helpers\Labels;
use CBM\Helpers\Token;
use CBM\Repository\ResourceRepository;

final class MailService {

	public function send_new_booking_admin( object $booking ): void {
		$to      = SettingsPage::get( 'admin_email', get_option( 'admin_email' ) );
		$subject = $this->parse(
			SettingsPage::get( 'mail_subject_admin_new', __( 'New booking request', 'crane-booking-manager' ) ),
			$booking
		);
		$body    = $this->parse(
			SettingsPage::get( 'mail_template_admin_new', '' ),
			$booking
		);
		$this->send( $to, $subject, $body );
	}

	public function send_new_booking_customer( object $booking ): void {
		$subject = $this->parse(
			SettingsPage::get( 'mail_subject_customer_pending', __( 'Booking request received', 'crane-booking-manager' ) ),
			$booking
		);
		$body    = $this->parse(
			SettingsPage::get( 'mail_template_customer_pending', '' ),
			$booking
		);
		$this->send( $booking->customer_email, $subject, $body );
	}

	public function send_approved_customer( object $booking ): void {
		$subject = $this->parse(
			SettingsPage::get( 'mail_subject_customer_approved', __( 'Booking approved', 'crane-booking-manager' ) ),
			$booking
		);
		$body    = $this->parse(
			SettingsPage::get( 'mail_template_customer_approved', '' ),
			$booking
		);

		$ics = $this->create_ics_file( $booking );
		$this->send( $booking->customer_email, $subject, $body, $ics ? [ $ics ] : [] );
		if ( $ics ) {
			@unlink( $ics );
		}
	}

	public function send_rejected_customer( object $booking ): void {
		$subject = $this->parse(
			SettingsPage::get( 'mail_subject_customer_rejected', __( 'Booking rejected', 'crane-booking-manager' ) ),
			$booking
		);
		$body    = $this->parse(
			SettingsPage::get( 'mail_template_customer_rejected', '' ),
			$booking
		);
		$this->send( $booking->customer_email, $subject, $body );
	}

	public function send_updated_customer( object $booking ): void {
		$subject = $this->parse(
			SettingsPage::get( 'mail_subject_customer_updated', __( 'Booking updated', 'crane-booking-manager' ) ),
			$booking
		);
		$body    = $this->parse(
			SettingsPage::get( 'mail_template_customer_updated', '' ),
			$booking
		);
		$this->send( $booking->customer_email, $subject, $body );
	}

	public function send_cancelled_customer( object $booking ): void {
		$subject = $this->parse(
			SettingsPage::get( 'mail_subject_customer_cancelled', __( 'Booking cancelled', 'crane-booking-manager' ) ),
			$booking
		);
		$body    = $this->parse(
			SettingsPage::get( 'mail_template_customer_cancelled', '' ),
			$booking
		);
		$this->send( $booking->customer_email, $subject, $body );
	}

	public function send_cancelled_admin( object $booking ): void {
		$to      = SettingsPage::get( 'admin_email', get_option( 'admin_email' ) );
		$subject = $this->parse(
			SettingsPage::get( 'mail_subject_admin_cancelled', __( 'Booking cancelled by customer', 'crane-booking-manager' ) ),
			$booking
		);
		$body    = $this->parse(
			SettingsPage::get( 'mail_template_admin_cancelled', '' ),
			$booking
		);
		$this->send( $to, $subject, $body );
	}

	public function send_reminder_customer( object $booking ): void {
		$subject = $this->parse(
			SettingsPage::get( 'mail_subject_customer_reminder', __( 'Booking reminder', 'crane-booking-manager' ) ),
			$booking
		);
		$body    = $this->parse(
			SettingsPage::get( 'mail_template_customer_reminder', '' ),
			$booking
		);
		$this->send( $booking->customer_email, $subject, $body );
	}

	public function replace_placeholders( string $template, array $data ): string {
		$map = [
			'{booking_id}'       => (string) ( $data['id']             ?? '' ),
			'{customer_name}'    => $data['customer_name']    ?? '',
			'{customer_phone}'   => $data['customer_phone']   ?? '',
			'{customer_email}'   => $data['customer_email']   ?? '',
			'{customer_company}' => $data['customer_company'] ?? '',
			'{location}'         => $data['location']         ?? '',
			'{date}'             => $data['booking_date']     ?? '',
			'{time_from}'        => $data['time_from']        ?? '',
			'{time_to}'          => $data['time_to']          ?? '',
			'{notes}'            => $data['notes']            ?? '',
			'{resource_name}'    => $data['resource_name']    ?? '',
			'{status}'           => Labels::status( $data['status'] ?? '' ),
			'{cancel_url}'       => $data['cancel_url']       ?? '',
			'{site_name}'        => get_bloginfo( 'name' ),
			'{admin_url}'        => admin_url( 'admin.php?page=cbm-bookings' ),
		];

		return str_replace( array_keys( $map ), array_values( $map ), $template );
	}

	private function parse( string $template, object $booking ): string {
		$data = (array) $booking;
		if ( isset( $booking->id ) ) {
			$data['cancel_url'] = Token::cancel_url( (int) $booking->id );
		}
		if ( empty( $data['resource_name'] ) && ! empty( $data['resource_id'] ) ) {
			$resource              = ( new ResourceRepository() )->find( (int) $data['resource_id'] );
			$data['resource_name'] = $resource->name ?? '';
		}
		return $this->replace_placeholders( $template, $data );
	}

	private function create_ics_file( object $booking ): ?string {
		$date = str_replace( '-', '', $booking->booking_date );
		$from = str_replace( ':', '', substr( $booking->time_from, 0, 5 ) ) . '00';
		$to   = str_replace( ':', '', substr( $booking->time_to,   0, 5 ) ) . '00';

		/* translators: %d: booking ID */
		$summary = sprintf( __( 'Booking #%d', 'crane-booking-manager' ), (int) $booking->id )
			. ' - ' . get_bloginfo( 'name' );

		$esc = static fn ( string $s ): string => str_replace(
			[ '\\', ';', ',', "\r\n", "\n" ],
			[ '\\\\', '\;', '\,', '\n', '\n' ],
			$s
		);

		// ponytail: TZID without a VTIMEZONE block; Google/Outlook accept named zones,
		// offset-style zones (e.g. "+02:00") fall back to floating local time.
		$tz     = wp_timezone_string();
		$tz_ref = str_contains( $tz, '/' ) ? ';TZID=' . $tz : '';

		$lines = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//Resource Booking Manager//EN',
			'METHOD:PUBLISH',
			'BEGIN:VEVENT',
			'UID:cbm-booking-' . absint( $booking->id ) . '@' . (string) wp_parse_url( home_url(), PHP_URL_HOST ),
			'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
			"DTSTART{$tz_ref}:{$date}T{$from}",
			"DTEND{$tz_ref}:{$date}T{$to}",
			'SUMMARY:' . $esc( $summary ),
			'LOCATION:' . $esc( (string) $booking->location ),
			'END:VEVENT',
			'END:VCALENDAR',
		];

		$path = get_temp_dir() . 'cbm-booking-' . absint( $booking->id ) . '.ics';

		return file_put_contents( $path, implode( "\r\n", $lines ) ) ? $path : null;
	}

	private function send( string $to, string $subject, string $body, array $attachments = [] ): void {
		if ( ! is_email( $to ) ) {
			return;
		}

		$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

		wp_mail(
			$to,
			wp_strip_all_tags( $subject ),
			$body,
			$headers,
			$attachments
		);
	}
}
