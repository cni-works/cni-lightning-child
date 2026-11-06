<?php
/**
 * Regression tests for the compact account item in post editing screens.
 *
 * Run with: php tests/admin-toolbar-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );

$lightning_child_toolbar_actions = array();
$lightning_child_toolbar_styles  = array();

function add_action( $hook, $callback, $priority = 10 ) {
	global $lightning_child_toolbar_actions;
	$lightning_child_toolbar_actions[] = array( $hook, $callback, $priority );
}

function get_stylesheet_directory() {
	return dirname( __DIR__ );
}

function get_stylesheet_directory_uri() {
	return 'https://example.test/wp-content/themes/cni-lightning-child';
}

function wp_enqueue_style( $handle, $source, $dependencies, $version ) {
	global $lightning_child_toolbar_styles;
	$lightning_child_toolbar_styles[ $handle ] = array( $source, $dependencies, $version );
}

function lightning_child_toolbar_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

require dirname( __DIR__ ) . '/inc/admin-toolbar.php';

lightning_child_toolbar_assert(
	in_array( array( 'admin_enqueue_scripts', 'lightning_child_enqueue_editor_toolbar_style', 10 ), $lightning_child_toolbar_actions, true ),
	'The editor toolbar stylesheet callback must be registered.'
);

lightning_child_enqueue_editor_toolbar_style( 'edit.php' );
lightning_child_toolbar_assert( empty( $lightning_child_toolbar_styles ), 'List and other administration screens must remain unchanged.' );

lightning_child_enqueue_editor_toolbar_style( 'post.php' );
lightning_child_toolbar_assert( isset( $lightning_child_toolbar_styles['lightning-child-admin-toolbar'] ), 'Existing post editing screens must load the compact toolbar style.' );

$style = file_get_contents( dirname( __DIR__ ) . '/assets/css/admin-toolbar.css' );
lightning_child_toolbar_assert( false !== strpos( $style, '.block-editor-page' ), 'The style must be limited to block editor screens.' );
lightning_child_toolbar_assert( false !== strpos( $style, 'font-size: 0 !important;' ), 'The visible greeting text must collapse.' );
lightning_child_toolbar_assert( false !== strpos( $style, '.avatar' ), 'The account avatar must remain available.' );

echo "Admin toolbar tests passed.\n";
