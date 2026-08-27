<?php
/**
 * Service — Core-owned data layer (Construction Order 004).
 *
 * Per 02_astrea_free_v1_specification.md §7, Service is user-registered
 * content describing what the office can be consulted about. FREE v1 has
 * no occupation-specific service content — only the generic "name" and
 * "description" that §7 requires. No fields beyond what §7 specifies have
 * been added (no category/grouping — not mentioned in spec).
 *
 * §7 explicitly requires both an archive ("サービス一覧") and individual
 * pages ("個別Service"), so this is a standard public post type with an
 * archive, unlike Price (see price.php) which has no such requirement.
 *
 * - 名称 (name)   -> post_title
 * - 説明 (description) -> post_content (WordPress's own block editor + Kses)
 *
 * There is deliberately no Service <-> Price relation and no Service
 * category taxonomy: neither has an explicit basis in the specification
 * (see docs/research/2026-08-26_construction_order_004_research.md §1.1/1.2).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Post type slug (also the Theme/Core public contract — see archive-astrea_service.html). */
const POST_TYPE = 'astrea_service';

add_action( 'init', __NAMESPACE__ . '\\register_post_type_and_meta' );

/**
 * Registers the astrea_service post type. No custom postmeta: Service has
 * no fields beyond the standard title/content WordPress already provides.
 *
 * @return void
 */
function register_post_type_and_meta() {
	register_post_type(
		POST_TYPE,
		array(
			'label'        => __( '取扱業務', 'astrea-core' ),
			'description'  => __( '取扱業務の一覧です。', 'astrea-core' ),
			'labels'       => array(
				'name'          => __( '取扱業務', 'astrea-core' ),
				'singular_name' => __( '取扱業務', 'astrea-core' ),
				'add_new_item'  => __( '取扱業務を追加', 'astrea-core' ),
				'edit_item'     => __( '取扱業務を編集', 'astrea-core' ),
				'all_items'     => __( '取扱業務一覧', 'astrea-core' ),
			),
			'public'       => true,
			'has_archive'  => 'services',
			'rewrite'      => array( 'slug' => 'services' ),
			'show_ui'      => true,
			'show_in_menu' => 'astrea-core',
			'show_in_rest' => true,
			'hierarchical' => false,
			'supports'     => array( 'title', 'editor', 'page-attributes' ),
			'menu_icon'    => 'dashicons-portfolio',
		)
	);
}

add_action( 'pre_get_posts', __NAMESPACE__ . '\\enforce_deterministic_order' );

/**
 * Ensures a stable display order when nothing more specific was requested.
 *
 * @param \WP_Query $query The query being filtered.
 * @return void
 */
function enforce_deterministic_order( \WP_Query $query ) {
	\Astrea\Core\Shared\enforce_deterministic_order( POST_TYPE, $query );
}

/**
 * Public read boundary: a single Service.
 *
 * Callers must not query astrea_service posts directly — the internal
 * representation may change without notice (Decision 013 / 021).
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function get_service( int $post_id ): ?array {
	$post = \Astrea\Core\Shared\get_published_post( $post_id, POST_TYPE );

	if ( null === $post ) {
		return null;
	}

	return to_array( $post );
}

/**
 * Public read boundary: all published Services, in the same deterministic
 * order Theme queries will see (menu_order, title, ID).
 *
 * @return array[]
 */
function get_services(): array {
	return array_map( __NAMESPACE__ . '\\to_array', \Astrea\Core\Shared\get_published_posts( POST_TYPE ) );
}

/**
 * Converts a WP_Post into the public Service array shape.
 *
 * @param \WP_Post $post A published astrea_service post.
 * @return array
 */
function to_array( \WP_Post $post ): array {
	return array(
		'id'          => $post->ID,
		'name'        => $post->post_title,
		'description' => $post->post_content,
	);
}
