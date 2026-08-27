<?php
/**
 * Title: HOME - Professional Teaser
 * Slug: astrea/home-professional-teaser
 * Categories: astrea
 * Description: 代表者（Professional Profile）を1名紹介する。代表者が未設定の場合はセクション全体を非表示にする（Decision 028）。
 *
 * Intentionally has NO wrapping Group with padding/background: the
 * astrea/representative Dynamic Block itself returns an empty string when
 * there is no representative to show, but a static wrapper block cannot
 * disappear along with it — giving that wrapper visible padding/background
 * would leave a hollow section on the page, defeating the whole-section
 * self-hide behaviour Decision 028 requires for HOME teasers. Spacing for
 * the rendered `.wp-block-astrea-representative` output is instead handled
 * via theme.json's `styles.blocks` CSS, which only applies when the div
 * actually exists.
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:astrea/representative {"heading":"代表者紹介"} /-->
