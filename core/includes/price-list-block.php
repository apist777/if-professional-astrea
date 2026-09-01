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
 * Per §8 (Empty State) and Decision 028 (Construction Order 008), this
 * block supports both of the two standardized zero-item behaviors from a
 * single implementation, selected per Pattern via block attributes rather
 * than a mode enum:
 *
 * - `heading` (optional): rendered as an <h2> above the list, but ONLY
 *   together with actual items — a heading is never emitted alone. Used
 *   by HOME teaser Patterns that need their own section title.
 * - `emptyMessage` (optional): when the item list is empty AND this is
 *   set, it is rendered as a friendly zero-item message instead of the
 *   heading+list. Used by the dedicated Price page Pattern (an "Archive
 *   専用ページ" per Decision 028), where the page's own post-title
 *   already serves as the heading and a blank page would be worse than a
 *   short message.
 *
 * Leaving both attributes unset reproduces the original Construction
 * Order 004 behavior exactly: zero items renders nothing at all — no
 * empty heading, no empty container (the HOME-teaser self-hide rule).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Price;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'init', __NAMESPACE__ . '\\register_block' );

/**
 * Registers the astrea/price-list Dynamic Block — server-rendered
 * (Decision 013 assigns Dynamic Block server-side rendering to Core;
 * Decision 012's Block namespace `astrea/*` applies regardless of which
 * of Theme/Core registers the block). Since Construction Order 013 this
 * also attaches a minimal Editor-only client registration (see
 * includes/editor-blocks.php) so the Block/Site Editor recognizes it.
 *
 * @return void
 */
function register_block() {
	register_block_type(
		'astrea/price-list',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_price_list_block',
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
 * Renders the Price list as plain semantic HTML.
 *
 * @param array $attributes Block attributes (`heading`, `emptyMessage`), see the file docblock.
 * @return string
 */
function render_price_list_block( array $attributes = array() ): string {
	$prices        = get_prices();
	$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
	$empty_message = isset( $attributes['emptyMessage'] ) ? (string) $attributes['emptyMessage'] : '';

	if ( empty( $prices ) ) {
		if ( '' === $empty_message ) {
			return '';
		}

		return '<p class="wp-block-astrea-price-list-empty">' . esc_html( $empty_message ) . '</p>';
	}

	$items = '';

	foreach ( $prices as $price ) {
		$items .= '<div class="wp-block-astrea-price-item">';
		// Construction Order 016D-R1: a decorative icon per Price item —
		// see price.php's META_ICON (same design as Service/Result:
		// picked from a fixed list, never guessed from the item name).
		$items .= \Astrea\Core\IconSystem\render( $price['icon'], 'wp-block-astrea-price-item-icon' );

		if ( '' !== $price['group'] ) {
			// Per-item kicker label, not a sorted/bucketed section grouping —
			// get_prices() orders by menu_order/title/ID, never by group, so
			// grouping into buckets here would require re-sorting (Post v1
			// Finding 8's territory, explicitly out of scope). Showing the
			// group as each item's own label works regardless of order.
			$items .= '<p class="wp-block-astrea-price-item-group">' . esc_html( $price['group'] ) . '</p>';
		}

		$items .= '<h3 class="wp-block-astrea-price-item-name">' . esc_html( $price['name'] ) . '</h3>';

		if ( '' !== $price['amount'] ) {
			$items .= '<p class="wp-block-astrea-price-item-amount">' . nl2br( esc_html( $price['amount'] ) ) . '</p>';
		}

		if ( '' !== $price['notes'] ) {
			$items .= '<p class="wp-block-astrea-price-item-notes">' . nl2br( esc_html( $price['notes'] ) ) . '</p>';
		}

		$items .= '</div>';
	}

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	// Construction Order 015E: the dedicated Price page wants a more
	// spacious, "detail page" presentation than the HOME teaser's compact
	// summary. There is no new context attribute for this — `heading` set
	// is already the existing, established signal that this call is the
	// HOME teaser (only home-price.php sets it; the Price page's own
	// content and the price-list Pattern both leave it unset and rely on
	// the page's own post-title instead), so it doubles as the compact/
	// detailed switch without inventing new Block API surface.
	$list_class = '' !== $heading ? 'wp-block-astrea-price-list wp-block-astrea-price-list--compact' : 'wp-block-astrea-price-list';

	return $heading_html . '<div class="' . esc_attr( $list_class ) . '">' . $items . '</div>';
}
