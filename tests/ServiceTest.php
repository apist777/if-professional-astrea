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

/**
 * @covers \Astrea\Core\Service
 */
class ServiceTest extends WP_UnitTestCase {

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
}
