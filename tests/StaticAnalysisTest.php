<?php
/**
 * Static checks over the published build.
 *
 * Each test here encodes a defect that was actually found and fixed during
 * Milestone 3, so the suite fails if any of them come back.
 */

declare( strict_types=1 );

namespace Valt\Tests;

use PHPUnit\Framework\TestCase;

final class StaticAnalysisTest extends TestCase {

	private const PLUGIN = 'code/valt-platform';
	private const THEME  = 'code/valt-theme';

	/**
	 * Functions that live in modules deliberately excluded from this build.
	 *
	 * Every one belongs to a subsystem whose feature flag defaults to false in
	 * valt_feature_enabled(), so the shortcodes that reach them return early and
	 * the call is never executed. They are listed explicitly rather than skipped
	 * silently: if a flag ever defaults to true, or a new name appears here
	 * without a flag behind it, that is a latent fatal and this list should not
	 * grow to accommodate it.
	 *
	 * @var array<string,string> function => gating feature flag
	 */
	private const OPTIONAL_MODULE_FUNCTIONS = [
		'valt_get_leaderboard'        => 'leaderboard',
		'valt_get_user_level'         => 'gamification',
		'valt_get_user_badges'        => 'gamification',
		'valt_badge_definitions'      => 'gamification',
		'valt_get_user_pledges'       => 'gamification',
		'valt_get_campaign_progress'  => 'campaigns',
		'valt_get_active_campaigns'   => 'campaigns',
	];

	/** The shipped pair — the plugin plus the theme it is deployed with. */
	private function analyzer(): Analyzer {
		$root = dirname( __DIR__ );
		return new Analyzer( [ $root . '/' . self::PLUGIN, $root . '/' . self::THEME ] );
	}

	private function pluginAnalyzer(): Analyzer {
		return new Analyzer( dirname( __DIR__ ) . '/' . self::PLUGIN );
	}

	public function test_all_php_files_are_syntactically_valid(): void {
		$a      = $this->analyzer();
		$errors = [];

		foreach ( $a->files() as $file ) {
			$cmd = escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $file ) . ' 2>&1';
			exec( $cmd, $out, $code );
			if ( $code !== 0 ) {
				$errors[] = $a->rel( $file ) . ': ' . implode( ' ', $out );
			}
			$out = [];
		}

		$this->assertSame( [], $errors, "PHP syntax errors:\n" . implode( "\n", $errors ) );
	}

	/**
	 * Header version and VALT_PLATFORM_VERSION must agree.
	 *
	 * They drifted (2.0.0 vs 2.0.1). WordPress shows the header; the constant
	 * drives asset cache-busting, so a mismatch ships stale CSS/JS to browsers
	 * that trust the header.
	 */
	public function test_plugin_version_is_consistent(): void {
		$a      = $this->pluginAnalyzer();
		$header = $a->headerVersion( 'valt-platform.php' );
		$const  = $a->constantVersion( 'valt-platform.php' );

		$this->assertNotNull( $header, 'Plugin header has no Version: line.' );
		$this->assertNotNull( $const, 'VALT_PLATFORM_VERSION is not defined.' );
		$this->assertSame(
			$header,
			$const,
			"Plugin header Version ({$header}) and VALT_PLATFORM_VERSION ({$const}) must match."
		);
	}

	/**
	 * No unguarded call to a function this build does not define.
	 *
	 * The public build deliberately omits stripe/gamification/leaderboard/
	 * campaigns. Calls into an omitted module are only safe when guarded by
	 * function_exists() or gated behind valt_feature_enabled(). An unguarded
	 * call is a latent fatal — this is what broke valt_resolve_artist_id().
	 */
	public function test_no_unguarded_calls_to_undefined_functions(): void {
		$a       = $this->analyzer();
		$defined = $a->definedFunctions();
		$bad     = [];

		foreach ( $a->valtCalls() as $call ) {
			if ( isset( $defined[ $call['name'] ] ) || $call['guarded'] ) {
				continue;
			}
			if ( isset( self::OPTIONAL_MODULE_FUNCTIONS[ $call['name'] ] ) ) {
				continue; // covered by test_optional_module_flags_default_to_disabled
			}
			$bad[] = "{$call['file']}:{$call['line']} calls {$call['name']}() — not defined in this build and not function_exists-guarded";
		}

		$this->assertSame(
			[],
			$bad,
			"Unguarded calls to functions absent from this build:\n" . implode( "\n", $bad )
		);
	}

	/**
	 * The allowlist above is only safe while those flags default to false.
	 *
	 * If a default flips to true, the excluded module's functions become
	 * reachable and every allowlisted call turns into a fatal. Assert the
	 * defaults rather than trusting the comment.
	 */
	public function test_optional_module_flags_default_to_disabled(): void {
		$src = (string) file_get_contents(
			dirname( __DIR__ ) . '/' . self::PLUGIN . '/includes/helpers.php'
		);

		$this->assertStringContainsString(
			'function valt_feature_enabled',
			$src,
			'valt_feature_enabled() is the gate the allowlist depends on.'
		);

		foreach ( array_unique( array_values( self::OPTIONAL_MODULE_FUNCTIONS ) ) as $flag ) {
			$this->assertMatchesRegularExpression(
				"/'{$flag}'\s*=>\s*false/",
				$src,
				"Feature '{$flag}' must default to false: its module is not shipped in this build, "
				. 'so enabling it by default would make the guarded call sites fatal.'
			);
		}
	}

	/**
	 * Documents a known coupling: valt-platform calls presentation helpers that
	 * valt-theme defines (valt_svg_*). code/README.md describes the two as
	 * "intentionally decoupled", which is not currently true in this direction.
	 *
	 * Pinned rather than failed: the pair always ships together, so it is not a
	 * live defect. The test exists so the coupling cannot silently widen.
	 */
	public function test_plugin_to_theme_coupling_is_limited_to_known_helpers(): void {
		$plugin      = $this->pluginAnalyzer();
		$pluginFuncs = $plugin->definedFunctions();
		$pair        = $this->analyzer()->definedFunctions();

		$crossCalls = [];
		foreach ( $plugin->valtCalls() as $call ) {
			$name = $call['name'];
			if ( isset( $pluginFuncs[ $name ] ) || ! isset( $pair[ $name ] ) ) {
				continue; // defined in the plugin, or absent from both (covered elsewhere)
			}
			$crossCalls[ $name ] = true;
		}

		$unexpected = array_values( array_filter(
			array_keys( $crossCalls ),
			static fn( string $n ): bool => strpos( $n, 'valt_svg_' ) !== 0
		) );

		$this->assertSame(
			[],
			$unexpected,
			"valt-platform may only depend on valt-theme for valt_svg_* icon helpers.\n"
			. "New cross-component calls widen a coupling the README says does not exist:\n"
			. implode( "\n", $unexpected )
		);
	}

	/** Every shortcode callback must be callable, not a bare string typo. */
	public function test_no_duplicate_function_definitions(): void {
		$a    = $this->analyzer();
		$seen = [];
		$dupes = [];

		foreach ( $a->files() as $file ) {
			foreach ( $a->codeTokens( $file ) as $i => $t ) {
				if ( $t[0] !== T_FUNCTION ) {
					continue;
				}
				$tk   = $a->codeTokens( $file );
				$next = $tk[ $i + 1 ] ?? null;
				if ( ! $next || $next[0] !== T_STRING ) {
					continue;
				}
				$prev = $tk[ $i - 1 ][0] ?? -1;
				if ( in_array( $prev, [ T_PUBLIC, T_PRIVATE, T_PROTECTED, T_STATIC, T_ABSTRACT, T_FINAL ], true ) ) {
					continue;
				}
				$name = $next[1];
				if ( isset( $seen[ $name ] ) ) {
					$dupes[] = "{$name}() defined in {$seen[$name]} and again in " . $a->rel( $file );
				}
				$seen[ $name ] = $a->rel( $file );
			}
		}

		$this->assertSame( [], $dupes, "Duplicate function definitions (fatal on load):\n" . implode( "\n", $dupes ) );
	}
}
