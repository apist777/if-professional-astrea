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
		delete_option( Setup\GENERATED_NAVIGATION_OPTION );
		delete_option( Setup\GENERATED_TEMPLATE_PARTS_OPTION );
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

	public function test_checklist_site_title_item_reflects_blogname() {
		update_option( 'blogname', '' );
		$this->assertFalse( $this->find_item( Setup\get_checklist_items(), 'site_title' )['done'] );

		update_option( 'blogname', 'テスト事務所' );
		$this->assertTrue( $this->find_item( Setup\get_checklist_items(), 'site_title' )['done'] );
	}

	public function test_checklist_site_title_item_never_touches_office_profile() {
		// Construction Order 013: office_name and blogname are deliberately
		// independent — Setup must never auto-copy one into the other.
		update_option( OfficeProfile\OPTION_NAME, OfficeProfile\sanitize( array( 'office_name' => '架空事務所' ) ) );
		update_option( 'blogname', '' );

		Setup\get_checklist_items();

		$this->assertSame( '', get_option( 'blogname' ), 'Reading the checklist must never write to blogname.' );
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

	public function test_generate_navigation_creates_a_published_navigation_with_links_for_existing_content() {
		self::factory()->post->create( array( 'post_type' => Service\POST_TYPE, 'post_status' => 'publish' ) );
		Setup\generate_pages();

		$nav_id = Setup\generate_navigation();

		$this->assertIsInt( $nav_id );
		$this->assertSame( 'wp_navigation', get_post_type( $nav_id ) );
		// Construction Order 013: must be 'publish', not 'draft' — WordPress
		// core's render_block_core_navigation() requires publish status
		// even when a Template Part explicitly binds it via `ref` (see
		// docs/research/2026-08-28_construction_order_013_research.md
		// §B-1) — a draft Navigation can never actually render anywhere,
		// `ref` or not.
		$this->assertSame( 'publish', get_post_status( $nav_id ) );

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

	public function test_generate_navigation_is_idempotent_returns_the_same_tracked_id() {
		$first  = Setup\generate_navigation();
		$second = Setup\generate_navigation();

		$this->assertSame( $first, $second, 'Re-running generate_navigation() must reuse the already-generated Navigation, not create a duplicate.' );
		$this->assertCount( 1, get_posts( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) );
	}

	public function test_generate_navigation_recreates_when_the_tracked_one_was_trashed() {
		$first = Setup\generate_navigation();
		wp_trash_post( $first );

		$second = Setup\generate_navigation();

		$this->assertNotSame( $first, $second, 'A trashed generated Navigation must be treated as gone, not "already generated".' );
		$this->assertSame( 'wp_navigation', get_post_type( $second ) );
	}

	public function test_navigation_still_exists_is_true_for_a_real_navigation() {
		$id = self::factory()->post->create( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish' ) );

		$this->assertTrue( Setup\navigation_still_exists( $id ) );
	}

	public function test_navigation_still_exists_is_false_once_trashed() {
		$id = self::factory()->post->create( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish' ) );
		wp_trash_post( $id );

		$this->assertFalse( Setup\navigation_still_exists( $id ) );
	}

	public function test_navigation_still_exists_is_false_for_a_nonexistent_id() {
		$this->assertFalse( Setup\navigation_still_exists( 999999 ) );
	}

	// -- Navigation ref injection (pure block-parsing logic, no Theme dependency) --

	public function test_inject_navigation_ref_sets_ref_on_a_bare_navigation_block() {
		$content = '<!-- wp:navigation {"overlayMenu":"mobile"} /-->';

		$result = Setup\inject_navigation_ref( $content, 42 );

		$this->assertStringContainsString( '"ref":42', $result );
		$this->assertStringContainsString( '"overlayMenu":"mobile"', $result, 'Existing attributes must be preserved alongside the new ref.' );
	}

	public function test_inject_navigation_ref_finds_navigation_nested_inside_a_group() {
		$content = '<!-- wp:group --><div class="wp-block-group"><!-- wp:navigation /--></div><!-- /wp:group -->';

		$result = Setup\inject_navigation_ref( $content, 7 );

		$this->assertStringContainsString( '"ref":7', $result );
	}

	public function test_inject_navigation_ref_is_a_noop_when_no_navigation_block_present() {
		$content = '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';

		$result = Setup\inject_navigation_ref( $content, 1 );

		$this->assertStringNotContainsString( 'ref', $result );
	}

	public function test_connect_navigation_to_template_part_returns_error_for_a_nonexistent_slug() {
		// No Theme is loaded in this PHPUnit environment (see this file's
		// docblock precedent for OfficeProfileTest/ProfessionalProfileTest
		// — real Theme/Core Template Part integration is verified via
		// tools/ci/smoke-test.sh against a real running site instead), so
		// get_block_template() can never resolve a real 'header'/'footer'
		// here. This only confirms the graceful-failure path.
		$result = Setup\connect_navigation_to_template_part( 'not-a-real-slug', 1 );

		$this->assertSame( 'error', $result );
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
