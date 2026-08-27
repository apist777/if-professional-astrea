<?php
/**
 * Title: HOME - FAQ
 * Slug: astrea/home-faq
 * Categories: astrea
 * Description: 重要FAQを最大3件紹介する。重要FAQが1件も無い場合はセクション全体（見出し含む）を非表示にする（Decision 028）。FAQ全件は専用のFAQ一覧ページ（Archiveテンプレート）を利用する。
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
<!-- wp:astrea/faq-list {"mode":"important","limit":3,"heading":"よくあるご質問"} /-->
