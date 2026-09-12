<?php
/**
 * ASTREA Starter Import — pure domain classification logic
 * (Construction 029-B Phase 1 "Domain" responsibility).
 *
 * `classify()` is the ONLY function in this file, and it is a pure
 * function: given an `Evidence` snapshot, it returns a `StateResult` and
 * touches nothing else — no database read, no database write, no option
 * mutation, no side effect of any kind (Construction 029-B Phase 10: pure
 * detection). This is what makes it exhaustively unit-testable without a
 * WordPress database: every test in Construction 029-B's test matrix
 * (Phase 11) can construct an `Evidence` object directly and assert on the
 * `StateResult` it produces.
 *
 * Fail-closed policy (Construction 029-B Phase 9): whenever the evidence
 * does not cleanly and unambiguously match FRESH or ASTREA_READY, the
 * result must NOT allow a new import. When in doubt, this function always
 * resolves toward the more restrictive state — never toward "probably
 * fine". False positives (calling a non-fresh site FRESH) are the one
 * class of mistake this module exists to prevent (Phase 12); a false
 * negative (blocking an actually-fresh site) is merely inconvenient and
 * always safe to fall back to here.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Classifies a site's current state from previously-gathered Evidence.
 *
 * Order of checks matters and is deliberate:
 *   1. Environment-level BLOCKs that make everything else moot (Multisite).
 *   2. The Starter Import marker itself, since it is the most direct,
 *      explicit signal — but only trusted after an internal-consistency
 *      check (Phase 9, CASE 09: a marker that contradicts the content it
 *      claims to have produced is UNKNOWN, not taken at face value).
 *   3. Only once the marker has been ruled out (not present / not_started)
 *      do we fall through to inferring state from content shape, and even
 *      then, any single unaccounted-for piece of content is enough to
 *      block (Phase 12: minimize false positives, not false negatives).
 *
 * @param Evidence $evidence Facts gathered by Detection\gather_evidence().
 * @return StateResult
 */
function classify( Evidence $evidence ): StateResult {

	// 1. Environment-level BLOCK: Multisite is out of scope for the first
	// release (Construction 029-B Phase 13) — never attempt to reason
	// about per-site vs. network-wide state here.
	if ( $evidence->is_multisite ) {
		return new StateResult(
			SiteState::UNKNOWN,
			false,
			REASON_MULTISITE_UNSUPPORTED,
			'Multisite is not supported by Starter Import in this release.',
			$evidence
		);
	}

	// 2. Trust the marker, but only if it is internally consistent with
	// the content that a real completed import would have produced.
	if ( ImportStatus::COMPLETED === $evidence->import_status ) {
		$has_generated_content = $evidence->import_generated_marker_present
			|| $evidence->total_astrea_cpt_count() > 0;

		if ( ! $has_generated_content ) {
			return new StateResult(
				SiteState::UNKNOWN,
				false,
				REASON_INCONSISTENT_COMPLETED_MARKER,
				'Import status is "completed" but no generated-object tracking or ASTREA content was found.',
				$evidence
			);
		}

		return new StateResult(
			SiteState::COMPLETED,
			false,
			REASON_IMPORT_MARKER_COMPLETED,
			'A Starter Import has already completed on this site.',
			$evidence
		);
	}

	if ( ImportStatus::RUNNING === $evidence->import_status ) {
		return new StateResult(
			SiteState::PARTIAL,
			false,
			REASON_IMPORT_MARKER_RUNNING,
			'A Starter Import is currently marked as running.',
			$evidence
		);
	}

	if ( ImportStatus::FAILED === $evidence->import_status ) {
		return new StateResult(
			SiteState::PARTIAL,
			false,
			REASON_IMPORT_MARKER_FAILED,
			'A previous Starter Import is marked as failed and did not complete.',
			$evidence
		);
	}

	// From here, import_status is NOT_STARTED or absent (null) — the
	// marker gives no signal either way, so state must be inferred from
	// content shape alone. Any of Core's own bookkeeping being internally
	// broken is itself treated as UNKNOWN rather than silently ignored.
	if ( $evidence->has_any_astrea_generated_content() && ! $evidence->astrea_generated_pages_consistent() ) {
		return new StateResult(
			SiteState::UNKNOWN,
			false,
			REASON_INCONSISTENT_GENERATED_TRACKING,
			'ASTREA generated-page tracking exists but does not match reality.',
			$evidence
		);
	}

	// A single unaccounted-for piece of content of ANY kind is enough to
	// block — deliberately not weighing "how much" content there is, to
	// keep the false-positive rate at zero (Phase 12).
	if ( $evidence->unaccounted_page_count > 0 ) {
		return new StateResult(
			SiteState::EXISTING_CONTENT,
			false,
			REASON_UNACCOUNTED_PAGE_CONTENT,
			'One or more pages exist that are neither a known WordPress default nor ASTREA-generated.',
			$evidence
		);
	}

	if ( $evidence->unaccounted_post_count > 0 ) {
		return new StateResult(
			SiteState::EXISTING_CONTENT,
			false,
			REASON_UNACCOUNTED_POST_CONTENT,
			'One or more posts exist beyond the untouched default "Hello world!" post.',
			$evidence
		);
	}

	if ( $evidence->attachment_count > 0 ) {
		return new StateResult(
			SiteState::EXISTING_CONTENT,
			false,
			REASON_UNACCOUNTED_ATTACHMENT,
			'One or more Media Library attachments exist. A genuinely fresh install has none.',
			$evidence
		);
	}

	if ( $evidence->total_astrea_cpt_count() > 0 ) {
		// ASTREA CPT content exists but there is no completed-or-running
		// marker to explain it — either created by hand through the
		// admin UI, or left over from an environment predating the
		// marker system. Either way, this is not a state a fresh new
		// Starter Import may run on top of.
		return new StateResult(
			SiteState::EXISTING_CONTENT,
			false,
			REASON_UNEXPECTED_CPT_CONTENT,
			'ASTREA content type entries exist without a matching import marker.',
			$evidence
		);
	}

	// Nothing unaccounted for. Distinguish FRESH (nothing ASTREA has
	// touched yet) from ASTREA_READY (only Core's own known generated
	// content, and it checked out consistent above) — both are import
	// candidates, but the distinction is useful diagnostic information.
	if ( $evidence->has_any_astrea_generated_content() ) {
		return new StateResult(
			SiteState::ASTREA_READY,
			true,
			REASON_ONLY_KNOWN_ASTREA_CONTENT,
			'Only known ASTREA Theme/Core-generated content is present.',
			$evidence
		);
	}

	return new StateResult(
		SiteState::FRESH,
		true,
		REASON_NO_CONTENT_AT_ALL,
		'No meaningful content of any kind was found.',
		$evidence
	);
}
