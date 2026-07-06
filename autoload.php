<?php
defined( 'ABSPATH' ) || exit;

spl_autoload_register( static function ( string $class ): void {
	$prefix   = 'CBM\\';
	$base_dir = CBM_PLUGIN_DIR . 'src/';

	if ( 0 !== strncmp( $prefix, $class, strlen( $prefix ) ) ) {
		return;
	}

	$relative_class = substr( $class, strlen( $prefix ) );
	$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );
