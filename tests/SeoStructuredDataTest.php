<?php
/**
 * Tests for ASTREA Core's Organization/Person/BreadcrumbList JSON-LD
 * generation (Construction Order 006, Decision 026).
 *
 * Real end-to-end <head> output (across Home/Service/Professional/FAQ
 * pages, plus SEO Plugin coexistence) is verified against a real running
 * site in tools/ci/smoke-test.sh; this file covers the pure data-building
 * functions.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Seo;
use Astrea\Core\OfficeProfile;
use Astrea\Core\ProfessionalProfile;

/**
 * @covers \Astrea\Core\Seo
 */
class SeoStructuredDataTest extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( OfficeProfile\OPTION_NAME );
		parent::tear_down();
	}

	private function create_professional( array $args = array() ): int {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => ProfessionalProfile\POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'テスト専門家',
				),
				$args
			)
		);
	}

	// -- Organization -----------------------------------------------------------

	public function test_organization_json_ld_is_null_without_office_name() {
		$this->assertNull( Seo\build_organization_json_ld() );
	}

	public function test_organization_json_ld_includes_basic_fields() {
		$sanitized = OfficeProfile\sanitize(
			array(
				'office_name' => 'テスト法律事務所',
				'address'     => '東京都千代田区1-1-1',
				'phone'       => '03-1234-5678',
			)
		);
		update_option( OfficeProfile\OPTION_NAME, $sanitized );

		$data = Seo\build_organization_json_ld();

		$this->assertSame( 'Organization', $data['@type'] );
		$this->assertSame( 'テスト法律事務所', $data['name'] );
		$this->assertSame( '東京都千代田区1-1-1', $data['address']['streetAddress'] );
		$this->assertSame( '03-1234-5678', $data['telephone'] );
		$this->assertArrayNotHasKey( 'employee', $data );
		$this->assertArrayNotHasKey( 'openingHoursSpecification', $data );
	}

	public function test_organization_json_ld_includes_employees() {
		$sanitized = OfficeProfile\sanitize( array( 'office_name' => 'テスト事務所' ) );
		update_option( OfficeProfile\OPTION_NAME, $sanitized );

		$this->create_professional(
			array( 'post_title' => '山田太郎' )
		);
		$id = ProfessionalProfile\get_profiles()[0]['id'];
		update_post_meta( $id, ProfessionalProfile\META_QUALIFICATION, '弁護士' );

		$data = Seo\build_organization_json_ld();

		$this->assertCount( 1, $data['employee'] );
		$this->assertSame( 'Person', $data['employee'][0]['@type'] );
		$this->assertSame( '山田太郎', $data['employee'][0]['name'] );
		$this->assertSame( '弁護士', $data['employee'][0]['jobTitle'] );
	}

	public function test_representative_flag_is_not_leaked_into_json_ld() {
		$sanitized = OfficeProfile\sanitize( array( 'office_name' => 'テスト事務所' ) );
		update_option( OfficeProfile\OPTION_NAME, $sanitized );

		$id = $this->create_professional( array( 'post_title' => '代表太郎' ) );
		update_post_meta( $id, ProfessionalProfile\META_IS_REPRESENTATIVE, true );

		$data = Seo\build_organization_json_ld();

		$this->assertArrayNotHasKey( 'is_representative', $data['employee'][0] );
		$this->assertArrayNotHasKey( 'representative', $data['employee'][0] );
	}

	public function test_multiple_professionals_are_all_listed_as_employees() {
		$sanitized = OfficeProfile\sanitize( array( 'office_name' => 'テスト事務所' ) );
		update_option( OfficeProfile\OPTION_NAME, $sanitized );

		$this->create_professional( array( 'post_title' => 'A' ) );
		$this->create_professional( array( 'post_title' => 'B' ) );
		$this->create_professional( array( 'post_title' => 'C' ) );

		$data = Seo\build_organization_json_ld();

		$this->assertCount( 3, $data['employee'] );
	}

	// -- Opening hours -------------------------------------------------------

	public function test_opening_hours_excludes_closed_days() {
		$weekly = array(
			'mon' => array(
				'closed' => false,
				'open'   => '09:00',
				'close'  => '18:00',
			),
			'tue' => array(
				'closed' => true,
				'open'   => '',
				'close'  => '',
			),
		);

		$specs = Seo\build_opening_hours_specification( $weekly );

		$this->assertCount( 1, $specs );
		$this->assertSame( 'https://schema.org/Monday', $specs[0]['dayOfWeek'] );
	}

	public function test_opening_hours_excludes_incomplete_entries() {
		$weekly = array(
			'mon' => array(
				'closed' => false,
				'open'   => '',
				'close'  => '',
			),
		);

		$this->assertSame( array(), Seo\build_opening_hours_specification( $weekly ) );
	}

	public function test_opening_hours_returns_empty_for_empty_input() {
		$this->assertSame( array(), Seo\build_opening_hours_specification( array() ) );
	}

	// -- BreadcrumbList -----------------------------------------------------

	public function test_breadcrumb_json_ld_is_null_on_front_page() {
		$this->go_to( home_url( '/' ) );
		update_option( 'show_on_front', 'posts' );

		$this->assertNull( Seo\build_breadcrumb_json_ld() );
	}

	public function test_breadcrumb_json_ld_matches_visual_breadcrumb() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'テストページ' ) );
		$this->go_to( get_permalink( $page_id ) );

		$json_ld = Seo\build_breadcrumb_json_ld();
		$visual  = Seo\render_breadcrumb_block();

		$this->assertSame( 'BreadcrumbList', $json_ld['@type'] );
		$this->assertStringContainsString( 'テストページ', $visual );
		$this->assertSame( 'テストページ', end( $json_ld['itemListElement'] )['name'] );
	}

	public function test_json_ld_is_valid_and_scriptable() {
		$sanitized = OfficeProfile\sanitize( array( 'office_name' => 'テスト事務所<script>' ) );
		update_option( OfficeProfile\OPTION_NAME, $sanitized );

		$data = Seo\build_organization_json_ld();
		$json = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );

		$this->assertStringNotContainsString( '</script>', $json );
		$this->assertIsArray( json_decode( $json, true ) );
	}
}
