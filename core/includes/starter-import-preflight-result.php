<?php
/**
 * ASTREA Starter Import — Preflight status enum (Construction 029-C Phase 1/2).
 *
 * @package Astrea\Core
 */

namespace Astrea\Core\StarterImport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Disallow direct access.
}

/**
 * Status of one Preflight check, or of the aggregate Preflight result.
 */
enum CheckStatus: string {
	case PASS    = 'pass';
	case WARNING = 'warning';
	case BLOCK   = 'block';
}
