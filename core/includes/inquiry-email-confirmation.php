<?php
/**
 * Inquiry — notification email confirmation (Decision 003).
 *
 * A changed/newly-set notification email address must be proven
 * deliverable before it receives real production notifications. Design
 * (see docs/research/2026-08-27_construction_order_005_research.md §3):
 *
 * - Single fixed-key Transient holds the pending state
 *   {email, token_hash, requested_at}. The Transient's own TTL (24h) IS
 *   the Token's expiry — no separate expiry bookkeeping.
 * - Only a SHA-256 hash of the token is ever stored; the raw token exists
 *   only in the URL of the email that was sent.
 * - Confirming deletes the Transient (prevents replay of the same link)
 *   and only then promotes the pending email to the confirmed
 *   `notification_email` setting.
 * - Resending overwrites the same Transient key with a fresh token,
 *   implicitly invalidating any previously-sent link.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Inquiry;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Fixed Transient key holding the single in-flight pending confirmation, if any. */
const PENDING_EMAIL_TRANSIENT = 'astrea_core_contact_pending_email_confirm';

/** Token lifetime, in seconds (research doc §1.6 — an implementation judgment, not spec-mandated). */
const TOKEN_TTL = DAY_IN_SECONDS;

const CONFIRM_ACTION = 'astrea_confirm_contact_email';

/**
 * Starts (or restarts) a pending notification-email confirmation: stores
 * the pending state and emails a confirmation link to the NEW address.
 * The currently-confirmed address (if any) is untouched until confirmed.
 *
 * @param string $new_email Candidate notification email address.
 * @return bool True if the confirmation email was queued for sending.
 */
function request_email_confirmation( string $new_email ): bool {
	$new_email = sanitize_email( $new_email );

	if ( '' === $new_email || ! is_email( $new_email ) ) {
		return false;
	}

	$token = wp_generate_password( 43, false );

	set_transient(
		PENDING_EMAIL_TRANSIENT,
		array(
			'email'        => $new_email,
			'token_hash'   => hash( 'sha256', $token ),
			'requested_at' => time(),
		),
		TOKEN_TTL
	);

	$confirm_url = add_query_arg(
		array(
			'action' => CONFIRM_ACTION,
			'token'  => $token,
		),
		admin_url( 'admin-post.php' )
	);

	$subject = __( '【ASTREA】通知先メールアドレスの確認', 'astrea-core' );
	$body    = sprintf(
		/* translators: 1: confirmation URL */
		__( "以下のURLにアクセスして、問い合わせ通知の送信先メールアドレスを確認してください。\n\n%1\$s\n\nこのURLは24時間有効です。心当たりがない場合はこのメールを無視してください。", 'astrea-core' ),
		$confirm_url
	);

	// wp_mail()'s own return value only reports whether the mail server accepted
	// the message for delivery, not final delivery — this is standard WordPress
	// behavior and is not treated as a fatal error here.
	return wp_mail( $new_email, $subject, $body );
}

add_action( 'admin_post_' . CONFIRM_ACTION, __NAMESPACE__ . '\\handle_confirm_email' );
add_action( 'admin_post_nopriv_' . CONFIRM_ACTION, __NAMESPACE__ . '\\handle_confirm_email' );

/**
 * Handles a click on the confirmation link.
 *
 * @return void
 */
function handle_confirm_email() {
	$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the token itself IS the one-time credential; a separate nonce would require the confirming visitor to already hold a WordPress session, which they may not.

	$result = confirm_pending_email( $token );

	$redirect = admin_url( 'admin.php?page=' . \Astrea\Core\Inquiry\Admin\PAGE_SLUG );
	$redirect = add_query_arg( 'astrea_contact_email_confirmed', $result ? '1' : '0', $redirect );

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Verifies a token against the pending confirmation state and, if valid,
 * promotes the pending email to the confirmed setting.
 *
 * @param string $token Raw token from the confirmation URL.
 * @return bool True on success.
 */
function confirm_pending_email( string $token ): bool {
	if ( '' === $token ) {
		return false;
	}

	$pending = get_transient( PENDING_EMAIL_TRANSIENT );

	if ( ! is_array( $pending ) || empty( $pending['token_hash'] ) || empty( $pending['email'] ) ) {
		return false;
	}

	if ( ! hash_equals( $pending['token_hash'], hash( 'sha256', $token ) ) ) {
		return false;
	}

	$settings                       = get_contact_settings();
	$settings['notification_email'] = $pending['email'];

	// register_setting() (inquiry-admin.php) hooks sanitize_settings_and_reschedule()
	// onto `sanitize_option_{SETTINGS_OPTION}`, which WordPress applies to EVERY
	// update_option() call for this option once admin_init has fired in the
	// current request — not just submissions through the settings form. That
	// sanitizer deliberately discards notification_email (defense in depth
	// against a spoofed form field) to enforce Decision 003's Token-only rule,
	// which would otherwise silently undo this very write. Unhook it for this
	// one trusted, internal, already-verified write.
	$sanitize_callback = __NAMESPACE__ . '\\Admin\\sanitize_settings_and_reschedule';
	remove_filter( 'sanitize_option_' . SETTINGS_OPTION, $sanitize_callback );
	update_option( SETTINGS_OPTION, $settings );
	add_filter( 'sanitize_option_' . SETTINGS_OPTION, $sanitize_callback );

	delete_transient( PENDING_EMAIL_TRANSIENT ); // Prevents replay of the same link.

	return true;
}

/**
 * The email address currently awaiting confirmation, if any (for the
 * admin screen — never the raw token).
 *
 * @return string
 */
function get_pending_email(): string {
	$pending = get_transient( PENDING_EMAIL_TRANSIENT );

	return is_array( $pending ) && ! empty( $pending['email'] ) ? (string) $pending['email'] : '';
}
