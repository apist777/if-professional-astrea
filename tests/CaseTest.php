<?php
/**
 * Tests for ASTREA Core's CASE（対応事例）feature (Construction Order 010).
 *
 * Core-only integration tests (WP_UnitTestCase, real WordPress
 * post/postmeta APIs). Theme display (Query Loop rendering on
 * archive-astrea_case.html / single-astrea_case.html) and the
 * Core-inactive/deactivate/reactivate states are covered by
 * tools/ci/smoke-test.sh against a real running site.
 *
 * @package Astrea\Core
 */

use Astrea\Core\CaseStudy;
use Astrea\Core\CaseStudy\Admin as CaseAdmin;
use Astrea\Core\Service;

/**
 * @covers \Astrea\Core\CaseStudy
 */
class CaseTest extends WP_UnitTestCase {

	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	private function create_case( array $args = array() ): int {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => CaseStudy\POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'テスト事例',
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

	public function test_zero_cases_returns_empty_array() {
		$this->assertSame( array(), CaseStudy\get_cases() );
	}

	public function test_one_case_is_returned() {
		$id = $this->create_case( array( 'post_title' => '相続手続きの事例' ) );

		$cases = CaseStudy\get_cases();

		$this->assertCount( 1, $cases );
		$this->assertSame( '相続手続きの事例', $cases[0]['title'] );
	}

	public function test_multiple_cases_are_all_returned() {
		$this->create_case();
		$this->create_case();
		$this->create_case();

		$this->assertCount( 3, CaseStudy\get_cases() );
	}

	public function test_display_order_uses_menu_order_then_title_then_id() {
		$bravo   = $this->create_case( array( 'post_title' => 'Bravo事例', 'menu_order' => 1 ) );
		$alpha   = $this->create_case( array( 'post_title' => 'Alpha事例', 'menu_order' => 0 ) );
		$charlie = $this->create_case( array( 'post_title' => 'Charlie事例', 'menu_order' => 1 ) );

		$titles = array_column( CaseStudy\get_cases(), 'title' );

		$this->assertSame( array( 'Alpha事例', 'Bravo事例', 'Charlie事例' ), $titles );
	}

	public function test_get_cases_excludes_drafts() {
		$this->create_case( array( 'post_status' => 'draft' ) );

		$this->assertSame( array(), CaseStudy\get_cases() );
	}

	public function test_get_case_rejects_nonexistent_id() {
		$this->assertNull( CaseStudy\get_case( 999999 ) );
	}

	public function test_get_case_rejects_wrong_post_type() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertNull( CaseStudy\get_case( $page_id ) );
	}

	public function test_is_public_and_has_archive() {
		$post_type_object = get_post_type_object( CaseStudy\POST_TYPE );

		$this->assertTrue( $post_type_object->public );
		$this->assertSame( 'cases', $post_type_object->has_archive );
	}

	public function test_excerpt_falls_back_to_empty_string_without_one() {
		// The post factory fills in a default post_excerpt unless told not to.
		$id = $this->create_case( array( 'post_excerpt' => '' ) );

		$this->assertSame( '', CaseStudy\get_case( $id )['excerpt'] );
	}

	// -- 関連Service -----------------------------------------------------------

	public function test_case_has_no_related_services_by_default() {
		$id = $this->create_case();

		$this->assertSame( array(), CaseStudy\get_case( $id )['related_services'] );
	}

	public function test_sanitize_related_services_keeps_published_service_ids() {
		$service_id = $this->create_service();

		$this->assertSame( array( $service_id ), CaseStudy\sanitize_related_services( array( $service_id ) ) );
	}

	public function test_sanitize_related_services_drops_unpublished_or_unknown_ids() {
		$draft_service = $this->create_service( array( 'post_status' => 'draft' ) );
		$page_id       = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertSame(
			array(),
			CaseStudy\sanitize_related_services( array( $draft_service, $page_id, 999999 ) )
		);
	}

	public function test_sanitize_related_services_removes_duplicates() {
		$service_id = $this->create_service();

		$this->assertSame(
			array( $service_id ),
			CaseStudy\sanitize_related_services( array( $service_id, $service_id ) )
		);
	}

	public function test_get_cases_for_service_returns_only_related_cases() {
		$service_a = $this->create_service( array( 'post_title' => 'A業務' ) );
		$service_b = $this->create_service( array( 'post_title' => 'B業務' ) );

		$case_related_to_a = $this->create_case( array( 'post_title' => 'A関連事例' ) );
		update_post_meta( $case_related_to_a, CaseStudy\META_RELATED_SERVICES, array( $service_a ) );

		$this->create_case( array( 'post_title' => 'B関連事例（別業務）' ) );

		$results = CaseStudy\get_cases_for_service( $service_a );

		$this->assertCount( 1, $results );
		$this->assertSame( 'A関連事例', $results[0]['title'] );

		$this->assertSame( array(), CaseStudy\get_cases_for_service( $service_b ) );
	}

	// -- Admin save ------------------------------------------------------------

	public function test_save_meta_sets_related_services_for_capable_user() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$service_id = $this->create_service();
		$id         = $this->create_case();

		$_POST[ CaseAdmin\NONCE_FIELD ]            = wp_create_nonce( CaseAdmin\NONCE_ACTION );
		$_POST[ CaseStudy\META_RELATED_SERVICES ] = array( (string) $service_id );

		CaseAdmin\save_meta( $id );

		$this->assertSame( array( $service_id ), CaseStudy\get_case( $id )['related_services'] );
	}

	public function test_save_meta_drops_unpublished_service_id() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$draft_service = $this->create_service( array( 'post_status' => 'draft' ) );
		$id            = $this->create_case();

		$_POST[ CaseAdmin\NONCE_FIELD ]           = wp_create_nonce( CaseAdmin\NONCE_ACTION );
		$_POST[ CaseStudy\META_RELATED_SERVICES ] = array( (string) $draft_service );

		CaseAdmin\save_meta( $id );

		$this->assertSame( array(), CaseStudy\get_case( $id )['related_services'] );
	}

	public function test_save_meta_rejects_missing_nonce() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$service_id = $this->create_service();
		$id         = $this->create_case();

		$_POST[ CaseStudy\META_RELATED_SERVICES ] = array( (string) $service_id );
		// No nonce field set at all.

		CaseAdmin\save_meta( $id );

		$this->assertSame( array(), CaseStudy\get_case( $id )['related_services'] );
	}

	public function test_save_meta_rejects_non_capable_user() {
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );
		$service_id = $this->create_service();
		$id         = $this->create_case();

		$_POST[ CaseAdmin\NONCE_FIELD ]           = wp_create_nonce( CaseAdmin\NONCE_ACTION );
		$_POST[ CaseStudy\META_RELATED_SERVICES ] = array( (string) $service_id );

		CaseAdmin\save_meta( $id );

		$this->assertSame( array(), CaseStudy\get_case( $id )['related_services'] );
	}

	public function test_deactivate_does_not_delete_cases() {
		$id = $this->create_case( array( 'post_title' => '削除されないはずの事例' ) );

		\Astrea\Core\deactivate();

		$this->assertNotNull( get_post( $id ), 'Decision 019: deactivation must never delete Core-owned data.' );
		$this->assertSame( '削除されないはずの事例', get_post( $id )->post_title );
	}

	// -- astrea/case-list Dynamic Block ------------------------------------------

	public function test_case_list_block_self_hides_with_zero_items_by_default() {
		$this->assertSame( '', CaseStudy\render_case_list_block() );
	}

	public function test_case_list_block_shows_empty_message_when_set() {
		$html = CaseStudy\render_case_list_block( array( 'emptyMessage' => '現在準備中です。' ) );

		$this->assertStringContainsString( '現在準備中です。', $html );
	}

	public function test_case_list_block_heading_appears_alongside_content() {
		$this->create_case( array( 'post_title' => '事例A' ) );

		$html = CaseStudy\render_case_list_block( array( 'heading' => '対応事例' ) );

		$this->assertStringContainsString( '<h2>対応事例</h2>', $html );
	}

	public function test_case_list_block_heading_is_not_emitted_alone_with_zero_items() {
		$html = CaseStudy\render_case_list_block( array( 'heading' => '対応事例' ) );

		$this->assertSame( '', $html, 'A heading must never be emitted alone when there are zero CASE entries.' );
	}

	public function test_case_list_block_respects_limit() {
		$this->create_case( array( 'post_title' => '事例1' ) );
		$this->create_case( array( 'post_title' => '事例2' ) );
		$this->create_case( array( 'post_title' => '事例3' ) );

		$html = CaseStudy\render_case_list_block( array( 'limit' => 2 ) );

		$this->assertStringContainsString( '事例1', $html );
		$this->assertStringContainsString( '事例2', $html );
		$this->assertStringNotContainsString( '事例3', $html );
	}

	public function test_case_list_block_links_title_to_single_page() {
		$id = $this->create_case( array( 'post_title' => '事例A' ) );

		$html = CaseStudy\render_case_list_block();

		$this->assertStringContainsString( esc_url( get_permalink( $id ) ), $html );
	}
}
