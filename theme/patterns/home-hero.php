<?php
/**
 * Title: HOME - Hero
 * Slug: astrea/home-hero
 * Categories: astrea
 * Description: HOME冒頭のHero。事務所名（Office Profile）と、編集可能なキャッチコピー・電話CTAを配置する。
 *
 * Construction Order 011: the office-name element is a Heading (level 1),
 * not a Paragraph — this is the ONLY H1 on the assembled HOME page (the
 * previous Paragraph-based version left HOME with no H1 at all). Visual
 * weight is kept identical to before via the same `xx-large`/`heading`
 * theme.json tokens, so becoming semantically an H1 does not make it look
 * any bigger than it already did (Semantic and Visual Styling are
 * deliberately decoupled here).
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:group {"tagName":"section","style":{"spacing":{"padding":{"top":"var:preset|spacing|x-large","bottom":"var:preset|spacing|x-large"}}},"backgroundColor":"surface","layout":{"type":"constrained"}} -->
<section class="wp-block-group has-surface-background-color has-background">

<!-- wp:heading {"level":1,"fontSize":"xx-large","fontFamily":"heading","metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"office_name"}}}}} -->
<h1 class="has-heading-font-family has-xx-large-font-size">ASTREA</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"large"} -->
<p class="has-large-font-size">お客様に寄り添う、専門家によるご相談窓口です。</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"metadata":{"bindings":{"url":{"source":"astrea-core/office-profile","args":{"key":"phone_tel"}},"text":{"source":"astrea-core/office-profile","args":{"key":"phone"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">お電話でのご相談</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">お問い合わせはこちら</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

</section>
<!-- /wp:group -->
