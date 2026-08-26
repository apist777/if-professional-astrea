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
}
