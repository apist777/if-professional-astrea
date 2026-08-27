<?php
/**
 * Title: HOME - Flow
 * Slug: astrea/home-flow
 * Categories: astrea
 * Description: ご相談から依頼までの流れを紹介する、編集可能な静的Pattern。ステップ数は固定せず自由に追加・削除できる（Decision 028、Coreデータ化しない）。
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:group {"tagName":"section","style":{"spacing":{"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large"}}},"backgroundColor":"surface","layout":{"type":"constrained"}} -->
<section class="wp-block-group has-surface-background-color has-background">

<!-- wp:heading {"fontFamily":"heading"} -->
<h2 class="has-heading-font-family">ご相談の流れ</h2>
<!-- /wp:heading -->

<!-- wp:list {"type":"ol"} -->
<ol>
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
