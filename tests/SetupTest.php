<?php
/**
 * Tests for ASTREA Core's Setup checklist, Page generation and Navigation
 * generation (Construction Order 007).
 *
 * Real end-to-end verification (checklist rendering on the Office Profile
 * page, the admin-post generation actions, the Core-recommendation notice
 * and its Dismiss) is covered in tools/ci/smoke-test.sh; this file covers
 * the pure data-building / idempotency logic.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Setup;
use Astrea\Core\OfficeProfile;
use Astrea\Core\Service;
use Astrea\Core\Price;
use Astrea\Core\Faq;
use Astrea\Core\ProfessionalProfile;
use Astrea\Core\Inquiry;
use Astrea\Core\Seo;

/**
 * @covers \Astrea\Core\Setup
 */
class SetupTest extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( OfficeProfile\OPTION_NAME );
		delete_option( Inquiry\SETTINGS_OPTION );
		delete_option( Seo\SETTINGS_OPTION );
		delete_option( Setup\GENERATED_PAGES_OPTION );
		delete_transient( Setup\CONTACT_REACHABLE_TRANSIENT );
		parent::tear_down();
	}

	// -- Checklist ----------------------------------------------------------

	public function test_checklist_office_profile_item_starts_undone() {
		$items = Setup\get_checklist_items();
		$item  = $this->find_item( $items, 'office_profile' );

		$this->assertFalse( $item['done'] );
		$this->assertSame( 'recommended', $item['priority'] );
	}

	public function test_checklist_office_profile_item_done_once_office_name_set() {
		update_option( OfficeProfile\OPTION_NAME, array( 'office_name' => 'テスト事務所' ) );

		$item = $this->find_item( Setup\get_checklist_items(), 'office_profile' );

		$this->assertTrue( $item['done'] );
	}

	public function test_checklist_service_item_reflects_published_service_count() {
		$this->assertFalse( $this->find_item( Setup\get_checklist_items(), 'service' )['done'] );

		self::factory()->post->create(
			array( 'post_type' => Service\POST_TYPE, 'post_status' => 'publish' )
		);

		$this->assertTrue( $this->find_item( Setup\get_checklist_items(), 'service' )['done'] );
	}

	public function test_checklist_professional_and_price_and_faq_items_are_optional() {
		$items = Setup\get_checklist_items();

		foreach ( array( 'professional', 'price', 'faq', 'seo_og_image', 'navigation' ) as $key ) {
			$this->assertSame( 'optional', $this->find_item( $items, $key )['priority'], "Expected {$key} to be optional." );
		}
	}

	public function test_checklist_notification_confirmed_item_reflects_confirmed_email() {
		$this->assertFalse( $this->find_item( Setup\get_checklist_items(), 'notification_confirmed' )['done'] );

		update_option( Inquiry\SETTINGS_OPTION, array( 'notification_email' => 'owner@example.com' ) );

		$this->assertTrue( $this->find_item( Setup\get_checklist_items(), 'notification_confirmed' )['done'] );
	}

	public function test_checklist_seo_og_image_item_reflects_setting() {
		$attachment_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.jpg' );

		$this->assertFalse( $this->find_item( Setup\get_checklist_items(), 'seo_og_image' )['done'] );

		update_option( Seo\SETTINGS_OPTION, array( 'og_image_id' => $attachment_id ) );

		$this->assertTrue( $this->find_item( Setup\get_checklist_items(), 'seo_og_image' )['done'] );
	}

	public function test_contact_reachable_is_false_without_any_contact_form_page() {
		self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );

		$this->assertFalse( Setup\is_contact_reachable() );
	}

	public function test_contact_reachable_is_true_once_a_published_page_embeds_the_contact_form_block() {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:astrea/contact-form /-->',
			)
		);

		$this->assertTrue( Setup\is_contact_reachable() );
	}

	public function test_contact_reachable_is_false_when_the_page_is_only_a_draft() {
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_content' => '<!-- wp:astrea/contact-form /-->',
			)
		);

		$this->assertFalse( Setup\is_contact_reachable() );
	}

	public function test_has_meaningful_navigation_is_false_by_default_and_true_after_creating_one() {
		$this->assertFalse( Setup\has_meaningful_navigation() );

		self::factory()->post->create( array( 'post_type' => 'wp_navigation', 'post_status' => 'draft' ) );

		$this->assertTrue( Setup\has_meaningful_navigation() );
	}

	public function test_has_meaningful_navigation_ignores_the_wordpress_page_list_fallback() {
		self::factory()->post->create(
			array(
				'post_type'    => 'wp_navigation',
				'post_status'  => 'publish',
				'post_name'    => 'navigation',
				'post_content' => '<!-- wp:page-list /-->',
			)
		);

		$this->assertFalse( Setup\has_meaningful_navigation(), 'WordPress\'s own auto-created Page List fallback must not count as a meaningful Navigation.' );
	}

	public function test_has_meaningful_navigation_true_once_the_fallback_is_edited() {
		self::factory()->post->create(
			array(
				'post_type'    => 'wp_navigation',
				'post_status'  => 'publish',
				'post_name'    => 'navigation',
				'post_content' => '<!-- wp:page-list /--><!-- wp:navigation-link {"label":"料金","url":"https://example.com/price/"} /-->',
			)
		);

		$this->assertTrue( Setup\has_meaningful_navigation() );
	}

	// -- Page generation ------------------------------------------------------

	public function test_generate_pages_creates_exactly_the_three_non_duplicate_pages() {
		$ids = Setup\generate_pages();

		$this->assertSame( array( 'about', 'price', 'contact' ), array_keys( $ids ) );
		foreach ( $ids as $id ) {
			$this->assertSame( 'page', get_post_type( $id ) );
			$this->assertSame( 'draft', get_post_status( $id ) );
		}

		// Service/Professional/FAQ already have their own CPT archives — no pages generated for them.
		$this->assertEmpty(
			get_posts( array( 'post_type' => 'page', 'post_status' => 'draft', 'numberposts' => -1, 's' => '取扱業務' ) )
		);
	}

	public function test_generate_pages_price_page_embeds_the_price_list_block() {
		$ids = Setup\generate_pages();

		$this->assertStringContainsString( '<!-- wp:astrea/price-list', get_post( $ids['price'] )->post_content );
	}

	public function test_generate_pages_contact_page_embeds_the_contact_form_block() {
		$ids = Setup\generate_pages();

		$this->assertStringContainsString( '<!-- wp:astrea/contact-form', get_post( $ids['contact'] )->post_content );
	}

	public function test_generate_pages_is_idempotent_and_never_overwrites_existing_content() {
		$first = Setup\generate_pages();

		wp_update_post(
			array(
				'ID'           => $first['about'],
				'post_content' => 'ユーザーが書いた本文',
			)
		);

		$second = Setup\generate_pages();

		$this->assertSame( $first, $second, 'Re-running must not create new pages for keys that already have a live page.' );
		$this->assertSame( 'ユーザーが書いた本文', get_post( $first['about'] )->post_content, 'Re-running must never overwrite existing page content.' );
	}

	public function test_generate_pages_recreates_a_page_that_was_trashed() {
		$first = Setup\generate_pages();
		wp_trash_post( $first['about'] );

		$second = Setup\generate_pages();

		$this->assertNotSame( $first['about'], $second['about'], 'A trashed generated page must be treated as gone, not "already generated".' );
		$this->assertSame( 'page', get_post_type( $second['about'] ) );
	}

	// -- Navigation generation --------------------------------------------------

	public function test_generate_navigation_refuses_when_a_navigation_already_exists() {
		self::factory()->post->create( array( 'post_type' => 'wp_navigation', 'post_status' => 'draft' ) );

		$result = Setup\generate_navigation();

		$this->assertWPError( $result );
	}

	public function test_generate_navigation_creates_a_draft_navigation_with_links_for_existing_content() {
		self::factory()->post->create( array( 'post_type' => Service\POST_TYPE, 'post_status' => 'publish' ) );
		Setup\generate_pages();

		$nav_id = Setup\generate_navigation();

		$this->assertIsInt( $nav_id );
		$this->assertSame( 'wp_navigation', get_post_type( $nav_id ) );
		$this->assertSame( 'draft', get_post_status( $nav_id ) );

		$content = get_post( $nav_id )->post_content;
		$this->assertStringContainsString( 'wp:navigation-link', $content );
		$this->assertStringContainsString( '取扱業務', $content );
		// Price/Contact pages were generated above, so their links must be present too.
		$this->assertStringContainsString( '料金', $content );
		$this->assertStringContainsString( 'お問い合わせ', $content );
		// No Professional/FAQ content exists yet, so those links must be absent.
		$this->assertStringNotContainsString( '専門家紹介', $content );
		$this->assertStringNotContainsString( '>FAQ<', $content );
	}

	public function test_generate_navigation_links_are_empty_when_nothing_exists_yet() {
		$links = Setup\navigation_links();

		$this->assertSame( array(), $links );
	}

	/**
	 * @param array[] $items Checklist items.
	 * @param string  $key   Item key to find.
	 * @return array
	 */
	private function find_item( array $items, string $key ): array {
		foreach ( $items as $item ) {
			if ( $item['key'] === $key ) {
				return $item;
			}
		}

		$this->fail( "No checklist item found for key '{$key}'." );
	}
}
