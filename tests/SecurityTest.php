<?php
/**
 * Security invariants for the published build.
 *
 * These encode the controls the Milestone 3 manual security assessment relied
 * on, so that a future change cannot quietly remove one. They are deliberately
 * structural (every route has a permission_callback) rather than a claim that
 * the code is free of vulnerabilities.
 */

declare( strict_types=1 );

namespace Valt\Tests;

use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase {

	private const PLUGIN = 'code/valt-platform';

	private function analyzer(): Analyzer {
		return new Analyzer( dirname( __DIR__ ) . '/' . self::PLUGIN );
	}

	private function source( string $file ): string {
		return (string) file_get_contents( $file );
	}

	/** Every REST route must declare an explicit permission_callback. */
	public function test_every_rest_route_declares_a_permission_callback(): void {
		$a       = $this->analyzer();
		$missing = [];

		foreach ( $a->files() as $file ) {
			$src = $this->source( $file );
			if ( strpos( $src, 'register_rest_route' ) === false ) {
				continue;
			}
			$routes      = preg_match_all( '/register_rest_route\s*\(/', $src );
			$permissions = preg_match_all( '/[\'"]permission_callback[\'"]\s*=>/', $src );
			if ( $routes > $permissions ) {
				$missing[] = $a->rel( $file ) . ": {$routes} route(s) but only {$permissions} permission_callback(s)";
			}
		}

		$this->assertSame(
			[],
			$missing,
			"A REST route without permission_callback defaults to public in WordPress:\n" . implode( "\n", $missing )
		);
	}

	/** No unauthenticated AJAX surface. */
	public function test_no_nopriv_ajax_handlers(): void {
		$a     = $this->analyzer();
		$found = [];

		foreach ( $a->files() as $file ) {
			if ( preg_match_all( '/wp_ajax_nopriv_([a-z0-9_]+)/i', $this->source( $file ), $m ) ) {
				foreach ( $m[1] as $action ) {
					$found[] = $a->rel( $file ) . ": wp_ajax_nopriv_{$action}";
				}
			}
		}

		$this->assertSame(
			[],
			$found,
			"Valt exposes no unauthenticated AJAX. Adding one needs its own review:\n" . implode( "\n", $found )
		);
	}

	/** Every authenticated AJAX handler verifies a nonce. */
	public function test_every_ajax_handler_checks_a_nonce(): void {
		$a       = $this->analyzer();
		$missing = [];

		foreach ( $a->files() as $file ) {
			$src = $this->source( $file );
			$handlers = preg_match_all( '/add_action\s*\(\s*[\'"]wp_ajax_[a-z0-9_]+[\'"]/i', $src );
			if ( ! $handlers ) {
				continue;
			}
			$checks = preg_match_all( '/check_ajax_referer|wp_verify_nonce/', $src );
			if ( $checks < $handlers ) {
				$missing[] = $a->rel( $file ) . ": {$handlers} wp_ajax_ handler(s) but only {$checks} nonce check(s)";
			}
		}

		$this->assertSame( [], $missing, "AJAX handlers missing nonce verification:\n" . implode( "\n", $missing ) );
	}

	/**
	 * State-changing admin pages must verify a nonce, not just a capability.
	 *
	 * A capability check alone stops the wrong user, not a cross-site request
	 * that rides the right user's session. The seeders had this gap.
	 */
	public function test_admin_pages_that_write_verify_a_nonce(): void {
		$a       = $this->analyzer();
		$missing = [];

		foreach ( $a->files() as $file ) {
			$src = $this->source( $file );
			// Pages that act on a GET confirm flag.
			if ( ! preg_match( '/\$_GET\[\s*[\'"]confirm[\'"]\s*\]/', $src ) ) {
				continue;
			}
			if ( strpos( $src, 'check_admin_referer' ) === false && strpos( $src, 'wp_verify_nonce' ) === false ) {
				$missing[] = $a->rel( $file ) . ': acts on ?confirm= without a nonce check (CSRF)';
			}
		}

		$this->assertSame( [], $missing, "Destructive admin actions without CSRF protection:\n" . implode( "\n", $missing ) );
	}

	/** No dangerous language constructs. */
	public function test_no_dangerous_functions(): void {
		$a     = $this->analyzer();
		$found = [];
		$banned = [ 'eval', 'create_function', 'shell_exec', 'passthru', 'proc_open', 'popen', 'unserialize', 'extract', 'assert' ];

		foreach ( $a->files() as $file ) {
			$tk = $a->codeTokens( $file );
			foreach ( $tk as $i => $t ) {
				if ( $t[0] === T_EVAL ) {
					$found[] = $a->rel( $file ) . ':' . $t[2] . ' uses eval';
					continue;
				}
				if ( $t[0] !== T_STRING || ! in_array( strtolower( $t[1] ), $banned, true ) ) {
					continue;
				}
				$next = $tk[ $i + 1 ] ?? null;
				$prev = $tk[ $i - 1 ] ?? null;
				if ( ! $next || $next[1] !== '(' ) {
					continue;
				}
				if ( $prev && in_array( $prev[0], [ T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON ], true ) ) {
					continue;
				}
				$found[] = $a->rel( $file ) . ':' . $t[2] . ' uses ' . $t[1];
			}
		}

		$this->assertSame( [], $found, "Dangerous constructs:\n" . implode( "\n", $found ) );
	}

	/** No credential may be committed to the repository. */
	public function test_no_hardcoded_secrets(): void {
		$a     = $this->analyzer();
		$found = [];

		$patterns = [
			'Stripe secret key'   => '/\bsk_(live|test)_[A-Za-z0-9]{16,}/',
			'Stripe webhook secret' => '/\bwhsec_[A-Za-z0-9]{16,}/',
			'Blockfrost project id' => '/\b(mainnet|preprod|preview)[A-Za-z0-9]{28,}/',
			'JWT'                 => '/\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\./',
			'Assigned long secret' => '/(api_key|apikey|secret|password|token)\s*=\s*[\'"][A-Za-z0-9_\-]{24,}[\'"]/i',
		];

		foreach ( $a->files() as $file ) {
			$src = $this->source( $file );
			foreach ( $patterns as $label => $re ) {
				if ( preg_match( $re, $src ) ) {
					$found[] = $a->rel( $file ) . ": possible {$label}";
				}
			}
		}

		$this->assertSame(
			[],
			$found,
			"Credentials must resolve at runtime from wp-config constants or WP options, never source:\n" . implode( "\n", $found )
		);
	}
}
