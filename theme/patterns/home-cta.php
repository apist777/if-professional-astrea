<?php
/**
 * Title: HOME - CTA
 * Slug: astrea/home-cta
 * Categories: astrea
 * Description: ページ末尾のCTA。電話番号（Office Profile Bindings）と、お問い合わせページへのボタンを配置する。お問い合わせページへのリンク先はPattern挿入後にユーザーがリンクを設定する（Construction Order 007のSetup機能で作成される問い合わせページを指定する）。
 *
 * Construction Order 016D-R2 §5 (position + button treatment only — same
 * Block/Contact-URL/Phone data, no new functionality):
 * - Now placed immediately after Price in HOME_PATTERN_SLUGS
 *   (core/includes/setup-home.php), matching the Owner Reference's closing
 *   band directly under the price table rather than at the very page end.
 * - Button pairing swapped to match the Reference: phone is now the
 *   Outline button (white border/text on this dark Contrast band),
 *   Contact is now the solid Gold (Accent) button — this is the
 *   established "Gold = primary action" relationship used elsewhere in
 *   Visual v3 (e.g. the Hero kicker, Section Heading kickers), not a new
 *   convention.
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:group {"tagName":"section","className":"astrea-final-cta","style":{"spacing":{"padding":{"top":"var:preset|spacing|x-large","bottom":"var:preset|spacing|x-large"}}},"backgroundColor":"contrast","textColor":"base","layout":{"type":"constrained","contentSize":"600px"}} -->
<section class="wp-block-group astrea-final-cta has-base-color has-contrast-background-color has-text-color has-background">

<!-- wp:heading {"textAlign":"center","fontFamily":"heading"} -->
<h2 class="has-text-align-center has-heading-font-family">まずはお気軽にご相談ください</h2>
<!-- /wp:heading -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"textColor":"base","className":"is-style-outline","metadata":{"bindings":{"url":{"source":"astrea-core/office-profile","args":{"key":"phone_tel"}},"text":{"source":"astrea-core/office-profile","args":{"key":"phone"}}}}} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-base-color has-text-color wp-element-button" href="#">お電話でのご相談</a></div>
<!-- /wp:button -->

<!-- wp:button {"backgroundColor":"accent","textColor":"contrast"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-contrast-color has-accent-background-color has-text-color wp-element-button" href="#">お問い合わせフォームへ</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

</section>
<!-- /wp:group -->
