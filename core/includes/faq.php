<?php
/**
 * FAQ — Core-owned data layer (Construction Order 004).
 *
 * Per 02_astrea_free_v1_specification.md §11, the required fields are:
 * 質問、回答、カテゴリ、関連Service、表示順、公開状態、重要FAQ — all
 * implemented here, and nothing beyond this list has been added.
 *
 * - 質問 (question) -> post_title
 * - 回答 (answer)   -> post_content (WordPress's own block editor + Kses)
 * - カテゴリ (category) -> taxonomy astrea_faq_category (non-hierarchical;
 *   spec gives no basis for parent/child nesting)
 * - 関連Service (related services) -> postmeta astrea_faq_related_services
 *   (array of astrea_service post IDs; sanitized down to IDs that are
 *   actually published Service posts)
 * - 表示順 (display order) -> menu_order (page-attributes)
 * - 公開状態 (publish status) -> post_status (WordPress's own mechanism —
 *   no parallel custom field)
 * - 重要FAQ (important flag) -> postmeta astrea_faq_is_important (boolean)
 *
 * FAQPage JSON-LD structured data is explicitly NOT implemented here — see
 * docs/research/2026-08-26_construction_order_004_research.md §1.3/§6 for
 * why this is deferred to a future SEO Foundation Construction Order
 * rather than assumed.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Faq;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Post type slug (also the Theme/Core public contract — see archive-astrea_faq.html). */
const POST_TYPE = 'astrea_faq';

/** Non-hierarchical taxonomy for FAQ category grouping. */
const TAXONOMY = 'astrea_faq_category';

/** Postmeta keys. Public contract for anything reading these directly via core/post-meta bindings. */
const META_IS_IMPORTANT     = 'astrea_faq_is_important';
const META_RELATED_SERVICES = 'astrea_faq_related_services';

add_action( 'init', __NAMESPACE__ . '\\register_post_type_and_meta' );

/**
 * Registers the astrea_faq post type, its taxonomy, and its meta fields.
 *
 * @return void
 */
function register_post_type_and_meta() {
	register_post_type(
		POST_TYPE,
		array(
			'label'        => __( 'FAQ', 'astrea-core' ),
			'description'  => __( 'よくある質問の一覧です。', 'astrea-core' ),
			'labels'       => array(
				'name'          => __( 'FAQ', 'astrea-core' ),
				'singular_name' => __( 'FAQ', 'astrea-core' ),
				'add_new_item'  => __( 'FAQを追加', 'astrea-core' ),
				'edit_item'     => __( 'FAQを編集', 'astrea-core' ),
				'all_items'     => __( 'FAQ一覧', 'astrea-core' ),
			),
			'public'       => true,
			'has_archive'  => 'faq',
			'rewrite'      => array( 'slug' => 'faq' ),
			'show_ui'      => true,
			'show_in_menu' => 'astrea-core',
			'show_in_rest' => true,
			'hierarchical' => false,
			'supports'     => array( 'title', 'editor', 'page-attributes' ),
			'menu_icon'    => 'dashicons-editor-help',
		)
	);

	register_taxonomy(
		TAXONOMY,
		POST_TYPE,
		array(
			'label'             => __( 'FAQカテゴリ', 'astrea-core' ),
			'labels'            => array(
				'name'          => __( 'FAQカテゴリ', 'astrea-core' ),
				'singular_name' => __( 'FAQカテゴリ', 'astrea-core' ),
			),
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'faq-category' ),
		)
	);

	register_post_meta(
		POST_TYPE,
		META_IS_IMPORTANT,
		array(
			'type'              => 'boolean',
			'single'            => true,
			'default'           => false,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => true,
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
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
 * Sanitizes the related-Service list down to IDs that are actually
 * published astrea_service posts. Unknown, deleted, unpublished, or
 * non-Service IDs are silently dropped rather than stored — this is the
 * boundary that guards get_faqs_for_service() from ever returning a
 * dangling reference.
 *
 * @param mixed $value Raw value (expected: array of ints/numeric strings).
 * @return int[]
 */
function sanitize_related_services( $value ): array {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$ids = array_unique( array_map( 'absint', $value ) );

	$ids = array_values(
		array_filter(
			$ids,
			static function ( int $id ): bool {
				return null !== \Astrea\Core\Service\get_service( $id );
			}
		)
	);

	return $ids;
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
 * Public read boundary: a single FAQ.
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function get_faq( int $post_id ): ?array {
	$post = \Astrea\Core\Shared\get_published_post( $post_id, POST_TYPE );

	if ( null === $post ) {
		return null;
	}

	return to_array( $post );
}

/**
 * Public read boundary: all published FAQs, in the same deterministic
 * order Theme queries will see (menu_order, title, ID).
 *
 * @return array[]
 */
function get_faqs(): array {
	return array_map( __NAMESPACE__ . '\\to_array', \Astrea\Core\Shared\get_published_posts( POST_TYPE ) );
}

/**
 * Public read boundary: published FAQs flagged as important, in the same
 * deterministic order as get_faqs(). Same pattern as
 * Astrea\Core\ProfessionalProfile\get_representatives().
 *
 * @return array[]
 */
function get_important_faqs(): array {
	return array_values(
		array_filter(
			get_faqs(),
			static function ( array $faq ): bool {
				return ! empty( $faq['is_important'] );
			}
		)
	);
}

/**
 * Public read boundary: published FAQs related to a given Service,
 * supporting §11's "同じFAQを…Serviceページ等で再利用する" reuse
 * requirement. Not yet wired into a Theme template (see research doc
 * §5) — the API exists and is tested, Theme display is future Pattern
 * work.
 *
 * @param int $service_id Service post ID.
 * @return array[]
 */
function get_faqs_for_service( int $service_id ): array {
	return array_values(
		array_filter(
			get_faqs(),
			static function ( array $faq ) use ( $service_id ): bool {
				return in_array( $service_id, $faq['related_services'], true );
			}
		)
	);
}

/**
 * Converts a WP_Post into the public FAQ array shape.
 *
 * @param \WP_Post $post A published astrea_faq post.
 * @return array
 */
function to_array( \WP_Post $post ): array {
	$categories = wp_get_post_terms( $post->ID, TAXONOMY, array( 'fields' => 'names' ) );

	if ( is_wp_error( $categories ) ) {
		$categories = array();
	}

	$related_services = get_post_meta( $post->ID, META_RELATED_SERVICES, true );

	return array(
		'id'               => $post->ID,
		'question'         => $post->post_title,
		'answer'           => $post->post_content,
		'categories'       => $categories,
		'related_services' => is_array( $related_services ) ? array_map( 'absint', $related_services ) : array(),
		'is_important'     => (bool) get_post_meta( $post->ID, META_IS_IMPORTANT, true ),
	);
}
