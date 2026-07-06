<?php
namespace CBM\Helpers;

defined( 'ABSPATH' ) || exit;


final class Token {

	private const OPTION = 'cbm_secret';

	private static function secret(): string {
		$secret = get_option( self::OPTION, '' );
		if ( ! is_string( $secret ) || $secret === '' ) {
			$secret = wp_generate_password( 64, true, true );
			update_option( self::OPTION, $secret, false );
		}
		return $secret;
	}

	public static function for_booking( int $booking_id ): string {
		return hash_hmac( 'sha256', 'cbm-cancel:' . $booking_id, self::secret() );
	}

	public static function verify( int $booking_id, string $token ): bool {
		return hash_equals( self::for_booking( $booking_id ), $token );
	}

	public static function cancel_url( int $booking_id ): string {
		return add_query_arg(
			[
				'cbm_action' => 'cancel',
				'booking_id' => $booking_id,
				'cbm_token'  => self::for_booking( $booking_id ),
			],
			home_url( '/' )
		);
	}
}
