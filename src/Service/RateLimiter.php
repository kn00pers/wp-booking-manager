<?php
namespace CBM\Service;

defined( 'ABSPATH' ) || exit;

final class RateLimiter {

	private int $max;
	private int $window;

	public function __construct( int $max = 5, int $window_seconds = 600 ) {
		$this->max    = $max;
		$this->window = $window_seconds;
	}

	
	public function check( string $ip ): bool {
		$key   = 'cbm_rate_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= $this->max ) {
			return false;
		}

		set_transient( $key, $count + 1, $this->window );

		return true;
	}
}
