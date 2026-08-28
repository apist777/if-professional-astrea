<?php
/**
 * Setup — admin UI (Construction Order 007).
 *
 * Deliberately not a new admin screen: renders as a section at the top of
 * the existing "ASTREA" Office Profile page (see office-profile-admin.php),
 * per docs/research/2026-08-27_construction_order_007_research.md §5 —
 * a separate Setup Wizard / dedicated Setup page was evaluated and rejected
 * in favour of reusing the entry point 02仕様書 §22 already describes.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Setup\Admin;

use function Astrea\Core\Setup\get_checklist_items;
use function Astrea\Core\Setup\has_meaningful_navigation;
use function Astrea\Core\Setup\is_home_configured;
use const Astrea\Core\Setup\GENERATE_PAGES_ACTION;
use const Astrea\Core\Setup\GENERATE_PAGES_NONCE;
use const Astrea\Core\Setup\GENERATE_NAVIGATION_ACTION;
use const Astrea\Core\Setup\GENERATE_NAVIGATION_NONCE;
use const Astrea\Core\Setup\GENERATE_HOME_ACTION;
use const Astrea\Core\Setup\GENERATE_HOME_NONCE;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'astrea_core_office_profile_page_top', __NAMESPACE__ . '\\render_checklist' );

/**
 * Renders the setup checklist and the two optional generation actions.
 *
 * @return void
 */
function render_checklist() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$items = get_checklist_items();
	?>
	<div class="astrea-core-setup-checklist" style="max-width: 720px; margin-bottom: 2em;">
		<h2><?php esc_html_e( 'セットアップ状況', 'astrea-core' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'すべて埋める必要はありません。「推奨」は集客サイトとして機能するために特に有用な項目、「任意」はサイトによって使わなくても問題ない項目です。', 'astrea-core' ); ?>
		</p>
		<ul style="list-style: none; margin-left: 0;">
			<?php foreach ( $items as $item ) : ?>
				<li>
					<?php if ( $item['done'] ) : ?>
						<span aria-hidden="true">&#10003;</span>
						<span><?php esc_html_e( '完了', 'astrea-core' ); ?>:</span>
					<?php else : ?>
						<span aria-hidden="true">&#9675;</span>
						<span>
							<?php
							echo 'recommended' === $item['priority']
								? esc_html__( '推奨', 'astrea-core' )
								: esc_html__( '任意', 'astrea-core' );
							?>
							:
						</span>
					<?php endif; ?>
					<?php if ( $item['done'] ) : ?>
						<?php echo esc_html( $item['label'] ); ?>
					<?php else : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<h3 id="astrea-setup-generate-home"><?php esc_html_e( 'ホームページの作成', 'astrea-core' ); ?></h3>
		<?php if ( isset( $_GET['astrea_setup_home_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of a message already produced by a nonce-verified admin-post handler. ?>
			<div class="notice notice-warning inline">
				<p><?php echo esc_html( rawurldecode( wp_unslash( (string) $_GET['astrea_setup_home_error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- escaped on output immediately above; source is our own handler's fixed, translated error strings, not free-form user input. ?></p>
			</div>
		<?php elseif ( isset( $_GET['astrea_setup_home_generated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success inline">
				<p><?php esc_html_e( 'ホームページを作成し、固定フロントページとして設定しました。', 'astrea-core' ); ?></p>
			</div>
		<?php endif; ?>
		<?php if ( is_home_configured() ) : ?>
			<p class="description">
				<?php esc_html_e( '既にホームページが設定されています。', 'astrea-core' ); ?>
			</p>
		<?php else : ?>
			<p class="description">
				<?php esc_html_e( 'Hero・取扱業務・専門家紹介・料金・FAQ・ご相談の流れ・CTAを組み合わせたホームページを作成し、サイトの固定フロントページとして設定します。既にホームページ（固定フロントページ）が設定されている場合は作成しません。', 'astrea-core' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( GENERATE_HOME_ACTION ); ?>" />
				<?php wp_nonce_field( GENERATE_HOME_ACTION, GENERATE_HOME_NONCE ); ?>
				<?php submit_button( __( 'ホームページを作成する', 'astrea-core' ), 'primary', 'submit', false ); ?>
			</form>
		<?php endif; ?>

		<h3 id="astrea-setup-generate-pages"><?php esc_html_e( '基本ページの作成', 'astrea-core' ); ?></h3>
		<p class="description">
			<?php esc_html_e( '「事務所概要」「料金」「お問い合わせ」の下書きページをまとめて作成します。取扱業務・専門家紹介・FAQは既に一覧ページがあるため対象に含みません。既に作成済みの場合、再度実行しても重複作成や上書きはしません。', 'astrea-core' ); ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( GENERATE_PAGES_ACTION ); ?>" />
			<?php wp_nonce_field( GENERATE_PAGES_ACTION, GENERATE_PAGES_NONCE ); ?>
			<?php submit_button( __( '基本ページを作成する', 'astrea-core' ), 'secondary', 'submit', false ); ?>
		</form>

		<h3 id="astrea-setup-generate-navigation"><?php esc_html_e( 'メニュー（Navigation）の作成', 'astrea-core' ); ?></h3>
		<?php render_navigation_result_notice(); ?>
		<?php if ( has_meaningful_navigation() ) : ?>
			<p class="description">
				<?php esc_html_e( '既にNavigationが存在するため、この機能は表示されません。', 'astrea-core' ); ?>
			</p>
		<?php else : ?>
			<p class="description">
				<?php esc_html_e( '取扱業務・専門家紹介・FAQ・作成済みの基本ページへのリンクを含むNavigationを1件作成し、ヘッダー・フッターが未編集であればそのまま反映します。既にNavigationがある場合は作成しません。', 'astrea-core' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( GENERATE_NAVIGATION_ACTION ); ?>" />
				<?php wp_nonce_field( GENERATE_NAVIGATION_ACTION, GENERATE_NAVIGATION_NONCE ); ?>
				<?php submit_button( __( '基本メニューを作成する', 'astrea-core' ), 'secondary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Renders the result of the last "基本メニューを作成する" submission
 * (Construction Order 013): whether the Navigation was generated, and —
 * separately, per Header/Footer — whether it was actually connected or
 * left alone because that Template Part is already customized. Never
 * describes a site owner's own customization as an error.
 *
 * @return void
 */
function render_navigation_result_notice() {
	if ( isset( $_GET['astrea_setup_navigation_exists'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of a message already produced by a nonce-verified admin-post handler.
		?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( '既にNavigationが存在するため、新しく作成しませんでした。', 'astrea-core' ); ?></p>
		</div>
		<?php
		return;
	}

	if ( ! isset( $_GET['astrea_setup_navigation_generated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$slot_labels = array(
		'header' => __( 'ヘッダー', 'astrea-core' ),
		'footer' => __( 'フッター', 'astrea-core' ),
	);

	$connected_slots = array();
	$custom_slots    = array();

	foreach ( $slot_labels as $slot => $label ) {
		$query_key = 'astrea_setup_navigation_' . $slot;
		$result    = isset( $_GET[ $query_key ] ) ? sanitize_key( wp_unslash( (string) $_GET[ $query_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of a message already produced by a nonce-verified admin-post handler.

		if ( 'connected' === $result ) {
			$connected_slots[] = $label;
		} elseif ( 'skipped_custom' === $result ) {
			$custom_slots[] = $label;
		}
	}
	?>
	<div class="notice notice-success inline">
		<p><?php esc_html_e( 'Navigationを作成しました。', 'astrea-core' ); ?></p>
		<?php if ( ! empty( $connected_slots ) ) : ?>
			<p>
				<?php
				printf(
					/* translators: %s: comma-separated list of "ヘッダー"/"フッター" */
					esc_html__( '%sに反映しました。', 'astrea-core' ),
					esc_html( implode( '・', $connected_slots ) )
				);
				?>
			</p>
		<?php endif; ?>
		<?php if ( ! empty( $custom_slots ) ) : ?>
			<p>
				<?php
				printf(
					/* translators: %s: comma-separated list of "ヘッダー"/"フッター" */
					esc_html__( '%sは既にカスタマイズされているため、自動では反映しませんでした。サイトエディターでNavigationブロックに手動で割り当ててください。', 'astrea-core' ),
					esc_html( implode( '・', $custom_slots ) )
				);
				?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
