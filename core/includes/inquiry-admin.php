<?php
/**
 * Inquiry — admin screen (Construction Order 005).
 *
 * A deliberately custom, read-only screen — NOT WordPress's native
 * edit.php/post.php (see inquiry.php's file header for why: an inquiry is
 * an immutable record of what a visitor submitted, and no search feature
 * is offered, per Decision 006).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Inquiry\Admin;

use function Astrea\Core\Inquiry\get_all;
use function Astrea\Core\Inquiry\get_exportable;
use function Astrea\Core\Inquiry\get_contact_settings;
use function Astrea\Core\Inquiry\sanitize_settings;
use function Astrea\Core\Inquiry\set_read;
use function Astrea\Core\Inquiry\count_unread;
use function Astrea\Core\Inquiry\request_email_confirmation;
use function Astrea\Core\Inquiry\get_pending_email;
use const Astrea\Core\Inquiry\SETTINGS_OPTION;
use const Astrea\Core\Inquiry\POST_TYPE;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const PAGE_SLUG            = 'astrea-core-contact';
const CAPABILITY           = 'manage_options';
const MARK_READ_ACTION     = 'astrea_contact_mark_read';
const MARK_READ_NONCE      = 'astrea_contact_mark_read_nonce';
const EXPORT_ACTION        = 'astrea_export_inquiries';
const EXPORT_NONCE_ACTION  = 'astrea_export_inquiries_nonce';
const REQUEST_EMAIL_ACTION = 'astrea_request_contact_email_confirmation';
const REQUEST_EMAIL_NONCE  = 'astrea_request_contact_email_nonce';

add_action( 'admin_menu', __NAMESPACE__ . '\\add_menu' );

/**
 * Registers the Contact submenu page under the ASTREA top-level menu,
 * with an unread-count badge (Decision 005: "未確認件数を表示").
 *
 * @return void
 */
function add_menu() {
	$unread = count_unread();
	$title  = __( '問い合わせ', 'astrea-core' );

	if ( $unread > 0 ) {
		$title .= sprintf( ' <span class="awaiting-mod"><span class="pending-count">%d</span></span>', $unread );
	}

	add_submenu_page(
		'astrea-core',
		__( '問い合わせ', 'astrea-core' ),
		$title,
		CAPABILITY,
		PAGE_SLUG,
		__NAMESPACE__ . '\\render_page'
	);
}

add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );

/**
 * Registers the Contact settings (Settings API).
 *
 * @return void
 */
function register_settings() {
	register_setting(
		'astrea_core_contact_settings_group',
		SETTINGS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_settings_and_reschedule',
			'default'           => array(),
		)
	);
}

/**
 * Sanitizes settings and reschedules the digest cron when relevant
 * settings changed (digest_time / notification_timing).
 *
 * @param mixed $input Raw input.
 * @return array
 */
function sanitize_settings_and_reschedule( $input ): array {
	$sanitized = sanitize_settings( $input );

	\Astrea\Core\Inquiry\reschedule_digest_cron();

	return $sanitized;
}

/**
 * Renders the Contact admin page: settings, notification email
 * confirmation status, and the read-only inquiry list.
 *
 * @return void
 */
function render_page() {
	if ( ! current_user_can( CAPABILITY ) ) {
		wp_die( esc_html__( 'この画面を表示する権限がありません。', 'astrea-core' ) );
	}

	$settings = get_contact_settings();
	$pending  = get_pending_email();

	if ( isset( $_GET['astrea_contact_email_confirmed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag.
		$confirmed = '1' === $_GET['astrea_contact_email_confirmed']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		printf(
			'<div class="notice %s"><p>%s</p></div>',
			$confirmed ? 'notice-success' : 'notice-error',
			$confirmed
				? esc_html__( '通知先メールアドレスを確認しました。', 'astrea-core' )
				: esc_html__( '確認リンクが無効か、有効期限が切れています。もう一度お試しください。', 'astrea-core' )
		);
	}

	if ( isset( $_GET['astrea_contact_email_requested'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html__( '確認メールを送信しました。', 'astrea-core' ) );
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( '問い合わせ', 'astrea-core' ); ?></h1>

		<h2><?php esc_html_e( '設定', 'astrea-core' ); ?></h2>
		<?php settings_errors( SETTINGS_OPTION ); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'astrea_core_contact_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="astrea_contact_retention_days"><?php esc_html_e( '保存期間', 'astrea-core' ); ?></label></th>
					<td>
						<select id="astrea_contact_retention_days" name="<?php echo esc_attr( SETTINGS_OPTION ); ?>[retention_days]">
							<?php foreach ( array( 10, 30, 60, 90 ) as $days ) : ?>
								<option value="<?php echo esc_attr( (string) $days ); ?>" <?php selected( $settings['retention_days'], $days ); ?>>
									<?php
									printf(
										/* translators: %d: number of days */
										esc_html__( '%d日', 'astrea-core' ),
										absint( $days )
									);
									?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'この期間を過ぎた問い合わせは自動的に削除されます。', 'astrea-core' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( '通知タイミング', 'astrea-core' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="<?php echo esc_attr( SETTINGS_OPTION ); ?>[notification_timing]" value="immediate" <?php checked( $settings['notification_timing'], 'immediate' ); ?> />
								<?php esc_html_e( '即時通知', 'astrea-core' ); ?>
							</label>
							<br />
							<label>
								<input type="radio" name="<?php echo esc_attr( SETTINGS_OPTION ); ?>[notification_timing]" value="digest" <?php checked( $settings['notification_timing'], 'digest' ); ?> />
								<?php esc_html_e( '指定時刻にまとめて通知', 'astrea-core' ); ?>
							</label>
							<input type="time" name="<?php echo esc_attr( SETTINGS_OPTION ); ?>[digest_time]" value="<?php echo esc_attr( $settings['digest_time'] ); ?>" />
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( '電話番号欄', 'astrea-core' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( SETTINGS_OPTION ); ?>[phone_enabled]" value="1" <?php checked( $settings['phone_enabled'] ); ?> />
							<?php esc_html_e( 'フォームに表示する', 'astrea-core' ); ?>
						</label>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( SETTINGS_OPTION ); ?>[phone_required]" value="1" <?php checked( $settings['phone_required'] ); ?> />
							<?php esc_html_e( '必須にする', 'astrea-core' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( '件名欄', 'astrea-core' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( SETTINGS_OPTION ); ?>[subject_enabled]" value="1" <?php checked( $settings['subject_enabled'] ); ?> />
							<?php esc_html_e( 'フォームに表示する', 'astrea-core' ); ?>
						</label>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( SETTINGS_OPTION ); ?>[subject_required]" value="1" <?php checked( $settings['subject_required'] ); ?> />
							<?php esc_html_e( '必須にする', 'astrea-core' ); ?>
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button( __( '設定を保存', 'astrea-core' ) ); ?>
		</form>

		<h2><?php esc_html_e( '通知先メールアドレス', 'astrea-core' ); ?></h2>
		<p>
			<?php if ( '' !== $settings['notification_email'] ) : ?>
				<?php
				printf(
					/* translators: %s: confirmed email address */
					esc_html__( '現在の通知先: %s（確認済み）', 'astrea-core' ),
					esc_html( $settings['notification_email'] )
				);
				?>
			<?php else : ?>
				<?php esc_html_e( '通知先メールアドレスは未設定です。', 'astrea-core' ); ?>
			<?php endif; ?>
		</p>
		<?php if ( '' !== $pending ) : ?>
			<p>
				<?php
				printf(
					/* translators: %s: pending email address */
					esc_html__( '確認待ち: %s（メール内のリンクをクリックすると切り替わります）', 'astrea-core' ),
					esc_html( $pending )
				);
				?>
			</p>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( REQUEST_EMAIL_ACTION ); ?>" />
			<?php wp_nonce_field( REQUEST_EMAIL_ACTION, REQUEST_EMAIL_NONCE ); ?>
			<p>
				<label for="astrea_contact_new_email"><?php esc_html_e( '新しい通知先メールアドレス', 'astrea-core' ); ?></label>
				<input type="email" id="astrea_contact_new_email" name="new_email" value="" class="regular-text" />
				<?php submit_button( __( '確認メールを送信', 'astrea-core' ), 'secondary', 'submit', false ); ?>
			</p>
		</form>

		<h2>
			<?php
			printf(
				/* translators: %d: number of stored inquiries */
				esc_html__( '問い合わせ一覧（%d件）', 'astrea-core' ),
				count( get_all() )
			);
			?>
		</h2>
		<p>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . EXPORT_ACTION ), EXPORT_NONCE_ACTION ) ); ?>">
				<?php esc_html_e( 'CSVをダウンロード', 'astrea-core' ); ?>
			</a>
		</p>
		<?php render_inquiry_table(); ?>
	</div>
	<?php
}

/**
 * Renders the read-only inquiry list. Each row's full message is behind a
 * native <details> disclosure (same accessible, JS-free pattern used for
 * FAQ in Construction Order 004).
 *
 * @return void
 */
function render_inquiry_table() {
	$inquiries = get_all();

	if ( empty( $inquiries ) ) {
		echo '<p>' . esc_html__( '問い合わせはまだありません。', 'astrea-core' ) . '</p>';
		return;
	}
	?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( '状態', 'astrea-core' ); ?></th>
				<th scope="col"><?php esc_html_e( '受信日時', 'astrea-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'お名前', 'astrea-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'メール', 'astrea-core' ); ?></th>
				<th scope="col"><?php esc_html_e( '件名 / 内容', 'astrea-core' ); ?></th>
				<th scope="col"><?php esc_html_e( '操作', 'astrea-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $inquiries as $inquiry ) : ?>
				<tr>
					<td><?php echo $inquiry['is_read'] ? esc_html__( '既読', 'astrea-core' ) : '<strong>' . esc_html__( '未読', 'astrea-core' ) . '</strong>'; ?></td>
					<td><?php echo esc_html( get_date_from_gmt( $inquiry['received_at'], 'Y-m-d H:i' ) ); ?></td>
					<td><?php echo esc_html( $inquiry['name'] ); ?></td>
					<td><?php echo esc_html( $inquiry['email'] ); ?></td>
					<td>
						<details>
							<summary><?php echo esc_html( $inquiry['subject'] ); ?></summary>
							<p><?php echo nl2br( esc_html( $inquiry['message'] ) ); ?></p>
							<?php if ( '' !== $inquiry['phone'] ) : ?>
								<p><?php echo esc_html( sprintf( /* translators: %s: phone number */ __( '電話: %s', 'astrea-core' ), $inquiry['phone'] ) ); ?></p>
							<?php endif; ?>
						</details>
					</td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="<?php echo esc_attr( MARK_READ_ACTION ); ?>" />
							<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) $inquiry['id'] ); ?>" />
							<input type="hidden" name="is_read" value="<?php echo $inquiry['is_read'] ? '0' : '1'; ?>" />
							<?php wp_nonce_field( MARK_READ_ACTION, MARK_READ_NONCE ); ?>
							<button type="submit" class="button-link">
								<?php echo $inquiry['is_read'] ? esc_html__( '未読に戻す', 'astrea-core' ) : esc_html__( '既読にする', 'astrea-core' ); ?>
							</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

add_action( 'admin_post_' . MARK_READ_ACTION, __NAMESPACE__ . '\\handle_mark_read' );

/**
 * Toggles an inquiry's read state.
 *
 * @return void
 */
function handle_mark_read() {
	if ( ! current_user_can( CAPABILITY ) ) {
		wp_die( esc_html__( 'この操作を行う権限がありません。', 'astrea-core' ) );
	}

	check_admin_referer( MARK_READ_ACTION, MARK_READ_NONCE );

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$is_read = ! empty( $_POST['is_read'] );

	if ( $post_id > 0 ) {
		set_read( $post_id, $is_read );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=' . PAGE_SLUG ) );
	exit;
}

add_action( 'admin_post_' . REQUEST_EMAIL_ACTION, __NAMESPACE__ . '\\handle_request_email_confirmation' );

/**
 * Starts (or resends) the notification-email confirmation flow.
 *
 * @return void
 */
function handle_request_email_confirmation() {
	if ( ! current_user_can( CAPABILITY ) ) {
		wp_die( esc_html__( 'この操作を行う権限がありません。', 'astrea-core' ) );
	}

	check_admin_referer( REQUEST_EMAIL_ACTION, REQUEST_EMAIL_NONCE );

	$new_email = isset( $_POST['new_email'] ) ? sanitize_email( wp_unslash( $_POST['new_email'] ) ) : '';

	if ( '' !== $new_email ) {
		request_email_confirmation( $new_email );
	}

	wp_safe_redirect( add_query_arg( 'astrea_contact_email_requested', '1', admin_url( 'admin.php?page=' . PAGE_SLUG ) ) );
	exit;
}

add_action( 'admin_post_' . EXPORT_ACTION, __NAMESPACE__ . '\\handle_export' );

/**
 * Streams the retained inquiries as a CSV file (Decision 006).
 *
 * @return void
 */
function handle_export() {
	if ( ! current_user_can( CAPABILITY ) ) {
		wp_die( esc_html__( 'この操作を行う権限がありません。', 'astrea-core' ) );
	}

	check_admin_referer( EXPORT_NONCE_ACTION );

	$inquiries = get_exportable();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="astrea-inquiries-' . gmdate( 'Ymd-His' ) . '.csv"' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- streaming a direct HTTP file download via php://output, not filesystem access; WP_Filesystem is not applicable. UTF-8 BOM, for Excel/Japanese text compatibility.

	fputcsv(
		$out,
		array(
			__( '受信日時', 'astrea-core' ),
			__( 'お名前', 'astrea-core' ),
			__( 'メール', 'astrea-core' ),
			__( '電話', 'astrea-core' ),
			__( '件名', 'astrea-core' ),
			__( '内容', 'astrea-core' ),
		)
	);

	foreach ( $inquiries as $inquiry ) {
		fputcsv(
			$out,
			array_map(
				__NAMESPACE__ . '\\sanitize_csv_cell',
				array(
					get_date_from_gmt( $inquiry['received_at'], 'Y-m-d H:i:s' ),
					$inquiry['name'],
					$inquiry['email'],
					$inquiry['phone'],
					$inquiry['subject'],
					$inquiry['message'],
				)
			)
		);
	}

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- streaming a direct HTTP file download via php://output, not filesystem access.
	exit;
}

/**
 * Neutralizes CSV formula injection: a cell beginning with =, +, -, or @
 * is interpreted as a formula by Excel/Sheets. Prefixing with a single
 * quote forces it to be read as plain text.
 *
 * @param string $value Raw cell value.
 * @return string
 */
function sanitize_csv_cell( string $value ): string {
	if ( preg_match( '/^[=+\-@]/', $value ) ) {
		return "'" . $value;
	}

	return $value;
}
