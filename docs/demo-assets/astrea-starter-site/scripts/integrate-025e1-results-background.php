<?php
/**
 * Construction 025-E1 — set the official Results-section background photo
 * on the ASTREA official Starter Site HOME page, using ONLY the standard Cover Block
 * background-image + overlay attributes that Theme 1.0.3's
 * `astrea/home-results-teaser` pattern now exposes.
 *
 * Idempotent (Construction 028 hardening): the web JPEG is uploaded once
 * (re-runs reuse the existing attachment by filename), and the HOME-page
 * Results section's *block structure* — not any asset filename string —
 * is what determines whether an upgrade is needed:
 *
 *   - no Results Cover yet (pre-1.0.3 bare `astrea/results-list`)
 *       -> wrap it in a new with-image Cover block.
 *   - a Results Cover already exists but points at a different
 *     attachment/dimRatio (including a differently-named asset from a
 *     prior run, e.g. after Construction 027's yamada->starter rename)
 *       -> update its attrs in place, no new wrapper created.
 *   - a Results Cover already exists and already matches
 *       -> no-op.
 *   - a Results Cover is found DOUBLE-NESTED (the Construction
 *     027-discovered bug: an old cover wrapping a newer cover, both
 *     carrying the `astrea-results-photoplane` className) -> self-heal by
 *     unwrapping to the inner (correct) one before evaluating the above.
 *
 * This intentionally never keys idempotency off an asset filename
 * substring — only off the actual `core/cover` block structure, via
 * parse_blocks()/serialize_blocks(), so a future asset rename can never
 * reproduce the double-nesting bug found in Construction 027.
 *
 * Expects /images/web/astrea-demo-starter-results-background.jpg to be
 * mounted (produced by make-results-web-jpeg.php from the PNG original).
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-includes/blocks.php';

function line( $m ) { echo $m . "\n"; }

const RESULTS_CLASS = 'astrea-results-photoplane';

// --- 1. Idempotent attachment upload -------------------------------------

global $wpdb;
$filename  = 'astrea-demo-starter-results-background.jpg';
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

// Order 025-E1 §9: photo stays a subtle presence, the numbers lead. Must
// be a multiple of 10 — WordPress's Cover overlay-opacity slider has
// step=10, and its save() snaps the `has-background-dim-N` class to the
// nearest ten, so any other value permanently fails block validation.
$dim_ratio = 80;

// ---------------------------------------------------------------------
// 2. Structural helpers (block-tree based, no string/filename matching).
// ---------------------------------------------------------------------

/**
 * True if $block is a `core/cover` carrying the Results className.
 */
function astrea_e1_is_results_cover( array $block ): bool {
	return isset( $block['blockName'] ) && 'core/cover' === $block['blockName']
		&& isset( $block['attrs']['className'] )
		&& false !== strpos( $block['attrs']['className'], RESULTS_CLASS );
}

/**
 * Rebuilds a Results Cover block's saved markup to match, byte-for-byte,
 * what WordPress Core's own `core/cover` save() emits for a with-image
 * Cover — same convention already proven (zero Block validation warnings)
 * for the Hero cover in integrate-025b2-hero-and-cases.php. innerBlocks is
 * preserved as real parsed blocks (via the innerContent null-placeholder
 * mechanism serialize_block() understands) rather than a hand-built string,
 * so whatever the results-list block's own attributes are, they survive
 * untouched.
 */
function astrea_e1_rebuild_results_cover( array $block, int $attach_id, string $url, int $dim_ratio ): array {
	$block['attrs']['url']      = $url;
	$block['attrs']['id']       = $attach_id;
	$block['attrs']['dimRatio'] = $dim_ratio;
	if ( ! isset( $block['attrs']['overlayColor'] ) ) {
		$block['attrs']['overlayColor'] = 'contrast';
	}
	if ( ! isset( $block['attrs']['align'] ) ) {
		$block['attrs']['align'] = 'full';
	}
	if ( ! isset( $block['attrs']['className'] ) ) {
		$block['attrs']['className'] = RESULTS_CLASS;
	}

	$wrapper_classes = 'wp-block-cover alignfull ' . trim( $block['attrs']['className'] );
	$img_html        = '<img class="wp-block-cover__image-background wp-image-' . $attach_id . '" alt="" src="' . esc_url( $url ) . '" data-object-fit="cover"/>';
	$span_html       = '<span aria-hidden="true" class="wp-block-cover__background has-' . $block['attrs']['overlayColor'] . '-background-color has-background-dim-' . $dim_ratio . ' has-background-dim"></span>';

	$before = '<div class="' . esc_attr( $wrapper_classes ) . '" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">'
		. $img_html . $span_html . "\n"
		. '<div class="wp-block-cover__inner-container">';
	$after  = '</div>' . "\n" . '</div>';

	// One inner slot (the astrea/results-list block); serialize_block()
	// replaces the `null` entry with serialize_blocks($block['innerBlocks']).
	$block['innerContent'] = array( $before, null, $after );
	$block['innerHTML']    = $before . $after;

	return $block;
}

/**
 * Walks the block tree looking for a Results Cover. Self-heals the
 * Construction 027 double-nesting bug on sight (a Results Cover whose own
 * innerBlocks contains ANOTHER Results Cover) by unwrapping to the inner
 * one. Then normalizes attrs to the target attachment/dimRatio — a no-op
 * rewrite if they already match.
 *
 * Returns true once a Results Cover was found and normalized anywhere in
 * $blocks (searched recursively), false if none exists yet.
 */
function astrea_e1_find_and_normalize( array &$blocks, int $attach_id, string $url, int $dim_ratio, array &$log ): bool {
	foreach ( $blocks as &$block ) {
		if ( astrea_e1_is_results_cover( $block ) ) {
			// Self-heal: unwrap double-nesting first.
			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as $inner ) {
					if ( astrea_e1_is_results_cover( $inner ) ) {
						$block   = $inner;
						$log[]   = 'Self-healed a double-nested Results Cover (unwrapped to the inner block).';
						break;
					}
				}
			}

			$already_correct = isset( $block['attrs']['url'], $block['attrs']['id'], $block['attrs']['dimRatio'] )
				&& $block['attrs']['url'] === $url
				&& (int) $block['attrs']['id'] === $attach_id
				&& (int) $block['attrs']['dimRatio'] === $dim_ratio;

			if ( $already_correct ) {
				$log[] = 'Results Cover already points at the current attachment/dimRatio — no-op.';
			} else {
				$block = astrea_e1_rebuild_results_cover( $block, $attach_id, $url, $dim_ratio );
				$log[] = 'Results Cover normalized to the current attachment/dimRatio.';
			}
			return true;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			if ( astrea_e1_find_and_normalize( $block['innerBlocks'], $attach_id, $url, $dim_ratio, $log ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Pre-1.0.3 fallback: no Results Cover exists anywhere yet, only the bare
 * `astrea/results-list` block (checked at any nesting depth, matching
 * where the pattern places it). Wraps it in a brand-new Results Cover,
 * in place, using the real parsed block object as the single innerBlock.
 */
function astrea_e1_wrap_bare_results_list( array &$blocks, int $attach_id, string $url, int $dim_ratio, array &$log ): bool {
	foreach ( $blocks as &$block ) {
		if ( isset( $block['blockName'] ) && 'astrea/results-list' === $block['blockName'] ) {
			$cover = array(
				'blockName'    => 'core/cover',
				'attrs'        => array(
					'url'          => $url,
					'id'           => $attach_id,
					'dimRatio'     => $dim_ratio,
					'overlayColor' => 'contrast',
					'align'        => 'full',
					'className'    => RESULTS_CLASS,
					'style'        => array( 'spacing' => array( 'padding' => array( 'top' => '0', 'bottom' => '0', 'left' => '0', 'right' => '0' ) ) ),
				),
				'innerBlocks'  => array( $block ),
				'innerHTML'    => '',
				'innerContent' => array(),
			);
			$block = astrea_e1_rebuild_results_cover( $cover, $attach_id, $url, $dim_ratio );
			$log[] = 'Wrapped the pre-1.0.3 bare astrea/results-list block in a new Results Cover.';
			return true;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			if ( astrea_e1_wrap_bare_results_list( $block['innerBlocks'], $attach_id, $url, $dim_ratio, $log ) ) {
				return true;
			}
		}
	}
	return false;
}

// ---------------------------------------------------------------------
// 3. Apply.
// ---------------------------------------------------------------------

$home       = (int) get_option( 'page_on_front' );
$home_post  = get_post( $home );
$blocks     = parse_blocks( $home_post->post_content );
$log        = array();

$handled = astrea_e1_find_and_normalize( $blocks, $attach_id, $url, $dim_ratio, $log );
if ( ! $handled ) {
	$handled = astrea_e1_wrap_bare_results_list( $blocks, $attach_id, $url, $dim_ratio, $log );
}

if ( ! $handled ) {
	line( 'ERROR: no Results Cover and no bare astrea/results-list block found on HOME — cannot proceed.' );
	exit( 1 );
}

foreach ( $log as $entry ) {
	line( $entry );
}

$new_content = serialize_blocks( $blocks );
if ( trim( $new_content ) !== trim( $home_post->post_content ) ) {
	wp_update_post( array( 'ID' => $home, 'post_content' => $new_content ) );
	line( "Results background set: attachment $attach_id, dimRatio $dim_ratio." );
} else {
	line( 'HOME content unchanged — skipped wp_update_post (avoids a needless revision).' );
}
