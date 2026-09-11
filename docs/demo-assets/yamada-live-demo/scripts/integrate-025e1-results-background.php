<?php
/**
 * Construction 025-E1 — set the official Results-section background photo
 * on the Yamada Demo HOME page, using ONLY the standard Cover Block
 * background-image + overlay attributes that Theme 1.0.3's
 * `astrea/home-results-teaser` pattern now exposes.
 *
 * Idempotent: the web JPEG is uploaded once (re-runs reuse the existing
 * attachment by filename), and the HOME-page Results block is upgraded
 * from either its pre-1.0.3 unwrapped form OR its 1.0.3 no-image wrapped
 * form to the with-image form. Re-running after the image is already set
 * is a no-op.
 *
 * Expects /images/web/astrea-demo-yamada-results-background.jpg to be
 * mounted (produced by make-results-web-jpeg.php from the PNG original).
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function line( $m ) { echo $m . "\n"; }

// --- 1. Idempotent attachment upload -------------------------------------

global $wpdb;
$filename  = 'astrea-demo-yamada-results-background.jpg';
$like      = '%' . $wpdb->esc_like( $filename ) . '%';
$attach_id = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
	$like
) );

if ( ! $attach_id ) {
	$bits   = file_get_contents( '/images/web/' . $filename );
	$upload = wp_upload_bits( $filename, null, $bits );
	if ( $upload['error'] ) {
		line( 'Upload error: ' . $upload['error'] );
		exit( 1 );
	}
	$attach_id = wp_insert_attachment( array(
		'post_mime_type' => 'image/jpeg',
		'post_title'     => '実績セクション背景（オフィス）',
		'post_status'    => 'inherit',
	), $upload['file'] );
	$meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	wp_update_attachment_metadata( $attach_id, $meta );
	update_post_meta( $attach_id, '_wp_attachment_image_alt', '法律事務所のオフィスデスクと都市の眺望' );
	line( "Uploaded attachment $attach_id." );
} else {
	line( "Reusing attachment $attach_id." );
}

$url = wp_get_attachment_url( $attach_id );

// --- 2. Upgrade the HOME-page Results block -----------------------------

$home    = (int) get_option( 'page_on_front' );
$content = get_post( $home )->post_content;

if ( false !== strpos( $content, 'astrea-demo-yamada-results-background' ) ) {
	line( 'Results background already set — nothing to do.' );
	exit( 0 );
}

$inner       = "\n<!-- wp:astrea/results-list {\"heading\":\"実績\"} /-->\n";
// Order 025-E1 §9: photo stays a subtle presence, the numbers lead. Must
// be a multiple of 10 — WordPress's Cover overlay-opacity slider has
// step=10, and its save() snaps the `has-background-dim-N` class to the
// nearest ten, so any other value permanently fails block validation.
$dim_ratio   = 80;

$with_image  = '<!-- wp:cover {"url":"' . $url . '","id":' . $attach_id . ',"dimRatio":' . $dim_ratio . ',"overlayColor":"contrast","align":"full","className":"astrea-results-photoplane","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}}} -->' . "\n"
	. '<div class="wp-block-cover alignfull astrea-results-photoplane" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><img class="wp-block-cover__image-background wp-image-' . $attach_id . '" alt="" src="' . $url . '" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-' . $dim_ratio . ' has-background-dim"></span>' . "\n"
	. '<div class="wp-block-cover__inner-container">' . $inner . '</div>' . "\n"
	. '</div>' . "\n"
	. '<!-- /wp:cover -->';

// (a) 1.0.3 no-image wrapped form.
$no_image_wrapped = '<!-- wp:cover {"dimRatio":100,"overlayColor":"contrast","align":"full","className":"astrea-results-photoplane","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}}} -->' . "\n"
	. '<div class="wp-block-cover alignfull astrea-results-photoplane" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">' . "\n"
	. '<span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span>' . "\n"
	. '<div class="wp-block-cover__inner-container">' . $inner . '</div>' . "\n"
	. '</div>' . "\n"
	. '<!-- /wp:cover -->';

// (b) pre-1.0.3 unwrapped form.
$unwrapped = '<!-- wp:astrea/results-list {"heading":"実績"} /-->';

if ( false !== strpos( $content, $no_image_wrapped ) ) {
	$content = str_replace( $no_image_wrapped, $with_image, $content );
	line( 'Upgraded 1.0.3 no-image wrapped Results block -> with-image.' );
} elseif ( false !== strpos( $content, $unwrapped ) ) {
	$content = str_replace( $unwrapped, $with_image, $content );
	line( 'Wrapped pre-1.0.3 unwrapped Results block -> with-image.' );
} else {
	line( 'ERROR: could not locate a known Results block form on HOME.' );
	exit( 1 );
}

wp_update_post( array( 'ID' => $home, 'post_content' => $content ) );
line( "Results background set: attachment $attach_id, dimRatio $dim_ratio." );
