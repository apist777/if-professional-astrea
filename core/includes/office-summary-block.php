<?php
/**
 * Office Profile — name/address/phone summary Dynamic Block
 * (Construction Order 015E).
 *
 * Office Name/Address/Phone have been readable since Construction Order
 * 002 only via the `astrea-core/office-profile` Block Bindings source
 * (block-bindings.php) attached to three independent `core/paragraph`
 * blocks — each with no label, and each rendering an empty `<p></p>`
 * whenever the underlying field is unset (Block Bindings can only replace
 * a block's inner content, never remove the block itself; the exact same
 * limitation already documented in office-hours-block.php and
 * professional-field-block.php).
 *
 * This block replaces that with a single Dynamic Block that reads through
 * the existing `get_office_profile()` public boundary (no new data) and
 * renders a Label/Value structure only for the fields that are actually
 * populated — Office Name is shown as an identity heading (it does not
 * need its own "事務所名" label, any more than a business card does),
 * Address/Phone become a `<dl>` of labelled rows. Any field left empty is
 * skipped individually rather than leaving a blank labelled row (Order
 * §7); the whole block self-hides (or shows `emptyMessage`) only when
 * ALL THREE fields are empty.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\OfficeProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'init', __NAMESPACE__ . '\\register_summary_block' );

/**
 * Registers the astrea/office-summary Dynamic Block.
 *
 * @return void
 */
function register_summary_block() {
	register_block_type(
		'astrea/office-summary',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_summary_block',
			'editor_script_handles' => array( \Astrea\Core\EditorBlocks\SCRIPT_HANDLE ),
			'attributes'            => array(
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
 * Renders Office Name/Address/Phone as plain semantic HTML.
 *
 * @param array $attributes Block attributes (`heading`, `emptyMessage`).
 * @return string
 */
function render_summary_block( array $attributes = array() ): string {
	$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
	$empty_message = isset( $attributes['emptyMessage'] ) ? (string) $attributes['emptyMessage'] : '';

	$profile     = get_office_profile();
	$office_name = trim( (string) $profile['office_name'] );
	$address     = trim( (string) $profile['address'] );
	$phone       = trim( (string) $profile['phone'] );

	if ( '' === $office_name && '' === $address && '' === $phone ) {
		if ( '' === $empty_message ) {
			return '';
		}

		return '<p class="wp-block-astrea-office-summary-empty">' . esc_html( $empty_message ) . '</p>';
	}

	$name_html = '' !== $office_name
		? '<p class="wp-block-astrea-office-summary-name">' . esc_html( $office_name ) . '</p>'
		: '';

	$rows = '';
	if ( '' !== $address ) {
		$rows .= '<dt>' . esc_html__( '所在地', 'astrea-core' ) . '</dt><dd>' . esc_html( $address ) . '</dd>';
	}

	if ( '' !== $phone ) {
		$tel_uri    = \Astrea\Core\Bindings\phone_to_tel_uri( $phone );
		$phone_html = $tel_uri
			? '<a href="' . esc_attr( $tel_uri ) . '">' . esc_html( $phone ) . '</a>'
			: esc_html( $phone );
		$rows      .= '<dt>' . esc_html__( '電話番号', 'astrea-core' ) . '</dt><dd>' . $phone_html . '</dd>';
	}

	$details_html = '' !== $rows ? '<dl class="wp-block-astrea-office-summary-details">' . $rows . '</dl>' : '';

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . '<div class="wp-block-astrea-office-summary">' . $name_html . $details_html . '</div>';
}
