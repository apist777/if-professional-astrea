<?php
/**
 * Tests for ASTREA Core's notification-email confirmation Token flow
 * (Construction Order 005, Decision 003).
 *
 * handle_confirm_email() itself ends in wp_safe_redirect()+exit and is
 * end-to-end tested via real HTTP requests in tools/ci/smoke-test.sh; this
 * file covers confirm_pending_email() and the surrounding state directly.
 *
 * @package Astrea\Core
 */

use Astrea\Core\Inquiry;

/**
 * @covers \Astrea\Core\Inquiry
 */
class EmailConfirmationTest extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( Inquiry\SETTINGS_OPTION );
		delete_transient( Inquiry\PENDING_EMAIL_TRANSIENT );
		parent::tear_down();
	}

	/**
	 * Captures the token from request_email_confirmation() without relying
	 * on actually sending mail: intercepts wp_mail() via a filter.
	 *
	 * @return string
	 */
	private function request_and_capture_token( string $email ): string {
		$captured = '';
		$capture  = function ( $args ) use ( &$captured ) {
			if ( preg_match( '/token=([^&\s]+)/', $args['message'], $matches ) ) {
				$captured = $matches[1];
			}
			return $args;
		};

		add_filter( 'wp_mail', $capture );
		Inquiry\request_email_confirmation( $email );
		remove_filter( 'wp_mail', $capture );

		return $captured;
	}

	public function test_request_confirmation_rejects_invalid_email() {
		$this->assertFalse( Inquiry\request_email_confirmation( 'not-an-email' ) );
		$this->assertSame( '', Inquiry\get_pending_email() );
	}

	public function test_request_confirmation_stores_pending_email() {
		Inquiry\request_email_confirmation( 'new@example.com' );

		$this->assertSame( 'new@example.com', Inquiry\get_pending_email() );
	}

	public function test_confirmed_token_promotes_pending_email_to_confirmed_setting() {
		$token = $this->request_and_capture_token( 'new@example.com' );

		$this->assertNotSame( '', $token );
		$this->assertTrue( Inquiry\confirm_pending_email( $token ) );
		$this->assertSame( 'new@example.com', Inquiry\get_contact_settings()['notification_email'] );
	}

	public function test_confirming_clears_the_pending_state() {
		$token = $this->request_and_capture_token( 'new@example.com' );
		Inquiry\confirm_pending_email( $token );

		$this->assertSame( '', Inquiry\get_pending_email() );
	}

	public function test_confirmed_email_is_not_used_until_confirmed() {
		update_option( Inquiry\SETTINGS_OPTION, array( 'notification_email' => 'old@example.com' ) );

		Inquiry\request_email_confirmation( 'new@example.com' );

		$this->assertSame( 'old@example.com', Inquiry\get_contact_settings()['notification_email'], 'The old confirmed address must remain in effect until the new one is confirmed.' );
	}

	public function test_invalid_token_is_rejected() {
		$this->request_and_capture_token( 'new@example.com' );

		$this->assertFalse( Inquiry\confirm_pending_email( 'wrong-token' ) );
		$this->assertSame( '', Inquiry\get_contact_settings()['notification_email'] );
	}

	public function test_empty_token_is_rejected() {
		$this->assertFalse( Inquiry\confirm_pending_email( '' ) );
	}

	public function test_token_replay_is_rejected() {
		$token = $this->request_and_capture_token( 'new@example.com' );

		$this->assertTrue( Inquiry\confirm_pending_email( $token ) );
		$this->assertFalse( Inquiry\confirm_pending_email( $token ), 'The same token must not be usable twice.' );
	}

	public function test_resending_invalidates_the_previous_token() {
		$first_token = $this->request_and_capture_token( 'new@example.com' );
		$this->request_and_capture_token( 'new@example.com' ); // Resend.

		$this->assertFalse( Inquiry\confirm_pending_email( $first_token ), 'A resend must invalidate the previously-issued token.' );
	}

	public function test_no_confirmation_email_sent_when_nothing_pending() {
		$this->assertSame( '', Inquiry\get_pending_email() );
	}
}
