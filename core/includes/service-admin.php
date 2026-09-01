<?php
/**
 * Service — admin meta box (Construction Order 016D-R1).
 *
 * WordPress's own post-edit screen already covers 名称 (title),
 * 説明 (editor) and 表示順 (page-attributes "Order"). This file only adds
 * Icon選択, which has no native WordPress field — same meta-box pattern
 * already established by result-admin.php / price-admin.php.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Service\Admin;

use function Astrea\Core\IconSystem\allowed_slugs;
use const Astrea\Core\Service\POST_TYPE;
use const Astrea\Core\Service\META_ICON;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const NONCE_ACTION = 'astrea_service_save_meta';
const NONCE_FIELD  = 'astrea_service_meta_nonce';

/**
 * Human-readable labels for each allowed icon slug, for the admin
 * `<select>` only — the stored value is always the slug itself.
 *
 * @return array<string,string>
 */
function icon_labels(): array {
	return array(
		'company'     => __( '会社（company）', 'astrea-core' ),
		'contract'    => __( '契約書（contract）', 'astrea-core' ),
		'document'    => __( '書類（document）', 'astrea-core' ),
		'folder'      => __( 'フォルダ／汎用（folder）', 'astrea-core' ),
		'inheritance' => __( '相続（inheritance）', 'astrea-core' ),
		'permit'      => __( '許可・許認可（permit）', 'astrea-core' ),
	);
}

add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_meta_boxes' );

/**
 * Registers the Service details meta box.
 *
 * @return void
 */
function add_meta_boxes() {
	add_meta_box(
		'astrea_service_details',
		__( '取扱業務の表示設定', 'astrea-core' ),
		__NAMESPACE__ . '\\render_meta_box',
		POST_TYPE,
		'side',
		'default'
	);
}

/**
 * Renders the meta box fields.
 *
 * @param \WP_Post $post Current post.
 * @return void
 */
function render_meta_box( \WP_Post $post ) {
	wp_nonce_field( NONCE_ACTION, NONCE_FIELD );

	$current = get_post_meta( $post->ID, META_ICON, true );
	$labels  = icon_labels();
	?>
	<p>
		<label for="astrea_service_icon"><strong><?php esc_html_e( 'アイコン', 'astrea-core' ); ?></strong></label><br />
		<select id="astrea_service_icon" name="<?php echo esc_attr( META_ICON ); ?>" class="widefat">
			<?php foreach ( allowed_slugs( 'service' ) as $slug ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current, $slug ); ?>>
					<?php echo esc_html( $labels[ $slug ] ?? $slug ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'HOME・アーカイブ等で取扱業務と一緒に表示する装飾アイコンです。内容と合うものを選んでください。迷った場合は「フォルダ／汎用」のままで問題ありません。', 'astrea-core' ); ?>
		</p>
	</p>
	<?php
}

add_action( 'save_post_' . POST_TYPE, __NAMESPACE__ . '\\save_meta' );

/**
 * Saves the Service icon meta field.
 *
 * @param int $post_id Post ID being saved.
 * @return void
 */
function save_meta( int $post_id ) {
	if ( ! isset( $_POST[ NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ NONCE_FIELD ] ) ), NONCE_ACTION ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST[ META_ICON ] ) ) {
		return;
	}

	// update_post_meta() does not run the register_post_meta()
	// sanitize_callback (that only fires for the REST API) — this classic
	// meta-box save path re-validates via the same allowed_slugs()
	// whitelist so a hand-crafted POST cannot store an arbitrary string.
	$value = sanitize_text_field( wp_unslash( $_POST[ META_ICON ] ) );

	if ( ! in_array( $value, allowed_slugs( 'service' ), true ) ) {
		$value = \Astrea\Core\IconSystem\default_slug( 'service' );
	}

	update_post_meta( $post_id, META_ICON, $value );
}
