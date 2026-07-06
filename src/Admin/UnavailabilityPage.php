<?php
namespace CBM\Admin;

defined( 'ABSPATH' ) || exit;

use CBM\Repository\UnavailabilityRepository;
use CBM\Repository\ResourceRepository;

final class UnavailabilityPage {

	public array $records = [];
	public array $resources = [];

	public function __construct() {
		$this->records   = ( new UnavailabilityRepository() )->list();
		$this->resources = ( new ResourceRepository() )->list( true );
	}
}
