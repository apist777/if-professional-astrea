<?php
/**
 * Office Profile — "営業時間" (weekly business hours summary) Dynamic Block
 * (Construction 029-CI Revision 1).
 *
 * Reads Office Profile's EXISTING `business_hours.weekly` data (the exact
 * same Single Source of Truth as `office-business-status-block.php`'s
 * "本日の営業状況" and `office-hours-block.php`'s Office-page weekly
 * table) — no new option, no Home-only storage, no duplicated schema.
 *
 * Groups CONSECUTIVE weekdays that share an identical schedule into one
 * row (e.g. "月〜金　09:00〜18:00"), so a site owner never has to read a
 * 7-row table just to answer "when are you normally open?" — that
 * detailed 7-row view already exists on the Office page
 * (`astrea/office-hours`) and is intentionally left untouched; this block
 * is the Home-page SUMMARY, not a replacement.
 *
 * Grouping rules (Construction 029-CI Revision 1 Owner order, PHASE 3-6):
 * - OPEN days with the IDENTICAL open/close time that are also
 *   CONSECUTIVE in the Mon..Sun week order are compressed into a single
 *   "start〜end　open〜close" row. Non-consecutive days that merely happen
 *   to share the same hours are never merged into a range — that would
 *   misrepresent which days are actually contiguous (explicitly banned:
 *   "月・水・金が同じ時間だから月〜金と表示" is wrong information).
 * - CLOSED days are always listed individually and joined with "・"
 *   (nakaguro), even when consecutive (e.g. Sat+Sun -> "土・日", never
 *   "土〜日") — a deliberate, distinct convention from the OPEN range
 *   dash, matching the Owner order's own worked examples.
 * - The single exception is every day of the week being closed, which
 *   renders as one "月〜日　定休日" row (the Owner order's own PHASE 9
 *   example) rather than a 7-item nakaguro list.
 * - A day with missing/malformed data is silently omitted from every row
 *   (never guessed into a range) — if NO day has usable data at all, the
 *   block returns no rows and self-hides entirely, exactly like the
 *   sibling office-hours-block.php / office-business-status-block.php.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\OfficeProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Single-character weekday label for compact summary rows (月/火/水/木/
 * 金/土/日) — distinct from weekday_label()'s full "月曜日" form used by
 * the Admin screen's weekly-hours table.
 *
 * @param string $day One of WEEKDAYS.
 * @return string
 */
function weekday_short_label( string $day ): string {
	$labels = array(
		'mon' => __( '月', 'astrea-core' ),
		'tue' => __( '火', 'astrea-core' ),
		'wed' => __( '水', 'astrea-core' ),
		'thu' => __( '木', 'astrea-core' ),
		'fri' => __( '金', 'astrea-core' ),
		'sat' => __( '土', 'astrea-core' ),
		'sun' => __( '日', 'astrea-core' ),
	);

	return $labels[ $day ] ?? $day;
}

/**
 * Builds each weekday's normalized "shape" for grouping purposes: an
 * array (comparable with `===`) for a usable day, or `null` for a day
 * whose data is missing/malformed and must be silently excluded.
 *
 * @param array $weekly business_hours.weekly shape (see office-profile.php).
 * @return array<string, array{type:string, open?:string, close?:string}|null> Keyed by WEEKDAYS.
 */
function weekly_hours_shapes( array $weekly ): array {
	$shapes = array();

	foreach ( WEEKDAYS as $day ) {
		if ( ! isset( $weekly[ $day ] ) || ! is_array( $weekly[ $day ] ) ) {
			$shapes[ $day ] = null;
			continue;
		}

		$row = $weekly[ $day ];

		if ( ! empty( $row['closed'] ) ) {
			$shapes[ $day ] = array( 'type' => 'closed' );
			continue;
		}

		$open  = isset( $row['open'] ) ? (string) $row['open'] : '';
		$close = isset( $row['close'] ) ? (string) $row['close'] : '';

		if ( ! is_valid_time_string( $open ) || ! is_valid_time_string( $close ) ) {
			$shapes[ $day ] = null;
			continue;
		}

		$shapes[ $day ] = array(
			'type'  => 'open',
			'open'  => $open,
			'close' => $close,
		);
	}

	return $shapes;
}

/**
 * Pure formatter: turns Office Profile's weekly hours into a small list
 * of human-readable summary rows, per the grouping rules documented at
 * the top of this file. Takes no dependency on how the data is stored,
 * so it is directly and repeatably testable.
 *
 * @param array $weekly business_hours.weekly shape (see office-profile.php get_defaults()).
 * @return array<int, array{days: string, value: string}> Empty when no day has usable data.
 */
function weekly_hours_summary_rows( array $weekly ): array {
	$shapes = weekly_hours_shapes( $weekly );

	$usable_days = array_filter( $shapes, static fn( $shape ) => null !== $shape );

	if ( empty( $usable_days ) ) {
		return array();
	}

	// Special case (Owner order PHASE 9): every single day usable AND
	// every one of them closed -> one whole-week range row, not a
	// 7-item nakaguro list.
	$all_closed = count( $usable_days ) === count( WEEKDAYS );
	foreach ( $usable_days as $shape ) {
		if ( 'closed' !== $shape['type'] ) {
			$all_closed = false;
			break;
		}
	}

	if ( $all_closed ) {
		return array(
			array(
				'days'  => weekday_short_label( WEEKDAYS[0] ) . '〜' . weekday_short_label( WEEKDAYS[ count( WEEKDAYS ) - 1 ] ),
				'value' => __( '定休日', 'astrea-core' ),
			),
		);
	}

	$rows  = array();
	$count = count( WEEKDAYS );
	$i     = 0;

	while ( $i < $count ) {
		$day   = WEEKDAYS[ $i ];
		$shape = $shapes[ $day ];

		if ( null === $shape ) {
			++$i;
			continue; // Unusable day: silently omitted, never guessed.
		}

		$j = $i;
		while ( $j + 1 < $count && $shapes[ WEEKDAYS[ $j + 1 ] ] === $shape ) {
			++$j;
		}

		$segment_days = array_slice( WEEKDAYS, $i, $j - $i + 1 );

		if ( 'closed' === $shape['type'] ) {
			$days_label = implode( '・', array_map( __NAMESPACE__ . '\\weekday_short_label', $segment_days ) );
			$value      = __( '定休日', 'astrea-core' );
		} else {
			$days_label = count( $segment_days ) > 1
				? weekday_short_label( $segment_days[0] ) . '〜' . weekday_short_label( $segment_days[ count( $segment_days ) - 1 ] )
				: weekday_short_label( $segment_days[0] );
			$value      = $shape['open'] . '〜' . $shape['close'];
		}

		$rows[] = array(
			'days'  => $days_label,
			'value' => $value,
		);

		$i = $j + 1;
	}

	return $rows;
}

/**
 * Impure wrapper: reads Office Profile's real data and delegates to the
 * pure formatter above.
 *
 * @return array<int, array{days: string, value: string}>
 */
function get_weekly_hours_summary(): array {
	$profile = get_office_profile();

	return weekly_hours_summary_rows( $profile['business_hours']['weekly'] );
}

add_action( 'init', __NAMESPACE__ . '\\register_business_hours_summary_block' );

/**
 * Registers the astrea/business-hours-summary Dynamic Block.
 *
 * @return void
 */
function register_business_hours_summary_block() {
	register_block_type(
		'astrea/business-hours-summary',
		array(
			'render_callback'       => __NAMESPACE__ . '\\render_business_hours_summary_block',
			'editor_script_handles' => array( \Astrea\Core\EditorBlocks\SCRIPT_HANDLE ),
		)
	);
}

/**
 * Renders the weekly hours summary as a small, plain semantic block —
 * self-hides entirely when there is no usable data at all (Construction
 * 029-CI Revision 1 PHASE 8), matching the "never fabricate" convention
 * already used by every other Office Profile Dynamic Block.
 *
 * @return string
 */
function render_business_hours_summary_block(): string {
	$rows = get_weekly_hours_summary();

	if ( empty( $rows ) ) {
		return '';
	}

	$items = '';
	foreach ( $rows as $row ) {
		$items .= '<div class="astrea-hours-summary-row">'
			. '<span class="astrea-hours-summary-days">' . esc_html( $row['days'] ) . '</span>'
			. '<span class="astrea-hours-summary-value">' . esc_html( $row['value'] ) . '</span>'
			. '</div>';
	}

	return '<div class="wp-block-astrea-business-hours-summary">'
		. '<p class="astrea-hours-summary-heading">' . esc_html__( '営業時間', 'astrea-core' ) . '</p>'
		. $items
		. '</div>';
}
