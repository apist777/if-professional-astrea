<?php
/**
 * SEO — Options API settings (Construction Order 006).
 *
 * Only two settings, both already authorized by existing spec text before
 * this Construction Order: a site-wide OGP fallback image (02仕様書§17
 * "サイト標準OGP画像を設定可能とする") and a Search Console HTML-tag
 * verification code (Decision 009). No other SEO input fields are added —
 * meta description and Organization/Person JSON-LD are generated entirely
 * from data that already exists (Office Profile, Professional Profile,
 * post content), per the order's explicit "過剰なSEO設定を要求しない".
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const SETTINGS_OPTION = 'astrea_core_seo_settings';

/**
 * Returns SEO settings merged with defaults.
 *
 * @return array
 */
function get_seo_settings(): array {
	$defaults = array(
		'og_image_id'                 => 0,
		'search_console_verification' => '',
	);

	$stored = get_option( SETTINGS_OPTION, array() );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	return array_merge( $defaults, $stored );
}

/**
 * Sanitizes SEO settings (Settings API sanitize_callback).
 *
 * @param mixed $input Raw input.
 * @return array
 */
function sanitize_settings( $input ): array {
	$input = is_array( $input ) ? $input : array();

	$og_image_id = isset( $input['og_image_id'] ) ? absint( $input['og_image_id'] ) : 0;
	if ( $og_image_id > 0 && 'attachment' !== get_post_type( $og_image_id ) ) {
		$og_image_id = 0; // Guard against a stale/invalid/deleted attachment reference.
	}

	$verification = isset( $input['search_console_verification'] ) ? sanitize_text_field( $input['search_console_verification'] ) : '';

	// Google's HTML-tag verification codes are base64-like strings. Reject
	// anything containing characters that would be unsafe or nonsensical in
	// this context (quotes, angle brackets, whitespace) rather than trying
	// to guess Google's exact format.
	if ( '' !== $verification && ! preg_match( '/^[A-Za-z0-9+\/=_-]+$/', $verification ) ) {
		add_settings_error(
			SETTINGS_OPTION,
			'astrea_seo_invalid_verification',
			__( 'Search Consoleの確認コードの形式が正しくありません。Google Search Consoleの「HTMLタグ」確認方法で表示される content="..." の値のみを貼り付けてください。', 'astrea-core' )
		);
		$verification = '';
	}

	return array(
		'og_image_id'                 => $og_image_id,
		'search_console_verification' => $verification,
	);
}
