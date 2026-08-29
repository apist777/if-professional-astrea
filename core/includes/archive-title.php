<?php
/**
 * Archive Title — removes WordPress's generic "Archives: %s" prefix for
 * ASTREA's own CPT archives only (Construction Order 015D, Visual v2).
 *
 * `get_the_archive_title()` (called internally by `core/query-title`,
 * which every ASTREA Archive template uses) prepends a generic, translated
 * "Archives: %s" / "Archive: %s" string ahead of the actual archive label
 * for post type archives. This is WordPress Core's own standard behavior,
 * not an ASTREA defect — but it reads as "WordPress admin" rather than
 * "this office's service listing" on the public-facing Archive pages.
 *
 * Scope is deliberately narrow: only the five ASTREA post type archives
 * (`astrea_service`, `astrea_professional`, `astrea_case`, `astrea_voice`,
 * `astrea_faq`) are affected. Search results, category/tag archives, date
 * archives, and any other post type's archive are left completely alone —
 * this filter returns the original, unmodified title for every other
 * context, so it cannot un-intentionally spread to functionality outside
 * this Construction Order's stated scope.
 *
 * This filter only touches the *visual* heading rendered by
 * `core/query-title` on the Archive template itself. It does not touch
 * `document_title_parts` or any other SEO/`<title>`-tag generation
 * (seo-meta.php owns that separately) — the browser tab title and search
 * engine result title are unaffected by this change.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\ArchiveTitle;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** The only post type archives this filter ever touches. */
const MANAGED_POST_TYPES = array( 'astrea_service', 'astrea_professional', 'astrea_case', 'astrea_voice', 'astrea_faq' );

add_filter( 'get_the_archive_title', __NAMESPACE__ . '\\strip_generic_prefix', 10, 2 );

/**
 * Replaces "Archives: %s" / "アーカイブ: %s" etc. with the plain post type
 * archive label, for ASTREA's own post types only.
 *
 * @param string $title    The archive title, as WordPress Core built it.
 * @param string $original The title before any other filter touched it (unused; Core's own signature).
 * @return string
 */
function strip_generic_prefix( string $title, string $original = '' ): string {
	unset( $original );

	if ( ! is_post_type_archive( MANAGED_POST_TYPES ) ) {
		return $title;
	}

	$queried_object = get_queried_object();

	if ( ! ( $queried_object instanceof \WP_Post_Type ) || empty( $queried_object->labels->name ) ) {
		return $title;
	}

	return $queried_object->labels->name;
}
