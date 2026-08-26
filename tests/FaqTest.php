<?php
/**
 * Tests for ASTREA Core's FAQ feature (Construction Order 004).
 *
 * Core-only integration tests (WP_UnitTestCase, real WordPress
 * post/postmeta/taxonomy APIs). Theme display (Query Loop rendering on
 * archive-astrea_faq.html / taxonomy-astrea_faq_category.html) and the
 * Core-inactive/deactivate/reactivate states are covered by
 * tools/ci/smoke-test.sh against a real running site.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Faq;
use Astrea\Core\Faq\Admin as FaqAdmin;
use Astrea\Core\Service;

/**
 * @covers \Astrea\Core\Faq
 */
class FaqTest extends WP_UnitTestCase {

	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	private function create_faq( array $args = array() ): int {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => Faq\POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'テスト質問',
				),
				$args
			)
		);
	}

	private function create_service( array $args = array() ): int {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => Service\POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'テスト業務',
				),
				$args
			)
		);
	}

	public function test_zero_faqs_returns_empty_array() {
		$this->assertSame( array(), Faq\get_faqs() );
	}

	public function test_one_faq_is_returned() {
		$this->create_faq(
			array(
				'post_title'   => '料金はいくらですか？',
				'post_content' => '案件により異なります。',
			)
		);

		$faqs = Faq\get_faqs();

		$this->assertCount( 1, $faqs );
		$this->assertSame( '料金はいくらですか？', $faqs[0]['question'] );
		$this->assertSame( '案件により異なります。', $faqs[0]['answer'] );
	}

	public function test_multiple_faqs_are_all_returned() {
		$this->create_faq( array( 'post_title' => 'A' ) );
		$this->create_faq( array( 'post_title' => 'B' ) );
		$this->create_faq( array( 'post_title' => 'C' ) );

		$this->assertCount( 3, Faq\get_faqs() );
	}

	public function test_display_order_uses_menu_order_then_title_then_id() {
		$c = $this->create_faq(
			array(
				'post_title' => 'Charlie',
				'menu_order' => 1,
			)
		);
		$a = $this->create_faq(
			array(
				'post_title' => 'Alpha',
				'menu_order' => 0,
			)
		);
		$b = $this->create_faq(
			array(
				'post_title' => 'Bravo',
				'menu_order' => 0,
			)
		);

		$ids = wp_list_pluck( Faq\get_faqs(), 'id' );

		$this->assertSame( array( $a, $b, $c ), $ids );
	}

	public function test_deleting_a_faq_removes_it_from_list_and_single_lookup() {
		$id = $this->create_faq();

		wp_delete_post( $id, true );

		$this->assertSame( array(), Faq\get_faqs() );
		$this->assertNull( Faq\get_faq( $id ) );
	}

	public function test_get_faq_rejects_nonexistent_id() {
		$this->assertNull( Faq\get_faq( 999999 ) );
	}

	public function test_get_faq_rejects_wrong_post_type() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertNull( Faq\get_faq( $page_id ), 'Must not return data for a post of a different post type.' );
	}

	public function test_get_faqs_excludes_drafts() {
		$this->create_faq( array( 'post_status' => 'draft' ) );

		$this->assertSame( array(), Faq\get_faqs() );
	}

	// -- カテゴリ (Taxonomy) --------------------------------------------------

	public function test_faq_has_no_category_by_default() {
		$id = $this->create_faq();

		$this->assertSame( array(), Faq\get_faq( $id )['categories'] );
	}

	public function test_faq_category_is_returned() {
		$id = $this->create_faq();
		wp_set_object_terms( $id, array( '料金について' ), Faq\TAXONOMY );

		$this->assertSame( array( '料金について' ), Faq\get_faq( $id )['categories'] );
	}

	// -- 重要FAQ ---------------------------------------------------------------

	public function test_no_important_faq_by_default() {
		$this->create_faq();

		$this->assertSame( array(), Faq\get_important_faqs() );
	}

	public function test_important_faq_is_returned() {
		$id = $this->create_faq( array( 'post_title' => '重要な質問' ) );
		update_post_meta( $id, Faq\META_IS_IMPORTANT, true );

		$important = Faq\get_important_faqs();

		$this->assertCount( 1, $important );
		$this->assertSame( '重要な質問', $important[0]['question'] );
	}

	// -- 関連Service -----------------------------------------------------------

	public function test_faq_has_no_related_services_by_default() {
		$id = $this->create_faq();

		$this->assertSame( array(), Faq\get_faq( $id )['related_services'] );
	}

	public function test_sanitize_related_services_keeps_published_service_ids() {
		$service_id = $this->create_service();

		$this->assertSame( array( $service_id ), Faq\sanitize_related_services( array( $service_id ) ) );
	}

	public function test_sanitize_related_services_drops_unpublished_or_unknown_ids() {
		$draft_service = $this->create_service( array( 'post_status' => 'draft' ) );
		$page_id       = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertSame(
			array(),
			Faq\sanitize_related_services( array( $draft_service, $page_id, 999999 ) )
		);
	}

	public function test_get_faqs_for_service_returns_only_related_faqs() {
		$service_a = $this->create_service( array( 'post_title' => 'A業務' ) );
		$service_b = $this->create_service( array( 'post_title' => 'B業務' ) );

		$faq_related_to_a = $this->create_faq( array( 'post_title' => 'A関連FAQ' ) );
		update_post_meta( $faq_related_to_a, Faq\META_RELATED_SERVICES, array( $service_a ) );

		$this->create_faq( array( 'post_title' => 'B関連FAQ（別業務）' ) );

		$results = Faq\get_faqs_for_service( $service_a );

		$this->assertCount( 1, $results );
		$this->assertSame( 'A関連FAQ', $results[0]['question'] );

		$this->assertSame( array(), Faq\get_faqs_for_service( $service_b ) );
	}

	// -- Admin save ------------------------------------------------------------

	public function test_save_meta_sets_is_important_and_related_services_for_capable_user() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$service_id = $this->create_service();
		$id         = $this->create_faq();

		$_POST[ FaqAdmin\NONCE_FIELD ]       = wp_create_nonce( FaqAdmin\NONCE_ACTION );
		$_POST[ Faq\META_IS_IMPORTANT ]      = '1';
		$_POST[ Faq\META_RELATED_SERVICES ]  = array( (string) $service_id );

		FaqAdmin\save_meta( $id );

		$faq = Faq\get_faq( $id );
		$this->assertTrue( $faq['is_important'] );
		$this->assertSame( array( $service_id ), $faq['related_services'] );
	}

	public function test_save_meta_drops_unpublished_service_id_from_related_services() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$draft_service = $this->create_service( array( 'post_status' => 'draft' ) );
		$id            = $this->create_faq();

		$_POST[ FaqAdmin\NONCE_FIELD ]      = wp_create_nonce( FaqAdmin\NONCE_ACTION );
		$_POST[ Faq\META_RELATED_SERVICES ] = array( (string) $draft_service );

		FaqAdmin\save_meta( $id );

		$this->assertSame( array(), Faq\get_faq( $id )['related_services'] );
	}

	public function test_save_meta_unsets_is_important_when_checkbox_absent() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_faq();
		update_post_meta( $id, Faq\META_IS_IMPORTANT, true );

		// Real unchecked checkboxes are simply absent from $_POST.
		$_POST[ FaqAdmin\NONCE_FIELD ] = wp_create_nonce( FaqAdmin\NONCE_ACTION );

		FaqAdmin\save_meta( $id );

		$this->assertFalse( Faq\get_faq( $id )['is_important'] );
	}

	public function test_save_meta_rejects_missing_nonce() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_faq();

		$_POST[ Faq\META_IS_IMPORTANT ] = '1';
		// No nonce field set at all.

		FaqAdmin\save_meta( $id );

		$this->assertFalse( Faq\get_faq( $id )['is_important'] );
	}

	public function test_save_meta_rejects_non_capable_user() {
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );
		$id = $this->create_faq();

		$_POST[ FaqAdmin\NONCE_FIELD ]  = wp_create_nonce( FaqAdmin\NONCE_ACTION );
		$_POST[ Faq\META_IS_IMPORTANT ] = '1';

		FaqAdmin\save_meta( $id );

		$this->assertFalse( Faq\get_faq( $id )['is_important'], 'A user without edit_post capability must not be able to write FAQ meta.' );
	}

	public function test_deactivate_does_not_delete_faqs() {
		$id = $this->create_faq( array( 'post_title' => '削除されないはずのFAQ' ) );

		\Astrea\Core\deactivate();

		$this->assertNotNull( get_post( $id ), 'Decision 019: deactivation must never delete Core-owned data.' );
		$this->assertSame( '削除されないはずのFAQ', get_post( $id )->post_title );
	}
}
