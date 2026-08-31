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
 * Construction Order 016C added a Media column (photo or an intentional
 * empty state) and a Category label sourced from the Case's own existing
 * `related_services` field — no new data model, reusing a pre-existing
 * field rather than inventing one. The Feature/Secondary visual
 * distinction (first row larger, Visual v3 Design Direction §9) is pure
 * Theme CSS keyed off row position (`:first-child`), not a new flag.
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
			'render_callback'       => __NAMESPACE__ . '\\render_case_list_block',
			'editor_script_handles' => array( \Astrea\Core\EditorBlocks\SCRIPT_HANDLE ),
			'attributes'            => array(
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

	// Construction Order 015D: on a CASE Single page, this block doubles as
	// the "Related Cases" section — exclude the post being viewed. Same
	// deterministic, same-post-type approach as Service's own Single reuse.
	if ( is_singular( POST_TYPE ) ) {
		$current_id = get_queried_object_id();
		$cases      = array_values(
			array_filter(
				$cases,
				static function ( $case_item ) use ( $current_id ) {
					return $case_item['id'] !== $current_id;
				}
			)
		);
	}

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
		$permalink = get_permalink( $case['id'] );

		$items .= '<div class="wp-block-astrea-case-item">';

		// Construction Order 016C (Visual v3 Design Direction §9): a
		// Media column (photo or an intentional empty state, never a
		// missing/broken image) and a Category/Service label sourced from
		// the Case's own existing `related_services` field (already
		// present — see case.php — never a new field). Only the first
		// related Service is used as the label to keep it a single short
		// word/phrase; a Case with none simply omits the label rather than
		// guessing one.
		if ( $case['photo_id'] ) {
			$items .= '<div class="wp-block-astrea-case-item-media">' . wp_get_attachment_image( $case['photo_id'], 'medium' ) . '</div>';
		} else {
			$items .= '<div class="wp-block-astrea-case-item-media is-empty" aria-hidden="true"></div>';
		}

		$items .= '<div class="wp-block-astrea-case-item-body">';

		if ( ! empty( $case['related_services'] ) ) {
			$related_service = \Astrea\Core\Service\get_service( $case['related_services'][0] );
			if ( $related_service ) {
				$items .= '<p class="wp-block-astrea-case-item-label">' . esc_html( $related_service['name'] ) . '</p>';
			}
		}

		$items .= '<h3><a href="' . esc_url( $permalink ) . '">' . esc_html( $case['title'] ) . '</a></h3>';

		$excerpt = '' !== $case['excerpt'] ? $case['excerpt'] : wp_trim_words( wp_strip_all_tags( $case['content'] ), 40 );
		if ( '' !== $excerpt ) {
			$items .= '<p class="wp-block-astrea-case-item-excerpt">' . esc_html( $excerpt ) . '</p>';
		}

		$items .= '<p class="wp-block-astrea-case-item-action"><a href="' . esc_url( $permalink ) . '">' . esc_html__( '詳しく見る', 'astrea-core' ) . '<span class="screen-reader-text">' .
			/* translators: %s: Case title, appended to a "詳しく見る" link's accessible name only — not shown visually. */
			sprintf( esc_html__( '（%sについて）', 'astrea-core' ), esc_html( $case['title'] ) ) .
			'</span></a></p>';

		$items .= '</div>'; // .wp-block-astrea-case-item-body
		$items .= '</div>'; // .wp-block-astrea-case-item
	}

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . '<div class="wp-block-astrea-case-list">' . $items . '</div>';
}
