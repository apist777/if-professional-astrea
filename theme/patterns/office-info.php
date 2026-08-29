<?php
/**
 * Title: 事務所情報
 * Slug: astrea/office-info
 * Categories: astrea
 * Description: 事務所名・所在地・電話番号（astrea/office-summary Dynamic Block、Construction Order 015Eより）に、営業時間・SNSリンク（Construction Order 011の既存Dynamic Block）を組み合わせた、事務所概要ページ等へ挿入可能なPattern。Professional Profile・ACCESS固有情報（最寄駅・徒歩時間・駐車場・地図等）は含まない（Decision 022の責任分離）。
 *
 * @package Astrea\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}
?>
<!-- wp:group {"className":"astrea-office-page","layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group astrea-office-page">

<!-- wp:astrea/office-summary /-->

<!-- wp:astrea/office-hours {"heading":"営業時間"} /-->

<!-- wp:astrea/office-sns {"heading":"SNS"} /-->

</div>
<!-- /wp:group -->
