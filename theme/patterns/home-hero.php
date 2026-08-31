<?php
/**
 * Title: HOME - Hero
 * Slug: astrea/home-hero
 * Categories: astrea
 * Description: HOME冒頭のHero（Visual v3, Construction Order 016B / 016B-R1）。事務所名（Office Profile）と、編集可能なキャッチコピー・電話/お問い合わせCTAを配置する。写真は任意（core/coverのImageをEditorから差し替え可能）——写真が無い状態でも単色Coverとして完成して見えることを標準状態とする。
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
 * - Text colour is `base` (white) throughout, not the Mockup's dark-navy-
 *   on-light-sky scheme — the Mockup's approved photo happened to have a
 *   bright, empty area on the left; a shipped default must stay legible
 *   over an ARBITRARY future photo of unknown brightness/composition, so
 *   it uses the same dark-overlay-plus-light-text convention already
 *   established for HOME's Closing CTA and the prior (015G) Hero, applied
 *   via the same `textColor:"base"` Group wrapper technique (Design
 *   Direction §10 explicitly allows implementing the intent rather than
 *   every Mockup CSS declaration literally).
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
 * Construction Order 016B-R1 implementation notes (Japanese Typography /
 * Hero Hierarchy revision — see
 * docs/research/2026-08-30_construction_016b_r1_japanese_typography_report.md):
 * - Semantic H1 / Visual Hero Copy separation: the office-name H1 is now
 *   styled as a SMALL Kicker (`astrea-hero-kicker`) instead of the large
 *   headline. A NEW, non-heading Paragraph (`astrea-hero-primary`) carries
 *   the large Editorial "Visual Primary Copy" that 016A-R1's approved
 *   Mockup treated as the Hero's visual focal point. This does NOT break
 *   Construction 011's "H1 = office name, only one H1 on HOME" contract —
 *   it only decouples *visual size* from *semantic heading level*, the
 *   same decoupling this exact Pattern already relied on before Visual v3
 *   (see the original 011 comment above: "Visual weight is kept identical
 *   ... Semantic and Visual Styling are deliberately decoupled here").
 * - The shipped Default's Visual Primary Copy reuses the pre-existing,
 *   already-reviewed generic supporting line ("お客様に寄り添う、専門家に
 *   よるご相談窓口です。") rather than inventing new marketing copy for the
 *   product default. No third "Supporting Copy" paragraph is shipped by
 *   default (kept to two tiers: Kicker + Primary) for the same
 *   minimal-invention reason; a site owner may add one from the Editor.
 * - Japanese line-breaking: `.astrea-hero-text` sets
 *   `word-break:normal;line-break:strict;overflow-wrap:anywhere` (CSS
 *   Text properties that inherit to the Kicker/Primary/Copy children), and
 *   each text element additionally uses `text-wrap:pretty` to ask the
 *   browser to balance line breaks and avoid short "orphan" trailing
 *   lines. This is a content-agnostic rendering hint — it works for ANY
 *   text a site owner types (long office names, mixed Japanese/Latin
 *   names, etc.), not just this Pattern's own default copy, and requires
 *   no hardcoded `<br>` tags.
 * - Font stack root-cause fix: the actual Typography bug Owner flagged
 *   (Kanji rendering with Simplified-Chinese-style glyph shapes on some
 *   platforms) was not a Markup/Pattern issue at all — it was
 *   `theme.json`'s font stacks naming only Mac/Windows Japanese font names
 *   with a bare `serif`/`sans-serif` keyword as the sole fallback. See
 *   `docs/research/2026-08-30_construction_016b_r1_japanese_typography_report.md`
 *   for the root-cause investigation and the new cross-platform
 *   Japanese-first font stacks (this Pattern's Markup did not need to
 *   change for that fix).
 *
 * Construction Order 016B-R2 implementation notes (First View
 * Reconstruction — see
 * docs/research/2026-08-30_construction_016b_r2_first_view_reconstruction_report.md):
 * - `minHeight` is now `62vh` (was a fixed `560px`). The Hero's real root
 *   cause of looking "boxed" on Desktop was NOT this Pattern at all — it
 *   was `theme/templates/front-page.html`'s bare `wp:post-content` block
 *   silently constraining the entire post content area (Hero included) to
 *   `contentSize` (720px). Fixing that (see front-page.html) restored true
 *   full-bleed width; this `vh` change additionally makes the Hero's
 *   height scale with the actual viewport (Order §6) instead of a fixed
 *   pixel floor that read as ~92% of viewport height on short laptop
 *   screens (1366×768) and ~66% on tall ones (1920×1080).
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","minHeight":62,"minHeightUnit":"vh","contentPosition":"center left","align":"full","className":"astrea-hero-v3"} -->
<div class="wp-block-cover alignfull astrea-hero-v3" style="min-height:62vh">
<span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span>
<div class="wp-block-cover__inner-container is-position-center-left has-custom-content-position">

<!-- wp:group {"className":"astrea-hero-text","textColor":"base"} -->
<div class="wp-block-group astrea-hero-text has-base-color has-text-color">

<!-- wp:heading {"level":1,"className":"astrea-hero-kicker","metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"office_name"}}}}} -->
<h1 class="astrea-hero-kicker">ASTREA</h1>
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

</div>
</div>
<!-- /wp:cover -->
