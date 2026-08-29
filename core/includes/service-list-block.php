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
		$items .= '<h3><a href="' . esc_url( $permalink ) . '">' . esc_html( $service['name'] ) . '</a></h3>';

		$excerpt = wp_trim_words( wp_strip_all_tags( $service['description'] ), 40 );
		if ( '' !== $excerpt ) {
			$items .= '<p class="wp-block-astrea-service-item-description">' . esc_html( $excerpt ) . '</p>';
		}

		$items .= '<p class="wp-block-astrea-service-item-action"><a href="' . esc_url( $permalink ) . '">' . esc_html__( '詳しく見る', 'astrea-core' ) . '</a></p>';

		$items .= '</div>';
	}

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . '<div class="wp-block-astrea-service-list">' . $items . '</div>';
}
