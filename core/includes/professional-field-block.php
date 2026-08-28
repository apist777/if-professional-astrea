<?php
/**
 * Professional Profile — single conditional-field Dynamic Block
 * (Construction Order 011).
 *
 * `single-astrea_professional.html` needs to show several optional
 * postmeta fields (資格/役職, 経歴, 学歴, 所属, 登録情報) without ever
 * leaving behind an empty, labelled section for a field the site owner
 * left blank — 02仕様書§8's "すべて任意とし、項目を埋めなければデザインが
 * 成立しない構造にはしない" applies here just as much as at save time.
 *
 * A plain `core/post-meta` Block Binding cannot do this: the binding only
 * replaces a block's inner content, it cannot remove the block itself, so
 * an unbound-when-empty Paragraph still renders as `<p></p>` (the exact
 * defect already present on archive-astrea_professional.html's
 * qualification paragraph — see the Construction Order 011 research doc
 * §2). This is the "最小の補助機構" the Construction Order 011 kickoff
 * explicitly allows for this one problem — a single small Dynamic Block,
 * not a redesign of Professional Profile's display into a large composite
 * block. It replaces the archive's bound Paragraph too, so both Archive
 * and Single share one fix instead of two.
 *
 * Deliberately reads through `ProfessionalProfile\get_profile()` (the
 * existing public read boundary, Decision 013) rather than
 * `get_post_meta()` directly, and deliberately renders no fallback/fictional
 * text when Core is inactive or the field is empty — it just returns ''
 * (Decision 021).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\ProfessionalProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** The only `field` attribute values this block understands. */
const ALLOWED_FIELDS = array( 'qualification', 'career', 'education', 'affiliation', 'registration_info' );

add_action( 'init', __NAMESPACE__ . '\\register_field_block' );

/**
 * Registers the astrea/professional-field Dynamic Block.
 *
 * @return void
 */
function register_field_block() {
	register_block_type(
		'astrea/professional-field',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_field_block',
			'editor_script_handles' => array( \Astrea\Core\EditorBlocks\SCRIPT_HANDLE ),
			'attributes'            => array(
				'field' => array(
					'type'    => 'string',
					'default' => '',
				),
				'label' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		)
	);
}

/**
 * Renders one Professional Profile field for the current post in context
 * (the Query Loop item on the Archive, or the queried object on Single),
 * or '' when there is no current astrea_professional post, the requested
 * field isn't one of ALLOWED_FIELDS, or the field's value is empty.
 *
 * @param array $attributes Block attributes: `field` (see ALLOWED_FIELDS), `label` (optional heading text).
 * @return string
 */
function render_field_block( array $attributes = array() ): string {
	$field = isset( $attributes['field'] ) ? (string) $attributes['field'] : '';
	$label = isset( $attributes['label'] ) ? (string) $attributes['label'] : '';

	if ( ! in_array( $field, ALLOWED_FIELDS, true ) ) {
		return '';
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	// Fully qualified (rather than the bare `get_profile()` call that would
	// otherwise resolve to this same, local function): WordPress core also
	// has a long-deprecated global `get_profile()` (since WP 3.0) with an
	// unrelated meaning, and phpcs's WordPress.WP.DeprecatedFunctions sniff
	// flags any bare call by that name without resolving namespaces.
	$profile = \Astrea\Core\ProfessionalProfile\get_profile( $post_id );
	if ( null === $profile ) {
		return '';
	}

	$value = (string) $profile[ $field ];
	if ( '' === trim( $value ) ) {
		return '';
	}

	$body = '<p>' . nl2br( esc_html( $value ) ) . '</p>';

	if ( '' === $label ) {
		return '<div class="wp-block-astrea-professional-field">' . $body . '</div>';
	}

	return '<div class="wp-block-astrea-professional-field"><h2>' . esc_html( $label ) . '</h2>' . $body . '</div>';
}
