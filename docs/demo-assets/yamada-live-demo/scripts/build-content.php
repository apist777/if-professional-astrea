<?php
/**
 * Construction 023 — local content build script for "やまだ行政書士事務所".
 *
 * Uses WordPress's own post/postmeta/option API exclusively (the same
 * capabilities ASTREA Core itself provides and the same Setup functions
 * the real admin UI buttons call) — no raw SQL, no fabricated capability.
 * Run once via `wp-playground-cli php` against the mounted wordpress-data
 * directory while the `server` process is stopped (SQLite is single-writer).
 */

require_once '/wordpress/wp-load.php';

function line( $msg ) { echo $msg . "\n"; }

// ---------------------------------------------------------------------
// 1. Office Profile (Astrea\Core\OfficeProfile)
// ---------------------------------------------------------------------

$office_input = array(
	'office_name' => 'やまだ行政書士事務所',
	'address'     => '東京都新宿区西新宿1-1-1 新宿タワー10F',
	'phone'       => '03-9876-5432',
);
$sanitized = \Astrea\Core\OfficeProfile\sanitize( $office_input );
update_option( \Astrea\Core\OfficeProfile\OPTION_NAME, $sanitized );
line( 'Office profile saved.' );

update_option( 'blogname', 'やまだ行政書士事務所' );
update_option( 'blog_public', 0 );

// ---------------------------------------------------------------------
// Helper: a simple GD-generated placeholder image at a controlled aspect
// ratio (NOT final art — Construction 023 Order §15 explicitly permits
// this for local build purposes; final imagery is a separate, Owner-
// approved future step per §7/§8).
// ---------------------------------------------------------------------

function make_placeholder( string $label, int $w, int $h, string $hex = '2c3e50' ): string {
	$img = imagecreatetruecolor( $w, $h );
	list($r, $g, $b) = sscanf( $hex, "%02x%02x%02x" );
	$bg = imagecolorallocate( $img, $r, $g, $b );
	imagefill( $img, 0, 0, $bg );
	$white = imagecolorallocate( $img, 255, 255, 255 );
	$text  = 'PLACEHOLDER: ' . $label . ' (' . $w . 'x' . $h . ')';
	$font  = 5;
	$tw    = imagefontwidth( $font ) * strlen( $text );
	imagestring( $img, $font, max( 10, (int) ( ( $w - $tw ) / 2 ) ), (int) ( $h / 2 ) - 10, $text, $white );
	$path = '/tmp/ph-' . md5( $label . $w . $h ) . '.png';
	imagepng( $img, $path );
	imagedestroy( $img );
	return $path;
}

function attach_placeholder( string $label, int $w, int $h, int $parent_post_id, string $filename ): int {
	$file_path = make_placeholder( $label, $w, $h );
	$upload    = wp_upload_bits( $filename, null, file_get_contents( $file_path ) );
	if ( $upload['error'] ) {
		line( 'Upload error: ' . $upload['error'] );
		return 0;
	}
	$attachment = array(
		'post_mime_type' => 'image/png',
		'post_title'     => $label,
		'post_status'    => 'inherit',
		'post_parent'    => $parent_post_id,
	);
	$attach_id = wp_insert_attachment( $attachment, $upload['file'], $parent_post_id );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	wp_update_attachment_metadata( $attach_id, $attach_data );
	update_post_meta( $attach_id, '_wp_attachment_image_alt', $label );
	return $attach_id;
}

// ---------------------------------------------------------------------
// 2. Professional (山田太郎) — astrea_professional
// ---------------------------------------------------------------------

$professional_id = wp_insert_post( array(
	'post_type'    => \Astrea\Core\ProfessionalProfile\POST_TYPE,
	'post_title'   => '山田 太郎',
	'post_content' => '行政書士として独立開業。建設業許可・相続手続きを中心に、丁寧なヒアリングを心がけています。',
	'post_status'  => 'publish',
	'menu_order'   => 0,
) );
update_post_meta( $professional_id, \Astrea\Core\ProfessionalProfile\META_QUALIFICATION, '行政書士' );
update_post_meta( $professional_id, \Astrea\Core\ProfessionalProfile\META_CAREER, '行政書士試験合格後、都内法律事務所にて5年間実務経験を積み、2024年に独立開業。' );
update_post_meta( $professional_id, \Astrea\Core\ProfessionalProfile\META_EDUCATION, '早稲田大学法学部卒業' );
update_post_meta( $professional_id, \Astrea\Core\ProfessionalProfile\META_AFFILIATION, '東京都行政書士会 会員' );
update_post_meta( $professional_id, \Astrea\Core\ProfessionalProfile\META_REGISTRATION_INFO, '行政書士登録番号：第98765432号（東京都行政書士会）' );
update_post_meta( $professional_id, \Astrea\Core\ProfessionalProfile\META_IS_REPRESENTATIVE, '1' );
$photo_id = attach_placeholder( '代表者 山田太郎 ポートレート', 900, 1200, $professional_id, 'yamada-professional-portrait.png' );
set_post_thumbnail( $professional_id, $photo_id );
line( "Professional created: $professional_id (photo attachment $photo_id)" );

// ---------------------------------------------------------------------
// 3. Services — astrea_service
// ---------------------------------------------------------------------

$services = array(
	array(
		'title'   => '会社設立サポート',
		'content' => '株式会社・合同会社の設立に必要な定款作成から登記書類の作成まで一貫してサポートします。',
		'icon'    => 'company',
	),
	array(
		'title'   => '建設業許可申請',
		'content' => '新規許可・更新・業種追加など、建設業許可に関する各種申請を代行します。',
		'icon'    => 'permit',
	),
	array(
		'title'   => '相続手続きサポート',
		'content' => '遺言書作成、遺産分割協議書の作成、相続手続き全般をサポートします。',
		'icon'    => 'inheritance',
	),
);

$service_ids = array();
foreach ( $services as $i => $s ) {
	$id = wp_insert_post( array(
		'post_type'    => \Astrea\Core\Service\POST_TYPE,
		'post_title'   => $s['title'],
		'post_content' => $s['content'],
		'post_status'  => 'publish',
		'menu_order'   => $i,
	) );
	update_post_meta( $id, \Astrea\Core\Service\META_ICON, $s['icon'] );
	$service_ids[] = $id;
	line( "Service created: $id ({$s['title']})" );
}

// ---------------------------------------------------------------------
// 4. Cases — astrea_case
// ---------------------------------------------------------------------

$cases = array(
	array(
		'title'   => '建設業許可を初回申請で取得',
		'excerpt' => '必要書類の準備を丁寧に行い、初回申請でスムーズに建設業許可を取得したケースです。',
		'content' => '新規に建設業を営むお客様より、建設業許可の取得についてご相談をいただきました。必要書類の準備段階から丁寧にヒアリングを行い、不備のない申請書類を作成した結果、初回申請でスムーズに許可を取得することができました。',
		'related' => array( 1 ), // index into $service_ids (建設業許可申請)
		'image'   => true,
	),
	array(
		'title'   => '相続手続きを2ヶ月で完了',
		'excerpt' => '複数の相続人がいる複雑な相続手続きの中、遺産分割協議書の作成から名義変更まで2ヶ月で完了しました。',
		'content' => '複数の相続人がいらっしゃる案件で、当初は協議がまとまるか不安な状況でしたが、各相続人様のご意向を丁寧に伺いながら遺産分割協議書を作成し、名義変更の手続きまで含めて約2ヶ月で完了いたしました。',
		'related' => array( 2 ),
		'image'   => false,
	),
	array(
		'title'   => '飲食店の会社設立をサポート',
		'excerpt' => '飲食業を目指す個人事業オーナー様の会社設立をサポートし、定款作成から登記書類作成までサポートしました。',
		'content' => '個人事業として飲食店を営んでいたお客様が法人化を検討されるにあたり、定款作成から必要書類の準備、登記書類の作成まで一貫してサポートし、スムーズな法人設立を実現しました。',
		'related' => array( 0 ),
		'image'   => false,
	),
);

foreach ( $cases as $i => $c ) {
	$id = wp_insert_post( array(
		'post_type'    => \Astrea\Core\CaseStudy\POST_TYPE,
		'post_title'   => $c['title'],
		'post_excerpt' => $c['excerpt'],
		'post_content' => $c['content'],
		'post_status'  => 'publish',
		'menu_order'   => $i,
	) );
	$related_ids = array_map( function ( $idx ) use ( $service_ids ) { return $service_ids[ $idx ]; }, $c['related'] );
	update_post_meta( $id, \Astrea\Core\CaseStudy\META_RELATED_SERVICES, $related_ids );
	if ( $c['image'] ) {
		$img_id = attach_placeholder( $c['title'] . ' イメージ', 1200, 800, $id, 'case-' . ( $i + 1 ) . '-image.png' );
		set_post_thumbnail( $id, $img_id );
	}
	line( "Case created: $id ({$c['title']})" );
}

// ---------------------------------------------------------------------
// 5. Results — astrea_result
// ---------------------------------------------------------------------

$results = array(
	array( 'title' => '取引実績', 'value' => '300社以上', 'icon' => 'result-company' ),
	array( 'title' => '相談実績', 'value' => '800件以上', 'icon' => 'result-consultation' ),
	array( 'title' => '許可取得率', 'value' => '98%', 'icon' => 'result-check' ),
);

foreach ( $results as $i => $r ) {
	$id = wp_insert_post( array(
		'post_type'   => \Astrea\Core\Result\POST_TYPE,
		'post_title'  => $r['title'],
		'post_status' => 'publish',
		'menu_order'  => $i,
	) );
	update_post_meta( $id, \Astrea\Core\Result\META_VALUE, $r['value'] );
	update_post_meta( $id, \Astrea\Core\Result\META_ICON, $r['icon'] );
	line( "Result created: $id ({$r['title']} = {$r['value']})" );
}

// ---------------------------------------------------------------------
// 6. Prices — astrea_price
// ---------------------------------------------------------------------

$prices = array(
	array( 'title' => '会社設立パック', 'amount' => '60,000円〜', 'notes' => '税別', 'group' => '法人設立', 'icon' => 'company' ),
	array( 'title' => '建設業許可申請', 'amount' => '120,000円〜', 'notes' => '税別', 'group' => '許認可', 'icon' => 'permit' ),
	array( 'title' => '相続手続きサポート', 'amount' => '90,000円〜', 'notes' => '税別', 'group' => '相続', 'icon' => 'inheritance' ),
	array( 'title' => '顧問契約', 'amount' => '月額25,000円〜', 'notes' => '税別', 'group' => '顧問', 'icon' => 'contract' ),
);

foreach ( $prices as $i => $p ) {
	$id = wp_insert_post( array(
		'post_type'   => \Astrea\Core\Price\POST_TYPE,
		'post_title'  => $p['title'],
		'post_status' => 'publish',
		'menu_order'  => $i,
	) );
	update_post_meta( $id, \Astrea\Core\Price\META_AMOUNT, $p['amount'] );
	update_post_meta( $id, \Astrea\Core\Price\META_NOTES, $p['notes'] );
	update_post_meta( $id, \Astrea\Core\Price\META_GROUP, $p['group'] );
	update_post_meta( $id, \Astrea\Core\Price\META_ICON, $p['icon'] );
	line( "Price created: $id ({$p['title']} = {$p['amount']})" );
}

// ---------------------------------------------------------------------
// 7. FAQ — astrea_faq
// ---------------------------------------------------------------------

$faqs = array(
	array( 'q' => '初回相談は無料ですか？', 'a' => 'はい、初回相談は無料です。お気軽にお問い合わせください。', 'important' => true ),
	array( 'q' => '対応エリアはどこまでですか？', 'a' => '東京都内を中心に、関東近郊まで対応しております。', 'important' => true ),
	array( 'q' => '費用はどのように決まりますか？', 'a' => '案件の内容・難易度に応じてお見積りいたします。ご相談時に概算費用をお伝えします。', 'important' => true ),
	array( 'q' => '対応までどのくらいの期間がかかりますか？', 'a' => '案件内容によりますが、概ね2週間〜2ヶ月ほどで完了することが多いです。詳しくはご相談時にお伝えします。', 'important' => false ),
);

foreach ( $faqs as $i => $f ) {
	$id = wp_insert_post( array(
		'post_type'    => \Astrea\Core\Faq\POST_TYPE,
		'post_title'   => $f['q'],
		'post_content' => $f['a'],
		'post_status'  => 'publish',
		'menu_order'   => $i,
	) );
	update_post_meta( $id, \Astrea\Core\Faq\META_IS_IMPORTANT, $f['important'] ? '1' : '' );
	line( "FAQ created: $id ({$f['q']}, important=" . ( $f['important'] ? 'yes' : 'no' ) . ')' );
}

// ---------------------------------------------------------------------
// 8. Voice — astrea_voice (no meta, no featured image by product design)
// ---------------------------------------------------------------------

$voices = array(
	array( 'label' => '建設会社 経営者様', 'body' => '建設業許可の申請でお世話になりました。書類の準備から申請まで一貫してサポートいただき、初回で許可が下りました。' ),
	array( 'label' => '相続手続きご依頼者様', 'body' => '複雑な相続手続きでしたが、親身に対応していただき、無事に手続きを終えることができました。' ),
	array( 'label' => '飲食店オーナー様', 'body' => '会社設立の手続きを丁寧にサポートしていただき、スムーズに開業できました。' ),
);

foreach ( $voices as $i => $v ) {
	$id = wp_insert_post( array(
		'post_type'    => \Astrea\Core\Voice\POST_TYPE,
		'post_title'   => $v['label'],
		'post_content' => $v['body'],
		'post_status'  => 'publish',
		'menu_order'   => $i,
	) );
	line( "Voice created: $id ({$v['label']})" );
}

// ---------------------------------------------------------------------
// 9. Setup: base pages, navigation, home — via ASTREA Core's own
//    Setup functions (the exact same code the admin UI buttons call).
// ---------------------------------------------------------------------

$pages_result = \Astrea\Core\Setup\generate_pages();
line( 'generate_pages(): ' . print_r( $pages_result, true ) );

$nav_result = \Astrea\Core\Setup\generate_navigation();
line( 'generate_navigation(): ' . print_r( $nav_result, true ) );

$home_result = \Astrea\Core\Setup\generate_home_page();
line( 'generate_home_page(): ' . print_r( $home_result, true ) );

// Publish the 3 generated base pages (Setup creates them as drafts,
// matching Construction 019's own observed behavior of publishing them
// through the editor).
$generated_pages = get_posts( array(
	'post_type'      => 'page',
	'post_status'    => 'draft',
	'posts_per_page' => -1,
) );
foreach ( $generated_pages as $p ) {
	wp_update_post( array( 'ID' => $p->ID, 'post_status' => 'publish' ) );
	line( 'Published page: ' . $p->post_title . ' (' . $p->ID . ')' );
}

// ---------------------------------------------------------------------
// 10. Fictional disclosure — appended to the 事務所概要 (About) page,
//     the natural, always-reachable place for it (Order §3).
// ---------------------------------------------------------------------

$about_page = get_page_by_path( '事務所概要' );
if ( ! $about_page ) {
	// Fallback: find by title if slug differs.
	$found = get_posts( array( 'post_type' => 'page', 'title' => '事務所概要', 'posts_per_page' => 1 ) );
	$about_page = $found ? $found[0] : null;
}
if ( $about_page ) {
	$disclosure = "\n\n<!-- wp:group {\"className\":\"astrea-demo-disclosure\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"1.5rem\",\"bottom\":\"1.5rem\",\"left\":\"1.5rem\",\"right\":\"1.5rem\"}},\"border\":{\"width\":\"1px\",\"radius\":\"8px\"}},\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group astrea-demo-disclosure\" style=\"border-width:1px;border-radius:8px;padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem\">\n<!-- wp:paragraph {\"fontSize\":\"small\"} -->\n<p class=\"has-small-font-size\">このWebサイトは If Professional ASTREA のデモサイトです。掲載されている事務所・人物・サービス内容・実績・お客様の声等は、デモ用に作成された架空の情報です。実在の事務所・人物とは一切関係ありません。</p>\n<!-- /wp:paragraph -->\n</div>\n<!-- /wp:group -->\n";
	wp_update_post( array(
		'ID'           => $about_page->ID,
		'post_content' => $about_page->post_content . $disclosure,
	) );
	line( 'Fictional disclosure appended to page ' . $about_page->ID );
} else {
	line( 'WARNING: 事務所概要 page not found — disclosure not added.' );
}

line( 'DONE.' );
