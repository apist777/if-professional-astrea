<?php
/**
 * Setup — basic page generation (Construction Order 007, Decision 016).
 *
 * Generates only the pages that have no reachable URL of their own:
 * Office Profile / 事務所概要, Price (astrea_price is deliberately
 * non-viewable — Construction Order 004 / Decision 026), and Contact (the
 * Contact Form block has no dedicated URL). Service, Professional and FAQ
 * already have their own CPT archive URLs (`/services/`, `/professionals/`,
 * `/faq/`, Construction Order 003/004) established after Decision 016 was
 * originally written, so generating separate pages for them would just
 * duplicate an existing URL — see
 * docs/research/2026-08-27_construction_order_007_research.md §8/§20-1.
 *
 * Only ever runs on explicit user action (a button click, admin-post with
 * Nonce). Never on Theme/Core activation. Re-running never duplicates or
 * overwrites an existing page — Decision 016's "重複生成・既存ページの無断
 * 上書きをしない" requirement.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Setup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Option storing which Page ID was generated for each definition key, so a
 * re-run can tell "already generated" apart from "not generated yet"
 * without guessing from title/slug. Core-owned; the generated Pages
 * themselves are ordinary WordPress user content and are NOT deleted by
 * Core Uninstall (Decision 016/019) — only this index option is.
 */
const GENERATED_PAGES_OPTION = 'astrea_core_generated_pages';

const GENERATE_PAGES_ACTION = 'astrea_setup_generate_pages';
const GENERATE_PAGES_NONCE  = 'astrea_setup_generate_pages_nonce';

/**
 * Definitions for the pages Setup can generate. Content is intentionally
 * minimal: either an existing Core Pattern (料金/お問い合わせ — no fictional
 * content, just the real Dynamic Block already used elsewhere) or a plain
 * placeholder paragraph the site owner is expected to rewrite (事務所概要 —
 * never fabricated business content, see 07 research §10).
 *
 * Construction Order 011 appended the same real Office Profile display
 * blocks used by the `astrea/office-info` Pattern (office_name/address/
 * phone Bindings + astrea/office-hours + astrea/office-sns) below the
 * placeholder paragraph — this is genuine data (or a self-hiding empty
 * state), never fabricated content, so it is safe to publish immediately.
 * Per the established Construction Order 009 HOME precedent,
 * generate_pages() below only ever inserts this for a page it is creating
 * for the first time — an already-generated (or user-edited) 事務所概要
 * page is never rewritten, regardless of this function's return value
 * changing over time.
 *
 * @return array<string, array{title: string, content: string}>
 */
function page_definitions(): array {
	return array(
		'about'   => array(
			'title'   => __( '事務所概要', 'astrea-core' ),
			'content' => "<!-- wp:paragraph -->\n<p>" .
				esc_html__( 'ここに事務所の紹介文を入力してください。', 'astrea-core' ) .
				"</p>\n<!-- /wp:paragraph -->\n\n" .
				'<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"office_name"}}}}} -->' . "\n<p></p>\n<!-- /wp:paragraph -->\n\n" .
				'<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"address"}}}}} -->' . "\n<p></p>\n<!-- /wp:paragraph -->\n\n" .
				'<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"phone"}}}}} -->' . "\n<p></p>\n<!-- /wp:paragraph -->\n\n" .
				'<!-- wp:astrea/office-hours {"heading":"' . esc_html__( '営業時間', 'astrea-core' ) . '"} /-->' . "\n\n" .
				'<!-- wp:astrea/office-sns {"heading":"' . esc_html__( 'SNS', 'astrea-core' ) . '"} /-->',
		),
		'price'   => array(
			'title'   => __( '料金', 'astrea-core' ),
			'content' => '<!-- wp:astrea/price-list {"emptyMessage":"現在、料金情報は準備中です。お問い合わせください。"} /-->',
		),
		'contact' => array(
			'title'   => __( 'お問い合わせ', 'astrea-core' ),
			'content' => '<!-- wp:astrea/contact-form /-->',
		),
	);
}

/**
 * Generates any of the defined pages that don't already exist as a live
 * (non-trashed) Page. Existing pages are left completely untouched — this
 * function never edits or replaces content once a page has been recorded.
 *
 * @return array<string, int> Map of definition key => Page ID (existing or newly created).
 */
function generate_pages(): array {
	$recorded = get_option( GENERATED_PAGES_OPTION, array() );
	if ( ! is_array( $recorded ) ) {
		$recorded = array();
	}

	foreach ( page_definitions() as $key => $definition ) {
		$existing_id = isset( $recorded[ $key ] ) ? (int) $recorded[ $key ] : 0;

		if ( $existing_id > 0 && page_still_exists( $existing_id ) ) {
			continue; // Already generated and not deleted since — do not touch it.
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => $definition['title'],
				'post_content' => $definition['content'],
			),
			true
		);

		if ( ! is_wp_error( $new_id ) ) {
			$recorded[ $key ] = $new_id;
		}
	}

	update_option( GENERATED_PAGES_OPTION, $recorded );

	return $recorded;
}

/**
 * Whether a previously generated Page ID still refers to a real, non-trashed page.
 *
 * @param int $post_id Page ID.
 * @return bool
 */
function page_still_exists( int $post_id ): bool {
	$post = get_post( $post_id );

	return $post instanceof \WP_Post && 'page' === $post->post_type && 'trash' !== $post->post_status;
}

/**
 * A published Contact page's URL, if one exists (Construction Order 015D
 * — read-only helper for Visual v2's Closing CTA; adds no new option, no
 * new persistence).
 *
 * Prefers the Setup-generated page tracked in `GENERATED_PAGES_OPTION`
 * (cheap, no query). But a real site's Contact page is not always that
 * one — the Owner Fixture itself proved this: its "お問い合わせ" page is a
 * genuine, published Page containing the Contact Form block, yet
 * `GENERATED_PAGES_OPTION` was never populated for it (created outside
 * the Setup flow), so relying on that tracking alone silently produced
 * "no Contact page" even though visitors can plainly reach one. Falls
 * back to the same detection `setup-checklist.php`'s
 * `is_contact_reachable()` already uses — scanning published Pages for
 * the Contact Form block — reusing existing detection rather than
 * inventing a second, narrower one (Order §27).
 *
 * Returns null whenever there is nothing safe to link to — callers must
 * treat null as "don't show a Contact action", never fall back to a
 * guessed or hardcoded URL.
 *
 * @return string|null
 */
function get_contact_page_url(): ?string {
	$recorded   = get_option( GENERATED_PAGES_OPTION, array() );
	$contact_id = isset( $recorded['contact'] ) ? (int) $recorded['contact'] : 0;

	if ( $contact_id > 0 && page_still_exists( $contact_id ) && 'publish' === get_post_status( $contact_id ) ) {
		$permalink = get_permalink( $contact_id );
		if ( $permalink ) {
			return $permalink;
		}
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

	foreach ( $pages as $page_id ) {
		if ( has_block( CONTACT_FORM_BLOCK_NAME, $page_id ) ) {
			$permalink = get_permalink( $page_id );
			return $permalink ? $permalink : null;
		}
	}

	return null;
}

add_action( 'admin_post_' . GENERATE_PAGES_ACTION, __NAMESPACE__ . '\\handle_generate_pages' );

/**
 * Handles the "基本ページを作成する" button submission.
 *
 * @return void
 */
function handle_generate_pages() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'この操作を行う権限がありません。', 'astrea-core' ) );
	}

	check_admin_referer( GENERATE_PAGES_ACTION, GENERATE_PAGES_NONCE );

	generate_pages();

	wp_safe_redirect(
		add_query_arg( 'astrea_setup_pages_generated', '1', admin_url( 'admin.php?page=astrea-core' ) ) . '#astrea-setup-generate-pages'
	);
	exit;
}
