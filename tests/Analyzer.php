<?php
/**
 * Tokenizer-based static analysis for the valt-platform plugin.
 *
 * Uses PHP's own lexer rather than regular expressions, so comments, doc blocks
 * and SQL string literals cannot masquerade as code. (A naive regex reports the
 * table name in "CREATE TABLE {$wpdb->prefix}valt_points_ledger (" as a call to
 * an undefined valt_points_ledger() function.)
 */

declare( strict_types=1 );

namespace Valt\Tests;

final class Analyzer {

	/** @var string[] Absolute paths making up the build under test. */
	private array $roots;

	/** @var array<string,array{tokens:array,src:string}> */
	private array $cache = [];

	/**
	 * @param string|string[] $roots One or more directories. Pass both the plugin
	 *                               and the theme to analyse the shipped pair, which
	 *                               is the unit WordPress actually runs.
	 */
	public function __construct( $roots ) {
		$this->roots = array_map(
			static fn( string $r ): string => rtrim( str_replace( '\\', '/', $r ), '/' ),
			(array) $roots
		);
	}

	public function root(): string {
		return $this->roots[0];
	}

	/** @return string[] Every PHP file in the build. */
	public function files(): array {
		$out = [];
		foreach ( $this->roots as $root ) {
			if ( ! is_dir( $root ) ) {
				continue;
			}
			$it = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS )
			);
			foreach ( $it as $f ) {
				if ( $f->isFile() && strtolower( $f->getExtension() ) === 'php' ) {
					$out[] = str_replace( '\\', '/', $f->getPathname() );
				}
			}
		}
		sort( $out );
		return $out;
	}

	public function rel( string $abs ): string {
		$abs = str_replace( '\\', '/', $abs );
		foreach ( $this->roots as $root ) {
			if ( strpos( $abs, $root ) === 0 ) {
				return basename( $root ) . '/' . ltrim( substr( $abs, strlen( $root ) ), '/' );
			}
		}
		return $abs;
	}

	private function load( string $file ): array {
		if ( ! isset( $this->cache[ $file ] ) ) {
			$src = (string) file_get_contents( $file );
			$this->cache[ $file ] = [ 'src' => $src, 'tokens' => token_get_all( $src ) ];
		}
		return $this->cache[ $file ];
	}

	/**
	 * Code tokens only — comments, doc blocks, inline HTML and whitespace removed.
	 *
	 * @return array<int,array{0:int,1:string,2:int}>
	 */
	public function codeTokens( string $file ): array {
		$skip = [ T_COMMENT, T_DOC_COMMENT, T_WHITESPACE, T_INLINE_HTML ];
		$out  = [];
		foreach ( $this->load( $file )['tokens'] as $t ) {
			if ( is_array( $t ) ) {
				if ( in_array( $t[0], $skip, true ) ) {
					continue;
				}
				$out[] = [ $t[0], $t[1], $t[2] ];
			} else {
				$out[] = [ -1, $t, 0 ];
			}
		}
		return $out;
	}

	/**
	 * Every function this build defines, as name => [file, line].
	 *
	 * @return array<string,array{0:string,1:int}>
	 */
	public function definedFunctions(): array {
		$defs = [];
		foreach ( $this->files() as $file ) {
			$tk = $this->codeTokens( $file );
			foreach ( $tk as $i => $t ) {
				if ( $t[0] !== T_FUNCTION ) {
					continue;
				}
				$next = $tk[ $i + 1 ] ?? null;
				if ( $next && $next[0] === T_STRING ) {
					// Skip methods: a preceding visibility/static keyword means class context.
					$prev = $tk[ $i - 1 ][0] ?? -1;
					if ( in_array( $prev, [ T_PUBLIC, T_PRIVATE, T_PROTECTED, T_STATIC, T_ABSTRACT, T_FINAL ], true ) ) {
						continue;
					}
					$defs[ $next[1] ] = [ $this->rel( $file ), $next[2] ];
				}
			}
		}
		return $defs;
	}

	/**
	 * Every call to a valt_*() function, with whether it is function_exists-guarded.
	 *
	 * A call counts as guarded when the same function name appears inside a
	 * function_exists() check earlier in the enclosing file. That is deliberately
	 * generous: it accepts both the inline ternary form and an enclosing if block,
	 * which are the two idioms this codebase actually uses.
	 *
	 * @return array<int,array{name:string,file:string,line:int,guarded:bool}>
	 */
	public function valtCalls(): array {
		$calls = [];
		foreach ( $this->files() as $file ) {
			$tk      = $this->codeTokens( $file );
			$guarded = [];

			// Pass 1: collect names appearing inside function_exists( '...' ).
			foreach ( $tk as $i => $t ) {
				if ( $t[0] === T_STRING && $t[1] === 'function_exists' ) {
					$arg = $tk[ $i + 2 ] ?? null;
					if ( $arg && $arg[0] === T_CONSTANT_ENCAPSED_STRING ) {
						$guarded[ trim( $arg[1], "'\"" ) ] = true;
					}
				}
			}

			// Pass 2: find valt_*( call sites.
			foreach ( $tk as $i => $t ) {
				if ( $t[0] !== T_STRING || strpos( $t[1], 'valt_' ) !== 0 ) {
					continue;
				}
				$next = $tk[ $i + 1 ] ?? null;
				if ( ! $next || $next[1] !== '(' ) {
					continue; // not a call
				}
				$prev = $tk[ $i - 1 ] ?? null;
				// Skip declarations, method calls, and static access.
				if ( $prev && in_array( $prev[0], [ T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW ], true ) ) {
					continue;
				}
				// Skip 'valt_x' inside function_exists('valt_x') itself.
				if ( $prev && $prev[1] === '(' && ( $tk[ $i - 2 ][1] ?? '' ) === 'function_exists' ) {
					continue;
				}
				$calls[] = [
					'name'    => $t[1],
					'file'    => $this->rel( $file ),
					'line'    => $t[2],
					'guarded' => isset( $guarded[ $t[1] ] ),
				];
			}
		}
		return $calls;
	}

	/**
	 * Files pulled in by an unconditional `require`/`include` at plugin top level.
	 *
	 * @return array<int,array{path:string,line:int}>
	 */
	public function unconditionalRequires( string $entry ): array {
		$file = $this->root() . '/' . $entry;
		$tk   = $this->codeTokens( $file );
		$out  = [];
		$depth = 0;
		foreach ( $tk as $i => $t ) {
			if ( $t[1] === '{' ) { $depth++; }
			if ( $t[1] === '}' ) { $depth--; }
			if ( ! in_array( $t[0], [ T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE ], true ) ) {
				continue;
			}
			if ( $depth > 0 ) {
				continue; // inside a conditional/foreach block
			}
			// Reconstruct the literal string parts of the require expression.
			$path = '';
			for ( $j = $i + 1; $j < count( $tk ); $j++ ) {
				if ( $tk[ $j ][1] === ';' ) {
					break;
				}
				if ( $tk[ $j ][0] === T_CONSTANT_ENCAPSED_STRING ) {
					$path .= trim( $tk[ $j ][1], "'\"" );
				}
			}
			if ( $path !== '' ) {
				$out[] = [ 'path' => $path, 'line' => $t[2] ];
			}
		}
		return $out;
	}

	/** Plugin header Version: value. */
	public function headerVersion( string $entry ): ?string {
		$src = (string) file_get_contents( $this->root() . '/' . $entry );
		return preg_match( '/^\s*\*\s*Version:\s*(.+)$/m', $src, $m ) ? trim( $m[1] ) : null;
	}

	/** VALT_PLATFORM_VERSION define() value. */
	public function constantVersion( string $entry ): ?string {
		$src = (string) file_get_contents( $this->root() . '/' . $entry );
		return preg_match( "/define\(\s*'VALT_PLATFORM_VERSION'\s*,\s*'([^']+)'/", $src, $m ) ? $m[1] : null;
	}
}
