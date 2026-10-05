<?php
/**
 * Desktop mega menu settings and presentation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize the mega menu activation mode.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_mega_menu_activation( $value ) {
	return 'all' === $value ? 'all' : 'class';
}

/**
 * Sanitize the mega menu presentation.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_mega_menu_layout( $value ) {
	return in_array( $value, array( 'cards', 'links', 'categories' ), true ) ? $value : 'cards';
}

/**
 * Sanitize the number of desktop columns.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_mega_menu_columns( $value ) {
	$value = absint( $value );
	return in_array( $value, array( 2, 3, 4 ), true ) ? $value : 4;
}

/**
 * Sanitize the desktop mega menu panel width percentage.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_mega_menu_width( $value ) {
	$value = absint( $value );
	return min( 100, max( 50, $value ) );
}

/**
 * Sanitize the card image ratio.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_mega_menu_image_ratio( $value ) {
	return in_array( $value, array( 'compact', 'standard', 'relaxed' ), true ) ? $value : 'standard';
}

/**
 * Sanitize the card hover effect.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_mega_menu_hover_effect( $value ) {
	$choices = array( 'none', 'darken', 'zoom', 'lift' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'zoom';
}

/**
 * Sanitize an optional hexadecimal color.
 *
 * An empty value intentionally falls back to Lightning's key color.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_mega_menu_optional_color( $value ) {
	if ( '' === $value || null === $value ) {
		return '';
	}

	$color = sanitize_hex_color( $value );
	return $color ? $color : '';
}

/**
 * Sanitize the child-menu title size.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_mega_menu_title_font_size( $value ) {
	$value = absint( $value );
	return min( 28, max( 12, $value ) );
}

/**
 * Register the initial mega menu controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_mega_menu( $wp_customize ) {
	$wp_customize->add_section(
		'lightning_child_mega_menu',
		array(
			'title'       => __( 'メガメニュー', 'cni-lightning-child' ),
			'description' => __( 'PC（幅1200px以上かつマウス操作）で、直下の子項目が2件以上あるグローバルナビをコンテナ幅のメガメニューとして表示します。子項目が1件の場合と、タブレット・スマートフォンではLightning標準メニューを使用します。', 'cni-lightning-child' ),
			'priority'    => 165,
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mega_menu_enabled',
		array(
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mega_menu_enabled',
		array(
			'type'        => 'checkbox',
			'settings'    => 'lightning_child_mega_menu_enabled',
			'section'     => 'lightning_child_mega_menu',
			'label'       => __( 'メガメニューを有効化', 'cni-lightning-child' ),
			'description' => __( '無効時はLightning標準のドロップダウンを使用します。', 'cni-lightning-child' ),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mega_menu_activation',
		array(
			'default'           => 'class',
			'sanitize_callback' => 'lightning_child_sanitize_mega_menu_activation',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mega_menu_activation',
		array(
			'type'        => 'select',
			'settings'    => 'lightning_child_mega_menu_activation',
			'section'     => 'lightning_child_mega_menu',
			'label'       => __( '適用対象', 'cni-lightning-child' ),
			'description' => __( '個別指定では、メニューの親項目のCSSクラスへ lightning-child-mega-menu を追加します。', 'cni-lightning-child' ),
			'choices'     => array(
				'class' => __( 'CSSクラスで個別指定', 'cni-lightning-child' ),
				'all'   => __( '子項目を持つ全親メニュー', 'cni-lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mega_menu_layout',
		array(
			'default'           => 'cards',
			'sanitize_callback' => 'lightning_child_sanitize_mega_menu_layout',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mega_menu_layout',
		array(
			'type'     => 'select',
			'settings' => 'lightning_child_mega_menu_layout',
			'section'  => 'lightning_child_mega_menu',
			'label'    => __( '表示形式', 'cni-lightning-child' ),
			'choices'  => array(
				'cards' => __( '画像カード（アイキャッチ＋タイトル）', 'cni-lightning-child' ),
				'links' => __( 'タイトル＋矢印', 'cni-lightning-child' ),
				'categories' => __( 'カテゴリ型（見出し＋一覧）', 'cni-lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mega_menu_background_color',
		array(
			'default'           => '#ffffff',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lightning_child_mega_menu_background_color',
			array(
				'label'    => __( '背景色', 'cni-lightning-child' ),
				'settings' => 'lightning_child_mega_menu_background_color',
				'section'  => 'lightning_child_mega_menu',
			)
		)
	);

	foreach (
		array(
			'category_accent_color'  => __( 'カテゴリ型：アクセント・アイコン色', 'cni-lightning-child' ),
			'category_icon_bg_color' => __( 'カテゴリ型：アイコン背景色', 'cni-lightning-child' ),
			'category_intro_bg_color' => __( 'カテゴリ型：親紹介背景色', 'cni-lightning-child' ),
			'category_heading_color' => __( 'カテゴリ型：見出し色', 'cni-lightning-child' ),
			'category_link_color'    => __( 'カテゴリ型：リンク色', 'cni-lightning-child' ),
			'category_border_color'  => __( 'カテゴリ型：区切り線色', 'cni-lightning-child' ),
		) as $setting => $label
	) {
		$wp_customize->add_setting( 'lightning_child_mega_menu_' . $setting, array( 'default' => '', 'sanitize_callback' => 'lightning_child_sanitize_mega_menu_optional_color' ) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'lightning_child_mega_menu_' . $setting, array( 'label' => $label, 'description' => __( '未指定の場合はテーマのキーカラーまたは標準色を使用します。', 'cni-lightning-child' ), 'settings' => 'lightning_child_mega_menu_' . $setting, 'section' => 'lightning_child_mega_menu' ) ) );
	}

	$wp_customize->add_setting(
		'lightning_child_mega_menu_columns',
		array(
			'default'           => 4,
			'sanitize_callback' => 'lightning_child_sanitize_mega_menu_columns',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mega_menu_columns',
		array(
			'type'        => 'select',
			'settings'    => 'lightning_child_mega_menu_columns',
			'section'     => 'lightning_child_mega_menu',
			'label'       => __( 'カラム数', 'cni-lightning-child' ),
			'description' => __( '項目が少ない場合も各項目は広がらず、指定カラムの左側から配置します。', 'cni-lightning-child' ),
			'choices'     => array(
				2 => __( '2カラム', 'cni-lightning-child' ),
				3 => __( '3カラム', 'cni-lightning-child' ),
				4 => __( '4カラム', 'cni-lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mega_menu_width',
		array(
			'default'           => 100,
			'sanitize_callback' => 'lightning_child_sanitize_mega_menu_width',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mega_menu_width',
		array(
			'type'        => 'range',
			'settings'    => 'lightning_child_mega_menu_width',
			'section'     => 'lightning_child_mega_menu',
			'label'       => __( 'メガメニュー枠の横幅', 'cni-lightning-child' ),
			'description' => __( 'カラム数とは別に、枠全体の横幅を50〜100%で指定します。枠は中央寄せで表示されます。', 'cni-lightning-child' ),
			'input_attrs' => array(
				'min'  => 50,
				'max'  => 100,
				'step' => 1,
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mega_menu_image_ratio',
		array(
			'default'           => 'standard',
			'sanitize_callback' => 'lightning_child_sanitize_mega_menu_image_ratio',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mega_menu_image_ratio',
		array(
			'type'        => 'select',
			'settings'    => 'lightning_child_mega_menu_image_ratio',
			'section'     => 'lightning_child_mega_menu',
			'label'       => __( 'カード画像比率', 'cni-lightning-child' ),
			'description' => __( '画像カード形式で使用します。すべて中央基準でトリミングします。', 'cni-lightning-child' ),
			'choices'     => array(
				'compact'  => __( 'コンパクト（2:1）', 'cni-lightning-child' ),
				'standard' => __( '標準（16:9）', 'cni-lightning-child' ),
				'relaxed'  => __( 'ゆったり（3:2）', 'cni-lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mega_menu_hover_effect',
		array(
			'default'           => 'zoom',
			'sanitize_callback' => 'lightning_child_sanitize_mega_menu_hover_effect',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mega_menu_hover_effect',
		array(
			'type'        => 'select',
			'settings'    => 'lightning_child_mega_menu_hover_effect',
			'section'     => 'lightning_child_mega_menu',
			'label'       => __( 'カードのマウスオーバー効果', 'cni-lightning-child' ),
			'description' => __( '画像カード形式で使用します。キーボード操作時も同等の状態を表示します。', 'cni-lightning-child' ),
			'choices'     => array(
				'none'   => __( 'なし', 'cni-lightning-child' ),
				'darken' => __( '画像を暗くする', 'cni-lightning-child' ),
				'zoom'   => __( '画像を拡大する', 'cni-lightning-child' ),
				'lift'   => __( 'カードを少し持ち上げる', 'cni-lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mega_menu_title_font_size',
		array(
			'default'           => 16,
			'sanitize_callback' => 'lightning_child_sanitize_mega_menu_title_font_size',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mega_menu_title_font_size',
		array(
			'type'        => 'range',
			'settings'    => 'lightning_child_mega_menu_title_font_size',
			'section'     => 'lightning_child_mega_menu',
			'label'       => __( '子メニュータイトル文字サイズ', 'cni-lightning-child' ),
			'description' => __( 'カード表示とタイトル一覧表示の両方へ反映します。', 'cni-lightning-child' ),
			'input_attrs' => array(
				'min'  => 12,
				'max'  => 28,
				'step' => 1,
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mega_menu_submenu_background_color',
		array(
			'default'           => '#ffffff',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lightning_child_mega_menu_submenu_background_color',
			array(
				'label'    => __( '孫メニュー背景色', 'cni-lightning-child' ),
				'settings' => 'lightning_child_mega_menu_submenu_background_color',
				'section'  => 'lightning_child_mega_menu',
			)
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mega_menu_submenu_text_color',
		array(
			'default'           => '#111111',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lightning_child_mega_menu_submenu_text_color',
			array(
				'label'    => __( '孫メニュー文字色', 'cni-lightning-child' ),
				'settings' => 'lightning_child_mega_menu_submenu_text_color',
				'section'  => 'lightning_child_mega_menu',
			)
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mega_menu_submenu_toggle_color',
		array(
			'default'           => '',
			'sanitize_callback' => 'lightning_child_sanitize_mega_menu_optional_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lightning_child_mega_menu_submenu_toggle_color',
			array(
				'label'       => __( '孫メニュー矢印の背景色', 'cni-lightning-child' ),
				'description' => __( '未指定の場合はLightningのキーカラーを使用します。', 'cni-lightning-child' ),
				'settings'    => 'lightning_child_mega_menu_submenu_toggle_color',
				'section'     => 'lightning_child_mega_menu',
			)
		)
	);
}
add_action( 'customize_register', 'lightning_child_customize_mega_menu' );

/**
 * Load the Customizer helper that displays the selected menu width.
 *
 * @return void
 */
function lightning_child_enqueue_mega_menu_customizer_controls() {
	$style_path  = get_stylesheet_directory() . '/assets/css/mega-menu-customizer-controls.css';
	$script_path = get_stylesheet_directory() . '/assets/js/mega-menu-customizer-controls.js';
	wp_enqueue_style(
		'lightning-child-mega-menu-customizer-controls',
		get_stylesheet_directory_uri() . '/assets/css/mega-menu-customizer-controls.css',
		array( 'customize-controls' ),
		file_exists( $style_path ) ? filemtime( $style_path ) : null
	);
	wp_enqueue_script(
		'lightning-child-mega-menu-customizer-controls',
		get_stylesheet_directory_uri() . '/assets/js/mega-menu-customizer-controls.js',
		array( 'customize-controls' ),
		file_exists( $script_path ) ? filemtime( $script_path ) : null,
		true
	);
}
add_action( 'customize_controls_enqueue_scripts', 'lightning_child_enqueue_mega_menu_customizer_controls' );

/**
 * Return whether the mega menu is enabled.
 *
 * @return bool
 */
function lightning_child_is_mega_menu_enabled() {
	return rest_sanitize_boolean( get_theme_mod( 'lightning_child_mega_menu_enabled', false ) );
}

/**
 * Determine whether wp_nav_menu() is rendering the desktop site header.
 *
 * The mobile navigation can reuse the same menu location. Checking the header's
 * public container ID prevents card markup from being added to the drawer.
 *
 * @param stdClass $args Menu arguments.
 * @return bool
 */
function lightning_child_is_header_global_menu( $args ) {
	return is_object( $args )
		&& isset( $args->container_id )
		&& 'global-nav' === $args->container_id;
}

/**
 * Mark eligible top-level parents and their direct children.
 *
 * @param WP_Post[] $items Menu items.
 * @param stdClass $args Menu arguments.
 * @return WP_Post[]
 */
function lightning_child_prepare_mega_menu_items( $items, $args ) {
	if ( ! lightning_child_is_mega_menu_enabled() || ! lightning_child_is_header_global_menu( $args ) ) {
		return $items;
	}

	$activation = lightning_child_sanitize_mega_menu_activation(
		get_theme_mod( 'lightning_child_mega_menu_activation', 'class' )
	);
	$child_counts      = array();
	$active_parent_ids = array();
	$active_child_ids  = array();

	foreach ( $items as $item ) {
		$parent_id = absint( $item->menu_item_parent );
		if ( $parent_id ) {
			$child_counts[ $parent_id ] = isset( $child_counts[ $parent_id ] )
				? $child_counts[ $parent_id ] + 1
				: 1;
		}
	}

	foreach ( $items as $item ) {
		if ( 0 !== absint( $item->menu_item_parent ) || 2 > ( $child_counts[ $item->ID ] ?? 0 ) ) {
			continue;
		}

		$item_classes = array_filter( (array) $item->classes );
		$is_active    = 'all' === $activation || in_array( 'lightning-child-mega-menu', $item_classes, true );
		if ( ! $is_active ) {
			continue;
		}

		$layout = lightning_child_get_mega_menu_parent_layout( $item->ID );
		$item->classes = array_values( array_unique( array_merge( $item_classes, array( 'lightning-child-mega-menu-parent', 'lightning-child-mega-menu-parent--' . $layout ) ) ) );
		$active_parent_ids[ $item->ID ] = true;
	}

	foreach ( $items as $item ) {
		$parent_id = absint( $item->menu_item_parent );
		if ( $parent_id && ! empty( $active_parent_ids[ $parent_id ] ) ) {
			$item_classes  = array_filter( (array) $item->classes );
			$plugin_classes = array( 'lightning-child-mega-menu-item' );
			if ( ! empty( $child_counts[ $item->ID ] ) ) {
				$plugin_classes[] = 'lightning-child-mega-menu-item-has-children';
			}
			$item->classes = array_values( array_unique( array_merge( $item_classes, $plugin_classes ) ) );
			$active_child_ids[ $item->ID ] = true;
		}
	}

	foreach ( $items as $item ) {
		$parent_id = absint( $item->menu_item_parent );
		if ( $parent_id && ! empty( $active_child_ids[ $parent_id ] ) ) {
			$item_classes  = array_filter( (array) $item->classes );
			$item->classes = array_values( array_unique( array_merge( $item_classes, array( 'lightning-child-mega-menu-grandchild' ) ) ) );
		}
	}

	/* Insert the category introduction as a normal menu item. This keeps the
	 * Lightning walker intact and prevents desktop-only markup from leaking
	 * into the mobile navigation. */
	$category_intros = array();
	foreach ( $items as $item ) {
		if ( ! in_array( 'lightning-child-mega-menu-parent--categories', (array) $item->classes, true ) ) {
			continue;
		}

		$intro                   = clone $item;
		$intro->ID               = - absint( $item->ID );
		$intro->db_id            = 0;
		$intro->menu_item_parent = $item->ID;
		$intro->title            = '';
		$intro->url              = '';
		$intro->target           = '';
		$intro->attr_title       = '';
		$intro->xfn              = '';
		$intro->classes          = array( 'menu-item', 'lightning-child-mega-menu__intro' );
		$category_intros[ $item->ID ] = $intro;
	}

	if ( $category_intros ) {
		$items_with_intros = array();
		foreach ( $items as $item ) {
			$items_with_intros[] = $item;
			if ( isset( $category_intros[ $item->ID ] ) ) {
				$items_with_intros[] = $category_intros[ $item->ID ];
			}
		}
		$items = $items_with_intros;
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'lightning_child_prepare_mega_menu_items', 20, 2 );

function lightning_child_get_mega_menu_parent_layout( $item_id ) {
	$layout = get_post_meta( absint( $item_id ), '_lightning_child_mega_menu_layout', true );
	return 'inherit' === $layout || '' === $layout ? lightning_child_sanitize_mega_menu_layout( get_theme_mod( 'lightning_child_mega_menu_layout', 'cards' ) ) : lightning_child_sanitize_mega_menu_layout( $layout );
}

function lightning_child_mega_menu_item_fields( $item_id, $item, $depth ) {
	if ( 0 === (int) $depth ) {
		$layout = get_post_meta( $item_id, '_lightning_child_mega_menu_layout', true );
		echo '<p class="description description-wide"><label>' . esc_html__( 'CNI メガメニュー：表示形式', 'cni-lightning-child' ) . '<select name="lightning_child_mega_menu_item[' . absint( $item_id ) . '][layout]">';
		foreach ( array( 'inherit' => __( '共通設定を使用', 'cni-lightning-child' ), 'cards' => __( '画像カード', 'cni-lightning-child' ), 'links' => __( 'タイトル一覧', 'cni-lightning-child' ), 'categories' => __( 'カテゴリ型', 'cni-lightning-child' ) ) as $value => $label ) { echo '<option value="' . esc_attr( $value ) . '"' . selected( $layout, $value, false ) . '>' . esc_html( $label ) . '</option>'; }
		echo '</select></label></p>';
		foreach ( array( 'eyebrow' => __( 'カテゴリ型：英語ラベル', 'cni-lightning-child' ), 'description' => __( 'カテゴリ型：説明文', 'cni-lightning-child' ), 'cta_text' => __( 'カテゴリ型：CTA文言', 'cni-lightning-child' ), 'cta_url' => __( 'カテゴリ型：CTA URL', 'cni-lightning-child' ) ) as $key => $label ) { echo '<p class="description description-wide"><label>' . esc_html( $label ) . '<input class="widefat" type="text" name="lightning_child_mega_menu_item[' . absint( $item_id ) . '][' . esc_attr( $key ) . ']" value="' . esc_attr( get_post_meta( $item_id, '_lightning_child_mega_menu_' . $key, true ) ) . '"></label></p>'; }
	}
	if ( 1 === (int) $depth ) { echo '<p class="description description-wide"><label>' . esc_html__( 'CNI メガメニュー：カテゴリアイコン（Font Awesome class）', 'cni-lightning-child' ) . '<input class="widefat" type="text" name="lightning_child_mega_menu_item[' . absint( $item_id ) . '][icon]" value="' . esc_attr( get_post_meta( $item_id, '_lightning_child_mega_menu_icon', true ) ) . '" placeholder="fas fa-users"></label></p>'; }
}
add_action( 'wp_nav_menu_item_custom_fields', 'lightning_child_mega_menu_item_fields', 10, 3 );

function lightning_child_save_mega_menu_item_fields( $menu_id, $menu_item_db_id ) {
	if ( ! current_user_can( 'edit_theme_options' ) || empty( $_POST['lightning_child_mega_menu_item'][ $menu_item_db_id ] ) ) { return; }
	$values = wp_unslash( $_POST['lightning_child_mega_menu_item'][ $menu_item_db_id ] );
	foreach ( array( 'layout', 'eyebrow', 'description', 'cta_text', 'cta_url', 'icon' ) as $key ) { if ( array_key_exists( $key, $values ) ) { update_post_meta( $menu_item_db_id, '_lightning_child_mega_menu_' . $key, 'layout' === $key ? ( 'inherit' === $values[ $key ] ? 'inherit' : lightning_child_sanitize_mega_menu_layout( $values[ $key ] ) ) : ( 'cta_url' === $key ? esc_url_raw( $values[ $key ] ) : sanitize_text_field( $values[ $key ] ) ) ); } }
}
add_action( 'wp_update_nav_menu_item', 'lightning_child_save_mega_menu_item_fields', 10, 2 );

/**
 * Use the linked content title for a card or link item.
 *
 * @param WP_Post $item Menu item.
 * @param string $fallback Existing menu label HTML.
 * @return string
 */
function lightning_child_get_mega_menu_item_title( $item, $fallback ) {
	if ( 'post_type' !== $item->type ) {
		return $fallback;
	}

	$title = get_the_title( absint( $item->object_id ) );
	return '' !== trim( $title ) ? esc_html( $title ) : $fallback;
}

/**
 * Add the linked page thumbnail and title to direct mega menu children.
 *
 * @param string $item_output Menu item HTML.
 * @param WP_Post $item Menu item.
 * @param int $depth Menu depth.
 * @param stdClass $args Menu arguments.
 * @return string
 */
function lightning_child_render_mega_menu_item( $item_output, $item, $depth, $args ) {
	if (
		1 === (int) $depth
		&& lightning_child_is_mega_menu_enabled()
		&& lightning_child_is_header_global_menu( $args )
		&& in_array( 'lightning-child-mega-menu__intro', (array) $item->classes, true )
	) {
		$parent_id  = absint( $item->menu_item_parent );
		$eyebrow    = get_post_meta( $parent_id, '_lightning_child_mega_menu_eyebrow', true );
		$description = get_post_meta( $parent_id, '_lightning_child_mega_menu_description', true );
		$cta_text   = get_post_meta( $parent_id, '_lightning_child_mega_menu_cta_text', true );
		$cta_url    = get_post_meta( $parent_id, '_lightning_child_mega_menu_cta_url', true );
		$title      = get_post_field( 'post_title', $parent_id );

		return ( $eyebrow ? '<span class="lightning-child-mega-menu__intro-eyebrow">' . esc_html( $eyebrow ) . '</span>' : '' )
			. '<span class="lightning-child-mega-menu__intro-title">' . esc_html( $title ) . '</span>'
			. ( $description ? '<p>' . esc_html( $description ) . '</p>' : '' )
			. ( $cta_text && $cta_url ? '<a class="lightning-child-mega-menu__intro-cta" href="' . esc_url( $cta_url ) . '">' . esc_html( $cta_text ) . ' <span aria-hidden="true">→</span></a>' : '' );
	}

	if (
		0 === (int) $depth
		&& lightning_child_is_mega_menu_enabled()
		&& lightning_child_is_header_global_menu( $args )
		&& in_array( 'lightning-child-mega-menu-parent', (array) $item->classes, true )
	) {
		return preg_replace( '/<\\/a>(?!.*<\\/a>)/is', '<span class="lightning-child-mega-menu__panel-pointer" aria-hidden="true"></span></a>', $item_output, 1 );
	}

	if (
		1 !== (int) $depth
		|| ! lightning_child_is_mega_menu_enabled()
		|| ! lightning_child_is_header_global_menu( $args )
		|| ! in_array( 'lightning-child-mega-menu-item', (array) $item->classes, true )
	) {
		return $item_output;
	}

	if ( ! preg_match( '/\A(.*?<a\b[^>]*>)(.*)(<\/a>.*)\z/is', $item_output, $matches ) ) {
		return $item_output;
	}

	$title  = lightning_child_get_mega_menu_item_title( $item, $matches[2] );
	$layout = lightning_child_get_mega_menu_parent_layout( $item->menu_item_parent );
	if ( 'categories' === $layout && in_array( 'lightning-child-mega-menu-item-has-children', (array) $item->classes, true ) ) {
		$icon = ' ' . get_post_meta( $item->ID, '_lightning_child_mega_menu_icon', true );
		foreach ( (array) $item->classes as $class ) {
			if ( 0 === strpos( $class, 'fa-' ) || 0 === strpos( $class, 'fas' ) || 0 === strpos( $class, 'far' ) || 0 === strpos( $class, 'fab' ) ) {
				$icon .= ' ' . sanitize_html_class( $class );
			}
		}
		$icon_html = trim( $icon ) ? '<span class="lightning-child-mega-menu__category-icon" aria-hidden="true"><i class="' . esc_attr( trim( $icon ) ) . '"></i></span>' : '';
		return $matches[1] . '<span class="lightning-child-mega-menu__category-title">' . $icon_html . '<span>' . $title . '</span></span>' . $matches[3];
	}
	$media  = '';

	if ( 'cards' === $layout ) {
		$thumbnail = '';
		if ( 'post_type' === $item->type && has_post_thumbnail( absint( $item->object_id ) ) ) {
			$thumbnail = get_the_post_thumbnail(
				absint( $item->object_id ),
				'medium_large',
				array(
					'class'    => 'lightning-child-mega-menu__image',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
		}

		$media = '<span class="lightning-child-mega-menu__media' . ( $thumbnail ? '' : ' is-placeholder' ) . '" aria-hidden="true">'
			. $thumbnail
			. '</span>';
	}

	$content = $media
		. '<span class="lightning-child-mega-menu__item-body">'
		. '<span class="lightning-child-mega-menu__title">' . $title . '</span>'
		. '<span class="lightning-child-mega-menu__arrow" aria-hidden="true">&rarr;</span>'
		. '</span>';

	return $matches[1] . $content . $matches[3];
}
add_filter( 'walker_nav_menu_start_el', 'lightning_child_render_mega_menu_item', 20, 4 );

/**
 * Add state classes used by the mega menu stylesheet.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function lightning_child_add_mega_menu_body_classes( $classes ) {
	if ( ! lightning_child_is_mega_menu_enabled() ) {
		return $classes;
	}

	$layout = lightning_child_sanitize_mega_menu_layout(
		get_theme_mod( 'lightning_child_mega_menu_layout', 'cards' )
	);
	$columns = lightning_child_sanitize_mega_menu_columns(
		get_theme_mod( 'lightning_child_mega_menu_columns', 4 )
	);
	$ratio = lightning_child_sanitize_mega_menu_image_ratio(
		get_theme_mod( 'lightning_child_mega_menu_image_ratio', 'standard' )
	);
	$hover = lightning_child_sanitize_mega_menu_hover_effect(
		get_theme_mod( 'lightning_child_mega_menu_hover_effect', 'zoom' )
	);

	$classes[] = 'lightning-child-mega-menu-enabled';
	$classes[] = 'lightning-child-mega-menu-layout-' . $layout;
	$classes[] = 'lightning-child-mega-menu-columns-' . $columns;
	$classes[] = 'lightning-child-mega-menu-ratio-' . $ratio;
	$classes[] = 'lightning-child-mega-menu-hover-' . $hover;

	return $classes;
}
add_filter( 'body_class', 'lightning_child_add_mega_menu_body_classes' );

/**
 * Load the mega menu assets only when the feature is enabled.
 *
 * @return void
 */
function lightning_child_enqueue_mega_menu_styles() {
	if ( ! lightning_child_is_mega_menu_enabled() ) {
		return;
	}

	$style_path = get_stylesheet_directory() . '/assets/css/mega-menu.css';
	wp_enqueue_style(
		'lightning-child-mega-menu',
		get_stylesheet_directory_uri() . '/assets/css/mega-menu.css',
		array( 'lightning-theme-style' ),
		file_exists( $style_path ) ? filemtime( $style_path ) : null
	);

	$background_color = sanitize_hex_color(
		get_theme_mod( 'lightning_child_mega_menu_background_color', '#ffffff' )
	);
	$submenu_background_color = sanitize_hex_color(
		get_theme_mod( 'lightning_child_mega_menu_submenu_background_color', '#ffffff' )
	);
	$submenu_text_color = sanitize_hex_color(
		get_theme_mod( 'lightning_child_mega_menu_submenu_text_color', '#111111' )
	);
	$submenu_toggle_color = lightning_child_sanitize_mega_menu_optional_color(
		get_theme_mod( 'lightning_child_mega_menu_submenu_toggle_color', '' )
	);
	$title_font_size = lightning_child_sanitize_mega_menu_title_font_size(
		get_theme_mod( 'lightning_child_mega_menu_title_font_size', 16 )
	);
	$menu_width = lightning_child_sanitize_mega_menu_width(
		get_theme_mod( 'lightning_child_mega_menu_width', 100 )
	);
	$category_colors = array();
	foreach ( array( 'accent', 'icon_bg', 'intro_bg', 'heading', 'link', 'border' ) as $color ) {
		$category_colors[ $color ] = lightning_child_sanitize_mega_menu_optional_color(
			get_theme_mod( 'lightning_child_mega_menu_category_' . $color . '_color', '' )
		);
	}
	wp_add_inline_style(
		'lightning-child-mega-menu',
		':root{'
		. '--lightning-child-mega-menu-background:' . ( $background_color ? $background_color : '#ffffff' ) . ';'
		. '--lightning-child-mega-submenu-background:' . ( $submenu_background_color ? $submenu_background_color : '#ffffff' ) . ';'
		. '--lightning-child-mega-submenu-text:' . ( $submenu_text_color ? $submenu_text_color : '#111111' ) . ';'
		. '--lightning-child-mega-submenu-toggle:' . ( $submenu_toggle_color ? $submenu_toggle_color : 'var(--vk-color-primary, #337ab7)' ) . ';'
		. '--lightning-child-mega-title-size:' . $title_font_size . 'px;'
		. '--lightning-child-mega-menu-width:' . $menu_width . '%;'
		. '--lightning-child-mega-category-accent:' . ( $category_colors['accent'] ? $category_colors['accent'] : 'var(--vk-color-primary, #337ab7)' ) . ';'
		. '--lightning-child-mega-category-icon-bg:' . ( $category_colors['icon_bg'] ? $category_colors['icon_bg'] : 'color-mix(in srgb, var(--lightning-child-mega-category-accent) 12%, transparent)' ) . ';'
		. '--lightning-child-mega-category-intro-bg:' . ( $category_colors['intro_bg'] ? $category_colors['intro_bg'] : 'color-mix(in srgb, var(--lightning-child-mega-menu-background, #fff) 88%, #f4f6f7)' ) . ';'
		. '--lightning-child-mega-category-heading:' . ( $category_colors['heading'] ? $category_colors['heading'] : '#111111' ) . ';'
		. '--lightning-child-mega-category-link:' . ( $category_colors['link'] ? $category_colors['link'] : '#1f2933' ) . ';'
		. '--lightning-child-mega-category-border:' . ( $category_colors['border'] ? $category_colors['border'] : '#e2e8ed' ) . ';'
		. '}'
	);

	$script_path = get_stylesheet_directory() . '/assets/js/mega-menu.js';
	wp_enqueue_script(
		'lightning-child-mega-menu',
		get_stylesheet_directory_uri() . '/assets/js/mega-menu.js',
		array(),
		file_exists( $script_path ) ? filemtime( $script_path ) : null,
		true
	);
	wp_localize_script(
		'lightning-child-mega-menu',
		'lightningChildMegaMenuL10n',
		array(
			'openSubmenu'  => __( '孫メニューを開く', 'cni-lightning-child' ),
			'closeSubmenu' => __( '孫メニューを閉じる', 'cni-lightning-child' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lightning_child_enqueue_mega_menu_styles', 30 );
