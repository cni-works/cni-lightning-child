<?php
/**
 * Mobile fixed navigation settings and output.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the maximum number of regular navigation buttons.
 *
 * @return int
 */
function lightning_child_get_mobile_fixed_nav_max_items() {
	return 5;
}

/**
 * Sanitize the number of regular navigation buttons.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_mobile_fixed_nav_item_count( $value ) {
	return 5 === absint( $value ) ? 5 : 4;
}

/**
 * Sanitize a mobile navigation URL.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_mobile_fixed_nav_url( $value ) {
	return is_string( $value ) ? esc_url_raw( trim( $value ) ) : '';
}

/**
 * Sanitize the mobile fixed navigation display style.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_mobile_fixed_nav_style( $value ) {
	return is_string( $value ) && in_array( $value, array( 'flat', 'floating' ), true ) ? $value : 'flat';
}

/**
 * Sanitize the label font size used inside the floating indicator.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_mobile_fixed_nav_floating_font_size( $value ) {
	$font_size = is_numeric( $value ) ? (int) $value : 10;
	return min( 12, max( 8, $font_size ) );
}

/**
 * Sanitize the content source used by the hamburger drawer.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_mobile_menu_source( $value ) {
	return is_string( $value ) && in_array( $value, array( 'footer', 'pattern' ), true ) ? $value : 'footer';
}

/**
 * Sanitize a footer template part selected only for the hamburger drawer.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_mobile_menu_footer_part( $value ) {
	if ( in_array( $value, array( 'current', 'block' ), true ) ) {
		return $value;
	}

	if ( is_string( $value ) && 0 === strpos( $value, 'part:' ) ) {
		$slug = sanitize_key( substr( $value, 5 ) );
		return '' !== $slug ? 'part:' . $slug : 'current';
	}

	return 'current';
}

/**
 * Return footer template part choices for the hamburger drawer.
 *
 * @return array<string, string>
 */
function lightning_child_get_mobile_menu_footer_choices() {
	$choices = array( 'current' => __( '通常フッター設定と同じ', 'cni-lightning-child' ) );
	if ( ! function_exists( 'lightning_child_get_template_part_choices' ) ) {
		return $choices;
	}

	foreach ( lightning_child_get_template_part_choices( 'footer' ) as $value => $label ) {
		if ( 'standard' !== $value ) {
			$choices[ $value ] = $label;
		}
	}

	return $choices;
}

/**
 * Sanitize a published My Pattern ID.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_mobile_menu_pattern_id( $value ) {
	$pattern_id = absint( $value );
	return $pattern_id
		&& 'wp_block' === get_post_type( $pattern_id )
		&& 'publish' === get_post_status( $pattern_id )
		? $pattern_id
		: 0;
}

/**
 * Return published My Patterns for the Customizer select control.
 *
 * @return array<int, string>
 */
function lightning_child_get_mobile_menu_pattern_choices() {
	$choices = array( 0 => __( '選択してください', 'cni-lightning-child' ) );
	$patterns = get_posts(
		array(
			'post_type'              => 'wp_block',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $patterns as $pattern ) {
		$title = trim( wp_strip_all_tags( get_the_title( $pattern ) ) );
		$choices[ $pattern->ID ] = '' !== $title
			? $title
			: sprintf(
				/* translators: %d: pattern post ID. */
				__( '名称未設定のパターン（ID: %d）', 'cni-lightning-child' ),
				$pattern->ID
			);
	}

	return $choices;
}

/**
 * Register mobile fixed navigation settings in the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_mobile_fixed_nav( $wp_customize ) {
	$section_id = 'lightning_child_mobile_fixed_nav';

	$wp_customize->add_section(
		$section_id,
		array(
			'title'       => __( 'Lightning モバイル固定ナビ', 'cni-lightning-child' ),
			'description' => __( 'スマートフォン画面の下部へ固定ナビを表示します。初期状態では表示されません。', 'cni-lightning-child' ),
			'priority'    => 168,
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mobile_fixed_nav_enabled',
		array(
			'default'           => false,
			'sanitize_callback' => 'lightning_child_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mobile_fixed_nav_enabled',
		array(
			'label'   => __( 'モバイル固定ナビを表示する', 'cni-lightning-child' ),
			'section' => $section_id,
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mobile_fixed_nav_style',
		array(
			'default'           => 'flat',
			'sanitize_callback' => 'lightning_child_sanitize_mobile_fixed_nav_style',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mobile_fixed_nav_style',
		array(
			'label'   => __( '下部メニュースタイル', 'cni-lightning-child' ),
			'section' => $section_id,
			'type'    => 'radio',
			'choices' => array(
				'flat'     => __( 'フラット', 'cni-lightning-child' ),
				'floating' => __( 'フローティング', 'cni-lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mobile_fixed_nav_floating_font_size',
		array(
			'default'           => 10,
			'sanitize_callback' => 'lightning_child_sanitize_mobile_fixed_nav_floating_font_size',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mobile_fixed_nav_floating_font_size',
		array(
			'label'       => __( 'フローティング型のメニュー文字サイズ', 'cni-lightning-child' ),
			'description' => __( '白い円の中へ表示します。フローティング型では短いメニュー名を推奨します。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'range',
			'input_attrs' => array(
				'min'  => 8,
				'max'  => 12,
				'step' => 1,
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mobile_fixed_nav_show_menu',
		array(
			'default'           => true,
			'sanitize_callback' => 'lightning_child_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mobile_fixed_nav_show_menu',
		array(
			'label'       => __( '左端にハンバーガーメニューを表示する', 'cni-lightning-child' ),
			'description' => __( '下の設定で選択した内容を左から開きます。表示できる内容がない場合は項目を出力しません。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mobile_fixed_nav_menu_source',
		array(
			'default'           => 'footer',
			'sanitize_callback' => 'lightning_child_sanitize_mobile_menu_source',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mobile_fixed_nav_menu_source',
		array(
			'label'       => __( 'ハンバーガーメニューの表示内容', 'cni-lightning-child' ),
			'description' => __( '既存サイトとの互換性のため、初期値は選択中のフッターテンプレートパーツです。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'radio',
			'choices'     => array(
				'footer'  => __( 'フッターテンプレートパーツ', 'cni-lightning-child' ),
				'pattern' => __( 'マイパターン', 'cni-lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mobile_fixed_nav_menu_footer_part',
		array(
			'default'           => 'current',
			'sanitize_callback' => 'lightning_child_sanitize_mobile_menu_footer_part',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mobile_fixed_nav_menu_footer_part',
		array(
			'label'       => __( '表示するフッターテンプレートパーツ', 'cni-lightning-child' ),
			'description' => __( 'ハンバーガー専用に、通常フッターとは別のフッターテンプレートパーツを選択できます。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'select',
			'choices'     => lightning_child_get_mobile_menu_footer_choices(),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mobile_fixed_nav_menu_pattern_id',
		array(
			'default'           => 0,
			'sanitize_callback' => 'lightning_child_sanitize_mobile_menu_pattern_id',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mobile_fixed_nav_menu_pattern_id',
		array(
			'label'       => __( '表示するマイパターン', 'cni-lightning-child' ),
			'description' => __( '「マイパターン」を選んだ場合に使用します。新しく作成した場合はカスタマイザーを開き直してください。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'select',
			'choices'     => lightning_child_get_mobile_menu_pattern_choices(),
		)
	);

	$menu_text_settings = array(
		'lightning_child_mobile_fixed_nav_menu_label' => array(
			'label'             => __( 'メニューボタンのテキスト', 'cni-lightning-child' ),
			'default'           => __( 'メニュー', 'cni-lightning-child' ),
			'sanitize_callback' => 'sanitize_text_field',
		),
		'lightning_child_mobile_fixed_nav_menu_icon'  => array(
			'label'             => __( 'メニューボタンのFont Awesomeクラス', 'cni-lightning-child' ),
			'default'           => 'fa-solid fa-bars',
			'sanitize_callback' => 'lightning_child_sanitize_font_awesome_classes',
		),
	);

	foreach ( $menu_text_settings as $setting_name => $setting ) {
		$wp_customize->add_setting(
			$setting_name,
			array(
				'default'           => $setting['default'],
				'sanitize_callback' => $setting['sanitize_callback'],
			)
		);
		$wp_customize->add_control(
			$setting_name,
			array(
				'label'       => $setting['label'],
				'description' => false !== strpos( $setting_name, '_icon' ) ? __( '例: fa-solid fa-bars（HTMLタグは入力しません）', 'cni-lightning-child' ) : '',
				'section'     => $section_id,
				'type'        => 'text',
			)
		);
	}

	$wp_customize->add_setting(
		'lightning_child_mobile_fixed_nav_item_count',
		array(
			'default'           => 4,
			'sanitize_callback' => 'lightning_child_sanitize_mobile_fixed_nav_item_count',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mobile_fixed_nav_item_count',
		array(
			'label'       => __( '通常リンクの項目数', 'cni-lightning-child' ),
			'description' => __( '5項目ではメニューボタンを含めて最大6ボタンになります。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'radio',
			'choices'     => array(
				4 => __( '4項目', 'cni-lightning-child' ),
				5 => __( '5項目', 'cni-lightning-child' ),
			),
		)
	);

	for ( $index = 1; $index <= lightning_child_get_mobile_fixed_nav_max_items(); $index++ ) {
		$prefix = 'lightning_child_mobile_fixed_nav_item_' . $index . '_';

		$wp_customize->add_setting(
			$prefix . 'label',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			$prefix . 'label',
			array(
				'label'   => sprintf(
					/* translators: %d: button number. */
					__( 'ボタン%d：リンクテキスト', 'cni-lightning-child' ),
					$index
				),
				'section' => $section_id,
				'type'    => 'text',
			)
		);

		$wp_customize->add_setting(
			$prefix . 'icon',
			array(
				'default'           => '',
				'sanitize_callback' => 'lightning_child_sanitize_font_awesome_classes',
			)
		);
		$wp_customize->add_control(
			$prefix . 'icon',
			array(
				'label'       => sprintf(
					/* translators: %d: button number. */
					__( 'ボタン%d：Font Awesomeクラス', 'cni-lightning-child' ),
					$index
				),
				'description' => __( '例: fa-solid fa-house / fa-brands fa-line', 'cni-lightning-child' ),
				'section'     => $section_id,
				'type'        => 'text',
			)
		);

		$wp_customize->add_setting(
			$prefix . 'url',
			array(
				'default'           => '',
				'sanitize_callback' => 'lightning_child_sanitize_mobile_fixed_nav_url',
			)
		);
		$wp_customize->add_control(
			$prefix . 'url',
			array(
				'label'       => sprintf(
					/* translators: %d: button number. */
					__( 'ボタン%d：リンクURL', 'cni-lightning-child' ),
					$index
				),
				'description' => __( '例: /company/、https://example.com/、tel:0000000000', 'cni-lightning-child' ),
				'section'     => $section_id,
				'type'        => 'text',
			)
		);

		$wp_customize->add_setting(
			$prefix . 'new_window',
			array(
				'default'           => false,
				'sanitize_callback' => 'lightning_child_sanitize_boolean',
			)
		);
		$wp_customize->add_control(
			$prefix . 'new_window',
			array(
				'label'   => sprintf(
					/* translators: %d: button number. */
					__( 'ボタン%d：リンク先を別ウィンドウで開く', 'cni-lightning-child' ),
					$index
				),
				'section' => $section_id,
				'type'    => 'checkbox',
			)
		);
	}

	$color_settings = array(
		'lightning_child_mobile_fixed_nav_background_color' => array(
			'label'   => __( '背景色', 'cni-lightning-child' ),
			'default' => '#333333',
		),
		'lightning_child_mobile_fixed_nav_text_color'       => array(
			'label'   => __( '文字・アイコン色', 'cni-lightning-child' ),
			'default' => '#ffffff',
		),
		'lightning_child_mobile_fixed_nav_active_background_color' => array(
			'label'   => __( '現在ページの背景色', 'cni-lightning-child' ),
			'default' => '#1a1a1a',
		),
		'lightning_child_mobile_fixed_nav_active_text_color' => array(
			'label'   => __( '現在ページの文字・アイコン色', 'cni-lightning-child' ),
			'default' => '#ffffff',
		),
		'lightning_child_mobile_fixed_nav_border_color'     => array(
			'label'   => __( '上端の境界線色', 'cni-lightning-child' ),
			'default' => '#555555',
		),
		'lightning_child_mobile_menu_drawer_background_color' => array(
			'label'   => __( 'ハンバーガー画面の背景色', 'cni-lightning-child' ),
			'default' => '#ffffff',
		),
		'lightning_child_mobile_menu_drawer_text_color' => array(
			'label'   => __( 'ハンバーガー画面の文字色', 'cni-lightning-child' ),
			'default' => '#333333',
		),
	);

	foreach ( $color_settings as $setting_name => $setting ) {
		$wp_customize->add_setting(
			$setting_name,
			array(
				'default'           => $setting['default'],
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				$setting_name,
				array(
					'label'   => $setting['label'],
					'section' => $section_id,
				)
			)
		);
	}
}
add_action( 'customize_register', 'lightning_child_customize_mobile_fixed_nav' );

/**
 * Determine whether the fixed navigation is enabled.
 *
 * @return bool
 */
function lightning_child_is_mobile_fixed_nav_enabled() {
	return rest_sanitize_boolean( get_theme_mod( 'lightning_child_mobile_fixed_nav_enabled', false ) );
}

/**
 * Return the selected mobile fixed navigation display style.
 *
 * @return string
 */
function lightning_child_get_mobile_fixed_nav_style() {
	return lightning_child_sanitize_mobile_fixed_nav_style(
		get_theme_mod( 'lightning_child_mobile_fixed_nav_style', 'flat' )
	);
}

/**
 * Return the label font size used inside the floating indicator.
 *
 * @return int
 */
function lightning_child_get_mobile_fixed_nav_floating_font_size() {
	return lightning_child_sanitize_mobile_fixed_nav_floating_font_size(
		get_theme_mod( 'lightning_child_mobile_fixed_nav_floating_font_size', 10 )
	);
}

/**
 * Return the renderable content selected for the hamburger drawer.
 *
 * @return array<string, mixed>
 */
function lightning_child_get_mobile_menu_content_source() {
	$source = lightning_child_sanitize_mobile_menu_source(
		get_theme_mod( 'lightning_child_mobile_fixed_nav_menu_source', 'footer' )
	);

	if ( 'pattern' === $source ) {
		$pattern_id = lightning_child_sanitize_mobile_menu_pattern_id(
			get_theme_mod( 'lightning_child_mobile_fixed_nav_menu_pattern_id', 0 )
		);
		$content = $pattern_id ? get_post_field( 'post_content', $pattern_id ) : '';
		return $pattern_id && is_string( $content ) && '' !== trim( $content )
			? array(
				'type' => 'pattern',
				'id'   => $pattern_id,
			)
			: array();
	}

	if ( ! function_exists( 'lightning_child_get_renderable_template_part_slug' )
		|| ! function_exists( 'lightning_child_get_template_part_mode' )
		|| ! function_exists( 'lightning_child_template_part_has_content' ) ) {
		return array();
	}

	$footer_part = lightning_child_sanitize_mobile_menu_footer_part(
		get_theme_mod( 'lightning_child_mobile_fixed_nav_menu_footer_part', 'current' )
	);
	if ( 'current' === $footer_part ) {
		$slug = 'block' === lightning_child_get_template_part_mode( 'footer' )
			? lightning_child_get_renderable_template_part_slug( 'footer' )
			: '';
	} elseif ( 'block' === $footer_part ) {
		$slug = 'footer';
	} else {
		$slug = 0 === strpos( $footer_part, 'part:' ) ? sanitize_key( substr( $footer_part, 5 ) ) : '';
	}

	if ( '' !== $slug && ! lightning_child_template_part_has_content( $slug, 'footer' ) ) {
		$slug = '';
	}

	return '' !== $slug
		? array(
			'type' => 'footer',
			'slug' => $slug,
		)
		: array();
}

/**
 * Determine whether a hamburger drawer item can be displayed.
 *
 * @return bool
 */
function lightning_child_should_show_mobile_menu() {
	return lightning_child_is_mobile_fixed_nav_enabled()
		&& rest_sanitize_boolean( get_theme_mod( 'lightning_child_mobile_fixed_nav_show_menu', true ) )
		&& ! empty( lightning_child_get_mobile_menu_content_source() );
}

/**
 * Backward-compatible alias for integrations using the previous function.
 *
 * @return bool
 */
function lightning_child_should_show_mobile_footer_menu() {
	return lightning_child_should_show_mobile_menu();
}

/**
 * Return configured regular navigation items.
 *
 * @return array<int, array<string, mixed>>
 */
function lightning_child_get_mobile_fixed_nav_items() {
	$count = lightning_child_sanitize_mobile_fixed_nav_item_count(
		get_theme_mod( 'lightning_child_mobile_fixed_nav_item_count', 4 )
	);
	$items = array();

	for ( $index = 1; $index <= $count; $index++ ) {
		$prefix = 'lightning_child_mobile_fixed_nav_item_' . $index . '_';
		$label  = sanitize_text_field( (string) get_theme_mod( $prefix . 'label', '' ) );
		$url    = lightning_child_sanitize_mobile_fixed_nav_url( get_theme_mod( $prefix . 'url', '' ) );

		if ( '' === $label || '' === $url ) {
			continue;
		}

		$items[] = array(
			'label'      => $label,
			'icon'       => lightning_child_sanitize_font_awesome_classes( get_theme_mod( $prefix . 'icon', '' ) ),
			'url'        => $url,
			'new_window' => rest_sanitize_boolean( get_theme_mod( $prefix . 'new_window', false ) ),
		);
	}

	return $items;
}

/**
 * Determine whether a configured URL points to the current page.
 *
 * @param string $url Configured URL.
 * @return bool
 */
function lightning_child_is_mobile_fixed_nav_current_url( $url ) {
	if ( preg_match( '/\A(?:tel|mailto|sms):/i', $url ) || 0 === strpos( $url, '#' ) ) {
		return false;
	}

	$absolute_url = preg_match( '/\Ahttps?:\/\//i', $url ) ? $url : home_url( '/' . ltrim( $url, '/' ) );
	$link_host    = wp_parse_url( $absolute_url, PHP_URL_HOST );
	$home_host    = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	if ( $link_host && $home_host && strtolower( $link_host ) !== strtolower( $home_host ) ) {
		return false;
	}

	$link_post_id = url_to_postid( $absolute_url );
	if ( $link_post_id && is_singular() ) {
		return get_queried_object_id() === $link_post_id;
	}

	global $wp;
	$current_url  = isset( $wp->request ) ? home_url( $wp->request ) : home_url( '/' );
	$link_path    = untrailingslashit( (string) wp_parse_url( $absolute_url, PHP_URL_PATH ) );
	$current_path = untrailingslashit( (string) wp_parse_url( $current_url, PHP_URL_PATH ) );

	return $link_path === $current_path;
}

/**
 * Add fixed navigation state classes to the body.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function lightning_child_add_mobile_fixed_nav_body_classes( $classes ) {
	if ( ! lightning_child_is_mobile_fixed_nav_enabled() ) {
		return $classes;
	}

	$show_menu = lightning_child_should_show_mobile_menu();
	$items     = lightning_child_get_mobile_fixed_nav_items();
	if ( ! $show_menu && empty( $items ) ) {
		return $classes;
	}

	$classes[] = 'lightning-child-mobile-fixed-nav-enabled';
	if ( $show_menu ) {
		$classes[] = 'lightning-child-mobile-fixed-nav-has-menu';
	}

	return $classes;
}
add_filter( 'body_class', 'lightning_child_add_mobile_fixed_nav_body_classes' );

/**
 * Output a Font Awesome icon when configured.
 *
 * @param string $classes Sanitized icon classes.
 * @return void
 */
function lightning_child_the_mobile_fixed_nav_icon( $classes ) {
	if ( '' !== $classes ) {
		echo '<i class="lightning-child-mobile-fixed-nav__icon ' . esc_attr( $classes ) . '" aria-hidden="true"></i>';
	}
}

/**
 * Render the selected hamburger drawer content.
 *
 * @param array<string, mixed> $source Validated content source.
 * @return void
 */
function lightning_child_render_mobile_menu_content( $source ) {
	if ( empty( $source['type'] ) ) {
		return;
	}

	if ( 'footer' === $source['type'] && ! empty( $source['slug'] ) ) {
		block_template_part( $source['slug'] );
		return;
	}

	if ( 'pattern' === $source['type'] && ! empty( $source['id'] ) ) {
		$content = get_post_field( 'post_content', absint( $source['id'] ) );
		if ( is_string( $content ) && '' !== trim( $content ) ) {
			echo do_blocks( $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core block rendering returns frontend HTML.
		}
	}
}

/**
 * Output the mobile fixed navigation.
 *
 * @return void
 */
function lightning_child_render_mobile_fixed_nav() {
	if ( ! lightning_child_is_mobile_fixed_nav_enabled() ) {
		return;
	}

	$show_menu = lightning_child_should_show_mobile_menu();
	$items     = lightning_child_get_mobile_fixed_nav_items();
	if ( ! $show_menu && empty( $items ) ) {
		return;
	}

	$total_items = count( $items ) + ( $show_menu ? 1 : 0 );
	$menu_source = $show_menu ? lightning_child_get_mobile_menu_content_source() : array();
	$nav_style   = lightning_child_get_mobile_fixed_nav_style();
	$nav_classes = array(
		'lightning-child-mobile-fixed-nav',
		'lightning-child-mobile-fixed-nav--' . $nav_style,
	);
	$drawer_classes = array( 'lightning-child-mobile-menu-drawer' );
	if ( 'footer' === ( $menu_source['type'] ?? '' ) ) {
		$drawer_classes[] = 'lightning-child-mobile-menu-drawer--source-footer';
	}
	if ( 6 <= $total_items ) {
		$nav_classes[] = 'lightning-child-mobile-fixed-nav--compact';
	}
	?>
	<nav class="<?php echo esc_attr( implode( ' ', $nav_classes ) ); ?>" aria-label="<?php esc_attr_e( 'モバイル固定ナビ', 'cni-lightning-child' ); ?>" style="--lightning-child-mobile-fixed-nav-items:<?php echo esc_attr( $total_items ); ?>">
		<ul class="lightning-child-mobile-fixed-nav__list">
			<?php if ( $show_menu ) : ?>
				<li class="lightning-child-mobile-fixed-nav__item">
					<a class="lightning-child-mobile-fixed-nav__link lightning-child-mobile-fixed-nav__menu-button" href="#lightning-child-mobile-menu-drawer" aria-controls="lightning-child-mobile-menu-drawer" aria-expanded="false" data-lightning-child-menu-toggle>
						<?php
						$menu_icon = lightning_child_sanitize_font_awesome_classes(
							get_theme_mod( 'lightning_child_mobile_fixed_nav_menu_icon', 'fa-solid fa-bars' )
						);
						lightning_child_the_mobile_fixed_nav_icon( $menu_icon );
						?>
						<span class="lightning-child-mobile-fixed-nav__label"><?php echo esc_html( get_theme_mod( 'lightning_child_mobile_fixed_nav_menu_label', __( 'メニュー', 'cni-lightning-child' ) ) ); ?></span>
					</a>
				</li>
			<?php endif; ?>

			<?php foreach ( $items as $item ) : ?>
				<?php $is_current = lightning_child_is_mobile_fixed_nav_current_url( $item['url'] ); ?>
				<li class="lightning-child-mobile-fixed-nav__item<?php echo $is_current ? ' is-current' : ''; ?>">
					<a class="lightning-child-mobile-fixed-nav__link" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $item['new_window'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?><?php echo $is_current ? ' aria-current="page"' : ''; ?>>
						<?php lightning_child_the_mobile_fixed_nav_icon( $item['icon'] ); ?>
						<span class="lightning-child-mobile-fixed-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
					</a>
					<?php if ( 'floating' === $nav_style ) : ?>
						<span class="lightning-child-mobile-fixed-nav__item-indicator" aria-hidden="true">
							<svg class="lightning-child-mobile-fixed-nav__indicator-shape" viewBox="0 0 220 64" preserveAspectRatio="none" focusable="false" aria-hidden="true">
								<path fill="currentColor" d="M0 64V40C12 40 20 40 28 39C55 38 69 33 82 26C96 17 124 17 138 26C151 33 165 38 192 39C200 40 208 40 220 40V64Z"></path>
							</svg>
						</span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php if ( $show_menu ) : ?>
		<aside id="lightning-child-mobile-menu-drawer" class="<?php echo esc_attr( implode( ' ', $drawer_classes ) ); ?>" aria-label="<?php esc_attr_e( 'ハンバーガーメニュー', 'cni-lightning-child' ); ?>">
			<button class="lightning-child-mobile-menu-drawer__close" type="button" data-lightning-child-menu-close>
				<span aria-hidden="true">&times;</span>
				<span class="screen-reader-text"><?php esc_html_e( 'メニューを閉じる', 'cni-lightning-child' ); ?></span>
			</button>
			<div class="lightning-child-mobile-menu-drawer__content">
				<?php lightning_child_render_mobile_menu_content( $menu_source ); ?>
			</div>
		</aside>
		<div class="lightning-child-mobile-menu-drawer-backdrop" data-lightning-child-menu-backdrop hidden></div>
	<?php endif; ?>
	<?php
}
add_action( 'wp_footer', 'lightning_child_render_mobile_fixed_nav', 5 );

/**
 * Return a validated mobile fixed navigation color.
 *
 * @param string $setting_name Theme modification name.
 * @param string $default      Default color.
 * @return string
 */
function lightning_child_get_mobile_fixed_nav_color( $setting_name, $default ) {
	$color = sanitize_hex_color( get_theme_mod( $setting_name, $default ) );
	return $color ? $color : $default;
}

/**
 * Add mobile fixed navigation color variables.
 *
 * @return void
 */
function lightning_child_add_mobile_fixed_nav_css() {
	if ( ! lightning_child_is_mobile_fixed_nav_enabled() ) {
		return;
	}

	$css = sprintf(
		':root{--lightning-child-mobile-fixed-nav-background:%1$s;--lightning-child-mobile-fixed-nav-text:%2$s;--lightning-child-mobile-fixed-nav-active-background:%3$s;--lightning-child-mobile-fixed-nav-active-text:%4$s;--lightning-child-mobile-fixed-nav-border:%5$s;--lightning-child-mobile-menu-drawer-background:%6$s;--lightning-child-mobile-menu-drawer-text:%7$s;--lightning-child-mobile-fixed-nav-floating-font-size:%8$dpx;}',
		lightning_child_get_mobile_fixed_nav_color( 'lightning_child_mobile_fixed_nav_background_color', '#333333' ),
		lightning_child_get_mobile_fixed_nav_color( 'lightning_child_mobile_fixed_nav_text_color', '#ffffff' ),
		lightning_child_get_mobile_fixed_nav_color( 'lightning_child_mobile_fixed_nav_active_background_color', '#1a1a1a' ),
		lightning_child_get_mobile_fixed_nav_color( 'lightning_child_mobile_fixed_nav_active_text_color', '#ffffff' ),
		lightning_child_get_mobile_fixed_nav_color( 'lightning_child_mobile_fixed_nav_border_color', '#555555' ),
		lightning_child_get_mobile_fixed_nav_color( 'lightning_child_mobile_menu_drawer_background_color', '#ffffff' ),
		lightning_child_get_mobile_fixed_nav_color( 'lightning_child_mobile_menu_drawer_text_color', '#333333' ),
		lightning_child_get_mobile_fixed_nav_floating_font_size()
	);

	wp_add_inline_style( 'lightning-theme-style', $css );
}
add_action( 'wp_enqueue_scripts', 'lightning_child_add_mobile_fixed_nav_css', 20 );

/**
 * Load the independent hamburger drawer script.
 *
 * @return void
 */
function lightning_child_enqueue_mobile_fixed_nav_script() {
	if ( ! lightning_child_is_mobile_fixed_nav_enabled()
		|| ( ! lightning_child_should_show_mobile_menu() && 'floating' !== lightning_child_get_mobile_fixed_nav_style() ) ) {
		return;
	}

	$script_path = get_stylesheet_directory() . '/assets/js/mobile-fixed-nav.js';
	wp_enqueue_script(
		'lightning-child-mobile-fixed-nav',
		get_stylesheet_directory_uri() . '/assets/js/mobile-fixed-nav.js',
		array(),
		file_exists( $script_path ) ? filemtime( $script_path ) : null,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'lightning_child_enqueue_mobile_fixed_nav_script', 30 );
