<?php
namespace CBM\Frontend;

defined( 'ABSPATH' ) || exit;

use CBM\Helpers\Token;
use CBM\Repository\BookingRepository;
use CBM\Service\BookingService;


final class Cancellation {

	public function register(): void {
		add_action( 'template_redirect', [ $this, 'maybe_handle' ] );
	}

	public function maybe_handle(): void {
		if ( is_admin() ) {
			return;
		}
		if ( ( $_GET['cbm_action'] ?? '' ) !== 'cancel' ) {
			return;
		}

		$booking_id = absint( $_GET['booking_id'] ?? 0 );
		$token      = sanitize_text_field( wp_unslash( $_GET['cbm_token'] ?? '' ) );

		$this->render_page( $booking_id, $token );
		exit;
	}

	private function render_page( int $booking_id, string $token ): void {
		$valid   = $booking_id > 0 && Token::verify( $booking_id, $token );
		$booking = $valid ? ( new BookingRepository() )->find( $booking_id ) : null;

		$state   = 'error';
		$message = __( 'Invalid or expired cancellation link.', 'crane-booking-manager' );

		if ( $booking ) {
			if ( in_array( $booking->status, [ 'cancelled', 'rejected' ], true ) ) {
				$state   = 'info';
				$message = __( 'This booking is no longer active and cannot be cancelled.', 'crane-booking-manager' );
			} else {
				$state = 'confirm';
			}
		}
		if (
			$state === 'confirm' &&
			( $_SERVER['REQUEST_METHOD'] ?? '' ) === 'POST'
		) {
			$nonce_ok = isset( $_POST['cbm_cancel_nonce'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cbm_cancel_nonce'] ) ), 'cbm_cancel_' . $booking_id );

			if ( $nonce_ok && Token::verify( $booking_id, $token ) ) {
				$result  = BookingService::make()->cancel_by_customer( $booking_id );
				$state   = $result['success'] ? 'success' : 'error';
				$message = $result['message'];
			} else {
				$state   = 'error';
				$message = __( 'Session expired. Refresh the page and try again.', 'crane-booking-manager' );
			}
		}
		get_header();
		?>
		<div class="cbm-cancel-wrap" style="max-width:640px;margin:3rem auto;padding:0 1rem;">
			<div class="cbm-cancel-card" style="border:1px solid #e5e7eb;border-radius:10px;padding:2rem;background:#fff;">
				<h1 style="margin-top:0;font-size:1.5rem;"><?php esc_html_e( 'Booking cancellation', 'crane-booking-manager' ); ?></h1>

				<?php if ( $state === 'confirm' && $booking ) : ?>
					<p><?php esc_html_e( 'Are you sure you want to cancel the booking below?', 'crane-booking-manager' ); ?></p>
					<table style="width:100%;border-collapse:collapse;margin:1rem 0;">
						<tr><td style="padding:.4rem 0;color:#6b7280;"><?php esc_html_e( 'Number:', 'crane-booking-manager' ); ?></td><td style="padding:.4rem 0;font-weight:600;">#<?php echo absint( $booking->id ); ?></td></tr>
						<tr><td style="padding:.4rem 0;color:#6b7280;"><?php esc_html_e( 'Full name:', 'crane-booking-manager' ); ?></td><td style="padding:.4rem 0;"><?php echo esc_html( $booking->customer_name ); ?></td></tr>
						<tr><td style="padding:.4rem 0;color:#6b7280;"><?php esc_html_e( 'Date:', 'crane-booking-manager' ); ?></td><td style="padding:.4rem 0;"><?php echo esc_html( $booking->booking_date ); ?></td></tr>
						<tr><td style="padding:.4rem 0;color:#6b7280;"><?php esc_html_e( 'Time:', 'crane-booking-manager' ); ?></td><td style="padding:.4rem 0;"><?php echo esc_html( substr( $booking->time_from, 0, 5 ) . ' - ' . substr( $booking->time_to, 0, 5 ) ); ?></td></tr>
						<tr><td style="padding:.4rem 0;color:#6b7280;"><?php esc_html_e( 'Location:', 'crane-booking-manager' ); ?></td><td style="padding:.4rem 0;"><?php echo esc_html( $booking->location ); ?></td></tr>
					</table>
					<form method="post">
						<?php wp_nonce_field( 'cbm_cancel_' . $booking_id, 'cbm_cancel_nonce' ); ?>
						<button type="submit"
							style="background:#dc2626;color:#fff;border:none;border-radius:6px;padding:.7rem 1.6rem;font-size:1rem;font-weight:600;cursor:pointer;">
							<?php esc_html_e( 'Cancel booking', 'crane-booking-manager' ); ?>
						</button>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>"
							style="margin-left:1rem;color:#6b7280;text-decoration:none;"><?php esc_html_e( 'Go back', 'crane-booking-manager' ); ?></a>
					</form>

				<?php elseif ( $state === 'success' ) : ?>
					<div style="padding:1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;color:#166534;">
						<?php echo esc_html( $message ); ?>
					</div>

				<?php elseif ( $state === 'info' ) : ?>
					<div style="padding:1rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;color:#1e40af;">
						<?php echo esc_html( $message ); ?>
					</div>

				<?php else : ?>
					<div style="padding:1rem;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;color:#991b1b;">
						<?php echo esc_html( $message ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		get_footer();
	}
}

