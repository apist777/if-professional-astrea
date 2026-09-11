<?php
require_once '/wordpress/wp-load.php';

function make_placeholder_ascii( string $ascii_label, int $w, int $h, string $hex = '2c3e50' ): string {
	$img = imagecreatetruecolor( $w, $h );
	list($r, $g, $b) = sscanf( $hex, "%02x%02x%02x" );
	$bg = imagecolorallocate( $img, $r, $g, $b );
	imagefill( $img, 0, 0, $bg );
	$white = imagecolorallocate( $img, 255, 255, 255 );
	$font  = 5;
	$lines = array( 'PLACEHOLDER PHOTO', $ascii_label, $w . 'x' . $h );
	$y     = (int) ( $h / 2 ) - 30;
	foreach ( $lines as $line ) {
		$tw = imagefontwidth( $font ) * strlen( $line );
		imagestring( $img, $font, max( 10, (int) ( ( $w - $tw ) / 2 ) ), $y, $line, $white );
		$y += imagefontheight( $font ) + 8;
	}
	$path = '/tmp/ph-' . md5( $ascii_label . $w . $h ) . '.png';
	imagepng( $img, $path );
	imagedestroy( $img );
	return $path;
}

function replace_attachment_file( int $attach_id, string $new_file_path ) {
	$file = get_attached_file( $attach_id );
	copy( $new_file_path, $file );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	// Regenerate all registered sizes from the new source file.
	$meta = wp_generate_attachment_metadata( $attach_id, $file );
	wp_update_attachment_metadata( $attach_id, $meta );
}

// Professional photo (attachment ID 6 per earlier DB check; re-derive safely by thumbnail meta instead of hardcoding).
$professional = get_posts( array( 'post_type' => 'astrea_professional', 'posts_per_page' => 1 ) );
if ( $professional ) {
	$photo_id = get_post_thumbnail_id( $professional[0]->ID );
	if ( $photo_id ) {
		$new = make_placeholder_ascii( 'Representative Portrait', 900, 1200 );
		replace_attachment_file( $photo_id, $new );
		echo "Professional photo regenerated ($photo_id)\n";
	}
}

// Case #1 image.
$cases = get_posts( array( 'post_type' => 'astrea_case', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
if ( isset( $cases[0] ) ) {
	$img_id = get_post_thumbnail_id( $cases[0]->ID );
	if ( $img_id ) {
		$new = make_placeholder_ascii( 'Case Study Photo', 1200, 800 );
		replace_attachment_file( $img_id, $new );
		echo "Case #1 image regenerated ($img_id)\n";
	}
}

echo "DONE.\n";
