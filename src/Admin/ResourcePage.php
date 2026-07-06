<?php
namespace CBM\Admin;

defined( 'ABSPATH' ) || exit;

use CBM\Repository\ResourceRepository;

final class ResourcePage {

	public array $resources = [];
	public ?object $editing = null;
	public string $message = '';

	public function __construct() {
		$repo = new ResourceRepository();

		if ( isset( $_POST['cbm_resource_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( $_POST['cbm_resource_nonce'] ), 'cbm_resource_save' )
		) {
			if ( ! current_user_can( 'manage_cbm_bookings' ) ) {
				wp_die( esc_html__( 'Insufficient permissions.', 'crane-booking-manager' ) );
			}

			$id   = absint( $_POST['resource_id'] ?? 0 );
			$data = [
				'name'        => sanitize_text_field( $_POST['name']        ?? '' ),
				'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
				'is_active'   => isset( $_POST['is_active'] ) ? 1 : 0,
			];

			if ( $id > 0 ) {
				$repo->update( $id, $data );
				$this->message = __( 'Resource updated.', 'crane-booking-manager' );
			} else {
				$repo->create( $data );
				$this->message = __( 'Resource added.', 'crane-booking-manager' );
			}
		}

		$edit_id = absint( $_GET['edit_id'] ?? 0 );
		if ( $edit_id > 0 ) {
			$this->editing = $repo->find( $edit_id );
		}

		$this->resources = $repo->list();
	}
}
