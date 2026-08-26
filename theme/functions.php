<?php
/**
 * ASTREA Theme bootstrap.
 *
 * Construction-phase skeleton only: registers baseline theme supports and the
 * Core-detection entry point that later feature Phases will build on. See
 * docs/specifications/05_astrea_free_v1_construction_baseline.md §4, §14.
 *
 * @package Astrea\Theme
 */

namespace Astrea\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const VERSION = '0.1.0';

add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );

/**
 * Registers theme supports and loads the theme text domain.
 *
 * @return void
 */
function setup() {
	load_theme_textdomain( 'astrea', get_template_directory() . '/languages' );

	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
}

/**
 * Reports whether ASTREA Core is active.
 *
 * Theme code MUST call this before reading any Core-provided data, calling a
 * Core-provided function, or relying on a Core-registered Block Binding
 * source. ASTREA Core is an optional, officially recommended plugin — the
 * Theme must never fatal or break when it is absent (Decision 013, Decision
 * 021).
 *
 * @return bool True when ASTREA Core is active.
 */
function is_core_active(): bool {
	return defined( 'ASTREA_CORE_VERSION' );
}
