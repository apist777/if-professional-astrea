<?php
/**
 * Setup checklist — Core-owned data layer (Construction Order 007).
 *
 * Deliberately holds no state of its own: every item is derived from the
 * same public read APIs Theme/PRO already use (Office Profile, Service,
 * Price, FAQ, Contact settings, SEO settings, wp_navigation posts). This
 * keeps the checklist always in sync with reality and avoids introducing a
 * second, separately-maintained "setup progress" data source that could
 * drift from the actual site content (see
 * docs/research/2026-08-27_construction_order_007_research.md §6).
 *
 * Per 02仕様書 §22 ("設定状況は点数化せず、完了/推奨/任意程度のチェックリスト
 * で案内する") and Decision 016 ("全項目完了を公開条件にしない"), this module
 * never blocks anything — it only reports done/not-done per item.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Setup;

use function Astrea\Core\OfficeProfile\get_office_profile;
use function Astrea\Core\ProfessionalProfile\get_profiles;
use function Astrea\Core\Service\get_services;
use function Astrea\Core\Price\get_prices;
use function Astrea\Core\Faq\get_faqs;
use function Astrea\Core\Inquiry\get_contact_settings;
use function Astrea\Core\Seo\get_seo_settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Block name embedded in a page for the contact form to be reachable. */
const CONTACT_FORM_BLOCK_NAME = 'astrea/contact-form';

/** Transient used to avoid re-scanning all published pages on every admin request. */
const CONTACT_REACHABLE_TRANSIENT = 'astrea_core_setup_contact_reachable';

/**
 * Returns the ordered list of setup checklist items.
 *
 * Each item: array{
 *   key: string,
 *   label: string,
 *   priority: 'recommended'|'optional',
 *   done: bool,
 *   url: string,
 * }
 *
 * @return array[]
 */
function get_checklist_items(): array {
	$office_name = trim( (string) get_office_profile()['office_name'] );

	return array(
		array(
			'key'      => 'home',
			'label'    => __( 'ホームページを公開する', 'astrea-core' ),
			'priority' => 'recommended',
			'done'     => is_home_configured(),
			'url'      => admin_url( 'admin.php?page=astrea-core#astrea-setup-generate-home' ),
		),
		array(
			'key'      => 'office_profile',
			'label'    => __( '事務所情報（事務所名）を入力する', 'astrea-core' ),
			'priority' => 'recommended',
			'done'     => '' !== $office_name,
			'url'      => admin_url( 'admin.php?page=astrea-core' ),
		),
		array(
			'key'      => 'service',
			'label'    => __( '取扱業務を1件以上登録する', 'astrea-core' ),
			'priority' => 'recommended',
			'done'     => count( get_services() ) > 0,
			'url'      => admin_url( 'post-new.php?post_type=astrea_service' ),
		),
		array(
			'key'      => 'contact_reachable',
			'label'    => __( '問い合わせフォームを設置したページを公開する', 'astrea-core' ),
			'priority' => 'recommended',
			'done'     => is_contact_reachable(),
			'url'      => admin_url( 'admin.php?page=astrea-core#astrea-setup-generate-pages' ),
		),
		array(
			'key'      => 'notification_confirmed',
			'label'    => __( '問い合わせの通知先メールアドレスを確認する', 'astrea-core' ),
			'priority' => 'recommended',
			'done'     => '' !== get_contact_settings()['notification_email'],
			'url'      => admin_url( 'admin.php?page=astrea-core-contact' ),
		),
		array(
			'key'      => 'professional',
			'label'    => __( '専門家プロフィールを登録する', 'astrea-core' ),
			'priority' => 'optional',
			'done'     => count( get_profiles() ) > 0,
			'url'      => admin_url( 'post-new.php?post_type=astrea_professional' ),
		),
		array(
			'key'      => 'price',
			'label'    => __( '料金を登録する', 'astrea-core' ),
			'priority' => 'optional',
			'done'     => count( get_prices() ) > 0,
			'url'      => admin_url( 'post-new.php?post_type=astrea_price' ),
		),
		array(
			'key'      => 'faq',
			'label'    => __( 'FAQを登録する', 'astrea-core' ),
			'priority' => 'optional',
			'done'     => count( get_faqs() ) > 0,
			'url'      => admin_url( 'post-new.php?post_type=astrea_faq' ),
		),
		array(
			'key'      => 'seo_og_image',
			'label'    => __( 'SEO：SNSシェア用の画像を設定する', 'astrea-core' ),
			'priority' => 'optional',
			'done'     => get_seo_settings()['og_image_id'] > 0,
			'url'      => admin_url( 'admin.php?page=astrea-core-seo' ),
		),
		array(
			'key'      => 'ga4',
			'label'    => __( 'GA4測定IDを設定する', 'astrea-core' ),
			'priority' => 'optional',
			'done'     => '' !== get_seo_settings()['ga4_measurement_id'],
			'url'      => admin_url( 'admin.php?page=astrea-core-seo' ),
		),
		array(
			'key'      => 'navigation',
			'label'    => __( 'サイトのメニュー（Navigation）を作成する', 'astrea-core' ),
			'priority' => 'optional',
			'done'     => has_meaningful_navigation(),
			'url'      => admin_url( 'admin.php?page=astrea-core#astrea-setup-generate-navigation' ),
		),
		array(
			'key'      => 'site_title',
			'label'    => __( 'サイトのタイトルを設定する', 'astrea-core' ),
			'priority' => 'optional',
			'done'     => is_site_title_configured(),
			'url'      => admin_url( 'options-general.php' ),
		),
	);
}

/**
 * Whether WordPress's own Site Title (`blogname`, Settings > General) is
 * set to anything at all. Construction Order 013: Office Profile's
 * `office_name` and WordPress's Site Title are deliberately independent —
 * ASTREA never reads or auto-copies `office_name` into `blogname` (Site
 * Title is a standard WordPress setting the site owner, not Core, owns) —
 * this only checks the clear, safe, already-existing WordPress state ("is
 * it blank"), never attempting to guess whether a non-blank value is
 * still some generic/default placeholder.
 *
 * @return bool
 */
function is_site_title_configured(): bool {
	return '' !== trim( (string) get_bloginfo( 'name' ) );
}

/**
 * Whether the site currently shows a real, published static Page as its
 * front page — regardless of whether ASTREA's Setup generated it or the
 * site owner built their own (Construction Order 009 §3: judged from real
 * WordPress state, not a tracked "we generated one" flag, so a site owner
 * who assembled their own HOME manually is correctly credited too).
 *
 * @return bool
 */
function is_home_configured(): bool {
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		return false;
	}

	$front_page_id = (int) get_option( 'page_on_front' );
	if ( $front_page_id <= 0 ) {
		return false;
	}

	$front_page = get_post( $front_page_id );

	return $front_page instanceof \WP_Post && 'page' === $front_page->post_type && 'publish' === $front_page->post_status;
}

/**
 * Whether at least one published Page contains the Contact Form block.
 *
 * Cached briefly (an hour) so sites with many pages don't re-scan every
 * published page on every admin request; the checklist is a soft guidance
 * UI, not a real-time indicator, so a short staleness window is acceptable.
 *
 * @return bool
 */
function is_contact_reachable(): bool {
	$cached = get_transient( CONTACT_REACHABLE_TRANSIENT );
	if ( false !== $cached ) {
		return (bool) $cached;
	}

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$reachable = false;
	foreach ( $pages as $page_id ) {
		if ( has_block( CONTACT_FORM_BLOCK_NAME, $page_id ) ) {
			$reachable = true;
			break;
		}
	}

	set_transient( CONTACT_REACHABLE_TRANSIENT, $reachable, HOUR_IN_SECONDS );

	return $reachable;
}

/**
 * The exact post_name/post_content WordPress core itself uses when it
 * auto-creates a fallback Navigation the first time a bare `core/navigation`
 * block (no `ref`) is rendered on the front end and no Navigation exists yet
 * (`WP_Navigation_Fallback::create_default_fallback()`, core since WP 6.3 —
 * verified against the wp-includes source: post_name 'navigation',
 * post_content '<!-- wp:page-list /-->'). Construction Order 008 gave the
 * Header/Footer a bare Navigation block, so this fallback is created
 * automatically on the very first page view — Construction Order 009
 * confirmed via real-machine testing that a single homepage request is
 * enough to turn has_meaningful_navigation()'s old zero-item state into a false
 * "done" before the site owner has done anything at all.
 */
const WP_FALLBACK_NAVIGATION_SLUG    = 'navigation';
const WP_FALLBACK_NAVIGATION_CONTENT = '<!-- wp:page-list /-->';

/**
 * Whether a wp_navigation post is WordPress's own untouched auto-generated
 * fallback (a plain Page List), as opposed to something the site owner or
 * ASTREA's Setup deliberately built. Editing the fallback at all (even
 * adding one link) changes its content and correctly stops it matching.
 *
 * @param \WP_Post $navigation A wp_navigation post.
 * @return bool
 */
function is_wordpress_fallback_navigation( \WP_Post $navigation ): bool {
	return WP_FALLBACK_NAVIGATION_SLUG === $navigation->post_name
		&& WP_FALLBACK_NAVIGATION_CONTENT === trim( $navigation->post_content );
}

/**
 * Whether the site has at least one *meaningful* Navigation — i.e. one
 * that isn't just WordPress's own auto-created Page List fallback (see
 * is_wordpress_fallback_navigation()). This is what gates both the setup
 * checklist's "done" state and whether ASTREA should ever offer to
 * generate one (see includes/setup-navigation.php) — a bare fallback that
 * nobody deliberately built must not read as "Setup complete".
 *
 * @return bool
 */
function has_meaningful_navigation(): bool {
	$navigations = get_posts(
		array(
			'post_type'      => 'wp_navigation',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ( $navigations as $navigation ) {
		if ( ! is_wordpress_fallback_navigation( $navigation ) ) {
			return true;
		}
	}

	return false;
}

add_action( 'save_post_page', __NAMESPACE__ . '\\clear_contact_reachable_cache' );
add_action( 'trashed_post', __NAMESPACE__ . '\\clear_contact_reachable_cache' );

/**
 * Clears the reachability cache whenever a Page is saved or trashed, so the
 * checklist reflects a just-published (or just-unpublished) Contact page
 * without waiting out the full cache TTL.
 *
 * @return void
 */
function clear_contact_reachable_cache() {
	delete_transient( CONTACT_REACHABLE_TRANSIENT );
}
