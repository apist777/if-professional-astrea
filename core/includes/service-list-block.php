<?php
/**
 * Service — Dynamic Block for HOME/teaser display (Construction Order 011).
 *
 * `home-services-teaser.php` used a Query Loop, which structurally cannot
 * hide its own section heading at zero items (Query Loop can only swap in
 * a `core/query-no-results` message inside itself) — the one HOME Teaser
 * that never conformed to Decision 028's "0件ならセクション全体（見出し
 * 含む）を非表示にする" rule, flagged in the Construction Order 011
 * research doc §5. This block follows the exact same `heading` /
 * `emptyMessage` / `limit` attribute convention already established by
 * `astrea/case-list` / `astrea/results-list` / `astrea/voice-list`
 * (Construction Order 010) and calls Core's existing public read boundary
 * (`Service\get_services()`) directly — no new data model, no change to
 * Service itself.
 *
 * Deliberately a plain, reusable listing block (usable from any Pattern or
 * page, not hardcoded to HOME) but deliberately does NOT add filtering,
 * sorting UI, pagination UI, category filtering, AJAX, or search — none of
 * that is needed for FREE v1 (Construction Order 011 kickoff, "やらないこと").
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Construction Order 016C: a decorative "folder" icon shown on every
 * Service item. `astrea_service` has no icon-selection field of its own
 * (adding one — new postmeta, new Admin UI, migration — is out of this
 * Order's scope), so a single generic, profession-agnostic glyph is used
 * uniformly rather than guessing which of the Owner's curated icon set
 * (`docs/research/visual-v3/assets/icons/astrea/`) matches an arbitrary
 * user-authored Service name. Inlined (not an `<img>`) so its stroke can
 * be recoloured per Style Variation via `currentColor` — the exact
 * technique the icon set's own README recommends. `aria-hidden` +
 * `focusable="false"`: purely decorative, the Service name itself is the
 * accessible content.
 */
const ICON_SVG = '<svg viewBox="0 0 48 48" role="img" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 14h14l4 5h18v19H6z"/><path d="M6 14v-4h13l4 4"/><path d="M10 31h28" class="wp-block-astrea-service-item-icon-accent"/></svg>';

add_action( 'init', __NAMESPACE__ . '\\register_list_block' );

/**
 * Registers the astrea/service-list Dynamic Block.
 *
 * @return void
 */
function register_list_block() {
	register_block_type(
		'astrea/service-list',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_service_list_block',
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
 * Renders a Service list as plain semantic HTML.
 *
 * @param array $attributes Block attributes: `limit` (0 = no limit), `heading`, `emptyMessage`.
 * @return string
 */
function render_service_list_block( array $attributes = array() ): string {
	$limit         = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 0;
	$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
	$empty_message = isset( $attributes['emptyMessage'] ) ? (string) $attributes['emptyMessage'] : '';

	$services = get_services();

	// Construction Order 015D: on a Service Single page, this block doubles
	// as the "Related Services" section — exclude the post being viewed so
	// it never lists itself as its own related item. Deterministic (same
	// post type, existing sort order), not a recommendation engine.
	if ( is_singular( POST_TYPE ) ) {
		$current_id = get_queried_object_id();
		$services   = array_values(
			array_filter(
				$services,
				static function ( $service ) use ( $current_id ) {
					return $service['id'] !== $current_id;
				}
			)
		);
	} elseif ( is_singular( \Astrea\Core\CaseStudy\POST_TYPE ) ) {
		// On a CASE Single page, this block shows the specific Services that
		// CASE's own existing `related_services` field already names
		// (Decision-preexisting data, not new — see case.php). Zero related
		// Services recorded for this Case simply self-hides, exactly like
		// any other zero-item state.
		$case                = \Astrea\Core\CaseStudy\get_case( get_queried_object_id() );
		$related_service_ids = ( $case && ! empty( $case['related_services'] ) ) ? $case['related_services'] : array();

		$services = array_values(
			array_filter(
				$services,
				static function ( $service ) use ( $related_service_ids ) {
					return in_array( $service['id'], $related_service_ids, true );
				}
			)
		);
	}

	if ( $limit > 0 ) {
		$services = array_slice( $services, 0, $limit );
	}

	if ( empty( $services ) ) {
		if ( '' === $empty_message ) {
			return '';
		}

		return '<p class="wp-block-astrea-service-list-empty">' . esc_html( $empty_message ) . '</p>';
	}

	$items = '';

	foreach ( $services as $service ) {
		$permalink = get_permalink( $service['id'] );

		$items .= '<div class="wp-block-astrea-service-item">';
		$items .= '<span class="wp-block-astrea-service-item-icon">' . ICON_SVG . '</span>';
		$items .= '<h3><a href="' . esc_url( $permalink ) . '">' . esc_html( $service['name'] ) . '</a></h3>';

		$excerpt = wp_trim_words( wp_strip_all_tags( $service['description'] ), 40 );
		if ( '' !== $excerpt ) {
			$items .= '<p class="wp-block-astrea-service-item-description">' . esc_html( $excerpt ) . '</p>';
		}

		$items .= '<p class="wp-block-astrea-service-item-action"><a href="' . esc_url( $permalink ) . '">' . esc_html__( '詳しく見る', 'astrea-core' ) . '<span class="screen-reader-text">' .
			/* translators: %s: Service name, appended to a "詳しく見る" link's accessible name only — not shown visually. */
			sprintf( esc_html__( '（%sについて）', 'astrea-core' ), esc_html( $service['name'] ) ) .
			'</span></a></p>';

		$items .= '</div>';
	}

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . '<div class="wp-block-astrea-service-list">' . $items . '</div>';
}
