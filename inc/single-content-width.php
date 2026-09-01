<?php
/**
 * Single post content width settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return supported width modes.
 *
 * @param bool $allow_inherit Whether the inherit choice is allowed.
 * @return string[]
 */
function lightning_child_get_single_content_width_modes( $allow_inherit = false ) {
	$modes = array( '840', '960', '1080', 'full', 'custom' );

	if ( $allow_inherit ) {
		array_unshift( $modes, 'inherit' );
	}

	return $modes;
}

/**
 * Sanitize the common width mode.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_single_content_width_mode( $value ) {
	$value = is_string( $value ) ? $value : '';

	return in_array( $value, lightning_child_get_single_content_width_modes(), true ) ? $value : '960';
}

/**
 * Sanitize a post-type width mode.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_single_content_width_override( $value ) {
	$value = is_string( $value ) ? $value : '';

	return in_array( $value, lightning_child_get_single_content_width_modes( true ), true ) ? $value : 'inherit';
}

/**
 * Sanitize a custom article width.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_single_content_custom_width( $value ) {
	$value = absint( $value );

	return min( 1200, max( 640, $value ) );
}

/**
 * Sanitize the two-column container width mode.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_two_column_width_mode( $value ) {
	$value   = is_string( $value ) ? $value : '';
	$choices = array( '1320', '1440', '1560', 'full', 'custom' );

	return in_array( $value, $choices, true ) ? $value : '1440';
}

/**
 * Sanitize a custom two-column container width.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_two_column_custom_width( $value ) {
	$value = absint( $value );

	return min( 1920, max( 1080, $value ) );
}

/**
 * Return choices shown by the two-column width control.
 *
 * @return array<string,string>
 */
function lightning_child_get_two_column_width_choices() {
	return array(
		'1320'   => __( '狭め（1320px）', 'cni-lightning-child' ),
		'1440'   => __( '標準（1440px）', 'cni-lightning-child' ),
		'1560'   => __( '広め（1560px）', 'cni-lightning-child' ),
		'full'   => __( 'Lightning標準（幅制限なし）', 'cni-lightning-child' ),
		'custom' => __( 'カスタム指定', 'cni-lightning-child' ),
	);
}

/**
 * Return post types supported by the single content width setting.
 *
 * Pages and attachments are intentionally excluded because their block layouts
 * commonly rely on the full Lightning container width.
 *
 * @return WP_Post_Type[]
 */
function lightning_child_get_single_content_width_post_types() {
	$post_types = get_post_types(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'objects'
	);

	unset( $post_types['page'], $post_types['attachment'] );

	return $post_types;
}

/**
 * Return choices shown by the width controls.
 *
 * @param bool $allow_inherit Whether the inherit choice is allowed.
 * @return array<string,string>
 */
function lightning_child_get_single_content_width_choices( $allow_inherit = false ) {
	$choices = array(
		'840'    => __( '狭め（840px）', 'cni-lightning-child' ),
		'960'    => __( '標準（960px）', 'cni-lightning-child' ),
		'1080'   => __( '広め（1080px）', 'cni-lightning-child' ),
		'full'   => __( 'Lightning標準（幅制限なし）', 'cni-lightning-child' ),
		'custom' => __( 'カスタム指定', 'cni-lightning-child' ),
	);

	if ( $allow_inherit ) {
		$choices = array( 'inherit' => __( '共通設定を使用', 'cni-lightning-child' ) ) + $choices;
	}

	return $choices;
}

/**
 * Determine whether a custom-width number control should be shown.
 *
 * @param WP_Customize_Control $control Current control.
 * @return bool
 */
function lightning_child_is_single_content_custom_width_control_active( $control ) {
	if ( ! $control instanceof WP_Customize_Control ) {
		return false;
	}

	$mode_setting_id = preg_replace( '/_custom_width\z/', '', $control->id );
	$mode_setting    = $control->manager->get_setting( $mode_setting_id );

	return $mode_setting && 'custom' === $mode_setting->value();
}

/**
 * Register article width settings under Lightning Layout Settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_single_content_width( $wp_customize ) {
	$wp_customize->add_section(
		'lightning_child_single_content_width',
		array(
			'title'       => __( '記事レイアウト幅設定', 'cni-lightning-child' ),
			'description' => __( '1カラムの個別記事本文幅と、本文・サイドバーを含む2カラム全体の最大幅を設定します。', 'cni-lightning-child' ),
			'panel'       => 'lightning_layout',
			'priority'    => 30,
		)
	);

	$wp_customize->add_setting(
		'lightning_child_single_content_width',
		array(
			'default'           => '960',
			'sanitize_callback' => 'lightning_child_sanitize_single_content_width_mode',
		)
	);
	$wp_customize->add_control(
		'lightning_child_single_content_width',
		array(
			'label'       => __( '共通の1カラム記事幅', 'cni-lightning-child' ),
			'description' => __( '未保存のサイトにも標準960pxが適用されます。', 'cni-lightning-child' ),
			'section'     => 'lightning_child_single_content_width',
			'type'        => 'select',
			'choices'     => lightning_child_get_single_content_width_choices(),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_single_content_width_custom_width',
		array(
			'default'           => 960,
			'sanitize_callback' => 'lightning_child_sanitize_single_content_custom_width',
		)
	);
	$wp_customize->add_control(
		'lightning_child_single_content_width_custom_width',
		array(
			'label'           => __( '共通のカスタム幅（px）', 'cni-lightning-child' ),
			'section'         => 'lightning_child_single_content_width',
			'type'            => 'number',
			'input_attrs'     => array(
				'min'  => 640,
				'max'  => 1200,
				'step' => 10,
			),
			'active_callback' => 'lightning_child_is_single_content_custom_width_control_active',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_two_column_width',
		array(
			'default'           => '1440',
			'sanitize_callback' => 'lightning_child_sanitize_two_column_width_mode',
		)
	);
	$wp_customize->add_control(
		'lightning_child_two_column_width',
		array(
			'label'       => __( '2カラムの全体最大幅', 'cni-lightning-child' ),
			'description' => __( '本文とサイドバーを含む幅です。固定ページ・投稿・アーカイブなど、Lightningの2カラム画面へ共通適用します。', 'cni-lightning-child' ),
			'section'     => 'lightning_child_single_content_width',
			'type'        => 'select',
			'choices'     => lightning_child_get_two_column_width_choices(),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_two_column_width_custom_width',
		array(
			'default'           => 1440,
			'sanitize_callback' => 'lightning_child_sanitize_two_column_custom_width',
		)
	);
	$wp_customize->add_control(
		'lightning_child_two_column_width_custom_width',
		array(
			'label'           => __( '2カラムのカスタム幅（px）', 'cni-lightning-child' ),
			'section'         => 'lightning_child_single_content_width',
			'type'            => 'number',
			'input_attrs'     => array(
				'min'  => 1080,
				'max'  => 1920,
				'step' => 10,
			),
			'active_callback' => 'lightning_child_is_single_content_custom_width_control_active',
		)
	);

	foreach ( lightning_child_get_single_content_width_post_types() as $post_type ) {
		$setting_id = 'lightning_child_single_content_width_' . $post_type->name;

		$wp_customize->add_setting(
			$setting_id,
			array(
				'default'           => 'inherit',
				'sanitize_callback' => 'lightning_child_sanitize_single_content_width_override',
			)
		);
		$wp_customize->add_control(
			$setting_id,
			array(
				/* translators: %s: post type label. */
				'label'   => sprintf( __( '%sの記事幅', 'cni-lightning-child' ), $post_type->labels->name ),
				'section' => 'lightning_child_single_content_width',
				'type'    => 'select',
				'choices' => lightning_child_get_single_content_width_choices( true ),
			)
		);

		$custom_setting_id = $setting_id . '_custom_width';
		$wp_customize->add_setting(
			$custom_setting_id,
			array(
				'default'           => 960,
				'sanitize_callback' => 'lightning_child_sanitize_single_content_custom_width',
			)
		);
		$wp_customize->add_control(
			$custom_setting_id,
			array(
				/* translators: %s: post type label. */
				'label'           => sprintf( __( '%sのカスタム幅（px）', 'cni-lightning-child' ), $post_type->labels->name ),
				'section'         => 'lightning_child_single_content_width',
				'type'            => 'number',
				'input_attrs'     => array(
					'min'  => 640,
					'max'  => 1200,
					'step' => 10,
				),
				'active_callback' => 'lightning_child_is_single_content_custom_width_control_active',
			)
		);
	}
}
add_action( 'customize_register', 'lightning_child_customize_single_content_width', 20 );

/**
 * Resolve the maximum width for the current single post.
 *
 * A zero value means that Lightning's standard container width is retained.
 *
 * @return int
 */
function lightning_child_get_current_single_content_width() {
	if ( ! is_singular() || is_page() || is_attachment() ) {
		return 0;
	}

	$post_type = get_post_type();
	if ( ! $post_type || ! post_type_exists( $post_type ) ) {
		return 0;
	}

	$post_type_object = get_post_type_object( $post_type );
	if ( ! $post_type_object || ! $post_type_object->public ) {
		return 0;
	}

	$override = lightning_child_sanitize_single_content_width_override(
		get_theme_mod( 'lightning_child_single_content_width_' . $post_type, 'inherit' )
	);
	$mode     = $override;

	if ( 'inherit' === $mode ) {
		$mode = lightning_child_sanitize_single_content_width_mode(
			get_theme_mod( 'lightning_child_single_content_width', '960' )
		);
	}

	if ( 'full' === $mode ) {
		return 0;
	}

	if ( 'custom' === $mode ) {
		$custom_setting = 'inherit' === $override
			? 'lightning_child_single_content_width_custom_width'
			: 'lightning_child_single_content_width_' . $post_type . '_custom_width';

		return lightning_child_sanitize_single_content_custom_width( get_theme_mod( $custom_setting, 960 ) );
	}

	return lightning_child_sanitize_single_content_custom_width( $mode );
}

/**
 * Add a stable class to eligible one-column article containers.
 *
 * @param array<string,string[]> $class_names Lightning class groups.
 * @return array<string,string[]>
 */
function lightning_child_add_single_content_width_class( $class_names ) {
	if (
		! function_exists( 'lightning_is_layout_onecolumn' )
		|| ! lightning_is_layout_onecolumn()
		|| 0 === lightning_child_get_current_single_content_width()
		|| empty( $class_names['main-section'] )
	) {
		return $class_names;
	}

	$class_names['main-section'][] = 'lightning-child-single-content-width';

	return $class_names;
}
add_filter( 'lightning_get_class_names', 'lightning_child_add_single_content_width_class', 20 );

/**
 * Add the resolved width as a sanitized CSS custom property.
 *
 * @return void
 */
function lightning_child_enqueue_single_content_width_style() {
	if ( ! function_exists( 'lightning_is_layout_onecolumn' ) || ! lightning_is_layout_onecolumn() ) {
		return;
	}

	$width = lightning_child_get_current_single_content_width();
	if ( 0 === $width ) {
		return;
	}

	wp_add_inline_style(
		'lightning-theme-style',
		':root{--lightning-child-single-content-width:' . absint( $width ) . 'px;}'
	);
}
add_action( 'wp_enqueue_scripts', 'lightning_child_enqueue_single_content_width_style', 30 );

/**
 * Resolve the maximum width for Lightning's two-column container.
 *
 * A zero value means that Lightning's standard container width is retained.
 *
 * @return int
 */
function lightning_child_get_two_column_width() {
	$mode = lightning_child_sanitize_two_column_width_mode(
		get_theme_mod( 'lightning_child_two_column_width', '1440' )
	);

	if ( 'full' === $mode ) {
		return 0;
	}

	if ( 'custom' === $mode ) {
		return lightning_child_sanitize_two_column_custom_width(
			get_theme_mod( 'lightning_child_two_column_width_custom_width', 1440 )
		);
	}

	return lightning_child_sanitize_two_column_custom_width( $mode );
}

/**
 * Determine whether the current screen uses Lightning's two-column layout.
 *
 * @return bool
 */
function lightning_child_is_two_column_layout() {
	return function_exists( 'lightning_is_layout_onecolumn' )
		&& function_exists( 'lightning_is_subsection' )
		&& ! lightning_is_layout_onecolumn()
		&& lightning_is_subsection();
}

/**
 * Add a body class only when the two-column width limit is active.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function lightning_child_add_two_column_width_body_class( $classes ) {
	if ( lightning_child_is_two_column_layout() && 0 !== lightning_child_get_two_column_width() ) {
		$classes[] = 'lightning-child-two-column-width';
	}

	return $classes;
}
add_filter( 'body_class', 'lightning_child_add_two_column_width_body_class' );

/**
 * Add the resolved two-column width as a sanitized CSS custom property.
 *
 * @return void
 */
function lightning_child_enqueue_two_column_width_style() {
	if ( ! lightning_child_is_two_column_layout() ) {
		return;
	}

	$width = lightning_child_get_two_column_width();
	if ( 0 === $width ) {
		return;
	}

	wp_add_inline_style(
		'lightning-theme-style',
		':root{--lightning-child-two-column-width:' . absint( $width ) . 'px;'
		. '--lightning-child-two-column-sidebar-width:' . absint( round( $width * 0.27 ) ) . 'px;}'
	);
}
add_action( 'wp_enqueue_scripts', 'lightning_child_enqueue_two_column_width_style', 30 );
