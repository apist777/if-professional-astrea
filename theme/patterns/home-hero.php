<?php
/**
 * Title: HOME - Hero
 * Slug: astrea/home-hero
 * Categories: astrea
 * Description: HOME冒頭のHero（Visual v3, Construction Order 016B）。事務所名（Office Profile）と、編集可能なキャッチコピー・電話/お問い合わせCTAを配置する。写真は任意（core/coverのImageをEditorから差し替え可能）——写真が無い状態でも単色Coverとして完成して見えることを標準状態とする。
 *
 * Construction Order 011: the office-name element is a Heading (level 1),
 * not a Paragraph — this is the ONLY H1 on the assembled HOME page. Visual
 * v3 (Construction Order 016A/016A-R1, Owner-approved) keeps this same
 * H1/Binding contract; only the surrounding structure/Typography changed.
 *
 * Construction Order 016B implementation notes:
 * - `core/cover` with NO `url` set is the shipped default — Gutenberg
 *   renders this as a solid-colour block (the `overlayColor` alone), which
 *   already satisfies "no-photo state must look like a deliberate design,
 *   not a broken template" (Design Direction §11) with zero extra Theme
 *   code. A site owner adds their own photo via the ordinary Cover block
 *   image control; no separate "with photo" markup exists to maintain.
 * - The Eyebrow repeats the office name in a small kicker style above the
 *   large H1 — same real Binding as the H1, not fabricated copy.
 * - Text colour is `base` (white) throughout, not the Mockup's dark-navy-
 *   on-light-sky scheme — the Mockup's approved photo happened to have a
 *   bright, empty area on the left; a shipped default must stay legible
 *   over an ARBITRARY future photo of unknown brightness/composition, so
 *   it uses the same dark-overlay-plus-light-text convention already
 *   established for HOME's Closing CTA and the prior (015G) Hero, applied
 *   via the same `textColor:"base"` Group wrapper technique (Design
 *   Direction §10 explicitly allows implementing the intent rather than
 *   every Mockup CSS declaration literally).
 * - Supporting copy text is unchanged from the pre-016B Hero (deliberately
 *   generic/profession-agnostic; Visual v3's more specific Fixture copy
 *   belongs to the Owner Fixture only, not the shipped product default —
 *   see docs/research/2026-08-29_astrea_visual_v3_design_research.md §1
 *   "Fixtureであり製品Default Copyとは区別する").
 * - The next-section hint is pure Theme CSS (a `::after` on the Cover's own
 *   className, no Markup) — a hand-added sibling element inside a Cover
 *   block's static wrapper would fail Gutenberg's Block Validation (Cover
 *   re-derives its own wrapper/background/inner-container from its
 *   attributes; anything outside that plus its actual InnerBlocks does not
 *   round-trip). Also deliberately has no hardcoded section name (the
 *   approved Mockup's "01 SERVICES" label assumed a fixed Section order;
 *   the shipped Pattern does not assume which Pattern follows it — see
 *   Construction 016B report).
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","minHeight":560,"contentPosition":"center left","align":"full","className":"astrea-hero-v3"} -->
<div class="wp-block-cover alignfull astrea-hero-v3" style="min-height:560px">
<span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span>
<div class="wp-block-cover__inner-container is-position-center-left has-custom-content-position">

<!-- wp:group {"className":"astrea-hero-text","textColor":"base","layout":{"type":"constrained","contentSize":"660px"}} -->
<div class="wp-block-group astrea-hero-text has-base-color has-text-color">

<!-- wp:paragraph {"className":"astrea-hero-eyebrow","metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"office_name"}}}}} -->
<p class="astrea-hero-eyebrow">ASTREA</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"astrea-hero-h1","fontFamily":"heading","metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"office_name"}}}}} -->
<h1 class="astrea-hero-h1 has-heading-font-family">ASTREA</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"astrea-hero-copy","fontSize":"large"} -->
<p class="astrea-hero-copy has-large-font-size">お客様に寄り添う、専門家によるご相談窓口です。</p>
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

</div>
<!-- /wp:group -->

</div>
</div>
<!-- /wp:cover -->
