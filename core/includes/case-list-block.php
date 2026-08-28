<?php
/**
 * CASE（対応事例） — Dynamic Block for HOME/teaser display (Construction
 * Order 010).
 *
 * Same `heading` / `emptyMessage` attribute convention as
 * `astrea/price-list` / `astrea/faq-list` (Decision 028): leaving both
 * unset self-hides the block entirely (including any heading) — the
 * HOME-teaser rule. Setting `emptyMessage` switches to the dedicated-page
 * rule instead. Unlike Price/FAQ, each item's title links to its own
 * Single page (astrea_case has one — see theme/templates/single-astrea_case.html),
 * since a case study is meant to be read in full.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\CaseStudy;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'init', __NAMESPACE__ . '\\register_list_block' );

/**
 * Registers the astrea/case-list Dynamic Block.
 *
 * @return void
 */
function register_list_block() {
	register_block_type(
		'astrea/case-list',
		array(
			'render_callback' => __NAMESPACE__ . '\\render_case_list_block',
			'attributes'      => array(
				'limit'        => array(
					'type'    => 'number',
					'default' => 0,
				),
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
 * Renders a CASE list as plain semantic HTML.
 *
 * @param array $attributes Block attributes: `limit` (0 = no limit), `heading`, `emptyMessage`.
 * @return string
 */
function render_case_list_block( array $attributes = array() ): string {
	$limit         = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 0;
	$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
	$empty_message = isset( $attributes['emptyMessage'] ) ? (string) $attributes['emptyMessage'] : '';

	$cases = get_cases();

	if ( $limit > 0 ) {
		$cases = array_slice( $cases, 0, $limit );
	}

	if ( empty( $cases ) ) {
		if ( '' === $empty_message ) {
			return '';
		}

		return '<p class="wp-block-astrea-case-list-empty">' . esc_html( $empty_message ) . '</p>';
	}

	$items = '';

	foreach ( $cases as $case ) {
		$items .= '<div class="wp-block-astrea-case-item">';
		$items .= '<h3><a href="' . esc_url( get_permalink( $case['id'] ) ) . '">' . esc_html( $case['title'] ) . '</a></h3>';

		$excerpt = '' !== $case['excerpt'] ? $case['excerpt'] : wp_trim_words( wp_strip_all_tags( $case['content'] ), 40 );
		if ( '' !== $excerpt ) {
			$items .= '<p>' . esc_html( $excerpt ) . '</p>';
		}

		$items .= '</div>';
	}

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . '<div class="wp-block-astrea-case-list">' . $items . '</div>';
}
