<?php
/**
 * Title: HOME - Hero
 * Slug: astrea/home-hero
 * Categories: astrea
 * Description: HOME冒頭のHero。事務所名（Office Profile）と、編集可能なキャッチコピー・電話CTAを配置する。
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:group {"tagName":"section","style":{"spacing":{"padding":{"top":"var:preset|spacing|x-large","bottom":"var:preset|spacing|x-large"}}},"backgroundColor":"surface","layout":{"type":"constrained"}} -->
<section class="wp-block-group has-surface-background-color has-background">

<!-- wp:paragraph {"fontSize":"xx-large","fontFamily":"heading","metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"office_name"}}}}} -->
<p class="has-heading-font-family has-xx-large-font-size">ASTREA</p>
<!-- /wp:paragraph -->

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
