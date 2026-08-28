<?php
/**
 * Tests for ASTREA Core's shared helpers (Construction Order 004, extended
 * Construction Order 010).
 *
 * sanitize_related_service_ids() was extracted from FAQ's original
 * implementation in Construction Order 010 so CASE could reuse the exact
 * same responsibility — these tests cover the shared function directly;
 * FaqTest.php and CaseTest.php cover each module's own delegation.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Shared;
use Astrea\Core\Service;

/**
 * @covers \Astrea\Core\Shared
 */
class SharedTest extends WP_UnitTestCase {

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

	public function test_sanitize_related_service_ids_returns_empty_array_for_non_array() {
		$this->assertSame( array(), Shared\sanitize_related_service_ids( 'not-an-array' ) );
		$this->assertSame( array(), Shared\sanitize_related_service_ids( null ) );
	}

	public function test_sanitize_related_service_ids_keeps_published_service_ids() {
		$service_id = $this->create_service();

		$this->assertSame( array( $service_id ), Shared\sanitize_related_service_ids( array( $service_id ) ) );
	}

	public function test_sanitize_related_service_ids_drops_unpublished_or_unknown_ids() {
		$draft_service = $this->create_service( array( 'post_status' => 'draft' ) );
		$page_id       = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertSame(
			array(),
			Shared\sanitize_related_service_ids( array( $draft_service, $page_id, 999999 ) )
		);
	}

	public function test_sanitize_related_service_ids_removes_duplicates() {
		$service_id = $this->create_service();

		$this->assertSame(
			array( $service_id ),
			Shared\sanitize_related_service_ids( array( $service_id, (string) $service_id, $service_id ) )
		);
	}

	public function test_sanitize_related_service_ids_coerces_numeric_strings() {
		$service_id = $this->create_service();

		$this->assertSame( array( $service_id ), Shared\sanitize_related_service_ids( array( (string) $service_id ) ) );
	}
}
