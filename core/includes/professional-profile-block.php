<?php
/**
 * Professional Profile — representative Dynamic Block (Construction Order
 * 008, Decision 028).
 *
 * Chosen over a Block Bindings Source extension after comparing both
 * against Decision 013, WordPress standard APIs, Core/Theme responsibility
 * separation, Core-inactive Fallback, maintenance cost, and Accessibility
 * (see the Construction Order 008 report for the full comparison):
 *
 * Block Bindings can only fall back per-field (a single unbound Paragraph/
 * Image keeps its own static content when the source returns null) — it
 * has no mechanism to hide an entire multi-field composite (photo + name +
 * qualification + bio) as one unit. Decision 028's HOME-teaser rule
 * requires exactly that whole-section self-hide when there is no
 * representative to show, which only a Dynamic Block (already the
 * established pattern for `astrea/price-list` and `astrea/faq-list`) can
 * express. Core-inactive Fallback, Accessibility (plain semantic HTML) and
 * Maintenance cost are equivalent between the two approaches here, so the
 * self-hide requirement was the deciding factor.
 *
 * Deliberately shows only the first flagged representative (Decision 025
 * permits 0..N, but a Hero-style single-person feature does not attempt to
 * lay out an unbounded number of people). Never guesses a representative
 * when none is flagged — consistent with Decision 023's migration logic,
 * which also refuses to guess.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\ProfessionalProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'init', __NAMESPACE__ . '\\register_representative_block' );

/**
 * Registers the astrea/representative Dynamic Block.
 *
 * @return void
 */
function register_representative_block() {
	register_block_type(
		'astrea/representative',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_representative_block',
			'editor_script_handles' => array( \Astrea\Core\EditorBlocks\SCRIPT_HANDLE ),
			'attributes'            => array(
				'heading'      => array(
					'type'    => 'string',
					'default' => '',
				),
				'emptyMessage' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		)
	);
}

/**
 * Renders the first flagged representative as plain semantic HTML.
 *
 * @param array $attributes Block attributes: `heading`, `emptyMessage`
 *                           (see file docblock and price-list-block.php for the convention).
 * @return string
 */
function render_representative_block( array $attributes = array() ): string {
	$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
	$empty_message = isset( $attributes['emptyMessage'] ) ? (string) $attributes['emptyMessage'] : '';

	$representatives = get_representatives();

	if ( empty( $representatives ) ) {
		if ( '' === $empty_message ) {
			return '';
		}

		return '<p class="wp-block-astrea-representative-empty">' . esc_html( $empty_message ) . '</p>';
	}

	$representative = $representatives[0];

	$has_photo = (bool) $representative['photo_id'];
	$body      = '<div class="wp-block-astrea-representative' . ( $has_photo ? ' has-photo' : ' no-photo' ) . '">';

	if ( $has_photo ) {
		// Construction Order 016C: 'large' (not 'medium') — Visual v3 makes
		// this photo the Section's dominant element (~55-60% of the row
		// width), not a small avatar; wp_get_attachment_image() still emits
		// a full srcset/sizes pair from whatever intermediate sizes exist,
		// so this only raises the requested base size, not a fixed pixel
		// image.
		$body .= '<div class="wp-block-astrea-representative-photo">' . wp_get_attachment_image( $representative['photo_id'], 'large' ) . '</div>';
	}

	$permalink = get_permalink( $representative['id'] );

	$body .= '<div class="wp-block-astrea-representative-body">';
	$body .= '<h3 class="wp-block-astrea-representative-name">' . esc_html( $representative['name'] ) . '</h3>';

	if ( '' !== $representative['qualification'] ) {
		$body .= '<p class="wp-block-astrea-representative-qualification">' . esc_html( $representative['qualification'] ) . '</p>';
	}

	$bio_excerpt = wp_trim_words( wp_strip_all_tags( $representative['bio'] ), 40 );
	if ( '' !== $bio_excerpt ) {
		$body .= '<p class="wp-block-astrea-representative-bio">' . esc_html( $bio_excerpt ) . '</p>';
	}

	// Construction Order 016C: Visual v3 (Design Direction §8) calls for a
	// Link alongside Role/Name/Statement; the Professional Single page
	// (theme/templates/single-astrea_professional.html) already exists and
	// was simply never linked to from this teaser.
	//
	// Construction Order 016D-R2 §3: label changed from the generic
	// "詳しく見る" (shared verbatim by CASE's own, independently-defined
	// action link) to "プロフィールを見る" and given an Outline CTA
	// button treatment (see .wp-block-astrea-representative-action in
	// theme.json) — this is Professional's own distinct string, so this
	// does not affect CASE's identical-looking but separately defined
	// "詳しく見る" link in case-list-block.php.
	$body .= '<p class="wp-block-astrea-representative-action"><a href="' . esc_url( $permalink ) . '">' . esc_html__( 'プロフィールを見る', 'astrea-core' ) . '<span class="screen-reader-text">' .
		/* translators: %s: Representative name, appended to a "プロフィールを見る" link's accessible name only — not shown visually. */
		sprintf( esc_html__( '（%sについて）', 'astrea-core' ), esc_html( $representative['name'] ) ) .
		'</span></a></p>';

	$body .= '</div>';
	$body .= '</div>';

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . $body;
}
