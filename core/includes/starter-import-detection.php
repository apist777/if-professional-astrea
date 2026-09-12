<?php
/**
 * ASTREA Starter Import — read-only site state detection
 * (Construction 029-B Phase 1 "Detection" responsibility).
 *
 * `gather_evidence()` is the ONLY place in Starter Import (as of
 * Construction 029-B) that touches the WordPress database, and it never
 * writes anything — no post, no option, no attachment, no menu item is
 * created, updated, or deleted by calling it, including calling it
 * repeatedly (Construction 029-B Phase 10, verified by the "detector
 * called 100 times" test in the accompanying test suite). Everything it
 * gathers is handed to the pure `Domain\classify()` function
 * (starter-import-domain.php), which is where all the actual decision
 * logic lives — this file's only job is turning "what's actually in the
 * database" into the `Evidence` shape that function expects.
 *
 * `detect_state()` is the one function anything outside this module
 * should call: gather + classify, composed.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

use function Astrea\Core\Setup\page_still_exists;
use const Astrea\Core\Setup\GENERATED_PAGES_OPTION;
use const Astrea\Core\Setup\GENERATED_NAVIGATION_OPTION;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Post statuses treated as "alive" for content-accounting purposes — trashed content never blocks a fresh import. */
const ALIVE_POST_STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );

/** The ASTREA custom post types Starter Import content can occupy (Construction 029-A Phase 6). */
const ASTREA_CPTS = array(
	'astrea_professional',
	'astrea_service',
	'astrea_case',
	'astrea_result',
	'astrea_price',
	'astrea_faq',
	'astrea_voice',
);

/**
 * Runs Detection + Domain classification in one call. This is the public
 * entry point Construction 029-C's Preflight (and everything after it)
 * should use rather than calling gather_evidence()/classify() separately.
 *
 * @return StateResult
 */
function detect_state(): StateResult {
	return classify( gather_evidence() );
}

/**
 * Gathers Evidence from the current site. Read-only: see this file's
 * docblock. Every query here uses an explicit post_status list — WordPress
 * `get_posts()`'s own default of 'publish' only would silently miss draft
 * Privacy Policy pages, private pages, etc., understating real content.
 *
 * @return Evidence
 */
function gather_evidence(): Evidence {
	return new Evidence(
		is_multisite(),
		detect_wp_default_post(),
		detect_wp_default_pages(),
		detect_astrea_generated_pages(),
		detect_astrea_generated_navigation(),
		count_astrea_cpts(),
		count_unaccounted_pages(),
		count_unaccounted_posts(),
		count_attachments(),
		read_import_status(),
		read_import_version(),
		import_generated_marker_present()
	);
}

/**
 * Whether a WordPress `post`, at a given post-status, matches an untouched
 * default: never edited since creation and never commented on. Title is
 * intentionally NOT part of this check on its own (Construction 029-B
 * Phase 4/13 — never rely on title text alone, since it can be translated
 * or the same title could legitimately be reused by a real post).
 *
 * @param \WP_Post $post The post to check.
 * @return bool
 */
function is_untouched( \WP_Post $post ): bool {
	return $post->post_modified_gmt === $post->post_date_gmt
		&& 0 === (int) $post->comment_count;
}

/**
 * Detects whether WordPress's own default "Hello world!" post is present.
 *
 * @return array{count:int, untouched:bool}
 */
function detect_wp_default_post(): array {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => ALIVE_POST_STATUSES,
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	$untouched_found = false;
	foreach ( $posts as $post ) {
		if ( ! $untouched_found && 'Hello world!' === $post->post_title && is_untouched( $post ) ) {
			$untouched_found = true;
		}
	}

	return array(
		'count'     => count( $posts ),
		'untouched' => $untouched_found,
	);
}

/**
 * Detects whether WordPress's own default pages (Sample Page, Privacy
 * Policy) are present, untouched.
 *
 * @return array<string, array{expected_id:int|null, exists:bool, title_matches:bool}>
 */
function detect_wp_default_pages(): array {
	$defaults = array(
		'sample_page'    => array(
			'title'    => 'Sample Page',
			'statuses' => array( 'publish' ),
		),
		'privacy_policy' => array(
			'title'    => 'Privacy Policy',
			'statuses' => array( 'draft', 'publish' ),
		),
	);

	$result = array();
	foreach ( $defaults as $key => $definition ) {
		$matches = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => $definition['statuses'],
				'title'          => $definition['title'],
				'posts_per_page' => 1,
			)
		);

		$found          = $matches ? $matches[0] : null;
		$result[ $key ] = array(
			'expected_id'   => $found ? (int) $found->ID : null,
			'exists'        => (bool) $found,
			'title_matches' => (bool) $found && is_untouched( $found ),
		);
	}

	return $result;
}

/**
 * Cross-checks Core's own `Setup\GENERATED_PAGES_OPTION` bookkeeping
 * against reality — this is the existing ownership pattern Construction
 * 028/029-A found already established in setup-pages.php/setup-home.php,
 * reused here rather than inventing a parallel one.
 *
 * @return array<string, array{recorded_id:int|null, exists:bool}>
 */
function detect_astrea_generated_pages(): array {
	$recorded = get_option( GENERATED_PAGES_OPTION, array() );
	if ( ! is_array( $recorded ) ) {
		$recorded = array();
	}

	$result = array();
	foreach ( array( 'about', 'price', 'contact', 'home' ) as $key ) {
		$id             = isset( $recorded[ $key ] ) ? (int) $recorded[ $key ] : null;
		$result[ $key ] = array(
			'recorded_id' => $id,
			'exists'      => null !== $id && $id > 0 && page_still_exists( $id ),
		);
	}

	return $result;
}

/**
 * Cross-checks Core's own `Setup\GENERATED_NAVIGATION_OPTION` bookkeeping
 * against reality.
 *
 * @return bool
 */
function detect_astrea_generated_navigation(): bool {
	$id = (int) get_option( GENERATED_NAVIGATION_OPTION, 0 );
	if ( $id <= 0 ) {
		return false;
	}
	$post = get_post( $id );
	return $post instanceof \WP_Post && 'wp_navigation' === $post->post_type && 'trash' !== $post->post_status;
}

/**
 * Counts posts (any alive status) per ASTREA custom post type.
 *
 * @return array<string, int>
 */
function count_astrea_cpts(): array {
	$counts = array();
	foreach ( ASTREA_CPTS as $post_type ) {
		$status_counts = wp_count_posts( $post_type );
		$total         = 0;
		foreach ( $status_counts as $status => $count ) {
			if ( in_array( $status, ALIVE_POST_STATUSES, true ) ) {
				$total += (int) $count;
			}
		}
		$counts[ $post_type ] = $total;
	}
	return $counts;
}

/**
 * Pages that are neither a recognized, untouched WordPress default nor a
 * page Core itself recorded as generated.
 *
 * @return int
 */
function count_unaccounted_pages(): int {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => ALIVE_POST_STATUSES,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	if ( ! $pages ) {
		return 0;
	}

	$accounted_ids = array();

	foreach ( detect_wp_default_pages() as $entry ) {
		if ( $entry['exists'] && $entry['title_matches'] ) {
			$accounted_ids[] = $entry['expected_id'];
		}
	}

	foreach ( detect_astrea_generated_pages() as $entry ) {
		if ( $entry['exists'] ) {
			$accounted_ids[] = $entry['recorded_id'];
		}
	}

	$unaccounted = array_diff( $pages, $accounted_ids );

	return count( $unaccounted );
}

/**
 * Posts (post_type=post) beyond a single untouched default "Hello world!"
 * post.
 *
 * @return int
 */
function count_unaccounted_posts(): int {
	$default       = detect_wp_default_post();
	$accounted_for = $default['untouched'] ? 1 : 0;

	return max( 0, $default['count'] - $accounted_for );
}

/**
 * Counts Media Library attachments of any (non-trashed) status.
 *
 * @return int
 */
function count_attachments(): int {
	$counts = wp_count_posts( 'attachment' );
	$total  = 0;
	foreach ( $counts as $status => $count ) {
		if ( 'trash' !== $status ) {
			$total += (int) $count;
		}
	}
	return $total;
}

/**
 * Reads the Starter Import status marker, decoded to its enum case.
 *
 * @return ImportStatus|null Null if the option is unset or holds an unrecognized value.
 */
function read_import_status(): ?ImportStatus {
	$raw = get_option( IMPORT_STATUS_OPTION, '' );
	if ( ! is_string( $raw ) || '' === $raw ) {
		return null;
	}
	return ImportStatus::tryFrom( $raw );
}

/**
 * Reads the Starter Package version marker.
 *
 * @return string|null Null if the option is unset or empty.
 */
function read_import_version(): ?string {
	$raw = get_option( IMPORT_VERSION_OPTION, '' );
	return ( is_string( $raw ) && '' !== $raw ) ? $raw : null;
}

/**
 * Whether the generated-object ownership map option is present and non-empty.
 *
 * @return bool
 */
function import_generated_marker_present(): bool {
	$raw = get_option( IMPORT_GENERATED_OPTION, array() );
	return is_array( $raw ) && count( $raw ) > 0;
}
