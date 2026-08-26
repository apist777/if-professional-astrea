<?php
/**
 * Tests for ASTREA Core's Inquiry (Contact) data layer (Construction Order 005).
 *
 * Core-only integration tests (WP_UnitTestCase, real WordPress post/postmeta/
 * option APIs). Real end-to-end HTTP submission, admin screen rendering, CSV
 * download headers, and Core-inactive/deactivate/reactivate states are
 * covered by tools/ci/smoke-test.sh against a real running site.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Inquiry;

/**
 * @covers \Astrea\Core\Inquiry
 */
class InquiryTest extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( Inquiry\SETTINGS_OPTION );
		parent::tear_down();
	}

	private function create_inquiry( array $data = array(), string $received_at = '' ): int {
		$id = Inquiry\create(
			array_merge(
				array(
					'name'            => 'テスト太郎',
					'email'           => 'test@example.com',
					'phone'           => '',
					'subject'         => 'テスト件名',
					'message'         => 'テストメッセージ',
					'privacy_consent' => true,
				),
				$data
			)
		);

		if ( '' !== $received_at ) {
			wp_update_post(
				array(
					'ID'            => $id,
					'post_date'     => $received_at,
					'post_date_gmt' => $received_at,
				)
			);
		}

		return $id;
	}

	// -- Storage / CRUD ---------------------------------------------------

	public function test_create_stores_all_fields() {
		$id      = $this->create_inquiry();
		$all     = Inquiry\get_all();
		$inquiry = $all[0];

		$this->assertSame( $id, $inquiry['id'] );
		$this->assertSame( 'テスト件名', $inquiry['subject'] );
		$this->assertSame( 'テストメッセージ', $inquiry['message'] );
		$this->assertSame( 'テスト太郎', $inquiry['name'] );
		$this->assertSame( 'test@example.com', $inquiry['email'] );
		$this->assertFalse( $inquiry['is_read'] );
		$this->assertFalse( $inquiry['notified'] );
		$this->assertTrue( $inquiry['privacy_consent'] );
	}

	public function test_create_uses_private_post_status() {
		$id = $this->create_inquiry();

		$this->assertSame( 'private', get_post_status( $id ) );
	}

	public function test_create_falls_back_to_placeholder_title_when_subject_empty() {
		$id   = $this->create_inquiry( array( 'subject' => '' ) );
		$post = get_post( $id );

		$this->assertNotSame( '', $post->post_title );
	}

	public function test_create_sanitizes_html_out_of_message() {
		$id      = $this->create_inquiry( array( 'message' => '<script>alert(1)</script>安全' ) );
		$inquiry = Inquiry\get_all()[0];

		$this->assertStringNotContainsString( '<script>', $inquiry['message'] );
		$this->assertStringContainsString( '安全', $inquiry['message'] );
		$this->assertSame( $id, $inquiry['id'] );
	}

	public function test_post_type_is_not_public_or_in_rest() {
		$post_type = get_post_type_object( Inquiry\POST_TYPE );

		$this->assertFalse( $post_type->public );
		$this->assertFalse( $post_type->publicly_queryable );
		$this->assertFalse( $post_type->show_in_rest );
		$this->assertFalse( $post_type->show_ui );
	}

	public function test_zero_inquiries_returns_empty_array() {
		$this->assertSame( array(), Inquiry\get_all() );
	}

	public function test_get_all_orders_newest_first() {
		$older = $this->create_inquiry( array( 'subject' => 'Older' ), '2020-01-01 00:00:00' );
		$newer = $this->create_inquiry( array( 'subject' => 'Newer' ), '2020-06-01 00:00:00' );

		$ids = wp_list_pluck( Inquiry\get_all(), 'id' );

		$this->assertSame( array( $newer, $older ), $ids );
	}

	// -- Read state ---------------------------------------------------------

	public function test_set_read_toggles_state() {
		$id = $this->create_inquiry();

		Inquiry\set_read( $id, true );
		$this->assertTrue( Inquiry\get_all()[0]['is_read'] );

		Inquiry\set_read( $id, false );
		$this->assertFalse( Inquiry\get_all()[0]['is_read'] );
	}

	public function test_set_read_rejects_wrong_post_type() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertFalse( Inquiry\set_read( $page_id, true ) );
	}

	public function test_count_unread_reflects_read_state() {
		$this->create_inquiry();
		$read_id = $this->create_inquiry();
		Inquiry\set_read( $read_id, true );

		$this->assertSame( 1, Inquiry\count_unread() );
	}

	// -- Settings -------------------------------------------------------------

	public function test_default_settings() {
		$settings = Inquiry\get_contact_settings();

		$this->assertSame( 30, $settings['retention_days'] );
		$this->assertSame( '', $settings['notification_email'] );
		$this->assertSame( 'immediate', $settings['notification_timing'] );
		$this->assertTrue( $settings['phone_enabled'] );
		$this->assertFalse( $settings['phone_required'] );
	}

	public function test_sanitize_settings_rejects_invalid_retention_days() {
		$sanitized = Inquiry\sanitize_settings( array( 'retention_days' => 45 ) );

		$this->assertSame( 30, $sanitized['retention_days'] ); // Falls back to existing default, 45 is not an allowed choice.
	}

	public function test_sanitize_settings_accepts_valid_retention_days() {
		foreach ( Inquiry\RETENTION_CHOICES as $days ) {
			$sanitized = Inquiry\sanitize_settings( array( 'retention_days' => $days ) );
			$this->assertSame( $days, $sanitized['retention_days'] );
		}
	}

	public function test_sanitize_settings_never_sets_notification_email_directly() {
		$sanitized = Inquiry\sanitize_settings( array( 'notification_email' => 'attacker@example.com' ) );

		$this->assertSame( '', $sanitized['notification_email'], 'notification_email must only ever change via the confirmed Token flow (Decision 003).' );
	}

	public function test_sanitize_settings_required_implies_enabled() {
		$sanitized = Inquiry\sanitize_settings( array( 'phone_required' => '1' ) ); // phone_enabled not set.

		$this->assertFalse( $sanitized['phone_enabled'] );
		$this->assertFalse( $sanitized['phone_required'], 'A field cannot be required while disabled.' );
	}

	public function test_sanitize_settings_rejects_malformed_digest_time() {
		update_option( Inquiry\SETTINGS_OPTION, array( 'digest_time' => '09:00' ) );

		$sanitized = Inquiry\sanitize_settings( array( 'digest_time' => 'not-a-time' ) );

		$this->assertSame( '09:00', $sanitized['digest_time'] );
	}

	public function test_privacy_consent_required_reflects_privacy_policy_page() {
		update_option( 'wp_page_for_privacy_policy', 0 );
		$this->assertFalse( Inquiry\is_privacy_consent_required() );

		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		update_option( 'wp_page_for_privacy_policy', $page_id );
		$this->assertTrue( Inquiry\is_privacy_consent_required() );

		update_option( 'wp_page_for_privacy_policy', 0 );
	}

	// -- Retention / Cleanup ---------------------------------------------------

	public function test_retention_cutoff_reflects_configured_days() {
		update_option( Inquiry\SETTINGS_OPTION, array( 'retention_days' => 10 ) );

		$expected = time() - ( 10 * DAY_IN_SECONDS );
		$this->assertEqualsWithDelta( $expected, Inquiry\retention_cutoff_timestamp(), 5 );
	}

	public function test_cleanup_expired_removes_only_expired_inquiries() {
		update_option( Inquiry\SETTINGS_OPTION, array( 'retention_days' => 30 ) );

		$expired = $this->create_inquiry( array( 'subject' => 'Expired' ), gmdate( 'Y-m-d H:i:s', time() - ( 40 * DAY_IN_SECONDS ) ) );
		$kept    = $this->create_inquiry( array( 'subject' => 'Kept' ), gmdate( 'Y-m-d H:i:s', time() - ( 5 * DAY_IN_SECONDS ) ) );

		$deleted_count = Inquiry\cleanup_expired();

		$this->assertSame( 1, $deleted_count );
		$this->assertNull( get_post( $expired ) );
		$this->assertNotNull( get_post( $kept ) );
	}

	public function test_cleanup_expired_is_a_noop_when_nothing_expired() {
		$this->create_inquiry();

		$this->assertSame( 0, Inquiry\cleanup_expired() );
	}

	public function test_get_exportable_excludes_expired_inquiries() {
		update_option( Inquiry\SETTINGS_OPTION, array( 'retention_days' => 30 ) );

		// Not yet physically cleaned up, but past retention — must not appear
		// in an Export even before the Cron/catch-up cleanup runs (Decision 006).
		$this->create_inquiry( array( 'subject' => 'Expired' ), gmdate( 'Y-m-d H:i:s', time() - ( 40 * DAY_IN_SECONDS ) ) );
		$kept = $this->create_inquiry( array( 'subject' => 'Kept' ), gmdate( 'Y-m-d H:i:s', time() - ( 5 * DAY_IN_SECONDS ) ) );

		$exportable = Inquiry\get_exportable();

		$this->assertCount( 1, $exportable );
		$this->assertSame( $kept, $exportable[0]['id'] );
	}

	// -- Deactivation retains data ------------------------------------------

	public function test_deactivate_does_not_delete_inquiries() {
		$id = $this->create_inquiry( array( 'subject' => '削除されないはずの問い合わせ' ) );

		\Astrea\Core\deactivate();

		$this->assertNotNull( get_post( $id ), 'Decision 019: deactivation must never delete Core-owned data.' );
	}
}
