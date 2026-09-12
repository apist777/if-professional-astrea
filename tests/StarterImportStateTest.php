<?php
/**
 * Tests for ASTREA Starter Import's site state detection foundation
 * (Construction 029-B).
 *
 * Covers the full test matrix from the Construction 029-B order (Phase
 * 11): CASE 01 through CASE 12, plus the pure-Evidence unit tests that do
 * not need a database at all and the fail-closed audit (Phase 9/12).
 *
 * @package Astrea\Core
 */

use Astrea\Core\StarterImport\SiteState;
use Astrea\Core\StarterImport\ImportStatus;
use Astrea\Core\StarterImport\Evidence;
use function Astrea\Core\StarterImport\classify;
use function Astrea\Core\StarterImport\detect_state;
use function Astrea\Core\StarterImport\gather_evidence;
use const Astrea\Core\StarterImport\IMPORT_STATUS_OPTION;
use const Astrea\Core\StarterImport\IMPORT_GENERATED_OPTION;
use Astrea\Core\Setup;
use Astrea\Core\Service;

/**
 * @covers \Astrea\Core\StarterImport
 */
class StarterImportStateTest extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( IMPORT_STATUS_OPTION );
		delete_option( IMPORT_GENERATED_OPTION );
		delete_option( Setup\GENERATED_PAGES_OPTION );
		delete_option( Setup\GENERATED_NAVIGATION_OPTION );
		parent::tear_down();
	}

	// -- Helpers --------------------------------------------------------

	/**
	 * Records a page as ASTREA-generated using Core's own existing
	 * ownership pattern, rather than a Construction 029-B-only mechanism.
	 */
	private function mark_as_astrea_generated_page( string $key, int $post_id ): void {
		$recorded         = get_option( Setup\GENERATED_PAGES_OPTION, array() );
		$recorded[ $key ] = $post_id;
		update_option( Setup\GENERATED_PAGES_OPTION, $recorded );
	}

	private function mark_as_astrea_generated_navigation( int $post_id ): void {
		update_option( Setup\GENERATED_NAVIGATION_OPTION, $post_id );
	}

	// -- CASE 01: Fresh WordPress ----------------------------------------

	public function test_case01_fresh_wordpress_is_allowed() {
		$result = detect_state();

		$this->assertSame( SiteState::FRESH, $result->state );
		$this->assertTrue( $result->import_allowed );
	}

	// -- CASE 02: ASTREA known generated pages only -----------------------

	public function test_case02_astrea_generated_pages_only_is_allowed() {
		$about_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => '事務所概要' ) );
		$this->mark_as_astrea_generated_page( 'about', $about_id );

		$nav_id = self::factory()->post->create( array( 'post_type' => 'wp_navigation' ) );
		$this->mark_as_astrea_generated_navigation( $nav_id );

		$result = detect_state();

		$this->assertSame( SiteState::ASTREA_READY, $result->state );
		$this->assertTrue( $result->import_allowed );
	}

	// -- CASE 03: Meaningful custom page exists ---------------------------

	public function test_case03_unaccounted_page_blocks_import() {
		self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => '会社案内' ) );

		$result = detect_state();

		$this->assertSame( SiteState::EXISTING_CONTENT, $result->state );
		$this->assertFalse( $result->import_allowed );
	}

	// -- CASE 04: Meaningful blog post exists -----------------------------

	public function test_case04_unaccounted_post_blocks_import() {
		self::factory()->post->create( array( 'post_type' => 'post', 'post_title' => 'お知らせ' ) );

		$result = detect_state();

		$this->assertSame( SiteState::EXISTING_CONTENT, $result->state );
		$this->assertFalse( $result->import_allowed );
	}

	// -- CASE 05: User attachment exists -----------------------------------

	/**
	 * A genuinely fresh WordPress install has zero Media Library
	 * attachments — nothing in WordPress core or ASTREA's own activation
	 * ever creates one automatically. Any attachment existing is therefore
	 * treated the same as any other unaccounted-for content: it blocks a
	 * new import (Construction 029-B Order, CASE 05: "safe result, reason
	 * documented"). This is deliberately conservative — a false positive
	 * (calling a site with real media "fresh") is the one class of mistake
	 * this module exists to avoid (Phase 12).
	 */
	public function test_case05_user_attachment_blocks_import() {
		self::factory()->post->create( array( 'post_type' => 'attachment', 'post_status' => 'inherit' ) );

		$result = detect_state();

		$this->assertSame( SiteState::EXISTING_CONTENT, $result->state );
		$this->assertFalse( $result->import_allowed );
		$this->assertSame( \Astrea\Core\StarterImport\REASON_UNACCOUNTED_ATTACHMENT, $result->reason_code );
	}

	// -- CASE 06/07: Starter marker running / failed -----------------------

	public function test_case06_running_marker_is_partial_and_blocked() {
		update_option( IMPORT_STATUS_OPTION, ImportStatus::RUNNING->value );

		$result = detect_state();

		$this->assertSame( SiteState::PARTIAL, $result->state );
		$this->assertFalse( $result->import_allowed );
	}

	public function test_case07_failed_marker_is_partial_and_blocked() {
		update_option( IMPORT_STATUS_OPTION, ImportStatus::FAILED->value );

		$result = detect_state();

		$this->assertSame( SiteState::PARTIAL, $result->state );
		$this->assertFalse( $result->import_allowed );
	}

	// -- CASE 08: Starter marker completed ---------------------------------

	public function test_case08_completed_marker_with_consistent_content_is_completed() {
		self::factory()->post->create( array( 'post_type' => Service\POST_TYPE ) );
		update_option( IMPORT_STATUS_OPTION, ImportStatus::COMPLETED->value );

		$result = detect_state();

		$this->assertSame( SiteState::COMPLETED, $result->state );
		$this->assertFalse( $result->import_allowed );
	}

	// -- CASE 09: Conflicting completed marker ------------------------------

	public function test_case09_completed_marker_without_any_content_is_unknown() {
		update_option( IMPORT_STATUS_OPTION, ImportStatus::COMPLETED->value );
		// Deliberately: no generated marker, no ASTREA CPT content at all.

		$result = detect_state();

		$this->assertSame( SiteState::UNKNOWN, $result->state );
		$this->assertFalse( $result->import_allowed );
		$this->assertSame( \Astrea\Core\StarterImport\REASON_INCONSISTENT_COMPLETED_MARKER, $result->reason_code );
	}

	// -- CASE 10: Unexpected CPT content without a marker -------------------

	public function test_case10_astrea_cpt_content_without_marker_is_existing_content() {
		self::factory()->post->create( array( 'post_type' => Service\POST_TYPE ) );
		// No IMPORT_STATUS_OPTION set at all.

		$result = detect_state();

		$this->assertSame( SiteState::EXISTING_CONTENT, $result->state );
		$this->assertFalse( $result->import_allowed );
		$this->assertSame( \Astrea\Core\StarterImport\REASON_UNEXPECTED_CPT_CONTENT, $result->reason_code );
	}

	// -- CASE 11: Repeated detection performs zero mutation ------------------

	public function test_case11_repeated_detection_never_mutates_site_state() {
		$posts_before      = wp_count_posts( 'post' )->publish ?? 0;
		$pages_before      = wp_count_posts( 'page' )->publish ?? 0;
		$attachments_before = wp_count_posts( 'attachment' )->inherit ?? 0;
		$options_before    = array(
			get_option( IMPORT_STATUS_OPTION, '__unset__' ),
			get_option( Setup\GENERATED_PAGES_OPTION, '__unset__' ),
			get_option( Setup\GENERATED_NAVIGATION_OPTION, '__unset__' ),
		);

		for ( $i = 0; $i < 100; $i++ ) {
			detect_state();
		}

		$this->assertSame( $posts_before, wp_count_posts( 'post' )->publish ?? 0 );
		$this->assertSame( $pages_before, wp_count_posts( 'page' )->publish ?? 0 );
		$this->assertSame( $attachments_before, wp_count_posts( 'attachment' )->inherit ?? 0 );
		$this->assertSame(
			$options_before,
			array(
				get_option( IMPORT_STATUS_OPTION, '__unset__' ),
				get_option( Setup\GENERATED_PAGES_OPTION, '__unset__' ),
				get_option( Setup\GENERATED_NAVIGATION_OPTION, '__unset__' ),
			)
		);
	}

	// -- CASE 12: ASTREA generated page + unrelated user content -------------

	public function test_case12_astrea_generated_page_plus_unrelated_content_blocks_import() {
		$about_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => '事務所概要' ) );
		$this->mark_as_astrea_generated_page( 'about', $about_id );

		self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => '会社案内' ) );

		$result = detect_state();

		$this->assertSame( SiteState::EXISTING_CONTENT, $result->state );
		$this->assertFalse( $result->import_allowed );
	}

	// -- Phase 13: Multisite is an explicit, unconditional BLOCK -------------

	public function test_multisite_is_always_blocked_regardless_of_content() {
		$evidence = new Evidence(
			true, // is_multisite
			array( 'count' => 0, 'untouched' => false ),
			array(),
			array(),
			false,
			array(),
			0,
			0,
			0,
			null,
			null,
			false
		);

		$result = classify( $evidence );

		$this->assertSame( SiteState::UNKNOWN, $result->state );
		$this->assertFalse( $result->import_allowed );
		$this->assertSame( \Astrea\Core\StarterImport\REASON_MULTISITE_UNSUPPORTED, $result->reason_code );
	}

	// -- Phase 9: fail-closed audit — every non-FRESH/ASTREA_READY state blocks

	public function test_fail_closed_every_state_except_fresh_and_astrea_ready_blocks_import() {
		foreach ( SiteState::cases() as $state ) {
			$expected_allowed = ( SiteState::FRESH === $state || SiteState::ASTREA_READY === $state );
			$this->assertSame(
				$expected_allowed,
				$state->allows_new_import(),
				"SiteState::{$state->name}->allows_new_import() should be " . ( $expected_allowed ? 'true' : 'false' )
			);
		}
	}

	// -- Evidence value object — pure unit tests, no database ----------------

	public function test_evidence_total_astrea_cpt_count_sums_all_types() {
		$evidence = $this->make_evidence( array( 'astrea_cpt_counts' => array( 'astrea_service' => 2, 'astrea_faq' => 3 ) ) );

		$this->assertSame( 5, $evidence->total_astrea_cpt_count() );
	}

	public function test_evidence_generated_pages_consistent_true_when_unrecorded_entries_are_simply_not_generated_yet() {
		// A page never generated yet (recorded_id null) is NOT an
		// inconsistency — only a recorded ID that no longer resolves is.
		$evidence = $this->make_evidence(
			array(
				'astrea_generated_pages' => array(
					'about' => array( 'recorded_id' => 5, 'exists' => true ),
					'home'  => array( 'recorded_id' => null, 'exists' => false ),
				),
			)
		);

		$this->assertTrue( $evidence->astrea_generated_pages_consistent() );
	}

	public function test_evidence_generated_pages_consistent_false_when_recorded_id_no_longer_exists() {
		$evidence = $this->make_evidence(
			array(
				'astrea_generated_pages' => array(
					'about' => array( 'recorded_id' => 5, 'exists' => true ),
					'home'  => array( 'recorded_id' => 9, 'exists' => false ),
				),
			)
		);

		$this->assertFalse( $evidence->astrea_generated_pages_consistent() );
	}

	/**
	 * Builds an Evidence object with sensible "empty site" defaults,
	 * overridable per-test — keeps the pure unit tests above short and
	 * focused on the one field under test.
	 *
	 * @param array<string, mixed> $overrides
	 */
	private function make_evidence( array $overrides = array() ): Evidence {
		$defaults = array(
			'is_multisite'                        => false,
			'wp_default_post'                     => array( 'count' => 0, 'untouched' => false ),
			'wp_default_pages'                     => array(),
			'astrea_generated_pages'               => array(),
			'astrea_generated_navigation_exists'   => false,
			'astrea_cpt_counts'                    => array(),
			'unaccounted_page_count'               => 0,
			'unaccounted_post_count'               => 0,
			'attachment_count'                     => 0,
			'import_status'                        => null,
			'import_version'                       => null,
			'import_generated_marker_present'      => false,
		);
		$args = array_merge( $defaults, $overrides );

		return new Evidence(
			$args['is_multisite'],
			$args['wp_default_post'],
			$args['wp_default_pages'],
			$args['astrea_generated_pages'],
			$args['astrea_generated_navigation_exists'],
			$args['astrea_cpt_counts'],
			$args['unaccounted_page_count'],
			$args['unaccounted_post_count'],
			$args['attachment_count'],
			$args['import_status'],
			$args['import_version'],
			$args['import_generated_marker_present']
		);
	}
}
