<?php
/**
 * Tests for ASTREA Core's Home "営業時間" weekly summary feature
 * (Construction 029-CI Revision 1).
 *
 * `weekly_hours_summary_rows()` is a pure function of the weekly hours
 * array — these tests exercise it directly rather than depending on
 * Office Profile persistence, mirroring OfficeBusinessStatusTest.php's
 * pattern.
 *
 * @package Astrea\Core
 */

use Astrea\Core\OfficeProfile;

/**
 * @covers \Astrea\Core\OfficeProfile
 */
class OfficeBusinessHoursSummaryTest extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( OfficeProfile\OPTION_NAME );
		parent::tear_down();
	}

	/**
	 * Builds a full weekly-hours array with every day closed except the
	 * ones explicitly overridden.
	 *
	 * @param array $overrides Map of day key => array( 'closed' => bool, 'open' => string, 'close' => string ).
	 * @return array
	 */
	private function weekly_with( array $overrides = array() ): array {
		$weekly = array();
		foreach ( OfficeProfile\WEEKDAYS as $day ) {
			$weekly[ $day ] = array(
				'closed' => true,
				'open'   => '',
				'close'  => '',
			);
		}

		foreach ( $overrides as $day => $row ) {
			$weekly[ $day ] = array_merge( $weekly[ $day ], $row );
		}

		return $weekly;
	}

	private function open( string $open, string $close ): array {
		return array(
			'closed' => false,
			'open'   => $open,
			'close'  => $close,
		);
	}

	public function test_monday_to_friday_same_hours_are_grouped_into_one_range() {
		$weekly = $this->weekly_with(
			array(
				'mon' => $this->open( '09:00', '18:00' ),
				'tue' => $this->open( '09:00', '18:00' ),
				'wed' => $this->open( '09:00', '18:00' ),
				'thu' => $this->open( '09:00', '18:00' ),
				'fri' => $this->open( '09:00', '18:00' ),
			)
		);

		$rows = OfficeProfile\weekly_hours_summary_rows( $weekly );

		$this->assertSame(
			array(
				array( 'days' => '月〜金', 'value' => '09:00〜18:00' ),
				array( 'days' => '土・日', 'value' => '定休日' ),
			),
			$rows
		);
	}

	public function test_saturday_sunday_closed_are_grouped_with_nakaguro_not_a_range() {
		$weekly = $this->weekly_with(
			array(
				'mon' => $this->open( '09:00', '18:00' ),
				'tue' => $this->open( '09:00', '18:00' ),
				'wed' => $this->open( '09:00', '18:00' ),
				'thu' => $this->open( '09:00', '18:00' ),
				'fri' => $this->open( '09:00', '18:00' ),
				'sat' => array( 'closed' => true ),
				'sun' => array( 'closed' => true ),
			)
		);

		$rows = OfficeProfile\weekly_hours_summary_rows( $weekly );

		$closed_row = end( $rows );
		$this->assertSame( '土・日', $closed_row['days'], 'Consecutive closed days must use 「・」, never 「〜」.' );
		$this->assertStringNotContainsString( '〜', $closed_row['days'] );
	}

	public function test_middle_weekday_closed_breaks_the_open_range_correctly() {
		// Mon-Tue open, Wed closed, Thu-Fri open, Sat-Sun closed.
		$weekly = $this->weekly_with(
			array(
				'mon' => $this->open( '09:00', '18:00' ),
				'tue' => $this->open( '09:00', '18:00' ),
				'wed' => array( 'closed' => true ),
				'thu' => $this->open( '09:00', '18:00' ),
				'fri' => $this->open( '09:00', '18:00' ),
			)
		);

		$rows = OfficeProfile\weekly_hours_summary_rows( $weekly );

		$this->assertSame(
			array(
				array( 'days' => '月〜火', 'value' => '09:00〜18:00' ),
				array( 'days' => '水', 'value' => '定休日' ),
				array( 'days' => '木〜金', 'value' => '09:00〜18:00' ),
				array( 'days' => '土・日', 'value' => '定休日' ),
			),
			$rows
		);
	}

	public function test_one_weekday_with_different_hours_is_its_own_row() {
		// Mon-Thu 09-18, Fri 10-17, Sat-Sun closed.
		$weekly = $this->weekly_with(
			array(
				'mon' => $this->open( '09:00', '18:00' ),
				'tue' => $this->open( '09:00', '18:00' ),
				'wed' => $this->open( '09:00', '18:00' ),
				'thu' => $this->open( '09:00', '18:00' ),
				'fri' => $this->open( '10:00', '17:00' ),
			)
		);

		$rows = OfficeProfile\weekly_hours_summary_rows( $weekly );

		$this->assertSame(
			array(
				array( 'days' => '月〜木', 'value' => '09:00〜18:00' ),
				array( 'days' => '金', 'value' => '10:00〜17:00' ),
				array( 'days' => '土・日', 'value' => '定休日' ),
			),
			$rows
		);
	}

	public function test_non_contiguous_days_with_same_hours_are_never_merged_into_a_false_range() {
		// Mon, Wed, Fri all 09-18 but Tue/Thu/Sat/Sun closed — must NOT
		// become a single "月〜金" row, which would misrepresent Tue/Thu
		// as open.
		$weekly = $this->weekly_with(
			array(
				'mon' => $this->open( '09:00', '18:00' ),
				'tue' => array( 'closed' => true ),
				'wed' => $this->open( '09:00', '18:00' ),
				'thu' => array( 'closed' => true ),
				'fri' => $this->open( '09:00', '18:00' ),
			)
		);

		$rows = OfficeProfile\weekly_hours_summary_rows( $weekly );

		foreach ( $rows as $row ) {
			if ( '09:00〜18:00' === $row['value'] ) {
				$this->assertNotSame( '月〜金', $row['days'], 'Non-contiguous same-hours days must never be reported as a single range.' );
			}
		}

		$this->assertSame(
			array(
				array( 'days' => '月', 'value' => '09:00〜18:00' ),
				array( 'days' => '火', 'value' => '定休日' ),
				array( 'days' => '水', 'value' => '09:00〜18:00' ),
				array( 'days' => '木', 'value' => '定休日' ),
				array( 'days' => '金', 'value' => '09:00〜18:00' ),
				array( 'days' => '土・日', 'value' => '定休日' ),
			),
			$rows
		);
	}

	public function test_non_contiguous_closed_days_are_joined_with_nakaguro() {
		$weekly = $this->weekly_with(
			array(
				'mon' => $this->open( '09:00', '18:00' ),
				'tue' => $this->open( '09:00', '18:00' ),
				'thu' => $this->open( '09:00', '18:00' ),
				'fri' => $this->open( '09:00', '18:00' ),
				'sat' => $this->open( '09:00', '18:00' ),
				'wed' => array( 'closed' => true ),
				'sun' => array( 'closed' => true ),
			)
		);

		$rows = OfficeProfile\weekly_hours_summary_rows( $weekly );

		$closed_rows = array_values( array_filter( $rows, static fn( $row ) => '定休日' === $row['value'] ) );

		$this->assertCount( 2, $closed_rows, 'Wed and Sun are not adjacent, so they must appear as two separate rows, not merged.' );
		$this->assertSame( '水', $closed_rows[0]['days'] );
		$this->assertSame( '日', $closed_rows[1]['days'] );
	}

	public function test_all_days_closed_renders_one_whole_week_row() {
		$weekly = $this->weekly_with(); // every day defaults to closed.

		$rows = OfficeProfile\weekly_hours_summary_rows( $weekly );

		$this->assertSame(
			array( array( 'days' => '月〜日', 'value' => '定休日' ) ),
			$rows,
			'All 7 days closed must be one whole-week row, not a 7-item nakaguro list.'
		);
	}

	public function test_missing_data_for_every_day_returns_no_rows() {
		$weekly = array(); // No days at all.

		$rows = OfficeProfile\weekly_hours_summary_rows( $weekly );

		$this->assertSame( array(), $rows );
	}

	public function test_invalid_time_on_one_day_is_silently_omitted_not_guessed() {
		$weekly = $this->weekly_with(
			array(
				'mon' => $this->open( '09:00', '18:00' ),
				'tue' => array( 'closed' => false, 'open' => 'not-a-time', 'close' => '18:00' ),
				'wed' => $this->open( '09:00', '18:00' ),
			)
		);

		$rows = OfficeProfile\weekly_hours_summary_rows( $weekly );

		// Tuesday must not appear merged into any range, and must not
		// silently claim a fabricated schedule.
		foreach ( $rows as $row ) {
			$this->assertStringNotContainsString( '火', $row['days'] );
		}
	}

	public function test_single_open_day_is_not_treated_as_a_range() {
		$weekly = $this->weekly_with( array( 'wed' => $this->open( '09:00', '18:00' ) ) );

		$rows = OfficeProfile\weekly_hours_summary_rows( $weekly );

		$open_row = current(
			array_values( array_filter( $rows, static fn( $row ) => '09:00〜18:00' === $row['value'] ) )
		);

		$this->assertSame( '水', $open_row['days'] );
		$this->assertStringNotContainsString( '〜', $open_row['days'] );
	}

	public function test_get_weekly_hours_summary_reflects_office_profile_changes() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'business_hours' => array(
						'weekly' => array(
							'mon' => $this->open( '09:00', '18:00' ),
							'tue' => $this->open( '09:00', '18:00' ),
							'wed' => $this->open( '09:00', '18:00' ),
							'thu' => $this->open( '09:00', '18:00' ),
							'fri' => $this->open( '09:00', '18:00' ),
							'sat' => array( 'closed' => true ),
							'sun' => array( 'closed' => true ),
						),
					),
				)
			)
		);

		$this->assertSame(
			array(
				array( 'days' => '月〜金', 'value' => '09:00〜18:00' ),
				array( 'days' => '土・日', 'value' => '定休日' ),
			),
			OfficeProfile\get_weekly_hours_summary()
		);

		// Change Wednesday to closed -> summary must immediately follow.
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'business_hours' => array(
						'weekly' => array(
							'mon' => $this->open( '09:00', '18:00' ),
							'tue' => $this->open( '09:00', '18:00' ),
							'wed' => array( 'closed' => true ),
							'thu' => $this->open( '09:00', '18:00' ),
							'fri' => $this->open( '09:00', '18:00' ),
							'sat' => array( 'closed' => true ),
							'sun' => array( 'closed' => true ),
						),
					),
				)
			)
		);

		$this->assertSame(
			array(
				array( 'days' => '月〜火', 'value' => '09:00〜18:00' ),
				array( 'days' => '水', 'value' => '定休日' ),
				array( 'days' => '木〜金', 'value' => '09:00〜18:00' ),
				array( 'days' => '土・日', 'value' => '定休日' ),
			),
			OfficeProfile\get_weekly_hours_summary()
		);
	}

	public function test_render_business_hours_summary_block_escapes_and_self_hides_when_empty() {
		// No option ever saved -> get_defaults() -> every day closed -> the
		// ALL_CLOSED special case, not an empty self-hide. Force genuine
		// emptiness instead: EVERY day malformed (a single malformed day
		// alone would still leave the other six valid via
		// merge_with_defaults()'s per-day backfill, per get_office_profile()).
		$all_malformed = array();
		foreach ( OfficeProfile\WEEKDAYS as $day ) {
			$all_malformed[ $day ] = array(
				'closed' => false,
				'open'   => 'bad',
				'close'  => 'data',
			);
		}

		update_option(
			OfficeProfile\OPTION_NAME,
			array_merge(
				OfficeProfile\get_defaults(),
				array(
					'business_hours' => array(
						'weekly'     => $all_malformed,
						'exceptions' => array(),
					),
				)
			)
		);

		$html = OfficeProfile\render_business_hours_summary_block();

		$this->assertSame( '', $html );
	}

	public function test_render_business_hours_summary_block_renders_grouped_rows() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'business_hours' => array(
						'weekly' => array(
							'mon' => $this->open( '09:00', '18:00' ),
							'tue' => $this->open( '09:00', '18:00' ),
							'wed' => $this->open( '09:00', '18:00' ),
							'thu' => $this->open( '09:00', '18:00' ),
							'fri' => $this->open( '09:00', '18:00' ),
							'sat' => array( 'closed' => true ),
							'sun' => array( 'closed' => true ),
						),
					),
				)
			)
		);

		$html = OfficeProfile\render_business_hours_summary_block();

		$this->assertStringContainsString( 'wp-block-astrea-business-hours-summary', $html );
		$this->assertStringContainsString( '営業時間', $html );
		$this->assertStringContainsString( '月〜金', $html );
		$this->assertStringContainsString( '09:00〜18:00', $html );
		$this->assertStringContainsString( '土・日', $html );
		$this->assertStringContainsString( '定休日', $html );
	}

	public function test_render_business_hours_summary_block_escapes_html() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'business_hours' => array(
						'weekly' => array(
							'mon' => $this->open( '09:00', '18:00' ),
						),
					),
				)
			)
		);

		$html = OfficeProfile\render_business_hours_summary_block();

		$this->assertStringNotContainsString( '<script', $html );
	}
}
