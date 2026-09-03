<?php
/**
 * Minimal WordPress API stubs — enough surface to load valt-platform.php and
 * everything it requires, without a WordPress install.
 *
 * Scope: require-time only. These stubs let us prove the plugin *loads* (no
 * missing files, no undefined functions at include time, no parse errors).
 * They are not a WordPress emulator and do not attempt behavioural fidelity.
 */

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['__valt_hooks']      = [];
$GLOBALS['__valt_shortcodes'] = [];
$GLOBALS['__valt_rest']       = [];

function add_action( $h, $cb = null, $p = 10, $a = 1 ) { $GLOBALS['__valt_hooks'][ $h ][] = $cb; }
function add_filter( $h, $cb = null, $p = 10, $a = 1 ) { $GLOBALS['__valt_hooks'][ $h ][] = $cb; }
function do_action( $h ) {}
function apply_filters( $h, $v = null ) { return $v; }
function add_shortcode( $t, $cb ) { $GLOBALS['__valt_shortcodes'][ $t ] = $cb; }
function register_activation_hook( $f, $cb ) { $GLOBALS['__valt_hooks']['activate'][] = $cb; }
function register_deactivation_hook( $f, $cb ) {}
function plugin_dir_path( $f ) { return rtrim( str_replace( '\\', '/', dirname( $f ) ), '/' ) . '/'; }
function plugin_dir_url( $f ) { return 'https://example.test/wp-content/plugins/valt-platform/'; }
function admin_url( $p = '' ) { return 'https://example.test/wp-admin/' . $p; }
function rest_url( $p = '' ) { return 'https://example.test/wp-json/' . $p; }
function site_url( $p = '' ) { return 'https://example.test/' . $p; }
function home_url( $p = '' ) { return 'https://example.test/' . $p; }
function wp_create_nonce( $a = -1 ) { return 'nonce'; }
function wp_nonce_url( $u, $a = -1 ) { return $u . '&_wpnonce=nonce'; }
function wp_nonce_field( $a = -1, $n = '_wpnonce', $r = true, $e = true ) {}
function check_admin_referer( $a = -1, $q = '_wpnonce' ) { return true; }
function check_ajax_referer( $a = -1, $q = false, $d = true ) { return true; }
function wp_verify_nonce( $n, $a = -1 ) { return 1; }
function is_user_logged_in() { return false; }
function get_current_user_id() { return 0; }
function current_user_can( $c ) { return false; }
function is_admin() { return false; }
function get_option( $k, $d = false ) { return $d; }
function update_option( $k, $v, $a = null ) { return true; }
function add_option( $k, $v = '' ) { return true; }
function delete_option( $k ) { return true; }
function get_post_meta( $i, $k = '', $s = false ) { return ''; }
function update_post_meta( $i, $k, $v, $p = '' ) { return true; }
function get_post( $p = null, $o = OBJECT ) { return null; }
function get_posts( $a = [] ) { return []; }
function get_the_title( $p = 0 ) { return ''; }
function get_the_ID() { return 0; }
function get_permalink( $p = 0 ) { return 'https://example.test/?p=0'; }
function get_post_thumbnail_id( $p = null ) { return 0; }
function wp_get_attachment_image_url( $i, $s = 'thumbnail' ) { return ''; }
function wp_get_attachment_url( $i ) { return ''; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_url( $s ) { return (string) $s; }
function esc_url_raw( $s ) { return (string) $s; }
function esc_textarea( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html__( $s, $d = null ) { return $s; }
function esc_attr__( $s, $d = null ) { return $s; }
function esc_html_e( $s, $d = null ) { echo $s; }
function __( $s, $d = null ) { return $s; }
function _e( $s, $d = null ) { echo $s; }
function _n( $a, $b, $n, $d = null ) { return $n === 1 ? $a : $b; }
function wp_kses_post( $s ) { return $s; }
function wp_kses( $s, $a = [] ) { return $s; }
function sanitize_text_field( $s ) { return is_string( $s ) ? strip_tags( $s ) : ''; }
function sanitize_textarea_field( $s ) { return is_string( $s ) ? strip_tags( $s ) : ''; }
function sanitize_email( $s ) { return (string) $s; }
function sanitize_key( $s ) { return (string) $s; }
function sanitize_title( $s ) { return (string) $s; }
function absint( $v ) { return abs( (int) $v ); }
function wp_enqueue_style() {}
function wp_enqueue_script() {}
function wp_register_script() {}
function wp_register_style() {}
function wp_localize_script() {}
function wp_enqueue_media() {}
function wp_add_inline_script() {}
function register_rest_route( $ns, $route, $args = [] ) { $GLOBALS['__valt_rest'][] = $ns . $route; }
function register_meta( $t, $k, $a = [] ) { return true; }
function register_post_type( $t, $a = [] ) {}
function register_setting( $g, $o, $a = [] ) {}
function add_settings_section() {}
function add_settings_field() {}
function settings_fields( $g ) {}
function do_settings_sections( $p ) {}
function submit_button( $t = null ) {}
function add_menu_page() { return ''; }
function add_submenu_page() { return ''; }
function add_options_page() { return ''; }
function wp_next_scheduled( $h, $a = [] ) { return false; }
function wp_schedule_event( $t, $r, $h, $a = [] ) { return true; }
function wp_clear_scheduled_hook( $h, $a = [] ) {}
function wp_schedule_single_event( $t, $h, $a = [] ) { return true; }
function wp_die( $m = '' ) { throw new RuntimeException( 'wp_die: ' . ( is_string( $m ) ? $m : '' ) ); }
function wp_send_json_success( $d = null ) {}
function wp_send_json_error( $d = null ) {}
function wp_json_encode( $d, $o = 0 ) { return json_encode( $d, $o ); }
function wp_remote_post( $u, $a = [] ) { return []; }
function wp_remote_get( $u, $a = [] ) { return []; }
function wp_remote_retrieve_body( $r ) { return ''; }
function wp_remote_retrieve_response_code( $r ) { return 200; }
function wp_mail( $to, $s, $m, $h = '', $a = [] ) { return true; }
function wp_upload_dir() { return [ 'basedir' => sys_get_temp_dir(), 'baseurl' => '' ]; }
function wp_parse_args( $a, $d = [] ) { return array_merge( (array) $d, (array) $a ); }
function shortcode_atts( $p, $a, $s = '' ) { return array_merge( (array) $p, (array) $a ); }
function do_shortcode( $c ) { return $c; }
function trailingslashit( $s ) { return rtrim( (string) $s, '/\\' ) . '/'; }
function untrailingslashit( $s ) { return rtrim( (string) $s, '/\\' ); }
function did_action( $h ) { return 0; }
function current_time( $t = 'mysql', $g = 0 ) { return '2026-01-01 00:00:00'; }
function human_time_diff( $f, $t = 0 ) { return '1 min'; }
function number_format_i18n( $n, $d = 0 ) { return number_format( (float) $n, $d ); }
function selected( $a, $b = true, $e = true ) { return ''; }
function checked( $a, $b = true, $e = true ) { return ''; }
function get_users( $a = [] ) { return []; }
function get_userdata( $id ) { return false; }
function is_wp_error( $t ) { return $t instanceof WP_Error; }
function __return_true() { return true; }
function __return_false() { return false; }
function __return_empty_array() { return []; }
function dbDelta( $q, $e = true ) { return []; }
function plugin_basename( $f ) { return basename( dirname( $f ) ) . '/' . basename( $f ); }
function wp_generate_password( $l = 12, $s = true, $x = false ) { return str_repeat( 'x', $l ); }
function wp_unslash( $v ) { return $v; }
function maybe_unserialize( $v ) { return $v; }

defined( 'OBJECT' ) || define( 'OBJECT', 'OBJECT' );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );
defined( 'HOUR_IN_SECONDS' ) || define( 'HOUR_IN_SECONDS', 3600 );
defined( 'MINUTE_IN_SECONDS' ) || define( 'MINUTE_IN_SECONDS', 60 );

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code, $message, $data;
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code = $code; $this->message = $message; $this->data = $data;
		}
		public function get_error_message() { return $this->message; }
		public function get_error_code() { return $this->code; }
	}
}
if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response { public function __construct( $data = null, $status = 200 ) {} }
}
if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		public function get_param( $k ) { return null; }
		public function get_params() { return []; }
		public function get_body() { return ''; }
		public function get_header( $k ) { return ''; }
	}
}
if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		public $posts = [];
		public function __construct( $a = [] ) {}
		public function have_posts() { return false; }
		public function the_post() {}
	}
}
if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post { public $ID = 0; public $post_author = 0; public $post_title = ''; }
}
if ( ! class_exists( 'WP_User' ) ) {
	class WP_User { public $ID = 0; }
}

if ( ! isset( $GLOBALS['wpdb'] ) ) {
	$GLOBALS['wpdb'] = new class {
		public $prefix = 'wp_';
		public $posts = 'wp_posts';
		public $postmeta = 'wp_postmeta';
		public $users = 'wp_users';
		public function get_charset_collate() { return ''; }
		public function prepare( $q, ...$a ) { return $q; }
		public function get_var( $q = null ) { return null; }
		public function get_row( $q = null, $o = OBJECT, $y = 0 ) { return null; }
		public function get_col( $q = null ) { return []; }
		public function get_results( $q = null, $o = OBJECT ) { return []; }
		public function query( $q ) { return 0; }
		public function insert( $t, $d, $f = null ) { return 1; }
		public function update( $t, $d, $w, $f = null, $wf = null ) { return 1; }
		public function delete( $t, $w, $f = null ) { return 1; }
	};
}
