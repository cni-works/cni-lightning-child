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

$css = file_get_contents( dirname( __DIR__ ) . '/style.css' );
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
	false !== strpos( $source, 'lightning-child-mobile-fixed-nav__indicator-edge' )
	&& false !== strpos( $css, 'stroke-width: 0.75;' )
	&& false !== strpos( $css, 'drop-shadow(0 -1.5px 2.5px rgba(0, 0, 0, 0.09))' ),
	'The completed arch must use only one subtle shadow along its upper contour.'
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
