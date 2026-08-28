<?php
/**
 * Tests for ASTREA Core's Office Profile feature (Construction Order 002).
 *
 * These are Core-only integration tests (WP_UnitTestCase, real WordPress
 * options API, real Settings API registration). They intentionally do NOT
 * load the ASTREA Theme or exercise Block Bindings end to end — that full
 * vertical slice (save -> Block Binding -> Theme render, including the
 * Core-inactive/deactivate/reactivate states) is covered by
 * tools/ci/smoke-test.sh against a real running site, which is a more
 * honest way to test that boundary than trying to fake it here.
 *
 * @package Astrea\Core
 */

use Astrea\Core\OfficeProfile;
use Astrea\Core\Bindings;

/**
 * @covers \Astrea\Core\OfficeProfile
 */
class OfficeProfileTest extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( OfficeProfile\OPTION_NAME );
		parent::tear_down();
	}

	public function test_defaults_are_returned_when_option_was_never_saved() {
		$profile = OfficeProfile\get_office_profile();

		$this->assertSame( '', $profile['office_name'] );
		$this->assertSame( '', $profile['phone'] );
		$this->assertSame( 2, $profile['schema_version'] );

		foreach ( OfficeProfile\WEEKDAYS as $day ) {
			$this->assertTrue( $profile['business_hours']['weekly'][ $day ]['closed'], "Day {$day} should default to closed" );
		}

		$this->assertSame( array(), $profile['business_hours']['exceptions'] );
		$this->assertSame( array(), $profile['sns_links'] );
	}

	public function test_sanitize_strips_tags_from_text_fields() {
		$result = OfficeProfile\sanitize(
			array(
				'office_name' => '<script>alert(1)</script>テスト事務所',
				'address'     => "東京都\n千代田区", // sanitize_text_field also collapses newlines.
			)
		);

		$this->assertSame( 'テスト事務所', $result['office_name'] );
		$this->assertSame( '東京都 千代田区', $result['address'] );
	}

	public function test_sanitize_does_not_process_representative_name_anymore() {
		// Decision 023: representative_name is retired. Even if somehow
		// submitted (e.g. a stale cached form), sanitize() must not revive
		// it as an active field.
		$result = OfficeProfile\sanitize( array( 'representative_name' => '山田太郎' ) );

		$this->assertArrayNotHasKey( 'representative_name', $result );
	}

	public function test_sanitize_never_touches_legacy_representative_name() {
		update_option(
			OfficeProfile\OPTION_NAME,
			array_merge(
				OfficeProfile\get_defaults(),
				array( OfficeProfile\LEGACY_REPRESENTATIVE_NAME_KEY => '旧・代表者名' )
			)
		);

		// Submitting the form (which no longer has a representative field
		// at all) must not wipe the preserved legacy value.
		$result = OfficeProfile\sanitize( array( 'office_name' => '事務所名のみ更新' ) );

		$this->assertSame( '旧・代表者名', $result[ OfficeProfile\LEGACY_REPRESENTATIVE_NAME_KEY ] );
	}

	public function test_migration_preserves_v1_representative_name_as_legacy() {
		update_option(
			OfficeProfile\OPTION_NAME,
			array(
				'schema_version'      => 1,
				'office_name'         => '旧事務所',
				'representative_name' => '旧代表者',
				'address'             => '',
				'phone'               => '',
			)
		);

		OfficeProfile\maybe_migrate();

		$raw = get_option( OfficeProfile\OPTION_NAME );
		$this->assertSame( 2, $raw['schema_version'] );
		$this->assertArrayNotHasKey( 'representative_name', $raw );
		$this->assertSame( '旧代表者', $raw[ OfficeProfile\LEGACY_REPRESENTATIVE_NAME_KEY ] );
	}

	public function test_migration_is_a_noop_when_nothing_was_ever_saved() {
		delete_option( OfficeProfile\OPTION_NAME );

		OfficeProfile\maybe_migrate();

		$this->assertFalse( get_option( OfficeProfile\OPTION_NAME, false ), 'A site that never saved Office Profile must not gain a new option row from migration alone.' );
	}

	public function test_migration_does_not_run_twice() {
		update_option(
			OfficeProfile\OPTION_NAME,
			array(
				'schema_version'      => 1,
				'representative_name' => '旧代表者',
			)
		);

		OfficeProfile\maybe_migrate();
		// Simulate the site owner manually clearing the legacy value after migrating by hand.
		$after_first = get_option( OfficeProfile\OPTION_NAME );
		$after_first[ OfficeProfile\LEGACY_REPRESENTATIVE_NAME_KEY ] = '';
		update_option( OfficeProfile\OPTION_NAME, $after_first );

		OfficeProfile\maybe_migrate();

		$this->assertSame( '', get_option( OfficeProfile\OPTION_NAME )[ OfficeProfile\LEGACY_REPRESENTATIVE_NAME_KEY ], 'A second migration run must not resurrect a value the site owner already cleared.' );
	}

	public function test_sanitize_accepts_a_well_formed_phone_number() {
		$result = OfficeProfile\sanitize( array( 'phone' => '03-1234-5678' ) );

		$this->assertSame( '03-1234-5678', $result['phone'] );
	}

	public function test_sanitize_rejects_invalid_phone_and_keeps_previous_value() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize( array( 'phone' => '03-1111-2222' ) )
		);

		$result = OfficeProfile\sanitize( array( 'phone' => 'call me maybe' ) );

		$this->assertSame( '03-1111-2222', $result['phone'], 'Invalid phone must roll back to the previously stored value.' );
	}

	public function test_sanitize_accepts_valid_weekly_hours() {
		$result = OfficeProfile\sanitize(
			array(
				'business_hours' => array(
					'weekly' => array(
						'mon' => array(
							'closed' => '',
							'open'   => '09:00',
							'close'  => '18:00',
						),
					),
				),
			)
		);

		$this->assertFalse( $result['business_hours']['weekly']['mon']['closed'] );
		$this->assertSame( '09:00', $result['business_hours']['weekly']['mon']['open'] );
		$this->assertSame( '18:00', $result['business_hours']['weekly']['mon']['close'] );
	}

	public function test_sanitize_rejects_invalid_time_and_keeps_previous_value() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'business_hours' => array(
						'weekly' => array(
							'mon' => array(
								'open'  => '09:00',
								'close' => '18:00',
							),
						),
					),
				)
			)
		);

		$result = OfficeProfile\sanitize(
			array(
				'business_hours' => array(
					'weekly' => array(
						'mon' => array(
							'open'  => '25:99',
							'close' => '18:00',
						),
					),
				),
			)
		);

		$this->assertSame( '09:00', $result['business_hours']['weekly']['mon']['open'], 'Invalid time must roll back the whole day to its previous value.' );
		$this->assertSame( '18:00', $result['business_hours']['weekly']['mon']['close'] );
	}

	public function test_sanitize_clears_open_close_when_day_marked_closed() {
		$result = OfficeProfile\sanitize(
			array(
				'business_hours' => array(
					'weekly' => array(
						'mon' => array(
							'closed' => '1',
							'open'   => '09:00',
							'close'  => '18:00',
						),
					),
				),
			)
		);

		$this->assertTrue( $result['business_hours']['weekly']['mon']['closed'] );
		$this->assertSame( '', $result['business_hours']['weekly']['mon']['open'] );
		$this->assertSame( '', $result['business_hours']['weekly']['mon']['close'] );
	}

	public function test_sanitize_exceptions_skips_fully_empty_rows() {
		$result = OfficeProfile\sanitize(
			array(
				'business_hours' => array(
					'exceptions' => array(
						array(
							'label'      => '',
							'start_date' => '',
							'end_date'   => '',
						),
					),
				),
			)
		);

		$this->assertSame( array(), $result['business_hours']['exceptions'] );
	}

	public function test_sanitize_exceptions_accepts_a_valid_range() {
		$result = OfficeProfile\sanitize(
			array(
				'business_hours' => array(
					'exceptions' => array(
						array(
							'label'      => '年末年始',
							'start_date' => '2026-12-29',
							'end_date'   => '2027-01-03',
						),
					),
				),
			)
		);

		$this->assertCount( 1, $result['business_hours']['exceptions'] );
		$this->assertSame( '年末年始', $result['business_hours']['exceptions'][0]['label'] );
	}

	public function test_sanitize_exceptions_drops_row_with_end_before_start() {
		$result = OfficeProfile\sanitize(
			array(
				'business_hours' => array(
					'exceptions' => array(
						array(
							'label'      => '不正な期間',
							'start_date' => '2027-01-03',
							'end_date'   => '2026-12-29',
						),
					),
				),
			)
		);

		$this->assertSame( array(), $result['business_hours']['exceptions'] );
	}

	public function test_sanitize_exceptions_drops_row_with_invalid_date() {
		$result = OfficeProfile\sanitize(
			array(
				'business_hours' => array(
					'exceptions' => array(
						array(
							'label'      => '不正な日付',
							'start_date' => 'not-a-date',
							'end_date'   => '',
						),
					),
				),
			)
		);

		$this->assertSame( array(), $result['business_hours']['exceptions'] );
	}

	public function test_sanitize_sns_links_accepts_a_valid_https_url() {
		$result = OfficeProfile\sanitize(
			array(
				'sns_links' => array(
					array(
						'label' => 'X',
						'url'   => 'https://x.com/example',
					),
				),
			)
		);

		$this->assertCount( 1, $result['sns_links'] );
		$this->assertSame( 'https://x.com/example', $result['sns_links'][0]['url'] );
	}

	public function test_sanitize_sns_links_rejects_javascript_scheme() {
		$result = OfficeProfile\sanitize(
			array(
				'sns_links' => array(
					array(
						'label' => 'evil',
						'url'   => 'javascript:alert(1)',
					),
				),
			)
		);

		$this->assertSame( array(), $result['sns_links'], 'A non-http(s) URL must be dropped entirely.' );
	}

	public function test_sanitize_sns_links_skips_fully_empty_rows() {
		$result = OfficeProfile\sanitize(
			array(
				'sns_links' => array(
					array(
						'label' => '',
						'url'   => '',
					),
				),
			)
		);

		$this->assertSame( array(), $result['sns_links'] );
	}

	public function test_admin_page_denies_non_admin_users() {
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$this->expectException( WPDieException::class );

		OfficeProfile\Admin\render_page();
	}

	public function test_admin_page_renders_for_admin_users() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		OfficeProfile\Admin\register_fields();

		ob_start();
		OfficeProfile\Admin\render_page();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'astrea_core_office_profile[office_name]', $html );
	}

	public function test_block_binding_returns_value_for_allowed_key() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize( array( 'office_name' => 'バインディング事務所' ) )
		);

		$value = Bindings\get_bound_value( array( 'key' => 'office_name' ), null, 'content' );

		$this->assertSame( 'バインディング事務所', $value );
	}

	public function test_block_binding_returns_null_for_unknown_key() {
		$value = Bindings\get_bound_value( array( 'key' => 'business_hours' ), null, 'content' );

		$this->assertNull( $value, 'Structured/collection fields must not be exposed through this scalar binding source.' );
	}

	public function test_block_binding_returns_null_when_value_is_unconfigured() {
		$value = Bindings\get_bound_value( array( 'key' => 'office_name' ), null, 'content' );

		$this->assertNull( $value, 'An empty/unconfigured value must fall back to the block\'s static content, not override it with an empty string.' );
	}

	public function test_block_binding_phone_tel_converts_phone_to_tel_uri() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize( array( 'phone' => '03-1234-5678' ) )
		);

		$value = Bindings\get_bound_value( array( 'key' => 'phone_tel' ), null, 'url' );

		$this->assertSame( 'tel:03-1234-5678', $value );
	}

	public function test_block_binding_phone_tel_strips_non_tel_safe_characters() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize( array( 'phone' => '03（1234）5678' ) )
		);

		$value = Bindings\get_bound_value( array( 'key' => 'phone_tel' ), null, 'url' );

		$this->assertSame( 'tel:0312345678', $value );
	}

	public function test_block_binding_phone_tel_returns_null_when_no_phone_is_set() {
		$value = Bindings\get_bound_value( array( 'key' => 'phone_tel' ), null, 'url' );

		$this->assertNull( $value );
	}

	public function test_deactivate_does_not_delete_office_profile_data() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize( array( 'office_name' => '削除されないはずの事務所' ) )
		);

		\Astrea\Core\deactivate();

		$profile = OfficeProfile\get_office_profile();
		$this->assertSame( '削除されないはずの事務所', $profile['office_name'], 'Decision 019: deactivation must never delete Core-owned data.' );
	}

	// -- Decision 023: legacy representative_name admin notice --------------

	public function test_legacy_representative_notice_shows_when_unresolved() {
		set_current_screen( 'toplevel_page_astrea-core' );
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		update_option(
			OfficeProfile\OPTION_NAME,
			array_merge( OfficeProfile\get_defaults(), array( OfficeProfile\LEGACY_REPRESENTATIVE_NAME_KEY => '旧代表太郎' ) )
		);

		ob_start();
		\Astrea\Core\OfficeProfile\Admin\maybe_render_legacy_representative_notice();
		$html = ob_get_clean();

		$this->assertStringContainsString( '旧代表太郎', $html );
	}

	public function test_legacy_representative_notice_hidden_once_someone_is_flagged_representative() {
		set_current_screen( 'toplevel_page_astrea-core' );
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		update_option(
			OfficeProfile\OPTION_NAME,
			array_merge( OfficeProfile\get_defaults(), array( OfficeProfile\LEGACY_REPRESENTATIVE_NAME_KEY => '旧代表太郎' ) )
		);
		$prof_id = self::factory()->post->create(
			array(
				'post_type'   => \Astrea\Core\ProfessionalProfile\POST_TYPE,
				'post_status' => 'publish',
			)
		);
		update_post_meta( $prof_id, \Astrea\Core\ProfessionalProfile\META_IS_REPRESENTATIVE, true );

		ob_start();
		\Astrea\Core\OfficeProfile\Admin\maybe_render_legacy_representative_notice();
		$html = ob_get_clean();

		$this->assertSame( '', $html, 'The notice must disappear on its own once a Professional Profile is flagged as representative.' );
	}

	public function test_legacy_representative_notice_hidden_when_no_legacy_value() {
		set_current_screen( 'toplevel_page_astrea-core' );
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		ob_start();
		\Astrea\Core\OfficeProfile\Admin\maybe_render_legacy_representative_notice();
		$html = ob_get_clean();

		$this->assertSame( '', $html );
	}

	// -- astrea/office-hours Dynamic Block (Construction Order 011) --

	public function test_hours_block_self_hides_when_nothing_configured() {
		$this->assertSame( '', OfficeProfile\render_hours_block() );
	}

	public function test_hours_block_shows_empty_message_when_set() {
		$html = OfficeProfile\render_hours_block( array( 'emptyMessage' => '営業時間は準備中です。' ) );

		$this->assertStringContainsString( '営業時間は準備中です。', $html );
	}

	public function test_hours_block_heading_is_not_emitted_alone_when_nothing_configured() {
		$html = OfficeProfile\render_hours_block( array( 'heading' => '営業時間' ) );

		$this->assertSame( '', $html, 'A heading must never be emitted alone when no business hours are configured.' );
	}

	public function test_hours_block_shows_configured_open_day() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'business_hours' => array(
						'weekly' => array(
							'mon' => array(
								'closed' => '',
								'open'   => '09:00',
								'close'  => '18:00',
							),
						),
					),
				)
			)
		);

		$html = OfficeProfile\render_hours_block( array( 'heading' => '営業時間' ) );

		$this->assertStringContainsString( '<h2>営業時間</h2>', $html );
		$this->assertStringContainsString( '月曜日', $html );
		$this->assertStringContainsString( '09:00〜18:00', $html );
	}

	public function test_hours_block_shows_closed_days_as_closed() {
		// A real admin submission always includes all 7 days (the weekly
		// table is a fixed repeater, not a variable one) — explicitly mark
		// every day but Monday closed here so this matches that shape,
		// rather than relying on sanitize()'s handling of a day missing
		// from the submission entirely (a different, unrelated case).
		$weekly = array();
		foreach ( OfficeProfile\WEEKDAYS as $day ) {
			$weekly[ $day ] = ( 'mon' === $day )
				? array( 'closed' => '', 'open' => '09:00', 'close' => '18:00' )
				: array( 'closed' => '1', 'open' => '', 'close' => '' );
		}

		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize( array( 'business_hours' => array( 'weekly' => $weekly ) ) )
		);

		$html = OfficeProfile\render_hours_block();

		$this->assertStringContainsString( '日曜日', $html );
		$this->assertStringContainsString( '休業', $html );
	}

	public function test_hours_block_shown_when_only_an_exception_is_configured() {
		// Every weekday still defaults to closed, but a closure exception
		// alone must still count as "configured" — a site that is only
		// noting a temporary closure has real information to show.
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'business_hours' => array(
						'exceptions' => array(
							array(
								'label'      => '年末年始',
								'start_date' => '2026-12-29',
								'end_date'   => '2027-01-03',
							),
						),
					),
				)
			)
		);

		$html = OfficeProfile\render_hours_block();

		$this->assertStringContainsString( '年末年始', $html );
		$this->assertStringContainsString( '2026年12月29日', $html );
		$this->assertStringContainsString( '2027年1月3日', $html );
	}

	public function test_hours_block_escapes_exception_label() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'business_hours' => array(
						'exceptions' => array(
							array(
								'label'      => '<script>alert(1)</script>臨時休業',
								'start_date' => '',
								'end_date'   => '',
							),
						),
					),
				)
			)
		);

		$html = OfficeProfile\render_hours_block();

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '臨時休業', $html );
	}

	// -- astrea/office-sns Dynamic Block (Construction Order 011) --

	public function test_sns_block_self_hides_with_zero_links() {
		$this->assertSame( '', OfficeProfile\render_sns_block() );
	}

	public function test_sns_block_shows_empty_message_when_set() {
		$html = OfficeProfile\render_sns_block( array( 'emptyMessage' => 'SNSは準備中です。' ) );

		$this->assertStringContainsString( 'SNSは準備中です。', $html );
	}

	public function test_sns_block_heading_is_not_emitted_alone_with_zero_links() {
		$html = OfficeProfile\render_sns_block( array( 'heading' => 'SNS' ) );

		$this->assertSame( '', $html, 'A heading must never be emitted alone when there are zero SNS links.' );
	}

	public function test_sns_block_renders_link_with_label() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'sns_links' => array(
						array(
							'label' => 'X',
							'url'   => 'https://x.com/example',
						),
					),
				)
			)
		);

		$html = OfficeProfile\render_sns_block( array( 'heading' => 'SNS' ) );

		$this->assertStringContainsString( '<h2>SNS</h2>', $html );
		$this->assertStringContainsString( '<a href="https://x.com/example">X</a>', $html );
	}

	public function test_sns_block_falls_back_to_url_when_label_empty() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'sns_links' => array(
						array(
							'label' => '',
							'url'   => 'https://x.com/example',
						),
					),
				)
			)
		);

		$html = OfficeProfile\render_sns_block();

		$this->assertStringContainsString( '<a href="https://x.com/example">https://x.com/example</a>', $html );
	}

	public function test_sns_block_renders_multiple_links() {
		update_option(
			OfficeProfile\OPTION_NAME,
			OfficeProfile\sanitize(
				array(
					'sns_links' => array(
						array(
							'label' => 'X',
							'url'   => 'https://x.com/example',
						),
						array(
							'label' => 'Facebook',
							'url'   => 'https://facebook.com/example',
						),
					),
				)
			)
		);

		$html = OfficeProfile\render_sns_block();

		$this->assertStringContainsString( 'X', $html );
		$this->assertStringContainsString( 'Facebook', $html );
	}

	public function test_sns_block_defends_against_a_javascript_scheme_link_even_if_stored_raw() {
		// sanitize_sns_links() already drops javascript:/data: URLs at save
		// time (see the tests above), so a real site can never store one via
		// the settings form — this bypasses sanitize() entirely (writing
		// directly to the option, as if the row had been corrupted some
		// other way) to prove the render path's own esc_url() call is an
		// independent, second line of defense, not merely relying on the
		// save-time check.
		update_option(
			OfficeProfile\OPTION_NAME,
			array_merge(
				OfficeProfile\get_defaults(),
				array( 'sns_links' => array( array( 'label' => 'evil', 'url' => 'javascript:alert(1)' ) ) )
			)
		);

		$html = OfficeProfile\render_sns_block();

		$this->assertStringNotContainsString( 'javascript:', $html );
	}
}
