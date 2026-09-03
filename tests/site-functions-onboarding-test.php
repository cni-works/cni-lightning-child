<?php
/**
 * Regression tests for CNI Site Functions onboarding.
 *
 * Run with: php tests/site-functions-onboarding-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );

class WP_Error {
	private $code;
	private $message;

	public function __construct( $code = '', $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}

	public function get_error_code() {
		return $this->code;
	}

	public function get_error_message() {
		return $this->message;
	}

	public function has_errors() {
		return '' !== $this->code;
	}
}

$cni_onboarding_actions      = array();
$cni_onboarding_capabilities = array();
$cni_onboarding_http_result  = null;
$cni_onboarding_screen       = 'dashboard';
$cni_onboarding_plugins      = array();
$cni_onboarding_active       = array();
$cni_onboarding_styles       = array();
$cni_onboarding_scripts      = array();
$cni_onboarding_localized    = array();

function add_action( $hook, $callback ) {
	global $cni_onboarding_actions;
	$cni_onboarding_actions[] = array( $hook, $callback );
}

function __( $text ) {
	return $text;
}

function esc_html__( $text ) {
	return $text;
}

function esc_html( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
	return $url;
}

function sanitize_text_field( $value ) {
	return is_string( $value ) ? trim( strip_tags( $value ) ) : '';
}

function sanitize_key( $value ) {
	return is_string( $value ) ? strtolower( preg_replace( '/[^a-zA-Z0-9_-]/', '', $value ) ) : '';
}

function wp_unslash( $value ) {
	return $value;
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function wp_safe_remote_get() {
	global $cni_onboarding_http_result;
	return $cni_onboarding_http_result;
}

function home_url() {
	return 'https://example.test/';
}

function wp_remote_retrieve_response_code( $response ) {
	return isset( $response['response']['code'] ) ? $response['response']['code'] : 0;
}

function wp_remote_retrieve_body( $response ) {
	return isset( $response['body'] ) ? $response['body'] : '';
}

function wp_http_validate_url( $url ) {
	return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : false;
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}

function current_user_can( $capability ) {
	global $cni_onboarding_capabilities;
	return ! empty( $cni_onboarding_capabilities[ $capability ] );
}

function get_current_screen() {
	global $cni_onboarding_screen;
	return (object) array( 'id' => $cni_onboarding_screen );
}

function get_transient() {
	return false;
}

function get_current_user_id() {
	return 1;
}

function is_multisite() {
	return false;
}

function get_site_option() {
	return array();
}

function get_option( $name ) {
	global $cni_onboarding_active;
	return 'active_plugins' === $name ? $cni_onboarding_active : array();
}

function get_plugins() {
	global $cni_onboarding_plugins;
	return $cni_onboarding_plugins;
}

function activate_plugin() {
	return null;
}

function is_plugin_active( $plugin ) {
	global $cni_onboarding_active;
	return in_array( $plugin, $cni_onboarding_active, true );
}

function get_stylesheet() {
	return 'cni-lightning-child';
}

function admin_url( $path = '' ) {
	return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
}

function get_stylesheet_directory() {
	return dirname( __DIR__ );
}

function get_stylesheet_directory_uri() {
	return 'https://example.test/wp-content/themes/cni-lightning-child';
}

function wp_enqueue_style( $handle, $source ) {
	global $cni_onboarding_styles;
	$cni_onboarding_styles[ $handle ] = $source;
}

function wp_enqueue_script( $handle, $source ) {
	global $cni_onboarding_scripts;
	$cni_onboarding_scripts[ $handle ] = $source;
}

function wp_localize_script( $handle, $object_name, $data ) {
	global $cni_onboarding_localized;
	$cni_onboarding_localized[ $handle ] = array(
		'object_name' => $object_name,
		'data'        => $data,
	);
}

require dirname( __DIR__ ) . '/inc/admin/class-site-functions-onboarding.php';

use CniWorks\CniLightningChild\Admin\Site_Functions_Onboarding;

function cni_onboarding_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: $message\n" );
		exit( 1 );
	}
}

$canonical = Site_Functions_Onboarding::PLUGIN_FILE;
$legacy    = Site_Functions_Onboarding::LEGACY_PLUGIN_FILE;

cni_onboarding_assert(
	Site_Functions_Onboarding::STATE_MISSING === Site_Functions_Onboarding::determine_state( array(), array() ),
	'1. Missing plugin must be detected.'
);
cni_onboarding_assert(
	Site_Functions_Onboarding::STATE_INACTIVE === Site_Functions_Onboarding::determine_state( array( $canonical => array() ), array() ),
	'2. Installed inactive canonical plugin must be detected.'
);
cni_onboarding_assert(
	Site_Functions_Onboarding::STATE_ACTIVE === Site_Functions_Onboarding::determine_state( array( $canonical => array() ), array( $canonical ) ),
	'3. Active canonical plugin must be detected.'
);
cni_onboarding_assert(
	Site_Functions_Onboarding::STATE_LEGACY_INACTIVE === Site_Functions_Onboarding::determine_state( array( $legacy => array() ), array() ),
	'4. Inactive legacy uppercase plugin must be detected.'
);
cni_onboarding_assert(
	Site_Functions_Onboarding::STATE_LEGACY_ACTIVE === Site_Functions_Onboarding::determine_state( array( $legacy => array() ), array( $legacy ) ),
	'5. Active legacy uppercase plugin must be detected.'
);

$reflection = new ReflectionClass( Site_Functions_Onboarding::class );
$onboarding = $reflection->newInstanceWithoutConstructor();

$cni_onboarding_http_result = new WP_Error( 'network_down', 'Network unavailable.' );
$request_error              = $onboarding->request_release();
cni_onboarding_assert(
	is_wp_error( $request_error ) && 'github_request_failed' === $request_error->get_error_code(),
	'6. GitHub API failure must fail closed.'
);

$temp_file = tempnam( sys_get_temp_dir(), 'cni-onboarding-' );
file_put_contents( $temp_file, 'not-the-release-zip' );
$digest_error = $onboarding->verify_downloaded_asset(
	$temp_file,
	array(
		'digest' => str_repeat( '0', 64 ),
		'size'   => filesize( $temp_file ),
	)
);
unlink( $temp_file );
cni_onboarding_assert(
	is_wp_error( $digest_error ) && 'asset_digest_mismatch' === $digest_error->get_error_code(),
	'7. Digest mismatch must stop installation.'
);

$cni_onboarding_capabilities = array();
ob_start();
$onboarding->render_notice();
$permission_output = ob_get_clean();
cni_onboarding_assert( '' === $permission_output, '8. Users without install/activation capabilities must see no onboarding action.' );

$release_without_digest = $onboarding->validate_release_data(
	array(
		'draft'      => false,
		'prerelease' => false,
		'tag_name'   => 'v1.0.0',
		'assets'     => array(
			array(
				'name'                 => 'cni-site-functions-1.0.0.zip',
				'state'                => 'uploaded',
				'browser_download_url' => 'https://github.com/cni-works/CNI-Site-Functions/releases/download/v1.0.0/cni-site-functions-1.0.0.zip',
				'size'                 => 100,
			),
		),
	)
);
cni_onboarding_assert(
	is_wp_error( $release_without_digest ) && 'missing_asset_digest' === $release_without_digest->get_error_code(),
	'Release assets without a digest must be rejected.'
);

$valid_release = $onboarding->validate_release_data(
	array(
		'draft'      => false,
		'prerelease' => false,
		'tag_name'   => 'v1.0.0',
		'assets'     => array(
			array(
				'name'                 => 'cni-site-functions-1.0.0.zip',
				'state'                => 'uploaded',
				'browser_download_url' => 'https://github.com/cni-works/CNI-Site-Functions/releases/download/v1.0.0/cni-site-functions-1.0.0.zip',
				'digest'              => 'sha256:' . str_repeat( 'a', 64 ),
				'size'                 => 100,
			),
		),
	)
);
cni_onboarding_assert(
	is_array( $valid_release ) && '1.0.0' === $valid_release['version'] && str_repeat( 'a', 64 ) === $valid_release['digest'],
	'A correctly named, digested asset from the exact repository must be accepted.'
);

$cni_onboarding_capabilities = array( 'install_plugins' => true );
$cni_onboarding_screen       = 'options-general';
ob_start();
$onboarding->render_notice();
$unsupported_screen_output = ob_get_clean();
cni_onboarding_assert( '' === $unsupported_screen_output, 'Onboarding must not appear outside Dashboard, Themes, and Plugins screens.' );

$cni_onboarding_screen  = 'dashboard';
$cni_onboarding_plugins = array( $canonical => array() );
$cni_onboarding_active  = array( $canonical );
ob_start();
$onboarding->render_notice();
$active_output = ob_get_clean();
cni_onboarding_assert( '' === $active_output, 'An active canonical plugin must suppress the onboarding notice.' );

$_GET['file']  = 'functions.php';
$_GET['theme'] = 'cni-lightning-child';
$cni_onboarding_screen       = 'theme-editor';
$cni_onboarding_capabilities = array( 'edit_themes' => true );
ob_start();
$onboarding->render_theme_functions_guidance();
$active_guidance = ob_get_clean();
cni_onboarding_assert(
	false !== strpos( $active_guidance, 'options-general.php?page=cni-site-functions' )
		&& false !== strpos( $active_guidance, '子テーマ更新時に失われる可能性' ),
	'Installed and active CNI Site Functions must show the editor guidance and direct settings link.'
);

$cni_onboarding_styles    = array();
$cni_onboarding_scripts   = array();
$cni_onboarding_localized = array();
$onboarding->enqueue_theme_functions_guidance_assets();
$asset_handle = 'cni-lightning-child-site-functions-guidance';
cni_onboarding_assert(
	isset( $cni_onboarding_styles[ $asset_handle ], $cni_onboarding_scripts[ $asset_handle ], $cni_onboarding_localized[ $asset_handle ] )
		&& false !== strpos( $cni_onboarding_localized[ $asset_handle ]['data']['primaryUrl'], 'options-general.php?page=cni-site-functions' ),
	'Active plugin state must enqueue the overlay and point its primary button to CNI Site Functions.'
);

$cni_onboarding_active = array();
ob_start();
$onboarding->render_theme_functions_guidance();
$inactive_guidance = ob_get_clean();
cni_onboarding_assert(
	false !== strpos( $inactive_guidance, 'plugins.php' ) && false !== strpos( $inactive_guidance, '現在は無効' ),
	'Installed but inactive CNI Site Functions must direct the maintainer to the Plugins screen.'
);

$cni_onboarding_localized = array();
$onboarding->enqueue_theme_functions_guidance_assets();
cni_onboarding_assert(
	isset( $cni_onboarding_localized[ $asset_handle ] )
		&& false !== strpos( $cni_onboarding_localized[ $asset_handle ]['data']['primaryUrl'], 'plugins.php' )
		&& false !== strpos( $cni_onboarding_localized[ $asset_handle ]['data']['heading'], '有効化' ),
	'Inactive plugin state must enqueue the overlay with the Plugins screen guidance.'
);

$cni_onboarding_plugins = array();
ob_start();
$onboarding->render_theme_functions_guidance();
$missing_guidance = ob_get_clean();
cni_onboarding_assert( '' === $missing_guidance, 'The editor guidance must not appear when the canonical plugin is not installed.' );

$cni_onboarding_styles    = array();
$cni_onboarding_scripts   = array();
$cni_onboarding_localized = array();
$onboarding->enqueue_theme_functions_guidance_assets();
cni_onboarding_assert(
	empty( $cni_onboarding_styles ) && empty( $cni_onboarding_scripts ) && empty( $cni_onboarding_localized ),
	'Overlay assets must not load when CNI Site Functions is not installed.'
);

$cni_onboarding_plugins = array( $canonical => array() );
$_GET['file']            = 'style.css';
ob_start();
$onboarding->render_theme_functions_guidance();
$other_file_guidance = ob_get_clean();
cni_onboarding_assert( '' === $other_file_guidance, 'The editor guidance must appear only for functions.php.' );

$onboarding->enqueue_theme_functions_guidance_assets();
cni_onboarding_assert(
	empty( $cni_onboarding_styles ) && empty( $cni_onboarding_scripts ) && empty( $cni_onboarding_localized ),
	'Overlay assets must not load for style.css.'
);

$_GET['file']  = 'functions.php';
$_GET['theme'] = 'another-theme';
$onboarding->enqueue_theme_functions_guidance_assets();
cni_onboarding_assert(
	empty( $cni_onboarding_styles ) && empty( $cni_onboarding_scripts ) && empty( $cni_onboarding_localized ),
	'Overlay assets must not load while another theme is selected.'
);

echo "CNI Site Functions onboarding tests passed.\n";
