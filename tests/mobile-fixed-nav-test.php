<?php
/**
 * Lightweight regression tests for mobile fixed navigation styles.
 *
 * Run with: php tests/mobile-fixed-nav-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );

$cni_mobile_nav_theme_mods = array();

function add_action( $hook, $callback, $priority = 10 ) {
}

function add_filter( $hook, $callback, $priority = 10 ) {
}

function get_theme_mod( $name, $default = false ) {
	global $cni_mobile_nav_theme_mods;
	return array_key_exists( $name, $cni_mobile_nav_theme_mods ) ? $cni_mobile_nav_theme_mods[ $name ] : $default;
}

require dirname( __DIR__ ) . '/inc/mobile-fixed-nav.php';

function cni_mobile_nav_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: $message\n" );
		exit( 1 );
	}
}

cni_mobile_nav_assert(
	'flat' === lightning_child_sanitize_mobile_fixed_nav_style( 'flat' ),
	'The flat style must remain valid.'
);
cni_mobile_nav_assert(
	'floating' === lightning_child_sanitize_mobile_fixed_nav_style( 'floating' ),
	'The floating style must be accepted.'
);
cni_mobile_nav_assert(
	'flat' === lightning_child_sanitize_mobile_fixed_nav_style( 'unknown' ),
	'Unknown styles must safely fall back to flat.'
);
cni_mobile_nav_assert(
	'flat' === lightning_child_sanitize_mobile_fixed_nav_style( array( 'floating' ) ),
	'Non-string styles must safely fall back to flat.'
);
cni_mobile_nav_assert(
	8 === lightning_child_sanitize_mobile_fixed_nav_floating_font_size( 6 )
	&& 10 === lightning_child_sanitize_mobile_fixed_nav_floating_font_size( 10 )
	&& 12 === lightning_child_sanitize_mobile_fixed_nav_floating_font_size( 14 ),
	'The floating label font size must remain within the supported 8 to 12 pixel range.'
);
cni_mobile_nav_assert(
	'flat' === lightning_child_get_mobile_fixed_nav_style(),
	'Existing sites without a saved style must remain flat.'
);

$cni_mobile_nav_theme_mods['lightning_child_mobile_fixed_nav_style'] = 'floating';
cni_mobile_nav_assert(
	'floating' === lightning_child_get_mobile_fixed_nav_style(),
	'The saved floating style must be returned.'
);

$source = file_get_contents( dirname( __DIR__ ) . '/inc/mobile-fixed-nav.php' );
cni_mobile_nav_assert(
	false !== strpos( $source, 'lightning-child-mobile-fixed-nav__item-indicator' ),
	'Each regular floating navigation item must render its own stationary indicator.'
);
cni_mobile_nav_assert(
	false !== strpos( $source, "'lightning-child-mobile-fixed-nav--' . \$nav_style" ),
	'The navigation output must include a style-specific class.'
);
cni_mobile_nav_assert(
	false !== strpos( $source, "'floating' !== lightning_child_get_mobile_fixed_nav_style()" ),
	'The script loader must support floating navigation without a hamburger drawer.'
);
cni_mobile_nav_assert(
	false !== strpos( $source, "lightning-child-mobile-menu-drawer--source-footer" )
	&& false !== strpos( $source, "'footer' === ( \$menu_source['type'] ?? '' )" ),
	'The drawer must expose a footer-source class without affecting pattern content.'
);

$css = file_get_contents( dirname( __DIR__ ) . '/style.css' );
cni_mobile_nav_assert(
	1 === preg_match( '/\.lightning-child-mobile-fixed-nav__icon\s*\{[^}]*margin:\s*0;/s', $css ),
	'Fixed navigation icons must reset generic theme margins in both flat and floating styles.'
);
cni_mobile_nav_assert(
	false === strpos( $css, 'isolation: isolate;' )
	&& 1 === preg_match( '/\.lightning-child-mobile-fixed-nav--floating \.lightning-child-mobile-fixed-nav__link\s*\{[^}]*z-index:\s*1;/s', $css )
	&& 1 === preg_match( '/\.lightning-child-mobile-fixed-nav--floating \.lightning-child-mobile-fixed-nav__item-indicator\s*\{[^}]*z-index:\s*0;/s', $css ),
	'All navigation links must share a layer above the wide floating indicator, including adjacent items.'
);
cni_mobile_nav_assert(
	false !== strpos( $css, '.lightning-child-mobile-menu-drawer--source-footer .lightning-child-mobile-menu-drawer__content' )
	&& false !== strpos( $css, '--wp--style--global--content-size: 100%;' )
	&& false !== strpos( $css, '--wp--style--global--wide-size: 100%;' )
	&& false !== strpos( $css, 'padding-right: 0 !important;' )
	&& false !== strpos( $css, 'padding-left: 0 !important;' ),
	'Footer template content must fill the drawer without inherited site-wide outer padding.'
);
cni_mobile_nav_assert(
	false !== strpos( $css, '--lightning-child-mobile-menu-drawer-width: min(100vw, 420px);' )
	&& false !== strpos( $css, 'body.lightning-child-mobile-menu-drawer-open .lightning-child-mobile-fixed-nav' )
	&& false !== strpos( $css, 'z-index: 10030;' )
	&& false !== strpos( $css, 'padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px));' ),
	'The drawer must use the full phone viewport while the fixed navigation remains visible above it.'
);
cni_mobile_nav_assert(
	false !== strpos( $source, 'lightning-child-mobile-fixed-nav__indicator-shape' )
	&& false !== strpos( $source, 'preserveAspectRatio="none"' ),
	'Each floating item must include its responsive inline SVG raised shape.'
);
cni_mobile_nav_assert(
	false !== strpos( $source, 'M0 64V40C12 40 20 40 28 39' )
	&& false !== strpos( $source, '82 26C96 17 124 17 138 26' ),
	'The raised shape baseline and broad rounded arch must match the approved geometry.'
);
cni_mobile_nav_assert(
	false === strpos( $source, 'lightning-child-mobile-fixed-nav__indicator-edge' )
	&& false === strpos( $css, 'drop-shadow(' ),
	'The floating indicator must not include the rejected contour shadow.'
);
cni_mobile_nav_assert(
	1 === preg_match( '/\.lightning-child-mobile-fixed-nav--floating\s*\{[^}]*border-top:\s*0;/s', $css ),
	'The floating navigation must remove the inherited top border.'
);
cni_mobile_nav_assert(
	false === strpos( $source, 'lightning-child-mobile-fixed-nav__indicator-icon' )
	&& false === strpos( $source, 'lightning-child-mobile-fixed-nav__indicator-label' ),
	'The indicator must not duplicate or reposition the link icon and label.'
);
cni_mobile_nav_assert(
	false !== strpos( $css, '.lightning-child-mobile-fixed-nav__indicator-shape' ),
	'The SVG raised shape must inherit the navigation background color.'
);
cni_mobile_nav_assert(
	false !== strpos( $css, '.lightning-child-mobile-fixed-nav__item-indicator::after' ),
	'Each stationary indicator must include its white circular surface.'
);
cni_mobile_nav_assert(
	false !== strpos( $css, 'color: var(--lightning-child-mobile-fixed-nav-background, #333333);' ),
	'The active icon must reuse the navigation background color.'
);
cni_mobile_nav_assert(
	false !== strpos( $css, 'margin: 0;' ),
	'The mobile navigation icon must reset inherited Lightning margins.'
);
cni_mobile_nav_assert(
	false !== strpos( $css, '.lightning-child-mobile-fixed-nav__item.is-indicator-active .lightning-child-mobile-fixed-nav__label' )
	&& false !== strpos( $css, 'transform: translateY(-8px);' ),
	'The active label must close the visual gap below the raised icon.'
);
cni_mobile_nav_assert(
	false !== strpos( $css, 'margin-top: 6px;' )
	&& false !== strpos( $css, '--lightning-child-mobile-fixed-nav-indicator-circle-size: min(58px, 15.5vw);' )
	&& false !== strpos( $css, '0.5833 - 4px' )
	&& false !== strpos( $css, 'transform: translate3d(-50%, 0, 0) scale(1);' )
	&& false !== strpos( $css, 'transform: translateY(-6px);' )
	&& false !== strpos( $css, 'transform: translateY(-8px);' )
	&& false !== strpos( $css, ':is(.fa-home, .fa-house, .fa-house-chimney) + .lightning-child-mobile-fixed-nav__label' )
	&& false !== strpos( $css, 'transform: translateY(-9px);' )
	&& false !== strpos( $css, 'transform 0.5s cubic-bezier(0.22, 1, 0.36, 1)' ),
	'The active indicator must align with the navigation edge while preserving the circle position and centering its icon.'
);

echo "Mobile fixed navigation PHP tests passed.\n";
