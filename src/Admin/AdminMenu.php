<?php
namespace CBM\Admin;

defined( 'ABSPATH' ) || exit;

use CBM\Repository\BookingRepository;

final class AdminMenu {

	public function register_menus(): void {
		$menu_title = __( 'Bookings', 'crane-booking-manager' );

		$pending = ( new BookingRepository() )->count_by_status( 'pending' );
		if ( $pending > 0 ) {
			$menu_title .= sprintf(
				' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>',
				$pending
			);
		}

		add_menu_page(
			__( 'Bookings', 'crane-booking-manager' ),
			$menu_title,
			'manage_cbm_bookings',
			'cbm-bookings',
			[ $this, 'render_booking_list' ],
			'dashicons-calendar-alt',
			25
		);

		add_submenu_page(
			'cbm-bookings',
			__( 'Booking list', 'crane-booking-manager' ),
			__( 'Booking list', 'crane-booking-manager' ),
			'manage_cbm_bookings',
			'cbm-bookings',
			[ $this, 'render_booking_list' ]
		);

		add_submenu_page(
			'cbm-bookings',
			__( 'Calendar', 'crane-booking-manager' ),
			__( 'Calendar', 'crane-booking-manager' ),
			'manage_cbm_bookings',
			'cbm-calendar',
			[ $this, 'render_calendar' ]
		);

		add_submenu_page(
			'cbm-bookings',
			__( 'Add booking', 'crane-booking-manager' ),
			__( 'Add booking', 'crane-booking-manager' ),
			'manage_cbm_bookings',
			'cbm-add-booking',
			[ $this, 'render_booking_edit' ]
		);

		add_submenu_page(
			'cbm-bookings',
			__( 'Unavailability blocks', 'crane-booking-manager' ),
			__( 'Blocks', 'crane-booking-manager' ),
			'manage_cbm_bookings',
			'cbm-unavailability',
			[ $this, 'render_unavailability' ]
		);

		add_submenu_page(
			'cbm-bookings',
			SettingsPage::label_plural(),
			SettingsPage::label_plural(),
			'manage_cbm_bookings',
			'cbm-resources',
			[ $this, 'render_resources' ]
		);

		add_submenu_page(
			'cbm-bookings',
			__( 'Settings', 'crane-booking-manager' ),
			__( 'Settings', 'crane-booking-manager' ),
			'manage_cbm_bookings',
			'cbm-settings',
			[ $this, 'render_settings' ]
		);
	}

	public function render_booking_list(): void {
		$page = new BookingListTable();
		$page->prepare_items();
		require CBM_PLUGIN_DIR . 'views/admin/booking-list.php';
	}

	public function render_calendar(): void {
		$page = new BookingCalendarPage();
		require CBM_PLUGIN_DIR . 'views/admin/booking-calendar.php';
	}

	public function render_booking_edit(): void {
		$page = new BookingEditPage();
		require CBM_PLUGIN_DIR . 'views/admin/booking-edit.php';
	}

	public function render_unavailability(): void {
		$page = new UnavailabilityPage();
		require CBM_PLUGIN_DIR . 'views/admin/unavailability.php';
	}

	public function render_resources(): void {
		$page = new ResourcePage();
		require CBM_PLUGIN_DIR . 'views/admin/resources.php';
	}

	public function render_settings(): void {
		$page = new SettingsPage();
		$page->render();
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'cbm-' ) && ! str_contains( $hook, 'cbm_' ) ) {
			return;
		}

		wp_enqueue_style(
			'cbm-admin',
			CBM_PLUGIN_URL . 'assets/css/admin.css',
			[],
			CBM_VERSION
		);

		wp_enqueue_script(
			'cbm-admin',
			CBM_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			CBM_VERSION,
			true
		);

		wp_localize_script( 'cbm-admin', 'cbmAdmin', [
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'cbm_admin_action' ),
			'exportNonce' => wp_create_nonce( 'cbm_export_csv' ),
			'saveNonce'   => wp_create_nonce( 'cbm_save_booking' ),
			'i18n'        => [
				'confirmDelete'      => __( 'Are you sure you want to delete this booking?', 'crane-booking-manager' ),
				'confirmApprove'     => __( 'Are you sure you want to approve this booking?', 'crane-booking-manager' ),
				'confirmReject'      => __( 'Are you sure you want to reject this booking?', 'crane-booking-manager' ),
				'confirmRestore'     => __( 'Are you sure you want to restore this booking?', 'crane-booking-manager' ),
				'confirmDeleteBlock' => __( 'Are you sure you want to delete this block?', 'crane-booking-manager' ),
				'errorGeneral'       => __( 'An error occurred.', 'crane-booking-manager' ),
				'connectionError'    => __( 'Server connection error.', 'crane-booking-manager' ),
				'dismiss'            => __( 'Dismiss', 'crane-booking-manager' ),
				'statusApproved'     => __( 'Approved', 'crane-booking-manager' ),
				'statusRejected'     => __( 'Rejected', 'crane-booking-manager' ),
				'saving'             => __( 'Saving...', 'crane-booking-manager' ),
				'saveChanges'        => __( 'Save changes', 'crane-booking-manager' ),
				'addBooking'         => __( 'Add booking', 'crane-booking-manager' ),
			],
		] );
	}
}
