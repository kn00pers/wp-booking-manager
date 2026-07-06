<?php
defined( 'ABSPATH' ) || exit;

use CBM\Admin\SettingsPage;

$settings = SettingsPage::get_all();

global $wp_locale;
$days = [];
for ( $d = 0; $d <= 6; $d++ ) {
	$days[ $d ] = $wp_locale->get_weekday( $d );
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Booking settings', 'crane-booking-manager' ); ?></h1>
	<hr class="wp-header-end">

	<?php settings_errors( 'cbm_settings' ); ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'cbm_settings_group' ); ?>

		<h2><?php esc_html_e( 'Naming', 'crane-booking-manager' ); ?></h2>
		<p class="description"><?php esc_html_e( 'What is being booked? These labels are used across the admin panel (e.g. "Crane" / "Cranes", "Room" / "Rooms").', 'crane-booking-manager' ); ?></p>
		<table class="form-table">
			<tr>
				<th><label for="cbm-label-singular"><?php esc_html_e( 'Resource name (singular)', 'crane-booking-manager' ); ?></label></th>
				<td>
					<input type="text" id="cbm-label-singular" name="cbm_settings[resource_label_singular]"
						value="<?php echo esc_attr( $settings['resource_label_singular'] ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="cbm-label-plural"><?php esc_html_e( 'Resource name (plural)', 'crane-booking-manager' ); ?></label></th>
				<td>
					<input type="text" id="cbm-label-plural" name="cbm_settings[resource_label_plural]"
						value="<?php echo esc_attr( $settings['resource_label_plural'] ); ?>" class="regular-text">
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Working hours', 'crane-booking-manager' ); ?></h2>
		<table class="widefat cbm-hours-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Day', 'crane-booking-manager' ); ?></th>
					<th><?php esc_html_e( 'Active', 'crane-booking-manager' ); ?></th>
					<th><?php esc_html_e( 'Time from', 'crane-booking-manager' ); ?></th>
					<th><?php esc_html_e( 'Time to', 'crane-booking-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php for ( $d = 0; $d <= 6; $d++ ) :
					$h = $settings['working_hours'][ $d ];
				?>
				<tr>
					<td><?php echo esc_html( $days[ $d ] ); ?></td>
					<td>
						<input type="checkbox"
							name="cbm_settings[working_hours][<?php echo absint( $d ); ?>][enabled]"
							value="1"
							<?php checked( ! empty( $h['enabled'] ) ); ?>>
					</td>
					<td>
						<input type="time"
							name="cbm_settings[working_hours][<?php echo absint( $d ); ?>][time_from]"
							value="<?php echo esc_attr( $h['time_from'] ); ?>">
					</td>
					<td>
						<input type="time"
							name="cbm_settings[working_hours][<?php echo absint( $d ); ?>][time_to]"
							value="<?php echo esc_attr( $h['time_to'] ); ?>">
					</td>
				</tr>
				<?php endfor; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'General', 'crane-booking-manager' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="cbm-slot"><?php esc_html_e( 'Slot duration (minutes)', 'crane-booking-manager' ); ?></label></th>
				<td>
					<select name="cbm_settings[slot_duration]" id="cbm-slot">
						<option value="30" <?php selected( $settings['slot_duration'], 30 ); ?>><?php esc_html_e( '30 minutes', 'crane-booking-manager' ); ?></option>
						<option value="60" <?php selected( $settings['slot_duration'], 60 ); ?>><?php esc_html_e( '60 minutes', 'crane-booking-manager' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="cbm-advance"><?php esc_html_e( 'Minimum advance notice (hours)', 'crane-booking-manager' ); ?></label></th>
				<td>
					<input type="number" id="cbm-advance" name="cbm_settings[min_advance_hours]"
						value="<?php echo absint( $settings['min_advance_hours'] ); ?>" min="0" step="1">
					<p class="description"><?php esc_html_e( '0 = same-day booking allowed.', 'crane-booking-manager' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cbm-pending-blocks"><?php esc_html_e( 'Pending bookings block the slot', 'crane-booking-manager' ); ?></label></th>
				<td>
					<input type="checkbox" id="cbm-pending-blocks"
						name="cbm_settings[pending_blocks_slot]" value="1"
						<?php checked( ! empty( $settings['pending_blocks_slot'] ) ); ?>>
					<p class="description"><?php esc_html_e( 'If checked, a pending booking temporarily blocks the time slot.', 'crane-booking-manager' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cbm-reminder"><?php esc_html_e( 'Reminder e-mail one day before', 'crane-booking-manager' ); ?></label></th>
				<td>
					<input type="checkbox" id="cbm-reminder"
						name="cbm_settings[reminder_enabled]" value="1"
						<?php checked( ! empty( $settings['reminder_enabled'] ) ); ?>>
					<p class="description"><?php esc_html_e( 'Sends a reminder to the customer one day before an approved booking.', 'crane-booking-manager' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cbm-admin-email"><?php esc_html_e( 'Administrator e-mail', 'crane-booking-manager' ); ?></label></th>
				<td>
					<input type="email" id="cbm-admin-email" name="cbm_settings[admin_email]"
						value="<?php echo esc_attr( $settings['admin_email'] ); ?>" class="regular-text">
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Cloudflare Turnstile (reCAPTCHA)', 'crane-booking-manager' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Protect the form against bots by providing Cloudflare Turnstile keys.', 'crane-booking-manager' ); ?></p>
		<table class="form-table">
			<tr>
				<th><label for="cbm-turnstile-site"><?php esc_html_e( 'Site Key', 'crane-booking-manager' ); ?></label></th>
				<td>
					<input type="text" id="cbm-turnstile-site" name="cbm_settings[turnstile_site_key]"
						value="<?php echo esc_attr( $settings['turnstile_site_key'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="cbm-turnstile-secret"><?php esc_html_e( 'Secret Key', 'crane-booking-manager' ); ?></label></th>
				<td>
					<input type="text" id="cbm-turnstile-secret" name="cbm_settings[turnstile_secret_key]"
						value="<?php echo esc_attr( $settings['turnstile_secret_key'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'E-mail templates', 'crane-booking-manager' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Available placeholders:', 'crane-booking-manager' ); ?> <code>{booking_id}, {customer_name}, {customer_phone}, {customer_email}, {customer_company}, {date}, {time_from}, {time_to}, {location}, {notes}, {resource_name}, {status}, {cancel_url}, {site_name}, {admin_url}</code></p>

		<?php
		$mail_fields = [
			'admin_new'          => __( 'New booking → Admin', 'crane-booking-manager' ),
			'customer_pending'   => __( 'New booking → Customer (confirmation with ID)', 'crane-booking-manager' ),
			'customer_approved'  => __( 'Approved → Customer', 'crane-booking-manager' ),
			'customer_rejected'  => __( 'Rejected → Customer', 'crane-booking-manager' ),
			'customer_updated'   => __( 'Booking edited → Customer', 'crane-booking-manager' ),
			'customer_cancelled' => __( 'Cancelled by customer → Customer', 'crane-booking-manager' ),
			'admin_cancelled'    => __( 'Cancelled by customer → Admin', 'crane-booking-manager' ),
			'customer_reminder'  => __( 'Reminder one day before → Customer', 'crane-booking-manager' ),
		];
		foreach ( $mail_fields as $key => $label ) :
			$subject_key  = "mail_subject_{$key}";
			$template_key = "mail_template_{$key}";
		?>
		<h3><?php echo esc_html( $label ); ?></h3>
		<table class="form-table">
			<tr>
				<th><label><?php esc_html_e( 'Subject', 'crane-booking-manager' ); ?></label></th>
				<td>
					<input type="text" name="cbm_settings[<?php echo esc_attr( $subject_key ); ?>]"
						value="<?php echo esc_attr( $settings[ $subject_key ] ?? '' ); ?>" class="large-text">
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Body', 'crane-booking-manager' ); ?></label></th>
				<td>
					<textarea name="cbm_settings[<?php echo esc_attr( $template_key ); ?>]"
						rows="6" class="large-text"><?php echo esc_textarea( $settings[ $template_key ] ?? '' ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php endforeach; ?>

		<?php submit_button( __( 'Save settings', 'crane-booking-manager' ) ); ?>
	</form>
</div>
