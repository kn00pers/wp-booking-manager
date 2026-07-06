<?php
namespace CBM\Frontend;

defined( 'ABSPATH' ) || exit;

use CBM\Repository\ResourceRepository;

final class FormRenderer {

	public function render( int $resource_id = 1 ): string {
		$resources = ( new ResourceRepository() )->list( true );

		$resource_exists = false;
		foreach ( $resources as $r ) {
			if ( (int) $r->id === $resource_id ) {
				$resource_exists = true;
				break;
			}
		}

		if ( ! $resource_exists && ! empty( $resources ) ) {
			$resource_id = (int) reset( $resources )->id;
		}

		ob_start();
		require CBM_PLUGIN_DIR . 'views/frontend/booking-form.php';
		return (string) ob_get_clean();
	}
}
