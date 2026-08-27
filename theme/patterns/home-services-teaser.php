<?php
/**
 * Title: HOME - Services Teaser
 * Slug: astrea/home-services-teaser
 * Categories: astrea
 * Description: 取扱業務の新着3件を紹介する。Query Loopはセクション見出しを含めた完全非表示ができないため、0件時はNo Resultsメッセージを表示する（Decision 028、Archive専用ページと同じ扱い。詳細はConstruction Order 008施工報告参照）。
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:group {"tagName":"section","style":{"spacing":{"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group">

<!-- wp:heading {"fontFamily":"heading"} -->
<h2 class="has-heading-font-family">取扱業務</h2>
<!-- /wp:heading -->

<!-- wp:query {"query":{"postType":"astrea_service","inherit":false,"perPage":3}} -->
<div class="wp-block-query">
<!-- wp:post-template -->
<!-- wp:group {"tagName":"article","layout":{"type":"constrained"}} -->
<article class="wp-block-group">
<!-- wp:post-title {"level":3,"isLink":true} /-->
<!-- wp:post-excerpt /-->
</article>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>現在、取扱業務の情報は準備中です。</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->

</section>
<!-- /wp:group -->
