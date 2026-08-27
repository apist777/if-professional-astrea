<?php
/**
 * SEO — admin settings screen (Construction Order 006).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Seo\Admin;

use function Astrea\Core\Seo\get_seo_settings;
use function Astrea\Core\Seo\sanitize_settings;
use function Astrea\Core\Seo\is_known_seo_plugin_active;
use const Astrea\Core\Seo\SETTINGS_OPTION;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const PAGE_SLUG = 'astrea-core-seo';

/** Populated with the real hook suffix add_submenu_page() returns — never guessed. */
$GLOBALS['astrea_seo_page_hook'] = '';

add_action( 'admin_menu', __NAMESPACE__ . '\\add_menu' );

/**
 * Registers the SEO submenu page under the ASTREA top-level menu.
 *
 * @return void
 */
function add_menu() {
	$GLOBALS['astrea_seo_page_hook'] = add_submenu_page(
		'astrea-core',
		__( 'SEO', 'astrea-core' ),
		__( 'SEO', 'astrea-core' ),
		'manage_options',
		PAGE_SLUG,
		__NAMESPACE__ . '\\render_page'
	);
}

add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );

/**
 * Registers the SEO settings (Settings API).
 *
 * @return void
 */
function register_settings() {
	register_setting(
		'astrea_core_seo_settings_group',
		SETTINGS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_settings_proxy',
			'default'           => array(),
		)
	);
}

/**
 * Thin proxy so the registered sanitize_callback lives in the expected
 * `Astrea\Core\Seo\Admin` namespace while delegating to the shared
 * sanitizer in seo-settings.php.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function sanitize_settings_proxy( $input ): array {
	return sanitize_settings( $input );
}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_media' );

/**
 * Enqueues WordPress's own Media Library modal on this settings screen
 * only (Decision: reuse the standard Media Library, not a custom uploader).
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function enqueue_media( string $hook ) {
	if ( $GLOBALS['astrea_seo_page_hook'] !== $hook ) {
		return;
	}

	wp_enqueue_media();
	wp_add_inline_script( 'media-editor', get_media_picker_script(), 'after' );
}

/**
 * Minimal vanilla JS invoking wp.media() — the standard WordPress Media
 * Library frame, not a custom upload framework.
 *
 * @return string
 */
function get_media_picker_script(): string {
	return <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
	var button = document.getElementById('astrea-seo-og-image-select');
	if (!button || typeof wp === 'undefined' || !wp.media) {
		return;
	}
	button.addEventListener('click', function (e) {
		e.preventDefault();
		var frame = wp.media({ title: button.getAttribute('data-title'), multiple: false, library: { type: 'image' } });
		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			document.getElementById('astrea_seo_og_image_id').value = attachment.id;
			var preview = document.getElementById('astrea-seo-og-image-preview');
			if (preview) {
				preview.src = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
				preview.style.display = '';
			}
		});
		frame.open();
	});
});
JS;
}

/**
 * Renders the SEO settings page.
 *
 * @return void
 */
function render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'この画面を表示する権限がありません。', 'astrea-core' ) );
	}

	$settings = get_seo_settings();

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SEO', 'astrea-core' ); ?></h1>

		<?php if ( is_known_seo_plugin_active() ) : ?>
			<div class="notice notice-info">
				<p>
					<?php esc_html_e( '既知のSEO Pluginを検出しました。重複を避けるため、ASTREA自身のmeta description・OGP・構造化データの出力は自動的に停止されています（Search Console確認用タグは引き続き出力されます）。', 'astrea-core' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<?php settings_errors( SETTINGS_OPTION ); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'astrea_core_seo_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="astrea-seo-og-image-select"><?php esc_html_e( 'サイト標準OGP画像', 'astrea-core' ); ?></label></th>
					<td>
						<input type="hidden" id="astrea_seo_og_image_id" name="<?php echo esc_attr( SETTINGS_OPTION ); ?>[og_image_id]" value="<?php echo esc_attr( (string) $settings['og_image_id'] ); ?>" />
						<?php if ( $settings['og_image_id'] > 0 && wp_get_attachment_image_url( $settings['og_image_id'], 'thumbnail' ) ) : ?>
							<img id="astrea-seo-og-image-preview" src="<?php echo esc_url( wp_get_attachment_image_url( $settings['og_image_id'], 'thumbnail' ) ); ?>" alt="" style="max-width:150px;height:auto;display:block;margin-bottom:8px;" />
						<?php else : ?>
							<img id="astrea-seo-og-image-preview" src="" alt="" style="max-width:150px;height:auto;display:none;margin-bottom:8px;" />
						<?php endif; ?>
						<button type="button" class="button" id="astrea-seo-og-image-select" data-title="<?php esc_attr_e( 'サイト標準OGP画像を選択', 'astrea-core' ); ?>">
							<?php esc_html_e( '画像を選択', 'astrea-core' ); ?>
						</button>
						<p class="description">
							<?php esc_html_e( 'ページ個別の画像（アイキャッチ画像）が無い場合に、SNS等でシェアされた際のフォールバック画像として使用します。推奨サイズ: 1200×630px程度。', 'astrea-core' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="astrea_seo_search_console"><?php esc_html_e( 'Search Console 確認コード', 'astrea-core' ); ?></label></th>
					<td>
						<input
							type="text"
							id="astrea_seo_search_console"
							name="<?php echo esc_attr( SETTINGS_OPTION ); ?>[search_console_verification]"
							value="<?php echo esc_attr( $settings['search_console_verification'] ); ?>"
							class="regular-text"
						/>
						<p class="description">
							<?php
							printf(
								/* translators: %s: example meta tag */
								esc_html__( 'Google Search Consoleの「所有権の確認」で「HTMLタグ」方式を選ぶと表示される %s の content の値だけを貼り付けてください。', 'astrea-core' ),
								'<code>&lt;meta name="google-site-verification" content="..." /&gt;</code>'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="astrea_seo_ga4_measurement_id"><?php esc_html_e( 'GA4 測定ID', 'astrea-core' ); ?></label></th>
					<td>
						<input
							type="text"
							id="astrea_seo_ga4_measurement_id"
							name="<?php echo esc_attr( SETTINGS_OPTION ); ?>[ga4_measurement_id]"
							value="<?php echo esc_attr( $settings['ga4_measurement_id'] ); ?>"
							class="regular-text"
							placeholder="G-XXXXXXXXXX"
						/>
						<p class="description">
							<?php esc_html_e( 'Googleアナリティクス（GA4）の測定ID（G-から始まる文字列）を入力すると、サイトの各ページにアクセス計測タグを出力します。空欄のままにすると何も出力しません。', 'astrea-core' ); ?>
						</p>
						<p class="description">
							<strong><?php esc_html_e( '注意：', 'astrea-core' ); ?></strong>
							<?php esc_html_e( 'この項目を設定すると、サイト訪問者の情報がGoogleへ送信されるようになります（Googleアナリティクスの標準的な仕組みによるものです）。設定する前に、必要に応じてプライバシーポリシー等への記載をご確認ください。', 'astrea-core' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( '設定を保存', 'astrea-core' ) ); ?>
		</form>
	</div>
	<?php
}
