<?php
require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function line( $m ) { echo $m . "\n"; }

/**
 * Loads a PNG, center-crops (non-destructively -- reads only, writes a
 * NEW file) to the given target aspect ratio, and re-saves as JPEG at
 * quality 85 for a practical, WordPress-compatible web delivery size.
 * The original PNG passed in is never modified.
 */
function make_web_jpeg( string $src_png, string $dest_jpeg, float $target_ratio, int $quality = 85 ): array {
	$src = imagecreatefrompng( $src_png );
	if ( ! $src ) {
		return array( 'ok' => false, 'error' => 'decode failed' );
	}
	$w = imagesx( $src );
	$h = imagesy( $src );
	$src_ratio = $w / $h;

	if ( $src_ratio > $target_ratio ) {
		// Source is relatively wider than target -> crop left/right.
		$new_w = (int) round( $h * $target_ratio );
		$new_h = $h;
		$crop_x = (int) round( ( $w - $new_w ) / 2 );
		$crop_y = 0;
	} else {
		// Source is relatively taller than target -> crop top/bottom.
		$new_w = $w;
		$new_h = (int) round( $w / $target_ratio );
		$crop_x = 0;
		$crop_y = (int) round( ( $h - $new_h ) / 2 );
	}

	$dst = imagecreatetruecolor( $new_w, $new_h );
	imagecopy( $dst, $src, 0, 0, $crop_x, $crop_y, $new_w, $new_h );

	// Flatten onto white in case of any alpha (source is RGB per earlier check, this is defensive).
	$bg = imagecreatetruecolor( $new_w, $new_h );
	$white = imagecolorallocate( $bg, 255, 255, 255 );
	imagefill( $bg, 0, 0, $white );
	imagecopy( $bg, $dst, 0, 0, 0, 0, $new_w, $new_h );

	imagejpeg( $bg, $dest_jpeg, $quality );
	imagedestroy( $src );
	imagedestroy( $dst );
	imagedestroy( $bg );

	return array(
		'ok'          => true,
		'source_size' => array( $w, $h ),
		'cropped_to'  => array( $new_w, $new_h ),
		'crop_offset' => array( $crop_x, $crop_y ),
		'file_size'   => filesize( $dest_jpeg ),
	);
}

// ---------------------------------------------------------------------
// 1. Build web-optimized JPEGs (non-destructive; PNG originals untouched).
// ---------------------------------------------------------------------

$result1 = make_web_jpeg(
	'/images/astrea-demo-starter-professional-portrait.png',
	'/images/web/astrea-demo-starter-professional-portrait.jpg',
	1.7,
	85
);
line( 'Professional portrait web JPEG: ' . print_r( $result1, true ) );

$result2 = make_web_jpeg(
	'/images/astrea-demo-starter-case-01-construction-permit.png',
	'/images/web/astrea-demo-starter-case-01-construction-permit.jpg',
	2.0,
	85
);
line( 'Case #1 web JPEG: ' . print_r( $result2, true ) );

// ---------------------------------------------------------------------
// 2. Upload both web JPEGs as new WordPress attachments and switch the
//    Professional / Case #1 Featured Image to them (WordPress's own
//    upload/attachment API -- no direct DB row editing).
// ---------------------------------------------------------------------

/**
 * Construction 028 hardening: idempotent by filename, matching the same
 * pattern already used by integrate-025b2-hero-and-cases.php and
 * integrate-025e1-results-background.php. Without this lookup, re-running
 * the pipeline against an already-built site re-uploaded a fresh duplicate
 * attachment on every run (attachment growth), which is exactly the class
 * of bug this Construction hardens against.
 */
function find_existing_attachment_by_filename( string $filename ): int {
	global $wpdb;
	$like = '%' . $wpdb->esc_like( $filename ) . '%';
	$id   = $wpdb->get_var( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
		$like
	) );
	return $id ? (int) $id : 0;
}

function upload_and_attach( string $jpeg_path, string $title, string $alt, string $filename, int $parent_post_id ): int {
	$existing = find_existing_attachment_by_filename( $filename );
	if ( $existing ) {
		line( "Reusing existing attachment $existing for $filename (no duplicate uploaded)." );
		return $existing;
	}

	$bits = file_get_contents( $jpeg_path );
	$upload = wp_upload_bits( $filename, null, $bits );
	if ( $upload['error'] ) {
		line( 'Upload error: ' . $upload['error'] );
		return 0;
	}
	$attachment = array(
		'post_mime_type' => 'image/jpeg',
		'post_title'     => $title,
		'post_status'    => 'inherit',
		'post_parent'    => $parent_post_id,
	);
	$attach_id = wp_insert_attachment( $attachment, $upload['file'], $parent_post_id );
	$meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	wp_update_attachment_metadata( $attach_id, $meta );
	update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt );
	line( "Uploaded new attachment $attach_id for $filename." );
	return $attach_id;
}

$professional = get_posts( array( 'post_type' => 'astrea_professional', 'posts_per_page' => 1 ) );
if ( $professional ) {
	$old_thumb_id = get_post_thumbnail_id( $professional[0]->ID );
	$new_id = upload_and_attach(
		'/images/web/astrea-demo-starter-professional-portrait.jpg',
		'代表者 伊吹文人 ポートレート（正式版）',
		'代表 伊吹文人（行政書士）のポートレート',
		'astrea-demo-starter-professional-portrait.jpg',
		$professional[0]->ID
	);
	if ( $new_id ) {
		set_post_thumbnail( $professional[0]->ID, $new_id );
		line( "Professional thumbnail switched: old={$old_thumb_id} new={$new_id}" );
	}
}

$cases = get_posts( array( 'post_type' => 'astrea_case', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
if ( isset( $cases[0] ) ) {
	$old_thumb_id = get_post_thumbnail_id( $cases[0]->ID );
	$new_id = upload_and_attach(
		'/images/web/astrea-demo-starter-case-01-construction-permit.jpg',
		'対応事例#1 建設業許可申請イメージ（正式版）',
		'建設業許可申請に関する書類・打ち合わせのイメージ',
		'astrea-demo-starter-case-01-construction-permit.jpg',
		$cases[0]->ID
	);
	if ( $new_id ) {
		set_post_thumbnail( $cases[0]->ID, $new_id );
		line( "Case #1 thumbnail switched: old={$old_thumb_id} new={$new_id}" );
	}
}

line( 'DONE.' );
