<?php
/**
 * Plugin Name: Resource Booking Manager
 * Description: Universal booking system for any resource — machines, rooms, vehicles, equipment.
 * Version:     2.0.0
 * Author:      astrodesign.pl
 * Text Domain: crane-booking-manager
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.0
 */

defined( 'ABSPATH' ) || exit;

define( 'CBM_VERSION',    '2.0.0' );
define( 'CBM_DB_VERSION', '1.1.0' );
define( 'CBM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CBM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CBM_PLUGIN_FILE', __FILE__ );

require_once CBM_PLUGIN_DIR . 'autoload.php';

use CBM\Activator;
use CBM\Deactivator;
use CBM\Plugin;

register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Deactivator::class, 'deactivate' ] );

add_action( 'plugins_loaded', static function (): void {
	Plugin::get_instance()->init();
} );
