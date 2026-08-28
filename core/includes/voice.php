<?php
/**
 * VOICE（お客様の声） — Core-owned data layer (Construction Order 010).
 *
 * Per 02_astrea_free_v1_specification.md §12 ("VOICE＝お客様の声") and
 * Decision 029. FREE v1 ships the minimal structure only:
 *
 * - 表示名/ラベル (display name) -> post_title. This is explicitly a
 *   public-facing label ("40代・会社経営者様" etc.), never a real name —
 *   the admin screen's own description text says so; no separate "real
 *   name" field exists to accidentally fill in.
 * - 本文 (testimonial body)      -> post_content
 * - 表示順 (order)               -> menu_order (page-attributes)
 * - 公開状態 (status)            -> post_status (WordPress's own mechanism)
 *
 * Deliberately does NOT support Featured Image: a real customer's photo
 * carries materially more Privacy/consent risk than an anonymized text
 * attribution, and CASE's lower-risk illustrative-image use case doesn't
 * transfer here (FIX confirmed at Construction Order 010 kickoff).
 * Deliberately has NO related_services meta and no admin meta box at all —
 * VOICE ships as a plain, standalone content type in FREE v1 (FIX
 * confirmed at kickoff; Post v1 may reconsider if real usage shows a
 * need). No consent-tracking metadata of any kind is implemented — see
 * Decision 029 (VOICE's permission-confirmation UI is Post v1); the
 * responsibility to confirm permission before publishing stays entirely
 * editorial, exactly like publishing any other content type.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Voice;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Post type slug (also the Theme/Core public contract — see archive-astrea_voice.html). */
const POST_TYPE = 'astrea_voice';

add_action( 'init', __NAMESPACE__ . '\\register_post_type' );

/**
 * Registers the astrea_voice post type. No custom postmeta.
 *
 * @return void
 */
function register_post_type() {
	\register_post_type(
		POST_TYPE,
		array(
			'label'        => __( 'お客様の声', 'astrea-core' ),
			'description'  => __( 'お客様の声の一覧です。', 'astrea-core' ),
			'labels'       => array(
				'name'          => __( 'お客様の声', 'astrea-core' ),
				'singular_name' => __( 'お客様の声', 'astrea-core' ),
				'add_new_item'  => __( 'お客様の声を追加', 'astrea-core' ),
				'edit_item'     => __( 'お客様の声を編集', 'astrea-core' ),
				'all_items'     => __( 'お客様の声一覧', 'astrea-core' ),
			),
			'public'       => true,
			'has_archive'  => 'voices',
			'rewrite'      => array( 'slug' => 'voices' ),
			'show_ui'      => true,
			'show_in_menu' => 'astrea-core',
			'show_in_rest' => true,
			'hierarchical' => false,
			'supports'     => array( 'title', 'editor', 'page-attributes' ),
			'menu_icon'    => 'dashicons-testimonial',
		)
	);
}

add_filter( 'enter_title_here', __NAMESPACE__ . '\\filter_title_placeholder', 10, 2 );

/**
 * Replaces the "Add title" placeholder with guidance not to enter a real
 * name, shown at the exact moment the site owner is about to type one —
 * WordPress's own standard mechanism for this, no custom admin notice needed.
 *
 * @param string   $placeholder Default placeholder text.
 * @param \WP_Post $post        Current post.
 * @return string
 */
function filter_title_placeholder( string $placeholder, \WP_Post $post ): string {
	if ( POST_TYPE !== $post->post_type ) {
		return $placeholder;
	}

	return __( '表示名（例：40代・会社経営者様。実名は入力しないでください）', 'astrea-core' );
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
 * Public read boundary: a single published VOICE entry by ID.
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function get_voice( int $post_id ): ?array {
	$post = \Astrea\Core\Shared\get_published_post( $post_id, POST_TYPE );

	if ( null === $post ) {
		return null;
	}

	return to_array( $post );
}

/**
 * Public read boundary: all published VOICE entries, in the standard
 * deterministic order.
 *
 * @return array[]
 */
function get_voices(): array {
	return array_map( __NAMESPACE__ . '\\to_array', \Astrea\Core\Shared\get_published_posts( POST_TYPE ) );
}

/**
 * Converts a VOICE post into its public array shape.
 *
 * @param \WP_Post $post VOICE post.
 * @return array
 */
function to_array( \WP_Post $post ): array {
	return array(
		'id'           => $post->ID,
		'display_name' => $post->post_title,
		'content'      => $post->post_content,
	);
}
