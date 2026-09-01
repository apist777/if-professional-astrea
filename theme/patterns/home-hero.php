<?php
/**
 * Title: HOME - Hero
 * Slug: astrea/home-hero
 * Categories: astrea
 * Description: HOME冒頭のHero（Visual v3）。事務所名（Office Profile）と、編集可能なキャッチコピー・電話/お問い合わせCTAを配置する。写真は任意——写真が無い状態でも完成して見えることを標準状態とする。
 *
 * Construction Order 011: the office-name element is a Heading (level 1),
 * not a Paragraph — this is the ONLY H1 on the assembled HOME page. Every
 * later Visual v3 revision (016A through 016D-R1) keeps this same
 * H1/Binding contract; only the surrounding structure/Typography changed.
 *
 * Construction Order 016D-R1 implementation notes (Owner Reference
 * Fidelity — MAJOR Hero reconstruction, see
 * docs/research/2026-09-01_construction_016d_r1_reference_fidelity_report.md):
 *
 * - Text Plane + Photography, not an Overlay Hero. Earlier Visual v3
 *   revisions (016B through 016D) used a single `core/cover` with the
 *   photo as a full-bleed background and text scrimmed on top. The Owner
 *   Reference composition is two separate visual planes side by side: a
 *   light Editorial text panel on the left, large photography on the
 *   right — not text-on-photo. This Pattern is now an outer flex Group
 *   with two children instead of one Cover block.
 * - The photo-plane is still a `core/cover` with NO `url` as the shipped
 *   default (Gutenberg's native no-image solid-colour rendering) — the
 *   exact same "no-photo must look deliberate, not broken" mechanism
 *   established in 016B, just confined to the right-hand plane now
 *   instead of the whole Hero. A site owner adds their own photo via the
 *   ordinary Cover block image control; no separate "with photo" markup
 *   exists to maintain.
 * - Text colour is now dark (`contrast`) on a light (`base`) panel,
 *   NOT white-on-photo. This is actually a robustness IMPROVEMENT over
 *   the previous overlay approach, not a regression: text legibility no
 *   longer depends on an arbitrary future photo's brightness/composition
 *   at all, since text never sits on top of the photo in this layout.
 * - Kicker (H1) is Gold (`accent`) per the Reference; Primary Copy stays
 *   the large Editorial focal point (Construction 016B-R1's Semantic H1 /
 *   Visual Hero Copy separation — H1 stays small, Construction 011's
 *   contract is unaffected).
 * - The next-section hint (`::after` on `.astrea-hero-v3`, pure Theme
 *   CSS, no Markup) is preserved from 016B — still applies to the outer
 *   wrapper, still has no hardcoded section name.
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:group {"align":"full","className":"astrea-hero-v3","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"stretch"}} -->
<div class="wp-block-group alignfull astrea-hero-v3">

<!-- wp:group {"className":"astrea-hero-textplane","backgroundColor":"base","textColor":"contrast","layout":{"type":"constrained"}} -->
<div class="wp-block-group astrea-hero-textplane has-contrast-color has-base-background-color has-text-color has-background">

<!-- wp:heading {"level":1,"className":"astrea-hero-kicker","textColor":"accent","metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"office_name"}}}}} -->
<h1 class="astrea-hero-kicker has-accent-color has-text-color">ASTREA</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"astrea-hero-primary","fontFamily":"heading"} -->
<p class="astrea-hero-primary has-heading-font-family">お客様に寄り添う、専門家によるご相談窓口です。</p>
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

<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","minHeight":62,"minHeightUnit":"vh","className":"astrea-hero-photoplane"} -->
<div class="wp-block-cover astrea-hero-photoplane" style="min-height:62vh">
<span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span>
<div class="wp-block-cover__inner-container">
</div>
</div>
<!-- /wp:cover -->

</div>
<!-- /wp:group -->
