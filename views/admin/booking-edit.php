<?php
defined( 'ABSPATH' ) || exit;

use CBM\Admin\SettingsPage;
use CBM\Helpers\Labels;

$b     = $page->booking;
$title = $page->is_edit
	/* translators: %d: booking ID */
	? sprintf( __( 'Edit booking #%d', 'crane-booking-manager' ), absint( $b->id ) )
	: __( 'Add booking', 'crane-booking-manager' );
$statuses = Labels::statuses();
?>
<div class="wrap">
	<h1><?php echo esc_html( $title ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=cbm-bookings' ) ); ?>" class="button">&larr; <?php esc_html_e( 'Booking list', 'crane-booking-manager' ); ?></a>
	<hr class="wp-header-end">

	<div id="cbm-notice"></div>

	<form id="cbm-edit-form" class="cbm-admin-form">
		<?php if ( $page->is_edit ) : ?>
			<input type="hidden" name="booking_id" value="<?php echo absint( $b->id ); ?>">
		<?php endif; ?>

		<table class="form-table">
			<tr>
				<th><label for="cbm-resource"><?php echo esc_html( SettingsPage::label_singular() ); ?> *</label></th>
				<td>
					<select name="resource_id" id="cbm-resource" required>
						<?php foreach ( $page->resources as $r ) : ?>
							<option value="<?php echo absint( $r->id ); ?>" <?php selected( (int) ( $b->resource_id ?? 1 ), (int) $r->id ); ?>>
								<?php echo esc_html( $r->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="cbm-name"><?php esc_html_e( 'Full name', 'crane-booking-manager' ); ?></label></th>
				<td><input type="text" id="cbm-name" name="customer_name" value="<?php echo esc_attr( $b->customer_name ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="cbm-phone"><?php esc_html_e( 'Phone', 'crane-booking-manager' ); ?></label></th>
				<td><input type="tel" id="cbm-phone" name="customer_phone" value="<?php echo esc_attr( $b->customer_phone ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="cbm-email"><?php esc_html_e( 'E-mail', 'crane-booking-manager' ); ?></label></th>
				<td><input type="email" id="cbm-email" name="customer_email" value="<?php echo esc_attr( $b->customer_email ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="cbm-company"><?php esc_html_e( 'Company', 'crane-booking-manager' ); ?></label></th>
				<td><input type="text" id="cbm-company" name="customer_company" value="<?php echo esc_attr( $b->customer_company ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="cbm-location"><?php esc_html_e( 'Location', 'crane-booking-manager' ); ?></label></th>
				<td><textarea id="cbm-location" name="location" rows="3" class="large-text"><?php echo esc_textarea( $b->location ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="cbm-date"><?php esc_html_e( 'Date', 'crane-booking-manager' ); ?> *</label></th>
				<td><input type="date" id="cbm-date" name="booking_date" value="<?php echo esc_attr( $b->booking_date ?? '' ); ?>" required></td>
			</tr>
			<tr>
				<th><label for="cbm-time-from"><?php esc_html_e( 'Time from', 'crane-booking-manager' ); ?> *</label></th>
				<td><input type="time" id="cbm-time-from" name="time_from" value="<?php echo esc_attr( substr( $b->time_from ?? '', 0, 5 ) ); ?>" required></td>
			</tr>
			<tr>
				<th><label for="cbm-time-to"><?php esc_html_e( 'Time to', 'crane-booking-manager' ); ?> *</label></th>
				<td><input type="time" id="cbm-time-to" name="time_to" value="<?php echo esc_attr( substr( $b->time_to ?? '', 0, 5 ) ); ?>" required></td>
			</tr>
			<tr>
				<th><label for="cbm-status"><?php esc_html_e( 'Status', 'crane-booking-manager' ); ?> *</label></th>
				<td>
					<select name="status" id="cbm-status" required>
						<?php foreach ( $statuses as $val => $label ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $b->status ?? 'pending', $val ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="cbm-notes"><?php esc_html_e( 'Notes', 'crane-booking-manager' ); ?></label></th>
				<td><textarea id="cbm-notes" name="notes" rows="4" class="large-text"><?php echo esc_textarea( $b->notes ?? '' ); ?></textarea></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php echo esc_html( $page->is_edit ? __( 'Save changes', 'crane-booking-manager' ) : __( 'Add booking', 'crane-booking-manager' ) ); ?>
			</button>
		</p>
	</form>

	<?php if ( $page->is_edit && ! empty( $page->logs ) ) : ?>
		<h2><?php esc_html_e( 'Change history', 'crane-booking-manager' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'crane-booking-manager' ); ?></th>
					<th><?php esc_html_e( 'Status change', 'crane-booking-manager' ); ?></th>
					<th><?php esc_html_e( 'User', 'crane-booking-manager' ); ?></th>
					<th><?php esc_html_e( 'Note', 'crane-booking-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $page->logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( $log->created_at ); ?></td>
						<td><?php echo esc_html( ( $log->old_status ?? '-' ) . ' → ' . $log->new_status ); ?></td>
						<td>
							<?php
							if ( $log->changed_by_user_id ) {
								$user = get_userdata( (int) $log->changed_by_user_id );
								echo $user ? esc_html( $user->display_name ) : '-';
							} else {
								echo '-';
							}
							?>
						</td>
						<td><?php echo esc_html( $log->note ?? '' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
