<?php
/**
 * Tests for ASTREA Core's Price feature (Construction Order 004).
 *
 * Core-only integration tests (WP_UnitTestCase, real WordPress post/postmeta
 * APIs). Theme display (Query Loop + core/post-meta binding rendering via
 * theme/patterns/price-list.php) and the Core-inactive/deactivate/reactivate
 * states are covered by tools/ci/smoke-test.sh against a real running site.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Price;
use Astrea\Core\Price\Admin as PriceAdmin;

/**
 * @covers \Astrea\Core\Price
 */
class PriceTest extends WP_UnitTestCase {

	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	private function create_price( array $args = array() ): int {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => Price\POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'テスト料金',
				),
				$args
			)
		);
	}

	public function test_zero_prices_returns_empty_array() {
		$this->assertSame( array(), Price\get_prices() );
	}

	public function test_one_price_is_returned() {
		$id = $this->create_price( array( 'post_title' => '相談料' ) );
		update_post_meta( $id, Price\META_AMOUNT, '初回30分無料' );
		update_post_meta( $id, Price\META_NOTES, '2回目以降は有料' );
		update_post_meta( $id, Price\META_GROUP, '相談' );

		$prices = Price\get_prices();

		$this->assertCount( 1, $prices );
		$this->assertSame( '相談料', $prices[0]['name'] );
		$this->assertSame( '初回30分無料', $prices[0]['amount'] );
		$this->assertSame( '2回目以降は有料', $prices[0]['notes'] );
		$this->assertSame( '相談', $prices[0]['group'] );
	}

	public function test_multiple_prices_are_all_returned() {
		$this->create_price( array( 'post_title' => 'A' ) );
		$this->create_price( array( 'post_title' => 'B' ) );
		$this->create_price( array( 'post_title' => 'C' ) );

		$this->assertCount( 3, Price\get_prices() );
	}

	public function test_display_order_uses_menu_order_then_title_then_id() {
		$c = $this->create_price(
			array(
				'post_title' => 'Charlie',
				'menu_order' => 1,
			)
		);
		$a = $this->create_price(
			array(
				'post_title' => 'Alpha',
				'menu_order' => 0,
			)
		);
		$b = $this->create_price(
			array(
				'post_title' => 'Bravo',
				'menu_order' => 0,
			)
		);

		$ids = wp_list_pluck( Price\get_prices(), 'id' );

		$this->assertSame( array( $a, $b, $c ), $ids );
	}

	public function test_deleting_a_price_removes_it_from_list_and_single_lookup() {
		$id = $this->create_price();

		wp_delete_post( $id, true );

		$this->assertSame( array(), Price\get_prices() );
		$this->assertNull( Price\get_price( $id ) );
	}

	public function test_get_price_rejects_nonexistent_id() {
		$this->assertNull( Price\get_price( 999999 ) );
	}

	public function test_get_price_rejects_wrong_post_type() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertNull( Price\get_price( $page_id ), 'Must not return data for a post of a different post type.' );
	}

	public function test_get_prices_excludes_drafts() {
		$this->create_price( array( 'post_status' => 'draft' ) );

		$this->assertSame( array(), Price\get_prices() );
	}

	public function test_is_not_public() {
		$post_type = get_post_type_object( Price\POST_TYPE );

		$this->assertFalse( $post_type->public, 'Price must not have an individual front-end URL — §10 gives no basis for one.' );
		$this->assertFalse( $post_type->publicly_queryable );
	}

	public function test_is_not_exposed_via_rest() {
		// Construction Order 011 Security Audit (MEDIUM finding): the REST
		// API gates a post type's controller on show_in_rest alone, not
		// `public` — this must stay false so /wp-json/wp/v2/astrea_price
		// cannot be read anonymously, matching this post type's "not
		// publicly queryable" design intent.
		$post_type = get_post_type_object( Price\POST_TYPE );

		$this->assertFalse( $post_type->show_in_rest );
	}

	public function test_all_meta_sanitizers_strip_tags() {
		$id = $this->create_price();

		foreach ( Price\meta_sanitizers() as $meta_key => $sanitize_callback ) {
			$sanitized = call_user_func( $sanitize_callback, '<script>alert(1)</script>安全なテキスト' );
			update_post_meta( $id, $meta_key, $sanitized );
		}

		$price = Price\get_price( $id );

		$this->assertStringNotContainsString( '<script>', $price['amount'] );
		$this->assertStringContainsString( '安全なテキスト', $price['amount'] );
	}

	public function test_save_meta_writes_known_fields_for_capable_user() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_price();

		$_POST[ PriceAdmin\NONCE_FIELD ] = wp_create_nonce( PriceAdmin\NONCE_ACTION );
		$_POST[ Price\META_AMOUNT ]      = '月額5,000円〜';
		$_POST[ Price\META_GROUP ]       = '顧問契約';

		PriceAdmin\save_meta( $id );

		$price = Price\get_price( $id );
		$this->assertSame( '月額5,000円〜', $price['amount'] );
		$this->assertSame( '顧問契約', $price['group'] );
	}

	public function test_save_meta_rejects_missing_nonce() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_price();

		$_POST[ Price\META_AMOUNT ] = '月額5,000円〜';
		// No nonce field set at all.

		PriceAdmin\save_meta( $id );

		$this->assertSame( '', get_post_meta( $id, Price\META_AMOUNT, true ) );
	}

	public function test_save_meta_rejects_non_capable_user() {
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );
		$id = $this->create_price();

		$_POST[ PriceAdmin\NONCE_FIELD ] = wp_create_nonce( PriceAdmin\NONCE_ACTION );
		$_POST[ Price\META_AMOUNT ]      = '月額5,000円〜';

		PriceAdmin\save_meta( $id );

		$this->assertSame( '', get_post_meta( $id, Price\META_AMOUNT, true ), 'A user without edit_post capability must not be able to write Price meta.' );
	}

	public function test_deactivate_does_not_delete_prices() {
		$id = $this->create_price( array( 'post_title' => '削除されないはずの料金' ) );

		\Astrea\Core\deactivate();

		$this->assertNotNull( get_post( $id ), 'Decision 019: deactivation must never delete Core-owned data.' );
		$this->assertSame( '削除されないはずの料金', get_post( $id )->post_title );
	}

	// -- astrea/price-list Dynamic Block heading/emptyMessage (Construction Order 008, Decision 028) --

	public function test_price_list_block_self_hides_with_zero_items_by_default() {
		$this->assertSame( '', Price\render_price_list_block() );
	}

	public function test_price_list_block_shows_empty_message_when_set() {
		$html = Price\render_price_list_block( array( 'emptyMessage' => '現在、料金情報は準備中です。' ) );

		$this->assertStringContainsString( '現在、料金情報は準備中です。', $html );
	}

	public function test_price_list_block_heading_appears_alongside_content() {
		$this->create_price( array( 'post_title' => 'テスト料金' ) );

		$html = Price\render_price_list_block( array( 'heading' => '料金' ) );

		$this->assertStringContainsString( '<h2>料金</h2>', $html );
	}

	public function test_price_list_block_heading_is_not_emitted_alone_with_zero_items() {
		$html = Price\render_price_list_block( array( 'heading' => '料金' ) );

		$this->assertSame( '', $html, 'A heading must never be emitted alone when there are zero Price entries.' );
	}
}
