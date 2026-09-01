<?php
/**
 * Tests for ASTREA Core's Service feature (Construction Order 004).
 *
 * Core-only integration tests (WP_UnitTestCase, real WordPress post APIs).
 * Theme display (Query Loop rendering, single/archive templates) and the
 * Core-inactive/deactivate/reactivate states are covered by
 * tools/ci/smoke-test.sh against a real running site, consistent with how
 * OfficeProfileTest.php / ProfessionalProfileTest.php split responsibilities
 * in earlier Construction Orders.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Service;
use Astrea\Core\Service\Admin as ServiceAdmin;
use Astrea\Core\IconSystem;

/**
 * @covers \Astrea\Core\Service
 */
class ServiceTest extends WP_UnitTestCase {

	public function tear_down() {
		$_POST = array();
		parent::tear_down();
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

	public function test_zero_services_returns_empty_array() {
		$this->assertSame( array(), Service\get_services() );
	}

	public function test_one_service_is_returned() {
		$this->create_service(
			array(
				'post_title'   => '契約書作成',
				'post_content' => '契約書の作成・レビューを行います。',
			)
		);

		$services = Service\get_services();

		$this->assertCount( 1, $services );
		$this->assertSame( '契約書作成', $services[0]['name'] );
		$this->assertSame( '契約書の作成・レビューを行います。', $services[0]['description'] );
	}

	public function test_multiple_services_are_all_returned() {
		$this->create_service( array( 'post_title' => 'A' ) );
		$this->create_service( array( 'post_title' => 'B' ) );
		$this->create_service( array( 'post_title' => 'C' ) );

		$this->assertCount( 3, Service\get_services() );
	}

	public function test_display_order_uses_menu_order_then_title_then_id() {
		$c = $this->create_service(
			array(
				'post_title' => 'Charlie',
				'menu_order' => 1,
			)
		);
		$a = $this->create_service(
			array(
				'post_title' => 'Alpha',
				'menu_order' => 0,
			)
		);
		$b = $this->create_service(
			array(
				'post_title' => 'Bravo',
				'menu_order' => 0,
			)
		);

		$ids = wp_list_pluck( Service\get_services(), 'id' );

		$this->assertSame( array( $a, $b, $c ), $ids );
	}

	public function test_editing_a_service_is_reflected_in_get_service() {
		$id = $this->create_service( array( 'post_title' => '編集前' ) );

		wp_update_post(
			array(
				'ID'         => $id,
				'post_title' => '編集後',
			)
		);

		$this->assertSame( '編集後', Service\get_service( $id )['name'] );
	}

	public function test_deleting_a_service_removes_it_from_list_and_single_lookup() {
		$id = $this->create_service();

		wp_delete_post( $id, true );

		$this->assertSame( array(), Service\get_services() );
		$this->assertNull( Service\get_service( $id ) );
	}

	public function test_get_service_rejects_nonexistent_id() {
		$this->assertNull( Service\get_service( 999999 ) );
	}

	public function test_get_service_rejects_wrong_post_type() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertNull( Service\get_service( $page_id ), 'Must not return data for a post of a different post type.' );
	}

	public function test_get_services_excludes_drafts() {
		$this->create_service( array( 'post_status' => 'draft' ) );

		$this->assertSame( array(), Service\get_services() );
	}

	public function test_deactivate_does_not_delete_services() {
		$id = $this->create_service( array( 'post_title' => '削除されないはずの業務' ) );

		\Astrea\Core\deactivate();

		$this->assertNotNull( get_post( $id ), 'Decision 019: deactivation must never delete Core-owned data.' );
		$this->assertSame( '削除されないはずの業務', get_post( $id )->post_title );
	}

	// -- astrea/service-list Dynamic Block (Construction Order 011, Decision 028) --

	public function test_service_list_block_self_hides_with_zero_items_by_default() {
		$this->assertSame( '', Service\render_service_list_block() );
	}

	public function test_service_list_block_shows_empty_message_when_set() {
		$html = Service\render_service_list_block( array( 'emptyMessage' => '現在、取扱業務の情報は準備中です。' ) );

		$this->assertStringContainsString( '現在、取扱業務の情報は準備中です。', $html );
	}

	public function test_service_list_block_heading_only_appears_alongside_content() {
		$this->create_service( array( 'post_title' => '契約書作成' ) );

		$with_content = Service\render_service_list_block( array( 'heading' => '取扱業務' ) );
		$this->assertStringContainsString( '<h2>取扱業務</h2>', $with_content );
	}

	public function test_service_list_block_heading_is_not_emitted_alone_with_zero_items() {
		$html = Service\render_service_list_block( array( 'heading' => '取扱業務' ) );

		$this->assertSame( '', $html, 'A heading must never be emitted alone when there are zero Service posts (Decision 028).' );
	}

	public function test_service_list_block_links_title_to_permalink() {
		$id = $this->create_service( array( 'post_title' => '契約書作成' ) );

		$html = Service\render_service_list_block();

		$this->assertStringContainsString( '<a href="' . esc_url( get_permalink( $id ) ) . '">契約書作成</a>', $html );
	}

	public function test_service_list_block_respects_limit() {
		$this->create_service( array( 'post_title' => '業務1' ) );
		$this->create_service( array( 'post_title' => '業務2' ) );
		$this->create_service( array( 'post_title' => '業務3' ) );

		$html = Service\render_service_list_block( array( 'limit' => 2 ) );

		$this->assertStringContainsString( '業務1', $html );
		$this->assertStringContainsString( '業務2', $html );
		$this->assertStringNotContainsString( '業務3', $html );
	}

	public function test_service_list_block_escapes_description_excerpt() {
		$this->create_service(
			array(
				'post_title'   => '危険業務',
				'post_content' => '<script>alert(1)</script>安全な説明文です。',
			)
		);

		$html = Service\render_service_list_block();

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '安全な説明文です。', $html );
	}

	// -- Icon (Construction Order 016D-R1) -----------------------------------

	public function test_icon_defaults_to_folder_when_never_set() {
		$id = $this->create_service();

		$this->assertSame( 'folder', Service\get_service( $id )['icon'], 'register_post_meta()\'s own default should apply for an unset key.' );
	}

	public function test_icon_reflects_a_valid_stored_value() {
		$id = $this->create_service();
		update_post_meta( $id, Service\META_ICON, 'company' );

		$this->assertSame( 'company', Service\get_service( $id )['icon'] );
	}

	public function test_existing_service_created_before_016d_r1_still_works() {
		// Simulates data written before this Order existed: no icon
		// postmeta row at all (not even an empty string) — must not fatal
		// and must fall back to the safe default.
		$id = $this->create_service( array( 'post_title' => '既存の業務' ) );

		$service = Service\get_service( $id );

		$this->assertSame( 'folder', $service['icon'] );
		$this->assertSame( '既存の業務', $service['name'] );
	}

	public function test_save_meta_keeps_a_valid_icon_for_capable_user() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_service();

		$_POST[ ServiceAdmin\NONCE_FIELD ] = wp_create_nonce( ServiceAdmin\NONCE_ACTION );
		$_POST[ Service\META_ICON ]        = 'permit';

		ServiceAdmin\save_meta( $id );

		$this->assertSame( 'permit', Service\get_service( $id )['icon'] );
	}

	public function test_save_meta_rejects_an_arbitrary_string_and_falls_back_to_default() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_service();

		$_POST[ ServiceAdmin\NONCE_FIELD ] = wp_create_nonce( ServiceAdmin\NONCE_ACTION );
		$_POST[ Service\META_ICON ]        = '<script>alert(1)</script>not-a-real-icon';

		ServiceAdmin\save_meta( $id );

		$this->assertSame( 'folder', Service\get_service( $id )['icon'], 'A hand-crafted POST value outside allowed_slugs() must never be stored as-is — the classic meta-box save path re-validates against the same whitelist as the REST sanitize_callback.' );
	}

	public function test_save_meta_rejects_an_icon_slug_belonging_to_a_different_context() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_service();

		$_POST[ ServiceAdmin\NONCE_FIELD ] = wp_create_nonce( ServiceAdmin\NONCE_ACTION );
		$_POST[ Service\META_ICON ]        = 'result-check'; // a real slug, just not Service-valid.

		ServiceAdmin\save_meta( $id );

		$this->assertSame( 'folder', Service\get_service( $id )['icon'] );
	}

	public function test_save_meta_rejects_missing_nonce_for_icon() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_service();

		$_POST[ Service\META_ICON ] = 'company';
		// No nonce field set at all.

		ServiceAdmin\save_meta( $id );

		$this->assertSame( 'folder', Service\get_service( $id )['icon'] );
	}

	public function test_service_list_block_renders_the_selected_icon() {
		$id = $this->create_service( array( 'post_title' => '相続手続き' ) );
		update_post_meta( $id, Service\META_ICON, 'inheritance' );

		$html = Service\render_service_list_block();

		$this->assertStringContainsString( 'wp-block-astrea-service-item-icon', $html );
	}
}
