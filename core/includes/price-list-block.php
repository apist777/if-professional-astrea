<?php
/**
 * Price — Dynamic Block for Theme display (Construction Order 004).
 *
 * Price is intentionally not a public/publicly_queryable post type (see
 * price.php) — §10 gives no basis for an individual Price URL. This is a
 * real WordPress constraint on the Query Loop block: Core's own
 * `build_query_vars_from_query_block()` silently falls back to the default
 * `post` post type unless `is_post_type_viewable()` is true, which requires
 * `publicly_queryable`. Verified empirically against a real wp-env site
 * (docs/research/2026-08-26_construction_order_004_research.md §4.2).
 *
 * Decision 013 anticipates exactly this case: "構造・処理を伴うもの（一覧・
 * 条件分岐・件数可変等）はDynamic Block等を用途に応じて利用する" — a
 * variable-count list that must not be independently browsable is the
 * Dynamic Block case, not the Query Loop case. This block calls Core's own
 * public read boundary (get_prices()) directly and renders server-side,
 * bypassing WP_Query's viewability gate entirely while keeping Price's "no
 * individual URL" property intact.
 *
 * Per §8 (Empty State), rendering nothing at all when there are zero Price
 * entries — no empty heading, no empty container — is a deliberate choice,
 * not an oversight.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Price;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'init', __NAMESPACE__ . '\\register_block' );

/**
 * Registers the astrea/price-list Dynamic Block (server-rendered only, no
 * editor script — Decision 012's Block namespace `astrea/*` applies
 * regardless of which of Theme/Core registers the block; Decision 013
 * assigns Dynamic Block server-side rendering to Core).
 *
 * @return void
 */
function register_block() {
	register_block_type(
		'astrea/price-list',
		array(
			'render_callback' => __NAMESPACE__ . '\\render_price_list_block',
			'attributes'      => array(),
		)
	);
}

/**
 * Renders the Price list as plain semantic HTML. Returns an empty string
 * when there are zero Price entries (§8 Empty State).
 *
 * @return string
 */
function render_price_list_block(): string {
	$prices = get_prices();

	if ( empty( $prices ) ) {
		return '';
	}

	$items = '';

	foreach ( $prices as $price ) {
		$items .= '<div class="wp-block-astrea-price-item">';
		$items .= '<h3>' . esc_html( $price['name'] ) . '</h3>';

		if ( '' !== $price['amount'] ) {
			$items .= '<p>' . nl2br( esc_html( $price['amount'] ) ) . '</p>';
		}

		if ( '' !== $price['notes'] ) {
			$items .= '<p>' . nl2br( esc_html( $price['notes'] ) ) . '</p>';
		}

		$items .= '</div>';
	}

	return '<div class="wp-block-astrea-price-list">' . $items . '</div>';
}
