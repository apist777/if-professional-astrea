<?php
/**
 * ASTREA Starter Import — concurrency lock (Construction 029-C Phase 8/9).
 *
 * A Preflight PASS is a point-in-time answer; nothing stops two requests
 * from both passing Preflight and then both trying to start an operation
 * (Construction 029-C Phase 8's race). This lock is the primitive that
 * actually prevents two operations from running at once — Preflight alone
 * cannot.
 *
 * Race safety relies on `add_option()`'s own behavior, not a
 * read-then-write pair: WordPress's `add_option()` refuses to insert (and
 * returns false) when the option name already exists, because the
 * `wp_options.option_name` column has a UNIQUE constraint — the database
 * itself, not application logic, is what makes two concurrent
 * `acquire_lock()` calls resolve to exactly one winner. A naive
 * `get_option()` existence check followed by `update_option()` would have
 * a race window between the two calls; `add_option()` does not.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/** Option holding the current lock, if any. Absence of this option means "unlocked". */
const LOCK_OPTION = 'astrea_starter_import_lock';

/** Seconds after which a lock with no heartbeat update is considered stale (Construction 029-C Phase 9). Detection only — never auto-cleared here. */
const LOCK_STALE_AFTER_SECONDS = 3600;

/**
 * Attempts to acquire the Starter Import lock for the given operation ID.
 *
 * @param string $operation_id This operation's ID (Phase 10).
 * @return bool True if this operation now owns the lock, false if another operation already holds it.
 */
function acquire_lock( string $operation_id ): bool {
	$now = gmdate( 'c' );

	$lock = array(
		'operation_id' => $operation_id,
		'created_at'   => $now,
		'updated_at'   => $now,
	);

	// autoload = 'no': a lock is short-lived, high-churn state that has no
	// business being loaded into every single page request's alloptions
	// cache.
	return add_option( LOCK_OPTION, $lock, '', false );
}

/**
 * Releases the lock, but ONLY if the caller is the operation that
 * currently owns it (Construction 029-C Phase 9: "別operationが他人のlockを
 * 解除できない"). Releasing a lock you don't own is a no-op that returns
 * false, never an error that could be swallowed into "released anyway".
 *
 * @param string $operation_id The operation attempting to release.
 * @return bool True if released, false if there was no lock or it belonged to a different operation.
 */
function release_lock( string $operation_id ): bool {
	$current = get_lock();
	if ( null === $current || $current['operation_id'] !== $operation_id ) {
		return false;
	}

	return delete_option( LOCK_OPTION );
}

/**
 * Reads the current lock, if any.
 *
 * @return array{operation_id:string, created_at:string, updated_at:string}|null
 */
function get_lock(): ?array {
	$raw = get_option( LOCK_OPTION, null );
	if ( ! is_array( $raw ) || ! isset( $raw['operation_id'], $raw['created_at'], $raw['updated_at'] ) ) {
		return null;
	}
	return $raw;
}

/**
 * Whether a lock is currently held by anyone.
 *
 * @return bool
 */
function is_locked(): bool {
	return null !== get_lock();
}

/**
 * Whether the current lock (if any) looks stale — old enough that the
 * operation holding it has very likely died without releasing it.
 *
 * Construction 029-C Phase 9 policy: detection only. This function NEVER
 * clears the lock itself; a stale lock is surfaced as information for a
 * human or for Construction 029-G's future recovery flow to act on, not
 * something this module silently works around.
 *
 * @return bool False when there is no lock at all (nothing to be stale).
 */
function is_lock_stale(): bool {
	$lock = get_lock();
	if ( null === $lock ) {
		return false;
	}

	$updated = strtotime( $lock['updated_at'] );
	if ( false === $updated ) {
		// An unparseable timestamp is itself a reason to treat the lock as
		// suspect/stale rather than trust it blindly.
		return true;
	}

	return ( time() - $updated ) > LOCK_STALE_AFTER_SECONDS;
}
