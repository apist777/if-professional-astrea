<?php
/**
 * Construction 025-E1 — produce the web-optimized JPEG for the Results
 * section background from the PNG original.
 *
 * Same non-destructive convention as Construction 023-B / 025-B2: reads
 * the PNG, writes a NEW quality-85 JPEG into images/web/, never touches
 * the original. NOT cropped — the Cover Block's own object-fit:cover
 * frames it responsively at render time (same as the Hero photo).
 *
 * Expects /images (docs/demo-assets/yamada-live-demo/images) mounted.
 */

require_once '/wordpress/wp-load.php';

function line( $m ) { echo $m . "\n"; }

$src_png  = '/images/astrea-demo-yamada-results-background.png';
$dest_jpg = '/images/web/astrea-demo-yamada-results-background.jpg';

$src = imagecreatefrompng( $src_png );
if ( ! $src ) {
	line( 'ERROR: could not read ' . $src_png );
	exit( 1 );
}
$w = imagesx( $src );
$h = imagesy( $src );
imagejpeg( $src, $dest_jpg, 85 );
imagedestroy( $src );

line( sprintf(
	'Results web JPEG: source %dx%d (%d bytes) -> %s (%d bytes, q85, uncropped)',
	$w,
	$h,
	filesize( $src_png ),
	basename( $dest_jpg ),
	filesize( $dest_jpg )
) );
