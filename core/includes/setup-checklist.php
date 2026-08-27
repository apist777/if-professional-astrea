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
			'key'      => 'navigation',
			'label'    => __( 'サイトのメニュー（Navigation）を作成する', 'astrea-core' ),
			'priority' => 'optional',
			'done'     => has_any_navigation(),
			'url'      => admin_url( 'admin.php?page=astrea-core#astrea-setup-generate-navigation' ),
		),
	);
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
 * Whether the site has at least one Navigation (wp_navigation post),
 * regardless of status — an existing draft Navigation still means "the
 * user already has one", which is what gates whether ASTREA should ever
 * offer to generate one (see includes/setup-navigation.php).
 *
 * @return bool
 */
function has_any_navigation(): bool {
	$navigations = get_posts(
		array(
			'post_type'      => 'wp_navigation',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	return ! empty( $navigations );
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
