<?php
/**
 * Tests for ASTREA Core's HOME assembly (Construction Order 009).
 *
 * The ASTREA Theme (and its registered HOME Patterns) is intentionally not
 * loaded in this PHPUnit environment (see tests/bootstrap.php) — so
 * assemble_home_content() always finds zero registered Patterns here, and
 * generate_home_page() always resolves via its "Patterns unavailable"
 * guard rather than actually publishing a real assembled Page. That's
 * fine: this file covers the idempotency/protection guards and
 * is_home_configured()'s state judgment, which are Theme-independent.
 * Real end-to-end assembly (with the Theme's Patterns actually present) is
 * covered by tools/ci/smoke-test.sh against a real running site.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Setup;

/**
 * @covers \Astrea\Core\Setup
 */
class SetupHomeTest extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( Setup\GENERATED_PAGES_OPTION );
		update_option( 'show_on_front', 'posts' );
		update_option( 'page_on_front', 0 );
		parent::tear_down();
	}

	public function test_assemble_home_content_is_empty_without_the_theme_active() {
		$this->assertSame( '', Setup\assemble_home_content() );
	}

	public function test_generate_home_page_refuses_when_no_patterns_are_registered() {
		$result = Setup\generate_home_page();

		$this->assertWPError( $result );
		$this->assertSame( 'astrea_home_patterns_unavailable', $result->get_error_code() );
	}

	public function test_generate_home_page_refuses_when_already_generated() {
		$existing_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( Setup\GENERATED_PAGES_OPTION, array( 'home' => $existing_id ) );

		$result = Setup\generate_home_page();

		$this->assertWPError( $result );
		$this->assertSame( 'astrea_home_exists', $result->get_error_code() );
	}

	public function test_generate_home_page_recreates_after_previously_generated_page_is_trashed() {
		$existing_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( Setup\GENERATED_PAGES_OPTION, array( 'home' => $existing_id ) );
		wp_trash_post( $existing_id );

		// No Theme patterns registered in this environment, so this still
		// resolves via the "patterns unavailable" guard rather than
		// "already exists" — proving the trashed page no longer blocks it.
		$result = Setup\generate_home_page();

		$this->assertWPError( $result );
		$this->assertSame( 'astrea_home_patterns_unavailable', $result->get_error_code() );
	}

	public function test_generate_home_page_refuses_when_a_different_static_front_page_exists() {
		$other_front_page = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $other_front_page );

		$result = Setup\generate_home_page();

		$this->assertWPError( $result );
		$this->assertSame( 'astrea_home_front_page_exists', $result->get_error_code() );
	}

	public function test_generate_home_page_proceeds_when_blog_is_the_home() {
		update_option( 'show_on_front', 'posts' );

		// "posts" (blog-as-home) is not treated as a blocking existing front
		// page — this resolves via the patterns-unavailable guard, not the
		// front-page-exists guard, proving the blog-as-home state itself was
		// never the reason it stopped.
		$result = Setup\generate_home_page();

		$this->assertWPError( $result );
		$this->assertSame( 'astrea_home_patterns_unavailable', $result->get_error_code() );
	}

	public function test_generate_home_page_proceeds_when_front_page_setting_points_to_a_trashed_page() {
		$trashed = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $trashed );
		wp_trash_post( $trashed );

		$result = Setup\generate_home_page();

		$this->assertWPError( $result );
		$this->assertSame( 'astrea_home_patterns_unavailable', $result->get_error_code(), 'A stale/trashed front-page reference must not block generation.' );
	}

	// -- is_home_configured() --------------------------------------------------

	public function test_is_home_configured_false_by_default() {
		$this->assertFalse( Setup\is_home_configured() );
	}

	public function test_is_home_configured_true_with_a_real_published_front_page() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );

		$this->assertTrue( Setup\is_home_configured() );
	}

	public function test_is_home_configured_false_when_front_page_is_a_draft() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'draft' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );

		$this->assertFalse( Setup\is_home_configured() );
	}

	public function test_is_home_configured_false_when_show_on_front_is_posts() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		update_option( 'show_on_front', 'posts' );
		update_option( 'page_on_front', $page_id );

		$this->assertFalse( Setup\is_home_configured() );
	}
}
