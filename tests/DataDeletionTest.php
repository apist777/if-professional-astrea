<?php
/**
 * Tests for ASTREA Core's explicit complete-data-deletion action
 * (Construction Order 009, Decision 019).
 *
 * The confirmation form's Capability/Nonce/checkbox/phrase gate
 * (handle_delete()) is an admin-post handler that calls wp_die()/exit — see
 * tools/ci/smoke-test.sh for its real end-to-end HTTP verification. This
 * file covers the pure data-layer function, delete_all_core_data(), and
 * the confirmation-phrase constant it depends on.
 *
 * @package Astrea\Core
 */

use Astrea\Core\DataDeletion;
use Astrea\Core\OfficeProfile;
use Astrea\Core\ProfessionalProfile;
use Astrea\Core\Service;
use Astrea\Core\Price;
use Astrea\Core\Faq;
use Astrea\Core\CaseStudy;
use Astrea\Core\Result;
use Astrea\Core\Voice;
use Astrea\Core\Inquiry;
use Astrea\Core\Seo;
use Astrea\Core\Setup;

/**
 * @covers \Astrea\Core\DataDeletion
 */
class DataDeletionTest extends WP_UnitTestCase {

	public function test_delete_all_core_data_removes_every_core_post_type() {
		self::factory()->post->create( array( 'post_type' => ProfessionalProfile\POST_TYPE ) );
		self::factory()->post->create( array( 'post_type' => Service\POST_TYPE ) );
		self::factory()->post->create( array( 'post_type' => Price\POST_TYPE ) );
		self::factory()->post->create( array( 'post_type' => Faq\POST_TYPE ) );
		self::factory()->post->create( array( 'post_type' => CaseStudy\POST_TYPE ) );
		self::factory()->post->create( array( 'post_type' => Result\POST_TYPE ) );
		self::factory()->post->create( array( 'post_type' => Voice\POST_TYPE ) );
		self::factory()->post->create( array( 'post_type' => Inquiry\POST_TYPE ) );

		$result = DataDeletion\delete_all_core_data();

		$this->assertSame( 8, $result['posts'] );
		$this->assertSame( array(), get_posts( array( 'post_type' => ProfessionalProfile\POST_TYPE, 'post_status' => 'any' ) ) );
		$this->assertSame( array(), get_posts( array( 'post_type' => Service\POST_TYPE, 'post_status' => 'any' ) ) );
		$this->assertSame( array(), get_posts( array( 'post_type' => Price\POST_TYPE, 'post_status' => 'any' ) ) );
		$this->assertSame( array(), get_posts( array( 'post_type' => Faq\POST_TYPE, 'post_status' => 'any' ) ) );
		$this->assertSame( array(), get_posts( array( 'post_type' => CaseStudy\POST_TYPE, 'post_status' => 'any' ) ) );
		$this->assertSame( array(), get_posts( array( 'post_type' => Result\POST_TYPE, 'post_status' => 'any' ) ) );
		$this->assertSame( array(), get_posts( array( 'post_type' => Voice\POST_TYPE, 'post_status' => 'any' ) ) );
		$this->assertSame( array(), get_posts( array( 'post_type' => Inquiry\POST_TYPE, 'post_status' => 'any' ) ) );
	}

	public function test_delete_all_core_data_never_deletes_case_featured_image() {
		$attachment_id = self::factory()->attachment->create_object( array( 'file' => 'case.png' ) );
		$case_id       = self::factory()->post->create( array( 'post_type' => CaseStudy\POST_TYPE ) );
		set_post_thumbnail( $case_id, $attachment_id );

		DataDeletion\delete_all_core_data();

		$this->assertNotNull( get_post( $attachment_id ), 'CASE Featured Images must never be deleted (Decision 019).' );
	}

	public function test_delete_all_core_data_removes_faq_taxonomy_terms() {
		$term = self::factory()->term->create_and_get( array( 'taxonomy' => Faq\TAXONOMY ) );

		DataDeletion\delete_all_core_data();

		$this->assertNull( get_term( $term->term_id, Faq\TAXONOMY ) );
	}

	public function test_delete_all_core_data_removes_core_options() {
		update_option( OfficeProfile\OPTION_NAME, array( 'office_name' => 'テスト事務所' ) );
		update_option( Inquiry\SETTINGS_OPTION, array( 'notification_email' => 'a@example.com' ) );
		update_option( Seo\SETTINGS_OPTION, array( 'ga4_measurement_id' => 'G-ABCD123456' ) );
		update_option( Setup\GENERATED_PAGES_OPTION, array( 'about' => 123 ) );

		DataDeletion\delete_all_core_data();

		$this->assertFalse( get_option( OfficeProfile\OPTION_NAME ) );
		$this->assertFalse( get_option( Inquiry\SETTINGS_OPTION ) );
		$this->assertFalse( get_option( Seo\SETTINGS_OPTION ) );
		$this->assertFalse( get_option( Setup\GENERATED_PAGES_OPTION ) );
	}

	public function test_delete_all_core_data_never_deletes_ordinary_pages() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Setupが生成したページ' ) );
		update_option( Setup\GENERATED_PAGES_OPTION, array( 'about' => $page_id ) );

		DataDeletion\delete_all_core_data();

		$this->assertNotNull( get_post( $page_id ), 'Decision 016/019: generated Pages are user content and must never be deleted by the Core data-deletion action.' );
	}

	public function test_delete_all_core_data_never_deletes_media_attachments() {
		$attachment_id = self::factory()->attachment->create_object( array( 'file' => 'test.png' ) );
		$professional   = self::factory()->post->create( array( 'post_type' => ProfessionalProfile\POST_TYPE ) );
		set_post_thumbnail( $professional, $attachment_id );

		DataDeletion\delete_all_core_data();

		$this->assertNotNull( get_post( $attachment_id ), 'Media Library attachments (e.g. Professional Profile photos) must never be deleted.' );
	}

	public function test_delete_all_core_data_is_idempotent() {
		DataDeletion\delete_all_core_data();

		$result = DataDeletion\delete_all_core_data();

		$this->assertSame( 0, $result['posts'] );
	}

	public function test_delete_all_core_data_removes_known_transients() {
		set_transient( Setup\CONTACT_REACHABLE_TRANSIENT, true, HOUR_IN_SECONDS );
		set_transient( Inquiry\CLEANUP_LAST_RUN_TRANSIENT, time(), HOUR_IN_SECONDS );

		DataDeletion\delete_all_core_data();

		$this->assertFalse( get_transient( Setup\CONTACT_REACHABLE_TRANSIENT ) );
		$this->assertFalse( get_transient( Inquiry\CLEANUP_LAST_RUN_TRANSIENT ) );
	}

	public function test_delete_all_core_data_unschedules_cron() {
		Inquiry\schedule_cleanup_cron();
		Inquiry\reschedule_digest_cron();

		DataDeletion\delete_all_core_data();

		$this->assertFalse( wp_next_scheduled( Inquiry\CLEANUP_CRON_HOOK ) );
		$this->assertFalse( wp_next_scheduled( Inquiry\DIGEST_CRON_HOOK ) );
	}

	public function test_confirm_phrase_is_the_expected_japanese_phrase() {
		$this->assertSame( '削除する', DataDeletion\CONFIRM_PHRASE );
	}
}
