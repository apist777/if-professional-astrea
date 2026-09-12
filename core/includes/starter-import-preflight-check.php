<?php
/**
 * ASTREA Starter Import — one individual Preflight check outcome
 * (Construction 029-C Phase 1/2).
 *
 * Mirrors the shape Construction 029-B established for `StateResult`:
 * never a bare boolean, always a structured, machine-readable result.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * One individual, machine-readable Preflight check outcome.
 */
final class PreflightCheck {

	/**
	 * Constructs an immutable check outcome.
	 *
	 * @param string               $id         Stable check identifier, e.g. "site_state", "php_version".
	 * @param CheckStatus          $status     PASS / WARNING / BLOCK.
	 * @param string               $code       Machine-readable reason code for this specific check.
	 * @param string               $diagnostic Short, English, developer-facing diagnostic (not Admin UI copy — Construction 029-H's concern).
	 * @param array<string, mixed> $evidence   Supporting facts (e.g. detected version numbers), for display/debugging.
	 */
	public function __construct(
		public readonly string $id,
		public readonly CheckStatus $status,
		public readonly string $code,
		public readonly string $diagnostic,
		public readonly array $evidence = array()
	) {
	}
}
