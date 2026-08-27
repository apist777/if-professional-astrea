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
			'render_callback' => __NAMESPACE__ . '\\render_representative_block',
			'attributes'      => array(
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

	$body = '<div class="wp-block-astrea-representative">';

	if ( $representative['photo_id'] ) {
		$body .= wp_get_attachment_image( $representative['photo_id'], 'medium' );
	}

	$body .= '<h3>' . esc_html( $representative['name'] ) . '</h3>';

	if ( '' !== $representative['qualification'] ) {
		$body .= '<p>' . esc_html( $representative['qualification'] ) . '</p>';
	}

	$bio_excerpt = wp_trim_words( wp_strip_all_tags( $representative['bio'] ), 40 );
	if ( '' !== $bio_excerpt ) {
		$body .= '<p>' . esc_html( $bio_excerpt ) . '</p>';
	}

	$body .= '</div>';

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . $body;
}
