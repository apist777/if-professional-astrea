<?php
/**
 * CASE（対応事例） — admin meta box (Construction Order 010).
 *
 * WordPress's own post-edit screen already covers タイトル (title), 本文
 * (block editor), 概要 (excerpt), 画像 (featured image), 表示順
 * (page-attributes "Order") and 公開状態 (native publish workflow). This
 * file only adds 関連する取扱業務 (related Service), which has no native
 * WordPress field — same Nonce/Capability/Sanitization pattern as FAQ's
 * meta box (Construction Order 004).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\CaseStudy\Admin;

use function Astrea\Core\CaseStudy\sanitize_related_services;
use const Astrea\Core\CaseStudy\POST_TYPE;
use const Astrea\Core\CaseStudy\META_RELATED_SERVICES;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const NONCE_ACTION = 'astrea_case_save_meta';
const NONCE_FIELD  = 'astrea_case_meta_nonce';

add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_meta_boxes' );

/**
 * Registers the CASE details meta box.
 *
 * @return void
 */
function add_meta_boxes() {
	add_meta_box(
		'astrea_case_details',
		__( '関連する取扱業務', 'astrea-core' ),
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

	$related_services   = get_post_meta( $post->ID, META_RELATED_SERVICES, true );
	$related_services   = is_array( $related_services ) ? array_map( 'absint', $related_services ) : array();
	$available_services = \Astrea\Core\Service\get_services();
	?>
	<?php if ( empty( $available_services ) ) : ?>
		<p class="description"><?php esc_html_e( '公開済みの取扱業務がまだありません。', 'astrea-core' ); ?></p>
	<?php else : ?>
		<?php foreach ( $available_services as $service ) : ?>
			<label style="display:block;">
				<input
					type="checkbox"
					name="<?php echo esc_attr( META_RELATED_SERVICES ); ?>[]"
					value="<?php echo esc_attr( (string) $service['id'] ); ?>"
					<?php checked( in_array( $service['id'], $related_services, true ) ); ?>
				/>
				<?php echo esc_html( $service['name'] ); ?>
			</label>
		<?php endforeach; ?>
	<?php endif; ?>
	<p class="description">
		<?php esc_html_e( '任意です。未選択のまま公開しても問題ありません。', 'astrea-core' ); ?>
	</p>
	<?php
}

add_action( 'save_post_' . POST_TYPE, __NAMESPACE__ . '\\save_meta' );

/**
 * Saves the CASE meta fields.
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

	$raw_related_services = isset( $_POST[ META_RELATED_SERVICES ] ) && is_array( $_POST[ META_RELATED_SERVICES ] )
		? array_map( 'absint', wp_unslash( $_POST[ META_RELATED_SERVICES ] ) )
		: array();

	update_post_meta( $post_id, META_RELATED_SERVICES, sanitize_related_services( $raw_related_services ) );
}
