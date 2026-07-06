<?php
defined( 'ABSPATH' ) || exit;

use CBM\Helpers\Labels;

?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Bookings', 'crane-booking-manager' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=cbm-add-booking' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add booking', 'crane-booking-manager' ); ?></a>
	<hr class="wp-header-end">

	<div id="cbm-notice"></div>

	<form method="get" class="cbm-filters">
		<input type="hidden" name="page" value="cbm-bookings">
		<select name="status">
			<option value=""><?php esc_html_e( '- All statuses -', 'crane-booking-manager' ); ?></option>
			<?php foreach ( Labels::statuses() as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( sanitize_text_field( $_GET['status'] ?? '' ), $val ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<input type="date" name="date_from" value="<?php echo esc_attr( sanitize_text_field( $_GET['date_from'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Date from', 'crane-booking-manager' ); ?>">
		<input type="date" name="date_to"   value="<?php echo esc_attr( sanitize_text_field( $_GET['date_to']   ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Date to', 'crane-booking-manager' ); ?>">
		<input type="search" name="s" value="<?php echo esc_attr( sanitize_text_field( $_GET['s'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Search customer...', 'crane-booking-manager' ); ?>">
		<label>
			<input type="checkbox" name="show_deleted" value="1" <?php checked( ! empty( $_GET['show_deleted'] ) ); ?>>
			<?php esc_html_e( 'Show deleted', 'crane-booking-manager' ); ?>
		</label>
		<?php submit_button( __( 'Filter', 'crane-booking-manager' ), 'secondary', '', false ); ?>
	</form>

	<p>
		<button id="cbm-export-csv" class="button"><?php esc_html_e( 'Export to CSV', 'crane-booking-manager' ); ?></button>
	</p>

	<form method="post" id="cbm-booking-list-form">
		<?php $page->display(); ?>
	</form>

	<div id="cbm-reject-modal" style="display:none;" class="cbm-modal">
		<div class="cbm-modal__inner">
			<h2><?php esc_html_e( 'Reject booking', 'crane-booking-manager' ); ?></h2>
			<label><?php esc_html_e( 'Rejection reason (optional):', 'crane-booking-manager' ); ?></label>
			<textarea id="cbm-reject-note" rows="3" style="width:100%"></textarea>
			<br><br>
			<button type="button" class="button button-primary" id="cbm-reject-confirm"><?php esc_html_e( 'Reject', 'crane-booking-manager' ); ?></button>
			<button type="button" class="button" id="cbm-reject-cancel"><?php esc_html_e( 'Cancel', 'crane-booking-manager' ); ?></button>
		</div>
	</div>
</div>
