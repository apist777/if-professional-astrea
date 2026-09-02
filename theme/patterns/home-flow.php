<?php
/**
 * Title: HOME - Flow
 * Slug: astrea/home-flow
 * Categories: astrea
 * Description: ご相談から依頼までの流れを紹介する、編集可能な静的Pattern。ステップ数は固定せず自由に追加・削除できる（Decision 028、Coreデータ化しない）。
 *
 * Construction Order 016D-R2 §8/§9: same content/data (no new Flow
 * feature) — restyled as a horizontal 01/02/03 Step sequence on Desktop
 * (Tablet/Mobile stack vertically, see the `.astrea-flow-steps` CSS in
 * theme.json), Section Rhythm changed from Surface (gray) to Base
 * (white/"light") to match the Owner Reference's rhythm target list, and
 * given the same English Kicker treatment as every other Section Heading
 * (`h2.astrea-flow-heading`, see theme.json's Kicker system) so the
 * Editorial language stays consistent all the way to the page bottom.
 *
 * Construction Order 016E-R2 §8: the wrapping Group's `layout:constrained`
 * was silently capping the Heading/List to a narrow (~625px, not
 * viewport-responsive) column via WordPress Core's own
 * `.is-layout-constrained > *` contentSize logic — switched to
 * `layout:{"type":"flow"}` (no automatic child width constraint) as a
 * first pass, but that alone did not fix it: the deeper cause is one
 * level further up. This Group itself (not just its children) has no
 * `align:full`, so the PAGE template's own constrained post-content
 * layout (front-page.html's `wp:post-content`, `align:full` +
 * `layout:inherit` since Construction 016B-R2) caps the `<section>`
 * element itself to the theme's default contentSize (720px) — no CSS
 * on the children can widen a box whose own parent is already capped.
 * `align:full` here (matching Hero's own outer Group) lifts that cap;
 * the section's own background stays full-bleed while its H2/OL
 * content is inset via this Pattern's own Shared Wide Grid CSS, exactly
 * like Services/CASE/Price/etc (bare Dynamic Blocks that sit directly
 * in post-content and were never subject to this problem at all).
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:group {"align":"full","tagName":"section","className":"astrea-home-flow","style":{"spacing":{"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large"}}},"backgroundColor":"base","layout":{"type":"flow"}} -->
<section class="wp-block-group alignfull astrea-home-flow has-base-background-color has-background">

<!-- wp:heading {"className":"astrea-flow-heading","fontFamily":"heading"} -->
<h2 class="astrea-flow-heading has-heading-font-family">ご相談の流れ</h2>
<!-- /wp:heading -->

<!-- wp:list {"type":"ol","className":"astrea-flow-steps"} -->
<ol class="astrea-flow-steps">
<!-- wp:list-item -->
<li><strong>お問い合わせ</strong> — フォームまたはお電話にてご連絡ください。</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><strong>ご相談・お見積り</strong> — ご状況を伺い、対応方針をご案内します。</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><strong>ご契約・着手</strong> — 内容にご納得いただいた上で対応を開始します。</li>
<!-- /wp:list-item -->
</ol>
<!-- /wp:list -->

</section>
<!-- /wp:group -->
