<?php
defined( 'ABSPATH' ) || exit;

use CBM\Admin\SettingsPage;

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Unavailability blocks', 'crane-booking-manager' ); ?></h1>
	<hr class="wp-header-end">
	<div id="cbm-notice"></div>

	<div class="cbm-split">
		<div class="cbm-split__form">
			<h2><?php esc_html_e( 'Add block', 'crane-booking-manager' ); ?></h2>
			<form id="cbm-unavail-form">
				<table class="form-table">
					<tr>
						<th><label for="cbm-unavail-resource"><?php echo esc_html( SettingsPage::label_singular() ); ?></label></th>
						<td>
							<select name="resource_id" id="cbm-unavail-resource">
								<?php foreach ( $page->resources as $r ) : ?>
									<option value="<?php echo absint( $r->id ); ?>"><?php echo esc_html( $r->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="cbm-unavail-from"><?php esc_html_e( 'Date from', 'crane-booking-manager' ); ?> *</label></th>
						<td><input type="date" name="date_from" id="cbm-unavail-from" required></td>
					</tr>
					<tr>
						<th><label for="cbm-unavail-to"><?php esc_html_e( 'Date to', 'crane-booking-manager' ); ?> *</label></th>
						<td><input type="date" name="date_to" id="cbm-unavail-to" required></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Time from', 'crane-booking-manager' ); ?></label></th>
						<td>
							<input type="time" name="time_from" id="cbm-unavail-time-from">
							<span class="description"><?php esc_html_e( 'Empty = whole day', 'crane-booking-manager' ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Time to', 'crane-booking-manager' ); ?></label></th>
						<td><input type="time" name="time_to" id="cbm-unavail-time-to"></td>
					</tr>
					<tr>
						<th><label for="cbm-unavail-reason"><?php esc_html_e( 'Reason', 'crane-booking-manager' ); ?></label></th>
						<td><input type="text" name="reason" id="cbm-unavail-reason" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Maintenance, Holiday', 'crane-booking-manager' ); ?>"></td>
					</tr>
				</table>
				<p>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Add block', 'crane-booking-manager' ); ?></button>
				</p>
			</form>
		</div>

		<div class="cbm-split__list">
			<h2><?php esc_html_e( 'Existing blocks', 'crane-booking-manager' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html( SettingsPage::label_singular() ); ?></th>
						<th><?php esc_html_e( 'Date from', 'crane-booking-manager' ); ?></th>
						<th><?php esc_html_e( 'Date to', 'crane-booking-manager' ); ?></th>
						<th><?php esc_html_e( 'Time from', 'crane-booking-manager' ); ?></th>
						<th><?php esc_html_e( 'Time to', 'crane-booking-manager' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'crane-booking-manager' ); ?></th>
						<th><?php esc_html_e( 'Action', 'crane-booking-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $page->records ) ) : ?>
						<tr><td colspan="7"><?php esc_html_e( 'No blocks.', 'crane-booking-manager' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $page->records as $row ) : ?>
							<tr id="cbm-unavail-row-<?php echo absint( $row->id ); ?>">
								<td><?php
$res_name = absint( $row->resource_id );
foreach ( $page->resources as $r ) {
	if ( (int) $r->id === (int) $row->resource_id ) {
		$res_name = esc_html( $r->name );
		break;
	}
}
echo $res_name;
?></td>
								<td><?php echo esc_html( $row->date_from ); ?></td>
								<td><?php echo esc_html( $row->date_to ); ?></td>
								<td><?php echo esc_html( $row->time_from ?? __( 'Whole day', 'crane-booking-manager' ) ); ?></td>
								<td><?php echo esc_html( $row->time_to ?? '' ); ?></td>
								<td><?php echo esc_html( $row->reason ?? '' ); ?></td>
								<td>
									<button class="button button-small cbm-delete-block" data-id="<?php echo absint( $row->id ); ?>"><?php esc_html_e( 'Delete', 'crane-booking-manager' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
