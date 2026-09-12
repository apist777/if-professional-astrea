<?php
/**
 * Construction 028 — Starter Pipeline Repeatability Test: snapshot script.
 *
 * Captures a semantic, machine-readable snapshot of the Starter Site's
 * current state (content counts, block structure fingerprints, navigation,
 * identity, portability) to /snapshot/out.json (host path configurable via
 * the --mount flag pointed at this VFS path). Contains no secrets.
 *
 * Usage: run this after each pipeline execution (RUN #1, #2, #3, ...)
 * against the SAME environment (no DB reset between runs), copy each
 * resulting out.json aside, and diff the semantic fields — NOT the whole
 * file, since post-modified timestamps and revision IDs are expected to
 * differ. See docs/demo-assets/astrea-starter-site/REPEATABILITY-TEST.md
 * for the full procedure and comparison script.
 *
 * Run via `wp-playground-cli php`/`run-blueprint` (true top-level scope —
 * wp-load.php below applies), or via any harness that has already
 * bootstrapped WordPress before including this file (in which case delete
 * the require_once line below; $wpdb etc. are already in scope from the
 * including harness).
 */
require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-includes/blocks.php';

function astrea_snap_count_wrappers( array $blocks, string $class_needle, int &$depth_max, int $depth = 0 ): int {
	$count = 0;
	foreach ( $blocks as $block ) {
		if ( isset( $block['blockName'] ) && 'core/cover' === $block['blockName']
			&& isset( $block['attrs']['className'] ) && false !== strpos( $block['attrs']['className'], $class_needle ) ) {
			$count++;
			if ( $depth > $depth_max ) { $depth_max = $depth; }
			if ( ! empty( $block['innerBlocks'] ) ) {
				$count += astrea_snap_count_wrappers( $block['innerBlocks'], $class_needle, $depth_max, $depth + 1 );
			}
		} elseif ( ! empty( $block['innerBlocks'] ) ) {
			$count += astrea_snap_count_wrappers( $block['innerBlocks'], $class_needle, $depth_max, $depth );
		}
	}
	return $count;
}

$snapshot = array();

// A. Content
$snapshot['site_title'] = get_option( 'blogname' );
$post_type_counts = array();
foreach ( array( 'page', 'post', 'attachment', 'astrea_professional', 'astrea_service', 'astrea_case', 'astrea_result', 'astrea_price', 'astrea_faq', 'astrea_voice', 'wp_navigation', 'wp_template_part' ) as $pt ) {
	$counts = wp_count_posts( $pt );
	$total  = 0;
	foreach ( $counts as $status => $n ) {
		if ( in_array( $status, array( 'publish', 'draft', 'private', 'inherit' ), true ) ) {
			$total += (int) $n;
		}
	}
	$post_type_counts[ $pt ] = $total;
}
$snapshot['post_type_counts'] = $post_type_counts;

$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
$snapshot['pages'] = array_map( function ( $p ) {
	return array( 'id' => $p->ID, 'slug' => $p->post_name, 'title' => $p->post_title, 'status' => $p->post_status );
}, $pages );

$professionals = get_posts( array( 'post_type' => 'astrea_professional', 'post_status' => 'any', 'posts_per_page' => -1 ) );
$snapshot['professionals'] = array_map( function ( $p ) { return array( 'id' => $p->ID, 'title' => $p->post_title ); }, $professionals );

foreach ( array( 'astrea_service', 'astrea_case', 'astrea_result', 'astrea_price', 'astrea_faq', 'astrea_voice' ) as $pt ) {
	$items = get_posts( array( 'post_type' => $pt, 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
	$snapshot['cpt_titles'][ $pt ] = array_map( function ( $p ) { return $p->post_title; }, $items );
}

// B. Media
global $wpdb;
$snapshot['attachment_count'] = (int) wp_count_posts( 'attachment' )->inherit;
$attachments = get_posts( array( 'post_type' => 'attachment', 'posts_per_page' => -1, 'post_status' => 'inherit' ) );
$filenames   = array();
foreach ( $attachments as $a ) {
	$file = get_post_meta( $a->ID, '_wp_attached_file', true );
	$filenames[] = basename( (string) $file );
}
sort( $filenames );
$snapshot['attachment_filenames'] = $filenames;
$dupe_check = array_count_values( $filenames );
$snapshot['duplicate_filenames'] = array_keys( array_filter( $dupe_check, function ( $n ) { return $n > 1; } ) );

// C. Blocks
$home_id = (int) get_option( 'page_on_front' );
$snapshot['page_on_front'] = $home_id;
$home_content = $home_id ? get_post( $home_id )->post_content : '';
$blocks = parse_blocks( $home_content );

$results_depth = 0;
$snapshot['results_wrapper_count'] = astrea_snap_count_wrappers( $blocks, 'astrea-results-photoplane', $results_depth );
$snapshot['results_wrapper_max_depth'] = $results_depth;
$hero_depth = 0;
$snapshot['hero_wrapper_count'] = astrea_snap_count_wrappers( $blocks, 'astrea-hero-photoplane', $hero_depth );
$snapshot['hero_wrapper_max_depth'] = $hero_depth;

// button/CTA count on home
function astrea_snap_count_blocks_by_name( array $blocks, string $name ): int {
	$c = 0;
	foreach ( $blocks as $b ) {
		if ( isset( $b['blockName'] ) && $name === $b['blockName'] ) { $c++; }
		if ( ! empty( $b['innerBlocks'] ) ) { $c += astrea_snap_count_blocks_by_name( $b['innerBlocks'], $name ); }
	}
	return $c;
}
$snapshot['home_button_count'] = astrea_snap_count_blocks_by_name( $blocks, 'core/button' );
$snapshot['home_cover_count']  = astrea_snap_count_blocks_by_name( $blocks, 'core/cover' );

// Header CTA + phone bindings (theme part, not DB, but confirm identity text)
$snapshot['results_list_count'] = astrea_snap_count_blocks_by_name( $blocks, 'astrea/results-list' );

// D. Navigation
$navs = get_posts( array( 'post_type' => 'wp_navigation', 'post_status' => 'any', 'posts_per_page' => -1 ) );
$snapshot['navigation_count'] = count( $navs );
$nav_items = array();
foreach ( $navs as $nav ) {
	$nav_blocks = parse_blocks( $nav->post_content );
	foreach ( $nav_blocks as $nb ) {
		if ( isset( $nb['blockName'] ) && 'core/navigation-link' === $nb['blockName'] ) {
			$nav_items[] = $nb['attrs']['label'] ?? '?';
		}
	}
}
$snapshot['navigation_items'] = $nav_items;
$snapshot['navigation_duplicate_labels'] = array_keys( array_filter( array_count_values( $nav_items ), function ( $n ) { return $n > 1; } ) );

// E. Settings
$snapshot['home'] = get_option( 'home' );
$snapshot['siteurl'] = get_option( 'siteurl' );
$snapshot['show_on_front'] = get_option( 'show_on_front' );
$snapshot['page_on_front_option'] = get_option( 'page_on_front' );
$snapshot['generated_pages'] = get_option( 'astrea_core_generated_pages' );
$snapshot['generated_navigation'] = get_option( 'astrea_core_generated_navigation' );
$profile = get_option( 'astrea_core_office_profile' );
$snapshot['office_profile_office_name'] = $profile['office_name'] ?? null;

// F. Identity
$body_haystack = $home_content . ' ' . wp_json_encode( $snapshot['professionals'] ) . ' ' . ( $snapshot['office_profile_office_name'] ?? '' );
$snapshot['identity_checks'] = array(
	'astrea_office_name_present' => false !== strpos( $body_haystack, 'ASTREA行政書士事務所' ),
	'ibuki_fumito_present'       => false !== strpos( $body_haystack, '伊吹' ) && false !== strpos( $body_haystack, '文人' ),
	'old_yamada_count'           => substr_count( $body_haystack, 'yamada' ) + substr_count( $body_haystack, '山田' ) + substr_count( $body_haystack, 'やまだ' ),
	'old_ibu_ifuo_count'         => substr_count( $body_haystack, '伊府' ) + substr_count( $body_haystack, '伊夫男' ),
);

// Portability
$snapshot['stale_url_scan'] = array();
foreach ( array( 'localhost', '127.0.0.1', '172.29.', ':8900', ':8901', ':8902', 'demo.project-if.jp', 'yamada' ) as $needle ) {
	$c = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE %s", '%' . $wpdb->esc_like( $needle ) . '%' ) );
	if ( $c > 0 ) { $snapshot['stale_url_scan'][ $needle ] = $c; }
}

file_put_contents( '/snapshot/out.json', wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
echo "snapshot written\n";
