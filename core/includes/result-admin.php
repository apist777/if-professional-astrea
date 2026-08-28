<?php
/**
 * RESULTS（実績） — admin meta box (Construction Order 010).
 *
 * WordPress's own post-edit screen already covers 実績ラベル (title) and
 * 表示順 (page-attributes "Order"). This file only adds 実績値, which has
 * no native WordPress field — same pattern as Price's meta box.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Result\Admin;

use const Astrea\Core\Result\POST_TYPE;
use const Astrea\Core\Result\META_VALUE;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const NONCE_ACTION = 'astrea_result_save_meta';
const NONCE_FIELD  = 'astrea_result_meta_nonce';

add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_meta_boxes' );

/**
 * Registers the RESULTS details meta box.
 *
 * @return void
 */
function add_meta_boxes() {
	add_meta_box(
		'astrea_result_details',
		__( '実績情報', 'astrea-core' ),
		__NAMESPACE__ . '\\render_meta_box',
		POST_TYPE,
		'normal',
		'high'
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

	$value = get_post_meta( $post->ID, META_VALUE, true );
	?>
	<p>
		<label for="astrea_result_value"><strong><?php esc_html_e( '実績値', 'astrea-core' ); ?></strong></label><br />
		<input
			type="text"
			id="astrea_result_value"
			name="<?php echo esc_attr( META_VALUE ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="widefat"
		/>
		<p class="description">
			<?php esc_html_e( '例：1,000件以上／2015年／全国対応／多数 等、数値でなくても自由に記述してください。「実績ラベル」（上部のタイトル欄）には「相談実績」「開業年」等を入力します。', 'astrea-core' ); ?>
		</p>
	</p>
	<?php
}

add_action( 'save_post_' . POST_TYPE, __NAMESPACE__ . '\\save_meta' );

/**
 * Saves the RESULTS meta field.
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

	if ( ! isset( $_POST[ META_VALUE ] ) ) {
		return;
	}

	update_post_meta( $post_id, META_VALUE, sanitize_text_field( wp_unslash( $_POST[ META_VALUE ] ) ) );
}
