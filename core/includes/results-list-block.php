<?php
/**
 * RESULTS（実績） — Dynamic Block for HOME/teaser display (Construction
 * Order 010).
 *
 * RESULTS has no individual URL (see result.php) and no dedicated Archive
 * page — this Dynamic Block is its only display path, matching Price's
 * precedent exactly. Same `heading` / `emptyMessage` attribute convention
 * (Decision 028). Each entry is rendered as a plain label+value pair; the
 * value is never assumed numeric and is output as free text (no
 * structured data — see the file header of result.php).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Result;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'init', __NAMESPACE__ . '\\register_list_block' );

/**
 * Registers the astrea/results-list Dynamic Block.
 *
 * @return void
 */
function register_list_block() {
	register_block_type(
		'astrea/results-list',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_results_list_block',
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
 * Renders a RESULTS list as plain semantic HTML.
 *
 * @param array $attributes Block attributes: `heading`, `emptyMessage`.
 * @return string
 */
function render_results_list_block( array $attributes = array() ): string {
	$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
	$empty_message = isset( $attributes['emptyMessage'] ) ? (string) $attributes['emptyMessage'] : '';

	$results = get_results();

	if ( empty( $results ) ) {
		if ( '' === $empty_message ) {
			return '';
		}

		return '<p class="wp-block-astrea-results-list-empty">' . esc_html( $empty_message ) . '</p>';
	}

	$items = '';

	foreach ( $results as $result ) {
		$items .= '<div class="wp-block-astrea-result-item">';
		$items .= '<p class="wp-block-astrea-result-value">' . esc_html( $result['value'] ) . '</p>';
		$items .= '<p class="wp-block-astrea-result-label">' . esc_html( $result['label'] ) . '</p>';
		$items .= '</div>';
	}

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . '<div class="wp-block-astrea-results-list">' . $items . '</div>';
}
