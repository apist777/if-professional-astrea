<?php
/**
 * Office Profile — "本日の営業状況" (today's business status) Dynamic Block
 * (Construction 029-CI).
 *
 * Reads Office Profile's EXISTING `business_hours.weekly` data (no new
 * option, no new stored schema — Construction 029-CI is presentation-only)
 * and derives one of a small set of today-status codes, using the current
 * time in WordPress's own configured timezone (`current_datetime()`,
 * WP 5.3+), never the server's system timezone or a new ASTREA-owned
 * timezone setting.
 *
 * Deliberately excluded from this Construction (see its report, PHASE 5/9):
 * closure `exceptions` (year-end/summer/ad-hoc closures) are not consulted
 * here, and no national-holiday inference is performed — both are
 * out-of-scope follow-ups, not silently-added scope. When today's weekly
 * row is closed, or open/close times are missing/malformed, or overnight
 * hours are detected (close <= open, not supported for this business
 * category), the status is UNKNOWN rather than a guessed OPEN/CLOSED — the
 * block then self-hides, matching the "never fabricate" convention already
 * used by office-hours-block.php / office-summary-block.php.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\OfficeProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Currently open, within today's configured hours. */
const BUSINESS_STATUS_OPEN = 'open';

/** Today is a business day, but the configured open time hasn't arrived yet. */
const BUSINESS_STATUS_BEFORE_OPEN = 'before_open';

/** Today is a business day, but the configured close time has passed. */
const BUSINESS_STATUS_AFTER_CLOSE = 'after_close';

/** Today's weekday is marked closed (定休日) in Office Profile. */
const BUSINESS_STATUS_CLOSED_DAY = 'closed_day';

/** Data is missing/malformed/unsupported (e.g. overnight hours) — never guessed. */
const BUSINESS_STATUS_UNKNOWN = 'unknown';

/**
 * Whether a string is a valid `HH:MM` 24-hour time, matching the exact
 * format `office-profile.php`'s `sanitize_weekly_hours()` already enforces
 * on save (00:00–23:59).
 *
 * @param string $value Candidate time string.
 * @return bool
 */
function is_valid_time_string( string $value ): bool {
	return 1 === preg_match( '/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $value );
}

/**
 * Converts a validated `HH:MM` string to minutes-since-midnight.
 *
 * @param string $time A string already confirmed valid by is_valid_time_string().
 * @return int
 */
function time_to_minutes( string $time ): int {
	list( $hours, $minutes ) = array_map( 'intval', explode( ':', $time ) );

	return ( $hours * 60 ) + $minutes;
}

/**
 * Pure decision function: given Office Profile's existing weekly hours and
 * a point in time, determines today's business status. Takes no
 * dependency on the current request's actual time or on Office Profile's
 * storage, so it can be exercised directly and repeatably from tests with
 * any injected `$now` (Construction 029-CI PHASE 19).
 *
 * @param array              $weekly business_hours.weekly shape (see office-profile.php get_defaults()).
 * @param \DateTimeImmutable $now    The moment to evaluate against, already in the site's configured timezone.
 * @return array{status: string, open: string, close: string} `open`/`close` are today's configured values when known, else ''.
 */
function determine_business_status( array $weekly, \DateTimeImmutable $now ): array {
	$day_index = ( (int) $now->format( 'N' ) ) - 1; // ISO-8601: 1 (Mon) .. 7 (Sun) -> 0..6.
	$day_key   = WEEKDAYS[ $day_index ] ?? null;

	if ( null === $day_key || ! isset( $weekly[ $day_key ] ) || ! is_array( $weekly[ $day_key ] ) ) {
		return array(
			'status' => BUSINESS_STATUS_UNKNOWN,
			'open'   => '',
			'close'  => '',
		);
	}

	$today = $weekly[ $day_key ];

	if ( ! empty( $today['closed'] ) ) {
		return array(
			'status' => BUSINESS_STATUS_CLOSED_DAY,
			'open'   => '',
			'close'  => '',
		);
	}

	$open  = isset( $today['open'] ) ? (string) $today['open'] : '';
	$close = isset( $today['close'] ) ? (string) $today['close'] : '';

	if ( ! is_valid_time_string( $open ) || ! is_valid_time_string( $close ) ) {
		return array(
			'status' => BUSINESS_STATUS_UNKNOWN,
			'open'   => $open,
			'close'  => $close,
		);
	}

	$open_minutes  = time_to_minutes( $open );
	$close_minutes = time_to_minutes( $close );

	if ( $close_minutes <= $open_minutes ) {
		// Overnight (e.g. 18:00-02:00) or a nonsensical range: not
		// supported for this business category (Construction 029-CI
		// PHASE 8) — UNKNOWN rather than a naive, likely-wrong comparison.
		return array(
			'status' => BUSINESS_STATUS_UNKNOWN,
			'open'   => $open,
			'close'  => $close,
		);
	}

	$now_minutes = ( (int) $now->format( 'H' ) ) * 60 + (int) $now->format( 'i' );

	if ( $now_minutes < $open_minutes ) {
		$status = BUSINESS_STATUS_BEFORE_OPEN;
	} elseif ( $now_minutes < $close_minutes ) {
		$status = BUSINESS_STATUS_OPEN;
	} else {
		$status = BUSINESS_STATUS_AFTER_CLOSE;
	}

	return array(
		'status' => $status,
		'open'   => $open,
		'close'  => $close,
	);
}

/**
 * Impure wrapper: reads Office Profile's real data and the current moment
 * in WordPress's configured timezone (`current_datetime()`, never the PHP
 * system timezone or a new ASTREA-owned setting — Construction 029-CI
 * PHASE 6), then delegates to the pure decision function above.
 *
 * @return array{status: string, open: string, close: string}
 */
function get_business_status(): array {
	$profile = get_office_profile();
	$weekly  = $profile['business_hours']['weekly'];

	return determine_business_status( $weekly, current_datetime() );
}

add_action( 'init', __NAMESPACE__ . '\\register_business_status_block' );

/**
 * Registers the astrea/business-status Dynamic Block.
 *
 * @return void
 */
function register_business_status_block() {
	register_block_type(
		'astrea/business-status',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_business_status_block',
			'editor_script_handles' => array( \Astrea\Core\EditorBlocks\SCRIPT_HANDLE ),
		)
	);
}

/**
 * Human-readable label for a status code.
 *
 * @param string $status One of the BUSINESS_STATUS_* constants.
 * @return string
 */
function business_status_label( string $status ): string {
	switch ( $status ) {
		case BUSINESS_STATUS_OPEN:
			return __( '本日営業中', 'astrea-core' );
		case BUSINESS_STATUS_BEFORE_OPEN:
			return __( '本日は営業時間前です', 'astrea-core' );
		case BUSINESS_STATUS_AFTER_CLOSE:
			return __( '本日の受付は終了しました', 'astrea-core' );
		case BUSINESS_STATUS_CLOSED_DAY:
			return __( '本日は定休日です', 'astrea-core' );
		default:
			return '';
	}
}

/**
 * Renders "本日の営業状況" as a small, plain semantic block near the
 * phone/contact CTA — self-hides entirely when the status is UNKNOWN
 * (missing/malformed/unsupported data), matching the existing
 * office-hours-block.php / office-summary-block.php "never fabricate"
 * convention rather than showing a fallback that could be wrong.
 *
 * @return string
 */
function render_business_status_block(): string {
	$result = get_business_status();
	$status = $result['status'];

	if ( BUSINESS_STATUS_UNKNOWN === $status ) {
		return '';
	}

	$label = business_status_label( $status );

	$hours_html = '';
	if ( '' !== $result['open'] && '' !== $result['close'] ) {
		$hours_html = '<p class="astrea-business-status-hours">' . sprintf(
			/* translators: 1: today's opening time (H:MM), 2: today's closing time (H:MM) */
			esc_html__( '本日の受付時間 %1$s〜%2$s', 'astrea-core' ),
			esc_html( $result['open'] ),
			esc_html( $result['close'] )
		) . '</p>';
	}

	$status_class = 'is-status-' . str_replace( '_', '-', $status );

	return '<div class="wp-block-astrea-business-status ' . esc_attr( $status_class ) . '">'
		. '<p class="astrea-business-status-label">' . esc_html( $label ) . '</p>'
		. $hours_html
		. '</div>';
}
