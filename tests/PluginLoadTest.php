<?php
/**
 * The plugin must survive being loaded by WordPress.
 *
 * Regression cover for the M3 finding that the published open-source build
 * could not be activated at all: valt-platform.php unconditionally required
 * includes/admin-docs.php and seed-data.php, neither of which ships in the
 * curated public build, so WordPress fatalled the moment it loaded the plugin.
 */

declare( strict_types=1 );

namespace Valt\Tests;

use PHPUnit\Framework\TestCase;

final class PluginLoadTest extends TestCase {

	private const PLUGIN = 'code/valt-platform';

	private static function repoRoot(): string {
		return dirname( __DIR__ );
	}

	/** Load the given build in a clean subprocess; return the parsed verdict. */
	private static function load( string $dir ): array {
		$php    = PHP_BINARY;
		$script = self::repoRoot() . '/tests/load-plugin.php';
		$cmd    = escapeshellarg( $php ) . ' ' . escapeshellarg( $script ) . ' ' . escapeshellarg( $dir ) . ' 2>&1';
		exec( $cmd, $lines, $code );

		$out = [];
		foreach ( $lines as $line ) {
			if ( strpos( $line, '=' ) !== false ) {
				[ $k, $v ] = explode( '=', $line, 2 );
				$out[ $k ] = $v;
			}
		}
		$out['__exit'] = $code;
		$out['__raw']  = implode( "\n", $lines );
		return $out;
	}

	public function test_plugin_loads_without_fatal(): void {
		$r = self::load( self::repoRoot() . '/' . self::PLUGIN );

		$this->assertSame(
			'LOADED',
			$r['VERDICT'] ?? null,
			"The plugin must load without a fatal error.\n" . ( $r['__raw'] ?? '' )
		);
		$this->assertSame( 0, $r['__exit'] );
	}

	public function test_every_unconditional_require_target_exists(): void {
		$a       = new Analyzer( self::repoRoot() . '/' . self::PLUGIN );
		$missing = [];

		foreach ( $a->unconditionalRequires( 'valt-platform.php' ) as $req ) {
			// Paths are built as VALT_PLATFORM_PATH . 'includes/foo.php'.
			$target = $a->root() . '/' . ltrim( $req['path'], '/' );
			if ( ! file_exists( $target ) ) {
				$missing[] = "valt-platform.php:{$req['line']} requires missing {$req['path']}";
			}
		}

		$this->assertSame(
			[],
			$missing,
			"An unconditional require of a file absent from this build is an activation fatal.\n"
			. "Optional modules must go through the file_exists() guard instead.\n"
			. implode( "\n", $missing )
		);
	}

	public function test_plugin_registers_its_shortcodes(): void {
		$r = self::load( self::repoRoot() . '/' . self::PLUGIN );
		$this->assertSame( 'LOADED', $r['VERDICT'] ?? null );

		$shortcodes = array_filter( explode( ',', $r['SHORTCODES'] ?? '' ) );

		// The token-gating and minting surfaces are the milestone-graded ones.
		foreach ( [ 'valt_gated_content', 'valt_mint_button', 'valt_song_grid', 'valt_artist_valt' ] as $expected ) {
			$this->assertContains( $expected, $shortcodes, "Missing shortcode [{$expected}]" );
		}
	}
}
