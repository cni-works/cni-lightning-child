<?php
/**
 * CNI Site Functions installation guidance.
 *
 * @package CniLightningChild
 */

namespace CniWorks\CniLightningChild\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shows an opt-in installer for the independent CNI Site Functions plugin.
 */
final class Site_Functions_Onboarding {

	const API_VERSION       = '2022-11-28';
	const OWNER             = 'cni-works';
	const REPOSITORY        = 'CNI-Site-Functions';
	const SLUG              = 'cni-site-functions';
	const PLUGIN_FILE       = 'cni-site-functions/cni-site-functions.php';
	const LEGACY_PLUGIN_FILE = 'CNI-Site-Functions/cni-site-functions.php';
	const CACHE_KEY         = 'cni_lightning_child_site_functions_release';
	const RESULT_KEY_PREFIX = 'cni_lightning_child_site_functions_result_';

	const STATE_MISSING         = 'missing';
	const STATE_INACTIVE        = 'inactive';
	const STATE_ACTIVE          = 'active';
	const STATE_LEGACY_INACTIVE = 'legacy_inactive';
	const STATE_LEGACY_ACTIVE   = 'legacy_active';

	/** @var self|null */
	private static $instance = null;

	/** Registers admin-only hooks once. */
	public static function register() {
		if ( null !== self::$instance ) {
			return;
		}

		self::$instance = new self();
		add_action( 'admin_notices', array( self::$instance, 'render_notice' ) );
		add_action( 'admin_notices', array( self::$instance, 'render_theme_functions_guidance' ) );
		add_action( 'admin_enqueue_scripts', array( self::$instance, 'enqueue_theme_functions_guidance_assets' ) );
		add_action( 'admin_post_cni_lightning_child_install_site_functions', array( self::$instance, 'handle_install' ) );
		add_action( 'admin_post_cni_lightning_child_activate_site_functions', array( self::$instance, 'handle_activation' ) );
	}

	/** Prevent direct construction. */
	private function __construct() {}

	/**
	 * Determines the plugin state from exact plugin basenames.
	 *
	 * Legacy detection deliberately takes precedence to prevent duplicate code
	 * from being loaded on case-sensitive production filesystems.
	 *
	 * @param array<string, mixed> $plugins               Installed plugins.
	 * @param string[]             $active_plugins        Site-active plugins.
	 * @param array<string, mixed> $network_active_plugins Network-active plugins.
	 * @return string
	 */
	public static function determine_state( array $plugins, array $active_plugins, array $network_active_plugins = array() ) {
		$legacy_active = in_array( self::LEGACY_PLUGIN_FILE, $active_plugins, true ) || isset( $network_active_plugins[ self::LEGACY_PLUGIN_FILE ] );
		if ( $legacy_active ) {
			return self::STATE_LEGACY_ACTIVE;
		}

		if ( isset( $plugins[ self::LEGACY_PLUGIN_FILE ] ) ) {
			return self::STATE_LEGACY_INACTIVE;
		}

		$canonical_active = in_array( self::PLUGIN_FILE, $active_plugins, true ) || isset( $network_active_plugins[ self::PLUGIN_FILE ] );
		if ( $canonical_active ) {
			return self::STATE_ACTIVE;
		}

		return isset( $plugins[ self::PLUGIN_FILE ] ) ? self::STATE_INACTIVE : self::STATE_MISSING;
	}

	/** @return string */
	public function get_plugin_state() {
		$this->load_plugin_api();

		$network_active = is_multisite() ? (array) get_site_option( 'active_sitewide_plugins', array() ) : array();

		return self::determine_state(
			get_plugins(),
			(array) get_option( 'active_plugins', array() ),
			$network_active
		);
	}

	/** Displays action feedback and onboarding on the selected admin screens. */
	public function render_notice() {
		if ( ! $this->is_supported_screen() ) {
			return;
		}

		$this->render_action_result();

		if ( ! current_user_can( 'install_plugins' ) && ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$state = $this->get_plugin_state();
		if ( self::STATE_ACTIVE === $state ) {
			return;
		}

		if ( self::STATE_LEGACY_ACTIVE === $state || self::STATE_LEGACY_INACTIVE === $state ) {
			$message = self::STATE_LEGACY_ACTIVE === $state
				? __( '旧開発版 CNI Site Functions（大文字フォルダ）が有効です。正式版との重複を防ぐため、自動インストールは停止しています。旧開発版を無効化・削除してから再確認してください。', 'cni-lightning-child' )
				: __( '旧開発版 CNI Site Functions（大文字フォルダ）が残っています。正式版との重複を防ぐため、自動インストールは停止しています。旧開発版を削除してから再確認してください。', 'cni-lightning-child' );
			$this->print_notice( 'warning', $message );
			return;
		}

		if ( self::STATE_INACTIVE === $state ) {
			if ( current_user_can( 'activate_plugins' ) ) {
				$this->print_action_notice(
					__( 'CNI Site Functionsはインストール済みですが、まだ有効化されていません。', 'cni-lightning-child' ),
					'cni_lightning_child_activate_site_functions',
					'cni_activate_site_functions',
					__( 'CNI Site Functionsを有効化', 'cni-lightning-child' )
				);
			}
			return;
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$release = $this->get_release();
		if ( is_wp_error( $release ) ) {
			$this->print_notice(
				'warning',
				__( 'CNI Site Functionsの最新版をGitHubから確認できませんでした。時間をおいて再確認するか、Releaseページから手動で導入してください。', 'cni-lightning-child' ),
				$this->repository_url()
			);
			return;
		}

		$this->print_action_notice(
			sprintf(
				/* translators: %s: plugin version. */
				__( 'サイト固有PHPを子テーマ更新から分離するため、CNI Site Functions %sを導入できます。インストール後の有効化は別操作です。', 'cni-lightning-child' ),
				esc_html( $release['version'] )
			),
			'cni_lightning_child_install_site_functions',
			'cni_install_site_functions',
			__( 'GitHubからインストール', 'cni-lightning-child' )
		);
	}

	/**
	 * Guides future maintainers away from update-managed functions.php edits.
	 *
	 * This notice is intentionally separate from the onboarding notice. It is
	 * shown only while this child theme's functions.php is selected, and only
	 * when the canonical CNI Site Functions plugin is already installed.
	 */
	public function render_theme_functions_guidance() {
		$plugin_state = $this->get_theme_functions_guidance_state();
		if ( ! $plugin_state ) {
			return;
		}

		$is_active = self::STATE_ACTIVE === $plugin_state;
		$message   = $is_active
			? __( 'このサイトの固有PHPは、子テーマのfunctions.phpではなく「CNI Site Functions」で管理します。ここへ直接追加したコードは、子テーマ更新時に失われる可能性があります。', 'cni-lightning-child' )
			: __( 'CNI Site Functionsはインストールされていますが、現在は無効です。サイト固有PHPをfunctions.phpへ直接追加せず、プラグインを有効化してCNI Site Functionsで管理してください。', 'cni-lightning-child' );
		$url       = $is_active
			? admin_url( 'options-general.php?page=cni-site-functions' )
			: admin_url( 'plugins.php' );
		$label     = $is_active
			? __( 'CNI Site Functionsを開く', 'cni-lightning-child' )
			: __( 'プラグイン一覧を開く', 'cni-lightning-child' );

		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'サイト固有PHPの編集場所について', 'cni-lightning-child' ) . '</strong></p>';
		echo '<p>' . esc_html( $message ) . '</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></p></div>';
	}

	/** Enqueues the editor-only overlay without modifying WordPress core. */
	public function enqueue_theme_functions_guidance_assets() {
		$plugin_state = $this->get_theme_functions_guidance_state();
		if ( ! $plugin_state ) {
			return;
		}

		$style_path = get_stylesheet_directory() . '/assets/css/admin-site-functions-guidance.css';
		$script_path = get_stylesheet_directory() . '/assets/js/admin-site-functions-guidance.js';
		if ( ! is_readable( $style_path ) || ! is_readable( $script_path ) ) {
			return;
		}

		$is_active = self::STATE_ACTIVE === $plugin_state;
		wp_enqueue_style(
			'cni-lightning-child-site-functions-guidance',
			get_stylesheet_directory_uri() . '/assets/css/admin-site-functions-guidance.css',
			array(),
			(string) filemtime( $style_path )
		);
		wp_enqueue_script(
			'cni-lightning-child-site-functions-guidance',
			get_stylesheet_directory_uri() . '/assets/js/admin-site-functions-guidance.js',
			array(),
			(string) filemtime( $script_path ),
			true
		);
		wp_localize_script(
			'cni-lightning-child-site-functions-guidance',
			'cniLightningChildSiteFunctionsGuidance',
			array(
				'heading'      => $is_active
					? __( 'サイト固有PHPは CNI Site Functions で管理してください', 'cni-lightning-child' )
					: __( 'CNI Site Functionsを有効化してください', 'cni-lightning-child' ),
				'description'  => $is_active
					? __( 'この子テーマのfunctions.phpに直接追加したコードは、子テーマ更新時に失われる可能性があります。このサイト固有のPHPコードは「CNI Site Functions」へ記載してください。', 'cni-lightning-child' )
					: __( 'CNI Site Functionsはインストール済みですが、現在は無効です。サイト固有PHPをfunctions.phpへ直接追加せず、プラグインを有効化してからCNI Site Functionsへ記載してください。', 'cni-lightning-child' ),
				'primaryLabel' => $is_active
					? __( 'CNI Site Functionsを開く', 'cni-lightning-child' )
					: __( 'プラグイン一覧を開く', 'cni-lightning-child' ),
				'primaryUrl'   => $is_active
					? admin_url( 'options-general.php?page=cni-site-functions' )
					: admin_url( 'plugins.php' ),
				'revealLabel'  => __( 'functions.php の内容を確認する', 'cni-lightning-child' ),
			)
		);
	}

	/** Handles an explicit install request without activating the plugin. */
	public function handle_install() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_die(
				esc_html__( 'このプラグインをインストールする権限がありません。', 'cni-lightning-child' ),
				esc_html__( '権限がありません', 'cni-lightning-child' ),
				array( 'response' => 403 )
			);
		}

		check_admin_referer( 'cni_install_site_functions' );
		$state = $this->get_plugin_state();

		if ( self::STATE_LEGACY_ACTIVE === $state || self::STATE_LEGACY_INACTIVE === $state ) {
			$this->redirect_with_result( 'error', __( '旧開発版が存在するため、正式版をインストールしませんでした。', 'cni-lightning-child' ) );
		}

		if ( self::STATE_ACTIVE === $state || self::STATE_INACTIVE === $state ) {
			$this->redirect_with_result( 'warning', __( 'CNI Site Functionsは既にインストールされています。', 'cni-lightning-child' ) );
		}

		$release = $this->get_release( true );
		if ( is_wp_error( $release ) ) {
			$this->redirect_with_result( 'error', $this->release_error_message( $release ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$temp_file = download_url( $release['package'], 30 );
		if ( is_wp_error( $temp_file ) ) {
			$this->redirect_with_result( 'error', __( 'Release ZIPをダウンロードできませんでした。', 'cni-lightning-child' ) );
		}

		$error_message = '';
		try {
			$verified = $this->verify_downloaded_asset( $temp_file, $release );
			if ( is_wp_error( $verified ) ) {
				$error_message = $this->release_error_message( $verified );
			}

			if ( '' === $error_message ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				$skin      = new \Automatic_Upgrader_Skin();
				$upgrader  = new \Plugin_Upgrader( $skin );
				$installed = $upgrader->install( $temp_file );

				if ( true !== $installed ) {
					$error         = is_wp_error( $installed ) ? $installed : $skin->get_errors();
					$error_message = is_wp_error( $error ) && $error->has_errors()
						? $error->get_error_message()
						: __( 'WordPressがプラグインをインストールできませんでした。', 'cni-lightning-child' );
				}
			}

			if ( '' === $error_message ) {
				wp_clean_plugins_cache( true );
				$this->load_plugin_api();
				$plugins = get_plugins();
				if ( ! isset( $plugins[ self::PLUGIN_FILE ] ) ) {
					$error_message = __( 'インストール先のフォルダまたはメインファイルを確認できませんでした。プラグインは有効化されていません。', 'cni-lightning-child' );
				}
			}
		} finally {
			if ( is_string( $temp_file ) && file_exists( $temp_file ) ) {
				wp_delete_file( $temp_file );
			}
		}

		if ( '' !== $error_message ) {
			$this->redirect_with_result( 'error', $error_message );
		}

		$this->redirect_with_result( 'success', __( 'CNI Site Functionsをインストールしました。まだ有効化されていません。次の「有効化」操作を行ってください。', 'cni-lightning-child' ) );
	}

	/** Handles a separate, explicit activation request. */
	public function handle_activation() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die(
				esc_html__( 'このプラグインを有効化する権限がありません。', 'cni-lightning-child' ),
				esc_html__( '権限がありません', 'cni-lightning-child' ),
				array( 'response' => 403 )
			);
		}

		check_admin_referer( 'cni_activate_site_functions' );
		$state = $this->get_plugin_state();

		if ( self::STATE_LEGACY_ACTIVE === $state || self::STATE_LEGACY_INACTIVE === $state ) {
			$this->redirect_with_result( 'error', __( '旧開発版が存在するため、正式版を有効化しませんでした。', 'cni-lightning-child' ) );
		}

		if ( self::STATE_ACTIVE === $state ) {
			$this->redirect_with_result( 'success', __( 'CNI Site Functionsは既に有効です。', 'cni-lightning-child' ) );
		}

		if ( self::STATE_INACTIVE !== $state ) {
			$this->redirect_with_result( 'error', __( '正式版CNI Site Functionsが見つかりません。', 'cni-lightning-child' ) );
		}

		$this->load_plugin_api();
		$result = activate_plugin( self::PLUGIN_FILE, '', false, false );

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_result( 'error', $result->get_error_message() );
		}

		$this->redirect_with_result( 'success', __( 'CNI Site Functionsを有効化しました。', 'cni-lightning-child' ) );
	}

	/**
	 * Returns the latest validated release, with positive and negative caching.
	 *
	 * @param bool $force Force a fresh GitHub request.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function get_release( $force = false ) {
		if ( $force ) {
			delete_site_transient( self::CACHE_KEY );
		} else {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( is_array( $cached ) && isset( $cached['state'] ) ) {
				if ( 'success' === $cached['state'] && isset( $cached['release'] ) && is_array( $cached['release'] ) ) {
					return $cached['release'];
				}

				return new \WP_Error( 'github_cached_failure', __( 'GitHub Release情報を取得できませんでした。', 'cni-lightning-child' ) );
			}
		}

		$release = $this->request_release();
		if ( is_wp_error( $release ) ) {
			set_site_transient( self::CACHE_KEY, array( 'state' => 'failure' ), HOUR_IN_SECONDS );
			return $release;
		}

		set_site_transient(
			self::CACHE_KEY,
			array(
				'state'   => 'success',
				'release' => $release,
			),
			12 * HOUR_IN_SECONDS
		);

		return $release;
	}

	/** @return array<string, mixed>|\WP_Error */
	public function request_release() {
		$response = wp_safe_remote_get(
			'https://api.github.com/repos/' . self::OWNER . '/' . self::REPOSITORY . '/releases/latest',
			array(
				'timeout'     => 5,
				'redirection' => 3,
				'headers'     => array(
					'Accept'               => 'application/vnd.github+json',
					'X-GitHub-Api-Version' => self::API_VERSION,
					'User-Agent'           => 'CNI-Lightning-Child-Site-Functions-Onboarding/1.0; ' . home_url( '/' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'github_request_failed', __( 'GitHub APIへ接続できませんでした。', 'cni-lightning-child' ) );
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error( 'github_http_error', __( 'GitHub APIから正常な応答を取得できませんでした。', 'cni-lightning-child' ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_Error( 'github_invalid_json', __( 'GitHub APIの応答を解析できませんでした。', 'cni-lightning-child' ) );
		}

		return $this->validate_release_data( $body );
	}

	/**
	 * Validates that one stable release asset belongs to the configured repository.
	 *
	 * @param array<string, mixed> $body GitHub release response.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function validate_release_data( array $body ) {
		if ( ! empty( $body['draft'] ) || ! empty( $body['prerelease'] ) || ! isset( $body['tag_name'] ) || ! is_string( $body['tag_name'] ) ) {
			return new \WP_Error( 'invalid_release', __( '公開済みの正式Releaseではありません。', 'cni-lightning-child' ) );
		}

		$matches = array();
		if ( ! preg_match( '/^v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/', $body['tag_name'], $matches ) ) {
			return new \WP_Error( 'invalid_release_tag', __( 'Releaseタグの形式が正しくありません。', 'cni-lightning-child' ) );
		}

		$version       = $matches[1] . '.' . $matches[2] . '.' . $matches[3];
		$asset_name    = self::SLUG . '-' . $version . '.zip';
		$matching      = array();
		$assets        = isset( $body['assets'] ) && is_array( $body['assets'] ) ? $body['assets'] : array();

		foreach ( $assets as $asset ) {
			if ( is_array( $asset ) && isset( $asset['name'], $asset['state'] ) && $asset_name === $asset['name'] && 'uploaded' === $asset['state'] ) {
				$matching[] = $asset;
			}
		}

		if ( 1 !== count( $matching ) ) {
			return new \WP_Error( 'invalid_release_asset', __( '正しいRelease ZIPを一意に特定できませんでした。', 'cni-lightning-child' ) );
		}

		$asset = $matching[0];
		if ( ! isset( $asset['browser_download_url'] ) || ! $this->is_valid_asset_url( $asset['browser_download_url'], $body['tag_name'], $asset_name ) ) {
			return new \WP_Error( 'invalid_asset_url', __( 'Release ZIPの取得元を検証できませんでした。', 'cni-lightning-child' ) );
		}

		if ( ! isset( $asset['digest'] ) || ! is_string( $asset['digest'] ) || ! preg_match( '/^sha256:[a-f0-9]{64}$/i', $asset['digest'] ) ) {
			return new \WP_Error( 'missing_asset_digest', __( 'Release ZIPのSHA-256 digestを取得できないため、インストールを中止しました。', 'cni-lightning-child' ) );
		}

		if ( ! isset( $asset['size'] ) || ! is_numeric( $asset['size'] ) || 0 >= (int) $asset['size'] ) {
			return new \WP_Error( 'invalid_asset_size', __( 'Release ZIPのファイルサイズを検証できませんでした。', 'cni-lightning-child' ) );
		}

		return array(
			'version' => $version,
			'package' => $asset['browser_download_url'],
			'digest'  => strtolower( substr( $asset['digest'], 7 ) ),
			'size'    => (int) $asset['size'],
		);
	}

	/**
	 * Verifies the downloaded bytes before WordPress unpacks them.
	 *
	 * @param string               $file    Temporary ZIP path.
	 * @param array<string, mixed> $release Validated release metadata.
	 * @return true|\WP_Error
	 */
	public function verify_downloaded_asset( $file, array $release ) {
		if ( ! is_string( $file ) || ! is_readable( $file ) || ! isset( $release['digest'], $release['size'] ) ) {
			return new \WP_Error( 'asset_not_readable', __( 'ダウンロードしたRelease ZIPを読み取れません。', 'cni-lightning-child' ) );
		}

		$actual_size = filesize( $file );
		$actual_hash = hash_file( 'sha256', $file );
		if ( false === $actual_size || (int) $release['size'] !== (int) $actual_size ) {
			return new \WP_Error( 'asset_size_mismatch', __( 'Release ZIPのファイルサイズがGitHubの情報と一致しません。', 'cni-lightning-child' ) );
		}

		if ( ! is_string( $actual_hash ) || ! hash_equals( strtolower( (string) $release['digest'] ), strtolower( $actual_hash ) ) ) {
			return new \WP_Error( 'asset_digest_mismatch', __( 'Release ZIPのSHA-256 digestが一致しないため、インストールを中止しました。', 'cni-lightning-child' ) );
		}

		return true;
	}

	/** @return bool */
	private function is_supported_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		return $screen && in_array( $screen->id, array( 'dashboard', 'themes', 'plugins' ), true );
	}

	/**
	 * Returns the canonical plugin state only in this child theme's functions editor.
	 *
	 * @return string|false Active/inactive state, or false outside the target.
	 */
	private function get_theme_functions_guidance_state() {
		if ( ! current_user_can( 'edit_themes' ) || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'theme-editor' !== $screen->id ) {
			return false;
		}

		$file = isset( $_GET['file'] ) && is_string( $_GET['file'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_text_field( wp_unslash( $_GET['file'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';
		$theme = isset( $_GET['theme'] ) && is_string( $_GET['theme'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( wp_unslash( $_GET['theme'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: get_stylesheet();

		if ( 'functions.php' !== $file || get_stylesheet() !== $theme ) {
			return false;
		}

		$this->load_plugin_api();
		$plugins = get_plugins();
		if ( ! isset( $plugins[ self::PLUGIN_FILE ] ) ) {
			return false;
		}

		return is_plugin_active( self::PLUGIN_FILE ) ? self::STATE_ACTIVE : self::STATE_INACTIVE;
	}

	/** @param mixed $url URL. @param string $tag Tag. @param string $filename Filename. @return bool */
	private function is_valid_asset_url( $url, $tag, $filename ) {
		if ( ! is_string( $url ) || ! wp_http_validate_url( $url ) ) {
			return false;
		}

		$expected_path = '/' . self::OWNER . '/' . self::REPOSITORY . '/releases/download/' . $tag . '/' . $filename;
		return 'https' === wp_parse_url( $url, PHP_URL_SCHEME )
			&& 'github.com' === strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) )
			&& $expected_path === wp_parse_url( $url, PHP_URL_PATH )
			&& null === wp_parse_url( $url, PHP_URL_QUERY )
			&& null === wp_parse_url( $url, PHP_URL_FRAGMENT );
	}

	/** @return void */
	private function load_plugin_api() {
		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	/** @param string $message Message. @param string $action Action. @param string $nonce Nonce. @param string $label Label. */
	private function print_action_notice( $message, $action, $nonce, $label ) {
		echo '<div class="notice notice-info"><p>' . wp_kses_post( $message ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0 0 1em">';
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
		wp_nonce_field( $nonce );
		submit_button( $label, 'primary', 'submit', false );
		echo '</form></div>';
	}

	/** @param string $type Notice type. @param string $message Message. @param string $link Optional link. */
	private function print_notice( $type, $message, $link = '' ) {
		$allowed = array( 'success', 'warning', 'error', 'info' );
		$type    = in_array( $type, $allowed, true ) ? $type : 'info';
		echo '<div class="notice notice-' . esc_attr( $type ) . '"><p>' . esc_html( $message );
		if ( $link ) {
			echo ' <a href="' . esc_url( $link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'GitHub Releaseを開く', 'cni-lightning-child' ) . '</a>';
		}
		echo '</p></div>';
	}

	/** Renders and consumes one user's action result. */
	private function render_action_result() {
		$key    = self::RESULT_KEY_PREFIX . get_current_user_id();
		$result = get_transient( $key );
		if ( ! is_array( $result ) || empty( $result['message'] ) ) {
			return;
		}

		delete_transient( $key );
		$this->print_notice( isset( $result['type'] ) ? $result['type'] : 'info', $result['message'] );
	}

	/** Stores safe action feedback and ends the request. */
	private function redirect_with_result( $type, $message ) {
		$allowed = array( 'success', 'warning', 'error', 'info' );
		$type    = in_array( $type, $allowed, true ) ? $type : 'info';
		$message = is_string( $message ) && '' !== trim( $message )
			? sanitize_text_field( $message )
			: __( '処理を完了できませんでした。', 'cni-lightning-child' );

		set_transient(
			self::RESULT_KEY_PREFIX . get_current_user_id(),
			array( 'type' => $type, 'message' => $message ),
			5 * MINUTE_IN_SECONDS
		);

		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/** @param \WP_Error $error Error. @return string */
	private function release_error_message( $error ) {
		$allowed_codes = array(
			'missing_asset_digest',
			'asset_size_mismatch',
			'asset_digest_mismatch',
			'asset_not_readable',
		);

		return in_array( $error->get_error_code(), $allowed_codes, true )
			? $error->get_error_message()
			: __( 'GitHub Releaseを安全に検証できなかったため、インストールを中止しました。', 'cni-lightning-child' );
	}

	/** @return string */
	private function repository_url() {
		return 'https://github.com/' . self::OWNER . '/' . self::REPOSITORY . '/releases';
	}
}

Site_Functions_Onboarding::register();
