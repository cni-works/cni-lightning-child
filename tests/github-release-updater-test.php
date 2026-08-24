<?php
/**
 * Lightweight regression test for the updater bootstrap and force-check path.
 *
 * Run with: php tests/github-release-updater-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );

$cni_updater_actions  = array();
$cni_updater_filters  = array();
$cni_deleted_cache    = array();
$cni_current_user_hits = 0;

function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, $args );
}

function add_action( $hook, $callback, $priority = 10 ) {
	global $cni_updater_actions;
	$cni_updater_actions[] = array( $hook, $callback, $priority );
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $cni_updater_filters;
	$cni_updater_filters[] = array( $hook, $callback, $priority, $accepted_args );
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}

function untrailingslashit( $value ) {
	return rtrim( $value, '/\\' );
}

function is_admin() {
	return true;
}

function current_user_can( $capability ) {
	global $cni_current_user_hits;
	++$cni_current_user_hits;
	return true;
}

function sanitize_text_field( $value ) {
	return $value;
}

function wp_unslash( $value ) {
	return $value;
}

function delete_site_transient( $key ) {
	global $cni_deleted_cache;
	$cni_deleted_cache[] = $key;
	return true;
}

require dirname( __DIR__ ) . '/inc/updater/class-github-release-updater.php';

function cni_updater_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: $message\n" );
		exit( 1 );
	}
}

$updater = new \CniWorks\CniLightningChild\Updater\GitHub_Release_Updater(
	array(
		'type'       => 'theme',
		'owner'      => 'cni-works',
		'repository' => 'cni-lightning-child',
		'slug'       => 'cni-lightning-child',
		'stylesheet' => 'cni-lightning-child',
		'version'    => '0.7.5',
		'update_uri' => 'https://github.com/cni-works/cni-lightning-child',
	)
);

cni_updater_test_assert( 0 === $cni_current_user_hits, 'Constructor must not call current_user_can().' );
cni_updater_test_assert(
	isset( $cni_updater_actions[0] ) && 'load-update-core.php' === $cni_updater_actions[0][0] && 5 === $cni_updater_actions[0][2],
	'force-check callback must be registered on load-update-core.php with priority 5.'
);
cni_updater_test_assert(
	isset( $cni_updater_filters[0] ) && 'update_themes_github.com' === $cni_updater_filters[0][0],
	'Theme updater must register the GitHub theme update filter.'
);

$_GET['force-check'] = array( 'invalid' );
$updater->maybe_clear_cache_for_forced_check();
cni_updater_test_assert( empty( $cni_deleted_cache ), 'Non-string force-check input must not clear cache.' );

$_GET['force-check'] = '1';
$updater->maybe_clear_cache_for_forced_check();
$expected_key = 'cniworks_gh_release_' . md5( 'cni-works/cni-lightning-child' );
cni_updater_test_assert( array( $expected_key ) === $cni_deleted_cache, 'Only this repository cache key may be cleared.' );
cni_updater_test_assert( 1 === $cni_current_user_hits, 'Capability must be checked only in the force-check callback.' );

echo "GitHub Release updater test passed.\n";
