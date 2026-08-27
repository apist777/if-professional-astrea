<?php
/**
 * Setup — basic Navigation generation (Construction Order 007).
 *
 * WordPress stores each Navigation menu as a `wp_navigation` post
 * containing Navigation Link block markup (Block Editor Handbook,
 * developer.wordpress.org, confirmed current as of 2026-08-27). ASTREA
 * never creates a Navigation automatically — only on explicit user action,
 * and only when the site has no Navigation at all yet, so an existing
 * hand-built menu (or one from a previous theme) is never touched or
 * duplicated (see docs/research/2026-08-27_construction_order_007_research.md §9).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Setup;

use function Astrea\Core\Service\get_services;
use function Astrea\Core\ProfessionalProfile\get_profiles;
use function Astrea\Core\Faq\get_faqs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const GENERATE_NAVIGATION_ACTION = 'astrea_setup_generate_navigation';
const GENERATE_NAVIGATION_NONCE  = 'astrea_setup_generate_navigation_nonce';

/**
 * Builds the link list for the generated Navigation: existing CPT
 * archives when they have at least one published item, plus whichever
 * generated pages (see includes/setup-pages.php) already exist. Skips any
 * destination that doesn't actually exist yet rather than linking to an
 * empty archive or a not-yet-generated page.
 *
 * @return array<int, array{label: string, url: string}>
 */
function navigation_links(): array {
	$links           = array();
	$generated_pages = get_option( GENERATED_PAGES_OPTION, array() );

	$about_id = isset( $generated_pages['about'] ) ? (int) $generated_pages['about'] : 0;
	if ( $about_id > 0 && page_still_exists( $about_id ) ) {
		$links[] = array(
			'label' => __( '事務所概要', 'astrea-core' ),
			'url'   => (string) get_permalink( $about_id ),
		);
	}

	if ( count( get_services() ) > 0 ) {
		$archive = get_post_type_archive_link( 'astrea_service' );
		if ( $archive ) {
			$links[] = array(
				'label' => __( '取扱業務', 'astrea-core' ),
				'url'   => $archive,
			);
		}
	}

	if ( count( get_profiles() ) > 0 ) {
		$archive = get_post_type_archive_link( 'astrea_professional' );
		if ( $archive ) {
			$links[] = array(
				'label' => __( '専門家紹介', 'astrea-core' ),
				'url'   => $archive,
			);
		}
	}

	$price_id = isset( $generated_pages['price'] ) ? (int) $generated_pages['price'] : 0;
	if ( $price_id > 0 && page_still_exists( $price_id ) ) {
		$links[] = array(
			'label' => __( '料金', 'astrea-core' ),
			'url'   => (string) get_permalink( $price_id ),
		);
	}

	if ( count( get_faqs() ) > 0 ) {
		$archive = get_post_type_archive_link( 'astrea_faq' );
		if ( $archive ) {
			$links[] = array(
				'label' => __( 'FAQ', 'astrea-core' ),
				'url'   => $archive,
			);
		}
	}

	$contact_id = isset( $generated_pages['contact'] ) ? (int) $generated_pages['contact'] : 0;
	if ( $contact_id > 0 && page_still_exists( $contact_id ) ) {
		$links[] = array(
			'label' => __( 'お問い合わせ', 'astrea-core' ),
			'url'   => (string) get_permalink( $contact_id ),
		);
	}

	return $links;
}

/**
 * Creates a single `wp_navigation` post from navigation_links(), but only
 * when the site has no Navigation at all yet (has_any_navigation() in
 * includes/setup-checklist.php gates this). Saved as draft, like the
 * generated Pages, so the site owner reviews it before it can appear
 * anywhere (a Navigation only renders once referenced from a Template/
 * Template Part in the Site Editor).
 *
 * @return int|\WP_Error New wp_navigation post ID, or WP_Error on failure.
 */
function generate_navigation() {
	if ( has_any_navigation() ) {
		return new \WP_Error( 'astrea_navigation_exists', __( '既にNavigationが存在するため生成しません。', 'astrea-core' ) );
	}

	$content = '';
	foreach ( navigation_links() as $link ) {
		$content .= sprintf(
			'<!-- wp:navigation-link %s /-->' . "\n",
			wp_json_encode(
				array(
					'label' => $link['label'],
					'url'   => $link['url'],
					'kind'  => 'custom',
				),
				JSON_UNESCAPED_UNICODE
			)
		);
	}

	return wp_insert_post(
		array(
			'post_type'    => 'wp_navigation',
			'post_status'  => 'draft',
			'post_title'   => __( 'ASTREA 基本メニュー', 'astrea-core' ),
			'post_content' => $content,
		),
		true
	);
}

add_action( 'admin_post_' . GENERATE_NAVIGATION_ACTION, __NAMESPACE__ . '\\handle_generate_navigation' );

/**
 * Handles the "基本メニューを作成する" button submission.
 *
 * @return void
 */
function handle_generate_navigation() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'この操作を行う権限がありません。', 'astrea-core' ) );
	}

	check_admin_referer( GENERATE_NAVIGATION_ACTION, GENERATE_NAVIGATION_NONCE );

	generate_navigation();

	wp_safe_redirect(
		add_query_arg( 'astrea_setup_navigation_generated', '1', admin_url( 'admin.php?page=astrea-core' ) ) . '#astrea-setup-generate-navigation'
	);
	exit;
}
