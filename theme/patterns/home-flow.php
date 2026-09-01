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
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:group {"tagName":"section","className":"astrea-home-flow","style":{"spacing":{"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large"}}},"backgroundColor":"base","layout":{"type":"constrained"}} -->
<section class="wp-block-group astrea-home-flow has-base-background-color has-background">

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
