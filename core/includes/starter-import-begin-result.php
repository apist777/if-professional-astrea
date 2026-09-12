<?php
/**
 * ASTREA Starter Import — begin_operation() result value object
 * (Construction 029-C Phase 15).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * The outcome of attempting to begin a new Starter Import operation.
 */
final class BeginOperationResult {

	/**
	 * Constructs an immutable begin-operation outcome.
	 *
	 * @param bool                 $success      Whether the operation started successfully.
	 * @param string|null          $operation_id The new operation's ID, only when $success is true.
	 * @param string               $code         Machine-readable outcome code.
	 * @param string               $diagnostic   Short developer-facing diagnostic.
	 * @param PreflightResult|null $preflight    The Preflight result this attempt was evaluated against, when available.
	 */
	public function __construct(
		public readonly bool $success,
		public readonly ?string $operation_id,
		public readonly string $code,
		public readonly string $diagnostic,
		public readonly ?PreflightResult $preflight = null
	) {
	}
}
