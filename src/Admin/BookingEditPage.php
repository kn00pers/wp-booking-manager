<?php
namespace CBM\Admin;

defined( 'ABSPATH' ) || exit;

use CBM\Repository\BookingRepository;
use CBM\Repository\ResourceRepository;
use CBM\Repository\LogRepository;

final class BookingEditPage {

	public ?object $booking = null;
	public array $resources = [];
	public array $logs = [];
	public bool $is_edit = false;

	public function __construct() {
		$booking_id = absint( $_GET['booking_id'] ?? 0 );

		$this->resources = ( new ResourceRepository() )->list( true );

		if ( $booking_id > 0 ) {
			$this->booking = ( new BookingRepository() )->find( $booking_id, true );
			$this->is_edit = $this->booking !== null;
			$this->logs    = ( new LogRepository() )->get_for_booking( $booking_id );
		}
	}
}
