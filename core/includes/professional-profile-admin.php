<?php
/**
 * Professional Profile — admin meta box.
 *
 * WordPress's own post-edit screen (title, block editor for 紹介文,
 * featured image for 写真, and the native "Order" field from
 * `page-attributes` support) already covers most of the required admin
 * UI. This file only adds the small set of Professional-specific fields
 * (資格・肩書 / 経歴 / 学歴 / 所属 / 登録情報) that don't have a native
 * WordPress field, following the same Nonce/Capability/Sanitization
 * pattern as Office Profile (Construction Order 002).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\ProfessionalProfile\Admin;

use function Astrea\Core\ProfessionalProfile\meta_sanitizers;
use const Astrea\Core\ProfessionalProfile\POST_TYPE;
use const Astrea\Core\ProfessionalProfile\META_QUALIFICATION;
use const Astrea\Core\ProfessionalProfile\META_CAREER;
use const Astrea\Core\ProfessionalProfile\META_EDUCATION;
use const Astrea\Core\ProfessionalProfile\META_AFFILIATION;
use const Astrea\Core\ProfessionalProfile\META_REGISTRATION_INFO;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const NONCE_ACTION = 'astrea_professional_save_meta';
const NONCE_FIELD  = 'astrea_professional_meta_nonce';

add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_meta_boxes' );

/**
 * Registers the Professional Profile details meta box.
 *
 * @return void
 */
function add_meta_boxes() {
	add_meta_box(
		'astrea_professional_details',
		__( '専門家情報（資格・経歴等）', 'astrea-core' ),
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

	$qualification     = get_post_meta( $post->ID, META_QUALIFICATION, true );
	$career            = get_post_meta( $post->ID, META_CAREER, true );
	$education         = get_post_meta( $post->ID, META_EDUCATION, true );
	$affiliation       = get_post_meta( $post->ID, META_AFFILIATION, true );
	$registration_info = get_post_meta( $post->ID, META_REGISTRATION_INFO, true );
	?>
	<p>
		<label for="astrea_professional_qualification"><strong><?php esc_html_e( '資格・肩書', 'astrea-core' ); ?></strong></label><br />
		<input
			type="text"
			id="astrea_professional_qualification"
			name="<?php echo esc_attr( META_QUALIFICATION ); ?>"
			value="<?php echo esc_attr( $qualification ); ?>"
			class="widefat"
		/>
	</p>
	<p>
		<label for="astrea_professional_career"><strong><?php esc_html_e( '経歴', 'astrea-core' ); ?></strong></label><br />
		<textarea
			id="astrea_professional_career"
			name="<?php echo esc_attr( META_CAREER ); ?>"
			class="widefat"
			rows="4"
		><?php echo esc_textarea( $career ); ?></textarea>
	</p>
	<p>
		<label for="astrea_professional_education"><strong><?php esc_html_e( '学歴', 'astrea-core' ); ?></strong></label><br />
		<textarea
			id="astrea_professional_education"
			name="<?php echo esc_attr( META_EDUCATION ); ?>"
			class="widefat"
			rows="3"
		><?php echo esc_textarea( $education ); ?></textarea>
	</p>
	<p>
		<label for="astrea_professional_affiliation"><strong><?php esc_html_e( '所属', 'astrea-core' ); ?></strong></label><br />
		<input
			type="text"
			id="astrea_professional_affiliation"
			name="<?php echo esc_attr( META_AFFILIATION ); ?>"
			value="<?php echo esc_attr( $affiliation ); ?>"
			class="widefat"
		/>
	</p>
	<p>
		<label for="astrea_professional_registration_info"><strong><?php esc_html_e( '登録情報', 'astrea-core' ); ?></strong></label><br />
		<input
			type="text"
			id="astrea_professional_registration_info"
			name="<?php echo esc_attr( META_REGISTRATION_INFO ); ?>"
			value="<?php echo esc_attr( $registration_info ); ?>"
			class="widefat"
		/>
	</p>
	<p class="description">
		<?php esc_html_e( 'すべての項目は任意です。未入力のまま公開しても問題ありません。', 'astrea-core' ); ?>
	</p>
	<?php
}

add_action( 'save_post_' . POST_TYPE, __NAMESPACE__ . '\\save_meta' );

/**
 * Saves the Professional Profile meta fields.
 *
 * Nonce + capability checked before any write; only the known meta keys
 * (meta_sanitizers()) are ever processed — no arbitrary meta key from
 * $_POST is accepted, and each is passed through its fixed, hardcoded
 * sanitizer (sanitize_text_field / sanitize_textarea_field only — never a
 * caller- or request-influenced callback).
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
