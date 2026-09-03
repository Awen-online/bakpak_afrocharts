<?php
/**
 * Load a valt-platform build against the WordPress stubs, in a clean process,
 * and print a machine-readable verdict.
 *
 * Run in a subprocess (see PluginLoadTest) so a fatal is observable rather than
 * taking the test runner down with it.
 *
 * Usage: php tests/load-plugin.php <path-to-valt-platform-dir>
 * Exit:  0 = loaded, 1 = fatal, 2 = bad invocation
 */

require __DIR__ . '/wp-stubs.php';

$dir  = isset( $argv[1] ) ? rtrim( str_replace( '\\', '/', $argv[1] ), '/' ) : '';
$main = $dir . '/valt-platform.php';

if ( $dir === '' || ! file_exists( $main ) ) {
	fwrite( STDERR, "load-plugin: no plugin entry at {$main}\n" );
	exit( 2 );
}

register_shutdown_function( static function () {
	$e = error_get_last();
	if ( $e && in_array( $e['type'], [ E_ERROR, E_COMPILE_ERROR, E_CORE_ERROR, E_PARSE ], true ) ) {
		echo "VERDICT=FATAL\n";
		echo 'MESSAGE=' . preg_replace( '/\s+/', ' ', $e['message'] ) . "\n";
		echo "WHERE={$e['file']}:{$e['line']}\n";
		exit( 1 );
	}
} );

require $main;

$shortcodes = array_keys( $GLOBALS['__valt_shortcodes'] );
sort( $shortcodes );

echo "VERDICT=LOADED\n";
echo 'VERSION=' . ( defined( 'VALT_PLATFORM_VERSION' ) ? VALT_PLATFORM_VERSION : '' ) . "\n";
echo 'SHORTCODES=' . implode( ',', $shortcodes ) . "\n";
echo 'HOOKS=' . count( $GLOBALS['__valt_hooks'], COUNT_RECURSIVE ) . "\n";
exit( 0 );
