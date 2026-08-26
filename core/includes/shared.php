<?php
/**
 * Shared helpers reused across ASTREA Core's 0..N record data features
 * (Professional Profile, Service, Price, FAQ).
 *
 * Extracted in Construction Order 004 after the same deterministic-order
 * and "safely read one published post" logic was found duplicated
 * verbatim across Professional Profile and the new Service/Price/FAQ
 * modules. This is a small set of generic-but-not-a-framework utilities,
 * not a content abstraction layer — each module still defines its own
 * fields, sanitization, and public API shape.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Shared;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Forces a deterministic default ordering (menu_order, then title, then ID)
 * on a query for the given post type, unless the query already asked for a
 * specific orderby.
 *
 * @param string    $post_type The post type this ordering applies to.
 * @param \WP_Query $query     The query being filtered (from pre_get_posts).
 * @return void
 */
function enforce_deterministic_order( string $post_type, \WP_Query $query ) {
	if ( $post_type !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( '' !== $query->get( 'orderby' ) ) {
		return; // Something more specific was explicitly requested; don't override it.
	}

	$query->set(
		'orderby',
		array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
			'ID'         => 'ASC',
		)
	);
}

/**
 * Safely resolves a post ID to a WP_Post, guarding against a wrong post
 * type, an unpublished post, or a nonexistent ID.
 *
 * @param int    $post_id   Post ID.
 * @param string $post_type Expected post type.
 * @return \WP_Post|null
 */
function get_published_post( int $post_id, string $post_type ): ?\WP_Post {
	$post = get_post( $post_id );

	if ( ! $post instanceof \WP_Post || $post_type !== $post->post_type || 'publish' !== $post->post_status ) {
		return null;
	}

	return $post;
}

/**
 * Fetches all published posts of the given type in the standard
 * deterministic order (menu_order, title, ID).
 *
 * @param string $post_type Post type.
 * @return \WP_Post[]
 */
function get_published_posts( string $post_type ): array {
	return get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
				'ID'         => 'ASC',
			),
		)
	);
}
