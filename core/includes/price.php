<?php
/**
 * Price — Core-owned data layer (Construction Order 004).
 *
 * Per 02_astrea_free_v1_specification.md §10, price information covers
 * fixed amounts, "from ¥X", monthly, hourly, free, custom-quote and other
 * free-text pricing styles — deliberately heterogeneous, so the amount
 * itself is stored as sanitized free text rather than a rigid numeric
 * field. Full alignment with schema.org Offer structured data is not
 * always mechanically possible for free-text amounts and is explicitly
 * out of scope for this Construction Order (see
 * docs/research/2026-08-26_construction_order_004_research.md §3.2/§6).
 *
 * Unlike Service, §10 gives no textual basis for individual Price URLs
 * (no "個別Price" reuse context is mentioned, only a single aggregate
 * "Priceページ") — so this post type is intentionally not public. It is
 * still manageable via the standard post list/edit UI and readable by
 * Query Loop blocks embedded in ordinary pages via a Theme Pattern
 * (see theme/patterns/price-list.php).
 *
 * There is deliberately no Price <-> Service relation: §10 only says the
 * same price information can be referenced for display from Service
 * pages, not that each Price entry has a formal foreign key to a Service
 * — and Construction Order 004 explicitly forbids inventing one without a
 * textual basis.
 *
 * - 表示名 (name)   -> post_title
 * - 金額 (amount)   -> postmeta astrea_price_amount (free text)
 * - 補足 (notes)    -> postmeta astrea_price_notes (free text)
 * - 料金グループ (group) -> postmeta astrea_price_group (free text)
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Price;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Post type slug. Not public — see file header. */
const POST_TYPE = 'astrea_price';

/** Postmeta keys. Public contract for anything reading these directly via core/post-meta bindings. */
const META_AMOUNT = 'astrea_price_amount';
const META_NOTES  = 'astrea_price_notes';
const META_GROUP  = 'astrea_price_group';

/**
 * All Price meta keys and how to sanitize each.
 *
 * @return array<string,callable>
 */
function meta_sanitizers(): array {
	return array(
		META_AMOUNT => 'sanitize_textarea_field',
		META_NOTES  => 'sanitize_textarea_field',
		META_GROUP  => 'sanitize_text_field',
	);
}

add_action( 'init', __NAMESPACE__ . '\\register_post_type_and_meta' );

/**
 * Registers the astrea_price post type and its meta fields.
 *
 * @return void
 */
function register_post_type_and_meta() {
	register_post_type(
		POST_TYPE,
		array(
			'label'               => __( '料金', 'astrea-core' ),
			'labels'              => array(
				'name'          => __( '料金', 'astrea-core' ),
				'singular_name' => __( '料金', 'astrea-core' ),
				'add_new_item'  => __( '料金を追加', 'astrea-core' ),
				'edit_item'     => __( '料金を編集', 'astrea-core' ),
				'all_items'     => __( '料金一覧', 'astrea-core' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'astrea-core',
			'show_in_rest'        => true,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'page-attributes' ),
			'menu_icon'           => 'dashicons-money-alt',
		)
	);

	foreach ( meta_sanitizers() as $meta_key => $sanitize_callback ) {
		register_post_meta(
			POST_TYPE,
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => $sanitize_callback,
				'show_in_rest'      => true,
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
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
 * Public read boundary: a single Price entry.
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function get_price( int $post_id ): ?array {
	$post = \Astrea\Core\Shared\get_published_post( $post_id, POST_TYPE );

	if ( null === $post ) {
		return null;
	}

	return to_array( $post );
}

/**
 * Public read boundary: all published Price entries, in the same
 * deterministic order Theme queries will see (menu_order, title, ID).
 *
 * @return array[]
 */
function get_prices(): array {
	return array_map( __NAMESPACE__ . '\\to_array', \Astrea\Core\Shared\get_published_posts( POST_TYPE ) );
}

/**
 * Converts a WP_Post into the public Price array shape.
 *
 * @param \WP_Post $post A published astrea_price post.
 * @return array
 */
function to_array( \WP_Post $post ): array {
	return array(
		'id'     => $post->ID,
		'name'   => $post->post_title,
		'amount' => get_post_meta( $post->ID, META_AMOUNT, true ),
		'notes'  => get_post_meta( $post->ID, META_NOTES, true ),
		'group'  => get_post_meta( $post->ID, META_GROUP, true ),
	);
}
