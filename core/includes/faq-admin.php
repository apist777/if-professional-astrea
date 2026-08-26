<?php
/**
 * FAQ — admin meta box.
 *
 * WordPress's own post-edit screen already covers 質問 (title), 回答 (block
 * editor), 表示順 (page-attributes "Order"), 公開状態 (native publish
 * workflow) and カテゴリ (the astrea_faq_category taxonomy gets its own
 * standard WordPress taxonomy meta box automatically). This file only adds
 * 重要FAQ and 関連Service, which have no native WordPress field, following
 * the same Nonce/Capability/Sanitization pattern as Office Profile /
 * Professional Profile / Price.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Faq\Admin;

use function Astrea\Core\Faq\sanitize_related_services;
use const Astrea\Core\Faq\POST_TYPE;
use const Astrea\Core\Faq\META_IS_IMPORTANT;
use const Astrea\Core\Faq\META_RELATED_SERVICES;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const NONCE_ACTION = 'astrea_faq_save_meta';
const NONCE_FIELD  = 'astrea_faq_meta_nonce';

add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_meta_boxes' );

/**
 * Registers the FAQ details meta box.
 *
 * @return void
 */
function add_meta_boxes() {
	add_meta_box(
		'astrea_faq_details',
		__( 'FAQ設定（重要FAQ・関連する取扱業務）', 'astrea-core' ),
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

	$is_important       = get_post_meta( $post->ID, META_IS_IMPORTANT, true );
	$related_services   = get_post_meta( $post->ID, META_RELATED_SERVICES, true );
	$related_services   = is_array( $related_services ) ? array_map( 'absint', $related_services ) : array();
	$available_services = \Astrea\Core\Service\get_services();
	?>
	<p>
		<label for="astrea_faq_is_important">
			<input
				type="checkbox"
				id="astrea_faq_is_important"
				name="<?php echo esc_attr( META_IS_IMPORTANT ); ?>"
				value="1"
				<?php checked( $is_important ); ?>
			/>
			<strong><?php esc_html_e( '重要FAQとして扱う', 'astrea-core' ); ?></strong>
		</label>
	</p>
	<p><strong><?php esc_html_e( '関連する取扱業務', 'astrea-core' ); ?></strong></p>
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
		<?php esc_html_e( 'すべての項目は任意です。未入力のまま公開しても問題ありません。', 'astrea-core' ); ?>
	</p>
	<?php
}

add_action( 'save_post_' . POST_TYPE, __NAMESPACE__ . '\\save_meta' );

/**
 * Saves the FAQ meta fields.
 *
 * Nonce + capability checked before any write. 関連Service goes through
 * sanitize_related_services(), the same sanitizer registered for the REST
 * schema, so an unknown/unpublished/non-Service ID posted here is dropped
 * exactly as it would be via the REST API.
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

	// Checkbox: absent from $_POST when unchecked.
	update_post_meta( $post_id, META_IS_IMPORTANT, isset( $_POST[ META_IS_IMPORTANT ] ) );

	$raw_related_services = isset( $_POST[ META_RELATED_SERVICES ] ) && is_array( $_POST[ META_RELATED_SERVICES ] )
		? array_map( 'absint', wp_unslash( $_POST[ META_RELATED_SERVICES ] ) )
		: array();

	update_post_meta( $post_id, META_RELATED_SERVICES, sanitize_related_services( $raw_related_services ) );
}
