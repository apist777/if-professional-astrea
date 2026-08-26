<?php
/**
 * Inquiry — notification email sending (immediate + digest).
 *
 * Per Decision 005, notification timing is either "immediate" (default)
 * or a single daily "digest" at an admin-chosen time. Per Decision 004,
 * notification always happens strictly AFTER the inquiry is already
 * saved — a failed or skipped notification never affects the stored
 * record (see notify_new_inquiry()'s caller in contact-form-block.php).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Inquiry;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

const DIGEST_CRON_HOOK = 'astrea_core_contact_digest';

/**
 * Notifies about a single newly-saved inquiry, if notification_timing is
 * "immediate" and a confirmed notification address exists. Called only
 * AFTER create() has already persisted the inquiry — its return value is
 * informational only and never causes the inquiry to be un-saved.
 *
 * @param int $post_id Newly-created astrea_inquiry post ID.
 * @return void
 */
function notify_new_inquiry( int $post_id ) {
	$settings = get_contact_settings();

	if ( 'immediate' !== $settings['notification_timing'] ) {
		return;
	}

	if ( '' === $settings['notification_email'] ) {
		return; // No confirmed address yet — nothing to notify (data is already safely stored).
	}

	$inquiry = to_array( get_post( $post_id ) );

	wp_mail(
		$settings['notification_email'],
		sprintf(
			/* translators: %s: inquiry subject */
			__( '【ASTREA】新しい問い合わせ: %s', 'astrea-core' ),
			$inquiry['subject']
		),
		render_inquiry_email_body( $inquiry )
	);
}

/**
 * Plain-text email body for a single inquiry.
 *
 * @param array $inquiry Inquiry array (see to_array()).
 * @return string
 */
function render_inquiry_email_body( array $inquiry ): string {
	return sprintf(
		"%1\$s: %2\$s\n%3\$s: %4\$s\n%5\$s: %6\$s\n\n%7\$s:\n%8\$s\n",
		__( 'お名前', 'astrea-core' ),
		$inquiry['name'],
		__( 'メール', 'astrea-core' ),
		$inquiry['email'],
		__( '電話', 'astrea-core' ),
		$inquiry['phone'],
		__( '内容', 'astrea-core' ),
		$inquiry['message']
	);
}

add_action( DIGEST_CRON_HOOK, __NAMESPACE__ . '\\send_digest' );

/**
 * Sends one aggregated email listing every not-yet-notified inquiry, then
 * marks them notified. No-op when there is nothing to notify or no
 * confirmed address — this keeps the digest cron a harmless no-op on
 * quiet sites rather than sending empty emails.
 *
 * @return void
 */
function send_digest() {
	$settings = get_contact_settings();

	if ( 'digest' !== $settings['notification_timing'] || '' === $settings['notification_email'] ) {
		return;
	}

	$unnotified = get_unnotified();

	if ( empty( $unnotified ) ) {
		return;
	}

	$body = '';
	foreach ( $unnotified as $inquiry ) {
		$body .= render_inquiry_email_body( $inquiry ) . "\n----------------------------------------\n\n";
	}

	wp_mail(
		$settings['notification_email'],
		sprintf(
			/* translators: %d: number of inquiries in the digest */
			__( '【ASTREA】問い合わせまとめ通知（%d件）', 'astrea-core' ),
			count( $unnotified )
		),
		$body
	);

	mark_notified( wp_list_pluck( $unnotified, 'id' ) );
}

/**
 * (Re)schedules the daily digest cron at the configured time of day.
 * Clears any previously-scheduled event first so changing digest_time
 * takes effect immediately rather than waiting for the old time to pass.
 *
 * @return void
 */
function reschedule_digest_cron() {
	$timestamp = wp_next_scheduled( DIGEST_CRON_HOOK );
	if ( false !== $timestamp ) {
		wp_unschedule_event( $timestamp, DIGEST_CRON_HOOK );
	}

	$digest_time           = get_contact_settings()['digest_time'];
	list( $hour, $minute ) = array_map( 'intval', explode( ':', $digest_time ) );

	$now      = current_datetime();
	$next_run = $now->setTime( $hour, $minute, 0 );

	if ( $next_run <= $now ) {
		$next_run = $next_run->modify( '+1 day' );
	}

	wp_schedule_event( $next_run->getTimestamp(), 'daily', DIGEST_CRON_HOOK );
}

/**
 * Clears the digest cron (Core deactivation).
 *
 * @return void
 */
function clear_digest_cron() {
	$timestamp = wp_next_scheduled( DIGEST_CRON_HOOK );
	if ( false !== $timestamp ) {
		wp_unschedule_event( $timestamp, DIGEST_CRON_HOOK );
	}
}
