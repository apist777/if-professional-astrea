<?php
/**
 * ASTREA Starter Import — aggregate Preflight result
 * (Construction 029-C Phase 1/2).
 *
 * Responsibility boundary (Construction 029-C order, "IMPORTANT
 * RESPONSIBILITY BOUNDARY"): this is Preflight's own result shape, kept
 * deliberately separate from Construction 029-B's `StateResult` (site
 * state) and from Current User Permission — a `PreflightResult`
 * references a `StateResult` as one of its checks' evidence, but is never
 * confused for it.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * The aggregate outcome of running every Preflight check.
 */
final class PreflightResult {

	/**
	 * Constructs an immutable aggregate result.
	 *
	 * @param CheckStatus      $status     Aggregate status: BLOCK if any check blocked, else WARNING if any warned, else PASS.
	 * @param bool             $can_start  Whether a new Starter Import operation may begin. False whenever $status is BLOCK.
	 * @param PreflightCheck[] $checks     Every check that ran, in the order Construction 029-C Phase 3 defines them.
	 * @param PreflightCheck[] $warnings   Convenience subset of $checks with WARNING status.
	 * @param PreflightCheck[] $blockers   Convenience subset of $checks with BLOCK status.
	 * @param StateResult      $site_state The Construction 029-B site state this Preflight was evaluated against (CHECK 01's evidence, kept accessible at the top level since so much else depends on it).
	 */
	public function __construct(
		public readonly CheckStatus $status,
		public readonly bool $can_start,
		public readonly array $checks,
		public readonly array $warnings,
		public readonly array $blockers,
		public readonly StateResult $site_state
	) {
	}

	/**
	 * Builds a PreflightResult from a flat list of checks, deriving the
	 * aggregate status/can_start/warnings/blockers deterministically —
	 * callers never compute these themselves, avoiding drift between the
	 * per-check statuses and the aggregate.
	 *
	 * @param PreflightCheck[] $checks     Every check that ran.
	 * @param StateResult      $site_state The site state those checks were evaluated against.
	 * @return PreflightResult
	 */
	public static function from_checks( array $checks, StateResult $site_state ): PreflightResult {
		$blockers = array();
		$warnings = array();

		foreach ( $checks as $check ) {
			if ( CheckStatus::BLOCK === $check->status ) {
				$blockers[] = $check;
			} elseif ( CheckStatus::WARNING === $check->status ) {
				$warnings[] = $check;
			}
		}

		if ( $blockers ) {
			$status = CheckStatus::BLOCK;
		} elseif ( $warnings ) {
			$status = CheckStatus::WARNING;
		} else {
			$status = CheckStatus::PASS;
		}

		return new self( $status, empty( $blockers ), $checks, $warnings, $blockers, $site_state );
	}
}
