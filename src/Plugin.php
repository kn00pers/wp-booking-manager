<?php
namespace CBM;

defined( 'ABSPATH' ) || exit;

use CBM\Admin\SettingsPage;

final class Plugin {

	private static ?self $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	private function __clone(): void {}

	public function __wakeup(): never {
		throw new \RuntimeException( 'Plugin singleton cannot be unserialized.' );
	}

	public function init(): void {
		$this->load_textdomain();
		$this->register_hooks();
	}

	private function load_textdomain(): void {
		load_plugin_textdomain(
			'crane-booking-manager',
			false,
			dirname( plugin_basename( CBM_PLUGIN_FILE ) ) . '/languages'
		);
	}

	private function register_hooks(): void {
		add_action( 'rest_api_init', static function (): void {
			( new Http\RestController() )->register_routes();
		} );

		$shortcode = new Frontend\Shortcode();
		add_shortcode( 'crane_booking_form', [ $shortcode, 'render' ] );
		add_shortcode( 'resource_booking_form', [ $shortcode, 'render' ] );
		( new Frontend\Cancellation() )->register();

		$menu = new Admin\AdminMenu();
		add_action( 'admin_menu',            [ $menu, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $menu, 'enqueue_assets' ] );

		( new Admin\SettingsPage() )->register();

		$ajax = new Http\AjaxController();
		$ajax->register_hooks();

		$this->register_reminders();
	}

	private function register_reminders(): void {
		add_action( 'cbm_send_reminders', static function (): void {
			if ( ! SettingsPage::get( 'reminder_enabled', true ) ) {
				return;
			}
			$repo     = new Repository\BookingRepository();
			$mail     = new Service\MailService();
			$tomorrow = wp_date( 'Y-m-d', time() + DAY_IN_SECONDS );
			foreach ( $repo->find_by_status_and_date( 'approved', $tomorrow ) as $booking ) {
				$mail->send_reminder_customer( $booking );
			}
		} );

		if ( ! wp_next_scheduled( 'cbm_send_reminders' ) ) {
			$first = new \DateTimeImmutable( 'tomorrow 08:00', wp_timezone() );
			wp_schedule_event( $first->getTimestamp(), 'daily', 'cbm_send_reminders' );
		}
	}
}
