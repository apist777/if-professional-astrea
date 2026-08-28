<?php
/**
 * Title: HOME - Services Teaser
 * Slug: astrea/home-services-teaser
 * Categories: astrea
 * Description: 取扱業務の新着3件を紹介する。1件も無い場合はセクション全体（見出し含む）を非表示にする（Decision 028）。
 *
 * Construction Order 011: replaced the original Query Loop implementation
 * with the new astrea/service-list Dynamic Block. Query Loop could not
 * hide its own section heading at zero items (only swap in a
 * `core/query-no-results` message) — the one HOME Teaser that never
 * conformed to Decision 028's whole-section self-hide rule, closed by
 * giving Service the same Dynamic Block treatment every other HOME Teaser
 * already has. Intentionally has NO wrapping Group with padding/background
 * — see home-professional-teaser.php's docblock for why.
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:astrea/service-list {"limit":3,"heading":"取扱業務"} /-->
