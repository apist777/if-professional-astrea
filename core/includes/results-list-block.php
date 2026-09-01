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
 * Construction Order 016D added a decorative icon on every Result item,
 * uniform across all items (matching Service's own precedent) because
 * `astrea_result` had no icon-selection field and guessing one from the
 * Result's free-text label/value (e.g. "200社以上" -> a company icon)
 * would be exactly the "fragile content-sniffing" that Order forbade.
 * 016D-R1 added a real `META_ICON` field (see result.php) — same reasoning
 * as Service's — so `$result['icon']` now reflects a per-item choice made
 * by a site owner, rendered via the shared
 * `Astrea\Core\IconSystem\render()` registry (`icon-system.php`).
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
		$items .= \Astrea\Core\IconSystem\render( $result['icon'], 'wp-block-astrea-result-item-icon' );
		$items .= '<p class="wp-block-astrea-result-value">' . render_value( $result['value'] ) . '</p>';
		$items .= '<p class="wp-block-astrea-result-label">' . esc_html( $result['label'] ) . '</p>';
		$items .= '</div>';
	}

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . '<div class="wp-block-astrea-results-list">' . $items . '</div>';
}

/**
 * Construction Order 016D-R1 §9: split a leading number from its trailing
 * unit ("200社以上" -> "200" + "社以上") so the unit can be styled smaller
 * than the big number (Reference composition). `value` stays free text
 * (never assumed numeric — see result.php's own docblock), so this is a
 * purely presentational, backward-compatible enhancement: any value with
 * no leading digit (e.g. "全国対応", "多数") renders exactly as before,
 * as one plain escaped string, no split markup at all.
 *
 * @param string $value Free-text RESULTS value.
 * @return string Escaped HTML — safe to echo directly.
 */
function render_value( string $value ): string {
	if ( ! preg_match( '/^([0-9,.]+)(.*)$/u', $value, $matches ) || '' === $matches[1] ) {
		return esc_html( $value );
	}

	$number = $matches[1];
	$unit   = $matches[2];

	if ( '' === $unit ) {
		return esc_html( $number );
	}

	return esc_html( $number ) . '<span class="wp-block-astrea-result-unit">' . esc_html( $unit ) . '</span>';
}
