<?php
/**
 * Office Profile — Core-owned data layer.
 *
 * Office Profile is the first ASTREA Core data feature (Construction Order
 * 002). It stores the compact "事務所基本情報" set defined in
 * docs/specifications/01_astrea_product_plan_v0.1.md §11 and
 * docs/specifications/02_astrea_free_v1_specification.md §4: office name,
 * representative name, address, phone, weekly business hours (with
 * exception date ranges for closures such as 年末年始/夏季休業/臨時休業),
 * and SNS links.
 *
 * Deliberately excluded from this data set (see
 * docs/research/2026-08-26_construction_order_002_report.md): professional
 * bio/qualifications (§8 PROFILE), CTA/consultation-method, and
 * ACCESS-page-only fields (nearest station, walk time, parking). These are
 * distinct Core responsibilities per AGENTS.md §6 and are left for a future
 * Construction Order rather than folded in here.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\OfficeProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Option name. Also the identifier a future explicit, user-confirmed
 * "delete all Core data" flow (Decision 019) must target.
 */
const OPTION_NAME = 'astrea_core_office_profile';

/**
 * Settings API group name used to register/save the option.
 */
const SETTINGS_GROUP = 'astrea_core_office_profile_group';

/**
 * Current data shape version, stored inside the option itself.
 *
 * There is no migration runner yet because this is the first schema
 * version ever shipped — there is nothing to migrate from. See the
 * Migration/Schema section of docs/research/2026-08-26_construction_order_002_report.md
 * for when a migration mechanism should be added.
 */
const SCHEMA_VERSION = 1;

const WEEKDAYS = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );

add_action( 'admin_init', __NAMESPACE__ . '\\register' );

/**
 * Registers the Office Profile option with the Settings API.
 *
 * Using register_setting() gives us, for free, from WordPress core:
 * nonce/CSRF protection (via settings_fields()+options.php), the
 * sanitize_callback hook, and a capability-gated save path — matching the
 * "WordPress standard API first" requirement instead of hand-rolling a
 * custom form handler.
 *
 * @return void
 */
function register() {
	register_setting(
		SETTINGS_GROUP,
		OPTION_NAME,
		array(
			'type'              => 'object',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize',
			'default'           => get_defaults(),
			'show_in_rest'      => false,
		)
	);
}

/**
 * Returns the default Office Profile shape.
 *
 * All fields default to "unconfigured" (empty / closed) rather than
 * fabricated values, matching the 60-point-publish principle: an unfilled
 * field must never cause the Theme to display invented data.
 *
 * @return array
 */
function get_defaults(): array {
	$weekly = array();
	foreach ( WEEKDAYS as $day ) {
		$weekly[ $day ] = array(
			'closed' => true,
			'open'   => '',
			'close'  => '',
		);
	}

	return array(
		'schema_version'      => SCHEMA_VERSION,
		'office_name'         => '',
		'representative_name' => '',
		'address'             => '',
		'phone'               => '',
		'business_hours'      => array(
			'weekly'     => $weekly,
			'exceptions' => array(),
		),
		'sns_links'           => array(),
	);
}

/**
 * Public read boundary for Office Profile data.
 *
 * This is the ONLY supported way for other code (Theme, future Blocks,
 * future PRO products) to read Office Profile data. Callers must not read
 * the `astrea_core_office_profile` option directly, and must not assume
 * anything about how/where this is stored internally (Decision 013,
 * Decision 021) — that internal representation may change in a future
 * Core version without notice.
 *
 * @return array Office Profile data, always containing every key from
 *               get_defaults(), even if never saved before.
 */
function get_office_profile(): array {
	$stored = get_option( OPTION_NAME, array() );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	return merge_with_defaults( $stored );
}

/**
 * Deep-merges stored data over the defaults so missing keys (e.g. after a
 * partial save, or before the option has ever been saved) are always
 * filled in rather than triggering undefined-index notices downstream.
 *
 * @param array $stored Raw stored value.
 * @return array
 */
function merge_with_defaults( array $stored ): array {
	$defaults = get_defaults();
	$merged   = array_merge( $defaults, $stored );

	$merged['business_hours'] = isset( $stored['business_hours'] ) && is_array( $stored['business_hours'] )
		? $stored['business_hours']
		: $defaults['business_hours'];

	if ( ! isset( $merged['business_hours']['weekly'] ) || ! is_array( $merged['business_hours']['weekly'] ) ) {
		$merged['business_hours']['weekly'] = $defaults['business_hours']['weekly'];
	} else {
		foreach ( WEEKDAYS as $day ) {
			if ( ! isset( $merged['business_hours']['weekly'][ $day ] ) || ! is_array( $merged['business_hours']['weekly'][ $day ] ) ) {
				$merged['business_hours']['weekly'][ $day ] = $defaults['business_hours']['weekly'][ $day ];
			}
		}
	}

	if ( ! isset( $merged['business_hours']['exceptions'] ) || ! is_array( $merged['business_hours']['exceptions'] ) ) {
		$merged['business_hours']['exceptions'] = array();
	}

	if ( ! isset( $merged['sns_links'] ) || ! is_array( $merged['sns_links'] ) ) {
		$merged['sns_links'] = array();
	}

	return $merged;
}

/**
 * Validates a `Y-m-d` date string.
 *
 * @param string $value Date string to check.
 * @return bool
 */
function is_valid_date( string $value ): bool {
	$date = \DateTime::createFromFormat( 'Y-m-d', $value );

	return $date instanceof \DateTime && $date->format( 'Y-m-d' ) === $value;
}

/**
 * Settings API sanitize_callback for the Office Profile option.
 *
 * On any individual field failing validation, that field is rolled back to
 * its previously stored value (rather than saving a corrupted value or
 * silently discarding the whole submission), and a settings error is
 * registered so the admin screen shows a clear, specific message.
 *
 * @param mixed $input Raw submitted value.
 * @return array Sanitized Office Profile data, always well-formed.
 */
function sanitize( $input ): array {
	$existing = get_office_profile();
	$input    = is_array( $input ) ? $input : array();
	$output   = $existing;

	$output['schema_version'] = SCHEMA_VERSION;

	$output['office_name']         = isset( $input['office_name'] ) ? sanitize_text_field( wp_unslash( (string) $input['office_name'] ) ) : '';
	$output['representative_name'] = isset( $input['representative_name'] ) ? sanitize_text_field( wp_unslash( (string) $input['representative_name'] ) ) : '';
	$output['address']             = isset( $input['address'] ) ? sanitize_text_field( wp_unslash( (string) $input['address'] ) ) : '';

	$output['phone'] = sanitize_phone( $input, $existing );

	$output['business_hours'] = array(
		'weekly'     => sanitize_weekly_hours( $input, $existing ),
		'exceptions' => sanitize_exceptions( $input ),
	);

	$output['sns_links'] = sanitize_sns_links( $input );

	return $output;
}

/**
 * Sanitizes/validates the phone field.
 *
 * @param array $input    Raw submitted data.
 * @param array $existing Previously stored, already-sanitized profile.
 * @return string
 */
function sanitize_phone( array $input, array $existing ): string {
	$raw = isset( $input['phone'] ) ? sanitize_text_field( wp_unslash( (string) $input['phone'] ) ) : '';

	if ( '' === $raw ) {
		return '';
	}

	// Digits (half-width and full-width), spaces, hyphens, parentheses, plus sign only.
	$pattern = '/^[0-9０-９\-ー－\+\(\)（）\s]+$/u';

	if ( preg_match( $pattern, $raw ) ) {
		return $raw;
	}

	add_settings_error(
		OPTION_NAME,
		'astrea_core_invalid_phone',
		__( '電話番号の形式が正しくありません。数字・ハイフン・カッコ・スペースのみ使用できます。変更前の値を保持しました。', 'astrea-core' )
	);

	return $existing['phone'];
}

/**
 * Sanitizes/validates the weekly business hours table.
 *
 * @param array $input    Raw submitted data.
 * @param array $existing Previously stored, already-sanitized profile.
 * @return array
 */
function sanitize_weekly_hours( array $input, array $existing ): array {
	$time_pattern = '/^([01][0-9]|2[0-3]):[0-5][0-9]$/';
	$submitted    = isset( $input['business_hours']['weekly'] ) && is_array( $input['business_hours']['weekly'] )
		? $input['business_hours']['weekly']
		: array();

	$weekly = array();

	foreach ( WEEKDAYS as $day ) {
		$row       = isset( $submitted[ $day ] ) && is_array( $submitted[ $day ] ) ? $submitted[ $day ] : array();
		$closed    = ! empty( $row['closed'] );
		$open_raw  = isset( $row['open'] ) ? sanitize_text_field( wp_unslash( (string) $row['open'] ) ) : '';
		$close_raw = isset( $row['close'] ) ? sanitize_text_field( wp_unslash( (string) $row['close'] ) ) : '';

		$open_valid  = ( '' === $open_raw ) || preg_match( $time_pattern, $open_raw );
		$close_valid = ( '' === $close_raw ) || preg_match( $time_pattern, $close_raw );

		if ( ! $open_valid || ! $close_valid ) {
			add_settings_error(
				OPTION_NAME,
				'astrea_core_invalid_hours_' . $day,
				sprintf(
					/* translators: %s: weekday label */
					__( '営業時間（%s）の時刻形式が正しくありません。HH:MM形式で入力してください。変更前の値を保持しました。', 'astrea-core' ),
					weekday_label( $day )
				)
			);

			$weekly[ $day ] = $existing['business_hours']['weekly'][ $day ];
			continue;
		}

		$weekly[ $day ] = array(
			'closed' => $closed,
			'open'   => $closed ? '' : $open_raw,
			'close'  => $closed ? '' : $close_raw,
		);
	}

	return $weekly;
}

/**
 * Sanitizes/validates the closure exceptions repeater (年末年始, 夏季休業,
 * 臨時休業, etc.). Fully empty rows are silently skipped (they simply mean
 * the admin left an unused slot blank). Rows with an invalid date, or an
 * end date before the start date, are dropped with a settings error rather
 * than saved in a broken state.
 *
 * @param array $input Raw submitted data.
 * @return array
 */
function sanitize_exceptions( array $input ): array {
	$rows = isset( $input['business_hours']['exceptions'] ) && is_array( $input['business_hours']['exceptions'] )
		? $input['business_hours']['exceptions']
		: array();

	$output = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label = isset( $row['label'] ) ? sanitize_text_field( wp_unslash( (string) $row['label'] ) ) : '';
		$start = isset( $row['start_date'] ) ? sanitize_text_field( wp_unslash( (string) $row['start_date'] ) ) : '';
		$end   = isset( $row['end_date'] ) ? sanitize_text_field( wp_unslash( (string) $row['end_date'] ) ) : '';

		if ( '' === $label && '' === $start && '' === $end ) {
			continue; // Unused repeater slot.
		}

		$start_valid = ( '' === $start ) || is_valid_date( $start );
		$end_valid   = ( '' === $end ) || is_valid_date( $end );

		if ( ! $start_valid || ! $end_valid ) {
			add_settings_error(
				OPTION_NAME,
				'astrea_core_invalid_exception_date',
				sprintf(
					/* translators: %s: the label the admin entered for this closure row */
					__( '休業期間「%s」の日付が正しくありません（YYYY-MM-DD形式）。この行は保存されませんでした。', 'astrea-core' ),
					'' !== $label ? $label : __( '（無題）', 'astrea-core' )
				)
			);
			continue;
		}

		if ( $start && $end && $start > $end ) {
			add_settings_error(
				OPTION_NAME,
				'astrea_core_invalid_exception_range',
				sprintf(
					/* translators: %s: the label the admin entered for this closure row */
					__( '休業期間「%s」の終了日は開始日より後にしてください。この行は保存されませんでした。', 'astrea-core' ),
					'' !== $label ? $label : __( '（無題）', 'astrea-core' )
				)
			);
			continue;
		}

		$output[] = array(
			'label'      => $label,
			'start_date' => $start,
			'end_date'   => $end,
		);
	}

	return $output;
}

/**
 * Sanitizes/validates the SNS links repeater. Empty rows are skipped;
 * rows with an unusable URL are dropped with a settings error.
 *
 * @param array $input Raw submitted data.
 * @return array
 */
function sanitize_sns_links( array $input ): array {
	$rows = isset( $input['sns_links'] ) && is_array( $input['sns_links'] ) ? $input['sns_links'] : array();

	$output = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label   = isset( $row['label'] ) ? sanitize_text_field( wp_unslash( (string) $row['label'] ) ) : '';
		$url_raw = isset( $row['url'] ) ? wp_unslash( (string) $row['url'] ) : '';

		if ( '' === $label && '' === $url_raw ) {
			continue; // Unused repeater slot.
		}

		$url = '' !== $url_raw ? esc_url_raw( $url_raw, array( 'http', 'https' ) ) : '';

		if ( '' !== $url_raw && '' === $url ) {
			add_settings_error(
				OPTION_NAME,
				'astrea_core_invalid_sns_url',
				sprintf(
					/* translators: %s: the label the admin entered for this SNS row */
					__( 'SNSリンク「%s」のURLが正しくありません（http/httpsのみ使用できます）。この行は保存されませんでした。', 'astrea-core' ),
					'' !== $label ? $label : __( '（無題）', 'astrea-core' )
				)
			);
			continue;
		}

		$output[] = array(
			'label' => $label,
			'url'   => $url,
		);
	}

	return $output;
}

/**
 * Human-readable Japanese weekday label.
 *
 * @param string $day One of WEEKDAYS.
 * @return string
 */
function weekday_label( string $day ): string {
	$labels = array(
		'mon' => __( '月曜日', 'astrea-core' ),
		'tue' => __( '火曜日', 'astrea-core' ),
		'wed' => __( '水曜日', 'astrea-core' ),
		'thu' => __( '木曜日', 'astrea-core' ),
		'fri' => __( '金曜日', 'astrea-core' ),
		'sat' => __( '土曜日', 'astrea-core' ),
		'sun' => __( '日曜日', 'astrea-core' ),
	);

	return $labels[ $day ] ?? $day;
}
