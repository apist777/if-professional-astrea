<?php
/**
 * VOICE（お客様の声） — Dynamic Block for HOME/teaser display (Construction
 * Order 010).
 *
 * Same `heading` / `emptyMessage` / self-hide convention as
 * `astrea/price-list` / `astrea/faq-list` / `astrea/case-list` (Decision
 * 028). Each entry is marked up as `<blockquote>`/`<cite>` — genuinely a
 * quotation from a real person, not decorative use of the element (per
 * Construction Order 010's explicit accessibility instruction not to
 * reach for blockquote purely for visual effect).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Voice;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'init', __NAMESPACE__ . '\\register_list_block' );

/**
 * Registers the astrea/voice-list Dynamic Block.
 *
 * @return void
 */
function register_list_block() {
	register_block_type(
		'astrea/voice-list',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_voice_list_block',
			'editor_script_handles' => array( \Astrea\Core\EditorBlocks\SCRIPT_HANDLE ),
			'attributes'            => array(
				'limit'        => array(
					'type'    => 'number',
					'default' => 0,
				),
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
 * Renders a VOICE list as plain semantic HTML.
 *
 * @param array $attributes Block attributes: `limit` (0 = no limit), `heading`, `emptyMessage`.
 * @return string
 */
function render_voice_list_block( array $attributes = array() ): string {
	$limit         = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 0;
	$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
	$empty_message = isset( $attributes['emptyMessage'] ) ? (string) $attributes['emptyMessage'] : '';

	$voices = get_voices();

	if ( $limit > 0 ) {
		$voices = array_slice( $voices, 0, $limit );
	}

	if ( empty( $voices ) ) {
		if ( '' === $empty_message ) {
			return '';
		}

		return '<p class="wp-block-astrea-voice-list-empty">' . esc_html( $empty_message ) . '</p>';
	}

	$items = '';

	foreach ( $voices as $voice ) {
		$items .= '<figure class="wp-block-astrea-voice-item">';
		$items .= '<blockquote>' . wp_kses_post( apply_filters( 'the_content', $voice['content'] ) ) . '</blockquote>'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- invoking WordPress core's own `the_content` filter, not declaring a new hook.
		$items .= '<figcaption><cite>' . esc_html( $voice['display_name'] ) . '</cite></figcaption>';
		$items .= '</figure>';
	}

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . '<div class="wp-block-astrea-voice-list">' . $items . '</div>';
}
