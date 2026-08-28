<?php
/**
 * Title: HOME - VOICE Teaser
 * Slug: astrea/home-voice-teaser
 * Categories: astrea
 * Description: お客様の声を最大3件紹介する。1件も無い場合はセクション全体（見出し含む）を非表示にする（Decision 028、Construction Order 010）。
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
<!-- wp:astrea/voice-list {"limit":3,"heading":"お客様の声"} /-->
