<?php
/**
 * Tests for ASTREA Core's shared Icon System registry (Construction
 * Order 016D-R1).
 *
 * @package Astrea\Core
 */

use Astrea\Core\IconSystem;

/**
 * @covers \Astrea\Core\IconSystem
 */
class IconSystemTest extends WP_UnitTestCase {

	public function test_registry_has_all_ten_owner_provided_icons() {
		$registry = IconSystem\registry();

		$this->assertCount( 10, $registry );
		foreach ( array( 'company', 'contract', 'document', 'folder', 'inheritance', 'permit', 'result-company', 'result-check', 'result-consultation', 'price-yen' ) as $slug ) {
			$this->assertArrayHasKey( $slug, $registry );
		}
	}

	public function test_registry_icons_use_current_color_not_hardcoded_stroke() {
		foreach ( IconSystem\registry() as $slug => $markup ) {
			$this->assertStringNotContainsString( '#102A43', $markup, "$slug must not hard-code the source file's Navy stroke — it needs to recolour per Style Variation." );
			$this->assertStringNotContainsString( '#B99A5C', $markup, "$slug must not hard-code the source file's Gold stroke." );
			$this->assertStringContainsString( 'currentColor', $markup );
		}
	}

	public function test_registry_icons_are_decorative() {
		foreach ( IconSystem\registry() as $slug => $markup ) {
			$this->assertStringContainsString( 'aria-hidden="true"', $markup, "$slug must stay decorative — the visible label text is the accessible content." );
		}
	}

	public function test_render_wraps_known_icon_in_requested_class() {
		$html = IconSystem\render( 'folder', 'wp-block-astrea-service-item-icon' );

		$this->assertStringContainsString( '<span class="wp-block-astrea-service-item-icon">', $html );
		$this->assertStringContainsString( '<svg', $html );
	}

	public function test_render_returns_empty_string_for_unknown_slug() {
		$this->assertSame( '', IconSystem\render( 'not-a-real-icon', 'some-class' ) );
	}

	public function test_allowed_slugs_are_disjoint_by_context_except_price_reuses_service() {
		$service = IconSystem\allowed_slugs( 'service' );
		$result  = IconSystem\allowed_slugs( 'result' );
		$price   = IconSystem\allowed_slugs( 'price' );

		$this->assertContains( 'folder', $service );
		$this->assertContains( 'result-check', $result );
		$this->assertContains( 'price-yen', $price );
		// Price explicitly reuses the Service icon set (Order §11).
		$this->assertContains( 'company', $price );
		// Result's icons are never valid for Service or Price.
		$this->assertNotContains( 'result-check', $service );
		$this->assertNotContains( 'result-check', $price );
	}

	public function test_default_slug_is_always_within_its_own_allowed_slugs() {
		foreach ( array( 'service', 'result', 'price' ) as $context ) {
			$this->assertContains( IconSystem\default_slug( $context ), IconSystem\allowed_slugs( $context ), "default_slug('$context') must be one of its own allowed_slugs() — otherwise the sanitizer's own fallback would be rejected by a stricter check elsewhere." );
		}
	}

	public function test_make_sanitizer_keeps_a_valid_slug() {
		$sanitize = IconSystem\make_sanitizer( 'service' );

		$this->assertSame( 'inheritance', $sanitize( 'inheritance' ) );
	}

	public function test_make_sanitizer_falls_back_on_invalid_slug() {
		$sanitize = IconSystem\make_sanitizer( 'service' );

		$this->assertSame( IconSystem\default_slug( 'service' ), $sanitize( 'not-a-real-icon' ) );
	}

	public function test_make_sanitizer_falls_back_on_a_slug_from_a_different_context() {
		$sanitize = IconSystem\make_sanitizer( 'service' );

		// 'result-check' is a real icon slug, just not a Service-valid one.
		$this->assertSame( IconSystem\default_slug( 'service' ), $sanitize( 'result-check' ) );
	}

	public function test_make_sanitizer_falls_back_on_missing_or_non_string_value() {
		$sanitize = IconSystem\make_sanitizer( 'result' );

		$this->assertSame( IconSystem\default_slug( 'result' ), $sanitize( '' ) );
		$this->assertSame( IconSystem\default_slug( 'result' ), $sanitize( null ) );
		$this->assertSame( IconSystem\default_slug( 'result' ), $sanitize( array( 'not', 'a', 'string' ) ) );
	}
}
