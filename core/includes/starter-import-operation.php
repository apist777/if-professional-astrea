<?php
/**
 * ASTREA Starter Import — operation ID + start-transaction foundation
 * (Construction 029-C Phase 10/15).
 *
 * `begin_operation()` is the one function that composes Preflight + Lock
 * + Lifecycle Marker into a single, defense-in-depth-aware "may I start,
 * and if so, claim it" primitive. It generates NO Starter content — no
 * post, page, attachment, or navigation entity is created anywhere in this
 * file. Construction 029-D is the first Construction that will call this
 * and then actually build content.
 *
 * Ordering (Construction 029-C Phase 8's race: two requests both pass
 * Preflight, then both try to start):
 *   1. Run Preflight. A BLOCK here means we stop before touching anything.
 *   2. Generate this attempt's operation_id.
 *   3. Acquire the lock. This is the actual race-safe step (Phase 8/9) —
 *      `acquire_lock()`'s `add_option()` guarantees only one caller wins
 *      even if both reached step 1 with a PASS at the same instant.
 *   4. Having WON the lock, re-check site state once more. The window
 *      between step 1's Preflight and step 3's lock acquisition is where
 *      another process could have changed the site (e.g. a human created
 *      a page in another tab) — re-checking closes that window before any
 *      marker is written.
 *   5. Write the running marker. If this fails for any reason, release
 *      the lock we just acquired rather than leave an orphaned lock with
 *      no corresponding running marker.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Generates a new operation ID. Uses WordPress's own UUID v4 generator
 * (available since WP 4.7) rather than inventing a bespoke ID scheme.
 * This ID correlates markers/lock/ownership-registry entries with one
 * attempt (Construction 029-C Phase 10) — it is NOT a security token and
 * must never be treated as one (e.g. as a substitute for a capability
 * check or a nonce).
 *
 * @return string
 */
function generate_operation_id(): string {
	return wp_generate_uuid4();
}

/**
 * Attempts to begin a new Starter Import operation.
 *
 * @param string|null $starter_version The Starter Package version about to be imported, if known.
 * @return BeginOperationResult
 */
function begin_operation( ?string $starter_version = null ): BeginOperationResult {
	$preflight = run_preflight();

	if ( ! $preflight->can_start ) {
		return new BeginOperationResult(
			false,
			null,
			'PREFLIGHT_BLOCKED',
			'Preflight did not pass; see $preflight->blockers for the specific reason(s).',
			$preflight
		);
	}

	$operation_id = generate_operation_id();

	if ( ! acquire_lock( $operation_id ) ) {
		return new BeginOperationResult(
			false,
			null,
			'LOCK_ACQUISITION_FAILED',
			'Another operation already holds the Starter Import lock.',
			$preflight
		);
	}

	// Close the race window between the Preflight snapshot above and this
	// moment: re-check site state now that we actually hold the lock.
	$recheck = detect_state();
	if ( ! $recheck->state->allows_new_import() ) {
		release_lock( $operation_id );
		return new BeginOperationResult(
			false,
			null,
			'STATE_CHANGED_SINCE_PREFLIGHT',
			'Site state changed between Preflight and lock acquisition: ' . $recheck->reason_code,
			$preflight
		);
	}

	if ( ! write_running_marker( $operation_id, $starter_version ) ) {
		// Should not happen (we just confirmed via check_import_lifecycle_marker
		// and $recheck that nothing else is running), but never leave an
		// orphaned lock behind if it somehow does.
		release_lock( $operation_id );
		return new BeginOperationResult(
			false,
			null,
			'MARKER_WRITE_FAILED',
			'Failed to write the running lifecycle marker.',
			$preflight
		);
	}

	stamp_ownership_registry_context( $operation_id, $starter_version );

	return new BeginOperationResult( true, $operation_id, 'OPERATION_STARTED', 'Starter Import operation started.', $preflight );
}
