<?php
/**
 * Setup — HOME assembly (Construction Order 009).
 *
 * Bridges the gap the Remaining Work Audit (2026-08-27) found between
 * Construction Order 007 (Setup) and 008 (Design System): a fresh site's
 * front page falls back to the empty blog index (`home.html`) because
 * nothing ever assembles Construction 008's HOME Patterns into an actual
 * Page and assigns it as the site's static front page. This file adds one
 * explicit, idempotent Setup action that does exactly that in a single
 * click — never automatically on Theme/Core activation (Decision 016).
 *
 * Architecture: this reads each Pattern's CURRENT content straight from
 * WordPress's own `WP_Block_Patterns_Registry` by slug at generation time,
 * rather than duplicating any Pattern markup here — there is exactly one
 * copy of each Pattern's content (the Theme's own `theme/patterns/*.php`
 * file), avoiding the future dual-management risk the order explicitly
 * warned against. Once copied into the new Page, the content becomes
 * ordinary, fully editable post content — identical to what happens when a
 * human manually inserts the same Patterns via the block inserter
 * (Decision 002: a Pattern is never the record of truth for its own
 * content once inserted).
 *
 * The Trust Pattern (`astrea/home-trust`) is deliberately excluded from the
 * default assembled set: its copy ("ここに信頼要素の説明を入力してください")
 * is an explicit fill-in-the-blank instruction, appropriate when a human
 * inserts it manually and edits before publishing, but not something this
 * action may publish unedited (matches the established policy against
 * ever publishing placeholder/fictional content — see
 * includes/setup-pages.php's 事務所概要 page). The remaining Patterns are
 * either fully data-driven with a documented empty-state (self-hiding
 * Dynamic Blocks) or generic-but-genuine copy (Flow, CTA), and are safe to
 * publish immediately.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Setup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * The ASTREA Theme's standard HOME Pattern set, in display order. These
 * slugs are a public contract between Theme and Core (the same role
 * Decision 012's `astrea/*` Block namespace already plays for Blocks),
 * documented here and in the Construction 008 research doc.
 *
 * Construction Order 010 added the CASE/RESULTS/VOICE Teasers. This only
 * affects NEW HOME generation (sites with no tracked 'home' entry yet) —
 * generate_home_page()'s existing idempotency guard means an
 * already-generated HOME (tracked or user-edited) is never touched by this
 * list changing, exactly as it was never touched when Construction 009's
 * own Patterns were first added here.
 */
const HOME_PATTERN_SLUGS = array(
	'astrea/home-hero',
	'astrea/home-services-teaser',
	'astrea/home-case-teaser',
	'astrea/home-results-teaser',
	'astrea/home-professional-teaser',
	'astrea/home-price',
	'astrea/home-faq',
	'astrea/home-voice-teaser',
	'astrea/home-flow',
	'astrea/home-cta',
);

const GENERATE_HOME_ACTION = 'astrea_setup_generate_home';
const GENERATE_HOME_NONCE  = 'astrea_setup_generate_home_nonce';

/**
 * Assembles the ASTREA Theme's HOME Patterns into a new, published Page
 * and sets it as the site's static front page — in one explicit action.
 *
 * Safe by construction against Construction Order 009's five protection
 * scenarios:
 *
 * A. New site, no front page configured -> generates and assigns normally.
 * B. ASTREA already generated one (tracked ID still a live page) -> no-op,
 *    returns a WP_Error rather than duplicating.
 * C. A static front page already exists that ASTREA didn't generate -> the
 *    existing assignment is never touched; returns a WP_Error.
 * D. Blog-as-home in use (`show_on_front` is not `'page'`) -> only ever
 *    changed by this explicit action being invoked at all; nothing changes
 *    it silently in the background.
 * E. A previously generated HOME page has since been edited by the user ->
 *    the same tracked-ID idempotency as B leaves it untouched.
 *
 * @return int|\WP_Error New Page ID, or WP_Error when generation was refused or unavailable.
 */
function generate_home_page() {
	$recorded = get_option( GENERATED_PAGES_OPTION, array() );
	if ( ! is_array( $recorded ) ) {
		$recorded = array();
	}

	$existing_id = isset( $recorded['home'] ) ? (int) $recorded['home'] : 0;
	if ( $existing_id > 0 && page_still_exists( $existing_id ) ) {
		return new \WP_Error( 'astrea_home_exists', __( 'ホームページは既に作成済みです。', 'astrea-core' ) );
	}

	$current_front_page_id = (int) get_option( 'page_on_front' );
	if ( 'page' === get_option( 'show_on_front' ) && $current_front_page_id > 0 && page_still_exists( $current_front_page_id ) ) {
		return new \WP_Error( 'astrea_home_front_page_exists', __( '既にホームページ（固定フロントページ）が設定されています。', 'astrea-core' ) );
	}

	$content = assemble_home_content();
	if ( '' === $content ) {
		return new \WP_Error( 'astrea_home_patterns_unavailable', __( 'HOME用のPatternが見つからないため、ホームページを作成できませんでした。ASTREA Themeが有効化されていることを確認してください。', 'astrea-core' ) );
	}

	$new_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'ホーム', 'astrea-core' ),
			'post_content' => $content,
		),
		true
	);

	if ( is_wp_error( $new_id ) ) {
		return $new_id;
	}

	update_option( 'page_on_front', $new_id );
	update_option( 'show_on_front', 'page' );

	$recorded['home'] = $new_id;
	update_option( GENERATED_PAGES_OPTION, $recorded );

	return $new_id;
}

/**
 * Concatenates the current registered content of each HOME_PATTERN_SLUGS
 * entry that is actually registered (gracefully skipping any that aren't —
 * e.g. a different Theme is active — rather than failing outright).
 *
 * @return string Combined block markup, or '' if none of the Patterns are registered.
 */
function assemble_home_content(): string {
	if ( ! class_exists( '\WP_Block_Patterns_Registry' ) ) {
		return '';
	}

	$registry = \WP_Block_Patterns_Registry::get_instance();
	$content  = '';

	foreach ( HOME_PATTERN_SLUGS as $slug ) {
		if ( ! $registry->is_registered( $slug ) ) {
			continue;
		}

		$pattern  = $registry->get_registered( $slug );
		$content .= $pattern['content'] . "\n\n";
	}

	return trim( $content );
}

add_action( 'admin_post_' . GENERATE_HOME_ACTION, __NAMESPACE__ . '\\handle_generate_home_page' );

/**
 * Handles the "ホームページを作成する" button submission.
 *
 * @return void
 */
function handle_generate_home_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'この操作を行う権限がありません。', 'astrea-core' ) );
	}

	check_admin_referer( GENERATE_HOME_ACTION, GENERATE_HOME_NONCE );

	$result = generate_home_page();

	$redirect = admin_url( 'admin.php?page=astrea-core' );
	if ( is_wp_error( $result ) ) {
		$redirect = add_query_arg( 'astrea_setup_home_error', rawurlencode( $result->get_error_message() ), $redirect );
	} else {
		$redirect = add_query_arg( 'astrea_setup_home_generated', '1', $redirect );
	}

	wp_safe_redirect( $redirect . '#astrea-setup-generate-home' );
	exit;
}
