<?php
/**
 * Theme Pattern structural integrity tests (Construction 029-CI Revision 2,
 * PHASE 15 — Fixture Drift Prevention).
 *
 * Root cause of the Revision 1 → Revision 2 gap was NOT a genuine
 * Starter-vs-fixture design divergence: it was a one-off, scratch
 * migration script (used only to backfill an already-existing
 * development preview's saved post_content) that failed to keep
 * `parse_blocks()`'s `innerContent` array in sync with `innerBlocks`
 * when splicing in a new block, silently dropping the phone/contact CTA
 * buttons on re-serialization. That script was never part of the
 * committed codebase, so it cannot regress here — but the underlying
 * "Home/CTA composition may drift without anyone noticing" risk is real
 * for the actual Theme Pattern files themselves, which a future edit
 * could still break by accident.
 *
 * These tests read the Theme's own Pattern files
 * (`theme/patterns/home-hero.php`, `theme/patterns/home-cta.php`) —
 * exactly what a fresh Starter Site build renders from — and assert
 * they still (a) contain every expected Dynamic Block, in the expected
 * relative order, and (b) parse/serialize through WordPress's own block
 * parser without any block being lost, which is the exact failure mode
 * Revision 1's scratch script exhibited.
 *
 * @package Astrea\Core
 */

class ThemePatternIntegrityTest extends WP_UnitTestCase {

	/**
	 * Captures a Theme Pattern PHP file's rendered block markup by
	 * including it inside an output buffer (ABSPATH is already defined by
	 * the test bootstrap, so the file's own guard does not exit).
	 *
	 * @param string $relative_path Path relative to the theme directory, e.g. 'patterns/home-hero.php'.
	 * @return string
	 */
	private function render_pattern_file( string $relative_path ): string {
		$path = WP_CONTENT_DIR . '/themes/astrea/' . $relative_path;
		$this->assertFileExists( $path, "Pattern file {$relative_path} must exist." );

		ob_start();
		include $path;
		return (string) ob_get_clean();
	}

	/**
	 * Recursively checks whether a parsed block tree contains a block of
	 * the given name anywhere (top level or nested).
	 *
	 * @param array  $blocks Result of parse_blocks().
	 * @param string $name   Block name to look for, e.g. 'astrea/business-status'.
	 * @return bool
	 */
	private function tree_contains_block( array $blocks, string $name ): bool {
		foreach ( $blocks as $block ) {
			if ( isset( $block['blockName'] ) && $name === $block['blockName'] ) {
				return true;
			}
			if ( ! empty( $block['innerBlocks'] ) && $this->tree_contains_block( $block['innerBlocks'], $name ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Recursively asserts that, for every container block in the tree,
	 * every `innerBlocks` entry actually gets consumed by a matching
	 * non-string `innerContent` slot when re-serialized — the exact
	 * mechanism `serialize_block()` uses, and the exact place Revision
	 * 1's scratch script broke it (extra innerBlocks entries beyond the
	 * innerContent null-count are silently dropped, never a visible
	 * error).
	 *
	 * @param array $blocks Result of parse_blocks().
	 * @return void
	 */
	private function assert_no_block_loses_content_on_roundtrip( array $blocks ): void {
		foreach ( $blocks as $block ) {
			$null_slots = 0;
			foreach ( $block['innerContent'] as $chunk ) {
				if ( null === $chunk ) {
					++$null_slots;
				}
			}

			$this->assertSame(
				count( $block['innerBlocks'] ),
				$null_slots,
				sprintf(
					'Block "%s" has %d innerBlocks but only %d innerContent placeholder(s) — a naive innerBlocks-only insertion would silently drop %d block(s) on serialize_blocks(), exactly like the Revision 1 scratch script did.',
					(string) $block['blockName'],
					count( $block['innerBlocks'] ),
					$null_slots,
					count( $block['innerBlocks'] ) - $null_slots
				)
			);

			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->assert_no_block_loses_content_on_roundtrip( $block['innerBlocks'] );
			}
		}
	}

	public function test_home_hero_pattern_contains_business_status_between_primary_copy_and_buttons() {
		$markup = $this->render_pattern_file( 'patterns/home-hero.php' );

		$primary_pos = strpos( $markup, 'astrea-hero-primary' );
		$status_pos  = strpos( $markup, 'wp:astrea/business-status' );
		$buttons_pos = strpos( $markup, 'wp:buttons' );

		$this->assertNotFalse( $primary_pos, 'Hero primary copy paragraph must exist.' );
		$this->assertNotFalse( $status_pos, 'astrea/business-status must exist in the Hero pattern.' );
		$this->assertNotFalse( $buttons_pos, 'The phone/contact buttons block must exist in the Hero pattern.' );

		$this->assertTrue(
			$primary_pos < $status_pos && $status_pos < $buttons_pos,
			'Expected order: primary copy → business-status → buttons (today\'s status, then how to act on it).'
		);
	}

	public function test_home_hero_pattern_phone_and_contact_buttons_survive_parsing() {
		$markup = $this->render_pattern_file( 'patterns/home-hero.php' );
		$blocks = parse_blocks( $markup );

		$this->assertTrue( $this->tree_contains_block( $blocks, 'astrea/business-status' ) );
		$this->assertStringContainsString( 'phone_tel', $markup, 'Phone CTA button binding must be present.' );
		$this->assertStringContainsString( 'お問い合わせはこちら', $markup, 'Contact CTA button text must be present.' );
	}

	public function test_home_hero_pattern_roundtrips_without_dropping_any_block() {
		$markup = $this->render_pattern_file( 'patterns/home-hero.php' );
		$blocks = parse_blocks( $markup );

		$this->assert_no_block_loses_content_on_roundtrip( $blocks );

		$reserialized = serialize_blocks( $blocks );
		$this->assertStringContainsString( 'お問い合わせはこちら', $reserialized, 'A roundtrip must not silently drop the contact button.' );
		$this->assertStringContainsString( 'wp:astrea/business-status', $reserialized );
	}

	public function test_home_cta_pattern_contains_weekly_hours_summary_between_heading_and_buttons() {
		$markup = $this->render_pattern_file( 'patterns/home-cta.php' );

		$heading_pos = strpos( $markup, 'まずはお気軽にご相談ください' );
		$summary_pos = strpos( $markup, 'wp:astrea/business-hours-summary' );
		$buttons_pos = strpos( $markup, 'wp:buttons' );

		$this->assertNotFalse( $heading_pos );
		$this->assertNotFalse( $summary_pos, 'astrea/business-hours-summary must exist in the CTA pattern.' );
		$this->assertNotFalse( $buttons_pos );

		$this->assertTrue(
			$heading_pos < $summary_pos && $summary_pos < $buttons_pos,
			'Expected order: heading → weekly hours summary → phone/contact buttons.'
		);
	}

	public function test_home_cta_pattern_phone_and_contact_buttons_survive_parsing() {
		$markup = $this->render_pattern_file( 'patterns/home-cta.php' );
		$blocks = parse_blocks( $markup );

		$this->assertTrue( $this->tree_contains_block( $blocks, 'astrea/business-hours-summary' ) );
		$this->assertStringContainsString( 'phone_tel', $markup );
		$this->assertStringContainsString( 'お問い合わせフォームへ', $markup );
	}

	public function test_home_cta_pattern_roundtrips_without_dropping_any_block() {
		$markup = $this->render_pattern_file( 'patterns/home-cta.php' );
		$blocks = parse_blocks( $markup );

		$this->assert_no_block_loses_content_on_roundtrip( $blocks );

		$reserialized = serialize_blocks( $blocks );
		$this->assertStringContainsString( 'お問い合わせフォームへ', $reserialized, 'A roundtrip must not silently drop the contact form button.' );
		$this->assertStringContainsString( 'wp:astrea/business-hours-summary', $reserialized );
	}

	public function test_office_name_binding_still_present_in_header_and_footer() {
		$header = file_get_contents( WP_CONTENT_DIR . '/themes/astrea/parts/header.html' );
		$footer = file_get_contents( WP_CONTENT_DIR . '/themes/astrea/parts/footer.html' );

		$this->assertStringContainsString( 'office_name_home_link', $header );
		$this->assertStringContainsString( 'office_name_home_link', $footer );

		// Roundtrip check for the template parts too — same failure mode class.
		foreach ( array( 'header' => $header, 'footer' => $footer ) as $label => $html ) {
			$blocks = parse_blocks( $html );
			$this->assert_no_block_loses_content_on_roundtrip( $blocks );
			$this->assertSame( trim( $html ), trim( serialize_blocks( $blocks ) ), "{$label}.html must roundtrip byte-for-byte." );
		}
	}
}
