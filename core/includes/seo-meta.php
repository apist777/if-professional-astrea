<?php
/**
 * SEO — meta description, OGP, and Search Console verification meta
 * (Construction Order 006).
 *
 * Title / canonical / robots / XML Sitemap / Site Icon are WordPress
 * standard features and are NOT reimplemented here (Decision 026,
 * docs/research/2026-08-27_construction_order_006_research.md §2) — this
 * file only covers what WordPress core does not provide.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Maximum meta description length (implementation judgment, common SEO convention — not spec-mandated). */
const DESCRIPTION_MAX_LENGTH = 160;

add_action( 'wp_head', __NAMESPACE__ . '\\output_meta_description', 1 );
add_action( 'wp_head', __NAMESPACE__ . '\\output_ogp', 1 );
add_action( 'wp_head', __NAMESPACE__ . '\\output_search_console_verification', 1 );

/**
 * Outputs <meta name="description">, unless a known SEO Plugin already
 * handles it, or there is nothing meaningful to say.
 *
 * @return void
 */
function output_meta_description() {
	if ( is_known_seo_plugin_active() ) {
		return;
	}

	$description = generate_description();

	if ( '' === $description ) {
		return;
	}

	printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
}

/**
 * Generates a meta description with a safe automatic Fallback chain, per
 * context: singular content -> excerpt; taxonomy/CPT archive -> registered
 * description; otherwise -> site tagline. Never fabricates marketing copy,
 * and returns '' (no tag) rather than guessing when nothing is available.
 *
 * @return string
 */
function generate_description(): string {
	if ( is_singular() ) {
		$excerpt = wp_strip_all_tags( get_the_excerpt() );
		return truncate_description( $excerpt );
	}

	if ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		if ( $term instanceof \WP_Term && '' !== trim( $term->description ) ) {
			return truncate_description( wp_strip_all_tags( $term->description ) );
		}
	}

	if ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
		$object    = $post_type ? get_post_type_object( $post_type ) : null;

		if ( $object && ! empty( $object->description ) ) {
			return truncate_description( wp_strip_all_tags( $object->description ) );
		}
	}

	$tagline = get_bloginfo( 'description' );

	return '' !== trim( $tagline ) ? truncate_description( $tagline ) : '';
}

/**
 * Trims a description to DESCRIPTION_MAX_LENGTH without cutting mid-word
 * where reasonably avoidable.
 *
 * @param string $text Raw text.
 * @return string
 */
function truncate_description( string $text ): string {
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) );

	if ( mb_strlen( $text ) <= DESCRIPTION_MAX_LENGTH ) {
		return $text;
	}

	return mb_substr( $text, 0, DESCRIPTION_MAX_LENGTH );
}

/**
 * Outputs the minimal OGP tag set (+ twitter:card), unless a known SEO
 * Plugin already handles it.
 *
 * @return void
 */
function output_ogp() {
	if ( is_known_seo_plugin_active() ) {
		return;
	}

	$title = is_singular() ? wp_strip_all_tags( get_the_title() ) : wp_strip_all_tags( wp_get_document_title() );
	$image = resolve_ogp_image_url();

	$tags = array(
		'og:site_name' => get_bloginfo( 'name' ),
		'og:type'      => is_singular( 'post' ) ? 'article' : 'website',
		'og:title'     => $title,
		'og:url'       => current_url(),
	);

	$description = generate_description();
	if ( '' !== $description ) {
		$tags['og:description'] = $description;
	}

	foreach ( $tags as $property => $content ) {
		if ( '' === trim( (string) $content ) ) {
			continue;
		}
		printf( '<meta property="%s" content="%s" />' . "\n", esc_attr( $property ), esc_attr( $content ) );
	}

	if ( null !== $image ) {
		printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
	}

	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
}

/**
 * Resolves the OGP image: per-page Featured Image first, then the
 * site-wide fallback image (Core setting), otherwise none. Never
 * fabricates a placeholder/dummy image URL.
 *
 * @return string|null
 */
function resolve_ogp_image_url(): ?string {
	if ( is_singular() ) {
		$thumbnail_id = get_post_thumbnail_id();
		if ( $thumbnail_id ) {
			$url = wp_get_attachment_image_url( $thumbnail_id, 'full' );
			if ( $url ) {
				return $url;
			}
		}
	}

	$og_image_id = get_seo_settings()['og_image_id'];
	if ( $og_image_id > 0 ) {
		$url = wp_get_attachment_image_url( $og_image_id, 'full' );
		if ( $url ) {
			return $url;
		}
	}

	return null;
}

/**
 * The current request's canonical-ish URL, built from WordPress's own
 * canonical resolver (singular content) or its matched rewrite request
 * (archives/search/etc.) — never raw $_SERVER values.
 *
 * @return string
 */
function current_url(): string {
	if ( is_singular() ) {
		$canonical = wp_get_canonical_url();
		if ( $canonical ) {
			return $canonical;
		}
	}

	global $wp;

	return home_url( add_query_arg( array(), $wp->request ) );
}

/**
 * Outputs the Search Console HTML-tag verification meta, if a code is
 * configured. Not suppressed by known-Plugin detection (research doc §11
 * — a second, differently-valued verification meta for a different
 * service does not conflict with anything).
 *
 * @return void
 */
function output_search_console_verification() {
	$code = get_seo_settings()['search_console_verification'];

	if ( '' === $code ) {
		return;
	}

	printf( '<meta name="google-site-verification" content="%s" />' . "\n", esc_attr( $code ) );
}
