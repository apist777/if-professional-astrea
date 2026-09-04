<?php
/**
 * Breadcrumb — single hierarchy resolver for both the visual UI and the
 * BreadcrumbList JSON-LD (Construction Order 006, Decision 010/026).
 *
 * `get_breadcrumb_items()` is the ONLY place that resolves "where am I in
 * the site hierarchy" — using nothing but WordPress's own standard post
 * hierarchy / post type archive / taxonomy APIs (no Breadcrumb-specific
 * data is stored anywhere). Both the visual Dynamic Block below and
 * seo-structured-data.php's BreadcrumbList JSON-LD call this single
 * function, so they cannot structurally diverge from each other.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Resolves the current request's breadcrumb trail.
 *
 * Each item is `['label' => string, 'url' => string|null]`. The last item
 * always has `url === null` (the current page, not linked). Returns an
 * empty array on the front page (nothing to show).
 *
 * @return array<int, array{label: string, url: ?string}>
 */
function get_breadcrumb_items(): array {
	if ( is_front_page() ) {
		return array();
	}

	$home = array(
		'label' => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);

	if ( is_singular( 'astrea_service' ) || is_post_type_archive( 'astrea_service' ) ) {
		return append_post_type_trail( $home, 'astrea_service' );
	}

	if ( is_singular( 'astrea_professional' ) || is_post_type_archive( 'astrea_professional' ) ) {
		return append_post_type_trail( $home, 'astrea_professional' );
	}

	if ( is_singular( 'astrea_faq' ) || is_post_type_archive( 'astrea_faq' ) ) {
		return append_post_type_trail( $home, 'astrea_faq' );
	}

	// Construction Order 015D: astrea_case/astrea_voice were previously
	// missing from this list, so a CASE Single silently fell through to the
	// generic 2-level `is_singular()` branch below — dropping the "対応事例"
	// archive link that Service/Professional Singles already show. Wiring
	// them into the same existing `append_post_type_trail()` helper closes
	// that gap; VOICE has no Single template today, but including it keeps
	// the hierarchy correct if that ever changes.
	if ( is_singular( 'astrea_case' ) || is_post_type_archive( 'astrea_case' ) ) {
		return append_post_type_trail( $home, 'astrea_case' );
	}

	if ( is_singular( 'astrea_voice' ) || is_post_type_archive( 'astrea_voice' ) ) {
		return append_post_type_trail( $home, 'astrea_voice' );
	}

	if ( is_tax( 'astrea_faq_category' ) ) {
		$term  = get_queried_object();
		$items = array( $home );

		$archive_link = get_post_type_archive_link( 'astrea_faq' );
		if ( $archive_link ) {
			$post_type_object = get_post_type_object( 'astrea_faq' );
			$items[]          = array(
				'label' => $post_type_object ? $post_type_object->labels->name : __( 'FAQ', 'astrea-core' ),
				'url'   => $archive_link,
			);
		}

		$items[] = array(
			'label' => $term instanceof \WP_Term ? $term->name : '',
			'url'   => null,
		);

		return $items;
	}

	if ( is_page() ) {
		$items     = array( $home );
		$ancestors = array_reverse( get_post_ancestors( get_queried_object_id() ) );

		foreach ( $ancestors as $ancestor_id ) {
			$items[] = array(
				'label' => get_the_title( $ancestor_id ),
				'url'   => get_permalink( $ancestor_id ),
			);
		}

		$items[] = array(
			'label' => get_the_title(),
			'url'   => null,
		);

		return $items;
	}

	if ( is_singular() || is_single() ) {
		return array(
			$home,
			array(
				'label' => get_the_title(),
				'url'   => null,
			),
		);
	}

	if ( is_search() ) {
		// Construction Order 017 (Historical Finding 7): get_the_archive_title()
		// has no is_search() branch of its own (it only special-cases
		// category/tag/author/date/post-type/tax archives), so it silently
		// fell through to Core's generic "Archives" string here — the same
		// request already renders a proper "「%s」の検索結果" title via
		// Core's own Query Title block. Matching that instead of the
		// generic fallback so the breadcrumb (and the BreadcrumbList
		// JSON-LD built from this same function) actually says what the
		// visitor searched for.
		return array(
			$home,
			array(
				/* translators: %s: the searched keyword. */
				'label' => sprintf( __( '「%s」の検索結果', 'astrea-core' ), get_search_query() ),
				'url'   => null,
			),
		);
	}

	if ( is_archive() || is_404() ) {
		return array(
			$home,
			array(
				'label' => wp_strip_all_tags( get_the_archive_title() ),
				'url'   => null,
			),
		);
	}

	return array( $home );
}

/**
 * Builds [Home, Archive, (Current)] for one of ASTREA's own post types.
 *
 * @param array  $home      The Home breadcrumb item.
 * @param string $post_type Post type slug.
 * @return array
 */
function append_post_type_trail( array $home, string $post_type ): array {
	$items            = array( $home );
	$post_type_object = get_post_type_object( $post_type );
	$archive_link     = get_post_type_archive_link( $post_type );

	if ( $archive_link && $post_type_object ) {
		$items[] = array(
			'label' => $post_type_object->labels->name,
			'url'   => is_singular( $post_type ) ? $archive_link : null,
		);
	}

	if ( is_singular( $post_type ) ) {
		$items[] = array(
			'label' => get_the_title(),
			'url'   => null,
		);
	}

	return $items;
}

add_action( 'init', __NAMESPACE__ . '\\register_breadcrumb_block' );

/**
 * Registers the astrea/breadcrumb Dynamic Block (visual UI).
 *
 * @return void
 */
function register_breadcrumb_block() {
	register_block_type(
		'astrea/breadcrumb',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_breadcrumb_block',
			'editor_script_handles' => array( \Astrea\Core\EditorBlocks\SCRIPT_HANDLE ),
			'attributes'            => array(),
		)
	);
}

/**
 * Renders the visual Breadcrumb as semantic HTML: `<nav aria-label>` +
 * `<ol>`, current page marked with `aria-current="page"` and not linked.
 * No JavaScript, no ARIA beyond what native elements already provide.
 *
 * @return string
 */
function render_breadcrumb_block(): string {
	$items = get_breadcrumb_items();

	if ( count( $items ) < 2 ) {
		return ''; // Front page, or nothing meaningful to show.
	}

	$last_index = count( $items ) - 1;
	$list_html  = '';

	foreach ( $items as $index => $item ) {
		if ( $index === $last_index || empty( $item['url'] ) ) {
			$list_html .= '<li><span aria-current="page">' . esc_html( $item['label'] ) . '</span></li>';
		} else {
			$list_html .= '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
		}
	}

	return sprintf(
		'<nav class="wp-block-astrea-breadcrumb" aria-label="%s"><ol>%s</ol></nav>',
		esc_attr__( 'パンくずリスト', 'astrea-core' ),
		$list_html
	);
}
