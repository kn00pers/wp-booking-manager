<?php
namespace CBM\Admin;

defined( 'ABSPATH' ) || exit;

use CBM\Repository\BookingRepository;

final class BookingCalendarPage {

	public int $year;
	public int $month;
	public int $days_in_month;
	public int $first_weekday;
	
	public array $by_date = [];

	public function __construct() {
		$this->year  = absint( $_GET['year']  ?? (int) current_time( 'Y' ) );
		$this->month = absint( $_GET['month'] ?? (int) current_time( 'n' ) );

		$this->year  = max( 2020, min( 2035, $this->year ) );
		$this->month = max( 1,    min( 12,   $this->month ) );

		$this->days_in_month = cal_days_in_month( CAL_GREGORIAN, $this->month, $this->year );
		$this->first_weekday = (int) ( new \DateTimeImmutable(
			sprintf( '%04d-%02d-01', $this->year, $this->month )
		) )->format( 'N' );

		$date_from = sprintf( '%04d-%02d-01', $this->year, $this->month );
		$date_to   = sprintf( '%04d-%02d-%02d', $this->year, $this->month, $this->days_in_month );

		$repo   = new BookingRepository();
		$result = $repo->list(
			[ 'date_from' => $date_from, 'date_to' => $date_to, 'orderby' => 'booking_date', 'order' => 'ASC' ],
			999,
			1
		);

		foreach ( $result['items'] as $b ) {
			if ( $b->status === 'rejected' ) {
				continue;
			}
			$this->by_date[ $b->booking_date ][] = $b;
		}
	}

	public function prev_url(): string {
		$m = $this->month - 1;
		$y = $this->year;
		if ( $m < 1 ) { $m = 12; $y--; }
		return admin_url( 'admin.php?page=cbm-calendar&year=' . $y . '&month=' . $m );
	}

	public function next_url(): string {
		$m = $this->month + 1;
		$y = $this->year;
		if ( $m > 12 ) { $m = 1; $y++; }
		return admin_url( 'admin.php?page=cbm-calendar&year=' . $y . '&month=' . $m );
	}

	public function month_label(): string {
		global $wp_locale;
		return $wp_locale->get_month( $this->month ) . ' ' . $this->year;
	}

	public function google_calendar_url( object $booking ): string {
		$tz       = wp_timezone_string();
		$date     = str_replace( '-', '', $booking->booking_date );
		$from     = str_replace( ':', '', substr( $booking->time_from, 0, 5 ) ) . '00';
		$to       = str_replace( ':', '', substr( $booking->time_to,   0, 5 ) ) . '00';
		$title    = __( 'Booking', 'crane-booking-manager' ) . ' - ' . $booking->customer_name;
		$details  = __( 'Customer', 'crane-booking-manager' ) . ": {$booking->customer_name} | "
			. __( 'Phone', 'crane-booking-manager' ) . ": {$booking->customer_phone}"
			. ( $booking->customer_company ? ' | ' . __( 'Company', 'crane-booking-manager' ) . ": {$booking->customer_company}" : '' )
			. " | ID: {$booking->id}";
		$location = $booking->location;

		return 'https://calendar.google.com/calendar/render?' . http_build_query( [
			'action'   => 'TEMPLATE',
			'text'     => $title,
			'dates'    => "{$date}T{$from}/{$date}T{$to}",
			'details'  => $details,
			'location' => $location,
			'ctz'      => $tz,
		] );
	}
}

