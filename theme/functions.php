<?php
/**
 * ASTREA Theme bootstrap.
 *
 * Construction-phase skeleton only: registers baseline theme supports and the
 * Core-detection entry point that later feature Phases will build on. See
 * docs/specifications/05_astrea_free_v1_construction_baseline.md §4, §14.
 *
 * @package Astrea\Theme
 */

namespace Astrea\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const VERSION = '0.1.0';

add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );

/**
 * Registers theme supports and loads the theme text domain.
 *
 * @return void
 */
function setup() {
	load_theme_textdomain( 'astrea', get_template_directory() . '/languages' );

	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
}

add_action( 'init', __NAMESPACE__ . '\\register_pattern_categories' );

/**
 * Registers the pattern category used by theme/patterns/*.php
 * (Construction Order 004: price-list.php).
 *
 * @return void
 */
function register_pattern_categories() {
	register_block_pattern_category(
		'astrea',
		array( 'label' => __( 'ASTREA', 'astrea' ) )
	);
}

/**
 * Reports whether ASTREA Core is active.
 *
 * Theme code MUST call this before reading any Core-provided data, calling a
 * Core-provided function, or relying on a Core-registered Block Binding
 * source. ASTREA Core is an optional, officially recommended plugin — the
 * Theme must never fatal or break when it is absent (Decision 013, Decision
 * 021).
 *
 * @return bool True when ASTREA Core is active.
 */
function is_core_active(): bool {
	return defined( 'ASTREA_CORE_VERSION' );
}

/** User meta key recording that a user has dismissed the Core-recommendation notice. */
const CORE_NOTICE_DISMISSED_META_KEY = 'astrea_core_notice_dismissed';

const DISMISS_CORE_NOTICE_ACTION = 'astrea_dismiss_core_notice';
const DISMISS_CORE_NOTICE_NONCE  = 'astrea_dismiss_core_notice_nonce';

add_action( 'admin_notices', __NAMESPACE__ . '\\maybe_render_core_recommendation_notice' );

/**
 * Recommends installing/activating ASTREA Core when it is absent, per
 * Decision 021 ("初回有効化時、Core未導入であれば『Coreを推奨』する案内を
 * 提示してよいが、案内をスキップしてもTheme自体は機能停止しない").
 *
 * Never blocks anything: shown only on the Dashboard and the Plugins
 * screen (not on every admin screen), and can be dismissed permanently per
 * user via usermeta. Does not auto-install or auto-activate anything —
 * WordPress's own best practice is to never activate a plugin on a user's
 * behalf; the link only opens the standard Plugins screen (Construction
 * Order 007 research §4/§16).
 *
 * @return void
 */
function maybe_render_core_recommendation_notice() {
	if ( is_core_active() ) {
		return;
	}

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) ) {
		return;
	}

	if ( get_user_meta( get_current_user_id(), CORE_NOTICE_DISMISSED_META_KEY, true ) ) {
		return;
	}

	$dismiss_url = wp_nonce_url(
		add_query_arg(
			'action',
			DISMISS_CORE_NOTICE_ACTION,
			admin_url( 'admin-post.php' )
		),
		DISMISS_CORE_NOTICE_ACTION,
		DISMISS_CORE_NOTICE_NONCE
	);
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<?php
			esc_html_e(
				'ASTREA Coreを有効化すると、事務所情報・専門家プロフィール・料金・FAQ・問い合わせフォーム・SEO機能等が利用できるようになります。ASTREA Themeは単体でも安全に動作しますが、これらの機能にはASTREA Coreが必要です。',
				'astrea'
			);
			?>
		</p>
		<p>
			<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'プラグイン画面を開く', 'astrea' ); ?>
			</a>
			<a href="<?php echo esc_url( $dismiss_url ); ?>">
				<?php esc_html_e( '今後表示しない', 'astrea' ); ?>
			</a>
		</p>
	</div>
	<?php
}

add_action( 'admin_post_' . DISMISS_CORE_NOTICE_ACTION, __NAMESPACE__ . '\\handle_dismiss_core_notice' );

/**
 * Persists a per-user dismissal of the Core-recommendation notice.
 *
 * @return void
 */
function handle_dismiss_core_notice() {
	check_admin_referer( DISMISS_CORE_NOTICE_ACTION, DISMISS_CORE_NOTICE_NONCE );

	update_user_meta( get_current_user_id(), CORE_NOTICE_DISMISSED_META_KEY, '1' );

	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
}
