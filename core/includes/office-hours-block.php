<?php
/**
 * Office Profile — business hours Dynamic Block (Construction Order 011).
 *
 * Office Profile has stored weekly business hours + closure exceptions
 * since Construction Order 002, but had no Theme display path at all: the
 * `astrea-core/office-profile` Block Bindings source (block-bindings.php)
 * only exposes 4 scalar fields, because Block Bindings has no mechanism
 * for a 7-row conditional table or a variable-length list (see that file's
 * docblock). This block reads Office Profile's existing data (no new data
 * model — `business_hours.weekly` / `business_hours.exceptions`, defined
 * in office-profile.php) and is the Dynamic Block that decision explicitly
 * anticipated.
 *
 * Follows the same `heading` / `emptyMessage` convention as the other
 * Core Dynamic Blocks (Decision 028): unset both to self-hide completely
 * (including the heading) when there is nothing configured, or set
 * `emptyMessage` to show a friendly message instead. "Nothing configured"
 * means every weekday is closed AND there are no closure exceptions — the
 * same shape get_defaults() returns for a site that has never touched this
 * screen, so a genuinely unconfigured site never publishes an empty table.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\OfficeProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

add_action( 'init', __NAMESPACE__ . '\\register_hours_block' );

/**
 * Registers the astrea/office-hours Dynamic Block.
 *
 * @return void
 */
function register_hours_block() {
	register_block_type(
		'astrea/office-hours',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_hours_block',
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
 * Whether there is anything worth displaying: at least one open weekday,
 * or at least one closure exception row.
 *
 * @param array $weekly     business_hours.weekly shape (see office-profile.php).
 * @param array $exceptions business_hours.exceptions shape.
 * @return bool
 */
function has_configured_hours( array $weekly, array $exceptions ): bool {
	foreach ( $weekly as $day ) {
		if ( empty( $day['closed'] ) ) {
			return true;
		}
	}

	return ! empty( $exceptions );
}

/**
 * Renders one closure-exception row as human-readable Japanese text.
 *
 * @param array $exception Row shape: label, start_date, end_date (Y-m-d or '').
 * @return string
 */
function format_exception( array $exception ): string {
	$label = (string) ( $exception['label'] ?? '' );
	$start = (string) ( $exception['start_date'] ?? '' );
	$end   = (string) ( $exception['end_date'] ?? '' );

	$format_date = static function ( string $date ): string {
		$timestamp = strtotime( $date );
		return $timestamp ? date_i18n( 'Y年n月j日', $timestamp ) : '';
	};

	$range = '';
	if ( '' !== $start && '' !== $end ) {
		$range = $format_date( $start ) . '〜' . $format_date( $end );
	} elseif ( '' !== $start ) {
		$range = $format_date( $start ) . '〜';
	} elseif ( '' !== $end ) {
		$range = '〜' . $format_date( $end );
	}

	if ( '' !== $label && '' !== $range ) {
		return $label . '：' . $range;
	}

	return '' !== $label ? $label : $range;
}

/**
 * Renders the weekly hours table + closure exceptions as plain semantic
 * HTML.
 *
 * @param array $attributes Block attributes (`heading`, `emptyMessage`).
 * @return string
 */
function render_hours_block( array $attributes = array() ): string {
	$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
	$empty_message = isset( $attributes['emptyMessage'] ) ? (string) $attributes['emptyMessage'] : '';

	$profile    = get_office_profile();
	$weekly     = $profile['business_hours']['weekly'];
	$exceptions = $profile['business_hours']['exceptions'];

	if ( ! has_configured_hours( $weekly, $exceptions ) ) {
		if ( '' === $empty_message ) {
			return '';
		}

		return '<p class="wp-block-astrea-office-hours-empty">' . esc_html( $empty_message ) . '</p>';
	}

	$rows = '';
	foreach ( WEEKDAYS as $day ) {
		$row = $weekly[ $day ];

		$value = empty( $row['closed'] )
			? esc_html( $row['open'] ) . '〜' . esc_html( $row['close'] )
			: esc_html__( '休業', 'astrea-core' );

		$rows .= '<dt>' . esc_html( weekday_label( $day ) ) . '</dt><dd>' . $value . '</dd>';
	}

	$body = '<dl class="wp-block-astrea-office-hours-weekly">' . $rows . '</dl>';

	if ( ! empty( $exceptions ) ) {
		$items = '';
		foreach ( $exceptions as $exception ) {
			$text = format_exception( $exception );
			if ( '' !== $text ) {
				$items .= '<li>' . esc_html( $text ) . '</li>';
			}
		}

		if ( '' !== $items ) {
			$body .= '<ul class="wp-block-astrea-office-hours-exceptions">' . $items . '</ul>';
		}
	}

	$heading_html = '' !== $heading ? '<h2>' . esc_html( $heading ) . '</h2>' : '';

	return $heading_html . '<div class="wp-block-astrea-office-hours">' . $body . '</div>';
}
