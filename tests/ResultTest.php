<?php
/**
 * Tests for ASTREA Core's RESULTS（実績）feature (Construction Order 010).
 *
 * Core-only integration tests (WP_UnitTestCase, real WordPress
 * post/postmeta APIs). Theme display (the astrea/results-list Dynamic
 * Block) and the Core-inactive/deactivate/reactivate states are covered
 * by tools/ci/smoke-test.sh against a real running site.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Result;
use Astrea\Core\Result\Admin as ResultAdmin;

/**
 * @covers \Astrea\Core\Result
 */
class ResultTest extends WP_UnitTestCase {

	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	private function create_result( array $args = array() ): int {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => Result\POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'テスト実績',
				),
				$args
			)
		);
	}

	public function test_zero_results_returns_empty_array() {
		$this->assertSame( array(), Result\get_results() );
	}

	public function test_one_result_is_returned() {
		$id = $this->create_result( array( 'post_title' => '相談実績' ) );
		update_post_meta( $id, Result\META_VALUE, '1,000件以上' );

		$results = Result\get_results();

		$this->assertCount( 1, $results );
		$this->assertSame( '相談実績', $results[0]['label'] );
		$this->assertSame( '1,000件以上', $results[0]['value'] );
	}

	public function test_value_is_never_assumed_numeric() {
		$id = $this->create_result( array( 'post_title' => '開業年' ) );
		update_post_meta( $id, Result\META_VALUE, '2015年' );

		$this->assertSame( '2015年', Result\get_result( $id )['value'] );
	}

	public function test_value_defaults_to_empty_string() {
		$id = $this->create_result();

		$this->assertSame( '', Result\get_result( $id )['value'] );
	}

	public function test_display_order_uses_menu_order_then_title_then_id() {
		$bravo   = $this->create_result( array( 'post_title' => 'Bravo実績', 'menu_order' => 1 ) );
		$alpha   = $this->create_result( array( 'post_title' => 'Alpha実績', 'menu_order' => 0 ) );
		$charlie = $this->create_result( array( 'post_title' => 'Charlie実績', 'menu_order' => 1 ) );

		$labels = array_column( Result\get_results(), 'label' );

		$this->assertSame( array( 'Alpha実績', 'Bravo実績', 'Charlie実績' ), $labels );
	}

	public function test_get_results_excludes_drafts() {
		$this->create_result( array( 'post_status' => 'draft' ) );

		$this->assertSame( array(), Result\get_results() );
	}

	public function test_get_result_rejects_nonexistent_id() {
		$this->assertNull( Result\get_result( 999999 ) );
	}

	public function test_is_not_public() {
		$post_type_object = get_post_type_object( Result\POST_TYPE );

		$this->assertFalse( $post_type_object->public );
		$this->assertFalse( $post_type_object->publicly_queryable );
	}

	public function test_is_not_exposed_via_rest() {
		// Construction Order 011 Security Audit (MEDIUM finding): same
		// REST-exposure contradiction as astrea_price — see PriceTest.php's
		// equivalent test for the full explanation.
		$post_type_object = get_post_type_object( Result\POST_TYPE );

		$this->assertFalse( $post_type_object->show_in_rest );
	}

	public function test_save_meta_writes_value_for_capable_user() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_result();

		$_POST[ ResultAdmin\NONCE_FIELD ] = wp_create_nonce( ResultAdmin\NONCE_ACTION );
		$_POST[ Result\META_VALUE ]       = '1,000件以上';

		ResultAdmin\save_meta( $id );

		$this->assertSame( '1,000件以上', Result\get_result( $id )['value'] );
	}

	public function test_save_meta_strips_tags_from_value() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_result();

		$_POST[ ResultAdmin\NONCE_FIELD ] = wp_create_nonce( ResultAdmin\NONCE_ACTION );
		$_POST[ Result\META_VALUE ]       = '<script>alert(1)</script>1,000件';

		ResultAdmin\save_meta( $id );

		$this->assertSame( '1,000件', Result\get_result( $id )['value'], 'sanitize_text_field() strips the entire script block, tags and content.' );
	}

	public function test_save_meta_rejects_missing_nonce() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_result();

		$_POST[ Result\META_VALUE ] = '1,000件以上';
		// No nonce field set at all.

		ResultAdmin\save_meta( $id );

		$this->assertSame( '', Result\get_result( $id )['value'] );
	}

	public function test_save_meta_rejects_non_capable_user() {
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );
		$id = $this->create_result();

		$_POST[ ResultAdmin\NONCE_FIELD ] = wp_create_nonce( ResultAdmin\NONCE_ACTION );
		$_POST[ Result\META_VALUE ]       = '1,000件以上';

		ResultAdmin\save_meta( $id );

		$this->assertSame( '', Result\get_result( $id )['value'] );
	}

	public function test_deactivate_does_not_delete_results() {
		$id = $this->create_result( array( 'post_title' => '削除されないはずの実績' ) );

		\Astrea\Core\deactivate();

		$this->assertNotNull( get_post( $id ), 'Decision 019: deactivation must never delete Core-owned data.' );
		$this->assertSame( '削除されないはずの実績', get_post( $id )->post_title );
	}

	// -- astrea/results-list Dynamic Block ----------------------------------

	public function test_results_list_block_self_hides_with_zero_items_by_default() {
		$this->assertSame( '', Result\render_results_list_block() );
	}

	public function test_results_list_block_shows_empty_message_when_set() {
		$html = Result\render_results_list_block( array( 'emptyMessage' => '現在準備中です。' ) );

		$this->assertStringContainsString( '現在準備中です。', $html );
	}

	public function test_results_list_block_heading_appears_alongside_content() {
		$id = $this->create_result( array( 'post_title' => '相談実績' ) );
		update_post_meta( $id, Result\META_VALUE, '1,000件以上' );

		$html = Result\render_results_list_block( array( 'heading' => '実績' ) );

		$this->assertStringContainsString( '<h2>実績</h2>', $html );
		$this->assertStringContainsString( '相談実績', $html );
		// Construction Order 016D-R1 §9: the number/unit typography split
		// means the raw string "1,000件以上" is no longer contiguous in
		// the output — see render_value()'s own dedicated tests below for
		// that behaviour; this only re-confirms both parts are present.
		$this->assertStringContainsString( '1,000', $html );
		$this->assertStringContainsString( '件以上', $html );
	}

	public function test_results_list_block_heading_is_not_emitted_alone_with_zero_items() {
		$html = Result\render_results_list_block( array( 'heading' => '実績' ) );

		$this->assertSame( '', $html, 'A heading must never be emitted alone when there are zero RESULTS entries.' );
	}

	// -- Icon (Construction Order 016D-R1) -----------------------------------

	public function test_icon_defaults_to_result_check_when_never_set() {
		$id = $this->create_result();

		$this->assertSame( 'result-check', Result\get_result( $id )['icon'] );
	}

	public function test_icon_reflects_a_valid_stored_value() {
		$id = $this->create_result();
		update_post_meta( $id, Result\META_ICON, 'result-company' );

		$this->assertSame( 'result-company', Result\get_result( $id )['icon'] );
	}

	public function test_existing_result_created_before_016d_r1_still_works() {
		$id = $this->create_result( array( 'post_title' => '既存の実績' ) );
		update_post_meta( $id, Result\META_VALUE, '1,000件以上' );

		$result = Result\get_result( $id );

		$this->assertSame( 'result-check', $result['icon'] );
		$this->assertSame( '1,000件以上', $result['value'] );
	}

	public function test_save_meta_keeps_a_valid_icon() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_result();

		$_POST[ ResultAdmin\NONCE_FIELD ] = wp_create_nonce( ResultAdmin\NONCE_ACTION );
		$_POST[ Result\META_ICON ]        = 'result-consultation';

		ResultAdmin\save_meta( $id );

		$this->assertSame( 'result-consultation', Result\get_result( $id )['icon'] );
	}

	public function test_save_meta_rejects_an_arbitrary_icon_string() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_result();

		$_POST[ ResultAdmin\NONCE_FIELD ] = wp_create_nonce( ResultAdmin\NONCE_ACTION );
		$_POST[ Result\META_ICON ]        = 'not-a-real-icon';

		ResultAdmin\save_meta( $id );

		$this->assertSame( 'result-check', Result\get_result( $id )['icon'] );
	}

	public function test_save_meta_rejects_an_icon_slug_belonging_to_a_different_context() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_result();

		$_POST[ ResultAdmin\NONCE_FIELD ] = wp_create_nonce( ResultAdmin\NONCE_ACTION );
		$_POST[ Result\META_ICON ]        = 'folder'; // a real slug, just Service-only.

		ResultAdmin\save_meta( $id );

		$this->assertSame( 'result-check', Result\get_result( $id )['icon'] );
	}

	// -- Value / unit typography split (Construction Order 016D-R1 §9) ------

	public function test_render_value_splits_a_leading_number_from_its_unit() {
		$html = Result\render_value( '200社以上' );

		$this->assertSame( '200<span class="wp-block-astrea-result-unit">社以上</span>', $html );
	}

	public function test_render_value_splits_a_percentage() {
		$this->assertSame( '98<span class="wp-block-astrea-result-unit">%</span>', Result\render_value( '98%' ) );
	}

	public function test_render_value_with_no_leading_number_renders_unsplit() {
		// Backward-compatible: existing free-text values with no leading
		// digit (e.g. authored before this Order) render exactly as
		// before, no split markup.
		$this->assertSame( '全国対応', Result\render_value( '全国対応' ) );
	}

	public function test_render_value_with_only_a_number_renders_unsplit() {
		$this->assertSame( '2015', Result\render_value( '2015' ) );
	}

	public function test_render_value_escapes_html() {
		$html = Result\render_value( '<script>alert(1)</script>200社以上' );

		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_results_list_block_renders_split_value_and_icon() {
		$id = $this->create_result( array( 'post_title' => '会社設立支援' ) );
		update_post_meta( $id, Result\META_VALUE, '200社以上' );
		update_post_meta( $id, Result\META_ICON, 'result-company' );

		$html = Result\render_results_list_block();

		$this->assertStringContainsString( '200<span class="wp-block-astrea-result-unit">社以上</span>', $html );
		$this->assertStringContainsString( 'wp-block-astrea-result-item-icon', $html );
	}
}
