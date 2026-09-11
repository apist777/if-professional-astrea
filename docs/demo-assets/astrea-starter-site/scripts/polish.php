<?php
require_once '/wordpress/wp-load.php';

// Replace the About page's default placeholder intro with real copy.
$about = get_posts( array( 'post_type' => 'page', 'title' => '事務所概要', 'posts_per_page' => 1 ) );
if ( $about ) {
	$post = $about[0];
	$intro = '当事務所は、地域の個人のお客様・小規模事業者様に寄り添う行政書士事務所です。会社設立や各種許認可申請、相続手続きなど、お客様お一人おひとりの状況を丁寧にお伺いしながら、わかりやすいご説明とスピーディーな対応を心がけております。初めての方もお気軽にご相談ください。';
	$new_content = str_replace(
		'ここに事務所の紹介文を入力してください。',
		$intro,
		$post->post_content
	);
	wp_update_post( array( 'ID' => $post->ID, 'post_content' => $new_content ) );
	echo "About page intro updated.\n";
}

// Fill in realistic business hours (weekday 9:00-18:00, weekend closed).
$profile = \Astrea\Core\OfficeProfile\get_office_profile();
$weekly  = $profile['business_hours']['weekly'];
foreach ( array( 'mon', 'tue', 'wed', 'thu', 'fri' ) as $day ) {
	$weekly[ $day ] = array( 'closed' => false, 'open' => '09:00', 'close' => '18:00' );
}
foreach ( array( 'sat', 'sun' ) as $day ) {
	$weekly[ $day ] = array( 'closed' => true, 'open' => '', 'close' => '' );
}
$input = array(
	'office_name'    => $profile['office_name'],
	'address'        => $profile['address'],
	'phone'          => $profile['phone'],
	'business_hours' => array(
		'weekly'     => $weekly,
		'exceptions' => array(),
	),
);
$sanitized = \Astrea\Core\OfficeProfile\sanitize( $input );
update_option( \Astrea\Core\OfficeProfile\OPTION_NAME, $sanitized );
echo "Business hours updated.\n";

echo "DONE.\n";
