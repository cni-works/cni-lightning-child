<?php
/**
 * Lightning Child functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reload the child stylesheet with its own cache version.
 *
 * Lightning registers get_stylesheet_uri() with the parent theme version. Use
 * the same public handle so existing inline styles remain attached, while the
 * Customizer preview and frontend both receive the current child stylesheet.
 *
 * @return void
 */
function lightning_child_enqueue_stylesheet() {
	$stylesheet_path = get_stylesheet_directory() . '/style.css';
	$version         = file_exists( $stylesheet_path ) ? filemtime( $stylesheet_path ) : null;
	$style_data      = array();
	$inline_styles   = array();

	global $wp_styles;
	if ( $wp_styles instanceof WP_Styles && isset( $wp_styles->registered['lightning-theme-style'] ) ) {
		$registered_style = $wp_styles->registered['lightning-theme-style'];
		$style_data       = is_array( $registered_style->extra ) ? $registered_style->extra : array();
		$inline_styles    = isset( $style_data['after'] ) && is_array( $style_data['after'] )
			? $style_data['after']
			: array();
		unset( $style_data['after'] );
	}

	wp_dequeue_style( 'lightning-theme-style' );
	wp_deregister_style( 'lightning-theme-style' );
	$dependencies = wp_style_is( 'lightning-common-style', 'registered' )
		? array( 'lightning-common-style' )
		: array();

	wp_enqueue_style(
		'lightning-theme-style',
		get_stylesheet_uri(),
		$dependencies,
		$version
	);

	foreach ( $style_data as $key => $value ) {
		wp_style_add_data( 'lightning-theme-style', $key, $value );
	}

	foreach ( $inline_styles as $inline_style ) {
		if ( is_string( $inline_style ) && '' !== trim( $inline_style ) ) {
			wp_add_inline_style( 'lightning-theme-style', $inline_style );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'lightning_child_enqueue_stylesheet', 15 );


/**
 * Determine whether an update-sensitive feature may run.
 *
 * Define LIGHTNING_CHILD_SAFE_MODE as true in wp-config.php to stop all
 * update-sensitive integrations. Individual features can also be stopped with
 * LIGHTNING_CHILD_DISABLE_HEADER_ENHANCEMENTS,
 * LIGHTNING_CHILD_DISABLE_MOBILE_FIXED_NAV,
 * LIGHTNING_CHILD_DISABLE_BLOCK_TEMPLATE_PARTS, or
 * LIGHTNING_CHILD_DISABLE_ARCHIVE_LAYOUT, or
 * LIGHTNING_CHILD_DISABLE_PAGE_HEADER.
 *
 * @param string $feature Feature identifier.
 * @return bool
 */
function lightning_child_is_feature_enabled( $feature ) {
	$feature_constants = array(
		'header_enhancements'  => 'LIGHTNING_CHILD_DISABLE_HEADER_ENHANCEMENTS',
		'mobile_fixed_nav'     => 'LIGHTNING_CHILD_DISABLE_MOBILE_FIXED_NAV',
		'block_template_parts' => 'LIGHTNING_CHILD_DISABLE_BLOCK_TEMPLATE_PARTS',
		'archive_layout'       => 'LIGHTNING_CHILD_DISABLE_ARCHIVE_LAYOUT',
		'page_header'          => 'LIGHTNING_CHILD_DISABLE_PAGE_HEADER',
	);

	if ( defined( 'LIGHTNING_CHILD_SAFE_MODE' ) && LIGHTNING_CHILD_SAFE_MODE ) {
		return false;
	}

	if ( isset( $feature_constants[ $feature ] ) ) {
		$constant_name = $feature_constants[ $feature ];
		if ( defined( $constant_name ) && constant( $constant_name ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Registers the GitHub Release updater for this child theme.
 *
 * GitHub Releases are the distribution source. WordPress installs an update
 * only through its own standard theme-update process.
 *
 * @return void
 */
function lightning_child_register_github_updater() {
	$updater_file = get_stylesheet_directory() . '/inc/updater/class-github-release-updater.php';

	if ( ! is_readable( $updater_file ) ) {
		return;
	}

	require_once $updater_file;

	$theme = wp_get_theme();
	if ( ! $theme->exists() ) {
		return;
	}

	new \CniWorks\CniLightningChild\Updater\GitHub_Release_Updater(
		array(
			'type'          => 'theme',
			'owner'         => 'cni-works',
			'repository'    => 'cni-lightning-child',
			'slug'          => 'cni-lightning-child',
			'stylesheet'    => get_stylesheet(),
			'version'       => $theme->get( 'Version' ),
			'update_uri'    => $theme->get( 'UpdateURI' ),
			'requires'      => $theme->get( 'RequiresWP' ),
			'requires_php'  => $theme->get( 'RequiresPHP' ),
			'cache_hours'   => 12,
			'failure_hours' => 1,
			'timeout'       => 5,
		)
	);
}
add_action( 'after_setup_theme', 'lightning_child_register_github_updater', 1 );

if ( is_admin() ) {
	require_once get_stylesheet_directory() . '/inc/admin/class-site-functions-onboarding.php';
}

require_once get_stylesheet_directory() . '/inc/theme-settings-migration.php';

/**
 * Show administrators when emergency safe mode is active.
 *
 * @return void
 */
function lightning_child_safe_mode_admin_notice() {
	if ( ! defined( 'LIGHTNING_CHILD_SAFE_MODE' ) || ! LIGHTNING_CHILD_SAFE_MODE || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'Lightning Child のセーフモードが有効です。更新依存の強い拡張機能を停止し、Lightning標準表示を優先しています。', 'cni-lightning-child' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'lightning_child_safe_mode_admin_notice' );

/**
 * Sanitize Font Awesome class names without accepting arbitrary HTML.
 *
 * Shared by the floating contact and mobile fixed navigation modules.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_font_awesome_classes( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$tokens  = preg_split( '/\s+/', trim( sanitize_text_field( $value ) ) );
	$classes = array();

	foreach ( $tokens as $token ) {
		$token = strtolower( $token );
		if ( preg_match( '/\A(?:fa[a-z]{0,4}|fa-[a-z0-9-]+)\z/', $token ) ) {
			$classes[] = $token;
		}

		if ( 8 <= count( $classes ) ) {
			break;
		}
	}

	return implode( ' ', array_unique( $classes ) );
}

/**
 * Resolve a taxonomy archive to one unambiguous post type.
 *
 * Shared taxonomies do not have a single reliable post type unless the query
 * explicitly limits one. Returning an empty value prevents settings for an
 * unrelated post type from being applied merely because it was registered
 * first.
 *
 * @param WP_Taxonomy|false $taxonomy Taxonomy object.
 * @return string
 */
function lightning_child_get_taxonomy_archive_post_type( $taxonomy ) {
	if ( ! $taxonomy instanceof WP_Taxonomy || empty( $taxonomy->object_type ) ) {
		return '';
	}

	$object_types = array_values( array_filter( array_map( 'sanitize_key', $taxonomy->object_type ) ) );
	$query_types  = get_query_var( 'post_type' );
	$query_types  = is_array( $query_types ) ? $query_types : array( $query_types );
	$query_types  = array_values( array_filter( array_map( 'sanitize_key', $query_types ) ) );
	$matches      = array_values( array_intersect( $object_types, $query_types ) );

	if ( 1 === count( $matches ) ) {
		return $matches[0];
	}

	return 1 === count( $object_types ) ? $object_types[0] : '';
}

require_once get_stylesheet_directory() . '/inc/page-visibility.php';
require_once get_stylesheet_directory() . '/inc/footer-settings.php';
require_once get_stylesheet_directory() . '/inc/post-display-settings.php';
require_once get_stylesheet_directory() . '/inc/single-content-width.php';
require_once get_stylesheet_directory() . '/inc/page-top-settings.php';
require_once get_stylesheet_directory() . '/inc/floating-contact.php';
require_once get_stylesheet_directory() . '/inc/font-settings.php';

if ( lightning_child_is_feature_enabled( 'page_header' ) ) {
	require_once get_stylesheet_directory() . '/inc/page-header-settings.php';
}

if ( lightning_child_is_feature_enabled( 'header_enhancements' ) ) {
	require_once get_stylesheet_directory() . '/inc/header-settings.php';
	require_once get_stylesheet_directory() . '/inc/mega-menu.php';
}

if ( lightning_child_is_feature_enabled( 'mobile_fixed_nav' ) ) {
	require_once get_stylesheet_directory() . '/inc/mobile-fixed-nav.php';
}

if ( lightning_child_is_feature_enabled( 'block_template_parts' ) ) {
	require_once get_stylesheet_directory() . '/inc/block-template-parts.php';
}

if ( lightning_child_is_feature_enabled( 'archive_layout' ) ) {
	require_once get_stylesheet_directory() . '/inc/archive-settings.php';
}
