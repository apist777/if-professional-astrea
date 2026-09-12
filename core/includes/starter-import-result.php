<?php
/**
 * ASTREA Starter Import — classification result value object
 * (Construction 029-B Phase 3).
 *
 * `StateResult` is the outcome of classifying one `Evidence` snapshot
 * (see starter-import-evidence.php / starter-import-domain.php): never a
 * bare string, always state + import_allowed + a machine-readable reason
 * code + a short human-readable diagnostic + the evidence it was based on
 * + any warnings.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * The outcome of classifying one `Evidence` snapshot.
 */
final class StateResult {

	/**
	 * Constructs an immutable classification result.
	 *
	 * @param SiteState $state          The canonical state.
	 * @param bool      $import_allowed Whether a NEW Starter Import may start (mirrors $state->allows_new_import(), stored explicitly so callers never have to re-derive it).
	 * @param string    $reason_code    Machine-readable reason, one of the REASON_* constants below.
	 * @param string    $diagnostic     Short, English, developer-facing diagnostic (NOT end-user copy — Construction 029-B Phase 3 explicitly keeps Admin-facing translation out of the domain layer; that is a Construction 029-H concern).
	 * @param Evidence  $evidence       The evidence this result was derived from.
	 * @param string[]  $warnings       Non-fatal observations worth surfacing even when the state itself is otherwise a clear PASS (e.g. a Starter version mismatch that isn't this Construction's concern to police).
	 */
	public function __construct(
		public readonly SiteState $state,
		public readonly bool $import_allowed,
		public readonly string $reason_code,
		public readonly string $diagnostic,
		public readonly Evidence $evidence,
		public readonly array $warnings = array()
	) {
	}
}

// -- Machine-readable reason codes (Construction 029-B Phase 3) -------------
//
// One constant per distinct reason the classifier can reach a verdict.
// Kept as plain string constants (not an enum) because this set is
// expected to grow as Construction 029-C+ add more nuanced preflight
// checks; enums would force a class-wide update for every addition, plain
// constants do not.

const REASON_NO_CONTENT_AT_ALL               = 'NO_CONTENT_AT_ALL';
const REASON_ONLY_KNOWN_ASTREA_CONTENT       = 'ONLY_KNOWN_ASTREA_CONTENT';
const REASON_IMPORT_MARKER_COMPLETED         = 'IMPORT_MARKER_COMPLETED';
const REASON_UNACCOUNTED_PAGE_CONTENT        = 'UNACCOUNTED_PAGE_CONTENT';
const REASON_UNACCOUNTED_POST_CONTENT        = 'UNACCOUNTED_POST_CONTENT';
const REASON_UNACCOUNTED_ATTACHMENT          = 'UNACCOUNTED_ATTACHMENT';
const REASON_UNEXPECTED_CPT_CONTENT          = 'UNEXPECTED_CPT_CONTENT';
const REASON_IMPORT_MARKER_RUNNING           = 'IMPORT_MARKER_RUNNING';
const REASON_IMPORT_MARKER_FAILED            = 'IMPORT_MARKER_FAILED';
const REASON_INCONSISTENT_COMPLETED_MARKER   = 'INCONSISTENT_COMPLETED_MARKER';
const REASON_INCONSISTENT_GENERATED_TRACKING = 'INCONSISTENT_GENERATED_TRACKING';
const REASON_MULTISITE_UNSUPPORTED           = 'MULTISITE_UNSUPPORTED';
