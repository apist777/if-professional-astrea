<?php
/**
 * Construction 028-F — ASTREA Live Demo-only layer.
 *
 * Appends the fictional/demo disclosure to the 事務所概要 (About) page.
 * This text ("このWebサイトは...デモサイトです。...実在の事務所・人物とは
 * 一切関係ありません。") identifies project-if.jp's PUBLIC LIVE DEMO
 * specifically. It is NOT part of the ASTREA Starter Site product and must
 * never be run as part of building a Starter Site a user will actually
 * adopt — only when building the public Live Demo, as an explicit extra
 * step after the 9-step Starter Site pipeline completes.
 *
 * Idempotent: if a `.astrea-demo-disclosure` group already exists anywhere
 * in the About page's content, this is a no-op (Construction 028-F Phase
 * 6 — re-running must never grow the disclosure count past 1).
 *
 * Run via `wp-playground-cli php`/`wp eval-file` (true top-level scope —
 * wp-load.php below applies), or via any harness that has already
 * bootstrapped WordPress before including this file (delete the
 * require_once line in that case; WordPress functions are already in scope).
 */
require_once '/wordpress/wp-load.php';

function line( $msg ) { echo $msg . "\n"; }

$about_page = get_page_by_path( '事務所概要' );
if ( ! $about_page ) {
	// Fallback: find by title if slug differs.
	$found      = get_posts( array( 'post_type' => 'page', 'title' => '事務所概要', 'posts_per_page' => 1 ) );
	$about_page = $found ? $found[0] : null;
}

if ( ! $about_page ) {
	line( 'ERROR: 事務所概要 page not found — disclosure not added.' );
	exit( 1 );
}

if ( false !== strpos( $about_page->post_content, 'astrea-demo-disclosure' ) ) {
	line( 'Live Demo disclosure already present on page ' . $about_page->ID . ' — no change.' );
	exit( 0 );
}

$disclosure = "\n\n<!-- wp:group {\"align\":\"wide\",\"className\":\"astrea-demo-disclosure\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"1.5rem\",\"bottom\":\"1.5rem\",\"left\":\"1.5rem\",\"right\":\"1.5rem\"}},\"border\":{\"width\":\"1px\",\"radius\":\"8px\"}},\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group alignwide astrea-demo-disclosure\" style=\"border-width:1px;border-radius:8px;padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem\">\n<!-- wp:paragraph {\"fontSize\":\"small\"} -->\n<p class=\"has-small-font-size\">このWebサイトは If Professional ASTREA のデモサイトです。掲載されている事務所・人物・サービス内容・実績・お客様の声等は、デモ用に作成された架空の情報です。実在の事務所・人物とは一切関係ありません。</p>\n<!-- /wp:paragraph -->\n</div>\n<!-- /wp:group -->\n";

wp_update_post( array(
	'ID'           => $about_page->ID,
	'post_content' => $about_page->post_content . $disclosure,
) );
line( 'Live Demo disclosure appended to page ' . $about_page->ID . '.' );
