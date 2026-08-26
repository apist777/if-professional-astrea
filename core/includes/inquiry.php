<?php
/**
 * Inquiry — Core-owned Contact data layer (Construction Order 005).
 *
 * Per 02_astrea_free_v1_specification.md §15 and Decision 003-007, a
 * Contact submission is saved to Core the moment it is received,
 * independent of whether the notification email succeeds (Decision 004),
 * retained for an admin-chosen period (10/30/60/90 days, default 30,
 * Decision 004), never exposed as a search feature (Decision 006), and
 * never publicly readable (this is not user-published content like
 * Office/Professional/Service/Price/FAQ).
 *
 * Storage: a non-public, non-queryable, non-REST Custom Post Type
 * (`astrea_inquiry`) with `show_ui`/`show_in_menu` OFF — WordPress's own
 * post/postmeta storage is reused, but no native edit-post.php screen is
 * generated for it (an inquiry is an immutable record of what a visitor
 * submitted; a normal post-edit screen would visually invite editing it,
 * which is inappropriate). All admin interaction goes through
 * inquiry-admin.php's own read-only screen instead. See
 * docs/research/2026-08-27_construction_order_005_research.md §2 for the
 * full storage comparison (Options API and a custom DB table were both
 * rejected).
 *
 * Fields:
 * - 件名 (subject)  -> post_title
 * - 問い合わせ内容 (message) -> post_content (plain text only, no HTML)
 * - 送信日時         -> post_date / post_date_gmt (WordPress's own field)
 * - 氏名 (name)      -> postmeta astrea_inquiry_name
 * - メール (email)   -> postmeta astrea_inquiry_email
 * - 電話 (phone)     -> postmeta astrea_inquiry_phone
 * - 既読状態         -> postmeta astrea_inquiry_is_read (bool)
 * - まとめ通知済み   -> postmeta astrea_inquiry_notified (bool)
 * - Privacy Policy同意 -> postmeta astrea_inquiry_privacy_consent (bool)
 *
 * Deliberately NOT stored: IP address (no textual basis in spec; used only
 * ephemerally, hashed, in a short-lived rate-limit transient — never
 * attached to the inquiry record itself. See research doc §1.7).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\Inquiry;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Post type slug. Never public, never in REST — see file header. */
const POST_TYPE = 'astrea_inquiry';

/** Postmeta keys. */
const META_NAME            = 'astrea_inquiry_name';
const META_EMAIL           = 'astrea_inquiry_email';
const META_PHONE           = 'astrea_inquiry_phone';
const META_IS_READ         = 'astrea_inquiry_is_read';
const META_NOTIFIED        = 'astrea_inquiry_notified';
const META_PRIVACY_CONSENT = 'astrea_inquiry_privacy_consent';

/** Options API: Contact settings (retention, notification, field toggles). */
const SETTINGS_OPTION = 'astrea_core_contact_settings';

/** Allowed retention periods, per Decision 004. */
const RETENTION_CHOICES = array( 10, 30, 60, 90 );

/** Maximum accepted length for the free-text message body (implementation judgment — not spec-mandated). */
const MESSAGE_MAX_LENGTH = 5000;

add_action( 'init', __NAMESPACE__ . '\\register_post_type' );

/**
 * Registers the astrea_inquiry post type: storage only, no native UI.
 *
 * @return void
 */
function register_post_type() {
	\register_post_type(
		POST_TYPE,
		array(
			'label'               => __( '問い合わせ', 'astrea-core' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor' ),
			'capabilities'        => array(
				'create_posts' => 'do_not_allow', // Inquiries are only ever created by the submission handler, never authored by hand.
			),
			'map_meta_cap'        => true,
		)
	);
}

/**
 * Returns Contact settings merged with defaults. Options API, single
 * record — a singleton configuration value, unlike the 0..N content types
 * (Service/Price/FAQ), so Options API is the correct fit here (same
 * reasoning as Office Profile).
 *
 * @return array
 */
function get_contact_settings(): array {
	$defaults = array(
		'retention_days'      => 30,
		'notification_email'  => '',
		'notification_timing' => 'immediate',
		'digest_time'         => '09:00',
		'phone_enabled'       => true,
		'phone_required'      => false,
		'subject_enabled'     => true,
		'subject_required'    => false,
	);

	$stored = get_option( SETTINGS_OPTION, array() );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	return array_merge( $defaults, $stored );
}

/**
 * Sanitizes and saves Contact settings (Settings API sanitize_callback).
 *
 * @param array $input Raw input.
 * @return array
 */
function sanitize_settings( $input ): array {
	$input    = is_array( $input ) ? $input : array();
	$existing = get_contact_settings();

	$retention_days = isset( $input['retention_days'] ) ? absint( $input['retention_days'] ) : $existing['retention_days'];
	if ( ! in_array( $retention_days, RETENTION_CHOICES, true ) ) {
		$retention_days = $existing['retention_days'];
	}

	$timing = isset( $input['notification_timing'] ) && 'digest' === $input['notification_timing'] ? 'digest' : 'immediate';

	$digest_time = $existing['digest_time'];
	if ( isset( $input['digest_time'] ) && preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', (string) $input['digest_time'] ) ) {
		$digest_time = $input['digest_time'];
	}

	return array(
		'retention_days'      => $retention_days,
		'notification_email'  => $existing['notification_email'], // Never settable directly here — only via the confirmed Token flow (Decision 003).
		'notification_timing' => $timing,
		'digest_time'         => $digest_time,
		'phone_enabled'       => ! empty( $input['phone_enabled'] ),
		'phone_required'      => ! empty( $input['phone_enabled'] ) && ! empty( $input['phone_required'] ),
		'subject_enabled'     => ! empty( $input['subject_enabled'] ),
		'subject_required'    => ! empty( $input['subject_enabled'] ) && ! empty( $input['subject_required'] ),
	);
}

/**
 * Whether the Privacy Policy consent field should be shown/required —
 * tied to WordPress's own Privacy Policy page setting rather than a
 * separate custom toggle (research doc §1.1).
 *
 * @return bool
 */
function is_privacy_consent_required(): bool {
	return '' !== get_privacy_policy_url();
}

/**
 * Creates a new Inquiry record. Assumes $data has already been validated;
 * this function only sanitizes on the way into storage (defense in depth)
 * and never rejects — validation happens earlier, in the submission
 * handler (contact-form-block.php).
 *
 * @param array $data Inquiry field values: name, email, phone, subject, message, privacy_consent.
 * @return int Post ID.
 */
function create( array $data ): int {
	$subject = sanitize_text_field( $data['subject'] ?? '' );

	$post_id = wp_insert_post(
		array(
			'post_type'    => POST_TYPE,
			'post_status'  => 'private',
			'post_title'   => '' !== $subject ? $subject : __( '(件名なし)', 'astrea-core' ),
			'post_content' => sanitize_textarea_field( $data['message'] ?? '' ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return 0;
	}

	update_post_meta( $post_id, META_NAME, sanitize_text_field( $data['name'] ?? '' ) );
	update_post_meta( $post_id, META_EMAIL, sanitize_email( $data['email'] ?? '' ) );
	update_post_meta( $post_id, META_PHONE, sanitize_text_field( $data['phone'] ?? '' ) );
	update_post_meta( $post_id, META_IS_READ, false );
	update_post_meta( $post_id, META_NOTIFIED, false );
	update_post_meta( $post_id, META_PRIVACY_CONSENT, ! empty( $data['privacy_consent'] ) );

	return $post_id;
}

/**
 * Converts a WP_Post into the internal Inquiry array shape. Not a public
 * read boundary in the Service/Price/FAQ sense — Contact data is never
 * exposed generally, only consumed by inquiry-admin.php.
 *
 * @param \WP_Post $post An astrea_inquiry post.
 * @return array
 */
function to_array( \WP_Post $post ): array {
	return array(
		'id'              => $post->ID,
		'subject'         => $post->post_title,
		'message'         => $post->post_content,
		'received_at'     => $post->post_date_gmt,
		'name'            => get_post_meta( $post->ID, META_NAME, true ),
		'email'           => get_post_meta( $post->ID, META_EMAIL, true ),
		'phone'           => get_post_meta( $post->ID, META_PHONE, true ),
		'is_read'         => (bool) get_post_meta( $post->ID, META_IS_READ, true ),
		'notified'        => (bool) get_post_meta( $post->ID, META_NOTIFIED, true ),
		'privacy_consent' => (bool) get_post_meta( $post->ID, META_PRIVACY_CONSENT, true ),
	);
}

/**
 * Lists all currently-stored inquiries (newest first), for the admin
 * screen. No search/filter parameters are offered, per Decision 006.
 *
 * @return array[]
 */
function get_all(): array {
	$posts = get_posts(
		array(
			'post_type'      => POST_TYPE,
			'post_status'    => 'private',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	return array_map( __NAMESPACE__ . '\\to_array', $posts );
}

/**
 * Counts unread inquiries, for the admin menu badge and Decision 005's
 * "未確認件数を表示" requirement.
 *
 * @return int
 */
function count_unread(): int {
	$query = new \WP_Query(
		array(
			'post_type'      => POST_TYPE,
			'post_status'    => 'private',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small internal admin-only dataset, no search feature per Decision 006.
				array(
					'key'     => META_IS_READ,
					'value'   => '1',
					'compare' => '!=',
				),
			),
		)
	);

	return (int) $query->found_posts;
}

/**
 * Returns inquiries not yet flagged as notified — used by the digest
 * notification cron.
 *
 * @return array[]
 */
function get_unnotified(): array {
	return array_values( array_filter( get_all(), static fn( array $inquiry ): bool => ! $inquiry['notified'] ) );
}

/**
 * Marks an inquiry read/unread. Returns false for an unknown/wrong-type ID.
 *
 * @param int  $post_id Post ID.
 * @param bool $is_read New read state.
 * @return bool
 */
function set_read( int $post_id, bool $is_read ): bool {
	$post = get_post( $post_id );

	if ( ! $post instanceof \WP_Post || POST_TYPE !== $post->post_type ) {
		return false;
	}

	update_post_meta( $post_id, META_IS_READ, $is_read );

	return true;
}

/**
 * Marks a list of inquiries as notified (digest cron, after sending).
 *
 * @param int[] $post_ids Post IDs.
 * @return void
 */
function mark_notified( array $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		update_post_meta( (int) $post_id, META_NOTIFIED, true );
	}
}

/**
 * The retention cutoff timestamp (GMT): inquiries received before this
 * moment are past their retention window.
 *
 * @return int Unix timestamp (GMT).
 */
function retention_cutoff_timestamp(): int {
	$days = get_contact_settings()['retention_days'];

	return time() - ( $days * DAY_IN_SECONDS );
}

/**
 * Deletes every inquiry past the current retention window. Used by both
 * the daily cron and the admin_init catch-up check (research doc §5).
 *
 * @return int Number of inquiries deleted.
 */
function cleanup_expired(): int {
	$cutoff = gmdate( 'Y-m-d H:i:s', retention_cutoff_timestamp() );

	$expired_ids = get_posts(
		array(
			'post_type'      => POST_TYPE,
			'post_status'    => 'private',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'column' => 'post_date_gmt',
					'before' => $cutoff,
				),
			),
		)
	);

	foreach ( $expired_ids as $post_id ) {
		wp_delete_post( $post_id, true );
	}

	return count( $expired_ids );
}

const CLEANUP_CRON_HOOK          = 'astrea_core_contact_cleanup';
const CLEANUP_LAST_RUN_TRANSIENT = 'astrea_core_contact_cleanup_last_run';

add_action( CLEANUP_CRON_HOOK, __NAMESPACE__ . '\\cleanup_expired' );

/**
 * (Re)schedules the daily Retention cleanup Cron.
 *
 * @return void
 */
function schedule_cleanup_cron() {
	if ( false === wp_next_scheduled( CLEANUP_CRON_HOOK ) ) {
		wp_schedule_event( time(), 'daily', CLEANUP_CRON_HOOK );
	}
}

/**
 * Clears the Retention cleanup Cron (Core deactivation).
 *
 * @return void
 */
function clear_cleanup_cron() {
	$timestamp = wp_next_scheduled( CLEANUP_CRON_HOOK );
	if ( false !== $timestamp ) {
		wp_unschedule_event( $timestamp, CLEANUP_CRON_HOOK );
	}
}

add_action( 'admin_init', __NAMESPACE__ . '\\maybe_catch_up_cleanup' );

/**
 * Safety-net cleanup (research doc §5): WP-Cron is access-driven and isn't
 * guaranteed to fire exactly on schedule. This runs the same cleanup at
 * most once per day via a lightweight admin_init check, so retention
 * overruns don't linger indefinitely on a low-traffic site even if the
 * Cron event is delayed. No custom scheduler/daemon is introduced.
 *
 * @return void
 */
function maybe_catch_up_cleanup() {
	if ( false !== get_transient( CLEANUP_LAST_RUN_TRANSIENT ) ) {
		return;
	}

	cleanup_expired();
	set_transient( CLEANUP_LAST_RUN_TRANSIENT, time(), DAY_IN_SECONDS );
}

/**
 * Inquiries within the current retention window, for CSV Export
 * (Decision 006: "CSVには保存期間内のデータのみを含め...").
 *
 * @return array[]
 */
function get_exportable(): array {
	$cutoff = retention_cutoff_timestamp();

	return array_values(
		array_filter(
			get_all(),
			static function ( array $inquiry ) use ( $cutoff ): bool {
				return strtotime( $inquiry['received_at'] . ' GMT' ) >= $cutoff;
			}
		)
	);
}
