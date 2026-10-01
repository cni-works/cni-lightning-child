<?php
/**
 * Small usability adjustments for WordPress's logged-in toolbar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the compact account-item style only in post editing screens.
 *
 * @param string $hook_suffix Current administration screen filename.
 * @return void
 */
function lightning_child_enqueue_editor_toolbar_style( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$style_path = get_stylesheet_directory() . '/assets/css/admin-toolbar.css';
	wp_enqueue_style(
		'lightning-child-admin-toolbar',
		get_stylesheet_directory_uri() . '/assets/css/admin-toolbar.css',
		array(),
		file_exists( $style_path ) ? filemtime( $style_path ) : null
	);
}
add_action( 'admin_enqueue_scripts', 'lightning_child_enqueue_editor_toolbar_style' );
