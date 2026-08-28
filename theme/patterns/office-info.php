<?php
/**
 * Title: 事務所情報
 * Slug: astrea/office-info
 * Categories: astrea
 * Description: 事務所名・所在地・電話番号（既存Block Bindings）に、営業時間・SNSリンク（Construction Order 011の新規Dynamic Block）を組み合わせた、事務所概要ページ等へ挿入可能なPattern。Professional Profile・ACCESS固有情報（最寄駅・徒歩時間・駐車場・地図等）は含まない（Decision 022の責任分離）。
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"office_name"}}}}} -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"address"}}}}} -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"astrea-core/office-profile","args":{"key":"phone"}}}}} -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:astrea/office-hours {"heading":"営業時間"} /-->

<!-- wp:astrea/office-sns {"heading":"SNS"} /-->

</div>
<!-- /wp:group -->
