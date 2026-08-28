<?php
/**
 * Office Profile — SNS links Dynamic Block (Construction Order 011).
 *
 * Office Profile has stored a variable-length list of SNS links since
 * Construction Order 002 (`sns_links`, each `{label, url}`), but had no
 * Theme display path — same structural reason as office-hours-block.php:
 * Block Bindings cannot express a variable-length list. Kept as a separate
 * block from astrea/office-hours (rather than one combined "Office info"
 * block) because the two have different data shapes, different display
 * conditions, and are reused independently (Construction Order 011 kickoff
 * FIX #3).
 *
 * URLs are already restricted to http/https at save time
 * (office-profile.php's sanitize_sns_links(), Construction Order 011
 * Security Audit item 13 — `javascript:`/`data:` cannot be stored at all),
 * but `esc_url()` is still applied here at render time as defence in depth
 * and because it is simply the correct function for an href attribute
 * regardless of what upstream validation already did.
 *
 * Same `heading` / `emptyMessage` convention as the other Core Dynamic
 * Blocks (Decision 028): zero links self-hides completely by default.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\OfficeProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'init', __NAMESPACE__ . '\\register_sns_block' );

/**
 * Registers the astrea/office-sns Dynamic Block.
 *
 * @return void
 */
function register_sns_block() {
	register_block_type(
		'astrea/office-sns',
		array(
			'render_callback' => __NAMESPACE__ . '\\render_sns_block',
			'attributes'      => array(
				'heading'      => array(
					'type'    => 'string',
					'default' => '',
				),
				'emptyMessage' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		)
	);
}

/**
 * Renders the SNS link list as plain semantic HTML.
 *
 * @param array $attributes Block attributes (`heading`, `emptyMessage`).
 * @return string
 */
function render_sns_block( array $attributes = array() ): string {
	$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
	$empty_message = isset( $attributes['emptyMessage'] ) ? (string) $attributes['emptyMessage'] : '';

	$profile = get_office_profile();
	$links   = $profile['sns_links'];

	if ( empty( $links ) ) {
		if ( '' === $empty_message ) {
			return '';
		}

		return '<p class="wp-block-astrea-office-sns-empty">' . esc_html( $empty_message ) . '</p>';
	}

	$items = '';
	foreach ( $links as $link ) {
		$url = isset( $link['url'] ) ? esc_url( (string) $link['url'], array( 'http', 'https' ) ) : '';
		if ( '' === $url ) {
			continue;
		}

		$label = isset( $link['label'] ) ? (string) $link['label'] : '';
		$text  = '' !== $label ? $label : $url;

		$items .= '<li><a href="' . $url . '">' . esc_html( $text ) . '</a></li>';
	}

	if ( '' === $items ) {
		if ( '' === $empty_message ) {
			return '';
		}

		return '<p class="wp-block-astrea-office-sns-empty">' . esc_html( $empty_message ) . '</p>';
	}

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . '<ul class="wp-block-astrea-office-sns">' . $items . '</ul>';
}
