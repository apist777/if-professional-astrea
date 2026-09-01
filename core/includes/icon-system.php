<?php
/**
 * ASTREA Icon System — shared decorative-icon registry (Construction
 * Order 016D-R1).
 *
 * Owner-created SVG assets are placed at `theme/assets/icons/{price,
 * results,services}/` (audited in Construction 016D — valid SVG,
 * consistent 0 0 48 48 viewBox, no external references/scripts). This
 * file is the ONE place that knows their path data, shared by every
 * Dynamic Block that needs to render one (Service/Result/Price) — so the
 * markup exists exactly once instead of being re-typed per block.
 *
 * Deliberately does NOT read the theme's SVG files at runtime
 * (`file_get_contents()` on a Theme path from Core would couple Core to
 * a specific Theme's file layout, contradicting Decision 013/021's
 * Core/Theme separation — Core must keep working if the active Theme is
 * ever swapped). Instead each icon's path data is copied here once,
 * converted from the source files' hard-coded `#102A43`/`#B99A5C`
 * strokes to `currentColor` / a `.astrea-icon-accent` class, matching
 * the exact technique already established for Service's `folder` icon
 * (Construction 016C) and documented as the recommended approach in the
 * asset set's own README (`docs/research/visual-v3/assets/icons/astrea/README.md`).
 * Content was verified byte-for-byte against the real files after
 * conversion (path data unchanged, only colour attributes replaced).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\IconSystem;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * All known icon slugs and their inline SVG markup.
 *
 * @return array<string,string>
 */
function registry(): array {
	static $icons = null;

	if ( null !== $icons ) {
		return $icons;
	}

	$open  = '<svg viewBox="0 0 48 48" role="img" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">';
	$close = '</svg>';
	$a     = 'class="astrea-icon-accent"'; // Accent-stroke path marker (Gold in Trust; per-Variation via CSS).

	$icons = array(
		// theme/assets/icons/services/*.svg.
		'company'             => $open . '<path d="M10 40V12h18v28"/><path d="M28 21h10v19"/><path d="M7 40h34"/><path d="M15 17h3m5 0h0m-8 6h3m5 0h0m-8 6h3m5 0h0m-8 6h3m5 0h0"/><path d="M32 26h2m-2 6h2m-2 6h2"/><path d="M20 40v-7h5v7" ' . $a . '/>' . $close,
		'contract'            => $open . '<path d="M10 9h18l8 8v22H10z"/><path d="M28 9v9h8"/><path d="M16 22h13m-13 6h10"/><path d="m27 35 9-9 4 4-9 9-5 1z" ' . $a . '/><path d="m34 28 4 4" ' . $a . '/>' . $close,
		'document'            => $open . '<path d="M12 7h19l7 7v27H12z"/><path d="M31 7v8h7"/><path d="M18 21h13m-13 6h13m-13 6h9"/><circle cx="33" cy="34" r="5" ' . $a . '/><path d="m31 34 1.5 1.5L35 32" ' . $a . '/>' . $close,
		'folder'              => $open . '<path d="M6 14h14l4 5h18v19H6z"/><path d="M6 14v-4h13l4 4"/><path d="M10 31h28" ' . $a . '/>' . $close,
		'inheritance'         => $open . '<circle cx="24" cy="11" r="5"/><circle cx="11" cy="18" r="4"/><circle cx="37" cy="18" r="4"/><path d="M15 36v-5c0-5 4-9 9-9s9 4 9 9v5"/><path d="M4 36v-4c0-4 3-7 7-7 2 0 4 .8 5.5 2.2"/><path d="M44 36v-4c0-4-3-7-7-7-2 0-4 .8-5.5 2.2"/><path d="M8 40h32" ' . $a . '/>' . $close,
		'permit'              => $open . '<path d="M13 7h17l7 7v25H13z"/><path d="M30 7v8h7"/><path d="M18 20h12m-12 6h8m-8 6h7"/><circle cx="34" cy="33" r="7" ' . $a . '/><path d="m31 33 2 2 4-5" ' . $a . '/>' . $close,
		// theme/assets/icons/results/*.svg.
		'result-company'      => $open . '<path d="M8 40V19h13v21"/><path d="M21 12h11v28"/><path d="M32 25h8v15"/><path d="M5 40h38"/><path d="M12 24h4m-4 6h4m-4 6h4M25 17h3m-3 6h3m-3 6h3m-3 6h3M35 30h2m-2 6h2"/><path d="M21 12l5-5 6 5" ' . $a . '/>' . $close,
		'result-check'        => $open . '<circle cx="24" cy="24" r="16"/><path d="m16 24 5 5 11-12" ' . $a . ' stroke-width="2.2"/>' . $close,
		'result-consultation' => $open . '<circle cx="17" cy="17" r="6"/><circle cx="31" cy="17" r="6"/><path d="M6 39v-5c0-6 5-11 11-11 3 0 6 1.2 8 3.2"/><path d="M42 39v-5c0-6-5-11-11-11-3 0-6 1.2-8 3.2"/><path d="M14 39h20" ' . $a . '/>' . $close,
		// theme/assets/icons/price/*.svg.
		'price-yen'           => $open . '<circle cx="24" cy="24" r="17"/><path d="m17 15 7 10 7-10"/><path d="M18 25h12M18 30h12M24 25v10" ' . $a . '/>' . $close,
	);

	return $icons;
}

/**
 * Renders a decorative icon by slug wrapped in a className span, or an
 * empty string for an unknown slug (never a broken/missing-image look —
 * callers simply get no icon, matching this product's established
 * "self-hide rather than render broken" convention).
 *
 * @param string $slug       Icon slug — see registry() above.
 * @param string $class_name Wrapper `<span>` className (block-specific sizing/margin lives in Theme CSS, keyed off this class).
 * @return string
 */
function render( string $slug, string $class_name ): string {
	$icons = registry();

	if ( ! isset( $icons[ $slug ] ) ) {
		return '';
	}

	return '<span class="' . esc_attr( $class_name ) . '">' . $icons[ $slug ] . '</span>';
}

/**
 * The allowed slugs for a given context, used both by each CPT's
 * `sanitize_callback` (reject anything else, fall back to the context's
 * default) and by its admin `<select>` (only ever offer a value the
 * sanitizer will actually keep).
 *
 * @param string $context 'service' | 'result' | 'price'.
 * @return string[]
 */
function allowed_slugs( string $context ): array {
	switch ( $context ) {
		case 'service':
			return array( 'company', 'contract', 'document', 'folder', 'inheritance', 'permit' );
		case 'result':
			return array( 'result-company', 'result-check', 'result-consultation' );
		case 'price':
			// Price re-uses the Service icon set (Construction 016D-R1
			// Order §11 assigns company/permit/inheritance to specific
			// Price items) plus its own price-yen glyph.
			return array( 'company', 'contract', 'document', 'folder', 'inheritance', 'permit', 'price-yen' );
		default:
			return array();
	}
}

/**
 * The safe default slug for a context — used both as the registered
 * postmeta default and as the sanitize_callback's fallback for an
 * invalid/missing value.
 *
 * @param string $context 'service' | 'result' | 'price'.
 * @return string
 */
function default_slug( string $context ): string {
	switch ( $context ) {
		case 'service':
			return 'folder';
		case 'result':
			return 'result-check';
		case 'price':
			return 'price-yen';
		default:
			return '';
	}
}

/**
 * Builds a `sanitize_callback` for `register_post_meta()` that only ever
 * keeps a value from `allowed_slugs($context)`, falling back to
 * `default_slug($context)` for anything else (empty string, unknown
 * slug, or a slug that belonged to a different context). This is the
 * ONE place that decides validity — every CPT registering an icon meta
 * key calls this rather than re-implementing the same whitelist check.
 *
 * @param string $context 'service' | 'result' | 'price'.
 * @return callable
 */
function make_sanitizer( string $context ): callable {
	return static function ( $value ) use ( $context ) {
		$value = is_string( $value ) ? $value : '';

		if ( in_array( $value, allowed_slugs( $context ), true ) ) {
			return $value;
		}

		return default_slug( $context );
	};
}
