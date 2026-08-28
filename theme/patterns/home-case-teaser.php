<?php
/**
 * Title: HOME - CASE Teaser
 * Slug: astrea/home-case-teaser
 * Categories: astrea
 * Description: 対応事例を最大3件紹介する。1件も無い場合はセクション全体（見出し含む）を非表示にする（Decision 028、Construction Order 010）。事例全件は専用の対応事例一覧ページ（Archiveテンプレート）を利用する。
 *
 * Intentionally has NO wrapping Group with padding/background — see
 * home-professional-teaser.php's docblock for why.
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:astrea/case-list {"limit":3,"heading":"対応事例"} /-->
