<?php
/**
 * ASTREA Starter Import — lifecycle marker write API
 * (Construction 029-C Phase 6/7).
 *
 * Construction 029-B defined `IMPORT_STATUS_OPTION`/`IMPORT_VERSION_OPTION`
 * as a READ-ONLY contract and explicitly left the write side to this
 * Construction. This file is that write side. It never touches 029-B's own
 * files (`starter-import-state.php`/`starter-import-marker.php`/
 * `starter-import-evidence.php`/`starter-import-domain.php`/
 * `starter-import-detection.php`) — it only reads their public contract
 * (`read_import_status()`, `ImportStatus`) the same way any other caller
 * would.
 *
 * Transition guard (Construction 029-C Phase 7): only three transitions
 * are permitted in this release. Every other combination — including
 * resuming a FAILED operation, since retry is Construction 029-G's
 * responsibility, not this one's — is rejected.
 *
 *   not_started / (absent) -> running   (write_running_marker）
 *   running                -> failed    (write_failed_marker)
 *   running                -> completed (write_completed_marker)
 *
 * Every write additionally requires the caller's operation_id to match
 * the operation_id already on record once one exists — this is the same
 * "ownership" principle Construction 029-C's lock uses (Phase 9): a
 * transition is only valid coming from the operation that is actually in
 * charge, not from a jaded errant caller with a stale ID.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Option holding the details of the current/most recent operation.
 * Deliberately does not duplicate IMPORT_STATUS_OPTION's value — status
 * stays the single source of truth for "what phase are we in", this only
 * holds the supporting detail.
 *
 * Shape: array{
 *   operation_id: string,
 *   starter_version: string|null,
 *   started_at: string,
 *   updated_at: string,
 *   completed_at: string|null,
 *   failed_step: string|null,
 *   last_error_code: string|null,
 * }
 */
const OPERATION_OPTION = 'astrea_starter_import_operation';

/**
 * Reads the current operation detail record, if any.
 *
 * @return array<string, mixed>|null
 */
function get_operation_detail(): ?array {
	$raw = get_option( OPERATION_OPTION, null );
	return is_array( $raw ) ? $raw : null;
}

/**
 * Transitions not_started/(absent) -> running.
 *
 * @param string      $operation_id    This operation's ID (Phase 10).
 * @param string|null $starter_version The Starter Package version about to be imported, if known.
 * @return bool True if the transition was written, false if rejected (current status is not not_started).
 */
function write_running_marker( string $operation_id, ?string $starter_version ): bool {
	$current = read_import_status();
	if ( null !== $current && ImportStatus::NOT_STARTED !== $current ) {
		return false;
	}

	$now = gmdate( 'c' );

	update_option( IMPORT_STATUS_OPTION, ImportStatus::RUNNING->value );
	update_option(
		OPERATION_OPTION,
		array(
			'operation_id'    => $operation_id,
			'starter_version' => $starter_version,
			'started_at'      => $now,
			'updated_at'      => $now,
			'completed_at'    => null,
			'failed_step'     => null,
			'last_error_code' => null,
		)
	);
	if ( null !== $starter_version ) {
		update_option( IMPORT_VERSION_OPTION, $starter_version );
	}

	return true;
}

/**
 * Transitions running -> failed. Rejected unless the operation is
 * currently running AND $operation_id matches the one on record.
 *
 * @param string $operation_id This operation's ID.
 * @param string $failed_step  Which step failed (a short machine-readable label, not a stack trace).
 * @param string $error_code   Machine-readable error code.
 * @return bool
 */
function write_failed_marker( string $operation_id, string $failed_step, string $error_code ): bool {
	if ( ! is_owning_operation( $operation_id, ImportStatus::RUNNING ) ) {
		return false;
	}

	$detail                    = get_operation_detail();
	$detail['updated_at']      = gmdate( 'c' );
	$detail['failed_step']     = $failed_step;
	$detail['last_error_code'] = $error_code;

	update_option( IMPORT_STATUS_OPTION, ImportStatus::FAILED->value );
	update_option( OPERATION_OPTION, $detail );

	return true;
}

/**
 * Transitions running -> completed. Rejected unless the operation is
 * currently running AND $operation_id matches the one on record.
 *
 * @param string $operation_id This operation's ID.
 * @return bool
 */
function write_completed_marker( string $operation_id ): bool {
	if ( ! is_owning_operation( $operation_id, ImportStatus::RUNNING ) ) {
		return false;
	}

	$detail                 = get_operation_detail();
	$now                    = gmdate( 'c' );
	$detail['updated_at']   = $now;
	$detail['completed_at'] = $now;

	update_option( IMPORT_STATUS_OPTION, ImportStatus::COMPLETED->value );
	update_option( OPERATION_OPTION, $detail );

	return true;
}

/**
 * Whether $operation_id is the one currently on record AND the marker is
 * at the expected status. Both conditions must hold — an operation_id
 * that matches but at the wrong status (or vice versa) is not "owning".
 *
 * @param string       $operation_id    This operation's ID.
 * @param ImportStatus $expected_status The status the marker must currently be at.
 * @return bool
 */
function is_owning_operation( string $operation_id, ImportStatus $expected_status ): bool {
	if ( read_import_status() !== $expected_status ) {
		return false;
	}

	$detail = get_operation_detail();
	return null !== $detail && isset( $detail['operation_id'] ) && $detail['operation_id'] === $operation_id;
}
