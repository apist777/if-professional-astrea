<?php
/**
 * Tests for ASTREA Core's known SEO Plugin detection (Construction Order 006).
 *
 * @package Astrea\Core
 */

use Astrea\Core\Seo;

/**
 * @covers \Astrea\Core\Seo
 */
class SeoPluginDetectionTest extends WP_UnitTestCase {

	public function tear_down() {
		update_option( 'active_plugins', array() );
		parent::tear_down();
	}

	public function test_no_known_plugin_active_by_default() {
		update_option( 'active_plugins', array( 'astrea-core/astrea-core.php' ) );

		$this->assertFalse( Seo\is_known_seo_plugin_active() );
	}

	public function test_detects_each_known_plugin() {
		foreach ( Seo\KNOWN_PLUGIN_BASENAMES as $basename ) {
			update_option( 'active_plugins', array( $basename ) );
			$this->assertTrue( Seo\is_known_seo_plugin_active(), "Failed to detect $basename" );
		}
	}

	public function test_unrelated_plugin_is_not_detected() {
		update_option( 'active_plugins', array( 'hello-dolly/hello.php' ) );

		$this->assertFalse( Seo\is_known_seo_plugin_active() );
	}
}
