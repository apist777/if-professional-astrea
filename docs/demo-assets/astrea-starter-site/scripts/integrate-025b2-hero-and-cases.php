<?php
/**
 * Construction 025-B2 — Hero + Case #2 + Case #3 final asset integration.
 *
 * Integrates the three newly-supplied assets (Hero office photo, Case #2
 * inheritance photo, Case #3 restaurant-incorporation photo) into the ASTREA
 * official Starter Site build using ONLY existing ASTREA Theme/Core standard-WordPress
 * features:
 *   - Hero:  the astrea-hero-photoplane `core/cover` block's standard
 *            background-image + overlay-dim controls (same two controls a
 *            user would set via the Block/Site Editor's Cover block
 *            sidebar).
 *   - Case:  the astrea_case CPT's standard Featured Image, exactly the
 *            same mechanism already used for Case #1 and the Representative
 *            portrait.
 *
 * No Theme/Core changes. No demo-specific CSS/template/conditional logic.
 * Idempotent: safe to re-run against the same environment (existing
 * matching attachments are reused by filename rather than duplicated),
 * and safe to run fresh against a brand-new clean-rebuild environment
 * (attachment IDs are looked up at runtime, never hardcoded).
 *
 * Expects, mounted at /images and /images/web respectively:
 *   /images/web/astrea-demo-starter-hero-office.jpg
 *   /images/web/astrea-demo-starter-case-02-inheritance.jpg
 *   /images/web/astrea-demo-starter-case-03-restaurant-incorporation.jpg
 * (Web-optimized JPEGs; see make-web-jpegs.php for how these are derived
 * from the PNG originals in /images.)
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-includes/blocks.php';

function line( $m ) { echo $m . "\n"; }

// ---------------------------------------------------------------------
// Top-level (hoisted) function declarations. Declared unconditionally at
// the top of the file so PHP makes them available regardless of which
// branch of the script below happens to run first (a conditional inline
// `function` declaration is only defined once that line of code actually
// executes — placing them here avoids that class of bug entirely).
// ---------------------------------------------------------------------

function find_existing_attachment_by_filename( string $filename ): int {
	global $wpdb;
	$like = '%' . $wpdb->esc_like( $filename ) . '%';
	$id   = $wpdb->get_var( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
		$like
	) );
	return $id ? (int) $id : 0;
}

function upload_and_attach_idempotent( string $jpeg_path, string $title, string $alt, string $filename, int $parent_post_id = 0 ): int {
	$existing = find_existing_attachment_by_filename( $filename );
	if ( $existing ) {
		line( "Reusing existing attachment $existing for $filename (no duplicate uploaded)." );
		return $existing;
	}

	$bits   = file_get_contents( $jpeg_path );
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
	$meta      = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	wp_update_attachment_metadata( $attach_id, $meta );
	update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt );
	line( "Uploaded new attachment $attach_id for $filename." );
	return $attach_id;
}

function update_cover_block_recursive( array &$block, int $attach_id, string $url, bool &$found ) {
	if ( isset( $block['blockName'] ) && 'core/cover' === $block['blockName']
		&& isset( $block['attrs']['className'] ) && false !== strpos( $block['attrs']['className'], 'astrea-hero-photoplane' ) ) {
		$block['attrs']['url']      = $url;
		$block['attrs']['id']       = $attach_id;
		$block['attrs']['dimRatio'] = 20; // Standard Cover Block overlay slider, lowered from 100 so the photo is visible.
		$found                      = true;
	}
	if ( ! empty( $block['innerBlocks'] ) ) {
		foreach ( $block['innerBlocks'] as &$inner ) {
			update_cover_block_recursive( $inner, $attach_id, $url, $found );
		}
	}
}

/**
 * Rebuilds the saved HTML of an astrea-hero-photoplane Cover block so it
 * matches, token-for-token, what WordPress Core's own `core/cover`
 * `save()` emits for the same attributes. Anything else trips the Block
 * Editor's "Block contains unexpected or invalid content" validation on
 * every future edit.
 *
 * Construction 025-E1H — the earlier version put the dim/overlay classes
 * on the <img> and dropped the <span> entirely, and never added the
 * `wp-image-{id}` class. WordPress's real save() output is:
 *
 *   <div class="wp-block-cover {className}" style="min-height:{n}{unit}">
 *     <img class="wp-block-cover__image-background wp-image-{id}" alt="" src="{url}" data-object-fit="cover"/>
 *     <span aria-hidden="true" class="wp-block-cover__background has-{color}-background-color has-background-dim-{ratio} has-background-dim"></span>
 *     <div class="wp-block-cover__inner-container"></div>
 *   </div>
 *
 * i.e. the <img> carries ONLY `wp-block-cover__image-background wp-image-{id}`,
 * the colour + dim classes live on a real <span>, and the dim classes are
 * ordered `has-background-dim-{ratio}` then `has-background-dim`.
 *
 * @param array $block Parsed block (attrs already updated with url/id/dimRatio).
 * @return array
 */
function rebuild_cover_inner_html( array $block ): array {
	$attrs           = $block['attrs'];
	$url             = esc_url( $attrs['url'] );
	$attach_id       = isset( $attrs['id'] ) ? (int) $attrs['id'] : 0;
	$dim_ratio       = (int) $attrs['dimRatio'];
	$overlay_color   = isset( $attrs['overlayColor'] ) ? $attrs['overlayColor'] : '';
	$min_height      = isset( $attrs['minHeight'] ) ? $attrs['minHeight'] : '';
	$min_height_unit = isset( $attrs['minHeightUnit'] ) ? $attrs['minHeightUnit'] : 'px';
	$class_name      = isset( $attrs['className'] ) ? $attrs['className'] : '';

	$wrapper_classes = 'wp-block-cover ' . trim( $class_name );
	$style           = '' !== (string) $min_height ? 'min-height:' . $min_height . $min_height_unit : '';

	// <img> — exactly the two classes WordPress Core's Cover save() emits.
	$img_classes = 'wp-block-cover__image-background';
	if ( $attach_id ) {
		$img_classes .= ' wp-image-' . $attach_id;
	}

	// <span> — the overlay colour + dim classes belong here, not on <img>.
	$span_classes = 'wp-block-cover__background';
	if ( $overlay_color ) {
		$span_classes .= ' has-' . $overlay_color . '-background-color';
	}
	if ( $dim_ratio > 0 ) {
		$span_classes .= ' has-background-dim-' . $dim_ratio . ' has-background-dim';
	}

	$style_attr = '' !== $style ? ' style="' . esc_attr( $style ) . '"' : '';

	$inner_html = '<div class="' . esc_attr( $wrapper_classes ) . '"' . $style_attr . '>'
		. '<img class="' . esc_attr( $img_classes ) . '" alt="" src="' . $url . '" data-object-fit="cover"/>'
		. '<span aria-hidden="true" class="' . esc_attr( $span_classes ) . '"></span>'
		. '<div class="wp-block-cover__inner-container"></div>'
		. '</div>';

	$block['innerHTML']    = $inner_html;
	$block['innerContent'] = array( $inner_html );
	return $block;
}

function splice_cover_rebuild( array &$blocks ) {
	foreach ( $blocks as &$block ) {
		if ( isset( $block['blockName'] ) && 'core/cover' === $block['blockName']
			&& isset( $block['attrs']['className'] ) && false !== strpos( $block['attrs']['className'], 'astrea-hero-photoplane' )
			&& isset( $block['attrs']['url'] ) ) {
			$block = rebuild_cover_inner_html( $block );
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			splice_cover_rebuild( $block['innerBlocks'] );
		}
	}
}

// ---------------------------------------------------------------------
// 1. Upload (or reuse) the 3 attachments.
// ---------------------------------------------------------------------

$hero_attach_id = upload_and_attach_idempotent(
	'/images/web/astrea-demo-starter-hero-office.jpg',
	'Hero オフィス風景（正式版）',
	'高層ビルのオフィスから見た都市の眺望とデスク',
	'astrea-demo-starter-hero-office.jpg'
);

$case2_attach_id = upload_and_attach_idempotent(
	'/images/web/astrea-demo-starter-case-02-inheritance.jpg',
	'対応事例#2 相続手続きイメージ（正式版）',
	'相続手続きに関する打ち合わせのイメージ',
	'astrea-demo-starter-case-02-inheritance.jpg'
);

$case3_attach_id = upload_and_attach_idempotent(
	'/images/web/astrea-demo-starter-case-03-restaurant-incorporation.jpg',
	'対応事例#3 飲食店の会社設立イメージ（正式版）',
	'飲食店の会社設立に関する打ち合わせのイメージ',
	'astrea-demo-starter-case-03-restaurant-incorporation.jpg'
);

if ( ! $hero_attach_id || ! $case2_attach_id || ! $case3_attach_id ) {
	line( 'ERROR: one or more attachments failed to upload/resolve. Aborting.' );
	exit( 1 );
}

// ---------------------------------------------------------------------
// 2. Hero: update the HOME page's Cover block (background image + overlay).
//    Done via WordPress's own parse_blocks()/serialize_blocks() against the
//    HOME page's real post_content — not a template hardcode.
// ---------------------------------------------------------------------

$home = (int) get_option( 'page_on_front' );
if ( ! $home ) {
	line( 'ERROR: no static front page set.' );
	exit( 1 );
}

$home_post = get_post( $home );
$blocks    = parse_blocks( $home_post->post_content );
$hero_url  = wp_get_attachment_url( $hero_attach_id );

$found = false;
foreach ( $blocks as &$block ) {
	update_cover_block_recursive( $block, $hero_attach_id, $hero_url, $found );
}
unset( $block );

if ( ! $found ) {
	line( 'ERROR: astrea-hero-photoplane cover block not found in HOME content.' );
	exit( 1 );
}

splice_cover_rebuild( $blocks );
$new_content = serialize_blocks( $blocks );

wp_update_post( array( 'ID' => $home, 'post_content' => $new_content ) );
line( "Hero cover block updated on HOME page $home (image id $hero_attach_id, dimRatio 20)." );

// ---------------------------------------------------------------------
// 3. Case #2 / #3: Featured Image (same standard mechanism as Case #1).
//    Cases are matched by their known demo titles rather than raw menu
//    order, so this stays correct even if post IDs differ between
//    environments.
// ---------------------------------------------------------------------

$cases = get_posts( array( 'post_type' => 'astrea_case', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );

$case2_title_fragment = '相続';
$case3_title_fragment = '飲食店';

foreach ( $cases as $case ) {
	if ( false !== strpos( $case->post_title, $case2_title_fragment ) ) {
		set_post_thumbnail( $case->ID, $case2_attach_id );
		line( "Case #2 ({$case->post_title}) featured image set to $case2_attach_id" );
	} elseif ( false !== strpos( $case->post_title, $case3_title_fragment ) ) {
		set_post_thumbnail( $case->ID, $case3_attach_id );
		line( "Case #3 ({$case->post_title}) featured image set to $case3_attach_id" );
	}
}

line( 'DONE.' );
