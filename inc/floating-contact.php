<?php
/**
 * Floating contact button settings and output.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize the floating button display mode.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_floating_contact_display_mode( $value ) {
	return 'always' === $value ? 'always' : 'scroll';
}

/**
 * Sanitize the side on which the buttons appear.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_floating_contact_side( $value ) {
	return 'left' === $value ? 'left' : 'right';
}

/**
 * Sanitize the floating button size preset.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_floating_contact_size( $value ) {
	$choices = array( 'small', 'compact', 'medium', 'large' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'medium';
}

/**
 * Sanitize a number into a specified range.
 *
 * @param mixed $value Submitted value.
 * @param int   $minimum Minimum value.
 * @param int   $maximum Maximum value.
 * @param int   $default Default value.
 * @return int
 */
function lightning_child_sanitize_floating_contact_number( $value, $minimum, $maximum, $default ) {
	if ( ! is_numeric( $value ) ) {
		return $default;
	}

	return min( $maximum, max( $minimum, absint( $value ) ) );
}

/**
 * Sanitize the scroll threshold.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_floating_contact_threshold( $value ) {
	return lightning_child_sanitize_floating_contact_number( $value, 0, 2000, 100 );
}

/**
 * Sanitize the vertical position in vh.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_floating_contact_top( $value ) {
	return lightning_child_sanitize_floating_contact_number( $value, 5, 80, 15 );
}

/**
 * Sanitize the optional smartphone-specific vertical position in vh.
 *
 * An empty value deliberately inherits the PC/tablet setting. This preserves
 * the appearance of sites configured before separate smartphone positioning
 * was introduced.
 *
 * @param mixed $value Submitted value.
 * @return int|string
 */
function lightning_child_sanitize_floating_contact_mobile_top( $value ) {
	if ( '' === $value || null === $value ) {
		return '';
	}

	return lightning_child_sanitize_floating_contact_top( $value );
}

/**
 * Sanitize the distance from the screen edge.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_floating_contact_edge( $value ) {
	return lightning_child_sanitize_floating_contact_number( $value, 0, 80, 0 );
}

/**
 * Sanitize the corner radius.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_floating_contact_radius( $value ) {
	return lightning_child_sanitize_floating_contact_number( $value, 0, 30, 5 );
}

/**
 * Sanitize a floating contact URL.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_floating_contact_url( $value ) {
	return is_string( $value ) ? esc_url_raw( trim( $value ) ) : '';
}

/**
 * Sanitize and limit a floating button label.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_floating_contact_label( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}

	return wp_html_excerpt( sanitize_text_field( $value ), 30, '' );
}

/**
 * Register floating contact settings in the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_floating_contact( $wp_customize ) {
	$section_id = 'lightning_child_floating_contact';

	$wp_customize->add_section(
		$section_id,
		array(
			'title'       => __( 'Lightning 右追尾ボタン', 'cni-lightning-child' ),
			'description' => __( 'お問い合わせやLINEへの固定ボタンを最大2つ表示します。初期状態では表示されません。', 'cni-lightning-child' ),
			'priority'    => 170,
		)
	);

	$wp_customize->add_setting(
		'lightning_child_floating_contact_enabled',
		array(
			'default'           => false,
			'sanitize_callback' => 'lightning_child_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'lightning_child_floating_contact_enabled',
		array(
			'label'   => __( '右追尾ボタンを表示する', 'cni-lightning-child' ),
			'section' => $section_id,
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_floating_contact_display_mode',
		array(
			'default'           => 'scroll',
			'sanitize_callback' => 'lightning_child_sanitize_floating_contact_display_mode',
		)
	);
	$wp_customize->add_control(
		'lightning_child_floating_contact_display_mode',
		array(
			'label'   => __( '表示タイミング', 'cni-lightning-child' ),
			'section' => $section_id,
			'type'    => 'radio',
			'choices' => array(
				'scroll' => __( 'スクロール後に表示', 'cni-lightning-child' ),
				'always' => __( '常に表示', 'cni-lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_floating_contact_scroll_threshold',
		array(
			'default'           => 100,
			'sanitize_callback' => 'lightning_child_sanitize_floating_contact_threshold',
		)
	);
	$wp_customize->add_control(
		'lightning_child_floating_contact_scroll_threshold',
		array(
			'label'       => __( '表示を開始するスクロール量', 'cni-lightning-child' ),
			'description' => __( '「スクロール後に表示」の場合に使用します。0〜2000pxで指定します。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0,
				'max'  => 2000,
				'step' => 10,
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_floating_contact_hide_mobile',
		array(
			'default'           => false,
			'sanitize_callback' => 'lightning_child_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'lightning_child_floating_contact_hide_mobile',
		array(
			'label'       => __( 'スマートフォンでは表示しない', 'cni-lightning-child' ),
			'description' => __( '画面幅767px以下で非表示にします。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_floating_contact_side',
		array(
			'default'           => 'right',
			'sanitize_callback' => 'lightning_child_sanitize_floating_contact_side',
		)
	);
	$wp_customize->add_control(
		'lightning_child_floating_contact_side',
		array(
			'label'   => __( '表示する側', 'cni-lightning-child' ),
			'section' => $section_id,
			'type'    => 'radio',
			'choices' => array(
				'right' => __( '右側', 'cni-lightning-child' ),
				'left'  => __( '左側', 'cni-lightning-child' ),
			),
		)
	);

	$number_settings = array(
		'lightning_child_floating_contact_top'    => array(
			'label'       => __( 'PC・タブレット：上からの位置', 'cni-lightning-child' ),
			'description' => __( '画面の高さに対する割合です。5〜80vhで指定します。既存サイトの設定はこの値を引き継ぎます。', 'cni-lightning-child' ),
			'default'     => 15,
			'sanitize'    => 'lightning_child_sanitize_floating_contact_top',
			'min'         => 5,
			'max'         => 80,
			'step'        => 1,
		),
		'lightning_child_floating_contact_top_mobile' => array(
			'label'       => __( 'スマートフォン：上からの位置', 'cni-lightning-child' ),
			'description' => __( '画面の高さに対する割合です。5〜80vhで指定します。空欄の場合はPC・タブレットの設定を使用します。', 'cni-lightning-child' ),
			'default'     => '',
			'sanitize'    => 'lightning_child_sanitize_floating_contact_mobile_top',
			'min'         => 5,
			'max'         => 80,
			'step'        => 1,
		),
		'lightning_child_floating_contact_edge'   => array(
			'label'       => __( '画面端からの余白', 'cni-lightning-child' ),
			'description' => __( '0〜80pxで指定します。', 'cni-lightning-child' ),
			'default'     => 0,
			'sanitize'    => 'lightning_child_sanitize_floating_contact_edge',
			'min'         => 0,
			'max'         => 80,
			'step'        => 1,
		),
		'lightning_child_floating_contact_radius' => array(
			'label'       => __( '角丸', 'cni-lightning-child' ),
			'description' => __( '0〜30pxで指定します。', 'cni-lightning-child' ),
			'default'     => 5,
			'sanitize'    => 'lightning_child_sanitize_floating_contact_radius',
			'min'         => 0,
			'max'         => 30,
			'step'        => 1,
		),
	);

	foreach ( $number_settings as $setting_name => $setting ) {
		$wp_customize->add_setting(
			$setting_name,
			array(
				'default'           => $setting['default'],
				'sanitize_callback' => $setting['sanitize'],
			)
		);
		$wp_customize->add_control(
			$setting_name,
			array(
				'label'       => $setting['label'],
				'description' => $setting['description'],
				'section'     => $section_id,
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => $setting['min'],
					'max'  => $setting['max'],
					'step' => $setting['step'],
				),
			)
		);
	}

	$wp_customize->add_setting(
		'lightning_child_floating_contact_size',
		array(
			'default'           => 'medium',
			'sanitize_callback' => 'lightning_child_sanitize_floating_contact_size',
		)
	);
	$wp_customize->add_control(
		'lightning_child_floating_contact_size',
		array(
			'label'   => __( 'ボタンサイズ', 'cni-lightning-child' ),
			'section' => $section_id,
			'type'    => 'select',
			'choices' => array(
				'small'   => __( '小', 'cni-lightning-child' ),
				'compact' => __( 'やや小さめ', 'cni-lightning-child' ),
				'medium'  => __( '標準', 'cni-lightning-child' ),
				'large'   => __( '大', 'cni-lightning-child' ),
			),
		)
	);

	$button_defaults = array(
		1 => array(
			'enabled' => true,
			'label'   => __( 'お問い合わせ', 'cni-lightning-child' ),
			'icon'    => 'fa-regular fa-envelope',
			'bg'      => '#005b32',
			'text'    => '#ffffff',
		),
		2 => array(
			'enabled' => false,
			'label'   => __( 'LINEで相談', 'cni-lightning-child' ),
			'icon'    => 'fa-brands fa-line',
			'bg'      => '#06c755',
			'text'    => '#ffffff',
		),
	);

	foreach ( $button_defaults as $index => $defaults ) {
		$prefix = 'lightning_child_floating_contact_' . $index . '_';

		$wp_customize->add_setting(
			$prefix . 'enabled',
			array(
				'default'           => $defaults['enabled'],
				'sanitize_callback' => 'lightning_child_sanitize_boolean',
			)
		);
		$wp_customize->add_control(
			$prefix . 'enabled',
			array(
				'label'   => sprintf(
					/* translators: %d: button number. */
					__( 'ボタン%dを表示する', 'cni-lightning-child' ),
					$index
				),
				'section' => $section_id,
				'type'    => 'checkbox',
			)
		);

		$text_settings = array(
			'label' => array(
				'label'    => __( 'テキスト', 'cni-lightning-child' ),
				'default'  => $defaults['label'],
				'sanitize' => 'lightning_child_sanitize_floating_contact_label',
				'desc'     => __( '縦書きで表示します（最大30文字）。', 'cni-lightning-child' ),
			),
			'icon'  => array(
				'label'    => __( 'Font Awesomeクラス', 'cni-lightning-child' ),
				'default'  => $defaults['icon'],
				'sanitize' => 'lightning_child_sanitize_font_awesome_classes',
				'desc'     => __( '例: fa-regular fa-envelope / fa-brands fa-line（HTMLタグは入力しません）', 'cni-lightning-child' ),
			),
			'url'   => array(
				'label'    => __( 'リンクURL', 'cni-lightning-child' ),
				'default'  => '',
				'sanitize' => 'lightning_child_sanitize_floating_contact_url',
				'desc'     => __( '例: /contact/、https://line.me/…、mailto:info@example.com', 'cni-lightning-child' ),
			),
		);

		foreach ( $text_settings as $suffix => $setting ) {
			$wp_customize->add_setting(
				$prefix . $suffix,
				array(
					'default'           => $setting['default'],
					'sanitize_callback' => $setting['sanitize'],
				)
			);
			$wp_customize->add_control(
				$prefix . $suffix,
				array(
					'label'       => sprintf(
						/* translators: 1: button number, 2: field label. */
						__( 'ボタン%1$d：%2$s', 'cni-lightning-child' ),
						$index,
						$setting['label']
					),
					'description' => $setting['desc'],
					'section'     => $section_id,
					'type'        => 'text',
				)
			);
		}

		$wp_customize->add_setting(
			$prefix . 'new_window',
			array(
				'default'           => 2 === $index,
				'sanitize_callback' => 'lightning_child_sanitize_boolean',
			)
		);
		$wp_customize->add_control(
			$prefix . 'new_window',
			array(
				'label'   => sprintf(
					/* translators: %d: button number. */
					__( 'ボタン%d：新しいウィンドウで開く', 'cni-lightning-child' ),
					$index
				),
				'section' => $section_id,
				'type'    => 'checkbox',
			)
		);

		$color_settings = array(
			'background_color' => array(
				'label'   => __( '背景色', 'cni-lightning-child' ),
				'default' => $defaults['bg'],
			),
			'text_color'       => array(
				'label'   => __( '文字・アイコン色', 'cni-lightning-child' ),
				'default' => $defaults['text'],
			),
		);

		foreach ( $color_settings as $suffix => $setting ) {
			$setting_name = $prefix . $suffix;
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
						'label'   => sprintf(
							/* translators: 1: button number, 2: field label. */
							__( 'ボタン%1$d：%2$s', 'cni-lightning-child' ),
							$index,
							$setting['label']
						),
						'section' => $section_id,
					)
				)
			);
		}
	}
}
add_action( 'customize_register', 'lightning_child_customize_floating_contact' );

/**
 * Determine whether the floating contact feature is enabled.
 *
 * @return bool
 */
function lightning_child_is_floating_contact_enabled() {
	return rest_sanitize_boolean( get_theme_mod( 'lightning_child_floating_contact_enabled', false ) );
}

/**
 * Return configured floating contact buttons.
 *
 * @return array<int, array<string, mixed>>
 */
function lightning_child_get_floating_contact_items() {
	$defaults = array(
		1 => array(
			'label' => __( 'お問い合わせ', 'cni-lightning-child' ),
			'icon'  => 'fa-regular fa-envelope',
		),
		2 => array(
			'label' => __( 'LINEで相談', 'cni-lightning-child' ),
			'icon'  => 'fa-brands fa-line',
		),
	);
	$items = array();

	foreach ( $defaults as $index => $default ) {
		$prefix  = 'lightning_child_floating_contact_' . $index . '_';
		$enabled = rest_sanitize_boolean( get_theme_mod( $prefix . 'enabled', 1 === $index ) );
		$label   = lightning_child_sanitize_floating_contact_label(
			get_theme_mod( $prefix . 'label', $default['label'] )
		);
		$url     = lightning_child_sanitize_floating_contact_url( get_theme_mod( $prefix . 'url', '' ) );

		if ( ! $enabled || '' === $label || '' === $url ) {
			continue;
		}

		$items[] = array(
			'index'      => $index,
			'label'      => $label,
			'icon'       => lightning_child_sanitize_font_awesome_classes(
				get_theme_mod( $prefix . 'icon', $default['icon'] )
			),
			'url'        => $url,
			'new_window' => rest_sanitize_boolean( get_theme_mod( $prefix . 'new_window', 2 === $index ) ),
		);
	}

	return $items;
}

/**
 * Return a validated floating button color.
 *
 * @param string $setting_name Theme modification name.
 * @param string $default      Default color.
 * @return string
 */
function lightning_child_get_floating_contact_color( $setting_name, $default ) {
	$color = sanitize_hex_color( get_theme_mod( $setting_name, $default ) );
	return $color ? $color : $default;
}

/**
 * Output the floating contact buttons.
 *
 * @return void
 */
function lightning_child_render_floating_contact() {
	if ( ! lightning_child_is_floating_contact_enabled() ) {
		return;
	}

	$items = lightning_child_get_floating_contact_items();
	if ( empty( $items ) ) {
		return;
	}

	$mode        = lightning_child_sanitize_floating_contact_display_mode(
		get_theme_mod( 'lightning_child_floating_contact_display_mode', 'scroll' )
	);
	$side        = lightning_child_sanitize_floating_contact_side(
		get_theme_mod( 'lightning_child_floating_contact_side', 'right' )
	);
	$size        = lightning_child_sanitize_floating_contact_size(
		get_theme_mod( 'lightning_child_floating_contact_size', 'medium' )
	);
	$hide_mobile = rest_sanitize_boolean( get_theme_mod( 'lightning_child_floating_contact_hide_mobile', false ) );
	$threshold   = lightning_child_sanitize_floating_contact_threshold(
		get_theme_mod( 'lightning_child_floating_contact_scroll_threshold', 100 )
	);
	$classes     = array(
		'lightning-child-floating-contact',
		'lightning-child-floating-contact--' . $side,
		'lightning-child-floating-contact--' . $size,
	);

	if ( 'always' === $mode ) {
		$classes[] = 'is-visible';
	}
	if ( $hide_mobile ) {
		$classes[] = 'lightning-child-floating-contact--hide-mobile';
	}
	?>
	<aside class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" aria-label="<?php esc_attr_e( 'お問い合わせ', 'cni-lightning-child' ); ?>" data-display-mode="<?php echo esc_attr( $mode ); ?>" data-scroll-threshold="<?php echo esc_attr( $threshold ); ?>">
		<?php foreach ( $items as $item ) : ?>
			<a class="lightning-child-floating-contact__link lightning-child-floating-contact__link--<?php echo esc_attr( $item['index'] ); ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $item['new_window'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
				<?php if ( '' !== $item['icon'] ) : ?>
					<i class="lightning-child-floating-contact__icon <?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></i>
				<?php endif; ?>
				<span class="lightning-child-floating-contact__label"><?php echo esc_html( $item['label'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</aside>
	<?php
}
add_action( 'wp_body_open', 'lightning_child_render_floating_contact', 30 );

/**
 * Add validated floating contact dimensions and colors.
 *
 * @return void
 */
function lightning_child_add_floating_contact_css() {
	if ( ! lightning_child_is_floating_contact_enabled() || empty( lightning_child_get_floating_contact_items() ) ) {
		return;
	}

	$top    = lightning_child_sanitize_floating_contact_top(
		get_theme_mod( 'lightning_child_floating_contact_top', 15 )
	);
	$mobile_top = lightning_child_sanitize_floating_contact_mobile_top(
		get_theme_mod( 'lightning_child_floating_contact_top_mobile', '' )
	);
	$edge   = lightning_child_sanitize_floating_contact_edge(
		get_theme_mod( 'lightning_child_floating_contact_edge', 0 )
	);
	$radius = lightning_child_sanitize_floating_contact_radius(
		get_theme_mod( 'lightning_child_floating_contact_radius', 5 )
	);
	$mobile_top_css = '' === $mobile_top ? '' : sprintf( '--lightning-child-floating-contact-top-mobile:%dvh;', $mobile_top );
	$css    = sprintf(
		':root{--lightning-child-floating-contact-top:%1$dvh;--lightning-child-floating-contact-top-desktop:%1$dvh;%2$s--lightning-child-floating-contact-edge:%3$dpx;--lightning-child-floating-contact-radius:%4$dpx;--lightning-child-floating-contact-1-bg:%5$s;--lightning-child-floating-contact-1-text:%6$s;--lightning-child-floating-contact-2-bg:%7$s;--lightning-child-floating-contact-2-text:%8$s;}',
		$top,
		$mobile_top_css,
		$edge,
		$radius,
		lightning_child_get_floating_contact_color( 'lightning_child_floating_contact_1_background_color', '#005b32' ),
		lightning_child_get_floating_contact_color( 'lightning_child_floating_contact_1_text_color', '#ffffff' ),
		lightning_child_get_floating_contact_color( 'lightning_child_floating_contact_2_background_color', '#06c755' ),
		lightning_child_get_floating_contact_color( 'lightning_child_floating_contact_2_text_color', '#ffffff' )
	);

	wp_add_inline_style( 'lightning-theme-style', $css );
}
add_action( 'wp_enqueue_scripts', 'lightning_child_add_floating_contact_css', 20 );

/**
 * Load the scroll observer only for scroll-triggered buttons.
 *
 * @return void
 */
function lightning_child_enqueue_floating_contact_script() {
	if ( ! lightning_child_is_floating_contact_enabled()
		|| empty( lightning_child_get_floating_contact_items() )
		|| 'scroll' !== lightning_child_sanitize_floating_contact_display_mode(
			get_theme_mod( 'lightning_child_floating_contact_display_mode', 'scroll' )
		) ) {
		return;
	}

	$script_path = get_stylesheet_directory() . '/assets/js/floating-contact.js';
	wp_enqueue_script(
		'lightning-child-floating-contact',
		get_stylesheet_directory_uri() . '/assets/js/floating-contact.js',
		array(),
		file_exists( $script_path ) ? filemtime( $script_path ) : null,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'lightning_child_enqueue_floating_contact_script', 30 );
