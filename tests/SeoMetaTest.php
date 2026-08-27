<?php
/**
 * Tests for ASTREA Core's meta description / OGP generation and SEO
 * settings sanitization (Construction Order 006).
 *
 * @package Astrea\Core
 */

use Astrea\Core\Seo;
use Astrea\Core\Service;
use Astrea\Core\Faq;
use Astrea\Core\Ga4;

/**
 * @covers \Astrea\Core\Seo
 */
class SeoMetaTest extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( Seo\SETTINGS_OPTION );
		update_option( 'blogdescription', '' );
		parent::tear_down();
	}

	// -- generate_description() ----------------------------------------------

	public function test_description_uses_excerpt_for_singular_content() {
		$post_id = self::factory()->post->create(
			array(
				'post_excerpt' => 'これはテストの抜粋です。',
			)
		);
		$this->go_to( get_permalink( $post_id ) );

		$this->assertSame( 'これはテストの抜粋です。', Seo\generate_description() );
	}

	public function test_description_falls_back_to_taxonomy_description() {
		$term = self::factory()->term->create_and_get(
			array(
				'taxonomy'    => Faq\TAXONOMY,
				'description' => 'カテゴリの説明文です。',
			)
		);
		$this->go_to( get_term_link( $term ) );

		$this->assertSame( 'カテゴリの説明文です。', Seo\generate_description() );
	}

	public function test_description_falls_back_to_post_type_archive_description() {
		$this->go_to( get_post_type_archive_link( Service\POST_TYPE ) );

		$this->assertSame( '取扱業務の一覧です。', Seo\generate_description() );
	}

	public function test_description_falls_back_to_tagline_when_nothing_else_available() {
		update_option( 'blogdescription', 'サイトのタグラインです。' );
		$this->go_to( home_url( '/?s=nonexistent' ) );

		$this->assertSame( 'サイトのタグラインです。', Seo\generate_description() );
	}

	public function test_description_is_empty_when_nothing_available() {
		update_option( 'blogdescription', '' );
		$this->go_to( home_url( '/?s=nonexistent' ) );

		$this->assertSame( '', Seo\generate_description() );
	}

	// -- truncate_description() -----------------------------------------------

	public function test_truncate_leaves_short_text_untouched() {
		$this->assertSame( '短い説明文', Seo\truncate_description( '短い説明文' ) );
	}

	public function test_truncate_cuts_long_text_to_max_length() {
		$long = str_repeat( 'あ', 300 );

		$this->assertSame( Seo\DESCRIPTION_MAX_LENGTH, mb_strlen( Seo\truncate_description( $long ) ) );
	}

	public function test_truncate_collapses_whitespace() {
		$this->assertSame( 'a b', Seo\truncate_description( "a\n\n  b" ) );
	}

	// -- resolve_ogp_image_url() -----------------------------------------------

	// create_upload_object() (rather than fileless create_object()) is used
	// here because resolve_ogp_image_url() calls wp_get_attachment_image_url(),
	// which requires a real file to resolve a URL from — the same
	// fileless-attachment pitfall documented for set_post_thumbnail() in
	// Construction Order 003's report.
	public function test_ogp_image_prefers_featured_image_over_site_fallback() {
		$featured_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.jpg' );
		$post_id     = self::factory()->post->create();
		update_post_meta( $post_id, '_thumbnail_id', $featured_id );
		$this->go_to( get_permalink( $post_id ) );

		$fallback_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		update_option( Seo\SETTINGS_OPTION, array( 'og_image_id' => $fallback_id ) );

		$this->assertStringContainsString( 'test-image', Seo\resolve_ogp_image_url() );
	}

	public function test_ogp_image_falls_back_to_site_wide_image() {
		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );

		$fallback_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		update_option( Seo\SETTINGS_OPTION, array( 'og_image_id' => $fallback_id ) );

		$this->assertStringContainsString( 'canola', Seo\resolve_ogp_image_url() );
	}

	public function test_ogp_image_is_null_when_nothing_available() {
		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertNull( Seo\resolve_ogp_image_url() );
	}

	// -- Settings sanitization -------------------------------------------------

	public function test_sanitize_settings_rejects_nonexistent_attachment() {
		$sanitized = Seo\sanitize_settings( array( 'og_image_id' => 999999 ) );

		$this->assertSame( 0, $sanitized['og_image_id'] );
	}

	public function test_sanitize_settings_accepts_real_attachment() {
		$attachment_id = self::factory()->attachment->create_object( array( 'file' => 'test.png' ) );

		$sanitized = Seo\sanitize_settings( array( 'og_image_id' => $attachment_id ) );

		$this->assertSame( $attachment_id, $sanitized['og_image_id'] );
	}

	public function test_sanitize_settings_accepts_valid_verification_code() {
		$sanitized = Seo\sanitize_settings( array( 'search_console_verification' => 'AbC123-_+/=' ) );

		$this->assertSame( 'AbC123-_+/=', $sanitized['search_console_verification'] );
	}

	public function test_sanitize_settings_rejects_invalid_verification_code() {
		$sanitized = Seo\sanitize_settings( array( 'search_console_verification' => '<script>alert(1)</script>' ) );

		$this->assertSame( '', $sanitized['search_console_verification'] );
	}

	public function test_sanitize_settings_rejects_verification_code_with_quotes() {
		$sanitized = Seo\sanitize_settings( array( 'search_console_verification' => 'abc" onmouseover="alert(1)' ) );

		$this->assertSame( '', $sanitized['search_console_verification'] );
	}

	// -- GA4 measurement ID (Construction Order 009) --------------------------

	public function test_sanitize_settings_accepts_valid_ga4_measurement_id() {
		$sanitized = Seo\sanitize_settings( array( 'ga4_measurement_id' => 'G-ABCD123456' ) );

		$this->assertSame( 'G-ABCD123456', $sanitized['ga4_measurement_id'] );
	}

	public function test_sanitize_settings_uppercases_ga4_measurement_id() {
		$sanitized = Seo\sanitize_settings( array( 'ga4_measurement_id' => 'g-abcd123456' ) );

		$this->assertSame( 'G-ABCD123456', $sanitized['ga4_measurement_id'] );
	}

	public function test_sanitize_settings_rejects_malformed_ga4_measurement_id() {
		$sanitized = Seo\sanitize_settings( array( 'ga4_measurement_id' => 'UA-12345-1' ) );

		$this->assertSame( '', $sanitized['ga4_measurement_id'] );
	}

	public function test_sanitize_settings_rejects_ga4_measurement_id_with_script_injection() {
		$sanitized = Seo\sanitize_settings( array( 'ga4_measurement_id' => 'G-ABC"><script>alert(1)</script>' ) );

		$this->assertSame( '', $sanitized['ga4_measurement_id'] );
	}

	public function test_ga4_tag_is_not_output_when_measurement_id_is_empty() {
		ob_start();
		Ga4\output_ga4_tag();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_ga4_tag_is_output_when_measurement_id_is_set() {
		update_option( Seo\SETTINGS_OPTION, array( 'ga4_measurement_id' => 'G-ABCD123456' ) );

		ob_start();
		Ga4\output_ga4_tag();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'G-ABCD123456', $output );
		$this->assertStringContainsString( 'googletagmanager.com/gtag/js', $output );
	}

	public function test_ga4_tag_is_suppressed_when_known_analytics_plugin_active() {
		update_option( Seo\SETTINGS_OPTION, array( 'ga4_measurement_id' => 'G-ABCD123456' ) );
		update_option( 'active_plugins', array( 'google-site-kit/google-site-kit.php' ) );

		ob_start();
		Ga4\output_ga4_tag();
		$output = ob_get_clean();

		$this->assertSame( '', $output );

		update_option( 'active_plugins', array() );
	}
}
