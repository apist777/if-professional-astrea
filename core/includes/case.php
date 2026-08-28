<?php
/**
 * CASE（対応事例） — Core-owned data layer (Construction Order 010).
 *
 * Per 02_astrea_free_v1_specification.md §12 ("CASE＝対応事例…CASEはService
 * 等との関連付けを可能とする") and Decision 029 (CASE/RESULTS/VOICE are FREE
 * v1 Release Blocking). CASE is narrative content — a case study of how a
 * request was handled — so it follows Service's CPT shape (title, content,
 * excerpt, featured image, deterministic order) rather than Price's
 * minimal shape. Fields:
 *
 * - タイトル (title)     -> post_title
 * - 概要 (excerpt)       -> post_excerpt (WordPress's own field; used for
 *   Archive/Teaser cards, no separate "summary" meta invented)
 * - 本文 (body)          -> post_content (the outcome/result is written as
 *   part of the narrative — no separate "結果" meta; a case study naturally
 *   includes its own outcome in prose)
 * - 画像 (image)         -> Featured Image (WordPress's own mechanism)
 * - 表示順 (order)       -> menu_order (page-attributes)
 * - 公開状態 (status)    -> post_status (WordPress's own mechanism)
 * - 関連Service          -> postmeta astrea_case_related_services (array of
 *   astrea_service IDs), sanitized via the same
 *   Shared\sanitize_related_service_ids() FAQ already uses — the exact same
 *   responsibility (keep only currently-published Service IDs), extracted
 *   in this Construction Order rather than duplicated.
 *
 * Namespaced as `CaseStudy` rather than the PHP-keyword-adjacent `Case` to
 * avoid any tooling/readability surprises; the post type slug itself
 * (`astrea_case`) is unaffected, matching the astrea_service/astrea_faq
 * naming convention.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\CaseStudy;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Post type slug (also the Theme/Core public contract — see archive-astrea_case.html). */
const POST_TYPE = 'astrea_case';

/** Postmeta key. Public contract for anything reading this directly. */
const META_RELATED_SERVICES = 'astrea_case_related_services';

add_action( 'init', __NAMESPACE__ . '\\register_post_type_and_meta' );

/**
 * Registers the astrea_case post type and its meta field.
 *
 * @return void
 */
function register_post_type_and_meta() {
	register_post_type(
		POST_TYPE,
		array(
			'label'        => __( '対応事例', 'astrea-core' ),
			'description'  => __( '対応事例の一覧です。', 'astrea-core' ),
			'labels'       => array(
				'name'          => __( '対応事例', 'astrea-core' ),
				'singular_name' => __( '対応事例', 'astrea-core' ),
				'add_new_item'  => __( '対応事例を追加', 'astrea-core' ),
				'edit_item'     => __( '対応事例を編集', 'astrea-core' ),
				'all_items'     => __( '対応事例一覧', 'astrea-core' ),
			),
			'public'       => true,
			'has_archive'  => 'cases',
			'rewrite'      => array( 'slug' => 'cases' ),
			'show_ui'      => true,
			'show_in_menu' => 'astrea-core',
			'show_in_rest' => true,
			'hierarchical' => false,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'menu_icon'    => 'dashicons-portfolio',
		)
	);

	register_post_meta(
		POST_TYPE,
		META_RELATED_SERVICES,
		array(
			'type'              => 'array',
			'single'            => true,
			'default'           => array(),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_related_services',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
			),
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}

/**
 * Sanitizes the related-Service list. Delegates to the shared
 * implementation FAQ also uses (Astrea\Core\Shared\sanitize_related_service_ids()).
 *
 * @param mixed $value Raw value (expected: array of ints/numeric strings).
 * @return int[]
 */
function sanitize_related_services( $value ): array {
	return \Astrea\Core\Shared\sanitize_related_service_ids( $value );
}

add_action( 'pre_get_posts', __NAMESPACE__ . '\\enforce_deterministic_order' );

/**
 * Forces the standard menu_order/title/ID ordering unless a query
 * explicitly asks for something else (see shared.php).
 *
 * @param \WP_Query $query The query being filtered.
 * @return void
 */
function enforce_deterministic_order( \WP_Query $query ) {
	\Astrea\Core\Shared\enforce_deterministic_order( POST_TYPE, $query );
}

/**
 * Public read boundary: a single published CASE by ID.
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function get_case( int $post_id ): ?array {
	$post = \Astrea\Core\Shared\get_published_post( $post_id, POST_TYPE );

	if ( null === $post ) {
		return null;
	}

	return to_array( $post );
}

/**
 * Public read boundary: all published CASEs, in the standard deterministic order.
 *
 * @return array[]
 */
function get_cases(): array {
	return array_map( __NAMESPACE__ . '\\to_array', \Astrea\Core\Shared\get_published_posts( POST_TYPE ) );
}

/**
 * Public read boundary: published CASEs related to a given Service,
 * mirroring Astrea\Core\Faq\get_faqs_for_service().
 *
 * @param int $service_id Service post ID.
 * @return array[]
 */
function get_cases_for_service( int $service_id ): array {
	return array_values(
		array_filter(
			get_cases(),
			static function ( array $case_entry ) use ( $service_id ): bool {
				return in_array( $service_id, $case_entry['related_services'], true );
			}
		)
	);
}

/**
 * Converts a CASE post into its public array shape.
 *
 * @param \WP_Post $post CASE post.
 * @return array
 */
function to_array( \WP_Post $post ): array {
	$photo_id = get_post_thumbnail_id( $post );

	// Guard against a missing/deleted attachment: don't hand back a dangling ID.
	if ( $photo_id && 'attachment' !== get_post_type( $photo_id ) ) {
		$photo_id = 0;
	}

	$related_services = get_post_meta( $post->ID, META_RELATED_SERVICES, true );

	return array(
		'id'               => $post->ID,
		'title'            => $post->post_title,
		'excerpt'          => has_excerpt( $post ) ? $post->post_excerpt : '',
		'content'          => $post->post_content,
		'photo_id'         => $photo_id ? $photo_id : null,
		'related_services' => is_array( $related_services ) ? array_map( 'absint', $related_services ) : array(),
	);
}
