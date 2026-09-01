<?php
/**
 * RESULTS（実績） — Core-owned data layer (Construction Order 010).
 *
 * Per 02_astrea_free_v1_specification.md §12 ("RESULTS＝公開可能な実績・
 * 数字") and Decision 029. RESULTS entries are short label+value stat
 * highlights ("相談実績" / "1,000件以上", "開業年" / "2015年") — not
 * narrative content — so this follows Price's minimal CPT shape rather
 * than CASE/Service's: no body editor, no individual URL (§10 gives no
 * basis for a standalone "result" page any more than Price has one for a
 * standalone price).
 *
 * - 実績ラベル (label) -> post_title
 * - 実績値 (value)     -> postmeta astrea_result_value (free text — never
 *   assumed numeric; "2015年"/"全国対応"/"多数" are all valid, matching
 *   Price's own free-text `amount` precedent). No structured data is
 *   generated from this value (see includes/results-list-block.php).
 * - 表示順 (order)     -> menu_order (page-attributes)
 * - 公開状態 (status)  -> post_status (WordPress's own mechanism)
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Result;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Post type slug. Not public — no individual "result" page makes sense, same reasoning as Price. */
const POST_TYPE = 'astrea_result';

/** Postmeta key. Public contract for anything reading this directly. */
const META_VALUE = 'astrea_result_value';

/**
 * Postmeta key: which ASTREA Icon System glyph (see icon-system.php)
 * this RESULT displays. Construction Order 016D-R1 — same reasoning as
 * Service's META_ICON (service.php): Owner forbade guessing an icon from
 * this entry's free-text label/value, so a site owner picks one from a
 * fixed list instead (see result-admin.php).
 */
const META_ICON = 'astrea_result_icon';

add_action( 'init', __NAMESPACE__ . '\\register_post_type_and_meta' );

/**
 * Registers the astrea_result post type and its meta field.
 *
 * @return void
 */
function register_post_type_and_meta() {
	register_post_type(
		POST_TYPE,
		array(
			'label'               => __( '実績', 'astrea-core' ),
			'labels'              => array(
				'name'          => __( '実績', 'astrea-core' ),
				'singular_name' => __( '実績', 'astrea-core' ),
				'add_new_item'  => __( '実績を追加', 'astrea-core' ),
				'edit_item'     => __( '実績を編集', 'astrea-core' ),
				'all_items'     => __( '実績一覧', 'astrea-core' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'astrea-core',
			// Construction Order 011 Security Audit (MEDIUM finding): same
			// REST-exposure contradiction as astrea_price (see price.php's
			// comment on this same key for the full explanation).
			'show_in_rest'        => false,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'page-attributes' ),
			'menu_icon'           => 'dashicons-chart-bar',
		)
	);

	register_post_meta(
		POST_TYPE,
		META_VALUE,
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_post_meta(
		POST_TYPE,
		META_ICON,
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => \Astrea\Core\IconSystem\default_slug( 'result' ),
			'sanitize_callback' => \Astrea\Core\IconSystem\make_sanitizer( 'result' ),
			'show_in_rest'      => true,
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
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
 * Public read boundary: a single published RESULTS entry by ID.
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function get_result( int $post_id ): ?array {
	$post = \Astrea\Core\Shared\get_published_post( $post_id, POST_TYPE );

	if ( null === $post ) {
		return null;
	}

	return to_array( $post );
}

/**
 * Public read boundary: all published RESULTS entries, in the standard
 * deterministic order.
 *
 * @return array[]
 */
function get_results(): array {
	return array_map( __NAMESPACE__ . '\\to_array', \Astrea\Core\Shared\get_published_posts( POST_TYPE ) );
}

/**
 * Converts a RESULTS post into its public array shape.
 *
 * @param \WP_Post $post RESULTS post.
 * @return array
 */
function to_array( \WP_Post $post ): array {
	$icon = get_post_meta( $post->ID, META_ICON, true );

	return array(
		'id'    => $post->ID,
		'label' => $post->post_title,
		'value' => get_post_meta( $post->ID, META_VALUE, true ),
		'icon'  => '' !== $icon ? $icon : \Astrea\Core\IconSystem\default_slug( 'result' ),
	);
}
