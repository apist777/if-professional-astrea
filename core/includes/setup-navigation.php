<?php
/**
 * Setup — basic Navigation generation (Construction Order 007, extended
 * Construction Order 013).
 *
 * WordPress stores each Navigation menu as a `wp_navigation` post
 * containing Navigation Link block markup (Block Editor Handbook,
 * developer.wordpress.org, confirmed current as of 2026-08-27). ASTREA
 * never creates a Navigation automatically — only on explicit user action,
 * and only when the site has no Navigation at all yet, so an existing
 * hand-built menu (or one from a previous theme) is never touched or
 * duplicated (see docs/research/2026-08-27_construction_order_007_research.md §9).
 *
 * Construction Order 012's Integrated Release Quality Audit found that the
 * generated Navigation, while created successfully, never actually
 * appeared in the Header/Footer: the Theme's bare `<!-- wp:navigation /-->`
 * block (no `ref`) resolves via `WP_Navigation_Fallback`, which only ever
 * considers `post_status = publish` Navigations when picking the "most
 * recently published" one — and this generated Navigation was
 * deliberately saved as `draft`. Even publishing it after the fact wasn't
 * reliably sufficient, since WordPress's own auto-created Page List
 * fallback (created the moment any page with a bare Navigation block is
 * first viewed) can independently win that same "most recent" comparison.
 * Construction Order 013's research (docs/research/2026-08-28_construction_order_013_research.md)
 * traced this precisely and confirmed, by reading
 * wp-includes/blocks/navigation.php directly, that `render_block_core_navigation()`
 * requires `post_status === 'publish'` even when `ref` IS explicitly set —
 * so a reliable fix needs both: (a) `publish` status, and (b) an explicit
 * `ref` binding into the Header/Footer Navigation blocks, rather than
 * relying on WordPress's fallback-resolution heuristic at all.
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
 * Option tracking the wp_navigation post ID Setup itself generated (if
 * any), separate from GENERATED_PAGES_OPTION (which is specifically about
 * the `page` post type) — mirrors the same "so a re-run knows 'already
 * generated' apart from 'not generated yet' without guessing" rationale
 * documented in includes/setup-pages.php.
 */
const GENERATED_NAVIGATION_OPTION = 'astrea_core_generated_navigation';

/**
 * Option tracking the wp_template_part post IDs (keyed by 'header'/
 * 'footer') that Setup itself created in order to bind the generated
 * Navigation's `ref` into an otherwise-unedited Template Part. Only ever
 * points at Template Parts Setup created itself — never at a Template
 * Part the site owner customized (see connect_navigation_to_template_part()).
 */
const GENERATED_TEMPLATE_PARTS_OPTION = 'astrea_core_generated_template_parts';

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
 * Whether a previously generated Navigation ID still refers to a real,
 * non-trashed wp_navigation post — mirrors page_still_exists().
 *
 * @param int $post_id Navigation post ID.
 * @return bool
 */
function navigation_still_exists( int $post_id ): bool {
	$post = get_post( $post_id );

	return $post instanceof \WP_Post && 'wp_navigation' === $post->post_type && 'trash' !== $post->post_status;
}

/**
 * Creates a single `wp_navigation` post from navigation_links(), but only
 * when the site has no *other* meaningful Navigation yet
 * (has_meaningful_navigation() in includes/setup-checklist.php gates
 * this — Scenario D, an existing user-built Navigation, is always
 * respected and left untouched). Re-running once Setup has already
 * generated one is idempotent: it returns the existing tracked ID rather
 * than creating a duplicate.
 *
 * Saved as `publish` (not `draft`) — required for the Header/Footer's
 * explicit `ref` binding to actually render it on the front end at all
 * (see the file docblock: `render_block_core_navigation()` requires
 * `publish` status even when `ref` is set). This is safe: the action is
 * still gated entirely behind an explicit, capability- and Nonce-checked
 * button click (handle_generate_navigation()), and its content is built
 * only from already-published Core content (existing Services/
 * Professionals/FAQ archives and already-generated Pages) — the same
 * content-safety guarantee `navigation_links()` already provided before
 * this change.
 *
 * @return int|\WP_Error wp_navigation post ID (new or already-tracked), or WP_Error when a different, non-ASTREA Navigation already exists.
 */
function generate_navigation() {
	$tracked_id = (int) get_option( GENERATED_NAVIGATION_OPTION, 0 );
	if ( $tracked_id > 0 && navigation_still_exists( $tracked_id ) ) {
		return $tracked_id;
	}

	if ( has_meaningful_navigation() ) {
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

	$new_id = wp_insert_post(
		array(
			'post_type'    => 'wp_navigation',
			'post_status'  => 'publish',
			'post_title'   => __( 'ASTREA 基本メニュー', 'astrea-core' ),
			'post_content' => $content,
		),
		true
	);

	if ( ! is_wp_error( $new_id ) ) {
		update_option( GENERATED_NAVIGATION_OPTION, $new_id );
	}

	return $new_id;
}

/**
 * Recursively sets `ref` on every `core/navigation` block found within a
 * parsed block tree (a Header/Footer Template Part has exactly one, but
 * this does not assume that structurally).
 *
 * @param array $blocks        Parsed blocks (parse_blocks() shape).
 * @param int   $navigation_id The wp_navigation post ID to bind.
 * @return array Modified blocks.
 */
function set_navigation_ref_recursive( array $blocks, int $navigation_id ): array {
	foreach ( $blocks as &$block ) {
		if ( isset( $block['blockName'] ) && 'core/navigation' === $block['blockName'] ) {
			if ( empty( $block['attrs'] ) || ! is_array( $block['attrs'] ) ) {
				$block['attrs'] = array();
			}
			$block['attrs']['ref'] = $navigation_id;
		}
		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			$block['innerBlocks'] = set_navigation_ref_recursive( $block['innerBlocks'], $navigation_id );
		}
	}
	unset( $block );

	return $blocks;
}

/**
 * Returns $content with every `core/navigation` block's `ref` attribute
 * set to $navigation_id, using WordPress's own standard block parser/
 * serializer (parse_blocks()/serialize_blocks()) rather than string
 * manipulation — avoids any dependency on exact JSON attribute
 * formatting/ordering in the source content.
 *
 * @param string $content       Template Part block markup.
 * @param int    $navigation_id wp_navigation post ID.
 * @return string
 */
function inject_navigation_ref( string $content, int $navigation_id ): string {
	return serialize_blocks( set_navigation_ref_recursive( parse_blocks( $content ), $navigation_id ) );
}

/**
 * Attempts to bind the generated Navigation's `ref` into one Template
 * Part ('header' or 'footer'), but ONLY when doing so cannot possibly
 * discard a site owner's own customization:
 *
 * - If Setup itself already created a custom Template Part for this slug
 *   (tracked in GENERATED_TEMPLATE_PARTS_OPTION) and it still exists, it is
 *   safe to update again — it is ASTREA's own generated content, not the
 *   user's, so re-running this is a plain idempotent `ref` refresh.
 * - Otherwise, WordPress's own public `get_block_template()` API
 *   (`WP_Block_Template->source`) is consulted: `'theme'` means the
 *   Template Part is still exactly the Theme file's own content (never
 *   opened/saved in the Site Editor) — safe to turn into a new custom
 *   Template Part with `ref` injected, mirroring exactly the shape
 *   WordPress's own Site Editor "Save" action would create (see
 *   `WP_REST_Templates_Controller::prepare_item_for_database()`, the same
 *   post_type/post_name/tax_input/meta_input fields are used here).
 *   `'custom'` means the site owner (or some other process) has already
 *   saved their own version — it is never modified.
 *
 * @param string $slug          'header' or 'footer'.
 * @param int    $navigation_id wp_navigation post ID to bind.
 * @return string One of 'connected', 'skipped_custom', 'error'.
 */
function connect_navigation_to_template_part( string $slug, int $navigation_id ): string {
	$template = get_block_template( get_stylesheet() . '//' . $slug, 'wp_template_part' );

	if ( ! $template ) {
		return 'error'; // Not expected: the Theme always ships header.html/footer.html.
	}

	$tracked         = get_option( GENERATED_TEMPLATE_PARTS_OPTION, array() );
	$tracked         = is_array( $tracked ) ? $tracked : array();
	$tracked_id      = isset( $tracked[ $slug ] ) ? (int) $tracked[ $slug ] : 0;
	$tracked_post    = $tracked_id > 0 ? get_post( $tracked_id ) : null;
	$setup_owns_part = $tracked_post instanceof \WP_Post
		&& 'wp_template_part' === $tracked_post->post_type
		&& 'trash' !== $tracked_post->post_status;

	if ( $setup_owns_part ) {
		wp_update_post(
			array(
				'ID'           => $tracked_id,
				'post_content' => inject_navigation_ref( $tracked_post->post_content, $navigation_id ),
			)
		);

		return 'connected';
	}

	if ( 'custom' === $template->source ) {
		// The site owner has already customized this Template Part
		// themselves (or it was customized by something other than
		// Setup) — never overwrite it.
		return 'skipped_custom';
	}

	// $template->source === 'theme': untouched, safe to turn into a new
	// custom Template Part with the Navigation ref injected.
	$new_id = wp_insert_post(
		array(
			'post_type'    => 'wp_template_part',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $template->title,
			'post_content' => inject_navigation_ref( $template->content, $navigation_id ),
			'tax_input'    => array(
				'wp_theme'              => $template->theme,
				'wp_template_part_area' => $template->area,
			),
			'meta_input'   => array(
				'origin' => $template->source,
			),
		),
		true
	);

	if ( is_wp_error( $new_id ) ) {
		return 'error';
	}

	$tracked[ $slug ] = $new_id;
	update_option( GENERATED_TEMPLATE_PARTS_OPTION, $tracked );

	return 'connected';
}

/**
 * Attempts to bind the generated Navigation into both Header and Footer.
 *
 * @param int $navigation_id wp_navigation post ID.
 * @return array{header: string, footer: string} Each 'connected'|'skipped_custom'|'error'.
 */
function connect_navigation_to_header_footer( int $navigation_id ): array {
	return array(
		'header' => connect_navigation_to_template_part( 'header', $navigation_id ),
		'footer' => connect_navigation_to_template_part( 'footer', $navigation_id ),
	);
}

add_action( 'admin_post_' . GENERATE_NAVIGATION_ACTION, __NAMESPACE__ . '\\handle_generate_navigation' );

/**
 * Handles the "基本メニューを作成する" button submission: generates (or
 * reuses the already-generated) Navigation, then attempts to connect it
 * into the Header/Footer Template Parts, redirecting with a result state
 * the admin screen renders as a specific message per slot.
 *
 * @return void
 */
function handle_generate_navigation() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'この操作を行う権限がありません。', 'astrea-core' ) );
	}

	check_admin_referer( GENERATE_NAVIGATION_ACTION, GENERATE_NAVIGATION_NONCE );

	$navigation_id = generate_navigation();

	$redirect = admin_url( 'admin.php?page=astrea-core' );

	if ( is_wp_error( $navigation_id ) ) {
		$redirect = add_query_arg( 'astrea_setup_navigation_exists', '1', $redirect );
	} else {
		$connections = connect_navigation_to_header_footer( $navigation_id );

		$redirect = add_query_arg(
			array(
				'astrea_setup_navigation_generated' => '1',
				'astrea_setup_navigation_header'    => rawurlencode( $connections['header'] ),
				'astrea_setup_navigation_footer'    => rawurlencode( $connections['footer'] ),
			),
			$redirect
		);
	}

	wp_safe_redirect( $redirect . '#astrea-setup-generate-navigation' );
	exit;
}
