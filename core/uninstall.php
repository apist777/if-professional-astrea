<?php
/**
 * Uninstall handler for ASTREA Core.
 *
 * Per Decision 019 (docs/specifications/04_astrea_free_v1_preconstruction_decisions.md),
 * complete deletion of Core-owned data (Office Profile, Service, Price, FAQ,
 * Contact, etc.) must only happen through the plugin's own explicit,
 * confirmed deletion flow — never merely because the plugin was deleted from
 * the Plugins screen. This construction-phase skeleton stores no data yet,
 * so there is nothing to remove here; this file exists to establish the
 * correct entry point before any data model is added.
 *
 * @package Astrea\Core
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Intentionally no-op: no Core-owned data exists yet in this construction
// phase. A future Phase will add an explicit, user-confirmed deletion flow
// here — plain WordPress "Delete" must keep behaving as a safe default.
