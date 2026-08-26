<?php
/**
 * Tests for ASTREA Core's Contact Form validation, rate limiting, and CSV
 * export sanitization (Construction Order 005).
 *
 * The actual admin-post submission handler (handle_submit()) always ends
 * in wp_safe_redirect() + exit and is therefore end-to-end tested via real
 * HTTP requests in tools/ci/smoke-test.sh, not here. This file covers the
 * pure functions the handler is built from.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Inquiry;

/**
 * @covers \Astrea\Core\Inquiry
 */
class ContactFormTest extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( Inquiry\SETTINGS_OPTION );
		parent::tear_down();
	}

	private function valid_values(): array {
		return array(
			'name'            => 'テスト太郎',
			'email'           => 'test@example.com',
			'phone'           => '',
			'subject'         => '',
			'message'         => 'テストメッセージ',
			'privacy_consent' => false,
		);
	}

	// -- Validation -----------------------------------------------------------

	public function test_valid_submission_has_no_errors() {
		$this->assertSame( array(), Inquiry\validate( $this->valid_values() ) );
	}

	public function test_missing_name_is_an_error() {
		$errors = Inquiry\validate( array_merge( $this->valid_values(), array( 'name' => '' ) ) );
		$this->assertArrayHasKey( 'name', $errors );
	}

	public function test_missing_email_is_an_error() {
		$errors = Inquiry\validate( array_merge( $this->valid_values(), array( 'email' => '' ) ) );
		$this->assertArrayHasKey( 'email', $errors );
	}

	public function test_malformed_email_is_an_error() {
		$errors = Inquiry\validate( array_merge( $this->valid_values(), array( 'email' => 'not-an-email' ) ) );
		$this->assertArrayHasKey( 'email', $errors );
	}

	public function test_missing_message_is_an_error() {
		$errors = Inquiry\validate( array_merge( $this->valid_values(), array( 'message' => '' ) ) );
		$this->assertArrayHasKey( 'message', $errors );
	}

	public function test_overlong_message_is_an_error() {
		$errors = Inquiry\validate( array_merge( $this->valid_values(), array( 'message' => str_repeat( 'あ', Inquiry\MESSAGE_MAX_LENGTH + 1 ) ) ) );
		$this->assertArrayHasKey( 'message', $errors );
	}

	public function test_phone_required_when_setting_enabled() {
		update_option( Inquiry\SETTINGS_OPTION, array( 'phone_enabled' => true, 'phone_required' => true ) );

		$errors = Inquiry\validate( array_merge( $this->valid_values(), array( 'phone' => '' ) ) );
		$this->assertArrayHasKey( 'phone', $errors );
	}

	public function test_phone_not_required_by_default() {
		$errors = Inquiry\validate( array_merge( $this->valid_values(), array( 'phone' => '' ) ) );
		$this->assertArrayNotHasKey( 'phone', $errors );
	}

	public function test_privacy_consent_required_when_policy_page_exists() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		update_option( 'wp_page_for_privacy_policy', $page_id );

		$errors = Inquiry\validate( array_merge( $this->valid_values(), array( 'privacy_consent' => false ) ) );
		$this->assertArrayHasKey( 'privacy_consent', $errors );

		update_option( 'wp_page_for_privacy_policy', 0 );
	}

	public function test_privacy_consent_not_required_without_policy_page() {
		update_option( 'wp_page_for_privacy_policy', 0 );

		$errors = Inquiry\validate( array_merge( $this->valid_values(), array( 'privacy_consent' => false ) ) );
		$this->assertArrayNotHasKey( 'privacy_consent', $errors );
	}

	// -- Rate limiting ----------------------------------------------------------

	public function test_not_rate_limited_before_any_submission() {
		$this->assertFalse( Inquiry\is_rate_limited() );
	}

	public function test_rate_limited_immediately_after_a_submission() {
		Inquiry\record_rate_limit();
		$this->assertTrue( Inquiry\is_rate_limited(), 'The minimum-interval throttle must reject an immediate resubmission.' );
	}

	public function test_rate_limited_after_max_submissions_per_hour() {
		$hash = Inquiry\rate_limit_ip_hash();
		// Simulate MAX_PER_HOUR submissions having already happened, without the
		// short per-submission interval throttle also being in effect.
		set_transient( 'astrea_contact_rl_hour_' . $hash, Inquiry\RATE_LIMIT_MAX_PER_HOUR, HOUR_IN_SECONDS );

		$this->assertTrue( Inquiry\is_rate_limited() );
	}

	// -- CSV Export Security -----------------------------------------------------

	public function test_csv_cell_formula_injection_is_neutralized() {
		foreach ( array( '=SUM(A1)', '+1+1', '-1+1', '@SUM(A1)' ) as $dangerous ) {
			$sanitized = \Astrea\Core\Inquiry\Admin\sanitize_csv_cell( $dangerous );
			$this->assertStringStartsWith( "'", $sanitized );
		}
	}

	public function test_csv_cell_normal_text_is_unchanged() {
		$this->assertSame( '田中太郎', \Astrea\Core\Inquiry\Admin\sanitize_csv_cell( '田中太郎' ) );
	}
}
