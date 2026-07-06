<?php
namespace CBM\Service;

defined( 'ABSPATH' ) || exit;

use CBM\Admin\SettingsPage;
use CBM\Helpers\DateTimeHelper;
use CBM\Repository\BookingRepository;
use CBM\Repository\UnavailabilityRepository;

final class AvailabilityService {

	public function __construct(
		private readonly BookingRepository $booking_repo,
		private readonly UnavailabilityRepository $unavail_repo
	) {}

	
	public function get_available_slots( int $resource_id, string $date ): array {
		$settings    = SettingsPage::get_all();
		$day_of_week = (int) ( new \DateTimeImmutable( $date ) )->format( 'w' );
		$hours       = $settings['working_hours'][ $day_of_week ] ?? [];

		if ( empty( $hours['enabled'] ) ) {
			return [
				'available'   => [],
				'blocked'     => [],
				'busy_ranges' => [],
				'work_end'    => '',
				'slot_minutes'=> 0,
				'day_blocked' => true,
				'reason'      => __( 'Day unavailable.', 'crane-booking-manager' ),
			];
		}

		$advance_hours = (int) ( $settings['min_advance_hours'] ?? 0 );
		if ( $advance_hours > 0 && DateTimeHelper::date_is_in_past( $date, $advance_hours ) ) {
			return [
				'available'   => [],
				'blocked'     => [],
				'busy_ranges' => [],
				'work_end'    => '',
				'slot_minutes'=> 0,
				'day_blocked' => true,
				'reason'      => __( 'Date too soon - advance notice required.', 'crane-booking-manager' ),
			];
		}

		$slot_duration = (int) ( $settings['slot_duration'] ?? 60 );
		$all_slots     = DateTimeHelper::generate_slots(
			$hours['time_from'],
			$hours['time_to'],
			$slot_duration
		);

		$blocked_ranges = $this->get_blocked_ranges( $resource_id, $date, $settings );
		$busy_ranges = [];
		foreach ( $blocked_ranges as $range ) {
			$busy_ranges[] = [
				'from' => substr( $range['from'], 0, 5 ),
				'to'   => substr( $range['to'], 0, 5 ),
			];
		}
		usort(
			$busy_ranges,
			static fn( $a, $b ) => DateTimeHelper::time_to_minutes( $a['from'] ) <=> DateTimeHelper::time_to_minutes( $b['from'] )
		);

		$available = [];
		$blocked   = [];

		foreach ( $all_slots as $slot_start ) {
			$slot_end = DateTimeHelper::minutes_to_time(
				DateTimeHelper::time_to_minutes( $slot_start ) + $slot_duration
			);

			$is_blocked = false;
			foreach ( $blocked_ranges as $range ) {
				if ( DateTimeHelper::times_overlap( $slot_start, $slot_end, $range['from'], $range['to'] ) ) {
					$is_blocked = true;
					break;
				}
			}

			if ( $is_blocked ) {
				$blocked[] = $slot_start;
			} else {
				$available[] = $slot_start;
			}
		}

		return [
			'available'    => $available,
			'blocked'      => $blocked,
			'busy_ranges'  => $busy_ranges,
			'work_end'     => substr( $hours['time_to'], 0, 5 ),
			'slot_minutes' => $slot_duration,
			'day_blocked'  => false,
			'reason'       => '',
		];
	}

	
	public function check_conflict(
		int $resource_id,
		string $date,
		string $time_from,
		string $time_to,
		?int $exclude_id = null
	): bool {
		$settings = SettingsPage::get_all();
		$statuses = [ 'approved' ];

		if ( ! empty( $settings['pending_blocks_slot'] ) ) {
			$statuses[] = 'pending';
		}

		$conflicts = $this->booking_repo->find_conflicting(
			$resource_id,
			$date,
			$time_from,
			$time_to,
			$statuses,
			$exclude_id
		);

		if ( ! empty( $conflicts ) ) {
			return true;
		}

		$unavail = $this->unavail_repo->find_for_range( $resource_id, $date, $date );
		foreach ( $unavail as $row ) {
			if ( $row->time_from === null ) {
				return true;
			}
			if ( DateTimeHelper::times_overlap( $time_from, $time_to, $row->time_from, $row->time_to ) ) {
				return true;
			}
		}

		return false;
	}

	
	public function get_blocked_dates( int $resource_id, string $year_month ): array {
		$settings = SettingsPage::get_all();

		[ $year, $month ] = array_map( 'intval', explode( '-', $year_month ) );
		$days_in_month    = cal_days_in_month( CAL_GREGORIAN, $month, $year );

		$blocked = [];

		for ( $day = 1; $day <= $days_in_month; $day++ ) {
			$date        = sprintf( '%04d-%02d-%02d', $year, $month, $day );
			$day_of_week = (int) ( new \DateTimeImmutable( $date ) )->format( 'w' );
			$hours       = $settings['working_hours'][ $day_of_week ] ?? [];

			if ( empty( $hours['enabled'] ) ) {
				$blocked[] = $date;
				continue;
			}

			$advance = (int) ( $settings['min_advance_hours'] ?? 0 );
			if ( $advance > 0 && DateTimeHelper::date_is_in_past( $date, $advance ) ) {
				$blocked[] = $date;
				continue;
			}

			$slots = $this->get_available_slots( $resource_id, $date );
			if ( empty( $slots['available'] ) ) {
				$blocked[] = $date;
			}
		}

		return $blocked;
	}

	
	public function get_month_status( int $resource_id, string $year_month ): array {
		global $wpdb;

		$settings      = SettingsPage::get_all();
		[ $year, $month ] = array_map( 'intval', explode( '-', $year_month ) );
		$days_in_month = cal_days_in_month( CAL_GREGORIAN, $month, $year );
		$slot_duration = (int) ( $settings['slot_duration'] ?? 60 );
		$advance_hours = (int) ( $settings['min_advance_hours'] ?? 0 );
		$month_start   = sprintf( '%04d-%02d-01', $year, $month );
		$month_end     = sprintf( '%04d-%02d-%02d', $year, $month, $days_in_month );
		$statuses     = [ 'approved' ];
		if ( ! empty( $settings['pending_blocks_slot'] ) ) {
			$statuses[] = 'pending';
		}
		$table        = $wpdb->prefix . 'cbm_bookings';
		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT booking_date, time_from, time_to FROM `{$table}`
				 WHERE resource_id  = %d
				 AND   booking_date BETWEEN %s AND %s
				 AND   deleted_at   IS NULL
				 AND   status       IN ({$placeholders})",
				...array_merge( [ $resource_id, $month_start, $month_end ], $statuses )
			)
		);

		$bookings_by_date = [];
		foreach ( $rows as $row ) {
			$bookings_by_date[ $row->booking_date ][] = [ 'from' => $row->time_from, 'to' => $row->time_to ];
		}
		$unavail_records = $this->unavail_repo->find_for_range( $resource_id, $month_start, $month_end );

		$result = [];

		for ( $day = 1; $day <= $days_in_month; $day++ ) {
			$date        = sprintf( '%04d-%02d-%02d', $year, $month, $day );
			$day_of_week = (int) ( new \DateTimeImmutable( $date ) )->format( 'w' );
			$hours       = $settings['working_hours'][ $day_of_week ] ?? [];

			if ( empty( $hours['enabled'] ) ) {
				$result[ $date ] = 'disabled';
				continue;
			}

			if ( $advance_hours > 0 && DateTimeHelper::date_is_in_past( $date, $advance_hours ) ) {
				$result[ $date ] = 'past';
				continue;
			}

			$all_slots   = DateTimeHelper::generate_slots( $hours['time_from'], $hours['time_to'], $slot_duration );
			$total_slots = count( $all_slots );

			if ( $total_slots === 0 ) {
				$result[ $date ] = 'disabled';
				continue;
			}
			$blocked_ranges = [];

			foreach ( $bookings_by_date[ $date ] ?? [] as $booking ) {
				$blocked_ranges[] = [ 'from' => $booking['from'], 'to' => $booking['to'] ];
			}

			foreach ( $unavail_records as $row ) {
				if ( $row->date_from <= $date && $row->date_to >= $date ) {
					$blocked_ranges[] = [
						'from' => $row->time_from ?? '00:00',
						'to'   => $row->time_to   ?? '23:59',
					];
				}
			}
			$available_count = 0;
			foreach ( $all_slots as $slot_start ) {
				$slot_end   = DateTimeHelper::minutes_to_time(
					DateTimeHelper::time_to_minutes( $slot_start ) + $slot_duration
				);
				$is_blocked = false;
				foreach ( $blocked_ranges as $range ) {
					if ( DateTimeHelper::times_overlap( $slot_start, $slot_end, $range['from'], $range['to'] ) ) {
						$is_blocked = true;
						break;
					}
				}
				if ( ! $is_blocked ) {
					$available_count++;
				}
			}

			if ( $available_count === 0 ) {
				$result[ $date ] = 'fully_booked';
			} elseif ( $available_count < $total_slots ) {
				$result[ $date ] = 'partial';
			} else {
				$result[ $date ] = 'available';
			}
		}

		return $result;
	}

	
	private function get_blocked_ranges( int $resource_id, string $date, array $settings ): array {
		global $wpdb;

		$ranges   = [];
		$statuses = [ 'approved' ];

		if ( ! empty( $settings['pending_blocks_slot'] ) ) {
			$statuses[] = 'pending';
		}

		$table        = $wpdb->prefix . 'cbm_bookings';
		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT time_from, time_to FROM `{$table}`
				 WHERE resource_id  = %d
				 AND   booking_date = %s
				 AND   deleted_at   IS NULL
				 AND   status       IN ({$placeholders})",
				...array_merge( [ $resource_id, $date ], $statuses )
			)
		);

		foreach ( $rows as $row ) {
			$ranges[] = [ 'from' => $row->time_from, 'to' => $row->time_to ];
		}

		$unavail = $this->unavail_repo->find_for_range( $resource_id, $date, $date );
		foreach ( $unavail as $row ) {
			$ranges[] = [
				'from' => $row->time_from ?? '00:00',
				'to'   => $row->time_to   ?? '23:59',
			];
		}

		return $ranges;
	}
}

