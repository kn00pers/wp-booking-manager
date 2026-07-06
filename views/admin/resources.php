<?php
defined( 'ABSPATH' ) || exit;

use CBM\Admin\SettingsPage;

$editing = $page->editing;
?>
<div class="wrap">
	<h1><?php echo esc_html( SettingsPage::label_plural() ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $page->message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $page->message ); ?></p></div>
	<?php endif; ?>

	<div class="cbm-split">
		<div class="cbm-split__form">
			<h2><?php echo esc_html( $editing ? __( 'Edit resource', 'crane-booking-manager' ) : __( 'Add resource', 'crane-booking-manager' ) ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'cbm_resource_save', 'cbm_resource_nonce' ); ?>
				<?php if ( $editing ) : ?>
					<input type="hidden" name="resource_id" value="<?php echo absint( $editing->id ); ?>">
				<?php endif; ?>
				<table class="form-table">
					<tr>
						<th><label for="cbm-res-name"><?php esc_html_e( 'Name', 'crane-booking-manager' ); ?> *</label></th>
						<td><input type="text" id="cbm-res-name" name="name" value="<?php echo esc_attr( $editing->name ?? '' ); ?>" required class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="cbm-res-desc"><?php esc_html_e( 'Description', 'crane-booking-manager' ); ?></label></th>
						<td><textarea id="cbm-res-desc" name="description" rows="3" class="large-text"><?php echo esc_textarea( $editing->description ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="cbm-res-active"><?php esc_html_e( 'Active', 'crane-booking-manager' ); ?></label></th>
						<td><input type="checkbox" id="cbm-res-active" name="is_active" value="1" <?php checked( (int) ( $editing->is_active ?? 1 ), 1 ); ?>></td>
					</tr>
				</table>
				<?php submit_button( $editing ? __( 'Save changes', 'crane-booking-manager' ) : __( 'Add resource', 'crane-booking-manager' ) ); ?>
				<?php if ( $editing ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=cbm-resources' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'crane-booking-manager' ); ?></a>
				<?php endif; ?>
			</form>
		</div>

		<div class="cbm-split__list">
			<h2><?php esc_html_e( 'Resource list', 'crane-booking-manager' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'crane-booking-manager' ); ?></th>
						<th><?php esc_html_e( 'Name', 'crane-booking-manager' ); ?></th>
						<th><?php esc_html_e( 'Description', 'crane-booking-manager' ); ?></th>
						<th><?php esc_html_e( 'Active', 'crane-booking-manager' ); ?></th>
						<th><?php esc_html_e( 'Action', 'crane-booking-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $page->resources as $r ) : ?>
						<tr>
							<td><?php echo absint( $r->id ); ?></td>
							<td><?php echo esc_html( $r->name ); ?></td>
							<td><?php echo esc_html( $r->description ); ?></td>
							<td><?php echo $r->is_active ? esc_html__( 'Yes', 'crane-booking-manager' ) : esc_html__( 'No', 'crane-booking-manager' ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=cbm-resources&edit_id=' . absint( $r->id ) ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'crane-booking-manager' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
