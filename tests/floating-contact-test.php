<?php
/**
 * Regression tests for floating contact position settings.
 *
 * Run with: php tests/floating-contact-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );

function add_action( $hook, $callback, $priority = 10 ) {
}

function add_filter( $hook, $callback, $priority = 10 ) {
}

function absint( $value ) {
	return abs( (int) $value );
}

function floating_contact_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: $message\n" );
		exit( 1 );
	}
}

require dirname( __DIR__ ) . '/inc/floating-contact.php';

floating_contact_assert(
	'' === lightning_child_sanitize_floating_contact_mobile_top( '' ),
	'An empty smartphone position must inherit the existing desktop setting.'
);
floating_contact_assert(
	5 === lightning_child_sanitize_floating_contact_mobile_top( 2 )
		&& 37 === lightning_child_sanitize_floating_contact_mobile_top( 37 )
		&& 80 === lightning_child_sanitize_floating_contact_mobile_top( 100 ),
	'The smartphone position must remain within the supported 5 to 80vh range.'
);

$source = file_get_contents( dirname( __DIR__ ) . '/inc/floating-contact.php' );
floating_contact_assert(
	false !== strpos( $source, 'lightning_child_floating_contact_top_mobile' ),
	'The optional smartphone position setting must be registered.'
);
floating_contact_assert(
	false !== strpos( $source, '--lightning-child-floating-contact-top-desktop' )
		&& false !== strpos( $source, '--lightning-child-floating-contact-top-mobile' ),
	'The inline CSS must expose separate desktop and smartphone position variables.'
);

$style = file_get_contents( dirname( __DIR__ ) . '/style.css' );
floating_contact_assert(
	false !== strpos( $style, '@media (max-width: 767px)' )
		&& false !== strpos( $style, '--lightning-child-floating-contact-top-current: var(--lightning-child-floating-contact-top-mobile' ),
	'The smartphone breakpoint must use the smartphone position with a desktop fallback.'
);

echo "Floating contact tests passed.\n";
