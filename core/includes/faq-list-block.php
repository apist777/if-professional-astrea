<?php
/**
 * FAQ — Dynamic Block for HOME/teaser display (Construction Order 008,
 * Decision 028).
 *
 * FAQ already has a dedicated Archive/Taxonomy display via Query Loop
 * (Construction Order 004) that lists every published FAQ. That mechanism
 * cannot express 02仕様書§11's "少数ならMinimal表示、多数ならカテゴリ表示
 * 等、件数に適したPatternを用意する" nor a "重要FAQのみ抜粋" teaser, since
 * Query Loop has no way to filter by the `is_important` postmeta flag or
 * cap the result count declaratively. This Dynamic Block calls Core's
 * existing public read boundary (`get_faqs()` / `get_important_faqs()`)
 * directly — no new FAQ data model, no new CPT, no new DB table.
 *
 * Follows the same `heading` / `emptyMessage` attribute convention as
 * `astrea/price-list` (see price-list-block.php's docblock): leaving both
 * unset self-hides the block entirely (including any heading) when there
 * is nothing to show — the Decision 028 HOME-teaser rule. Setting
 * `emptyMessage` switches to the Decision 028 dedicated-page rule
 * instead (a friendly message, no heading requirement).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Faq;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'init', __NAMESPACE__ . '\\register_list_block' );

/**
 * Registers the astrea/faq-list Dynamic Block.
 *
 * @return void
 */
function register_list_block() {
	register_block_type(
		'astrea/faq-list',
		array(
			'render_callback' => __NAMESPACE__ . '\\render_faq_list_block',
			'attributes'      => array(
				'mode'         => array(
					'type'    => 'string',
					'default' => 'important',
				),
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
 * Renders a FAQ list as plain semantic HTML.
 *
 * @param array $attributes Block attributes: `mode` ('important'|'all'),
 *                           `limit` (0 = no limit), `heading`, `emptyMessage`.
 * @return string
 */
function render_faq_list_block( array $attributes = array() ): string {
	$mode          = ( isset( $attributes['mode'] ) && 'all' === $attributes['mode'] ) ? 'all' : 'important';
	$limit         = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 0;
	$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
	$empty_message = isset( $attributes['emptyMessage'] ) ? (string) $attributes['emptyMessage'] : '';

	$faqs = ( 'all' === $mode ) ? get_faqs() : get_important_faqs();

	if ( $limit > 0 ) {
		$faqs = array_slice( $faqs, 0, $limit );
	}

	if ( empty( $faqs ) ) {
		if ( '' === $empty_message ) {
			return '';
		}

		return '<p class="wp-block-astrea-faq-list-empty">' . esc_html( $empty_message ) . '</p>';
	}

	$items = '';

	foreach ( $faqs as $faq ) {
		$items .= '<div class="wp-block-astrea-faq-item">';
		$items .= '<h3>' . esc_html( $faq['question'] ) . '</h3>';
		$items .= '<div>' . wp_kses_post( apply_filters( 'the_content', $faq['answer'] ) ) . '</div>'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- invoking WordPress core's own `the_content` filter, not declaring a new hook.
		$items .= '</div>';
	}

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . '<div class="wp-block-astrea-faq-list">' . $items . '</div>';
}
