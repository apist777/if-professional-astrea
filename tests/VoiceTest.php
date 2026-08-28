<?php
/**
 * Tests for ASTREA Core's VOICE（お客様の声）feature (Construction Order 010).
 *
 * Core-only integration tests (WP_UnitTestCase, real WordPress post APIs).
 * Theme display (Query Loop rendering on archive-astrea_voice.html) and
 * the Core-inactive/deactivate/reactivate states are covered by
 * tools/ci/smoke-test.sh against a real running site.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Voice;

/**
 * @covers \Astrea\Core\Voice
 */
class VoiceTest extends WP_UnitTestCase {

	private function create_voice( array $args = array() ): int {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_type'    => Voice\POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => 'テスト表示名',
					'post_content' => 'テストの声',
				),
				$args
			)
		);
	}

	public function test_zero_voices_returns_empty_array() {
		$this->assertSame( array(), Voice\get_voices() );
	}

	public function test_one_voice_is_returned() {
		$id = $this->create_voice( array( 'post_title' => '40代・会社経営者様', 'post_content' => '大変助かりました。' ) );

		$voices = Voice\get_voices();

		$this->assertCount( 1, $voices );
		$this->assertSame( '40代・会社経営者様', $voices[0]['display_name'] );
		$this->assertSame( '大変助かりました。', $voices[0]['content'] );
	}

	public function test_multiple_voices_are_all_returned() {
		$this->create_voice();
		$this->create_voice();

		$this->assertCount( 2, Voice\get_voices() );
	}

	public function test_display_order_uses_menu_order_then_title_then_id() {
		$bravo   = $this->create_voice( array( 'post_title' => 'Bravoの声', 'menu_order' => 1 ) );
		$alpha   = $this->create_voice( array( 'post_title' => 'Alphaの声', 'menu_order' => 0 ) );
		$charlie = $this->create_voice( array( 'post_title' => 'Charlieの声', 'menu_order' => 1 ) );

		$names = array_column( Voice\get_voices(), 'display_name' );

		$this->assertSame( array( 'Alphaの声', 'Bravoの声', 'Charlieの声' ), $names );
	}

	public function test_get_voices_excludes_drafts() {
		$this->create_voice( array( 'post_status' => 'draft' ) );

		$this->assertSame( array(), Voice\get_voices() );
	}

	public function test_get_voice_rejects_nonexistent_id() {
		$this->assertNull( Voice\get_voice( 999999 ) );
	}

	public function test_is_public_and_has_archive() {
		$post_type_object = get_post_type_object( Voice\POST_TYPE );

		$this->assertTrue( $post_type_object->public );
		$this->assertSame( 'voices', $post_type_object->has_archive );
	}

	public function test_does_not_support_thumbnail() {
		$this->assertFalse( post_type_supports( Voice\POST_TYPE, 'thumbnail' ), 'VOICE must not encourage uploading real customer photos.' );
	}

	public function test_title_placeholder_guides_away_from_real_names() {
		$post = get_default_post_to_edit( Voice\POST_TYPE, false );

		$placeholder = apply_filters( 'enter_title_here', __( 'Add title' ), $post ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- invoking WordPress core's own `enter_title_here` filter, not declaring a new hook.

		$this->assertStringContainsString( '実名は入力しないでください', $placeholder );
	}

	public function test_deactivate_does_not_delete_voices() {
		$id = $this->create_voice( array( 'post_title' => '削除されないはずの声' ) );

		\Astrea\Core\deactivate();

		$this->assertNotNull( get_post( $id ), 'Decision 019: deactivation must never delete Core-owned data.' );
		$this->assertSame( '削除されないはずの声', get_post( $id )->post_title );
	}

	// -- astrea/voice-list Dynamic Block ----------------------------------

	public function test_voice_list_block_self_hides_with_zero_items_by_default() {
		$this->assertSame( '', Voice\render_voice_list_block() );
	}

	public function test_voice_list_block_shows_empty_message_when_set() {
		$html = Voice\render_voice_list_block( array( 'emptyMessage' => '現在準備中です。' ) );

		$this->assertStringContainsString( '現在準備中です。', $html );
	}

	public function test_voice_list_block_uses_blockquote_and_cite() {
		$this->create_voice( array( 'post_title' => '40代・会社経営者様', 'post_content' => '大変助かりました。' ) );

		$html = Voice\render_voice_list_block();

		$this->assertStringContainsString( '<blockquote>', $html );
		$this->assertStringContainsString( '<cite>40代・会社経営者様</cite>', $html );
		$this->assertStringContainsString( '大変助かりました。', $html );
	}

	public function test_voice_list_block_heading_only_appears_alongside_content() {
		$this->create_voice();

		$with_content = Voice\render_voice_list_block( array( 'heading' => 'お客様の声' ) );
		$this->assertStringContainsString( '<h2>お客様の声</h2>', $with_content );
	}

	public function test_voice_list_block_heading_is_not_emitted_alone_with_zero_items() {
		$html = Voice\render_voice_list_block( array( 'heading' => 'お客様の声' ) );

		$this->assertSame( '', $html, 'A heading must never be emitted alone when there are zero VOICE entries.' );
	}

	public function test_voice_list_block_respects_limit() {
		$this->create_voice( array( 'post_title' => '声1' ) );
		$this->create_voice( array( 'post_title' => '声2' ) );
		$this->create_voice( array( 'post_title' => '声3' ) );

		$html = Voice\render_voice_list_block( array( 'limit' => 2 ) );

		$this->assertStringContainsString( '声1', $html );
		$this->assertStringContainsString( '声2', $html );
		$this->assertStringNotContainsString( '声3', $html );
	}
}
