<?php
namespace CBM\Helpers;

defined( 'ABSPATH' ) || exit;

final class DateTimeHelper {

	public static function now_wp(): \DateTimeImmutable {
		return new \DateTimeImmutable( 'now', wp_timezone() );
	}

	public static function validate_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$d = \DateTimeImmutable::createFromFormat( 'Y-m-d', $date );
		return $d !== false && $d->format( 'Y-m-d' ) === $date;
	}

	public static function validate_time( string $time ): bool {
		if ( ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
			return false;
		}
		[ $h, $m ] = explode( ':', $time );
		return (int) $h >= 0 && (int) $h <= 23 && (int) $m >= 0 && (int) $m <= 59;
	}

	
	public static function generate_slots(
		string $time_from,
		string $time_to,
		int $duration_minutes
	): array {
		$slots    = [];
		$start    = self::time_to_minutes( $time_from );
		$end      = self::time_to_minutes( $time_to );
		$duration = max( 1, $duration_minutes );

		for ( $m = $start; $m < $end; $m += $duration ) {
			$slots[] = self::minutes_to_time( $m );
		}

		return $slots;
	}

	
	public static function times_overlap(
		string $start1,
		string $end1,
		string $start2,
		string $end2
	): bool {
		$s1 = self::time_to_minutes( $start1 );
		$e1 = self::time_to_minutes( $end1 );
		$s2 = self::time_to_minutes( $start2 );
		$e2 = self::time_to_minutes( $end2 );

		return $s1 < $e2 && $e1 > $s2;
	}

	public static function time_to_minutes( string $time ): int {
		[ $h, $m ] = array_map( 'intval', explode( ':', $time ) );
		return $h * 60 + $m;
	}

	public static function minutes_to_time( int $minutes ): string {
		return sprintf( '%02d:%02d', intdiv( $minutes, 60 ), $minutes % 60 );
	}

	public static function date_is_in_past( string $date, int $advance_hours ): bool {
		$now      = self::now_wp();
		$boundary = $now->modify( "+{$advance_hours} hours" );
		$booking_day_end = \DateTimeImmutable::createFromFormat(
			'Y-m-d',
			$date,
			wp_timezone()
		)->setTime( 23, 59, 59 );

		return $booking_day_end < $boundary;
	}
}
