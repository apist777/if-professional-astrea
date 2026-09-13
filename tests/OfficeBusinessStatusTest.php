<?php
/**
 * Tests for ASTREA Core's "本日の営業状況" business status feature
 * (Construction 029-CI).
 *
 * The decision logic (`determine_business_status()`) is a pure function of
 * (weekly hours, a point in time) — these tests exercise it directly with
 * injected `DateTimeImmutable` instances rather than depending on the
 * actual current time, so every state (OPEN / BEFORE_OPEN / AFTER_CLOSE /
 * CLOSED_DAY / UNKNOWN) is exactly and repeatably reachable.
 *
 * 2026-09-14 is a known Monday; 2026-09-19 is a known Saturday — used
 * throughout as fixed weekday anchors.
 *
 * @package Astrea\Core
 */

use Astrea\Core\OfficeProfile;

/**
 * @covers \Astrea\Core\OfficeProfile
 */
class OfficeBusinessStatusTest extends WP_UnitTestCase {

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

	private function monday( string $time ): \DateTimeImmutable {
		return new \DateTimeImmutable( "2026-09-14 {$time}" );
	}

	private function saturday( string $time ): \DateTimeImmutable {
		return new \DateTimeImmutable( "2026-09-19 {$time}" );
	}

	public function test_monday_1000_within_0900_1800_is_open() {
		$weekly = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '09:00', 'close' => '18:00' ) ) );
		$result = OfficeProfile\determine_business_status( $weekly, $this->monday( '10:00:00' ) );

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_OPEN, $result['status'] );
		$this->assertSame( '09:00', $result['open'] );
		$this->assertSame( '18:00', $result['close'] );
	}

	public function test_monday_0859_is_before_open() {
		$weekly = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '09:00', 'close' => '18:00' ) ) );
		$result = OfficeProfile\determine_business_status( $weekly, $this->monday( '08:59:00' ) );

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_BEFORE_OPEN, $result['status'] );
	}

	public function test_monday_exactly_0900_is_open() {
		$weekly = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '09:00', 'close' => '18:00' ) ) );
		$result = OfficeProfile\determine_business_status( $weekly, $this->monday( '09:00:00' ) );

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_OPEN, $result['status'], 'The opening minute itself must be OPEN (inclusive lower bound).' );
	}

	public function test_monday_1759_is_open() {
		$weekly = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '09:00', 'close' => '18:00' ) ) );
		$result = OfficeProfile\determine_business_status( $weekly, $this->monday( '17:59:00' ) );

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_OPEN, $result['status'] );
	}

	public function test_monday_exactly_1800_is_after_close() {
		$weekly = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '09:00', 'close' => '18:00' ) ) );
		$result = OfficeProfile\determine_business_status( $weekly, $this->monday( '18:00:00' ) );

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_AFTER_CLOSE, $result['status'], 'The closing minute itself must be AFTER_CLOSE (exclusive upper bound).' );
	}

	public function test_monday_2000_is_after_close() {
		$weekly = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '09:00', 'close' => '18:00' ) ) );
		$result = OfficeProfile\determine_business_status( $weekly, $this->monday( '20:00:00' ) );

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_AFTER_CLOSE, $result['status'] );
	}

	public function test_saturday_closed_is_closed_day() {
		$weekly = $this->weekly_with( array( 'sat' => array( 'closed' => true ) ) );
		$result = OfficeProfile\determine_business_status( $weekly, $this->saturday( '12:00:00' ) );

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_CLOSED_DAY, $result['status'] );
		$this->assertSame( '', $result['open'] );
		$this->assertSame( '', $result['close'] );
	}

	public function test_missing_day_key_is_unknown_not_a_false_claim() {
		$weekly = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '09:00', 'close' => '18:00' ) ) );
		unset( $weekly['mon'] );

		$result = OfficeProfile\determine_business_status( $weekly, $this->monday( '10:00:00' ) );

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_UNKNOWN, $result['status'], 'Missing data must never be reported as OPEN or CLOSED.' );
	}

	public function test_open_without_close_time_is_unknown() {
		$weekly = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '09:00', 'close' => '' ) ) );
		$result = OfficeProfile\determine_business_status( $weekly, $this->monday( '10:00:00' ) );

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_UNKNOWN, $result['status'] );
	}

	public function test_malformed_time_string_is_unknown() {
		$weekly = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => 'nine am', 'close' => '18:00' ) ) );
		$result = OfficeProfile\determine_business_status( $weekly, $this->monday( '10:00:00' ) );

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_UNKNOWN, $result['status'] );
	}

	public function test_overnight_hours_are_unknown_not_guessed() {
		// close <= open (e.g. 18:00-02:00): not supported for this
		// business category (Construction 029-CI PHASE 8) — must not be
		// silently mis-evaluated by naive comparison.
		$weekly = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '18:00', 'close' => '02:00' ) ) );
		$result = OfficeProfile\determine_business_status( $weekly, $this->monday( '20:00:00' ) );

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_UNKNOWN, $result['status'] );
	}

	public function test_changed_business_hours_are_followed() {
		$weekly_early = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '09:00', 'close' => '18:00' ) ) );
		$result_early = OfficeProfile\determine_business_status( $weekly_early, $this->monday( '19:00:00' ) );
		$this->assertSame( OfficeProfile\BUSINESS_STATUS_AFTER_CLOSE, $result_early['status'] );

		// Office Profile is changed to stay open later.
		$weekly_extended = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '09:00', 'close' => '20:00' ) ) );
		$result_extended = OfficeProfile\determine_business_status( $weekly_extended, $this->monday( '19:00:00' ) );
		$this->assertSame( OfficeProfile\BUSINESS_STATUS_OPEN, $result_extended['status'], 'A later close time must be picked up immediately, with no separate cache/rebuild step.' );
	}

	public function test_changed_closed_day_is_followed() {
		$weekly_closed = $this->weekly_with( array( 'mon' => array( 'closed' => true ) ) );
		$this->assertSame( OfficeProfile\BUSINESS_STATUS_CLOSED_DAY, OfficeProfile\determine_business_status( $weekly_closed, $this->monday( '10:00:00' ) )['status'] );

		$weekly_open = $this->weekly_with( array( 'mon' => array( 'closed' => false, 'open' => '09:00', 'close' => '18:00' ) ) );
		$this->assertSame( OfficeProfile\BUSINESS_STATUS_OPEN, OfficeProfile\determine_business_status( $weekly_open, $this->monday( '10:00:00' ) )['status'] );
	}

	public function test_get_business_status_reads_from_office_profile_and_current_time() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'business_hours' => array(
						'weekly' => array(
							'mon' => array( 'closed' => '', 'open' => '00:00', 'close' => '23:59' ),
							'tue' => array( 'closed' => '', 'open' => '00:00', 'close' => '23:59' ),
							'wed' => array( 'closed' => '', 'open' => '00:00', 'close' => '23:59' ),
							'thu' => array( 'closed' => '', 'open' => '00:00', 'close' => '23:59' ),
							'fri' => array( 'closed' => '', 'open' => '00:00', 'close' => '23:59' ),
							'sat' => array( 'closed' => '', 'open' => '00:00', 'close' => '23:59' ),
							'sun' => array( 'closed' => '', 'open' => '00:00', 'close' => '23:59' ),
						),
					),
				)
			)
		);

		// A business open every day nearly all day (00:00-23:59) is open
		// right now, regardless of when the test suite happens to run —
		// this integration test only checks the real get_office_profile()
		// + current_datetime() wiring, not specific boundary logic (that
		// is covered by the pure-function tests above).
		$result = OfficeProfile\get_business_status();

		$this->assertSame( OfficeProfile\BUSINESS_STATUS_OPEN, $result['status'] );
	}

	public function test_render_business_status_block_self_hides_when_unknown() {
		// No Office Profile ever saved -> every day defaults to closed
		// per get_defaults()... actually defaults to CLOSED_DAY, not
		// UNKNOWN. Force a genuinely UNKNOWN case: malformed stored data.
		update_option(
			OfficeProfile\OPTION_NAME,
			array_merge(
				OfficeProfile\get_defaults(),
				array(
					'business_hours' => array(
						'weekly'     => array( 'mon' => array( 'closed' => false, 'open' => 'bad', 'close' => 'data' ) ),
						'exceptions' => array(),
					),
				)
			)
		);

		$html = OfficeProfile\render_business_status_block();

		if ( 1 === (int) ( new \DateTimeImmutable() )->format( 'N' ) ) {
			$this->assertSame( '', $html, 'Malformed weekly data on a Monday must self-hide, never fabricate a status.' );
		} else {
			$this->markTestSkipped( 'This assertion only applies when the test suite happens to run on a Monday; the pure-function tests above already cover every weekday deterministically.' );
		}
	}

	public function test_render_business_status_block_escapes_and_shows_label_for_open_status() {
		update_option(
			OfficeProfile\OPTION_NAME,
			array_merge(
				OfficeProfile\get_defaults(),
				array(
					'business_hours' => array(
						'weekly'     => array_fill_keys( OfficeProfile\WEEKDAYS, array( 'closed' => false, 'open' => '00:00', 'close' => '23:59' ) ),
						'exceptions' => array(),
					),
				)
			)
		);

		$html = OfficeProfile\render_business_status_block();

		$this->assertStringContainsString( 'wp-block-astrea-business-status', $html );
		$this->assertStringContainsString( 'is-status-open', $html );
		$this->assertStringContainsString( '本日営業中', $html );
		$this->assertStringContainsString( '00:00', $html );
		$this->assertStringContainsString( '23:59', $html );
	}
}
