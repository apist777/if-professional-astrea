<?php
/**
 * Title: HOME - RESULTS Teaser
 * Slug: astrea/home-results-teaser
 * Categories: astrea
 * Description: 実績（ラベル+値）を紹介する。1件も無い場合はセクション全体（見出し含む）を非表示にする（Decision 028、Construction Order 010）。
 *
 * Construction Order 025-E1: wraps astrea/results-list in a standard
 * `core/cover` block — the exact same WordPress-native mechanism already
 * used by home-hero.php's `astrea-hero-photoplane` — so a site owner can
 * set/replace/remove a background image, adjust its overlay color/opacity,
 * and set its focal point entirely from the standard Cover Block sidebar,
 * with zero custom PHP UI.
 *
 * With no image selected (the state every NEW insertion of this pattern
 * starts in, matching WordPress's own "no image" Cover placeholder), the
 * Cover's own `dimRatio:100`/`overlayColor:contrast` renders a solid navy
 * fill behind `astrea/results-list`, which is visually indistinguishable
 * from the pre-025-E1 unwrapped block (both are the same solid navy —
 * see theme.json's `.astrea-results-photoplane:has(.wp-block-cover__image-background)`
 * rule, which only makes results-list's own background transparent once a
 * real background image is actually present).
 *
 * Backward compatibility: this new wrapper only affects *newly inserted*
 * copies of this pattern. Already-published content that still has the
 * old, unwrapped `<!-- wp:astrea/results-list /-->` markup (with no
 * `astrea-results-photoplane` ancestor at all) is completely unaffected —
 * `.wp-block-astrea-results-list`'s own CSS rule is untouched, so it keeps
 * rendering exactly as it did on Theme 1.0.2, pixel-for-pixel, with no
 * re-save required.
 *
 * `style.spacing.padding:0` explicitly overrides one of WordPress Core's
 * own Cover Block defaults (a 20px inner-container padding — confirmed
 * present, if invisible, on `astrea-hero-photoplane` too) via the same
 * attribute-driven inline style the Block Editor itself would generate,
 * so results-list's own edge-to-edge CSS reaches the Cover's full width.
 *
 * Core's OTHER default — a 430px `min-height` — is deliberately NOT
 * neutralized via a `minHeight` attribute: Gutenberg's own `save()`
 * treats `minHeight:0` as falsy/unset and omits it from the generated
 * `style` attribute, so hand-writing a literal `min-height:0px` into this
 * markup would make the saved post body permanently disagree with what
 * the Block Editor regenerates, tripping "Block contains unexpected or
 * invalid content" on every future edit. Instead, `theme.json` carries a
 * plain `.astrea-results-photoplane{min-height:0;}` CSS rule that
 * neutralizes Core's 430px default without touching any block attribute
 * — verified via the Block Editor's own validation (no warning) and a
 * pixel-diff against the pre-025-E1 unwrapped block (zero-byte diff).
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","align":"full","className":"astrea-results-photoplane","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}}} -->
<div class="wp-block-cover alignfull astrea-results-photoplane" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
<span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span>
<div class="wp-block-cover__inner-container">
<!-- wp:astrea/results-list {"heading":"実績"} /-->
</div>
</div>
<!-- /wp:cover -->
