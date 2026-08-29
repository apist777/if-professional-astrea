<?php
/**
 * Closing CTA — self-hiding Dynamic Block for Single template "No Empty
 * Endings" (Construction Order 015D, Visual v2).
 *
 * Every Service/Professional/CASE Single ends with this block instead of
 * running straight into the Footer. Uses only existing, already-public
 * data: the Contact page URL Setup already tracks
 * (`Astrea\Core\Setup\get_contact_page_url()`) and Office Profile's phone
 * number (the same source `astrea-core/office-profile` Block Bindings
 * already reads). No new Semantic Data, no new persistence, no new
 * Endpoint.
 *
 * Never hardcodes a URL and never fabricates urgency copy ("無料相談",
 * "24時間受付" etc. are explicitly out of scope) — the heading and button
 * labels are neutral and identical regardless of Style Variation or
 * office. If neither a Contact page nor a phone number is available
 * (Setup never run, Core just reactivated, Office Profile empty), the
 * block renders nothing at all rather than a broken/dead-end button —
 * the same self-hide convention every other astrea/* Dynamic Block uses.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\ClosingCta;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'init', __NAMESPACE__ . '\\register_block' );

/**
 * Registers the astrea/closing-cta Dynamic Block.
 *
 * @return void
 */
function register_block() {
	register_block_type(
		'astrea/closing-cta',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_closing_cta_block',
			'editor_script_handles' => array( \Astrea\Core\EditorBlocks\SCRIPT_HANDLE ),
			'attributes'            => array(
				'heading' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		)
	);
}

/**
 * Renders a Closing CTA section, or '' when there is no safe action to offer.
 *
 * @param array $attributes Block attributes: `heading` (optional; a sensible default is used when empty).
 * @return string
 */
function render_closing_cta_block( array $attributes = array() ): string {
	$heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';

	$contact_url = \Astrea\Core\Setup\get_contact_page_url();

	$profile = \Astrea\Core\OfficeProfile\get_office_profile();
	$phone   = (string) ( $profile['phone'] ?? '' );
	$tel_uri = '' !== $phone ? \Astrea\Core\Bindings\phone_to_tel_uri( $phone ) : '';

	if ( null === $contact_url && '' === $phone ) {
		return ''; // Nothing safe to offer — no dead-end button, no broken link.
	}

	if ( '' === $heading ) {
		$heading = __( 'お問い合わせ', 'astrea-core' );
	}

	$buttons = '';

	if ( '' !== $phone ) {
		$buttons .= '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $tel_uri ) . '">' . esc_html( $phone ) . '</a></div>';
	}

	if ( null !== $contact_url ) {
		$buttons .= '<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $contact_url ) . '">' . esc_html__( 'お問い合わせはこちら', 'astrea-core' ) . '</a></div>';
	}

	return '<div class="wp-block-astrea-closing-cta"><h2>' . esc_html( $heading ) . '</h2><div class="wp-block-buttons">' . $buttons . '</div></div>';
}
