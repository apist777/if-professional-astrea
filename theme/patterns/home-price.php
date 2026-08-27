<?php
/**
 * Title: HOME - Price
 * Slug: astrea/home-price
 * Categories: astrea
 * Description: 料金情報を紹介する。料金が1件も無い場合はセクション全体（見出し含む）を非表示にする（Decision 028。専用の料金ページには「料金一覧」Patternを使う）。
 *
 * Intentionally has NO wrapping Group with padding/background — see
 * home-professional-teaser.php's docblock for why.
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:astrea/price-list {"heading":"料金"} /-->
