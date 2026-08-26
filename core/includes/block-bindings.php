<?php
/**
 * Block Bindings — the "Blockがつなぐ" layer of ASTREA's Core→Theme
 * Architecture (Construction Baseline §14 / Decision 013).
 *
 * Registers a Block Bindings source that exposes a small, explicit set of
 * scalar Office Profile fields to standard core blocks (Paragraph, Heading,
 * Button, Image), so the Theme can connect to them using only
 * block markup — no PHP function call from Theme to Core is required for
 * this path. If ASTREA Core is inactive, this source is never registered,
 * and WordPress natively falls back to each block's own static content
 * (verified behaviour of the Block Bindings API) — satisfying Decision 021
 * without any extra guard code on either side.
 *
 * Structured/collection data (business_hours, sns_links) is intentionally
 * NOT exposed here: Block Bindings only supports scalar block attributes
 * (e.g. a paragraph's `content`), not lists. Displaying those will need a
 * Dynamic Block in a future Construction Order.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Bindings;

use function Astrea\Core\OfficeProfile\get_office_profile;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Public contract: this string is what Theme templates bind to. */
const OFFICE_PROFILE_SOURCE = 'astrea-core/office-profile';

/** Public contract: the only `args.key` values a binding may request. */
const ALLOWED_KEYS = array( 'office_name', 'representative_name', 'address', 'phone' );

add_action( 'init', __NAMESPACE__ . '\\register_office_profile_source' );

/**
 * Registers the `astrea-core/office-profile` Block Bindings source.
 *
 * @return void
 */
function register_office_profile_source() {
	if ( ! function_exists( 'register_block_bindings_source' ) ) {
		return;
	}

	register_block_bindings_source(
		OFFICE_PROFILE_SOURCE,
		array(
			'label'              => __( 'ASTREA — 事務所情報', 'astrea-core' ),
			'get_value_callback' => __NAMESPACE__ . '\\get_bound_value',
		)
	);
}

/**
 * Value callback for the office-profile binding source.
 *
 * Returning null (for an unknown key, or an unconfigured/empty value) is
 * intentional: it tells WordPress core to fall back to the block's own
 * static content instead of overwriting it with an empty string.
 *
 * @param array     $source_args    Binding args, e.g. array( 'key' => 'office_name' ).
 * @param \WP_Block $block_instance The current block instance (unused, required by the callback signature).
 * @param string    $attribute_name The bound attribute name (unused, required by the callback signature).
 * @return string|null
 */
function get_bound_value( $source_args, $block_instance, $attribute_name ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$key = isset( $source_args['key'] ) ? (string) $source_args['key'] : '';

	if ( ! in_array( $key, ALLOWED_KEYS, true ) ) {
		return null;
	}

	$profile = get_office_profile();
	$value   = $profile[ $key ] ?? '';

	return ( '' !== $value ) ? $value : null;
}
