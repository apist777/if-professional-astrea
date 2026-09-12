<?php
/**
 * ASTREA Starter Import — ownership marker read contract
 * (Construction 029-B Phase 5/7).
 *
 * Defines the shape of the Starter Import ownership marker (option names
 * and the `ImportStatus` value set) that Construction 029-A proposed and
 * this Construction treats as a READ-ONLY contract: nothing in
 * Construction 029-B ever calls `update_option()`/`add_option()`/
 * `delete_option()` on any of these. Construction 029-C owns the write
 * side (Preflight + marker lifecycle) — see starter-import-detection.php
 * for where these are read.
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Starter Import ownership marker status values.
 */
enum ImportStatus: string {
	case NOT_STARTED = 'not_started';
	case RUNNING     = 'running';
	case FAILED      = 'failed';
	case COMPLETED   = 'completed';
}

/**
 * Option name for the Starter Import status marker (Construction 029-A
 * Phase 5). Read-only in this Construction.
 */
const IMPORT_STATUS_OPTION = 'astrea_starter_import_status';

/**
 * Option name for the Starter Package version the site was imported with
 * (Construction 029-A Phase 5/17). Read-only in this Construction.
 */
const IMPORT_VERSION_OPTION = 'astrea_starter_import_version';

/**
 * Option name for the generated-object ownership map (Construction 029-A
 * Phase 5's `astrea_starter_import_generated`). Construction 029-B only
 * reads this to detect internal inconsistency (Phase 9, CASE 09); it is
 * never written here. The exact per-CPT shape is a Construction 029-C
 * concern — 029-B treats it as an opaque array and only asks "is it
 * present and non-empty".
 */
const IMPORT_GENERATED_OPTION = 'astrea_starter_import_generated';
