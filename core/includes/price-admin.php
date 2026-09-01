<?php
/**
 * Price — admin meta box.
 *
 * Same Nonce/Capability/Sanitization pattern as Professional Profile
 * (Construction Order 003) and Office Profile (Construction Order 002).
 * WordPress's own post-edit screen already covers 表示名 (title) and 表示順
 * (the native "Order" field from `page-attributes`); this file only adds
 * 金額 / 補足 / 料金グループ, which have no native WordPress field.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Price\Admin;

use function Astrea\Core\Price\meta_sanitizers;
use function Astrea\Core\IconSystem\allowed_slugs;
use const Astrea\Core\Price\POST_TYPE;
use const Astrea\Core\Price\META_AMOUNT;
use const Astrea\Core\Price\META_NOTES;
use const Astrea\Core\Price\META_GROUP;
use const Astrea\Core\Price\META_ICON;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const NONCE_ACTION = 'astrea_price_save_meta';
const NONCE_FIELD  = 'astrea_price_meta_nonce';

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
		'price-yen'   => __( '料金（price-yen）', 'astrea-core' ),
	);
}

add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_meta_boxes' );

/**
 * Registers the Price details meta box.
 *
 * @return void
 */
function add_meta_boxes() {
	add_meta_box(
		'astrea_price_details',
		__( '料金情報', 'astrea-core' ),
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

	$amount      = get_post_meta( $post->ID, META_AMOUNT, true );
	$notes       = get_post_meta( $post->ID, META_NOTES, true );
	$group       = get_post_meta( $post->ID, META_GROUP, true );
	$icon        = get_post_meta( $post->ID, META_ICON, true );
	$icon_labels = icon_labels();
	?>
	<p>
		<label for="astrea_price_amount"><strong><?php esc_html_e( '金額', 'astrea-core' ); ?></strong></label><br />
		<textarea
			id="astrea_price_amount"
			name="<?php echo esc_attr( META_AMOUNT ); ?>"
			class="widefat"
			rows="3"
		><?php echo esc_textarea( $amount ); ?></textarea>
		<p class="description">
			<?php esc_html_e( '例：3万円〜／月額5,000円／初回相談無料／個別見積 等、自由に記述してください。', 'astrea-core' ); ?>
		</p>
	</p>
	<p>
		<label for="astrea_price_notes"><strong><?php esc_html_e( '補足（実費・追加費用等）', 'astrea-core' ); ?></strong></label><br />
		<textarea
			id="astrea_price_notes"
			name="<?php echo esc_attr( META_NOTES ); ?>"
			class="widefat"
			rows="3"
		><?php echo esc_textarea( $notes ); ?></textarea>
	</p>
	<p>
		<label for="astrea_price_group"><strong><?php esc_html_e( '料金グループ', 'astrea-core' ); ?></strong></label><br />
		<input
			type="text"
			id="astrea_price_group"
			name="<?php echo esc_attr( META_GROUP ); ?>"
			value="<?php echo esc_attr( $group ); ?>"
			class="widefat"
		/>
	</p>
	<p>
		<label for="astrea_price_icon"><strong><?php esc_html_e( 'アイコン', 'astrea-core' ); ?></strong></label><br />
		<select id="astrea_price_icon" name="<?php echo esc_attr( META_ICON ); ?>" class="widefat">
			<?php foreach ( allowed_slugs( 'price' ) as $slug ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $icon, $slug ); ?>>
					<?php echo esc_html( $icon_labels[ $slug ] ?? $slug ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="description">
		<?php esc_html_e( 'すべての項目は任意です。未入力のまま公開しても問題ありません。', 'astrea-core' ); ?>
	</p>
	<?php
}

add_action( 'save_post_' . POST_TYPE, __NAMESPACE__ . '\\save_meta' );

/**
 * Saves the Price meta fields.
 *
 * Nonce + capability checked before any write; only the known meta keys
 * (meta_sanitizers()) are ever processed, each through its fixed,
 * hardcoded sanitizer.
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

	foreach ( meta_sanitizers() as $meta_key => $sanitize_callback ) {
		if ( ! isset( $_POST[ $meta_key ] ) ) {
			continue;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $sanitize_callback is always sanitize_text_field or sanitize_textarea_field (see meta_sanitizers()), never request-controlled.
		$value = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $meta_key ] ) );
		update_post_meta( $post_id, $meta_key, $value );
	}
}
