<?php
/**
 * Professional Profile — Core-owned data layer.
 *
 * Professional Profile is the second ASTREA Core data feature
 * (Construction Order 003). Per Decision 022, it is responsible for the
 * professional(s) belonging to the Office — as opposed to Office Profile
 * (Construction Order 002), which is the office/organization itself.
 *
 * A site has exactly one Office (Office Profile is a single Option), and
 * 0..N Professional Profiles. There is deliberately no "belongs to office"
 * foreign key: since FREE v1 is single-site/single-office, every
 * Professional Profile on the site implicitly belongs to that one Office.
 *
 * Fields implemented, per 02_astrea_free_v1_specification.md §8 and
 * Decision 022 — nothing added beyond what is already specified:
 *
 * - 氏名 (name)          -> post_title
 * - 紹介文 (bio)         -> post_content (WordPress's own block editor + Kses)
 * - 写真 (photo)         -> featured image (a Media Library attachment, not copied)
 * - 資格・肩書           -> postmeta astrea_professional_qualification
 * - 経歴                 -> postmeta astrea_professional_career
 * - 学歴                 -> postmeta astrea_professional_education
 * - 所属                 -> postmeta astrea_professional_affiliation
 * - 登録情報             -> postmeta astrea_professional_registration_info
 *
 * All fields are optional (02仕様書§8: "すべて任意とし、項目を埋めなければ
 * デザインが成立しない構造にはしない") — including 氏名. No field is
 * enforced as required at the storage layer.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\ProfessionalProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Post type slug (also the Theme/Core public contract — see archive-astrea_professional.html). */
const POST_TYPE = 'astrea_professional';

/** Postmeta keys. Public contract for anything reading these directly via core/post-meta bindings. */
const META_QUALIFICATION     = 'astrea_professional_qualification';
const META_CAREER            = 'astrea_professional_career';
const META_EDUCATION         = 'astrea_professional_education';
const META_AFFILIATION       = 'astrea_professional_affiliation';
const META_REGISTRATION_INFO = 'astrea_professional_registration_info';

/**
 * All Professional Profile meta keys and how to sanitize each.
 *
 * @return array<string,callable>
 */
function meta_sanitizers(): array {
	return array(
		META_QUALIFICATION     => 'sanitize_text_field',
		META_CAREER            => 'sanitize_textarea_field',
		META_EDUCATION         => 'sanitize_textarea_field',
		META_AFFILIATION       => 'sanitize_text_field',
		META_REGISTRATION_INFO => 'sanitize_text_field',
	);
}

add_action( 'init', __NAMESPACE__ . '\\register_post_type_and_meta' );

/**
 * Registers the astrea_professional post type and its meta fields.
 *
 * @return void
 */
function register_post_type_and_meta() {
	register_post_type(
		POST_TYPE,
		array(
			'label'        => __( '専門家プロフィール', 'astrea-core' ),
			'labels'       => array(
				'name'          => __( '専門家プロフィール', 'astrea-core' ),
				'singular_name' => __( '専門家プロフィール', 'astrea-core' ),
				'add_new_item'  => __( '専門家プロフィールを追加', 'astrea-core' ),
				'edit_item'     => __( '専門家プロフィールを編集', 'astrea-core' ),
				'all_items'     => __( '専門家プロフィール一覧', 'astrea-core' ),
			),
			'public'       => true,
			'has_archive'  => 'professionals',
			'rewrite'      => array( 'slug' => 'professionals' ),
			'show_ui'      => true,
			'show_in_menu' => 'astrea-core',
			'show_in_rest' => true,
			'hierarchical' => false,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'menu_icon'    => 'dashicons-groups',
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
 * The `menu_order` field defaults to 0 for every new post, so relying on
 * it alone produces ties. When a query for this post type didn't ask for a
 * specific orderby, fall back to menu_order, then title, then ID so the
 * result is deterministic instead of depending on incidental DB order.
 *
 * @param \WP_Query $query The query being filtered.
 * @return void
 */
function enforce_deterministic_order( \WP_Query $query ) {
	if ( POST_TYPE !== $query->get( 'post_type' ) ) {
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
 * Public read boundary: a single Professional Profile.
 *
 * This is the only supported way for other code (Theme, future PRO
 * products, future Dynamic Blocks) to read Professional Profile data as a
 * plain array rather than through WordPress's native post/Query Loop
 * rendering. Callers must not read post meta keys or query astrea_professional
 * posts directly — the internal representation may change without notice
 * (Decision 013 / 021).
 *
 * Guards against invalid-ID reference: returns null unless $post_id is an
 * existing, published astrea_professional post.
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function get_profile( int $post_id ): ?array {
	$post = get_post( $post_id );

	if ( ! $post instanceof \WP_Post || POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
		return null;
	}

	return to_array( $post );
}

/**
 * Public read boundary: all published Professional Profiles, in the same
 * deterministic order Theme queries will see (menu_order, title, ID).
 *
 * @return array[]
 */
function get_profiles(): array {
	$posts = get_posts(
		array(
			'post_type'      => POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
				'ID'         => 'ASC',
			),
		)
	);

	return array_map( __NAMESPACE__ . '\\to_array', $posts );
}

/**
 * Converts a WP_Post into the public Professional Profile array shape.
 *
 * @param \WP_Post $post A published astrea_professional post.
 * @return array
 */
function to_array( \WP_Post $post ): array {
	$photo_id = get_post_thumbnail_id( $post );

	// Guard against a missing/deleted attachment: don't hand back a dangling ID.
	if ( $photo_id && 'attachment' !== get_post_type( $photo_id ) ) {
		$photo_id = 0;
	}

	return array(
		'id'                => $post->ID,
		'name'              => $post->post_title,
		'bio'               => $post->post_content,
		'photo_id'          => $photo_id ? $photo_id : null,
		'qualification'     => get_post_meta( $post->ID, META_QUALIFICATION, true ),
		'career'            => get_post_meta( $post->ID, META_CAREER, true ),
		'education'         => get_post_meta( $post->ID, META_EDUCATION, true ),
		'affiliation'       => get_post_meta( $post->ID, META_AFFILIATION, true ),
		'registration_info' => get_post_meta( $post->ID, META_REGISTRATION_INFO, true ),
	);
}
