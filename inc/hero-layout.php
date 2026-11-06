<?php
/**
 * Full-width hero handling for CNI Blocks Outer+.
 *
 * CNI Blocks only declares the hero intent in block attributes and classes.
 * Lightning renders the main content and sidebar as sibling float columns, so
 * a leading full-width hero must be output before those columns to allow the
 * sidebar to begin below it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the leading CNI Blocks Outer+ selected for the full-width hero layout.
 *
 * Only the first meaningful top-level block is eligible. This prevents a
 * content block placed partway through an article from changing page layout.
 *
 * @return array{post_id:int,block_index:int,block:array<string,mixed>}|null
 */
function lightning_child_get_full_width_hero_block() {
	static $hero_block = null;
	static $is_resolved = false;

	if ( $is_resolved ) {
		return $hero_block;
	}

	$is_resolved = true;

	if (
		is_admin()
		|| is_feed()
		|| ! is_singular()
		|| ! function_exists( 'parse_blocks' )
		|| ! lightning_child_is_two_column_layout()
	) {
		return null;
	}

	$post = get_queried_object();
	if ( ! $post instanceof WP_Post || '' === $post->post_content ) {
		return null;
	}

	$blocks = parse_blocks( $post->post_content );

	foreach ( $blocks as $block_index => $block ) {
		$block_name = isset( $block['blockName'] ) ? $block['blockName'] : null;
		$inner_html = isset( $block['innerHTML'] ) ? $block['innerHTML'] : '';

		// Ignore whitespace before the first saved block.
		if ( null === $block_name && '' === trim( $inner_html ) ) {
			continue;
		}

		if ( 'cni-blocks/outer' !== $block_name ) {
			return null;
		}

		$attributes = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		if (
			empty( $attributes['isHero'] )
			|| ! isset( $attributes['heroLayout'] )
			|| 'full-width' !== $attributes['heroLayout']
		) {
			return null;
		}

		$hero_block = array(
			'post_id'     => (int) $post->ID,
			'block_index' => (int) $block_index,
			'block'       => $block,
		);

		return $hero_block;
	}

	return null;
}

/**
 * Add a body class only when a leading full-width hero will be separated.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function lightning_child_add_full_width_hero_body_class( $classes ) {
	if ( null !== lightning_child_get_full_width_hero_block() ) {
		$classes[] = 'lightning-child-full-width-hero-layout';
	}

	return $classes;
}
add_filter( 'body_class', 'lightning_child_add_full_width_hero_body_class' );

/**
 * Render a leading full-width hero before Lightning's content/sidebar columns.
 *
 * @return void
 */
function lightning_child_render_full_width_hero() {
	$hero_block = lightning_child_get_full_width_hero_block();
	if ( null === $hero_block ) {
		return;
	}

	echo '<div class="lightning-child-full-width-hero">';
	// The block is rendered through WordPress's normal block renderer.
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo render_block( $hero_block['block'] );
	echo '</div>';
}
add_action( 'lightning_site_body_prepend', 'lightning_child_render_full_width_hero', 5 );

/**
 * Remove the separately rendered hero from the main post content.
 *
 * This runs before core's do_blocks filter so all remaining blocks retain the
 * normal WordPress rendering pipeline.
 *
 * @param string $content Raw post content.
 * @return string
 */
function lightning_child_remove_full_width_hero_from_content( $content ) {
	if ( ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	$hero_block = lightning_child_get_full_width_hero_block();
	if ( null === $hero_block || get_the_ID() !== $hero_block['post_id'] ) {
		return $content;
	}

	$blocks = parse_blocks( $content );
	$index  = $hero_block['block_index'];

	if (
		! isset( $blocks[ $index ] )
		|| 'cni-blocks/outer' !== $blocks[ $index ]['blockName']
	) {
		return $content;
	}

	unset( $blocks[ $index ] );

	return serialize_blocks( array_values( $blocks ) );
}
add_filter( 'the_content', 'lightning_child_remove_full_width_hero_from_content', 8 );
